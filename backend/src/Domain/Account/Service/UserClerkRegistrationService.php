<?php

namespace App\Domain\Account\Service;

use App\Domain\Account\Entity\AccountEntity;
use App\Domain\Account\Repository\AccountRepository;
use App\Shared\Utils\AccountUsername;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class UserClerkRegistrationService
{
    private const ADMIN_EMAIL_ALLOWLIST = [
        'smmojica@fit.edu.ph',
    ];

    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly Connection $connection,
        private readonly ClerkInvitationSyncService $clerkInvitationSyncService
    ) {
    }

    public function register(array $requestBody): array
    {
        $registration = $this->normalizeRegistrationRequest($requestBody);

        if ($this->hasMissingRequiredRegistrationFields($registration)) {
            return $this->error('ValidationError', 'clerkUserId and emailAddress are required.', 400);
        }

        $existingAccount = $this->accountRepository->findOneByClerkUserId($registration['clerkUserId']);
        if ($existingAccount !== null) {
            return $this->handleExistingClerkAccount($existingAccount, $registration);
        }

        $existingEmailAccount = $this->accountRepository->findOneByEmailAddress($registration['emailAddress']);
        if ($existingEmailAccount !== null) {
            return $this->linkExistingEmailAccount($existingEmailAccount, $registration);
        }

        return $this->createNewAccount($registration);
    }

    private function hasMissingRequiredRegistrationFields(array $registration): bool
    {
        return $registration['clerkUserId'] === ''
            || $registration['emailAddress'] === '';
    }

    private function normalizeRegistrationRequest(array $requestBody): array
    {
        $emailAddress = trim($requestBody['emailAddress'] ?? '');
        $contactNumber = trim($requestBody['contactNumber'] ?? '');
        $isAdminEmail = $this->isAdminEmail($emailAddress);

        return [
            'clerkUserId' => trim($requestBody['clerkUserId'] ?? ''),
            'firstName' => trim($requestBody['firstName'] ?? ''),
            'lastName' => trim($requestBody['lastName'] ?? ''),
            'emailAddress' => $emailAddress,
            'username' => AccountUsername::fromEmail($emailAddress),
            'role' => $this->resolveRole(trim($requestBody['role'] ?? 'ROLE_BORROWER'), $emailAddress),
            'contactNumber' => $contactNumber,
            'department' => trim($requestBody['department'] ?? ''),
            'idNumber' => trim($requestBody['idNumber'] ?? $requestBody['studentIdNumber'] ?? $contactNumber),
            'status' => $isAdminEmail ? 'active' : 'pending',
            'isApproved' => $isAdminEmail,
            'isVerified' => $isAdminEmail,
            'isAdminEmail' => $isAdminEmail,
        ];
    }

    private function handleExistingClerkAccount(AccountEntity $account, array $registration): array
    {
        if ($registration['isAdminEmail']) {
            $this->promoteExistingAccountToAdmin($account);
            return $this->success('Existing account promoted to admin.', $this->buildExistingAccountPayload($account, [
                'roleDesignation' => 'ROLE_ADMIN',
                'status' => 'active',
                'isApproved' => true,
            ]));
        }

        $this->clerkInvitationSyncService->syncAcceptedInvitationForEmail(
            $registration['emailAddress'],
            $registration['clerkUserId']
        );

        $refreshedAccount = $this->loadAccountSnapshot($account->getAccountIdentifier());
        if ($refreshedAccount !== null && $this->canUseExistingClerkAccountSnapshot($refreshedAccount)) {
            return $this->success('Account already registered.', $this->buildSnapshotAccountPayload($refreshedAccount));
        }

        if (!$this->canUseExistingClerkAccount($account)) {
            return $this->error('AccountPendingInvitation', 'Please wait for an administrator invitation before signing in.', 403);
        }

        return $this->success('Account already registered.', $this->buildExistingAccountPayload($account));
    }

    private function canUseExistingClerkAccount(AccountEntity $account): bool
    {
        $status = strtolower($account->getStatus());

        return $account->getIsVerified() && in_array($status, ['active', 'approved', 'accepted'], true);
    }

    private function promoteExistingAccountToAdmin(AccountEntity $account): void
    {
        $this->connection->executeStatement(
            'UPDATE accounts
             SET role_designation = :roleDesignation,
                 status = :status,
                 is_verified = :isVerified,
                 verification_status = :verificationStatus,
                 is_approved = :isApproved,
                 is_active = :isActive,
                 updated_timestamp = :updatedTimestamp
             WHERE account_identifier = :accountIdentifier',
            $this->buildAdminPromotionParameters($account),
            $this->adminPromotionTypes()
        );
    }

    private function buildAdminPromotionParameters(AccountEntity $account): array
    {
        return [
            'roleDesignation' => 'ROLE_ADMIN',
            'status' => 'active',
            'isVerified' => true,
            'verificationStatus' => 'verified',
            'isApproved' => true,
            'isActive' => true,
            'updatedTimestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'accountIdentifier' => $account->getAccountIdentifier(),
        ];
    }

    private function adminPromotionTypes(): array
    {
        return [
            'roleDesignation' => ParameterType::STRING,
            'status' => ParameterType::STRING,
            'isVerified' => ParameterType::BOOLEAN,
            'verificationStatus' => ParameterType::STRING,
            'isApproved' => ParameterType::BOOLEAN,
            'isActive' => ParameterType::BOOLEAN,
            'updatedTimestamp' => ParameterType::STRING,
            'accountIdentifier' => ParameterType::INTEGER,
        ];
    }

    private function linkExistingEmailAccount(AccountEntity $account, array $registration): array
    {
        $this->clerkInvitationSyncService->syncAcceptedInvitationForEmail(
            $registration['emailAddress'],
            $registration['clerkUserId']
        );

        $refreshedSnapshot = $this->loadAccountSnapshot($account->getAccountIdentifier());
        if ($refreshedSnapshot !== null && $this->canUseExistingClerkAccountSnapshot($refreshedSnapshot)) {
            return $this->success('Account linked to Clerk successfully.', $this->buildSnapshotAccountPayload($refreshedSnapshot));
        }

        if (!$this->canLinkExistingEmailAccount($account, $registration)) {
            return $this->error('AccountPendingInvitation', 'Please wait for an administrator invitation before signing in.', 403);
        }

        $nextState = $this->resolveExistingEmailAccountState($account, $registration);
        $now = (new \DateTime())->format('Y-m-d H:i:s');

        $this->updateExistingEmailAccount($account, $registration, $nextState, $now);

        if ($nextState['shouldMarkInvitationAccepted']) {
            $this->markLatestInvitationAccepted($registration['emailAddress'], $now);
        }

        return $this->success('Account linked to Clerk successfully.', $this->buildRegistrationAccountPayload($registration, $nextState, [
            'accountIdentifier' => $account->getAccountIdentifier(),
        ]));
    }

    private function canLinkExistingEmailAccount(AccountEntity $account, array $registration): bool
    {
        if ($registration['isAdminEmail']) {
            return true;
        }

        $status = strtolower(trim($account->getStatus()));
        if (in_array($status, ['active', 'approved', 'accepted'], true) && $account->getClerkUserId() !== null) {
            return true;
        }

        return $this->hasAcceptedInvitation($registration['emailAddress']);
    }

    private function resolveExistingEmailAccountState(AccountEntity $account, array $registration): array
    {
        $existingStatus = strtolower($account->getStatus());
        $existingRole = strtoupper(trim($account->getRoleDesignation()));
        $existingIsAdmin = in_array($existingRole, ['ADMIN', 'ROLE_ADMIN'], true);
        $latestInvitation = $this->findLatestInvitationForEmail($registration['emailAddress']);
        $hasAcceptedInvitation = $this->isAcceptedInvitation($latestInvitation);
        $nextIsVerified = $account->getIsVerified() || $registration['isVerified'] || $existingIsAdmin || $hasAcceptedInvitation;
        $nextIsApproved = $account->getIsApproved() || $registration['isApproved'] || $existingIsAdmin || $hasAcceptedInvitation;
        $nextIsActive = $account->getIsActive() || $hasAcceptedInvitation;
        $nextStatus = $hasAcceptedInvitation || in_array($existingStatus, ['active', 'approved', 'accepted'], true)
            ? 'active'
            : ($existingStatus !== '' ? $existingStatus : 'pending');

        return [
            'role' => $existingIsAdmin ? 'ROLE_ADMIN' : $registration['role'],
            'isVerified' => $nextIsVerified,
            'isApproved' => $nextIsApproved,
            'isActive' => $nextIsActive,
            'status' => $nextStatus !== '' ? $nextStatus : $registration['status'],
            'hasOpenInvitation' => $this->isOpenInvitation($latestInvitation),
            'shouldMarkInvitationAccepted' => $hasAcceptedInvitation || $nextStatus === 'active',
        ];
    }

    private function updateExistingEmailAccount(AccountEntity $account, array $registration, array $nextState, string $updatedAt): void
    {
        $resolvedFirstName = $registration['firstName'] !== '' ? $registration['firstName'] : $account->getFirstName();
        $resolvedLastName = $registration['lastName'] !== '' ? $registration['lastName'] : $account->getLastName();

        $this->connection->executeStatement(
            "UPDATE accounts
             SET last_name = :lastName,
                 first_name = :firstName,
                 username = :username,
                 role_designation = :roleDesignation,
                 id_number = :idNumber,
                 department = :department,
                 contact_number = :contactNumber,
                 clerk_user_id = :clerkUserId,
                 status = :status,
                 is_verified = :isVerified,
                 verification_status = :verificationStatus,
                 invitation_status = :invitationStatus,
                 is_approved = :isApproved,
                 is_active = :isActive,
                 approved_at = CASE
                    WHEN :status = 'active' THEN COALESCE(approved_at, :approvedAt)
                    ELSE approved_at
                 END,
                 updated_timestamp = :updatedTimestamp
             WHERE account_identifier = :accountIdentifier",
            $this->buildExistingEmailUpdateParameters($account, $registration, $nextState, $updatedAt, $resolvedFirstName, $resolvedLastName),
            $this->existingEmailUpdateTypes($registration, $nextState)
        );
    }

    private function buildExistingEmailUpdateParameters(
        AccountEntity $account,
        array $registration,
        array $nextState,
        string $updatedAt,
        string $resolvedFirstName,
        string $resolvedLastName
    ): array
    {
        return [
            'lastName' => $resolvedLastName,
            'firstName' => $resolvedFirstName,
            'username' => $registration['username'],
            'roleDesignation' => $nextState['role'],
            'idNumber' => $registration['idNumber'] ?: null,
            'department' => $registration['department'] ?: null,
            'contactNumber' => $registration['contactNumber'] ?: null,
            'clerkUserId' => $registration['clerkUserId'],
            'status' => $nextState['status'],
            'isVerified' => $nextState['isVerified'],
            'verificationStatus' => $nextState['isVerified'] ? 'verified' : 'unverified',
            'invitationStatus' => $nextState['status'] === 'active' ? 'accepted' : ($nextState['isVerified'] ? 'sent' : 'not_sent'),
            'isApproved' => $nextState['isApproved'],
            'isActive' => $nextState['isActive'],
            'approvedAt' => $nextState['status'] === 'active' ? $updatedAt : null,
            'updatedTimestamp' => $updatedAt,
            'accountIdentifier' => $account->getAccountIdentifier(),
        ];
    }

    private function existingEmailUpdateTypes(array $registration, array $nextState): array
    {
        return [
            'lastName' => ParameterType::STRING,
            'firstName' => ParameterType::STRING,
            'username' => ParameterType::STRING,
            'roleDesignation' => ParameterType::STRING,
            'idNumber' => $registration['idNumber'] === '' ? ParameterType::NULL : ParameterType::STRING,
            'department' => $registration['department'] === '' ? ParameterType::NULL : ParameterType::STRING,
            'contactNumber' => $registration['contactNumber'] === '' ? ParameterType::NULL : ParameterType::STRING,
            'clerkUserId' => ParameterType::STRING,
            'status' => ParameterType::STRING,
            'isVerified' => ParameterType::BOOLEAN,
            'verificationStatus' => ParameterType::STRING,
            'invitationStatus' => ParameterType::STRING,
            'isApproved' => ParameterType::BOOLEAN,
            'isActive' => ParameterType::BOOLEAN,
            'approvedAt' => $nextState['status'] === 'active' ? ParameterType::STRING : ParameterType::NULL,
            'updatedTimestamp' => ParameterType::STRING,
            'accountIdentifier' => ParameterType::INTEGER,
        ];
    }

    private function createNewAccount(array $registration): array
    {
        if (!$registration['isAdminEmail']) {
            return $this->error('AccountPendingInvitation', 'Please wait for an administrator invitation before signing in.', 403);
        }

        try {
            $now = (new \DateTime())->format('Y-m-d H:i:s');
            $this->connection->executeStatement(
                'INSERT INTO accounts
                    (last_name, first_name, email_address, username, role_designation, id_number, department,
                     contact_number, clerk_user_id, status, is_approved, is_active,
                     is_verified, verification_status, invitation_status, failed_login_attempts, created_timestamp, updated_timestamp)
                 VALUES
                    (:lastName, :firstName, :emailAddress, :username, :roleDesignation, :idNumber, :department,
                     :contactNumber, :clerkUserId, :status, :isApproved, :isActive,
                     :isVerified, :verificationStatus, :invitationStatus, :failedLoginAttempts, :createdTimestamp, :updatedTimestamp)',
                $this->buildNewAccountParameters($registration, $now),
                $this->newAccountTypes($registration)
            );

            return $this->success('Account registered successfully.', $this->buildRegistrationAccountPayload($registration, [
                'role' => $registration['role'],
                'status' => $registration['status'],
                'isApproved' => $registration['isApproved'],
            ], [
                'accountIdentifier' => (int)$this->connection->lastInsertId(),
            ]), 201);
        } catch (\Throwable $exception) {
            return $this->error('RegistrationFailed', 'Failed to register account: ' . $exception->getMessage(), 500);
        }
    }

    private function buildNewAccountParameters(array $registration, string $timestamp): array
    {
        return [
            'lastName' => $registration['lastName'],
            'firstName' => $registration['firstName'],
            'emailAddress' => $registration['emailAddress'],
            'username' => $registration['username'],
            'roleDesignation' => $registration['role'],
            'idNumber' => $registration['idNumber'] ?: null,
            'department' => $registration['department'] ?: null,
            'contactNumber' => $registration['contactNumber'] ?: null,
            'clerkUserId' => $registration['clerkUserId'],
            'status' => $registration['status'],
            'isApproved' => $registration['isApproved'],
            'isActive' => true,
            'isVerified' => $registration['isVerified'],
            'verificationStatus' => $registration['isVerified'] ? 'verified' : 'unverified',
            'invitationStatus' => $registration['isVerified'] ? 'accepted' : 'not_sent',
            'failedLoginAttempts' => 0,
            'createdTimestamp' => $timestamp,
            'updatedTimestamp' => $timestamp,
        ];
    }

    private function newAccountTypes(array $registration): array
    {
        return [
            'lastName' => ParameterType::STRING,
            'firstName' => ParameterType::STRING,
            'emailAddress' => ParameterType::STRING,
            'username' => ParameterType::STRING,
            'roleDesignation' => ParameterType::STRING,
            'idNumber' => $registration['idNumber'] === '' ? ParameterType::NULL : ParameterType::STRING,
            'department' => $registration['department'] === '' ? ParameterType::NULL : ParameterType::STRING,
            'contactNumber' => $registration['contactNumber'] === '' ? ParameterType::NULL : ParameterType::STRING,
            'clerkUserId' => ParameterType::STRING,
            'status' => ParameterType::STRING,
            'isApproved' => ParameterType::BOOLEAN,
            'isActive' => ParameterType::BOOLEAN,
            'isVerified' => ParameterType::BOOLEAN,
            'verificationStatus' => ParameterType::STRING,
            'invitationStatus' => ParameterType::STRING,
            'failedLoginAttempts' => ParameterType::INTEGER,
            'createdTimestamp' => ParameterType::STRING,
            'updatedTimestamp' => ParameterType::STRING,
        ];
    }

    private function isAdminEmail(string $emailAddress): bool
    {
        return in_array(strtolower(trim($emailAddress)), self::ADMIN_EMAIL_ALLOWLIST, true);
    }

    private function resolveRole(string $requestedRole, string $emailAddress): string
    {
        if ($this->isAdminEmail($emailAddress)) {
            return 'ROLE_ADMIN';
        }

        $role = strtoupper(trim($requestedRole));

        return match (true) {
            in_array($role, ['ADMIN', 'ROLE_ADMIN'], true) => 'ROLE_ADMIN',
            in_array($role, ['BORROWER', 'ROLE_BORROWER'], true) => 'ROLE_BORROWER',
            str_starts_with($role, 'ROLE_') => $role,
            default => 'ROLE_BORROWER',
        };
    }

    private function findLatestInvitationForEmail(string $emailAddress): ?array
    {
        $invitation = $this->connection->fetchAssociative(
            'SELECT id, status, expires_at, accepted_at
             FROM invitations
             WHERE LOWER(email) = LOWER(:emailAddress)
             ORDER BY created_at DESC
             LIMIT 1',
            ['emailAddress' => $emailAddress],
            ['emailAddress' => ParameterType::STRING]
        );

        return $invitation ?: null;
    }

    private function isOpenInvitation(?array $invitation): bool
    {
        if (!$this->isPendingInvitationRecord($invitation)) {
            return false;
        }

        try {
            return new \DateTimeImmutable((string)$invitation['expires_at']) >= new \DateTimeImmutable();
        } catch (\Throwable) {
            return false;
        }
    }

    private function isPendingInvitationRecord(?array $invitation): bool
    {
        if ($invitation === null || !empty($invitation['accepted_at'])) {
            return false;
        }

        $status = strtolower((string)($invitation['status'] ?? 'pending'));
        return !in_array($status, ['accepted', 'expired', 'rejected', 'denied'], true);
    }

    private function isAcceptedInvitation(?array $invitation): bool
    {
        if ($invitation === null) {
            return false;
        }

        if (!empty($invitation['accepted_at'])) {
            return true;
        }

        $status = strtolower((string)($invitation['status'] ?? 'pending'));
        return in_array($status, ['accepted', 'completed'], true);
    }

    private function hasAcceptedInvitation(string $emailAddress): bool
    {
        return $this->isAcceptedInvitation($this->findLatestInvitationForEmail($emailAddress));
    }

    private function markLatestInvitationAccepted(string $emailAddress, string $acceptedAt): void
    {
        $this->connection->executeStatement(
            "UPDATE invitations
             SET status = 'accepted',
                 accepted_at = COALESCE(accepted_at, :acceptedAt)
             WHERE id = (
                SELECT id
                FROM invitations
                WHERE LOWER(email) = LOWER(:emailAddress)
                ORDER BY created_at DESC
                LIMIT 1
             )",
            [
                'emailAddress' => $emailAddress,
                'acceptedAt' => $acceptedAt,
            ],
            [
                'emailAddress' => ParameterType::STRING,
                'acceptedAt' => ParameterType::STRING,
            ]
        );
    }

    private function buildExistingAccountPayload(AccountEntity $account, array $overrides = []): array
    {
        return array_merge([
            'accountIdentifier' => $account->getAccountIdentifier(),
            'clerkUserId' => $account->getClerkUserId(),
            'firstName' => $account->getFirstName(),
            'lastName' => $account->getLastName(),
            'emailAddress' => $account->getEmailAddress(),
            'username' => $account->getUsername(),
            'roleDesignation' => $account->getRoleDesignation(),
            'status' => $account->getStatus(),
            'isApproved' => $account->getIsApproved(),
            'isVerified' => $account->getIsVerified(),
            'isActive' => $account->getIsActive(),
            'verificationStatus' => $account->getVerificationStatus(),
            'invitationStatus' => $account->getInvitationStatus(),
        ], $overrides);
    }

    private function loadAccountSnapshot(int $accountIdentifier): ?array
    {
        $account = $this->connection->fetchAssociative(
            'SELECT account_identifier, clerk_user_id, first_name, last_name, email_address, username,
                    role_designation, status, is_approved, is_verified, is_active,
                    verification_status, invitation_status
             FROM accounts
             WHERE account_identifier = :accountIdentifier
             LIMIT 1',
            ['accountIdentifier' => $accountIdentifier],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        return $account ?: null;
    }

    private function canUseExistingClerkAccountSnapshot(array $account): bool
    {
        $status = strtolower(trim((string)($account['status'] ?? 'pending')));
        return $this->toDatabaseBoolean($account['is_verified'] ?? false)
            && in_array($status, ['active', 'approved', 'accepted'], true);
    }

    private function buildSnapshotAccountPayload(array $account): array
    {
        return [
            'accountIdentifier' => (int)$account['account_identifier'],
            'clerkUserId' => !empty($account['clerk_user_id']) ? (string)$account['clerk_user_id'] : null,
            'firstName' => (string)($account['first_name'] ?? ''),
            'lastName' => (string)($account['last_name'] ?? ''),
            'emailAddress' => (string)($account['email_address'] ?? ''),
            'username' => (string)($account['username'] ?? ''),
            'roleDesignation' => (string)($account['role_designation'] ?? 'ROLE_BORROWER'),
            'status' => (string)($account['status'] ?? 'pending'),
            'isApproved' => $this->toDatabaseBoolean($account['is_approved'] ?? false),
            'isVerified' => $this->toDatabaseBoolean($account['is_verified'] ?? false),
            'isActive' => $this->toDatabaseBoolean($account['is_active'] ?? true),
            'verificationStatus' => !empty($account['verification_status']) ? (string)$account['verification_status'] : null,
            'invitationStatus' => !empty($account['invitation_status']) ? (string)$account['invitation_status'] : null,
        ];
    }

    private function buildRegistrationAccountPayload(array $registration, array $state, array $overrides = []): array
    {
        return array_merge([
            'clerkUserId' => $registration['clerkUserId'],
            'firstName' => $registration['firstName'],
            'lastName' => $registration['lastName'],
            'emailAddress' => $registration['emailAddress'],
            'username' => $registration['username'],
            'roleDesignation' => $state['role'],
            'status' => $state['status'],
            'isApproved' => $state['isApproved'],
            'isVerified' => $state['isVerified'] ?? false,
            'isActive' => $state['isActive'] ?? true,
            'verificationStatus' => ($state['isVerified'] ?? false) ? 'verified' : 'unverified',
            'invitationStatus' => ($state['status'] ?? '') === 'active'
                ? 'accepted'
                : (($state['isVerified'] ?? false) ? 'sent' : 'not_sent'),
        ], $overrides);
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

    private function success(string $message, array $account, int $status = 200): array
    {
        return [
            'success' => true,
            'status' => $status,
            'data' => [
                'message' => $message,
                'account' => $account,
            ],
        ];
    }

    private function error(string $code, string $message, int $status): array
    {
        return [
            'success' => false,
            'errorCode' => $code,
            'message' => $message,
            'status' => $status,
        ];
    }
}
