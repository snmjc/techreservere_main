<?php

namespace App\Domain\Venue\Service;

use App\Domain\Venue\DTO\VenueResponseDTO;
use App\Domain\Venue\Entity\VenueEntity;
use App\Domain\Venue\Repository\VenueRepository;
use App\Domain\Reservation\Repository\ReservationRepository;
use App\Domain\Reservation\Entity\ReservationEntity;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;

class VenueManagementService
{
    private const ALLOWED_OPERATIONAL_STATUSES = ['Active', 'Inactive', 'Maintenance'];

    private VenueRepository $venueRepository;
    private ReservationRepository $reservationRepository;

    public function __construct(VenueRepository $venueRepository, ReservationRepository $reservationRepository)
    {
        $this->venueRepository = $venueRepository;
        $this->reservationRepository = $reservationRepository;
    }

    /** @return VenueResponseDTO[] */
    public function getAllVenues(?string $selectedDate = null, ?string $startTime = null, ?string $endTime = null): array
    {
        $entities = $this->venueRepository->findAllVenues();
        return $this->transformEntitiesToDTOs($entities, $selectedDate, $startTime, $endTime);
    }

    /** @return VenueResponseDTO[] */
    public function getAvailableVenues(?string $selectedDate = null, ?string $startTime = null, ?string $endTime = null): array
    {
        $dtos = $this->getAllVenues($selectedDate, $startTime, $endTime);

        return array_values(array_filter(
            $dtos,
            static fn (VenueResponseDTO $venueDTO): bool => $venueDTO->availabilityStatus === 'Available'
        ));
    }

    public function getVenueById(int $venueIdentifier): VenueResponseDTO
    {
        $entity = $this->venueRepository->find($venueIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Venue not found: ' . $venueIdentifier);
        }

        return $this->transformEntityToDTO($entity, $entity->getAvailabilityStatus());
    }

    public function createVenue(string $venueName, ?string $venueLocation, ?string $floorLevel, ?int $capacityLimit, ?string $availabilityDate, ?string $operationalStatus, ?string $description, ?string $imageUrl): VenueResponseDTO
    {
        [$normalizedVenueName, $normalizedVenueLocation, $normalizedCapacity, $resolvedAvailabilityDate, $resolvedOperationalStatus, $normalizedImageUrl] = $this->validateAndNormalizeVenuePayload(
            venueName: $venueName,
            venueLocation: $venueLocation,
            capacityLimit: $capacityLimit,
            availabilityDate: $availabilityDate,
            operationalStatus: $operationalStatus,
            imageUrl: $imageUrl
        );

        $this->ensureUniqueVenueName($normalizedVenueName);

        $entity = new VenueEntity();
        $entity->setVenueName($normalizedVenueName);
        $entity->setVenueLocation($normalizedVenueLocation);
        $entity->setFloorLevel($floorLevel);
        $entity->setCapacityLimit($normalizedCapacity);
        $entity->setAvailabilityDate($resolvedAvailabilityDate);
        $entity->setOperationalStatus($resolvedOperationalStatus);
        $entity->setAvailabilityStatus($this->resolveAvailabilityStatus($resolvedOperationalStatus, $resolvedAvailabilityDate));
        $entity->setDescription($description);
        $entity->setImageUrl($normalizedImageUrl);
        $this->venueRepository->persistVenue($entity);
        return $this->transformEntityToDTO($entity);
    }

    public function updateVenue(int $venueIdentifier, string $venueName, ?string $venueLocation, ?string $floorLevel, ?int $capacityLimit, ?string $availabilityDate, ?string $operationalStatus, ?string $description, ?string $imageUrl): VenueResponseDTO
    {
        $entity = $this->venueRepository->find($venueIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Venue not found: ' . $venueIdentifier);
        }

        [$normalizedVenueName, $normalizedVenueLocation, $normalizedCapacity, $resolvedAvailabilityDate, $resolvedOperationalStatus, $normalizedImageUrl] = $this->validateAndNormalizeVenuePayload(
            venueName: $venueName,
            venueLocation: $venueLocation,
            capacityLimit: $capacityLimit,
            availabilityDate: $availabilityDate,
            operationalStatus: $operationalStatus,
            imageUrl: $imageUrl
        );

        $this->ensureUniqueVenueName($normalizedVenueName, $entity->getVenueIdentifier());

        $entity->setVenueName($normalizedVenueName);
        $entity->setVenueLocation($normalizedVenueLocation);
        $entity->setFloorLevel($floorLevel);
        $entity->setCapacityLimit($normalizedCapacity);
        $entity->setAvailabilityDate($resolvedAvailabilityDate);
        $entity->setOperationalStatus($resolvedOperationalStatus);
        $entity->setAvailabilityStatus($this->resolveAvailabilityStatus($resolvedOperationalStatus, $resolvedAvailabilityDate));
        $entity->setDescription($description);
        $entity->setImageUrl($normalizedImageUrl);
        $this->venueRepository->persistVenue($entity);
        return $this->transformEntityToDTO($entity);
    }

