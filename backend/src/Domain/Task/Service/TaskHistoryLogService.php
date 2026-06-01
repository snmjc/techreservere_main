<?php

namespace App\Domain\Task\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class TaskHistoryLogService
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function syncHistoryLog(int $taskIdentifier, ?int $reservationIdentifier, ?int $assignedToAccountId): void
    {
        $this->connection->executeStatement(
            'DELETE FROM history_logs WHERE task_assignment_id = :taskIdentifier',
            ['taskIdentifier' => $taskIdentifier],
            ['taskIdentifier' => ParameterType::INTEGER]
        );

        if ($reservationIdentifier === null || $assignedToAccountId === null) {
            return;
        }

        $staffId = $this->connection->fetchOne(
            'SELECT id FROM staff_info WHERE account_identifier = :accountIdentifier',
            ['accountIdentifier' => $assignedToAccountId],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        if (!$staffId) {
            return;
        }

        $this->connection->executeStatement(
            'INSERT INTO history_logs (staff_id, reservation_id, task_assignment_id)
             VALUES (:staffId, :reservationIdentifier, :taskIdentifier)
             ON CONFLICT (staff_id, reservation_id, task_assignment_id) DO NOTHING',
            [
                'staffId' => (int)$staffId,
                'reservationIdentifier' => $reservationIdentifier,
                'taskIdentifier' => $taskIdentifier,
            ],
            [
                'staffId' => ParameterType::INTEGER,
                'reservationIdentifier' => ParameterType::INTEGER,
                'taskIdentifier' => ParameterType::INTEGER,
            ]
        );
    }
}
