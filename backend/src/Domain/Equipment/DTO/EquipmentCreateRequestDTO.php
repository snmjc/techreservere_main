<?php

namespace App\Domain\Equipment\DTO;

class EquipmentCreateRequestDTO
{
    // ===== AI GENERATED: EquipmentCreateRequestDTO =====
    // Purpose: Request DTO for creating equipment record
    // Inputs: equipmentName, categoryName, totalQuantity, operationalStatus, scheduleDescription
    // Returns: none (transport object)
    // Flow:
    // 1. Validated at controller level
    // 2. Passed to EquipmentCreateService

    public string $equipmentName;
    public string $categoryName;
    public int $totalQuantity;
    public string $operationalStatus;
    public ?string $scheduleDescription;

    public function __construct(
        string $equipmentName,
        string $categoryName,
        int $totalQuantity,
        string $operationalStatus = 'Active',
        ?string $scheduleDescription = null
    ) {
        $this->equipmentName = $equipmentName;
        $this->categoryName = $categoryName;
        $this->totalQuantity = $totalQuantity;
        $this->operationalStatus = $operationalStatus;
        $this->scheduleDescription = $scheduleDescription;
    }
}
