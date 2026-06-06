<?php

namespace App\Domain\Equipment\DTO;

class EquipmentResponseDTO
{
    public function __construct(
        public int $equipmentIdentifier,
        public string $equipmentName,
        public string $equipmentCategory,
        public string $equipmentBrand,
        public int $totalQuantity,
        public int $availableQuantity,
        public string $operationalStatus,
        public string $equipmentState,
        public ?string $description,
        public ?string $imageUrl,
        public string $barcode,
        public string $assetId,
        public ?string $photoData,
        public string $createdTimestamp,
        public string $updatedTimestamp
    ) {
    }

    public function toResponseArray(): array
    {
        return [
            'equipmentIdentifier' => $this->equipmentIdentifier,
            'equipmentName' => $this->equipmentName,
            'equipmentCategory' => $this->equipmentCategory,
            'equipmentBrand' => $this->equipmentBrand,
            'totalQuantity' => $this->totalQuantity,
            'availableQuantity' => $this->availableQuantity,
            'operationalStatus' => $this->operationalStatus,
            'equipmentState' => $this->equipmentState,
            'description' => $this->description,
            'imageUrl' => $this->imageUrl,
            'barcode' => $this->barcode,
            'assetId' => $this->assetId,
            'serialNumber' => $this->assetId,
            'photoData' => $this->photoData,
            'createdTimestamp' => $this->createdTimestamp,
            'updatedTimestamp' => $this->updatedTimestamp,
            'categoryName' => $this->equipmentCategory,
            'scheduleDescription' => $this->description,
        ];
    }
}
