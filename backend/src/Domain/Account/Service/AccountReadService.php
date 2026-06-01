<?php

namespace App\Domain\Account\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class AccountReadService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AccountResponseMapperService $accountResponseMapperService
    ) {
    }

    public function getAcceptedAccounts(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            "WITH accepted_accounts AS (
                SELECT accounts.account_identifier, accounts.id_number, accounts.last_name, accounts.first_name,
                       accounts.email_address, accounts.role_designation, accounts.department, accounts.contact_number,
                       accounts.profile_photo_data,
                       staff_info.employee_id_number AS staff_employee_id_number,
                       staff_info.first_name AS staff_first_name,
                       staff_info.last_name AS staff_last_name,
                       staff_info.phone_number AS staff_phone_number,
                       staff_info.role AS staff_role,
                       staff_info.image_url AS staff_image_url,
                       accounts.status, accounts.is_approved, accounts.is_active, accounts.created_timestamp,
                       accounts.last_login_timestamp,
                       latest_invitation.created_at AS invite_sent_at,
                       latest_invitation.expires_at AS invite_expires_at,
                       latest_invitation.accepted_at AS invite_accepted_at
                FROM accounts
                LEFT JOIN staff_info ON staff_info.account_identifier = accounts.account_identifier
                LEFT JOIN LATERAL (
                   SELECT created_at, expires_at, accepted_at
                   FROM invitations
                   WHERE LOWER(email) = LOWER(accounts.email_address)
                   ORDER BY created_at DESC
                   LIMIT 1
                ) latest_invitation ON TRUE
                WHERE COALESCE(accounts.is_approved, FALSE) = TRUE
                  AND LOWER(COALESCE(accounts.status, 'pending')) IN ('approved', 'disabled')
                  AND (
                    latest_invitation.accepted_at IS NOT NULL
                    OR accounts.role_designation IN ('ROLE_ADMIN', 'ADMIN', 'ROLE_STAFF')
                  )
             ),
             deduped_by_email AS (
                SELECT DISTINCT ON (LOWER(email_address)) *
                FROM accepted_accounts
                ORDER BY LOWER(email_address), created_timestamp DESC, account_identifier DESC
             ),
             deduped_by_id AS (
                SELECT DISTINCT ON (COALESCE(NULLIF(id_number, ''), account_identifier::text)) *
                FROM deduped_by_email
                ORDER BY COALESCE(NULLIF(id_number, ''), account_identifier::text), created_timestamp DESC, account_identifier DESC
             ),
             deduped_by_phone AS (
                SELECT DISTINCT ON (COALESCE(NULLIF(contact_number, ''), account_identifier::text)) *
                FROM deduped_by_id
                ORDER BY COALESCE(NULLIF(contact_number, ''), account_identifier::text), created_timestamp DESC, account_identifier DESC
             )
             SELECT *
             FROM deduped_by_phone
             ORDER BY created_timestamp DESC"
        );

        return array_map(fn (array $row): array => $this->accountResponseMapperService->mapAccountRow($row), $rows);
    }

    public function getMappedAccountById(int $accountIdentifier): ?array
    {
        $row = $this->connection->fetchAssociative(
            "SELECT accounts.account_identifier, accounts.id_number, accounts.last_name, accounts.first_name, accounts.email_address, accounts.role_designation,
                    accounts.department, accounts.contact_number, accounts.status, accounts.is_approved, accounts.is_active, accounts.created_timestamp,
                    accounts.profile_photo_data,
                    staff_info.employee_id_number AS staff_employee_id_number,
                    staff_info.first_name AS staff_first_name,
                    staff_info.last_name AS staff_last_name,
                    staff_info.phone_number AS staff_phone_number,
                    staff_info.role AS staff_role,
                    staff_info.image_url AS staff_image_url,
                    accounts.last_login_timestamp,
                    latest_invitation.created_at AS invite_sent_at,
                    latest_invitation.expires_at AS invite_expires_at,
                    latest_invitation.accepted_at AS invite_accepted_at
             FROM accounts
             LEFT JOIN staff_info ON staff_info.account_identifier = accounts.account_identifier
             LEFT JOIN LATERAL (
                SELECT created_at, expires_at, accepted_at
                FROM invitations
                WHERE LOWER(email) = LOWER(accounts.email_address)
                ORDER BY created_at DESC
                LIMIT 1
             ) latest_invitation ON TRUE
             WHERE accounts.account_identifier = :accountIdentifier",
            ['accountIdentifier' => $accountIdentifier],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        return $row ? $this->accountResponseMapperService->mapAccountRow($row) : null;
    }

    public function getSettingsAccountById(int $accountIdentifier): ?array
    {
        $row = $this->connection->fetchAssociative(
            "SELECT accounts.account_identifier, accounts.id_number, accounts.last_name, accounts.first_name, accounts.email_address, accounts.role_designation,
                    accounts.department, accounts.contact_number, accounts.status, accounts.is_approved, accounts.is_active, accounts.created_timestamp,
                    accounts.last_login_timestamp, accounts.profile_photo_data,
                    staff_info.employee_id_number AS staff_employee_id_number,
                    staff_info.first_name AS staff_first_name,
                    staff_info.last_name AS staff_last_name,
                    staff_info.phone_number AS staff_phone_number,
                    staff_info.role AS staff_role,
                    staff_info.image_url AS staff_image_url
             FROM accounts
             LEFT JOIN staff_info ON staff_info.account_identifier = accounts.account_identifier
             WHERE accounts.account_identifier = :accountIdentifier",
            ['accountIdentifier' => $accountIdentifier],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        if (!$row) {
            return null;
        }

        $mapped = $this->accountResponseMapperService->mapAccountRow($row + [
            'invite_sent_at' => null,
            'invite_expires_at' => null,
            'invite_accepted_at' => null,
        ]);
        $mapped['profilePhotoData'] = $row['profile_photo_data'] ? (string)$row['profile_photo_data'] : null;

        return $mapped;
    }

    public function getEmployeeWorkLogs(int $accountIdentifier): array
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT
                history_logs.id AS history_log_id,
                history_logs.staff_id,
                history_logs.reservation_id,
                history_logs.task_assignment_id,
                tasks.task_identifier,
                tasks.task_title,
                tasks.task_description,
                tasks.task_type,
                tasks.task_status,
                tasks.assigned_to_account_id,
                tasks.due_date_timestamp,
                tasks.created_timestamp,
                tasks.updated_timestamp,
                reservations.reservation_identifier,
                reservations.reservation_code,
                reservations.organization_name,
                reservations.event_date_time,
                reservations.purpose_description,
                reservations.activity_type,
                reservations.current_status AS reservation_status,
                reservations.requested_equipment_list,
                reservations.requested_quantity,
                reservations.priority_level
             FROM history_logs
             INNER JOIN staff_info ON staff_info.id = history_logs.staff_id
             INNER JOIN tasks ON tasks.task_identifier = history_logs.task_assignment_id
             INNER JOIN reservations ON reservations.reservation_identifier = history_logs.reservation_id
             WHERE staff_info.account_identifier = :accountIdentifier
             ORDER BY COALESCE(tasks.due_date_timestamp, tasks.created_timestamp) DESC, history_logs.id DESC",
            ['accountIdentifier' => $accountIdentifier],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        return array_map(fn (array $row): array => $this->accountResponseMapperService->mapEmployeeWorkLogRow($row), $rows);
    }

    public function getAccountStateById(int $accountIdentifier): ?array
    {
        $account = $this->connection->fetchAssociative(
            'SELECT account_identifier, id_number, email_address, department, clerk_user_id, status, is_approved, is_active
             FROM accounts
             WHERE account_identifier = :accountIdentifier',
            ['accountIdentifier' => $accountIdentifier],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        return $account ?: null;
    }

    public function hasDuplicateStaffPhone(string $phoneNumber, int $accountIdentifier): bool
    {
        return (bool)$this->connection->fetchOne(
            'SELECT 1 FROM staff_info WHERE phone_number = :phoneNumber AND account_identifier <> :accountIdentifier',
            ['phoneNumber' => $phoneNumber, 'accountIdentifier' => $accountIdentifier],
            ['phoneNumber' => ParameterType::STRING, 'accountIdentifier' => ParameterType::INTEGER]
        );
    }
}
