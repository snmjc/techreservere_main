<?php

namespace App\Domain\Account\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class AccountConflictLookupService
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function findByEmail(string $emailAddress): ?array
    {
        $account = $this->connection->fetchAssociative(
            'SELECT account_identifier, id_number, first_name, last_name, email_address, username, role_designation,
                    department, status, is_approved, is_active, invitation_status, clerk_user_id, created_timestamp, updated_timestamp
             FROM accounts
             WHERE LOWER(email_address) = LOWER(:emailAddress)
             ORDER BY
                CASE
                    WHEN COALESCE(NULLIF(clerk_user_id, \'\'), \'\') <> \'\' THEN 0
                    WHEN LOWER(COALESCE(invitation_status, \'not_sent\')) = \'accepted\' THEN 1
                    WHEN LOWER(COALESCE(status, \'pending\')) IN (\'active\', \'approved\', \'accepted\') THEN 2
                    WHEN LOWER(COALESCE(invitation_status, \'not_sent\')) = \'sent\' THEN 3
                    WHEN LOWER(COALESCE(status, \'pending\')) = \'invited\' THEN 4
                    ELSE 5
                END,
                updated_timestamp DESC NULLS LAST,
                created_timestamp DESC NULLS LAST,
                account_identifier DESC
             LIMIT 1',
            ['emailAddress' => $emailAddress],
            ['emailAddress' => ParameterType::STRING]
        );

        return $account ?: null;
    }

    public function findByIdNumber(string $idNumber): ?array
    {
        $account = $this->connection->fetchAssociative(
            'SELECT accounts.account_identifier,
                    COALESCE(staff_info.employee_id_number, accounts.id_number) AS id_number,
                    COALESCE(staff_info.first_name, accounts.first_name) AS first_name,
                    COALESCE(staff_info.last_name, accounts.last_name) AS last_name,
                    accounts.email_address, accounts.username, accounts.role_designation,
                    COALESCE(staff_info.role, accounts.department) AS department,
                    accounts.status, accounts.is_approved, accounts.is_active, accounts.clerk_user_id, accounts.created_timestamp
             FROM accounts
             LEFT JOIN staff_info ON staff_info.account_identifier = accounts.account_identifier
             WHERE accounts.id_number = :idNumber OR staff_info.employee_id_number = :idNumber
             LIMIT 1',
            ['idNumber' => $idNumber],
            ['idNumber' => ParameterType::STRING]
        );

        return $account ?: null;
    }

    public function findStaffByPhone(string $phone): ?array
    {
        $account = $this->connection->fetchAssociative(
            "SELECT accounts.account_identifier,
                    COALESCE(staff_info.employee_id_number, accounts.id_number) AS id_number,
                    COALESCE(staff_info.first_name, accounts.first_name) AS first_name,
                    COALESCE(staff_info.last_name, accounts.last_name) AS last_name,
                    accounts.email_address, accounts.username, accounts.role_designation,
                    COALESCE(staff_info.role, accounts.department) AS department,
                    COALESCE(staff_info.phone_number, accounts.contact_number) AS contact_number,
                    accounts.status, accounts.is_approved, accounts.is_active, accounts.clerk_user_id, accounts.created_timestamp
             FROM accounts
             LEFT JOIN staff_info ON staff_info.account_identifier = accounts.account_identifier
             WHERE (accounts.contact_number = :phone OR staff_info.phone_number = :phone)
               AND accounts.role_designation = 'ROLE_STAFF'
             LIMIT 1",
            ['phone' => $phone],
            ['phone' => ParameterType::STRING]
        );

        return $account ?: null;
    }

    public function buildDuplicateAccountMessage(string $fieldName, array $account): string
    {
        $fullName = trim((string)($account['first_name'] ?? '') . ' ' . (string)($account['last_name'] ?? ''));
        $status = $this->formatConflictStatus(
            (string)($account['status'] ?? 'pending'),
            $this->toDatabaseBoolean($account['is_approved'] ?? false)
        );
        $accountType = $this->resolveConflictAccountType($account);

        return sprintf(
            'An account with this %s already exists: %s (%s, %s, %s). Check Manage Accounts or switch Requests Hub filters.',
            $fieldName,
            $fullName !== '' ? $fullName : (string)($account['email_address'] ?? 'Unknown account'),
            (string)($account['email_address'] ?? 'No email'),
            $accountType,
            $status
        );
    }

    public function normalizeConflict(array $account, string $matchedField): array
    {
        return [
            'matchedField' => $matchedField,
            'accountIdentifier' => (int)($account['account_identifier'] ?? 0),
            'idNumber' => $account['id_number'] ?? null,
            'firstName' => (string)($account['first_name'] ?? ''),
            'lastName' => (string)($account['last_name'] ?? ''),
            'emailAddress' => (string)($account['email_address'] ?? ''),
            'accountType' => $this->resolveConflictAccountType($account),
            'status' => (string)($account['status'] ?? 'pending'),
            'isApproved' => $this->toDatabaseBoolean($account['is_approved'] ?? false),
            'isActive' => $this->toDatabaseBoolean($account['is_active'] ?? false),
        ];
    }

    private function resolveConflictAccountType(array $account): string
    {
        $roleDesignation = strtoupper((string)($account['role_designation'] ?? ''));
        $department = strtolower((string)($account['department'] ?? ''));

        if (str_contains($roleDesignation, 'ADMIN')) {
            return 'Admin';
        }

        if (
            str_contains($roleDesignation, 'STAFF') ||
            str_contains($roleDesignation, 'EMPLOYEE') ||
            str_contains($department, 'staff') ||
            str_contains($department, 'employee') ||
            str_contains($department, 'technical') ||
            str_contains($department, 'maintenance')
        ) {
            return 'Employee';
        }

        return 'User';
    }

    private function formatConflictStatus(string $status, bool $isApproved): string
    {
        $normalizedStatus = strtolower($status);

        if ($isApproved || $normalizedStatus === 'approved') {
            return 'Verified';
        }

        if ($normalizedStatus === 'rejected') {
            return 'Denied';
        }

        if ($normalizedStatus === 'invited') {
            return 'Unverified';
        }

        return 'Unverified';
    }

    private function toDatabaseBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        return in_array(strtolower(trim((string)$value)), ['1', 't', 'true', 'yes'], true);
    }
}