    public function deleteVenue(int $venueIdentifier): void
    {
        $entity = $this->venueRepository->find($venueIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Venue not found: ' . $venueIdentifier);
        }
        $this->venueRepository->removeVenue($entity);
    }

    private function transformEntityToDTO(VenueEntity $entity, string $availabilityStatus, array $reservationTimeRanges = []): VenueResponseDTO
    {
        return new VenueResponseDTO(
            venueIdentifier: $entity->getVenueIdentifier(),
            venueName: $entity->getVenueName(),
            venueLocation: $entity->getVenueLocation(),
            floorLevel: $entity->getFloorLevel(),
            capacityLimit: $entity->getCapacityLimit(),
            availabilityDate: $entity->getAvailabilityDate()?->format('Y-m-d'),
            operationalStatus: $entity->getOperationalStatus(),
            availabilityStatus: $availabilityStatus,
            description: $entity->getDescription(),
            imageUrl: $entity->getImageUrl(),
            createdTimestamp: $entity->getCreatedTimestamp()->format(\DateTime::ATOM),
            reservationTimeRanges: $reservationTimeRanges
        );
    }

    private function validateAndNormalizeVenuePayload(
        string $venueName,
        ?string $venueLocation,
        ?int $capacityLimit,
        ?string $availabilityDate,
        ?string $operationalStatus,
        ?string $imageUrl
    ): array {
        $normalizedVenueName = trim($venueName);
        $normalizedVenueLocation = trim((string)($venueLocation ?? ''));
        $normalizedImageUrl = trim((string)($imageUrl ?? ''));
        $resolvedOperationalStatus = trim((string)($operationalStatus ?? ''));

        if (mb_strlen($normalizedVenueName) < 2) {
            throw new DomainValidationException('Venue name must be at least 2 characters.');
        }

        if (mb_strlen($normalizedVenueLocation) < 2) {
            throw new DomainValidationException('Location must be at least 2 characters.');
        }

        if ($capacityLimit === null || $capacityLimit <= 0) {
            throw new DomainValidationException('Capacity must be a whole number greater than zero.');
        }

        if ($availabilityDate === null || trim($availabilityDate) === '') {
            throw new DomainValidationException('Availability date is required.');
        }

        try {
            $resolvedAvailabilityDate = new \DateTimeImmutable(trim($availabilityDate));
        } catch (\Throwable) {
            throw new DomainValidationException('Availability date is invalid.');
        }

        if (!in_array($resolvedOperationalStatus, self::ALLOWED_OPERATIONAL_STATUSES, true)) {
            throw new DomainValidationException('Operational status is required.');
        }

        if ($normalizedImageUrl !== '' && !$this->isValidJpgImagePayload($normalizedImageUrl)) {
            throw new DomainValidationException('Venue photo must be a .jpg image only.');
        }

        return [
            $normalizedVenueName,
            $normalizedVenueLocation,
            $capacityLimit,
            $resolvedAvailabilityDate,
            $resolvedOperationalStatus,
            $normalizedImageUrl === '' ? null : $normalizedImageUrl,
        ];
    }

    private function ensureUniqueVenueName(string $venueName, ?int $ignoreVenueIdentifier = null): void
    {
        $existingVenue = $this->venueRepository->findOneByVenueName($venueName);
        if ($existingVenue === null) {
            return;
        }

        if ($ignoreVenueIdentifier !== null && $existingVenue->getVenueIdentifier() === $ignoreVenueIdentifier) {
            return;
        }

        throw new DomainValidationException('Venue name already exists.');
    }

    private function resolveAvailabilityStatus(string $operationalStatus, \DateTimeInterface $availabilityDate): string
    {
        if ($operationalStatus !== 'Active') {
            return 'Unavailable';
        }

        $today = new \DateTimeImmutable('today');
        $availableOn = \DateTimeImmutable::createFromInterface($availabilityDate)->setTime(0, 0);

        return $availableOn > $today ? 'Unavailable' : 'Available';
    }

