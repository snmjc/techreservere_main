<?php

namespace App\Domain\Task\Service;

use App\Domain\Task\DTO\TaskMutationRequestDTO;
use App\Shared\Exceptions\DomainValidationException;

class TaskValidationService
{
    public const ALLOWED_STATUSES = ['Pending', 'In Progress', 'Completed', 'Cancelled'];

    public function validateMutation(TaskMutationRequestDTO $request): void
    {
        $this->validateTitle($request->taskTitle);

        if (trim($request->taskType) === '') {
            throw new DomainValidationException('Task type is required.');
        }

        $this->validateStatus($request->taskStatus);
    }

    public function validateStatus(string $taskStatus): void
    {
        if (!in_array(trim($taskStatus), self::ALLOWED_STATUSES, true)) {
            throw new DomainValidationException('Invalid task status: ' . $taskStatus);
        }
    }

    public function normalizeOptionalText(?string $value): ?string
    {
        $trimmed = trim((string)$value);
        return $trimmed === '' ? null : $trimmed;
    }

    public function parseDueDate(?string $dueDateTimestamp): ?\DateTimeInterface
    {
        return $this->parseDateTime($dueDateTimestamp, 'Due date is invalid.');
    }

    public function parseDateTime(?string $dateTimeValue, string $errorMessage): ?\DateTimeInterface
    {
        if ($dateTimeValue === null) {
            return null;
        }

        try {
            return new \DateTime($dateTimeValue);
        } catch (\Throwable) {
            throw new DomainValidationException($errorMessage);
        }
    }

    private function validateTitle(string $taskTitle): void
    {
        if (trim($taskTitle) === '') {
            throw new DomainValidationException('Task name is required.');
        }

        if (mb_strlen(trim($taskTitle)) > 200) {
            throw new DomainValidationException('Task name must not exceed 200 characters.');
        }
    }
}
