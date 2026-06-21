<?php

namespace App\Domain\Equipment\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Domain\Equipment\Repository\EquipmentRepository::class)]
#[ORM\Table(
    name: 'equipment',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'UNIQ_EQUIPMENT_BARCODE', columns: ['barcode']),
        new ORM\UniqueConstraint(name: 'UNIQ_EQUIPMENT_ASSET_ID', columns: ['asset_id']),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class EquipmentEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $equipmentIdentifier = null;

    #[ORM\Column(type: Types::STRING, length: 150)]
    private string $equipmentName = '';

    #[ORM\Column(name: 'equipment_category', type: Types::STRING, length: 100)]
    private string $equipmentCategory = '';

    #[ORM\Column(name: 'equipment_brand', type: Types::STRING, length: 100)]
    private string $equipmentBrand = '';

    #[ORM\Column(name: 'total_quantity', type: Types::INTEGER)]
    private int $totalQuantity = 0;

    #[ORM\Column(name: 'available_quantity', type: Types::INTEGER)]
    private int $availableQuantity = 0;

    #[ORM\Column(name: 'operational_status', type: Types::STRING, length: 50)]
    private string $operationalStatus = 'Available';

    #[ORM\Column(name: 'equipment_state', type: Types::STRING, length: 50)]
    private string $equipmentState = 'Available';

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'image_url', type: Types::TEXT, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: Types::STRING, length: 120)]
    private string $barcode = '';

    #[ORM\Column(name: 'asset_id', type: Types::STRING, length: 13)]
    private string $assetId = '';

    #[ORM\Column(name: 'photo_data', type: Types::TEXT, nullable: true)]
    private ?string $photoData = null;

    #[ORM\Column(name: 'photo_display_mode', type: Types::STRING, length: 20, options: ['default' => 'contain'])]
    private string $photoDisplayMode = 'contain';

    #[ORM\Column(name: 'photo_position_x', type: Types::INTEGER, options: ['default' => 50])]
    private int $photoPositionX = 50;

    #[ORM\Column(name: 'photo_position_y', type: Types::INTEGER, options: ['default' => 50])]
    private int $photoPositionY = 50;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdTimestamp;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedTimestamp;

    public function __construct()
    {
        $this->createdTimestamp = new \DateTime();
        $this->updatedTimestamp = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedTimestamp = new \DateTime();
    }

    public function getEquipmentIdentifier(): ?int { return $this->equipmentIdentifier; }
    public function getEquipmentName(): string { return $this->equipmentName; }
    public function setEquipmentName(string $equipmentName): self { $this->equipmentName = $equipmentName; return $this; }
    public function getEquipmentCategory(): string { return $this->equipmentCategory; }
    public function setEquipmentCategory(string $equipmentCategory): self { $this->equipmentCategory = $equipmentCategory; return $this; }
    public function getCategoryName(): string { return $this->equipmentCategory; }
    public function setCategoryName(string $categoryName): self { $this->equipmentCategory = $categoryName; return $this; }
    public function getEquipmentBrand(): string { return $this->equipmentBrand; }
    public function setEquipmentBrand(string $equipmentBrand): self { $this->equipmentBrand = $equipmentBrand; return $this; }
    public function getTotalQuantity(): int { return $this->totalQuantity; }
    public function setTotalQuantity(int $totalQuantity): self { $this->totalQuantity = $totalQuantity; return $this; }
    public function getAvailableQuantity(): int { return $this->availableQuantity; }
    public function setAvailableQuantity(int $availableQuantity): self { $this->availableQuantity = $availableQuantity; return $this; }
    public function getOperationalStatus(): string { return $this->operationalStatus; }
    public function setOperationalStatus(string $operationalStatus): self { $this->operationalStatus = $operationalStatus; return $this; }
    public function getEquipmentState(): string { return $this->equipmentState; }
    public function setEquipmentState(string $equipmentState): self { $this->equipmentState = $equipmentState; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getScheduleDescription(): ?string { return $this->description; }
    public function setScheduleDescription(?string $scheduleDescription): self { $this->description = $scheduleDescription; return $this; }
    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): self { $this->imageUrl = $imageUrl; return $this; }
    public function getBarcode(): string { return $this->barcode; }
    public function setBarcode(string $barcode): self { $this->barcode = $barcode; return $this; }
    public function getAssetId(): string { return $this->assetId; }
    public function setAssetId(string $assetId): self { $this->assetId = $assetId; return $this; }
    public function getSerialNumber(): string { return $this->assetId; }
    public function setSerialNumber(string $serialNumber): self { $this->assetId = $serialNumber; return $this; }
    public function getPhotoData(): ?string { return $this->photoData; }
    public function setPhotoData(?string $photoData): self { $this->photoData = $photoData; return $this; }
    public function getPhotoDisplayMode(): string { return $this->photoDisplayMode; }
    public function setPhotoDisplayMode(string $photoDisplayMode): self { $this->photoDisplayMode = $photoDisplayMode; return $this; }
    public function getPhotoPositionX(): int { return $this->photoPositionX; }
    public function setPhotoPositionX(int $photoPositionX): self { $this->photoPositionX = $photoPositionX; return $this; }
    public function getPhotoPositionY(): int { return $this->photoPositionY; }
    public function setPhotoPositionY(int $photoPositionY): self { $this->photoPositionY = $photoPositionY; return $this; }
    public function getCreatedTimestamp(): \DateTimeInterface { return $this->createdTimestamp; }
    public function getUpdatedTimestamp(): \DateTimeInterface { return $this->updatedTimestamp; }
}
