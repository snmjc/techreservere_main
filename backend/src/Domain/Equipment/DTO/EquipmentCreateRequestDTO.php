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
        public ?string $imageUrl = null,
        public string $barcode = '',
        public string $assetId = '',
        public ?string $photoData = null,
        public string $photoDisplayMode = 'contain',
        public int $photoPositionX = 50,
        public int $photoPositionY = 50
    ) {
    }
}
