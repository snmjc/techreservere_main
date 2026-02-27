<?php

namespace App\Domain\ReleaseReturn\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Domain\ReleaseReturn\Repository\ReleaseReturnRepository::class)]
#[ORM\Table(name: 'release_returns')]
#[ORM\HasLifecycleCallbacks]
class ReleaseReturnEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $releaseReturnIdentifier = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $reservationIdentifier = 0;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $transactionType = 'Release';

    #[ORM\Column(type: Types::JSON)]
    private array $equipmentItemList = [];

    #[ORM\Column(type: Types::INTEGER)]
    private int $quantityProcessed = 0;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $conditionStatus = 'Good';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $remarksDescription = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $processedByAccountId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $processedTimestamp;

    public function __construct()
    {
        $this->processedTimestamp = new \DateTime();
    }

    public function getReleaseReturnIdentifier(): ?int { return $this->releaseReturnIdentifier; }
    public function getReservationIdentifier(): int { return $this->reservationIdentifier; }
    public function setReservationIdentifier(int $val): self { $this->reservationIdentifier = $val; return $this; }
    public function getTransactionType(): string { return $this->transactionType; }
    public function setTransactionType(string $val): self { $this->transactionType = $val; return $this; }
    public function getEquipmentItemList(): array { return $this->equipmentItemList; }
    public function setEquipmentItemList(array $val): self { $this->equipmentItemList = $val; return $this; }
    public function getQuantityProcessed(): int { return $this->quantityProcessed; }
    public function setQuantityProcessed(int $val): self { $this->quantityProcessed = $val; return $this; }
    public function getConditionStatus(): string { return $this->conditionStatus; }
    public function setConditionStatus(string $val): self { $this->conditionStatus = $val; return $this; }
    public function getRemarksDescription(): ?string { return $this->remarksDescription; }
    public function setRemarksDescription(?string $val): self { $this->remarksDescription = $val; return $this; }
    public function getProcessedByAccountId(): ?int { return $this->processedByAccountId; }
    public function setProcessedByAccountId(?int $val): self { $this->processedByAccountId = $val; return $this; }
    public function getProcessedTimestamp(): \DateTimeInterface { return $this->processedTimestamp; }
}
