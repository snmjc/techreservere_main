<?php

namespace App\Domain\Task\Service;

use App\Domain\Reservation\Entity\ReservationEntity;
use App\Shared\Exceptions\DomainValidationException;

class TaskAssignmentTemplateService
{
    private const DEFAULT_TEMPLATE = [
        'taskTitle' => '{activityType} Preparation',
        'taskDescription' => '{purposeDescription}',
        'taskType' => 'Preparation',
        'smsMessage' => "hi! {assignedStaff}.\n\nYou have task on {dueDate}, {taskName}: {reservationCode}.\n{reservationPurpose}\n\nIf you can't please do contact the Facilities Office for changing of staff",
    ];

    private const VARIABLES = [
        'activityType' => 'Reservation activity type',
        'purposeDescription' => 'Reservation purpose',
        'reservationPurpose' => 'Reservation purpose',
        'reservationCode' => 'Reservation code',
        'reservationIdentifier' => 'Reservation ID',
        'organizationName' => 'Organization name',
        'venueName' => 'Venue or facility name',
        'requestedQuantity' => 'Requested quantity',
        'eventDate' => 'Event date',
        'eventTime' => 'Event time',
        'eventDateTime' => 'Event date and time',
        'endDateTime' => 'End date and time',
        'assignedStaff' => 'Assigned staff name',
        'dueDate' => 'Task due date',
        'taskName' => 'Task name',
        'taskType' => 'Task type',
    ];

    public function getTemplate(): array
    {
        $template = $this->loadTemplate();

        return [
            ...$template,
            'variables' => self::VARIABLES,
        ];
    }

    public function updateTemplate(array $body): array
    {
        $candidate = [
            'taskTitle' => $this->normalizeTemplateText($body['taskTitle'] ?? self::DEFAULT_TEMPLATE['taskTitle']),
            'taskDescription' => $this->normalizeTemplateText($body['taskDescription'] ?? self::DEFAULT_TEMPLATE['taskDescription']),
            'taskType' => $this->normalizeTemplateText($body['taskType'] ?? self::DEFAULT_TEMPLATE['taskType']),
            'smsMessage' => $this->normalizeTemplateText($body['smsMessage'] ?? self::DEFAULT_TEMPLATE['smsMessage']),
            'updatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        if ($candidate['taskTitle'] === '') {
            throw new DomainValidationException('Task title format is required.');
        }

        if ($candidate['taskType'] === '') {
            throw new DomainValidationException('Task type format is required.');
        }

        if (mb_strlen($candidate['taskTitle']) > 300) {
            throw new DomainValidationException('Task title format must not exceed 300 characters.');
        }

        if (mb_strlen($candidate['taskDescription']) > 1000) {
            throw new DomainValidationException('Task description format must not exceed 1000 characters.');
        }

        if (mb_strlen($candidate['smsMessage']) > 1000) {
            throw new DomainValidationException('SMS message format must not exceed 1000 characters.');
        }

        foreach (['taskTitle', 'taskDescription', 'taskType', 'smsMessage'] as $templateKey) {
            $this->assertKnownVariables($candidate[$templateKey], $templateKey);
        }

        $this->writeTemplate($candidate);

        return $this->getTemplate();
    }

    public function buildReservationContext(ReservationEntity $reservation): array
    {
        $eventDateTime = $reservation->getEventDateTime();
        $endDateTime = $reservation->getEndDateTime();

        return [
            'activityType' => trim($reservation->getActivityType()),
            'purposeDescription' => trim($reservation->getPurposeDescription()),
            'reservationPurpose' => trim($reservation->getPurposeDescription()),
            'reservationCode' => trim($reservation->getReservationCode()),
            'reservationIdentifier' => (string)($reservation->getReservationIdentifier() ?? ''),
            'organizationName' => trim($reservation->getOrganizationName()),
            'venueName' => '',
            'requestedQuantity' => (string)$reservation->getRequestedQuantity(),
            'eventDate' => $this->formatDate($eventDateTime),
            'eventTime' => $this->formatTime($eventDateTime),
            'eventDateTime' => $this->formatDateTime($eventDateTime),
            'endDateTime' => $endDateTime instanceof \DateTimeInterface ? $this->formatDateTime($endDateTime) : '',
        ];
    }

    public function buildTaskContext(array $task): array
    {
        $taskLabel = trim((string)($task['taskTitle'] ?? '')) ?: trim((string)($task['taskType'] ?? 'Assigned task')) ?: 'Assigned task';
        $reservationPurpose = trim((string)($task['taskDescription'] ?? '')) ?: 'No description provided.';
        $dueDate = $this->formatTaskDueDate($task);
        $reservationCode = trim((string)($task['reservationCode'] ?? ''))
            ?: (trim((string)($task['reservationLabel'] ?? '')) ?: 'Reservation');

        return [
            'activityType' => trim((string)($task['reservationActivityType'] ?? '')),
            'purposeDescription' => $reservationPurpose,
            'reservationPurpose' => $reservationPurpose,
            'reservationCode' => $reservationCode,
            'reservationIdentifier' => (string)($task['reservationIdentifier'] ?? ''),
            'organizationName' => trim((string)($task['organizationName'] ?? '')),
            'venueName' => trim((string)($task['venueName'] ?? $task['facilityName'] ?? '')),
            'requestedQuantity' => '',
            'eventDate' => $this->formatNullableTimestamp($task['eventDateTime'] ?? null, 'M j, Y'),
            'eventTime' => $this->formatNullableTimestamp($task['eventDateTime'] ?? null, 'g:i A'),
            'eventDateTime' => $this->formatNullableTimestamp($task['eventDateTime'] ?? null, 'M j, Y g:i A'),
            'endDateTime' => $this->formatNullableTimestamp($task['endDateTime'] ?? null, 'M j, Y g:i A'),
            'assignedStaff' => trim((string)($task['assignedStaffName'] ?? '')),
            'dueDate' => $dueDate !== '' ? $dueDate : 'the scheduled date',
            'taskName' => $taskLabel,
            'taskType' => trim((string)($task['taskType'] ?? '')),
        ];
    }

