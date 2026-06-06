<?php

namespace App\Domain\Equipment\DTO;

class EquipmentCreateRequestDTO
{
    public function __construct(
        public string $equipmentName,
        public string $equipmentCategory,
        public string $equipmentBrand,
        public int $availableQuantity,
        public string $operationalStatus,
        public ?string $description = null,
        public string $barcode = '',
        public string $serialNumber = '',
        public ?string $photoData = null
    ) {
    }
}
