<?php

namespace App\Domain\Account\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class StaffInfoWriterService
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function upsertStaffInfo(
        int $accountIdentifier,
        string $employeeIdNumber,
        string $firstName,
        string $lastName,
        string $phoneNumber,
        string $role,
        ?string $imageUrl
    ): void {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->connection->executeStatement(
            'INSERT INTO staff_info
                (account_identifier, employee_id_number, first_name, last_name, phone_number, role, image_url, created_timestamp, updated_timestamp)
             VALUES
                (:accountIdentifier, :employeeIdNumber, :firstName, :lastName, :phoneNumber, :role, :imageUrl, :createdTimestamp, :updatedTimestamp)
             ON CONFLICT (account_identifier) WHERE account_identifier IS NOT NULL
             DO UPDATE SET
                employee_id_number = EXCLUDED.employee_id_number,
                first_name = EXCLUDED.first_name,
                last_name = EXCLUDED.last_name,
                phone_number = EXCLUDED.phone_number,
                role = EXCLUDED.role,
                image_url = COALESCE(EXCLUDED.image_url, staff_info.image_url),
                updated_timestamp = EXCLUDED.updated_timestamp',
            [
                'accountIdentifier' => $accountIdentifier,
                'employeeIdNumber' => $employeeIdNumber,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'phoneNumber' => $phoneNumber,
                'role' => $role,
                'imageUrl' => $imageUrl !== '' ? $imageUrl : null,
                'createdTimestamp' => $now,
                'updatedTimestamp' => $now,
            ],
            [
                'accountIdentifier' => ParameterType::INTEGER,
                'employeeIdNumber' => ParameterType::STRING,
                'firstName' => ParameterType::STRING,
                'lastName' => ParameterType::STRING,
                'phoneNumber' => ParameterType::STRING,
                'role' => ParameterType::STRING,
                'imageUrl' => $imageUrl === null || $imageUrl === '' ? ParameterType::NULL : ParameterType::STRING,
                'createdTimestamp' => ParameterType::STRING,
                'updatedTimestamp' => ParameterType::STRING,
            ]
        );
    }
}