    public function renderTaskTitle(array $variables): string
    {
        $title = trim($this->renderTemplate($this->loadTemplate()['taskTitle'], $variables));

        return $title !== '' ? $title : 'Reservation Preparation';
    }

    public function renderTaskDescription(array $variables): ?string
    {
        $description = trim($this->renderTemplate($this->loadTemplate()['taskDescription'], $variables));

        return $description !== '' ? $description : null;
    }

    public function renderTaskType(array $variables): string
    {
        $taskType = trim($this->renderTemplate($this->loadTemplate()['taskType'], $variables));

        return $taskType !== '' ? $taskType : 'Preparation';
    }

    public function renderSmsMessage(array $variables): string
    {
        return trim($this->renderTemplate($this->loadTemplate()['smsMessage'], $variables));
    }

    private function loadTemplate(): array
    {
        $path = $this->templatePath();
        if (!is_file($path)) {
            $this->writeTemplate([
                ...self::DEFAULT_TEMPLATE,
                'updatedAt' => null,
            ]);
        }

        $rawTemplate = json_decode((string)file_get_contents($path), true);
        $template = is_array($rawTemplate) ? $rawTemplate : [];

        return [
            ...self::DEFAULT_TEMPLATE,
            ...array_intersect_key($template, array_flip(['taskTitle', 'taskDescription', 'taskType', 'smsMessage', 'updatedAt'])),
        ];
    }

    private function writeTemplate(array $template): void
    {
        $path = $this->templatePath();
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $payload = json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            throw new DomainValidationException('Unable to encode task assignment format.');
        }

        $temporaryPath = $path . '.tmp';
        file_put_contents($temporaryPath, $payload . PHP_EOL, LOCK_EX);
        rename($temporaryPath, $path);
    }

    private function templatePath(): string
    {
        return dirname(__DIR__, 4) . '/var/task-assignment-format.json';
    }

    private function renderTemplate(string $template, array $variables): string
    {
        return (string)preg_replace_callback('/\{([a-zA-Z][a-zA-Z0-9_]*)\}/', static function (array $match) use ($variables): string {
            return trim((string)($variables[$match[1]] ?? ''));
        }, $template);
    }

    private function assertKnownVariables(string $template, string $fieldName): void
    {
        preg_match_all('/\{([a-zA-Z][a-zA-Z0-9_]*)\}/', $template, $matches);
        $unknownVariables = array_values(array_diff(array_unique($matches[1] ?? []), array_keys(self::VARIABLES)));
        if ($unknownVariables !== []) {
            throw new DomainValidationException(sprintf(
                '%s uses unknown variable(s): %s.',
                $fieldName,
                implode(', ', array_map(static fn (string $variable): string => '{' . $variable . '}', $unknownVariables))
            ));
        }
    }

    private function normalizeTemplateText(mixed $value): string
    {
        return trim(str_replace("\r\n", "\n", (string)($value ?? '')));
    }

    private function formatTaskDueDate(array $task): string
    {
        $dueDate = $this->formatNullableTimestamp($task['dueDateTimestamp'] ?? null, 'M j, Y g:i A');
        if ($dueDate !== '') {
            return $dueDate;
        }

        $startDate = $this->formatNullableTimestamp($task['eventDateTime'] ?? null, 'M j, Y g:i A');
        $endDate = $this->formatNullableTimestamp($task['endDateTime'] ?? null, 'M j, Y g:i A');

        if ($startDate === '' || $endDate === '' || $startDate === $endDate) {
            return $startDate;
        }

        return $startDate . ' - ' . $endDate;
    }

    private function formatNullableTimestamp(mixed $value, string $format): string
    {
        $timestamp = trim((string)($value ?? ''));
        if ($timestamp === '') {
            return '';
        }

        try {
            return (new \DateTimeImmutable($timestamp, new \DateTimeZone('Asia/Manila')))
                ->setTimezone(new \DateTimeZone('Asia/Manila'))
                ->format($format);
        } catch (\Throwable) {
            return '';
        }
    }

    private function formatDate(\DateTimeInterface $dateTime): string
    {
        return \DateTimeImmutable::createFromInterface($dateTime)
            ->setTimezone(new \DateTimeZone('Asia/Manila'))
            ->format('M j, Y');
    }

    private function formatTime(\DateTimeInterface $dateTime): string
    {
        return \DateTimeImmutable::createFromInterface($dateTime)
            ->setTimezone(new \DateTimeZone('Asia/Manila'))
            ->format('g:i A');
    }

    private function formatDateTime(\DateTimeInterface $dateTime): string
    {
        return \DateTimeImmutable::createFromInterface($dateTime)
            ->setTimezone(new \DateTimeZone('Asia/Manila'))
            ->format('M j, Y g:i A');
    }
}
