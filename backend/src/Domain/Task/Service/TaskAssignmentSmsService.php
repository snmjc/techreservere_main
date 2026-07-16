<?php

namespace App\Domain\Task\Service;

use App\Domain\Notification\Service\NotificationDispatchService;
use App\Shared\Exceptions\DomainValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class TaskAssignmentSmsService
{
    private const TEXTBEE_API_BASE = 'https://api.textbee.dev/api/v1';
    private const BRAND_NAME = 'TechReserve';
    private const DEFAULT_TEST_MESSAGE = "TechReserve: hi! <Assigned Staff>.\n\n"
        . "You have task on <Due Date>, <Task Name>: <Reservation Code>.\n"
        . "<Reservation Purpose>\n\n"
        . "If you can't please do contact the Facilities Office for changing of staff";

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly SmsMessageLogService $smsMessageLogService,
        private readonly TaskAssignmentTemplateService $taskAssignmentTemplateService,
        private readonly NotificationDispatchService $notificationDispatchService
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

        $this->sendInAppAssignmentNotification($currentAssignedTo, $currentTask);

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

            return sprintf(
                'Task assignment saved, but the SMS notification could not be delivered. %s',
                $this->buildUserFacingFailureReason($exception)
            );
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

            throw new DomainValidationException(sprintf(
                'The TextBee test SMS could not be delivered. %s',
                $this->buildUserFacingFailureReason($exception)
            ));
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

        $messageBody = $messageBody !== '' ? $messageBody : self::DEFAULT_TEST_MESSAGE;

        return $this->ensureBrandSignature($messageBody);
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
        $responsePayload = null;

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
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'recipients' => [$destinationPhoneNumber],
                        'message' => $message,
                    ],
                ],
            );

            $responsePayload = $this->decodeResponsePayload($response);
            $this->assertSuccessfulProviderResponse($response, $responsePayload);

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
                $responsePayload,
                $taskIdentifier,
                $assignedToAccountId
            );

            throw $exception;
        }
    }

    private function sendInAppAssignmentNotification(int $recipientAccountId, array $task): void
    {
        try {
            $this->notificationDispatchService->sendNotification(
                $recipientAccountId,
                self::BRAND_NAME . ' Task Assignment',
                $this->buildNotificationMessage($task),
                'maintenance'
            );
        } catch (\Throwable $exception) {
            $this->logger->error('Task assignment in-app notification failed.', [
                'taskIdentifier' => $task['taskIdentifier'] ?? null,
                'assignedToAccountId' => $recipientAccountId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function buildNotificationMessage(array $task): string
    {
        $taskName = trim((string)($task['taskTitle'] ?? $task['taskType'] ?? 'Assigned task'));
        $reservationCode = trim((string)($task['reservationCode'] ?? ''));
        $schedule = $this->taskAssignmentTemplateService->buildTaskContext($task)['dueDate'] ?? 'the scheduled date';
        $venueName = trim((string)($task['venueName'] ?? $task['facilityName'] ?? ''));

        $parts = [
            sprintf('You have been assigned to %s.', $taskName !== '' ? $taskName : 'a new task'),
            $reservationCode !== '' ? sprintf('Reservation: %s.', $reservationCode) : null,
            $venueName !== '' ? sprintf('Venue: %s.', $venueName) : null,
            $schedule !== '' ? sprintf('Schedule: %s.', $schedule) : null,
        ];

        return implode(' ', array_values(array_filter($parts)));
    }

    private function ensureBrandSignature(string $messageBody): string
    {
        $trimmedMessage = trim($messageBody);
        if ($trimmedMessage === '') {
            return self::DEFAULT_TEST_MESSAGE;
        }

        if (stripos($trimmedMessage, self::BRAND_NAME) === 0) {
            return $trimmedMessage;
        }

        return self::BRAND_NAME . ': ' . $trimmedMessage;
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
        ?array $responsePayload = null,
        ?int $taskIdentifier = null,
        ?int $assignedToAccountId = null
    ): void {
        try {
            $this->smsMessageLogService->recordFailure(
                $source,
                $recipients,
                $message,
                $errorMessage,
                $responsePayload,
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
        return trim((string)(
            $_ENV['TEXTBEE_API_KEY']
            ?? $_SERVER['TEXTBEE_API_KEY']
            ?? $_ENV['API_KEY']
            ?? $_SERVER['API_KEY']
            ?? ''
        ));
    }

    private function textBeeDeviceId(): string
    {
        return trim((string)(
            $_ENV['TEXTBEE_DEVICE_ID']
            ?? $_SERVER['TEXTBEE_DEVICE_ID']
            ?? $_ENV['DEVICE_ID']
            ?? $_SERVER['DEVICE_ID']
            ?? ''
        ));
    }

    private function decodeResponsePayload(ResponseInterface $response): array
    {
        $responseContent = $response->getContent(false);
        $responsePayload = json_decode($responseContent, true);

        if (is_array($responsePayload)) {
            return $responsePayload;
        }

        return ['rawResponse' => $responseContent];
    }

    private function assertSuccessfulProviderResponse(ResponseInterface $response, array $responsePayload): void
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400) {
            throw new \RuntimeException($this->extractProviderErrorMessage($responsePayload, $statusCode));
        }

        $providerStatus = strtoupper(trim((string)($responsePayload['data']['status'] ?? $responsePayload['status'] ?? '')));
        if (in_array($providerStatus, ['FAILED', 'REJECTED', 'ERROR'], true)) {
            throw new \RuntimeException($this->extractProviderErrorMessage($responsePayload, $statusCode));
        }
    }

    private function extractProviderErrorMessage(array $responsePayload, ?int $statusCode = null): string
    {
        $candidates = [
            $responsePayload['message'] ?? null,
            $responsePayload['error'] ?? null,
            $responsePayload['details'] ?? null,
            is_array($responsePayload['data'] ?? null) ? ($responsePayload['data']['message'] ?? null) : null,
            is_array($responsePayload['data'] ?? null) ? ($responsePayload['data']['error'] ?? null) : null,
            is_array($responsePayload['data'] ?? null) ? ($responsePayload['data']['details'] ?? null) : null,
        ];

        foreach ($candidates as $candidate) {
            $normalizedCandidate = trim((string)($candidate ?? ''));
            if ($normalizedCandidate !== '') {
                return $normalizedCandidate;
            }
        }

        if ($statusCode !== null) {
            return sprintf('TextBee returned HTTP %d without a readable error message.', $statusCode);
        }

        return 'TextBee did not return a readable error message.';
    }

    private function buildUserFacingFailureReason(\Throwable $exception): string
    {
        $message = trim($exception->getMessage());
        if ($message === '') {
            return 'Please verify the TextBee device is active, has SMS permission, and can send messages from the connected phone.';
        }

        return $message;
    }
}
