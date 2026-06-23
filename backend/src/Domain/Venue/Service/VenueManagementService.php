<?php

namespace App\Domain\Venue\Service;

use App\Domain\Venue\DTO\VenueResponseDTO;
use App\Domain\Venue\Entity\VenueEntity;
use App\Domain\Venue\Repository\VenueRepository;
use App\Domain\Reservation\Repository\ReservationRepository;
use App\Domain\Reservation\Entity\ReservationEntity;
use App\Domain\Reservation\Service\ReservationPolicyConfigService;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;
use App\Shared\Utils\AppClock;
use Doctrine\DBAL\Connection;

class VenueManagementService
{
    private const ALLOWED_OPERATIONAL_STATUSES = ['Active', 'Inactive', 'Maintenance'];
    private const ALLOWED_AVAILABILITY_STATUSES = ['Available', 'Unavailable'];
    private const MAX_IMAGE_URL_LENGTH = 1_600_000;

    private VenueRepository $venueRepository;
    private ReservationRepository $reservationRepository;
    private bool $venueSchemaEnsured = false;

    public function __construct(
        VenueRepository $venueRepository,
        ReservationRepository $reservationRepository,
        private readonly Connection $connection,
        private readonly ReservationPolicyConfigService $reservationPolicyConfigService
    )
    {
        $this->venueRepository = $venueRepository;
        $this->reservationRepository = $reservationRepository;
    }

    /** @return VenueResponseDTO[] */
    public function getAllVenues(?string $selectedDate = null, ?string $startTime = null, ?string $endTime = null): array
    {
        $this->ensureVenueSchemaReady();
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
        $this->ensureVenueSchemaReady();
        $entity = $this->venueRepository->find($venueIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Venue not found: ' . $venueIdentifier);
        }

        return $this->transformEntityToDTO($entity, $this->resolveAvailabilityStatus($entity->getOperationalStatus(), $entity->getAvailabilityDate() ?? AppClock::now()));
    }

    public function createVenue(string $venueName, ?string $venueLocation, ?string $floorLevel, ?int $capacityLimit, ?string $availabilityDate, ?string $operationalStatus, ?string $availabilityStatus, ?string $description, ?string $imageUrl): VenueResponseDTO
    {
        $this->ensureVenueSchemaReady();
        [$normalizedVenueName, $normalizedVenueLocation, $normalizedFloorLevel, $normalizedCapacity, $resolvedAvailabilityDate, $resolvedOperationalStatus, $resolvedAvailabilityStatus, $normalizedDescription, $normalizedImageUrl] = $this->validateAndNormalizeVenuePayload(
            venueName: $venueName,
            venueLocation: $venueLocation,
            floorLevel: $floorLevel,
            capacityLimit: $capacityLimit,
            availabilityDate: $availabilityDate,
            operationalStatus: $operationalStatus,
            availabilityStatus: $availabilityStatus,
            description: $description,
            imageUrl: $imageUrl
        );

        $this->ensureUniqueVenueName($normalizedVenueName);

        $entity = new VenueEntity();
        $entity->setVenueName($normalizedVenueName);
        $entity->setVenueLocation($normalizedVenueLocation);
        $entity->setFloorLevel($normalizedFloorLevel);
        $entity->setCapacityLimit($normalizedCapacity);
        $entity->setAvailabilityDate($resolvedAvailabilityDate);
        $entity->setOperationalStatus($resolvedOperationalStatus);
        $entity->setAvailabilityStatus($resolvedAvailabilityStatus);
        $entity->setDescription($normalizedDescription);
        $entity->setImageUrl($normalizedImageUrl);
        $this->venueRepository->persistVenue($entity);
        return $this->transformEntityToDTO(
            $entity,
            $this->resolveAvailabilityStatus($entity->getOperationalStatus(), $entity->getAvailabilityDate() ?? AppClock::now())
        );
    }

