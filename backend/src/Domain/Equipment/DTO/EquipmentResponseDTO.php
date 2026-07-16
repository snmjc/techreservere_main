<?php

namespace App\Domain\Equipment\DTO;

class EquipmentResponseDTO
{
    public function __construct(
        public int $equipmentIdentifier,
        public string $equipmentName,
        public string $equipmentCategory,
        public string $equipmentBrand,
        public ?string $equipmentModel,
        public int $totalQuantity,
        public int $availableQuantity,
        public int $reservedQuantity,
        public int $borrowedQuantity,
        public int $underMaintenanceQuantity,
        public int $unavailableQuantity,
        public string $operationalStatus,
        public string $equipmentState,
        public ?string $description,
        public ?string $remarks,
        public ?array $specifications,
        public ?string $imageUrl,
        public string $barcode,
        public string $assetId,
        public array $units,
        public ?string $photoData,
        public string $photoDisplayMode,
        public int $photoPositionX,
        public int $photoPositionY,
        public int $dispatchedTodayQuantity,
        public int $dispatchedTodayReservationCount,
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
            'equipmentModel' => $this->equipmentModel,
            'totalQuantity' => $this->totalQuantity,
            'availableQuantity' => $this->availableQuantity,
            'reservedQuantity' => $this->reservedQuantity,
            'borrowedQuantity' => $this->borrowedQuantity,
            'underMaintenanceQuantity' => $this->underMaintenanceQuantity,
            'unavailableQuantity' => $this->unavailableQuantity,
            'operationalStatus' => $this->operationalStatus,
            'equipmentState' => $this->equipmentState,
            'description' => $this->description,
            'remarks' => $this->remarks,
            'specifications' => $this->specifications,
            'imageUrl' => $this->imageUrl,
            'barcode' => $this->barcode,
            'assetId' => $this->assetId,
            'serialNumber' => $this->assetId,
            'units' => $this->units,
            'photoData' => $this->photoData,
            'photoDisplayMode' => $this->photoDisplayMode,
            'photoPositionX' => $this->photoPositionX,
            'photoPositionY' => $this->photoPositionY,
            'dispatchedTodayQuantity' => $this->dispatchedTodayQuantity,
            'dispatchedTodayReservationCount' => $this->dispatchedTodayReservationCount,
            'createdTimestamp' => $this->createdTimestamp,
            'updatedTimestamp' => $this->updatedTimestamp,
            'categoryName' => $this->equipmentCategory,
            'scheduleDescription' => $this->description,
        ];
    }
}
