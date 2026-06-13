<?php

namespace App\Domain\Account\Service\Wishlist;

use Doctrine\DBAL\Connection;
use App\Domain\Account\Service\SignupSupportingDocumentStorageService;

class WishlistAccountReadService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly SignupSupportingDocumentStorageService $signupSupportingDocumentStorageService
    )
    {
    }

    public function getWishlistAccounts(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT DISTINCT ON (LOWER(accounts.email_address))
                    accounts.account_identifier, accounts.id_number, accounts.last_name, accounts.first_name,
                    accounts.email_address, accounts.username, accounts.role_designation, accounts.department,
                    accounts.contact_number, accounts.status, accounts.is_approved, accounts.is_verified,
                    accounts.verification_status, accounts.invitation_status, accounts.clerk_user_id, accounts.invited_at, accounts.approved_at,
                    accounts.created_timestamp, accounts.updated_timestamp,
                    accounts.signup_supporting_document_name, accounts.signup_supporting_document_mime_type,
                    accounts.signup_supporting_document_data, accounts.signup_supporting_document_path,
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
             WHERE LOWER(COALESCE(accounts.status, 'pending')) NOT IN ('active', 'approved', 'accepted')
               AND LOWER(COALESCE(accounts.invitation_status, 'not_sent')) <> 'accepted'
             ORDER BY
                LOWER(accounts.email_address),
                CASE
                    WHEN LOWER(COALESCE(accounts.invitation_status, 'not_sent')) = 'sent' THEN 0
                    WHEN LOWER(COALESCE(accounts.status, 'pending')) = 'invited' THEN 1
                    WHEN LOWER(COALESCE(accounts.verification_status, 'unverified')) = 'verified' THEN 2
                    ELSE 3
                END,
                latest_invitation.created_at DESC NULLS LAST,
                accounts.updated_timestamp DESC NULLS LAST,
                accounts.created_timestamp DESC NULLS LAST,
                accounts.account_identifier DESC"
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
        $employeeRoleLabel = str_contains($department, 'faculty') || str_contains($normalizedRole, 'FACULTY')
            ? 'Faculty'
            : ($department !== '' ? ucwords($department) : 'Technical Staff');
        $roleLabel = $isAdmin ? 'Admin' : ($isEmployee ? $employeeRoleLabel : 'User: Student');

        $supportingDocumentPath = $this->resolveAvailableSupportingDocumentPath($row);

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
            'isVerified' => $this->toDatabaseBoolean($row['is_verified'] ?? false),
            'verificationStatus' => !empty($row['verification_status']) ? (string)$row['verification_status'] : null,
            'invitationStatus' => !empty($row['invitation_status']) ? (string)$row['invitation_status'] : 'not_sent',
            'clerkUserId' => !empty($row['clerk_user_id']) ? (string)$row['clerk_user_id'] : null,
            'supportingDocumentName' => $supportingDocumentPath !== null && $row['signup_supporting_document_name']
                ? (string)$row['signup_supporting_document_name']
                : null,
            'supportingDocumentMimeType' => $supportingDocumentPath !== null && $row['signup_supporting_document_mime_type']
                ? (string)$row['signup_supporting_document_mime_type']
                : null,
            'supportingDocumentData' => $row['signup_supporting_document_data'] ? (string)$row['signup_supporting_document_data'] : null,
            'supportingDocumentPath' => $supportingDocumentPath,
            'registeredAt' => (string)$row['created_timestamp'],
            'inviteStatus' => $row['invite_status'] ? (string)$row['invite_status'] : null,
            'inviteInvitedBy' => $row['invite_invited_by'] ? (string)$row['invite_invited_by'] : null,
            'inviteSentAt' => $row['invite_sent_at'] ? (string)$row['invite_sent_at'] : null,
            'inviteExpiresAt' => $row['invite_expires_at'] ? (string)$row['invite_expires_at'] : null,
            'inviteAcceptedAt' => $row['invite_accepted_at'] ? (string)$row['invite_accepted_at'] : null,
            'invitedAt' => $row['invited_at'] ? (string)$row['invited_at'] : null,
            'approvedAt' => $row['approved_at'] ? (string)$row['approved_at'] : null,
        ];
    }

    private function resolveWishlistStatus(array $row): string
    {
        $status = strtolower((string)($row['status'] ?? 'pending'));
        $isApproved = $this->toDatabaseBoolean($row['is_approved'] ?? false);
        $isVerified = $this->toDatabaseBoolean($row['is_verified'] ?? false);
        $invitationStatus = strtolower((string)($row['invitation_status'] ?? $row['invite_status'] ?? 'not_sent'));

        if ($status === 'active' && !empty($row['clerk_user_id'])) {
            return 'approved';
        }

        if ($invitationStatus === 'accepted') {
            return 'approved';
        }

        if (in_array($status, ['rejected', 'denied'], true)) {
            return $status;
        }

        if ($status === 'invited' || $invitationStatus === 'sent') {
            if (!empty($row['invite_expires_at'])) {
                try {
                    $expiresAt = new \DateTimeImmutable((string)$row['invite_expires_at']);
                    return $expiresAt < new \DateTimeImmutable() ? 'expired' : 'verified';
                } catch (\Throwable) {
                    return 'verified';
                }
            }

            return 'verified';
        }

        if ($isVerified || strtolower((string)($row['verification_status'] ?? '')) === 'verified' || $status === 'verified') {
            return 'verified';
        }

        if (in_array($status, ['approved', 'active'], true)) {
            return $isApproved ? 'approved' : 'verified';
        }

        return 'unverified';
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

    private function resolveAvailableSupportingDocumentPath(array $row): ?string
    {
        $relativePath = !empty($row['signup_supporting_document_path'])
            ? (string)$row['signup_supporting_document_path']
            : '';

        if ($relativePath === '') {
            return null;
        }

        return $this->signupSupportingDocumentStorageService->fileExists($relativePath)
            ? $relativePath
            : null;
    }
}
