<?php

namespace App\Domain\Equipment\DTO;

class EquipmentResponseDTO
{
    // ===== AI GENERATED: EquipmentResponseDTO =====
    // Purpose: Response DTO for equipment data
    // Inputs: entity properties
    // Returns: normalized array
    // Flow:
    // 1. Maps entity properties to transport shape
    // 2. Returned by controller as JSON response

    public int $equipmentIdentifier;
    public string $equipmentName;
    public string $equipmentCategory;
    public string $equipmentBrand;
    public int $availableQuantity;
    public string $operationalStatus;
    public string $equipmentState;
    public ?string $description;
    public ?string $imageUrl;
    public string $barcode;
    public string $assetId;
    public string $createdTimestamp;
    public string $updatedTimestamp;

    public function __construct(
        int $equipmentIdentifier,
        string $equipmentName,
        string $equipmentCategory,
        string $equipmentBrand,
        int $availableQuantity,
        string $operationalStatus,
        string $equipmentState,
        ?string $description,
        ?string $imageUrl,
        string $barcode,
        string $assetId,
        string $createdTimestamp,
        string $updatedTimestamp
    ) {
        $this->equipmentIdentifier = $equipmentIdentifier;
        $this->equipmentName = $equipmentName;
        $this->equipmentCategory = $equipmentCategory;
        $this->equipmentBrand = $equipmentBrand;
        $this->availableQuantity = $availableQuantity;
        $this->operationalStatus = $operationalStatus;
        $this->equipmentState = $equipmentState;
        $this->description = $description;
        $this->imageUrl = $imageUrl;
        $this->barcode = $barcode;
        $this->assetId = $assetId;
        $this->createdTimestamp = $createdTimestamp;
        $this->updatedTimestamp = $updatedTimestamp;
    }

    public function toResponseArray(): array
    {
        return [
            'equipmentIdentifier' => $this->equipmentIdentifier,
            'equipmentName' => $this->equipmentName,
            'equipmentCategory' => $this->equipmentCategory,
            'equipmentBrand' => $this->equipmentBrand,
            'availableQuantity' => $this->availableQuantity,
            'operationalStatus' => $this->operationalStatus,
            'equipmentState' => $this->equipmentState,
            'description' => $this->description,
            'imageUrl' => $this->imageUrl,
            'barcode' => $this->barcode,
            'assetId' => $this->assetId,
            'createdTimestamp' => $this->createdTimestamp,
            'updatedTimestamp' => $this->updatedTimestamp,
            'categoryName' => $this->equipmentCategory,
            'totalQuantity' => $this->availableQuantity,
            'scheduleDescription' => $this->description,
        ];
    }
}
