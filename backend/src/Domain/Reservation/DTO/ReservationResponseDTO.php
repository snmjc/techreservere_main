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
    public ?string $borrowerRemarks;
    public ?string $adminRemarks;
    public ?string $approvalRemarks;
    public ?string $denialReason;
    public ?string $cancellationReason;
    public ?string $completionRemarks;
    public ?string $manualOverrideReason;
    public string $currentStatus;
    public ?string $priorityLevel;
    public ?string $rejectionReason;
    public ?array $supportingDocuments;
    public string $submissionTimestamp;
    public array $remarkEvents;

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
        ?string $borrowerRemarks,
        ?string $adminRemarks,
        ?string $approvalRemarks,
        ?string $denialReason,
        ?string $cancellationReason,
        ?string $completionRemarks,
        ?string $manualOverrideReason,
        string $currentStatus,
        ?string $priorityLevel,
        ?string $rejectionReason,
        ?array $supportingDocuments,
        string $submissionTimestamp,
        array $remarkEvents = [],
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
        $this->borrowerRemarks = $borrowerRemarks;
        $this->adminRemarks = $adminRemarks;
        $this->approvalRemarks = $approvalRemarks;
        $this->denialReason = $denialReason;
        $this->cancellationReason = $cancellationReason;
        $this->completionRemarks = $completionRemarks;
        $this->manualOverrideReason = $manualOverrideReason;
        $this->currentStatus = $currentStatus;
        $this->priorityLevel = $priorityLevel;
        $this->rejectionReason = $rejectionReason;
        $this->supportingDocuments = $supportingDocuments;
        $this->submissionTimestamp = $submissionTimestamp;
        $this->remarkEvents = $remarkEvents;
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
            'borrowerRemarks' => $this->borrowerRemarks,
            'adminRemarks' => $this->adminRemarks,
            'approvalRemarks' => $this->approvalRemarks,
            'denialReason' => $this->denialReason,
            'cancellationReason' => $this->cancellationReason,
            'completionRemarks' => $this->completionRemarks,
            'manualOverrideReason' => $this->manualOverrideReason,
            'currentStatus' => $this->currentStatus,
            'priorityLevel' => $this->priorityLevel,
            'rejectionReason' => $this->rejectionReason,
            'supportingDocuments' => $this->supportingDocuments,
            'submissionTimestamp' => $this->submissionTimestamp,
            'remarkEvents' => $this->remarkEvents,
        ];
    }
}
