<?php

namespace App\Domain\Reservation\DTO;

class ReservationCreateRequestDTO
{
    // ===== AI GENERATED: ReservationCreateRequestDTO =====
    // Purpose: Request DTO for creating reservation
    // Inputs: all reservation submission fields
    // Returns: none (transport object)
    // Flow:
    // 1. Validated at controller
    // 2. Passed to ReservationCreateService

    public string $organizationName;
    public ?int $venueIdentifier;
    public array $requestedEquipmentList;
    public int $requestedQuantity;
    public string $eventDateTime;
    public string $purposeDescription;
    public string $activityType;
    public ?array $supportingDocuments;

    public function __construct(
        string $organizationName,
        ?int $venueIdentifier,
        array $requestedEquipmentList,
        int $requestedQuantity,
        string $eventDateTime,
        string $purposeDescription,
        string $activityType,
        ?array $supportingDocuments = null
    ) {
        $this->organizationName = $organizationName;
        $this->venueIdentifier = $venueIdentifier;
        $this->requestedEquipmentList = $requestedEquipmentList;
        $this->requestedQuantity = $requestedQuantity;
        $this->eventDateTime = $eventDateTime;
        $this->purposeDescription = $purposeDescription;
        $this->activityType = $activityType;
        $this->supportingDocuments = $supportingDocuments;
    }
}
