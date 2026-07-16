<?php

namespace App\Domain\Reservation\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class ReservationMetadataService
{
    private bool $schemaReady = false;

    public function __construct(private readonly Connection $connection)
    {
    }

    public function ensureSchemaReady(): void
    {
        if ($this->schemaReady) {
            return;
        }

        $columns = $this->connection->fetchFirstColumn(
            "SELECT column_name
             FROM information_schema.columns
             WHERE table_schema = CURRENT_SCHEMA()
               AND table_name = 'reservations'"
        );

        if ($columns !== []) {
            $runtimeColumns = [
                'admin_remarks' => 'ALTER TABLE reservations ADD COLUMN admin_remarks TEXT DEFAULT NULL',
                'approval_remarks' => 'ALTER TABLE reservations ADD COLUMN approval_remarks TEXT DEFAULT NULL',
                'denial_reason' => 'ALTER TABLE reservations ADD COLUMN denial_reason TEXT DEFAULT NULL',
                'cancellation_reason' => 'ALTER TABLE reservations ADD COLUMN cancellation_reason TEXT DEFAULT NULL',
                'completion_remarks' => 'ALTER TABLE reservations ADD COLUMN completion_remarks TEXT DEFAULT NULL',
                'manual_override_reason' => 'ALTER TABLE reservations ADD COLUMN manual_override_reason TEXT DEFAULT NULL',
            ];

            foreach ($runtimeColumns as $columnName => $statement) {
                if (!in_array($columnName, $columns, true)) {
                    $this->connection->executeStatement($statement);
                }
            }
        }

        $this->connection->executeStatement(
            'CREATE TABLE IF NOT EXISTS reservation_remark_events (
                reservation_remark_event_identifier SERIAL PRIMARY KEY,
                reservation_identifier INT NOT NULL REFERENCES reservations(reservation_identifier) ON DELETE CASCADE,
                author_account_id INT DEFAULT NULL,
                author_name VARCHAR(180) DEFAULT NULL,
                author_role VARCHAR(80) DEFAULT NULL,
                remark_type VARCHAR(80) NOT NULL,
                content TEXT NOT NULL,
                occurred_timestamp TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $this->schemaReady = true;
    }

    public function updateStatusRemarks(
        int $reservationIdentifier,
        string $status,
        ?string $reason,
        ?int $authorAccountId = null,
        ?string $authorName = null,
        ?string $authorRole = null
    ): void {
        $normalizedReason = $this->normalizeNullableString($reason);
        $statusKey = strtolower(trim($status));

        $payload = [
            'admin_remarks' => null,
            'approval_remarks' => null,
            'denial_reason' => null,
            'cancellation_reason' => null,
            'completion_remarks' => null,
            'manual_override_reason' => null,
        ];

        $remarkType = null;
        switch ($statusKey) {
            case 'approved':
                $payload['approval_remarks'] = $normalizedReason;
                $payload['admin_remarks'] = $normalizedReason;
                $remarkType = 'approval_remarks';
                break;
            case 'rejected':
                $payload['denial_reason'] = $normalizedReason;
                $payload['admin_remarks'] = $normalizedReason;
                $remarkType = 'denial_reason';
                break;
            case 'cancelled':
            case 'canceled':
                $payload['cancellation_reason'] = $normalizedReason;
                $remarkType = 'cancellation_reason';
                break;
            case 'completed':
                $payload['completion_remarks'] = $normalizedReason;
                $remarkType = 'completion_remarks';
                break;
            default:
                $payload['manual_override_reason'] = $normalizedReason;
                $payload['admin_remarks'] = $normalizedReason;
                $remarkType = $normalizedReason !== null ? 'manual_override_reason' : null;
                break;
        }

        $this->connection->executeStatement(
            'UPDATE reservations
             SET admin_remarks = COALESCE(:adminRemarks, admin_remarks),
                 approval_remarks = COALESCE(:approvalRemarks, approval_remarks),
                 denial_reason = COALESCE(:denialReason, denial_reason),
                 cancellation_reason = COALESCE(:cancellationReason, cancellation_reason),
                 completion_remarks = COALESCE(:completionRemarks, completion_remarks),
                 manual_override_reason = COALESCE(:manualOverrideReason, manual_override_reason)
             WHERE reservation_identifier = :reservationIdentifier',
            [
                'adminRemarks' => $payload['admin_remarks'],
                'approvalRemarks' => $payload['approval_remarks'],
                'denialReason' => $payload['denial_reason'],
                'cancellationReason' => $payload['cancellation_reason'],
                'completionRemarks' => $payload['completion_remarks'],
                'manualOverrideReason' => $payload['manual_override_reason'],
                'reservationIdentifier' => $reservationIdentifier,
            ],
            [
                'reservationIdentifier' => ParameterType::INTEGER,
            ]
        );

        if ($remarkType !== null && $normalizedReason !== null) {
            $this->recordRemarkEvent(
                $reservationIdentifier,
                $remarkType,
                $normalizedReason,
                $authorAccountId,
                $authorName,
                $authorRole
            );
        }
    }

    public function fetchMetadataByReservationIds(array $reservationIdentifiers): array
    {
        $reservationIdentifiers = array_values(array_filter(array_map('intval', $reservationIdentifiers), static fn (int $value): bool => $value > 0));
        if ($reservationIdentifiers === []) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT reservation_identifier, admin_remarks, approval_remarks, denial_reason,
                    cancellation_reason, completion_remarks, manual_override_reason
             FROM reservations
             WHERE reservation_identifier IN (:reservationIdentifiers)',
            ['reservationIdentifiers' => $reservationIdentifiers],
            ['reservationIdentifiers' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
        );

        $events = $this->connection->fetchAllAssociative(
            'SELECT reservation_identifier, author_account_id, author_name, author_role, remark_type, content, occurred_timestamp
             FROM reservation_remark_events
             WHERE reservation_identifier IN (:reservationIdentifiers)
             ORDER BY occurred_timestamp DESC, reservation_remark_event_identifier DESC',
            ['reservationIdentifiers' => $reservationIdentifiers],
            ['reservationIdentifiers' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
        );

        $eventsByReservation = [];
        foreach ($events as $event) {
            $reservationIdentifier = (int) $event['reservation_identifier'];
            $eventsByReservation[$reservationIdentifier] ??= [];
            $eventsByReservation[$reservationIdentifier][] = [
                'authorAccountId' => isset($event['author_account_id']) ? (int) $event['author_account_id'] : null,
                'authorName' => $event['author_name'] === null ? null : (string) $event['author_name'],
                'authorRole' => $event['author_role'] === null ? null : (string) $event['author_role'],
                'remarkType' => (string) $event['remark_type'],
                'content' => (string) $event['content'],
                'occurredTimestamp' => (new \DateTimeImmutable((string) $event['occurred_timestamp']))->format(\DateTimeInterface::ATOM),
            ];
        }

        $metadataByReservation = [];
        foreach ($rows as $row) {
            $reservationIdentifier = (int) $row['reservation_identifier'];
            $metadataByReservation[$reservationIdentifier] = [
                'adminRemarks' => $this->normalizeNullableString($row['admin_remarks'] ?? null),
                'approvalRemarks' => $this->normalizeNullableString($row['approval_remarks'] ?? null),
                'denialReason' => $this->normalizeNullableString($row['denial_reason'] ?? null),
                'cancellationReason' => $this->normalizeNullableString($row['cancellation_reason'] ?? null),
                'completionRemarks' => $this->normalizeNullableString($row['completion_remarks'] ?? null),
                'manualOverrideReason' => $this->normalizeNullableString($row['manual_override_reason'] ?? null),
                'remarkEvents' => $eventsByReservation[$reservationIdentifier] ?? [],
            ];
        }

        return $metadataByReservation;
    }

    private function recordRemarkEvent(
        int $reservationIdentifier,
        string $remarkType,
        string $content,
        ?int $authorAccountId,
        ?string $authorName,
        ?string $authorRole
    ): void {
        $this->connection->insert(
            'reservation_remark_events',
            [
                'reservation_identifier' => $reservationIdentifier,
                'author_account_id' => $authorAccountId,
                'author_name' => $this->normalizeNullableString($authorName),
                'author_role' => $this->normalizeNullableString($authorRole),
                'remark_type' => $remarkType,
                'content' => $content,
                'occurred_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]
        );
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));
        return $normalized === '' ? null : $normalized;
    }
}
