<?php

namespace App\Domain\Task\DTO;

class TaskMutationRequestDTO
{
    public function __construct(
        public readonly string $taskTitle,
        public readonly ?string $taskDescription,
        public readonly string $taskType,
        public readonly string $taskStatus,
        public readonly ?int $reservationIdentifier,
        public readonly ?int $assignedToAccountId,
        public readonly ?string $dueDateTimestamp
    ) {
    }
}
