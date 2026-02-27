<?php

namespace App\Domain\AuditLog\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Domain\AuditLog\Repository\AuditLogRepository::class)]
#[ORM\Table(name: 'audit_logs')]
class AuditLogEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $auditLogIdentifier = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $performedByAccountId = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $actionPerformed = '';

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $targetEntityType = '';

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $targetEntityIdentifier = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $changeDetails = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $occurredTimestamp;

    public function __construct()
    {
        $this->occurredTimestamp = new \DateTime();
    }

    public function getAuditLogIdentifier(): ?int { return $this->auditLogIdentifier; }
    public function getPerformedByAccountId(): ?int { return $this->performedByAccountId; }
    public function setPerformedByAccountId(?int $val): self { $this->performedByAccountId = $val; return $this; }
    public function getActionPerformed(): string { return $this->actionPerformed; }
    public function setActionPerformed(string $val): self { $this->actionPerformed = $val; return $this; }
    public function getTargetEntityType(): string { return $this->targetEntityType; }
    public function setTargetEntityType(string $val): self { $this->targetEntityType = $val; return $this; }
    public function getTargetEntityIdentifier(): ?int { return $this->targetEntityIdentifier; }
    public function setTargetEntityIdentifier(?int $val): self { $this->targetEntityIdentifier = $val; return $this; }
    public function getChangeDetails(): ?array { return $this->changeDetails; }
    public function setChangeDetails(?array $val): self { $this->changeDetails = $val; return $this; }
    public function getOccurredTimestamp(): \DateTimeInterface { return $this->occurredTimestamp; }
}
