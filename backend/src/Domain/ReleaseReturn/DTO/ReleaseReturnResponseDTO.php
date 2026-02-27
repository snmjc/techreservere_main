<?php

namespace App\Domain\ReleaseReturn\DTO;

class ReleaseReturnResponseDTO
{
    // ===== AI GENERATED: ReleaseReturnResponseDTO =====
    // Purpose: Response DTO for release/return transaction data

    public int $releaseReturnIdentifier;
    public int $reservationIdentifier;
    public string $transactionType;
    public array $equipmentItemList;
    public int $quantityProcessed;
    public string $conditionStatus;
    public ?string $remarksDescription;
    public ?int $processedByAccountId;
    public string $processedTimestamp;

    public function __construct(int $releaseReturnIdentifier, int $reservationIdentifier, string $transactionType, array $equipmentItemList, int $quantityProcessed, string $conditionStatus, ?string $remarksDescription, ?int $processedByAccountId, string $processedTimestamp)
    {
        $this->releaseReturnIdentifier = $releaseReturnIdentifier;
        $this->reservationIdentifier = $reservationIdentifier;
        $this->transactionType = $transactionType;
        $this->equipmentItemList = $equipmentItemList;
        $this->quantityProcessed = $quantityProcessed;
        $this->conditionStatus = $conditionStatus;
        $this->remarksDescription = $remarksDescription;
        $this->processedByAccountId = $processedByAccountId;
        $this->processedTimestamp = $processedTimestamp;
    }

    public function toResponseArray(): array
    {
        return [
            'releaseReturnIdentifier' => $this->releaseReturnIdentifier,
            'reservationIdentifier' => $this->reservationIdentifier,
            'transactionType' => $this->transactionType,
            'equipmentItemList' => $this->equipmentItemList,
            'quantityProcessed' => $this->quantityProcessed,
            'conditionStatus' => $this->conditionStatus,
            'remarksDescription' => $this->remarksDescription,
            'processedByAccountId' => $this->processedByAccountId,
            'processedTimestamp' => $this->processedTimestamp,
        ];
    }
}
