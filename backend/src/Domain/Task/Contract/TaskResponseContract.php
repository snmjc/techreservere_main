<?php

namespace App\Domain\Task\Contract;

class TaskResponseContract
{
    // ===== AI GENERATED: TaskResponseContract =====
    // Purpose: Inter-domain communication shape for task data

    public int $taskIdentifier;
    public string $taskTitle;
    public string $taskStatus;

    public function __construct(int $taskIdentifier, string $taskTitle, string $taskStatus)
    {
        $this->taskIdentifier = $taskIdentifier;
        $this->taskTitle = $taskTitle;
        $this->taskStatus = $taskStatus;
    }
}
