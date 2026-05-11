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
    public ?string $floorLevel;
    public ?int $capacityLimit;
    public string $availabilityStatus;
    public ?string $description;
    public ?string $imageUrl;
    public string $createdTimestamp;

    public function __construct(int $venueIdentifier, string $venueName, ?string $venueLocation, ?string $floorLevel, ?int $capacityLimit, string $availabilityStatus, ?string $description, ?string $imageUrl, string $createdTimestamp)
    {
        $this->venueIdentifier = $venueIdentifier;
        $this->venueName = $venueName;
        $this->venueLocation = $venueLocation;
        $this->floorLevel = $floorLevel;
        $this->capacityLimit = $capacityLimit;
        $this->availabilityStatus = $availabilityStatus;
        $this->description = $description;
        $this->imageUrl = $imageUrl;
        $this->createdTimestamp = $createdTimestamp;
    }

    public function toResponseArray(): array
    {
        return [
            'venueIdentifier' => $this->venueIdentifier,
            'venueName' => $this->venueName,
            'venueLocation' => $this->venueLocation,
            'floorLevel' => $this->floorLevel,
            'capacityLimit' => $this->capacityLimit,
            'availabilityStatus' => $this->availabilityStatus,
            'description' => $this->description,
            'imageUrl' => $this->imageUrl,
            'createdTimestamp' => $this->createdTimestamp,
        ];
    }
}
