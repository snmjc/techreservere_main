<?php

namespace App\Domain\Task\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Domain\Task\Repository\TaskRepository::class)]
#[ORM\Table(name: 'tasks')]
#[ORM\HasLifecycleCallbacks]
class TaskEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $taskIdentifier = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $reservationIdentifier = null;

    #[ORM\Column(type: Types::STRING, length: 200)]
    private string $taskTitle = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $taskDescription = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $taskType = 'Preparation';

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $taskStatus = 'Pending';

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $assignedToAccountId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dueDateTimestamp = null;

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
    public function onPreUpdate(): void { $this->updatedTimestamp = new \DateTime(); }

    public function getTaskIdentifier(): ?int { return $this->taskIdentifier; }
    public function getReservationIdentifier(): ?int { return $this->reservationIdentifier; }
    public function setReservationIdentifier(?int $val): self { $this->reservationIdentifier = $val; return $this; }
    public function getTaskTitle(): string { return $this->taskTitle; }
    public function setTaskTitle(string $val): self { $this->taskTitle = $val; return $this; }
    public function getTaskDescription(): ?string { return $this->taskDescription; }
    public function setTaskDescription(?string $val): self { $this->taskDescription = $val; return $this; }
    public function getTaskType(): string { return $this->taskType; }
    public function setTaskType(string $val): self { $this->taskType = $val; return $this; }
    public function getTaskStatus(): string { return $this->taskStatus; }
    public function setTaskStatus(string $val): self { $this->taskStatus = $val; return $this; }
    public function getAssignedToAccountId(): ?int { return $this->assignedToAccountId; }
    public function setAssignedToAccountId(?int $val): self { $this->assignedToAccountId = $val; return $this; }
    public function getDueDateTimestamp(): ?\DateTimeInterface { return $this->dueDateTimestamp; }
    public function setDueDateTimestamp(?\DateTimeInterface $val): self { $this->dueDateTimestamp = $val; return $this; }
    public function getCreatedTimestamp(): \DateTimeInterface { return $this->createdTimestamp; }
    public function getUpdatedTimestamp(): \DateTimeInterface { return $this->updatedTimestamp; }
}
