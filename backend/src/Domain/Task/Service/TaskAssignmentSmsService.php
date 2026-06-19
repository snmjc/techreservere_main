<?php

namespace App\Domain\Task\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TaskAssignmentSmsService
{
    private const TWILIO_API_BASE = 'https://api.twilio.com/2010-04-01/Accounts/';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger
    ) {
    }

    public function notifyOnAssignmentChange(?array $previousTask, array $currentTask): ?string
    {
        $currentAssignedTo = (int)($currentTask['assignedToAccountId'] ?? 0);
        if ($currentAssignedTo <= 0) {
            return null;
        }

        $previousAssignedTo = (int)($previousTask['assignedToAccountId'] ?? 0);
        $isCreate = $previousTask === null;
        $shouldSend = $isCreate
            ? $currentAssignedTo > 0
            : $previousAssignedTo !== $currentAssignedTo;

        if (!$shouldSend) {
            return null;
        }

        $destinationPhoneNumber = $this->normalizePhoneNumber($currentTask['assignedStaffPhone'] ?? null);
        if ($destinationPhoneNumber === null) {
            $this->logger->warning('Task assignment SMS skipped because assigned staff has no valid phone number.', [
                'taskIdentifier' => $currentTask['taskIdentifier'] ?? null,
                'assignedToAccountId' => $currentTask['assignedToAccountId'] ?? null,
            ]);

            return 'Task assignment saved, but the assigned staff has no valid phone number for SMS.';
        }

        if (!$this->isSmsConfigured()) {
            $this->logger->warning('Task assignment SMS skipped because Twilio is not configured.', [
                'taskIdentifier' => $currentTask['taskIdentifier'] ?? null,
                'assignedToAccountId' => $currentTask['assignedToAccountId'] ?? null,
            ]);

            return 'Task assignment saved, but SMS notifications are not configured yet.';
        }

        $messageBody = $this->buildMessageBody($currentTask, $isCreate ? 'create' : 'reassign');

        try {
            $response = $this->httpClient->request('POST', self::TWILIO_API_BASE . rawurlencode($this->twilioAccountSid()) . '/Messages.json', [
                'auth_basic' => [$this->twilioAccountSid(), $this->twilioAuthToken()],
                'body' => [
                    'To' => $destinationPhoneNumber,
                    'From' => $this->twilioFromNumber(),
                    'Body' => $messageBody,
                ],
            ]);
            $response->getContent();

            return null;
        } catch (\Throwable $exception) {
            $this->logger->error('Task assignment SMS failed.', [
                'taskIdentifier' => $currentTask['taskIdentifier'] ?? null,
                'assignedToAccountId' => $currentTask['assignedToAccountId'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            return 'Task assignment saved, but the SMS notification could not be delivered.';
        }
    }

    private function buildMessageBody(array $task, string $mode): string
    {
        $staffName = trim((string)($task['assignedStaffName'] ?? ''));
        $greeting = $staffName !== '' ? sprintf('Hello %s.', $staffName) : 'Hello.';
        $taskLabel = trim((string)($task['taskTitle'] ?? '')) ?: trim((string)($task['taskType'] ?? 'Assigned task')) ?: 'Assigned task';
        $reservationCode = trim((string)($task['reservationCode'] ?? ''));
        $facilityName = trim((string)($task['facilityName'] ?? '')) ?: trim((string)($task['organizationName'] ?? ''));
        $dueDateTime = $this->formatDueDateTime($task['dueDateTimestamp'] ?? null, $task['eventDateTime'] ?? null, $task['endDateTime'] ?? null);
        $intro = $mode === 'reassign'
            ? 'TechReserve: A task has been assigned to you.'
            : 'TechReserve: You have a new assigned task.';

        $segments = [
            $intro,
            $greeting,
            sprintf('Task: %s.', $taskLabel),
        ];

        if ($reservationCode !== '') {
            $segments[] = sprintf('Reservation: %s.', $reservationCode);
        }

        if ($facilityName !== '') {
            $segments[] = sprintf('Facility: %s.', $facilityName);
        }

        if ($dueDateTime !== '') {
            $segments[] = sprintf('Due: %s.', $dueDateTime);
        }

        $segments[] = 'Please coordinate with the admin office if you have questions.';

        return implode(' ', $segments);
    }

    private function formatDueDateTime(?string $dueDateTimestamp, ?string $startTimestamp, ?string $endTimestamp): string
    {
        $preferredTimestamp = $dueDateTimestamp ?: $startTimestamp;
        if ($preferredTimestamp === null || $preferredTimestamp === '') {
            return '';
        }

        try {
            $startDateTime = new \DateTimeImmutable($preferredTimestamp, new \DateTimeZone('Asia/Manila'));
        } catch (\Throwable) {
            return '';
        }

        $formattedStart = $startDateTime->setTimezone(new \DateTimeZone('Asia/Manila'))->format('M j, Y g:i A');

        if ($endTimestamp === null || $endTimestamp === '') {
            return $formattedStart;
        }

        try {
            $endDateTime = new \DateTimeImmutable($endTimestamp, new \DateTimeZone('Asia/Manila'));
        } catch (\Throwable) {
            return $formattedStart;
        }

        $endDateTime = $endDateTime->setTimezone(new \DateTimeZone('Asia/Manila'));
        $formattedEnd = $endDateTime->format('M j, Y g:i A');

        return $formattedStart === $formattedEnd ? $formattedStart : $formattedStart . ' - ' . $formattedEnd;
    }

    private function normalizePhoneNumber(?string $phoneNumber): ?string
    {
        $digitsOnly = preg_replace('/\D+/', '', (string)($phoneNumber ?? '')) ?? '';

        if ($digitsOnly === '') {
            return null;
        }

        if (str_starts_with($digitsOnly, '09')) {
            $digitsOnly = substr($digitsOnly, 1);
        }

        if (str_starts_with($digitsOnly, '639') && strlen($digitsOnly) === 12) {
            return '+' . $digitsOnly;
        }

        if (preg_match('/^9\d{9}$/', $digitsOnly) === 1) {
            return '+63' . $digitsOnly;
        }

        return null;
    }

    private function isSmsConfigured(): bool
    {
        return $this->twilioAccountSid() !== ''
            && $this->twilioAuthToken() !== ''
            && $this->twilioFromNumber() !== '';
    }

    private function twilioAccountSid(): string
    {
        return trim((string)($_ENV['TWILIO_ACCOUNT_SID'] ?? $_SERVER['TWILIO_ACCOUNT_SID'] ?? ''));
    }

    private function twilioAuthToken(): string
    {
        return trim((string)($_ENV['TWILIO_AUTH_TOKEN'] ?? $_SERVER['TWILIO_AUTH_TOKEN'] ?? ''));
    }

    private function twilioFromNumber(): string
    {
        return trim((string)($_ENV['TWILIO_FROM_NUMBER'] ?? $_SERVER['TWILIO_FROM_NUMBER'] ?? ''));
    }
}
