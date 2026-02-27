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
    public string $categoryName;
    public int $totalQuantity;
    public int $availableQuantity;
    public string $operationalStatus;
    public string $equipmentState;
    public ?string $scheduleDescription;
    public string $createdTimestamp;

    public function __construct(
        int $equipmentIdentifier,
        string $equipmentName,
        string $categoryName,
        int $totalQuantity,
        int $availableQuantity,
        string $operationalStatus,
        string $equipmentState,
        ?string $scheduleDescription,
        string $createdTimestamp
    ) {
        $this->equipmentIdentifier = $equipmentIdentifier;
        $this->equipmentName = $equipmentName;
        $this->categoryName = $categoryName;
        $this->totalQuantity = $totalQuantity;
        $this->availableQuantity = $availableQuantity;
        $this->operationalStatus = $operationalStatus;
        $this->equipmentState = $equipmentState;
        $this->scheduleDescription = $scheduleDescription;
        $this->createdTimestamp = $createdTimestamp;
    }

    public function toResponseArray(): array
    {
        return [
            'equipmentIdentifier' => $this->equipmentIdentifier,
            'equipmentName' => $this->equipmentName,
            'categoryName' => $this->categoryName,
            'totalQuantity' => $this->totalQuantity,
            'availableQuantity' => $this->availableQuantity,
            'operationalStatus' => $this->operationalStatus,
            'equipmentState' => $this->equipmentState,
            'scheduleDescription' => $this->scheduleDescription,
            'createdTimestamp' => $this->createdTimestamp,
        ];
    }
}