    /** @param VenueEntity[] $entities
     *  @return VenueResponseDTO[]
     */
    private function transformEntitiesToDTOs(array $entities, ?string $selectedDate, ?string $startTime, ?string $endTime): array
    {
        $availabilityWindow = $this->buildAvailabilityWindow($selectedDate, $startTime, $endTime);
        $reservationMap = $this->buildReservationMap($entities, $availabilityWindow);

        return array_map(function (VenueEntity $entity) use ($availabilityWindow, $reservationMap): VenueResponseDTO {
            $reservationRows = $reservationMap[$entity->getVenueIdentifier()] ?? [];
            $baseAvailabilityStatus = $entity->getAvailabilityStatus();

            if ($availabilityWindow !== null) {
                $baseAvailabilityStatus = $this->resolveAvailabilityStatusForSelectedDate(
                    $entity->getOperationalStatus(),
                    $entity->getAvailabilityDate(),
                    $availabilityWindow['selectedDate']
                );
            }

            $availabilityStatus = $baseAvailabilityStatus;
            if ($availabilityStatus === 'Available' && $reservationRows !== []) {
                $availabilityStatus = 'Unavailable';
            }

            return $this->transformEntityToDTO(
                $entity,
                $availabilityStatus,
                array_map([$this, 'formatReservationWindow'], $reservationRows)
            );
        }, $entities);
    }

    private function buildAvailabilityWindow(?string $selectedDate, ?string $startTime, ?string $endTime): ?array
    {
        $normalizedDate = trim((string) ($selectedDate ?? ''));
        if ($normalizedDate === '') {
            return null;
        }

        try {
            $selectedDateValue = new \DateTimeImmutable($normalizedDate);
        } catch (\Throwable) {
            return null;
        }

        $normalizedStartTime = trim((string) ($startTime ?? ''));
        $normalizedEndTime = trim((string) ($endTime ?? ''));

        if ($normalizedStartTime !== '' && $normalizedEndTime !== '') {
            try {
                $rangeStart = new \DateTimeImmutable(sprintf('%s %s', $normalizedDate, $normalizedStartTime));
                $rangeEnd = new \DateTimeImmutable(sprintf('%s %s', $normalizedDate, $normalizedEndTime));
            } catch (\Throwable) {
                $rangeStart = $selectedDateValue->setTime(0, 0);
                $rangeEnd = $selectedDateValue->setTime(23, 59, 59);
            }
        } else {
            $rangeStart = $selectedDateValue->setTime(0, 0);
            $rangeEnd = $selectedDateValue->setTime(23, 59, 59);
        }

        return [
            'selectedDate' => $selectedDateValue,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
        ];
    }

    /** @param VenueEntity[] $entities */
    private function buildReservationMap(array $entities, ?array $availabilityWindow): array
    {
        if ($availabilityWindow === null) {
            return [];
        }

        $venueIdentifiers = array_values(array_filter(
            array_map(static fn (VenueEntity $entity): ?int => $entity->getVenueIdentifier(), $entities),
            static fn (?int $venueIdentifier): bool => $venueIdentifier !== null
        ));

        $reservations = $this->reservationRepository->findVenueReservationsOverlappingRange(
            $venueIdentifiers,
            $availabilityWindow['rangeStart'],
            $availabilityWindow['rangeEnd']
        );

        $reservationMap = [];
        foreach ($reservations as $reservation) {
            $venueIdentifier = $reservation->getVenueIdentifier();
            if ($venueIdentifier === null) {
                continue;
            }

            if (!isset($reservationMap[$venueIdentifier])) {
                $reservationMap[$venueIdentifier] = [];
            }

            $reservationMap[$venueIdentifier][] = $reservation;
        }

        return $reservationMap;
    }

    private function formatReservationWindow(ReservationEntity $reservation): string
    {
        $startDateTime = $reservation->getEventDateTime();
        $endDateTime = $reservation->getEndDateTime() ?? $startDateTime;

        return sprintf('%s - %s', $startDateTime->format('g:i A'), $endDateTime->format('g:i A'));
    }

    private function resolveAvailabilityStatusForSelectedDate(string $operationalStatus, ?\DateTimeInterface $availabilityDate, \DateTimeInterface $selectedDate): string
    {
        if ($operationalStatus !== 'Active') {
            return 'Unavailable';
        }

        if ($availabilityDate === null) {
            return 'Unavailable';
        }

        $selectedDay = \DateTimeImmutable::createFromInterface($selectedDate)->setTime(0, 0);
        $availableOn = \DateTimeImmutable::createFromInterface($availabilityDate)->setTime(0, 0);

        return $availableOn > $selectedDay ? 'Unavailable' : 'Available';
    }

    private function isValidJpgImagePayload(string $imageUrl): bool
    {
        if (str_starts_with($imageUrl, 'data:image/jpeg;base64,')) {
            return true;
        }

        $normalizedImageUrl = strtolower($imageUrl);
        return str_ends_with($normalizedImageUrl, '.jpg') || str_ends_with($normalizedImageUrl, '.jpeg');
    }
}
