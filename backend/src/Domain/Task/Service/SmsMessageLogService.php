<?php

namespace App\Domain\Task\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class SmsMessageLogService
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function recordSuccess(
        string $source,
        array $recipients,
        string $message,
        array $responsePayload,
        ?int $taskIdentifier = null,
        ?int $assignedToAccountId = null
    ): void {
        $data = is_array($responsePayload['data'] ?? null) ? $responsePayload['data'] : [];

        $this->insertLog(
            source: $source,
            recipients: $this->normalizeRecipients($data['recipients'] ?? $recipients),
            message: trim((string)($data['message'] ?? '')) ?: $message,
            status: trim((string)($data['status'] ?? 'SUBMITTED')) ?: 'SUBMITTED',
            providerMessageId: $this->normalizeOptionalString($data['_id'] ?? null),
            providerCreatedAt: $this->normalizeProviderTimestamp($data['createdAt'] ?? null),
            responsePayload: $responsePayload,
            errorMessage: null,
            taskIdentifier: $taskIdentifier,
            assignedToAccountId: $assignedToAccountId
        );
    }

    public function recordFailure(
        string $source,
        array $recipients,
        string $message,
        string $errorMessage,
        ?array $responsePayload = null,
        ?int $taskIdentifier = null,
        ?int $assignedToAccountId = null
    ): void {
        $this->insertLog(
            source: $source,
            recipients: $this->normalizeRecipients($recipients),
            message: $message,
            status: 'FAILED',
            providerMessageId: null,
            providerCreatedAt: null,
            responsePayload: $responsePayload,
            errorMessage: $errorMessage,
            taskIdentifier: $taskIdentifier,
            assignedToAccountId: $assignedToAccountId
        );
    }

    private function insertLog(
        string $source,
        array $recipients,
        string $message,
        string $status,
        ?string $providerMessageId,
        ?string $providerCreatedAt,
        ?array $responsePayload,
        ?string $errorMessage,
        ?int $taskIdentifier,
        ?int $assignedToAccountId
    ): void {
        $this->connection->executeStatement(
            'INSERT INTO sms_message_logs (
                provider,
                message_source,
                task_identifier,
                assigned_to_account_id,
                provider_message_id,
                message,
                recipients,
                delivery_status,
                provider_created_at,
                response_payload,
                error_message,
                created_at
             ) VALUES (
                :provider,
                :messageSource,
                :taskIdentifier,
                :assignedToAccountId,
                :providerMessageId,
                :message,
                CAST(:recipients AS JSONB),
                :deliveryStatus,
                :providerCreatedAt,
                CAST(:responsePayload AS JSONB),
                :errorMessage,
                NOW()
             )',
            [
                'provider' => 'TextBee',
                'messageSource' => $source,
                'taskIdentifier' => $taskIdentifier,
                'assignedToAccountId' => $assignedToAccountId,
                'providerMessageId' => $providerMessageId,
                'message' => $message,
                'recipients' => $this->encodeJson($recipients),
                'deliveryStatus' => $status,
                'providerCreatedAt' => $providerCreatedAt,
                'responsePayload' => $this->encodeJson($responsePayload),
                'errorMessage' => $errorMessage,
            ],
            [
                'provider' => ParameterType::STRING,
                'messageSource' => ParameterType::STRING,
                'taskIdentifier' => $taskIdentifier === null ? ParameterType::NULL : ParameterType::INTEGER,
                'assignedToAccountId' => $assignedToAccountId === null ? ParameterType::NULL : ParameterType::INTEGER,
                'providerMessageId' => $providerMessageId === null ? ParameterType::NULL : ParameterType::STRING,
                'message' => ParameterType::STRING,
                'recipients' => ParameterType::STRING,
                'deliveryStatus' => ParameterType::STRING,
                'providerCreatedAt' => $providerCreatedAt === null ? ParameterType::NULL : ParameterType::STRING,
                'responsePayload' => ParameterType::STRING,
                'errorMessage' => $errorMessage === null ? ParameterType::NULL : ParameterType::STRING,
            ]
        );
    }

    private function normalizeRecipients(mixed $recipients): array
    {
        if (!is_array($recipients)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (mixed $recipient): string => trim((string)$recipient), $recipients),
            static fn (string $recipient): bool => $recipient !== ''
        ));
    }

    private function normalizeProviderTimestamp(mixed $value): ?string
    {
        $timestamp = $this->normalizeOptionalString($value);
        if ($timestamp === null) {
            return null;
        }

        try {
            return (new \DateTimeImmutable($timestamp))->format(\DateTimeInterface::ATOM);
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        $normalized = trim((string)($value ?? ''));
        return $normalized === '' ? null : $normalized;
    }

    private function encodeJson(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
