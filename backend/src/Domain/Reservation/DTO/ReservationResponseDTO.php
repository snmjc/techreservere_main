<?php

namespace App\Domain\Reservation\DTO;

class ReservationResponseDTO
{
    // ===== AI GENERATED: ReservationResponseDTO =====
    // Purpose: Response DTO for reservation data
    // Inputs: entity properties
    // Returns: normalized array

    public int $reservationIdentifier;
    public string $reservationCode;
    public int $borrowerAccountId;
    public string $organizationName;
    public ?int $venueIdentifier;
    public array $requestedEquipmentList;
    public int $requestedQuantity;
    public string $eventDateTime;
    public string $purposeDescription;
    public string $activityType;
    public string $currentStatus;
    public ?string $priorityLevel;
    public ?string $rejectionReason;
    public ?array $supportingDocuments;
    public string $submissionTimestamp;

    public function __construct(
        int $reservationIdentifier,
        string $reservationCode,
        int $borrowerAccountId,
        string $organizationName,
        ?int $venueIdentifier,
        array $requestedEquipmentList,
        int $requestedQuantity,
        string $eventDateTime,
        string $purposeDescription,
        string $activityType,
        string $currentStatus,
        ?string $priorityLevel,
        ?string $rejectionReason,
        ?array $supportingDocuments,
        string $submissionTimestamp
    ) {
        $this->reservationIdentifier = $reservationIdentifier;
        $this->reservationCode = $reservationCode;
        $this->borrowerAccountId = $borrowerAccountId;
        $this->organizationName = $organizationName;
        $this->venueIdentifier = $venueIdentifier;
        $this->requestedEquipmentList = $requestedEquipmentList;
        $this->requestedQuantity = $requestedQuantity;
        $this->eventDateTime = $eventDateTime;
        $this->purposeDescription = $purposeDescription;
        $this->activityType = $activityType;
        $this->currentStatus = $currentStatus;
        $this->priorityLevel = $priorityLevel;
        $this->rejectionReason = $rejectionReason;
        $this->supportingDocuments = $supportingDocuments;
        $this->submissionTimestamp = $submissionTimestamp;
    }

    public function toResponseArray(): array
    {
        return [
            'reservationIdentifier' => $this->reservationIdentifier,
            'reservationCode' => $this->reservationCode,
            'borrowerAccountId' => $this->borrowerAccountId,
            'organizationName' => $this->organizationName,
            'venueIdentifier' => $this->venueIdentifier,
            'requestedEquipmentList' => $this->requestedEquipmentList,
            'requestedQuantity' => $this->requestedQuantity,
            'eventDateTime' => $this->eventDateTime,
            'purposeDescription' => $this->purposeDescription,
            'activityType' => $this->activityType,
            'currentStatus' => $this->currentStatus,
            'priorityLevel' => $this->priorityLevel,
            'rejectionReason' => $this->rejectionReason,
            'supportingDocuments' => $this->supportingDocuments,
            'submissionTimestamp' => $this->submissionTimestamp,
        ];
    }
}
