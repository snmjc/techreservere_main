<?php

namespace App\Domain\AuditLog\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class AuditLogRecordService
{
    public function __construct(private readonly Connection $connection)
    {
    }

    // ===== AI GENERATED: recordAuditLog =====
    // Purpose: Record an audit log entry for any domain action
    // Inputs: performedByAccountId, actionPerformed, targetEntityType, targetEntityIdentifier, changeDetails
    // Returns: void
    // Flow:
    // 1. Create AuditLogEntity
    // 2. Persist via repository

    public function recordAuditLog(?int $performedByAccountId, string $actionPerformed, string $targetEntityType, ?int $targetEntityIdentifier = null, ?array $changeDetails = null): void
    {
        $this->connection->insert(
            'audit_logs',
            [
                'performed_by_account_id' => $performedByAccountId,
                'action_performed' => $actionPerformed,
                'target_entity_type' => $targetEntityType,
                'target_entity_identifier' => $targetEntityIdentifier,
                'change_details' => $changeDetails === null ? null : json_encode($changeDetails, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'occurred_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ],
            [
                'performed_by_account_id' => $performedByAccountId === null ? ParameterType::NULL : ParameterType::INTEGER,
                'action_performed' => ParameterType::STRING,
                'target_entity_type' => ParameterType::STRING,
                'target_entity_identifier' => $targetEntityIdentifier === null ? ParameterType::NULL : ParameterType::INTEGER,
                'change_details' => $changeDetails === null ? ParameterType::NULL : ParameterType::STRING,
                'occurred_timestamp' => ParameterType::STRING,
            ]
        );
    }

    // ===== AI GENERATED: getAllAuditLogs =====
    // Purpose: Retrieve all audit log entries (Admin only)
    // Inputs: none
    // Returns: array of audit log data arrays

    public function getAllAuditLogs(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT audit_log_identifier, performed_by_account_id, action_performed, target_entity_type, target_entity_identifier, change_details, occurred_timestamp
             FROM audit_logs
             ORDER BY occurred_timestamp DESC, audit_log_identifier DESC'
        );

        return array_map(function (array $row): array {
            $changeDetails = $row['change_details'];
            if (is_string($changeDetails) && $changeDetails !== '') {
                $decoded = json_decode($changeDetails, true);
                $changeDetails = is_array($decoded) ? $decoded : $changeDetails;
            }

            return [
                'auditLogIdentifier' => isset($row['audit_log_identifier']) ? (int)$row['audit_log_identifier'] : null,
                'performedByAccountId' => isset($row['performed_by_account_id']) ? (int)$row['performed_by_account_id'] : null,
                'actionPerformed' => (string)($row['action_performed'] ?? ''),
                'targetEntityType' => (string)($row['target_entity_type'] ?? ''),
                'targetEntityIdentifier' => isset($row['target_entity_identifier']) ? (int)$row['target_entity_identifier'] : null,
                'changeDetails' => $changeDetails,
                'occurredTimestamp' => !empty($row['occurred_timestamp'])
                    ? (new \DateTimeImmutable((string)$row['occurred_timestamp']))->format(\DateTimeInterface::ATOM)
                    : null,
            ];
        }, $rows);
    }
}
