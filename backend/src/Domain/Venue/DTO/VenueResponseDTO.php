<?php

namespace App\Domain\Venue\DTO;

class VenueResponseDTO
{
    // ===== AI GENERATED: VenueResponseDTO =====
    // Purpose: Response DTO for venue data
    // Inputs: entity properties
    // Returns: normalized array

    public int $venueIdentifier;
    public string $venueName;
    public ?string $venueLocation;
    public ?int $capacityLimit;
    public string $availabilityStatus;
    public string $createdTimestamp;

    public function __construct(int $venueIdentifier, string $venueName, ?string $venueLocation, ?int $capacityLimit, string $availabilityStatus, string $createdTimestamp)
    {
        $this->venueIdentifier = $venueIdentifier;
        $this->venueName = $venueName;
        $this->venueLocation = $venueLocation;
        $this->capacityLimit = $capacityLimit;
        $this->availabilityStatus = $availabilityStatus;
        $this->createdTimestamp = $createdTimestamp;
    }

    public function toResponseArray(): array
    {
        return [
            'venueIdentifier' => $this->venueIdentifier,
            'venueName' => $this->venueName,
            'venueLocation' => $this->venueLocation,
            'capacityLimit' => $this->capacityLimit,
            'availabilityStatus' => $this->availabilityStatus,
            'createdTimestamp' => $this->createdTimestamp,
        ];
    }
}
