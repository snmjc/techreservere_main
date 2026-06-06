<?php

namespace App\Domain\Equipment\DTO;

class EquipmentCreateRequestDTO
{
    public string $equipmentName;
    public string $equipmentCategory;
    public string $equipmentBrand;
    public int $availableQuantity;
    public string $operationalStatus;
    public ?string $description;
    public ?string $imageUrl;
    public string $barcode;
    public string $assetId;

    public function __construct(
        string $equipmentName,
        string $equipmentCategory,
        string $equipmentBrand,
        int $availableQuantity,
        string $operationalStatus,
        ?string $description,
        ?string $imageUrl,
        string $barcode,
        string $assetId
    ) {
        $this->equipmentName = $equipmentName;
        $this->equipmentCategory = $equipmentCategory;
        $this->equipmentBrand = $equipmentBrand;
        $this->availableQuantity = $availableQuantity;
        $this->operationalStatus = $operationalStatus;
        $this->description = $description;
        $this->imageUrl = $imageUrl;
        $this->barcode = $barcode;
        $this->assetId = $assetId;
    }
}
