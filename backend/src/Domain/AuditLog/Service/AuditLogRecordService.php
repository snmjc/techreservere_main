<?php

namespace App\Domain\AuditLog\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class AuditLogRecordService
{
    private bool $schemaReady = false;

    public function __construct(private readonly Connection $connection)
    {
    }

    public function recordAuditLog(
        ?int $performedByAccountId,
        string $actionPerformed,
        string $targetEntityType,
        ?int $targetEntityIdentifier = null,
        ?array $changeDetails = null,
        array $context = []
    ): void {
        $this->ensureSchemaReady();

        $this->connection->insert(
            'audit_logs',
            [
                'performed_by_account_id' => $performedByAccountId,
                'action_performed' => $actionPerformed,
                'target_entity_type' => $targetEntityType,
                'target_entity_identifier' => $targetEntityIdentifier,
                'change_details' => $changeDetails === null ? null : json_encode($changeDetails, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'actor_name' => $context['actorName'] ?? null,
                'actor_role' => $context['actorRole'] ?? null,
                'module_name' => $context['module'] ?? null,
                'target_display_label' => $context['targetDisplayLabel'] ?? null,
                'previous_value_json' => isset($context['previousValue']) ? json_encode($context['previousValue'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
                'updated_value_json' => isset($context['updatedValue']) ? json_encode($context['updatedValue'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
                'reason_remarks' => $context['reason'] ?? null,
                'ip_address' => $context['ipAddress'] ?? null,
                'device_metadata' => $context['deviceMetadata'] ?? null,
                'occurred_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function getAllAuditLogs(array $filters = []): array
    {
        $this->ensureSchemaReady();

        $rows = $this->connection->fetchAllAssociative(
            'SELECT audit_log_identifier, performed_by_account_id, action_performed, target_entity_type, target_entity_identifier,
                    change_details, actor_name, actor_role, module_name, target_display_label, previous_value_json,
                    updated_value_json, reason_remarks, ip_address, device_metadata, occurred_timestamp
             FROM audit_logs
             ORDER BY occurred_timestamp DESC, audit_log_identifier DESC'
        );

        $normalizedRows = array_map(function (array $row): array {
            return [
                'auditLogIdentifier' => isset($row['audit_log_identifier']) ? (int) $row['audit_log_identifier'] : null,
                'performedByAccountId' => isset($row['performed_by_account_id']) ? (int) $row['performed_by_account_id'] : null,
                'actionPerformed' => (string) ($row['action_performed'] ?? ''),
                'targetEntityType' => (string) ($row['target_entity_type'] ?? ''),
                'targetEntityIdentifier' => isset($row['target_entity_identifier']) ? (int) $row['target_entity_identifier'] : null,
                'changeDetails' => $this->decodeJsonColumn($row['change_details'] ?? null),
                'actorName' => $row['actor_name'] === null ? null : (string) $row['actor_name'],
                'actorRole' => $row['actor_role'] === null ? null : (string) $row['actor_role'],
                'module' => $row['module_name'] === null ? null : (string) $row['module_name'],
                'targetDisplayLabel' => $row['target_display_label'] === null ? null : (string) $row['target_display_label'],
                'previousValue' => $this->decodeJsonColumn($row['previous_value_json'] ?? null),
                'updatedValue' => $this->decodeJsonColumn($row['updated_value_json'] ?? null),
                'reason' => $row['reason_remarks'] === null ? null : (string) $row['reason_remarks'],
                'ipAddress' => $row['ip_address'] === null ? null : (string) $row['ip_address'],
                'deviceMetadata' => $row['device_metadata'] === null ? null : (string) $row['device_metadata'],
                'occurredTimestamp' => !empty($row['occurred_timestamp'])
                    ? (new \DateTimeImmutable((string) $row['occurred_timestamp']))->format(\DateTimeInterface::ATOM)
                    : null,
            ];
        }, $rows);

        return array_values(array_filter($normalizedRows, function (array $row) use ($filters): bool {
            $scope = strtolower(trim((string) ($filters['scope'] ?? '')));
            if ($scope === 'equipment_inventory' && !$this->isEquipmentInventoryAuditRow($row)) {
                return false;
            }

            $search = strtolower(trim((string) ($filters['search'] ?? '')));
            if ($search !== '') {
                $haystack = strtolower(implode(' ', array_filter([
                    $row['actorName'] ?? '',
                    $row['actorRole'] ?? '',
                    $row['actionPerformed'] ?? '',
                    $row['module'] ?? '',
                    $row['targetEntityType'] ?? '',
                    $row['targetDisplayLabel'] ?? '',
                    (string) ($row['targetEntityIdentifier'] ?? ''),
                ])));
                if (!str_contains($haystack, $search)) {
                    return false;
                }
            }

            foreach (['actorRole' => 'role', 'actionPerformed' => 'action', 'module' => 'module'] as $field => $filterKey) {
                $filterValue = trim((string) ($filters[$filterKey] ?? ''));
                if ($filterValue !== '' && strcasecmp((string) ($row[$field] ?? ''), $filterValue) !== 0) {
                    return false;
                }
            }

            return true;
        }));
    }

    private function isEquipmentInventoryAuditRow(array $row): bool
    {
        $module = strtolower(trim((string) ($row['module'] ?? '')));
        $targetEntityType = strtolower(trim((string) ($row['targetEntityType'] ?? '')));

        return in_array($module, ['equipment inventory', 'inventory', 'equipment'], true)
            || in_array($targetEntityType, ['equipment', 'equipment_inventory', 'inventory'], true);
    }

    private function ensureSchemaReady(): void
    {
        if ($this->schemaReady) {
            return;
        }

        $columns = $this->connection->fetchFirstColumn(
            "SELECT column_name
             FROM information_schema.columns
             WHERE table_schema = CURRENT_SCHEMA()
               AND table_name = 'audit_logs'"
        );

        if ($columns !== []) {
            $runtimeColumns = [
                'actor_name' => 'ALTER TABLE audit_logs ADD COLUMN actor_name VARCHAR(180) DEFAULT NULL',
                'actor_role' => 'ALTER TABLE audit_logs ADD COLUMN actor_role VARCHAR(80) DEFAULT NULL',
                'module_name' => 'ALTER TABLE audit_logs ADD COLUMN module_name VARCHAR(120) DEFAULT NULL',
                'target_display_label' => 'ALTER TABLE audit_logs ADD COLUMN target_display_label VARCHAR(255) DEFAULT NULL',
                'previous_value_json' => 'ALTER TABLE audit_logs ADD COLUMN previous_value_json JSONB DEFAULT NULL',
                'updated_value_json' => 'ALTER TABLE audit_logs ADD COLUMN updated_value_json JSONB DEFAULT NULL',
                'reason_remarks' => 'ALTER TABLE audit_logs ADD COLUMN reason_remarks TEXT DEFAULT NULL',
                'ip_address' => 'ALTER TABLE audit_logs ADD COLUMN ip_address VARCHAR(80) DEFAULT NULL',
                'device_metadata' => 'ALTER TABLE audit_logs ADD COLUMN device_metadata TEXT DEFAULT NULL',
            ];

            foreach ($runtimeColumns as $columnName => $statement) {
                if (!in_array($columnName, $columns, true)) {
                    $this->connection->executeStatement($statement);
                }
            }
        }

        $this->schemaReady = true;
    }

    private function decodeJsonColumn(mixed $value): mixed
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
