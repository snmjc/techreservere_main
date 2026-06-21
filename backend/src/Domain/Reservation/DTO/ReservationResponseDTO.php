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
    public string $borrowerFirstName;
    public string $borrowerLastName;
    public string $borrowerFullName;
    public ?string $borrowerEmailAddress;
    public ?string $borrowerContactNumber;
    public string $organizationName;
    public ?int $venueIdentifier;
    public ?string $venueName;
    public array $requestedEquipmentList;
    public int $requestedQuantity;
    public string $eventDateTime;
    public string $endDateTime;
    public string $activityTimeRange;
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
        ?string $venueName,
        array $requestedEquipmentList,
        int $requestedQuantity,
        string $eventDateTime,
        string $endDateTime,
        string $activityTimeRange,
        string $purposeDescription,
        string $activityType,
        string $currentStatus,
        ?string $priorityLevel,
        ?string $rejectionReason,
        ?array $supportingDocuments,
        string $submissionTimestamp,
        string $borrowerFirstName = '',
        string $borrowerLastName = '',
        string $borrowerFullName = '',
        ?string $borrowerEmailAddress = null,
        ?string $borrowerContactNumber = null
    ) {
        $this->reservationIdentifier = $reservationIdentifier;
        $this->reservationCode = $reservationCode;
        $this->borrowerAccountId = $borrowerAccountId;
        $this->borrowerFirstName = $borrowerFirstName;
        $this->borrowerLastName = $borrowerLastName;
        $this->borrowerFullName = $borrowerFullName;
        $this->borrowerEmailAddress = $borrowerEmailAddress;
        $this->borrowerContactNumber = $borrowerContactNumber;
        $this->organizationName = $organizationName;
        $this->venueIdentifier = $venueIdentifier;
        $this->venueName = $venueName;
        $this->requestedEquipmentList = $requestedEquipmentList;
        $this->requestedQuantity = $requestedQuantity;
        $this->eventDateTime = $eventDateTime;
        $this->endDateTime = $endDateTime;
        $this->activityTimeRange = $activityTimeRange;
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
            'borrowerFirstName' => $this->borrowerFirstName,
            'borrowerLastName' => $this->borrowerLastName,
            'borrowerFullName' => $this->borrowerFullName,
            'borrowerEmailAddress' => $this->borrowerEmailAddress,
            'borrowerContactNumber' => $this->borrowerContactNumber,
            'organizationName' => $this->organizationName,
            'venueIdentifier' => $this->venueIdentifier,
            'venueName' => $this->venueName,
            'requestedEquipmentList' => $this->requestedEquipmentList,
            'requestedQuantity' => $this->requestedQuantity,
            'eventDateTime' => $this->eventDateTime,
            'endDateTime' => $this->endDateTime,
            'activityTimeRange' => $this->activityTimeRange,
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