    public function updateVenue(int $venueIdentifier, string $venueName, ?string $venueLocation, ?string $floorLevel, ?int $capacityLimit, ?string $availabilityDate, ?string $operationalStatus, ?string $availabilityStatus, ?string $description, ?string $imageUrl, bool $replaceImage = false): VenueResponseDTO
    {
        $this->ensureVenueSchemaReady();
        $entity = $this->venueRepository->find($venueIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Venue not found: ' . $venueIdentifier);
        }

        [$normalizedVenueName, $normalizedVenueLocation, $normalizedFloorLevel, $normalizedCapacity, $resolvedAvailabilityDate, $resolvedOperationalStatus, $resolvedAvailabilityStatus, $normalizedDescription, $normalizedImageUrl] = $this->validateAndNormalizeVenuePayload(
            venueName: $venueName,
            venueLocation: $venueLocation,
            floorLevel: $floorLevel,
            capacityLimit: $capacityLimit,
            availabilityDate: $availabilityDate,
            operationalStatus: $operationalStatus,
            availabilityStatus: $availabilityStatus,
            description: $description,
            imageUrl: $imageUrl
        );

        $this->ensureUniqueVenueName($normalizedVenueName, $entity->getVenueIdentifier());

        $entity->setVenueName($normalizedVenueName);
        $entity->setVenueLocation($normalizedVenueLocation);
        $entity->setFloorLevel($normalizedFloorLevel);
        $entity->setCapacityLimit($normalizedCapacity);
        $entity->setAvailabilityDate($resolvedAvailabilityDate);
        $entity->setOperationalStatus($resolvedOperationalStatus);
        $entity->setAvailabilityStatus($resolvedAvailabilityStatus);
        $entity->setDescription($normalizedDescription);
        if ($replaceImage) {
            $entity->setImageUrl($normalizedImageUrl);
        }
        $this->venueRepository->persistVenue($entity);
        return $this->transformEntityToDTO(
            $entity,
            $this->resolveAvailabilityStatus($entity->getOperationalStatus(), $entity->getAvailabilityDate() ?? AppClock::now())
        );
    }

