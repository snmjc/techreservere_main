<?php

namespace App\Domain\Task\Service;

use App\Shared\Exceptions\DomainValidationException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class TaskLinkedRecordValidator
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function validateLinkedRecords(?int $reservationIdentifier, ?int $assignedToAccountId): void
    {
        if ($reservationIdentifier !== null && !$this->reservationExists($reservationIdentifier)) {
            throw new DomainValidationException('Selected reservation was not found.');
        }

        if ($assignedToAccountId !== null && !$this->activeStaffExists($assignedToAccountId)) {
            throw new DomainValidationException('Selected assigned staff was not found or is inactive.');
        }
    }

    private function reservationExists(int $reservationIdentifier): bool
    {
        return (bool)$this->connection->fetchOne(
            'SELECT 1 FROM reservations WHERE reservation_identifier = :reservationIdentifier',
            ['reservationIdentifier' => $reservationIdentifier],
            ['reservationIdentifier' => ParameterType::INTEGER]
        );
    }

    private function activeStaffExists(int $assignedToAccountId): bool
    {
        return (bool)$this->connection->fetchOne(
            "SELECT 1
             FROM accounts
             LEFT JOIN staff_info ON staff_info.account_identifier = accounts.account_identifier
             WHERE accounts.account_identifier = :accountIdentifier
               AND accounts.role_designation = 'ROLE_STAFF'
               AND COALESCE(accounts.is_active, TRUE) = TRUE",
            ['accountIdentifier' => $assignedToAccountId],
            ['accountIdentifier' => ParameterType::INTEGER]
        );
    }
}
