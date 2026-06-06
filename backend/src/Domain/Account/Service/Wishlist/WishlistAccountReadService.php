<?php

namespace App\Domain\Account\Service\Wishlist;

use App\Domain\Account\Service\ClerkInvitationSyncService;
use App\Shared\Utils\AppClock;
use Doctrine\DBAL\Connection;

class WishlistAccountReadService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ClerkInvitationSyncService $clerkInvitationSyncService
    )
    {
    }

    public function getWishlistAccounts(): array
    {
        $this->clerkInvitationSyncService->reconcileAcceptedAccountsFromLocalInvitations();

        $rows = $this->connection->fetchAllAssociative(
            "SELECT DISTINCT ON (LOWER(accounts.email_address))
                    accounts.account_identifier, accounts.id_number, accounts.last_name, accounts.first_name,
                    accounts.email_address, accounts.username, accounts.role_designation, accounts.department,
                    accounts.contact_number, accounts.status, accounts.is_approved, accounts.created_timestamp,
                    accounts.signup_supporting_document_name, accounts.signup_supporting_document_mime_type,
                    accounts.signup_supporting_document_path, accounts.signup_supporting_document_size_bytes,
                    accounts.signup_supporting_document_uploaded_at, accounts.signup_supporting_document_verification_status,
                    staff_info.employee_id_number AS staff_employee_id_number,
                    staff_info.first_name AS staff_first_name,
                    staff_info.last_name AS staff_last_name,
                    staff_info.phone_number AS staff_phone_number,
                    staff_info.role AS staff_role,
                    staff_info.image_url AS staff_image_url,
                    latest_invitation.status AS invite_status,
                    latest_invitation.invited_by AS invite_invited_by,
                    latest_invitation.created_at AS invite_sent_at,
                    latest_invitation.expires_at AS invite_expires_at,
                    latest_invitation.accepted_at AS invite_accepted_at
             FROM accounts
             LEFT JOIN LATERAL (
                SELECT status, invited_by, created_at, expires_at, accepted_at
                FROM invitations
                WHERE LOWER(email) = LOWER(accounts.email_address)
                ORDER BY created_at DESC
                LIMIT 1
             ) latest_invitation ON TRUE
             LEFT JOIN staff_info ON staff_info.account_identifier = accounts.account_identifier
             WHERE COALESCE(accounts.is_approved, FALSE) = FALSE
               AND LOWER(COALESCE(accounts.status, 'pending')) NOT IN ('approved', 'disabled')
             ORDER BY LOWER(accounts.email_address), accounts.created_timestamp DESC"
        );

        return array_map(fn (array $row): array => $this->mapWishlistAccountRow($row), $rows);
    }

    private function mapWishlistAccountRow(array $row): array
    {
        $roleDesignation = (string)($row['role_designation'] ?? 'ROLE_BORROWER');
        $department = strtolower((string)($row['department'] ?? ''));
        $isAdmin = str_contains(strtoupper($roleDesignation), 'ADMIN');
        $normalizedRole = strtoupper($roleDesignation);
        $isEmployee = !$isAdmin && (
            str_contains($normalizedRole, 'STAFF') ||
            str_contains($normalizedRole, 'EMPLOYEE') ||
            str_contains($department, 'staff') ||
            str_contains($department, 'employee') ||
            str_contains($department, 'technical') ||
            str_contains($department, 'maintenance')
        );
        $accountType = $isAdmin ? 'Admin' : ($isEmployee ? 'Employee' : 'User');
        $isFacultyUser = str_contains($department, 'faculty') || str_contains($normalizedRole, 'FACULTY');
        $employeeRoleLabel = $isFacultyUser
            ? 'Faculty'
            : ($department !== '' ? ucwords($department) : 'Technical Staff');
        $roleLabel = $isAdmin
            ? 'Admin'
            : ($isEmployee ? $employeeRoleLabel : ($isFacultyUser ? 'User: Faculty' : 'User: Student'));

        return [
            'accountIdentifier' => (int)$row['account_identifier'],
            'idNumber' => ($isEmployee && !empty($row['staff_employee_id_number'])) ? (string)$row['staff_employee_id_number'] : ($row['id_number'] ?: substr((string)$row['created_timestamp'], 0, 4) . str_pad((string)$row['account_identifier'], 4, '0', STR_PAD_LEFT)),
            'lastName' => ($isEmployee && !empty($row['staff_last_name'])) ? (string)$row['staff_last_name'] : (string)$row['last_name'],
            'firstName' => ($isEmployee && !empty($row['staff_first_name'])) ? (string)$row['staff_first_name'] : (string)$row['first_name'],
            'emailAddress' => (string)$row['email_address'],
            'username' => $row['username'] ? (string)$row['username'] : null,
            'contactNumber' => ($isEmployee && !empty($row['staff_phone_number'])) ? (string)$row['staff_phone_number'] : ($row['contact_number'] ? (string)$row['contact_number'] : null),
            'roleDesignation' => $roleDesignation,
            'roleLabel' => ($isEmployee && !empty($row['staff_role'])) ? (string)$row['staff_role'] : $roleLabel,
            'accountType' => $accountType,
            'accountStatus' => $this->resolveWishlistStatus($row),
            'isApproved' => $this->toDatabaseBoolean($row['is_approved'] ?? false),
            'supportingDocumentName' => $row['signup_supporting_document_name'] ? (string)$row['signup_supporting_document_name'] : null,
            'supportingDocumentMimeType' => $row['signup_supporting_document_mime_type'] ? (string)$row['signup_supporting_document_mime_type'] : null,
            'supportingDocumentPath' => $row['signup_supporting_document_path'] ? (string)$row['signup_supporting_document_path'] : null,
            'supportingDocumentSizeBytes' => isset($row['signup_supporting_document_size_bytes']) ? (int)$row['signup_supporting_document_size_bytes'] : null,
            'supportingDocumentUploadedAt' => $row['signup_supporting_document_uploaded_at'] ? (string)$row['signup_supporting_document_uploaded_at'] : null,
            'supportingDocumentVerificationStatus' => $row['signup_supporting_document_verification_status'] ? (string)$row['signup_supporting_document_verification_status'] : null,
            'supportingDocumentData' => null,
            'registeredAt' => (string)$row['created_timestamp'],
            'inviteStatus' => $row['invite_status'] ? (string)$row['invite_status'] : null,
            'inviteInvitedBy' => $row['invite_invited_by'] ? (string)$row['invite_invited_by'] : null,
            'inviteSentAt' => $row['invite_sent_at'] ? (string)$row['invite_sent_at'] : null,
            'inviteExpiresAt' => $row['invite_expires_at'] ? (string)$row['invite_expires_at'] : null,
            'inviteAcceptedAt' => $row['invite_accepted_at'] ? (string)$row['invite_accepted_at'] : null,
        ];
    }

    private function resolveWishlistStatus(array $row): string
    {
        $status = strtolower((string)($row['status'] ?? 'pending'));
        if (!empty($row['invite_accepted_at'])) {
            return 'verified';
        }

        if ($this->toDatabaseBoolean($row['is_approved'] ?? false) && $status === 'approved') {
            return 'verified';
        }

        if (in_array($status, ['rejected', 'denied'], true)) {
            return $status;
        }

        if (!empty($row['invite_expires_at'])) {
            try {
                $expiresAt = new \DateTimeImmutable((string)$row['invite_expires_at'], AppClock::timezone());
                return $expiresAt < AppClock::now() ? 'expired' : 'unverified';
            } catch (\Throwable) {
                return $status;
            }
        }

        if ($status === 'invited') {
            return 'unverified';
        }

        if ($status === 'approved' || $status === 'verified') {
            return 'verified';
        }

        return 'not_invited';
    }

    private function toDatabaseBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        $normalized = strtolower(trim((string)$value));
        return in_array($normalized, ['1', 't', 'true', 'yes'], true);
    }
}
