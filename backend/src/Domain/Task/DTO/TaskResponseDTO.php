<?php

namespace App\Domain\Task\DTO;

class TaskResponseDTO
{
    // ===== AI GENERATED: TaskResponseDTO =====
    // Purpose: Response DTO for task data

    public int $taskIdentifier;
    public ?int $reservationIdentifier;
    public string $taskTitle;
    public ?string $taskDescription;
    public string $taskType;
    public string $taskStatus;
    public ?int $assignedToAccountId;
    public ?string $dueDateTimestamp;
    public string $createdTimestamp;

    public function __construct(int $taskIdentifier, ?int $reservationIdentifier, string $taskTitle, ?string $taskDescription, string $taskType, string $taskStatus, ?int $assignedToAccountId, ?string $dueDateTimestamp, string $createdTimestamp)
    {
        $this->taskIdentifier = $taskIdentifier;
        $this->reservationIdentifier = $reservationIdentifier;
        $this->taskTitle = $taskTitle;
        $this->taskDescription = $taskDescription;
        $this->taskType = $taskType;
        $this->taskStatus = $taskStatus;
        $this->assignedToAccountId = $assignedToAccountId;
        $this->dueDateTimestamp = $dueDateTimestamp;
        $this->createdTimestamp = $createdTimestamp;
    }

    public function toResponseArray(): array
    {
        return [
            'taskIdentifier' => $this->taskIdentifier,
            'reservationIdentifier' => $this->reservationIdentifier,
            'taskTitle' => $this->taskTitle,
            'taskDescription' => $this->taskDescription,
            'taskType' => $this->taskType,
            'taskStatus' => $this->taskStatus,
            'assignedToAccountId' => $this->assignedToAccountId,
            'dueDateTimestamp' => $this->dueDateTimestamp,
            'createdTimestamp' => $this->createdTimestamp,
        ];
    }
}
