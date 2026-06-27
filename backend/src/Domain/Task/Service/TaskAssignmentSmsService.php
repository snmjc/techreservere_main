<?php

namespace App\Domain\Task\Service;

use App\Shared\Exceptions\DomainValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TaskAssignmentSmsService
{
    private const TEXTBEE_API_BASE = 'https://api.textbee.dev/api/v1';
    private const DEFAULT_TEST_MESSAGE = "hi! <Assigned Staff>.\n\n"
        . "You have task on <Due Date>, <Task Name>: <Reservation Code>.\n"
        . "<Reservation Purpose>\n\n"
        . "If you can't please do contact the Facilities Office for changing of staff";

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly SmsMessageLogService $smsMessageLogService,
        private readonly TaskAssignmentTemplateService $taskAssignmentTemplateService
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

        $messageBody = $this->buildMessageBody($currentTask);
        $rawPhoneNumber = trim((string)($currentTask['assignedStaffPhone'] ?? ''));
        $destinationPhoneNumber = $this->normalizePhoneNumber($rawPhoneNumber);
        if ($destinationPhoneNumber === null) {
            $this->recordFailureSafely(
                'task_assignment',
                $rawPhoneNumber !== '' ? [$rawPhoneNumber] : [],
                $messageBody,
                'Assigned staff has no valid phone number.',
                (int)($currentTask['taskIdentifier'] ?? 0) ?: null,
                $currentAssignedTo
            );
            $this->logger->warning('Task assignment SMS skipped because assigned staff has no valid phone number.', [
                'taskIdentifier' => $currentTask['taskIdentifier'] ?? null,
                'assignedToAccountId' => $currentTask['assignedToAccountId'] ?? null,
            ]);

            return 'Task assignment saved, but the assigned staff has no valid phone number for SMS.';
        }

        if (!$this->isSmsConfigured()) {
            $this->recordFailureSafely(
                'task_assignment',
                [$destinationPhoneNumber],
                $messageBody,
                'TextBee SMS notifications are not configured.',
                (int)($currentTask['taskIdentifier'] ?? 0) ?: null,
                $currentAssignedTo
            );
            $this->logger->warning('Task assignment SMS skipped because TextBee is not configured.', [
                'taskIdentifier' => $currentTask['taskIdentifier'] ?? null,
                'assignedToAccountId' => $currentTask['assignedToAccountId'] ?? null,
            ]);

            return 'Task assignment saved, but SMS notifications are not configured yet.';
        }

        try {
            $this->sendMessage(
                $destinationPhoneNumber,
                $messageBody,
                'task_assignment',
                (int)($currentTask['taskIdentifier'] ?? 0) ?: null,
                $currentAssignedTo
            );

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

    public function sendTestSms(string $phoneNumber, ?string $message = null): array
    {
        $normalizedMessage = trim((string)$message);
        if ($normalizedMessage === '') {
            $normalizedMessage = self::DEFAULT_TEST_MESSAGE;
        }

        if (mb_strlen($normalizedMessage) > 1000) {
            throw new DomainValidationException('Test message must not exceed 1000 characters.');
        }

        $rawPhoneNumber = trim($phoneNumber);
        $destinationPhoneNumber = $this->normalizePhoneNumber($rawPhoneNumber);
        if ($destinationPhoneNumber === null) {
            $this->recordFailureSafely(
                'manual_test',
                $rawPhoneNumber !== '' ? [$rawPhoneNumber] : [],
                $normalizedMessage,
                'Invalid Philippine mobile number.'
            );

            throw new DomainValidationException(
                'Phone number must be a valid Philippine mobile number, such as 09171234567 or +639171234567.'
            );
        }

        if (!$this->isSmsConfigured()) {
            $this->recordFailureSafely(
                'manual_test',
                [$destinationPhoneNumber],
                $normalizedMessage,
                'TextBee SMS notifications are not configured.'
            );
            throw new DomainValidationException('TextBee SMS notifications are not configured.');
        }

        try {
            $providerResponse = $this->sendMessage(
                $destinationPhoneNumber,
                $normalizedMessage,
                'manual_test'
            );
        } catch (\Throwable $exception) {
            $this->logger->error('TextBee test SMS failed.', [
                'recipient' => $destinationPhoneNumber,
                'error' => $exception->getMessage(),
            ]);

            throw new DomainValidationException('The TextBee test SMS could not be delivered.');
        }

        return [
            'recipient' => $destinationPhoneNumber,
            'message' => $normalizedMessage,
            'providerMessageId' => $providerResponse['data']['_id'] ?? null,
            'status' => $providerResponse['data']['status'] ?? null,
            'providerCreatedAt' => $providerResponse['data']['createdAt'] ?? null,
        ];
    }

    private function buildMessageBody(array $task): string
    {
        $messageBody = $this->taskAssignmentTemplateService->renderSmsMessage(
            $this->taskAssignmentTemplateService->buildTaskContext($task)
        );

        return $messageBody !== '' ? $messageBody : self::DEFAULT_TEST_MESSAGE;
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

    private function sendMessage(
        string $destinationPhoneNumber,
        string $message,
        string $source,
        ?int $taskIdentifier = null,
        ?int $assignedToAccountId = null
    ): array
    {
        try {
            $response = $this->httpClient->request(
                'POST',
                sprintf(
                    '%s/gateway/devices/%s/send-sms',
                    self::TEXTBEE_API_BASE,
                    rawurlencode($this->textBeeDeviceId())
                ),
                [
                    'headers' => [
                        'x-api-key' => $this->textBeeApiKey(),
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'recipients' => [$destinationPhoneNumber],
                        'message' => $message,
                    ],
                ],
            );
            $responseContent = $response->getContent();
            $responsePayload = json_decode($responseContent, true);
            if (!is_array($responsePayload)) {
                $responsePayload = ['rawResponse' => $responseContent];
            }

            $this->recordSuccessSafely(
                $source,
                [$destinationPhoneNumber],
                $message,
                $responsePayload,
                $taskIdentifier,
                $assignedToAccountId
            );

            return $responsePayload;
        } catch (\Throwable $exception) {
            $this->recordFailureSafely(
                $source,
                [$destinationPhoneNumber],
                $message,
                $exception->getMessage(),
                $taskIdentifier,
                $assignedToAccountId
            );

            throw $exception;
        }
    }

    private function recordSuccessSafely(
        string $source,
        array $recipients,
        string $message,
        array $responsePayload,
        ?int $taskIdentifier = null,
        ?int $assignedToAccountId = null
    ): void {
        try {
            $this->smsMessageLogService->recordSuccess(
                $source,
                $recipients,
                $message,
                $responsePayload,
                $taskIdentifier,
                $assignedToAccountId
            );
        } catch (\Throwable $exception) {
            $this->logger->error('SMS was submitted, but its database log could not be saved.', [
                'source' => $source,
                'taskIdentifier' => $taskIdentifier,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function recordFailureSafely(
        string $source,
        array $recipients,
        string $message,
        string $errorMessage,
        ?int $taskIdentifier = null,
        ?int $assignedToAccountId = null
    ): void {
        try {
            $this->smsMessageLogService->recordFailure(
                $source,
                $recipients,
                $message,
                $errorMessage,
                null,
                $taskIdentifier,
                $assignedToAccountId
            );
        } catch (\Throwable $exception) {
            $this->logger->error('Failed SMS attempt could not be saved to the database log.', [
                'source' => $source,
                'taskIdentifier' => $taskIdentifier,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function isSmsConfigured(): bool
    {
        return $this->textBeeApiKey() !== ''
            && $this->textBeeDeviceId() !== '';
    }

    private function textBeeApiKey(): string
    {
        return trim((string)($_ENV['API_KEY'] ?? $_SERVER['API_KEY'] ?? ''));
    }

    private function textBeeDeviceId(): string
    {
        return trim((string)($_ENV['DEVICE_ID'] ?? $_SERVER['DEVICE_ID'] ?? ''));
    }
}