    public function deleteVenue(int $venueIdentifier): void
    {
        $this->ensureVenueSchemaReady();
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
        ?string $floorLevel,
        ?int $capacityLimit,
        ?string $availabilityDate,
        ?string $operationalStatus,
        ?string $availabilityStatus,
        ?string $description,
        ?string $imageUrl
    ): array {
        $normalizedVenueName = trim($venueName);
        $normalizedVenueLocation = trim((string)($venueLocation ?? ''));
        $normalizedFloorLevel = trim((string)($floorLevel ?? ''));
        $normalizedDescription = trim((string)($description ?? ''));
        $normalizedImageUrl = trim((string)($imageUrl ?? ''));
        $resolvedOperationalStatus = trim((string)($operationalStatus ?? ''));
        $resolvedAvailabilityStatus = trim((string)($availabilityStatus ?? ''));

        if (mb_strlen($normalizedVenueName) < 2) {
            throw new DomainValidationException('Venue name must be at least 2 characters.');
        }

        if (mb_strlen($normalizedVenueLocation) < 2) {
            throw new DomainValidationException('Location must be at least 2 characters.');
        }

        if ($normalizedFloorLevel === '') {
            throw new DomainValidationException('Floor level is required.');
        }

        if ($capacityLimit === null || $capacityLimit <= 0) {
            throw new DomainValidationException('Capacity must be a whole number greater than zero.');
        }

        if ($availabilityDate === null || trim($availabilityDate) === '') {
            throw new DomainValidationException('Availability date is required.');
        }

        try {
            $resolvedAvailabilityDate = new \DateTime(trim($availabilityDate));
        } catch (\Throwable) {
            throw new DomainValidationException('Availability date is invalid.');
        }

        if (!in_array($resolvedOperationalStatus, self::ALLOWED_OPERATIONAL_STATUSES, true)) {
            throw new DomainValidationException('Operational status is required.');
        }

        if (!in_array($resolvedAvailabilityStatus, self::ALLOWED_AVAILABILITY_STATUSES, true)) {
            throw new DomainValidationException('Room availability is required.');
        }

        if ($normalizedImageUrl !== '' && !$this->isValidJpgImagePayload($normalizedImageUrl)) {
            throw new DomainValidationException('Venue photo must be a .jpg image only.');
        }

        if ($normalizedImageUrl !== '' && strlen($normalizedImageUrl) > self::MAX_IMAGE_URL_LENGTH) {
            throw new DomainValidationException('Venue photo is too large. Please upload a smaller JPG image.');
        }

        return [
            $normalizedVenueName,
            $normalizedVenueLocation,
            $normalizedFloorLevel,
            $capacityLimit,
            $resolvedAvailabilityDate,
            $resolvedOperationalStatus,
            $resolvedAvailabilityStatus,
            $normalizedDescription === '' ? null : $normalizedDescription,
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

        $today = AppClock::now()->setTime(0, 0);
        $availableOn = \DateTimeImmutable::createFromInterface($availabilityDate)
            ->setTimezone(AppClock::timezone())
            ->setTime(0, 0);

        return $availableOn > $today ? 'Unavailable' : 'Available';
    }

    /** @param VenueEntity[] $entities
     *  @return VenueResponseDTO[]
     */
    private function transformEntitiesToDTOs(array $entities, ?string $selectedDate, ?string $startTime, ?string $endTime): array
    {
        $availabilityWindow = $this->buildAvailabilityWindow($selectedDate, $startTime, $endTime);
        [$reservationMap, $scheduleBlockMap] = $this->buildBlockedTimeMaps($entities, $availabilityWindow);

        return array_map(function (VenueEntity $entity) use ($reservationMap, $scheduleBlockMap): VenueResponseDTO {
            $reservationRows = $reservationMap[$entity->getVenueIdentifier()] ?? [];
            $scheduleBlocks = $scheduleBlockMap[$entity->getVenueIdentifier()] ?? [];
            $availabilityStatus = $this->resolveAvailabilityStatus(
                $entity->getOperationalStatus(),
                $entity->getAvailabilityDate() ?? AppClock::now()
            );

            if ($availabilityStatus === 'Available' && ($reservationRows !== [] || $scheduleBlocks !== [])) {
                $availabilityStatus = 'Unavailable';
            }

            return $this->transformEntityToDTO(
                $entity,
                $availabilityStatus,
                [
                    ...array_map([$this, 'formatReservationWindow'], $reservationRows),
                    ...array_map([$this, 'formatScheduleBlockWindow'], $scheduleBlocks),
                ]
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
            $selectedDateValue = new \DateTimeImmutable($normalizedDate, AppClock::timezone());
        } catch (\Throwable) {
            return null;
        }

        $normalizedStartTime = trim((string) ($startTime ?? ''));
        $normalizedEndTime = trim((string) ($endTime ?? ''));

        if ($normalizedStartTime !== '' && $normalizedEndTime !== '') {
            try {
                $rangeStart = new \DateTimeImmutable(sprintf('%s %s', $normalizedDate, $normalizedStartTime), AppClock::timezone());
                $rangeEnd = new \DateTimeImmutable(sprintf('%s %s', $normalizedDate, $normalizedEndTime), AppClock::timezone());
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
    private function buildBlockedTimeMaps(array $entities, ?array $availabilityWindow): array
    {
        if ($availabilityWindow === null) {
            return [[], []];
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
        $scheduleBlocks = $this->reservationPolicyConfigService->findScheduleBlocksOverlappingRange(
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

        $scheduleBlockMap = [];
        foreach ($scheduleBlocks as $scheduleBlock) {
            $venueIdentifier = (int) ($scheduleBlock['venueIdentifier'] ?? 0);
            if ($venueIdentifier <= 0) {
                continue;
            }

            if (!isset($scheduleBlockMap[$venueIdentifier])) {
                $scheduleBlockMap[$venueIdentifier] = [];
            }

            $scheduleBlockMap[$venueIdentifier][] = $scheduleBlock;
        }

        return [$reservationMap, $scheduleBlockMap];
    }

    private function formatReservationWindow(ReservationEntity $reservation): string
    {
        $startDateTime = $reservation->getEventDateTime();
        $endDateTime = $reservation->getEndDateTime() ?? $startDateTime;

        return sprintf('%s - %s', $startDateTime->format('g:i A'), $endDateTime->format('g:i A'));
    }

    private function formatScheduleBlockWindow(array $scheduleBlock): string
    {
        $label = trim((string) ($scheduleBlock['blockLabel'] ?? 'Class Schedule'));
        $startTime = trim((string) ($scheduleBlock['startTime'] ?? ''));
        $endTime = trim((string) ($scheduleBlock['endTime'] ?? ''));

        try {
            $formattedStart = (new \DateTimeImmutable(sprintf('1970-01-01 %s', $startTime), AppClock::timezone()))->format('g:i A');
            $formattedEnd = (new \DateTimeImmutable(sprintf('1970-01-01 %s', $endTime), AppClock::timezone()))->format('g:i A');
            return sprintf('%s: %s - %s', $label, $formattedStart, $formattedEnd);
        } catch (\Throwable) {
            return sprintf('%s: %s - %s', $label, $startTime, $endTime);
        }
    }

    private function isValidJpgImagePayload(string $imageUrl): bool
    {
        if (str_starts_with($imageUrl, 'data:image/jpeg;base64,')) {
            return true;
        }

        $normalizedImageUrl = strtolower($imageUrl);
        return str_ends_with($normalizedImageUrl, '.jpg') || str_ends_with($normalizedImageUrl, '.jpeg');
    }

    private function ensureVenueSchemaReady(): void
    {
        if ($this->venueSchemaEnsured) {
            return;
        }

        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist(['venues'])) {
            $this->venueSchemaEnsured = true;
            return;
        }

        $this->connection->executeStatement("ALTER TABLE venues ADD COLUMN IF NOT EXISTS floor_level VARCHAR(50) DEFAULT NULL");
        $this->connection->executeStatement("ALTER TABLE venues ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL");
        $this->connection->executeStatement("ALTER TABLE venues ADD COLUMN IF NOT EXISTS availability_date DATE DEFAULT CURRENT_DATE");
        $this->connection->executeStatement("ALTER TABLE venues ADD COLUMN IF NOT EXISTS operational_status VARCHAR(50) DEFAULT 'Active'");
        $this->connection->executeStatement("ALTER TABLE venues ADD COLUMN IF NOT EXISTS image_url TEXT DEFAULT NULL");
        $this->connection->executeStatement("ALTER TABLE venues ALTER COLUMN image_url TYPE TEXT");
        $this->connection->executeStatement("UPDATE venues SET availability_date = CURRENT_DATE WHERE availability_date IS NULL");
        $this->connection->executeStatement("UPDATE venues SET operational_status = 'Active' WHERE operational_status IS NULL OR operational_status = ''");
        $this->connection->executeStatement("UPDATE venues SET availability_status = 'Available' WHERE availability_status IS NULL OR availability_status = ''");
        $duplicateVenueNameCount = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM (
                SELECT LOWER(venue_name)
                FROM venues
                GROUP BY LOWER(venue_name)
                HAVING COUNT(*) > 1
            ) duplicate_venue_names"
        );

        if ($duplicateVenueNameCount === 0) {
            $this->connection->executeStatement("CREATE UNIQUE INDEX IF NOT EXISTS uniq_venues_lower_venue_name ON venues (LOWER(venue_name))");
        }

        $this->venueSchemaEnsured = true;
    }
}
