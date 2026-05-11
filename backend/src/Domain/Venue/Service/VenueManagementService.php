<?php

namespace App\Domain\Venue\Service;

use App\Domain\Venue\DTO\VenueResponseDTO;
use App\Domain\Venue\Entity\VenueEntity;
use App\Domain\Venue\Repository\VenueRepository;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;

class VenueManagementService
{
    private VenueRepository $venueRepository;

    public function __construct(VenueRepository $venueRepository)
    {
        $this->venueRepository = $venueRepository;
    }

    /** @return VenueResponseDTO[] */
    public function getAllVenues(): array
    {
        $entities = $this->venueRepository->findAllVenues();
        return array_map(fn($e) => $this->transformEntityToDTO($e), $entities);
    }

    /** @return VenueResponseDTO[] */
    public function getAvailableVenues(): array
    {
        $entities = $this->venueRepository->findAvailableVenues();
        return array_map(fn($e) => $this->transformEntityToDTO($e), $entities);
    }

    public function createVenue(string $venueName, ?string $venueLocation, ?string $floorLevel, ?int $capacityLimit, ?string $description, ?string $imageUrl): VenueResponseDTO
    {
        if (empty($venueName)) {
            throw new DomainValidationException('Venue name is required.');
        }
        $entity = new VenueEntity();
        $entity->setVenueName($venueName);
        $entity->setVenueLocation($venueLocation);
        $entity->setFloorLevel($floorLevel);
        $entity->setCapacityLimit($capacityLimit);
        $entity->setDescription($description);
        $entity->setImageUrl($imageUrl);
        $this->venueRepository->persistVenue($entity);
        return $this->transformEntityToDTO($entity);
    }

    public function updateVenue(int $venueIdentifier, string $venueName, ?string $venueLocation, ?string $floorLevel, ?int $capacityLimit, ?string $description, ?string $imageUrl, ?string $availabilityStatus): VenueResponseDTO
    {
        $entity = $this->venueRepository->find($venueIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Venue not found: ' . $venueIdentifier);
        }
        if (empty($venueName)) {
            throw new DomainValidationException('Venue name is required.');
        }
        $entity->setVenueName($venueName);
        $entity->setVenueLocation($venueLocation);
        $entity->setFloorLevel($floorLevel);
        $entity->setCapacityLimit($capacityLimit);
        $entity->setDescription($description);
        $entity->setImageUrl($imageUrl);
        if ($availabilityStatus !== null) {
            $entity->setAvailabilityStatus($availabilityStatus);
        }
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

    private function transformEntityToDTO(VenueEntity $entity): VenueResponseDTO
    {
        return new VenueResponseDTO(
            venueIdentifier: $entity->getVenueIdentifier(),
            venueName: $entity->getVenueName(),
            venueLocation: $entity->getVenueLocation(),
            floorLevel: $entity->getFloorLevel(),
            capacityLimit: $entity->getCapacityLimit(),
            availabilityStatus: $entity->getAvailabilityStatus(),
            description: $entity->getDescription(),
            imageUrl: $entity->getImageUrl(),
            createdTimestamp: $entity->getCreatedTimestamp()->format(\DateTime::ATOM)
        );
    }
}
