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
        private readonly Connection $connection
    ) {
    }

    public function register(array $requestBody): array
    {
        $registration = $this->normalizeRegistrationRequest($requestBody);

        if ($this->hasMissingRequiredRegistrationFields($registration)) {
            return $this->error('ValidationError', 'clerkUserId, firstName, lastName, and emailAddress are required.', 400);
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
            || $registration['firstName'] === ''
            || $registration['lastName'] === ''
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
            'status' => $isAdminEmail ? 'approved' : 'pending',
            'isApproved' => $isAdminEmail,
            'isAdminEmail' => $isAdminEmail,
        ];
    }

    private function handleExistingClerkAccount(AccountEntity $account, array $registration): array
    {
        if ($registration['isAdminEmail']) {
            $this->promoteExistingAccountToAdmin($account);
            return $this->success('Existing account promoted to admin.', $this->buildExistingAccountPayload($account, [
                'roleDesignation' => 'ROLE_ADMIN',
                'status' => 'approved',
                'isApproved' => true,
            ]));
        }

        if (!$this->canUseExistingClerkAccount($account, $registration)) {
            return $this->error('AccountPendingInvitation', 'Please wait for an administrator invitation before signing in.', 403);
        }

        return $this->success('Account already registered.', $this->buildExistingAccountPayload($account));
    }

    private function canUseExistingClerkAccount(AccountEntity $account, array $registration): bool
    {
        $status = strtolower($account->getStatus());

        return $account->getIsApproved() && $status === 'approved';
    }

    private function promoteExistingAccountToAdmin(AccountEntity $account): void
    {
        $this->connection->executeStatement(
            'UPDATE accounts
             SET role_designation = :roleDesignation,
                 status = :status,
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
            'status' => 'approved',
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
            'isApproved' => ParameterType::BOOLEAN,
            'isActive' => ParameterType::BOOLEAN,
            'updatedTimestamp' => ParameterType::STRING,
            'accountIdentifier' => ParameterType::INTEGER,
        ];
    }

    private function linkExistingEmailAccount(AccountEntity $account, array $registration): array
    {
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

    private function resolveExistingEmailAccountState(AccountEntity $account, array $registration): array
    {
        $existingStatus = strtolower($account->getStatus());
        $existingRole = strtoupper(trim($account->getRoleDesignation()));
        $existingIsAdmin = in_array($existingRole, ['ADMIN', 'ROLE_ADMIN'], true);
        $latestInvitation = $this->findLatestInvitationForEmail($registration['emailAddress']);
        $hasOpenInvitation = $this->isOpenInvitation($latestInvitation);
        $nextIsApproved = $account->getIsApproved() || $registration['isApproved'] || $existingIsAdmin;
        $nextIsActive = $nextIsApproved ? $account->getIsActive() : true;
        $nextStatus = $nextIsApproved
            ? ($nextIsActive ? 'approved' : 'disabled')
            : ($hasOpenInvitation || $existingStatus === 'invited' ? 'invited' : $existingStatus);

        return [
            'role' => $existingIsAdmin ? 'ROLE_ADMIN' : $registration['role'],
            'isApproved' => $nextIsApproved,
            'isActive' => $nextIsActive,
            'status' => $nextStatus !== '' ? $nextStatus : $registration['status'],
            'hasOpenInvitation' => $hasOpenInvitation,
            'shouldMarkInvitationAccepted' => false,
        ];
    }

    private function updateExistingEmailAccount(AccountEntity $account, array $registration, array $nextState, string $updatedAt): void
    {
        $this->connection->executeStatement(
            'UPDATE accounts
             SET last_name = :lastName,
                 first_name = :firstName,
                 username = :username,
                 role_designation = :roleDesignation,
                 id_number = :idNumber,
                 department = :department,
                 contact_number = :contactNumber,
                 clerk_user_id = :clerkUserId,
                 status = :status,
                 is_approved = :isApproved,
                 is_active = :isActive,
                 updated_timestamp = :updatedTimestamp
             WHERE account_identifier = :accountIdentifier',
            $this->buildExistingEmailUpdateParameters($account, $registration, $nextState, $updatedAt),
            $this->existingEmailUpdateTypes($registration)
        );
    }

    private function buildExistingEmailUpdateParameters(AccountEntity $account, array $registration, array $nextState, string $updatedAt): array
    {
        return [
            'lastName' => $registration['lastName'],
            'firstName' => $registration['firstName'],
            'username' => $registration['username'],
            'roleDesignation' => $nextState['role'],
            'idNumber' => $registration['idNumber'] ?: null,
            'department' => $registration['department'] ?: null,
            'contactNumber' => $registration['contactNumber'] ?: null,
            'clerkUserId' => $registration['clerkUserId'],
            'status' => $nextState['status'],
            'isApproved' => $nextState['isApproved'],
            'isActive' => $nextState['isActive'],
            'updatedTimestamp' => $updatedAt,
            'accountIdentifier' => $account->getAccountIdentifier(),
        ];
    }

    private function existingEmailUpdateTypes(array $registration): array
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
            'isApproved' => ParameterType::BOOLEAN,
            'isActive' => ParameterType::BOOLEAN,
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
                     failed_login_attempts, created_timestamp, updated_timestamp)
                 VALUES
                    (:lastName, :firstName, :emailAddress, :username, :roleDesignation, :idNumber, :department,
                     :contactNumber, :clerkUserId, :status, :isApproved, :isActive,
                     :failedLoginAttempts, :createdTimestamp, :updatedTimestamp)',
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
        ], $overrides);
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
            'isActive' => $state['isActive'] ?? true,
        ], $overrides);
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
