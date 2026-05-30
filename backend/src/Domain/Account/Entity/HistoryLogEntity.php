<?php

namespace App\Domain\Account\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'history_logs')]
class HistoryLogEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $staffId;

    #[ORM\Column(type: Types::INTEGER)]
    private int $reservationId;

    #[ORM\Column(type: Types::INTEGER)]
    private int $taskAssignmentId;

    public function getId(): ?int { return $this->id; }
    public function getStaffId(): int { return $this->staffId; }
    public function setStaffId(int $staffId): self { $this->staffId = $staffId; return $this; }
    public function getReservationId(): int { return $this->reservationId; }
    public function setReservationId(int $reservationId): self { $this->reservationId = $reservationId; return $this; }
    public function getTaskAssignmentId(): int { return $this->taskAssignmentId; }
    public function setTaskAssignmentId(int $taskAssignmentId): self { $this->taskAssignmentId = $taskAssignmentId; return $this; }
}
