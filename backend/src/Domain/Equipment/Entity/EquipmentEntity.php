<?php

namespace App\Domain\Equipment\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Domain\Equipment\Repository\EquipmentRepository::class)]
#[ORM\Table(name: 'equipment')]
#[ORM\HasLifecycleCallbacks]
class EquipmentEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $equipmentIdentifier = null;

    #[ORM\Column(type: Types::STRING, length: 150)]
    private string $equipmentName = '';

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $categoryName = '';

    #[ORM\Column(type: Types::INTEGER)]
    private int $totalQuantity = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $availableQuantity = 0;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $operationalStatus = 'Active';

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $equipmentState = 'Available';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $scheduleDescription = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdTimestamp;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
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
    public function getCategoryName(): string { return $this->categoryName; }
    public function setCategoryName(string $categoryName): self { $this->categoryName = $categoryName; return $this; }
    public function getTotalQuantity(): int { return $this->totalQuantity; }
    public function setTotalQuantity(int $totalQuantity): self { $this->totalQuantity = $totalQuantity; return $this; }
    public function getAvailableQuantity(): int { return $this->availableQuantity; }
    public function setAvailableQuantity(int $availableQuantity): self { $this->availableQuantity = $availableQuantity; return $this; }
    public function getOperationalStatus(): string { return $this->operationalStatus; }
    public function setOperationalStatus(string $operationalStatus): self { $this->operationalStatus = $operationalStatus; return $this; }
    public function getEquipmentState(): string { return $this->equipmentState; }
    public function setEquipmentState(string $equipmentState): self { $this->equipmentState = $equipmentState; return $this; }
    public function getScheduleDescription(): ?string { return $this->scheduleDescription; }
    public function setScheduleDescription(?string $scheduleDescription): self { $this->scheduleDescription = $scheduleDescription; return $this; }
    public function getCreatedTimestamp(): \DateTimeInterface { return $this->createdTimestamp; }
    public function getUpdatedTimestamp(): \DateTimeInterface { return $this->updatedTimestamp; }
}
