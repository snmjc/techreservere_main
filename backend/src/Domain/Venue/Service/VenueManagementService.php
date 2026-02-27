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

    public function createVenue(string $venueName, ?string $venueLocation, ?int $capacityLimit): VenueResponseDTO
    {
        if (empty($venueName)) {
            throw new DomainValidationException('Venue name is required.');
        }
        $entity = new VenueEntity();
        $entity->setVenueName($venueName);
        $entity->setVenueLocation($venueLocation);
        $entity->setCapacityLimit($capacityLimit);
        $this->venueRepository->persistVenue($entity);
        return $this->transformEntityToDTO($entity);
    }

    private function transformEntityToDTO(VenueEntity $entity): VenueResponseDTO
    {
        return new VenueResponseDTO(
            venueIdentifier: $entity->getVenueIdentifier(),
            venueName: $entity->getVenueName(),
            venueLocation: $entity->getVenueLocation(),
            capacityLimit: $entity->getCapacityLimit(),
            availabilityStatus: $entity->getAvailabilityStatus(),
            createdTimestamp: $entity->getCreatedTimestamp()->format(\DateTime::ATOM)
        );
    }
}
