<?php

namespace App\Domain\Equipment\Contract;

class EquipmentResponseContract
{
    // ===== AI GENERATED: EquipmentResponseContract =====
    // Purpose: Inter-domain communication shape for equipment data
    // Inputs: none
    // Returns: none
    // Flow:
    // 1. Used when Reservation domain needs equipment info
    // 2. Prevents entity exposure across domain boundaries

    public int $equipmentIdentifier;
    public string $equipmentName;
    public int $availableQuantity;
    public string $equipmentState;

    public function __construct(
        int $equipmentIdentifier,
        string $equipmentName,
        int $availableQuantity,
        string $equipmentState
    ) {
        $this->equipmentIdentifier = $equipmentIdentifier;
        $this->equipmentName = $equipmentName;
        $this->availableQuantity = $availableQuantity;
        $this->equipmentState = $equipmentState;
    }
}
