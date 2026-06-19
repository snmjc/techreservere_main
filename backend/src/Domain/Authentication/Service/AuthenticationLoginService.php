<?php

namespace App\Domain\Authentication\Service;

use App\Domain\Account\Entity\AccountEntity;
use App\Domain\Account\Repository\AccountRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class AuthenticationLoginService
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly Connection $connection
    ) {
    }

    public function login(string $emailAddress, string $passwordText): array
    {
        try {
            $account = $this->accountRepository->findOneByEmailAddressOrUsername($emailAddress);
        } catch (\Throwable) {
            return $this->error('DatabaseUnavailable', 'The authentication database is currently unavailable. Please make sure the TechReserve database service is running.', 503);
        }

        if ($account === null) {
            return $this->error('AuthenticationFailed', 'Invalid email address or password.', 401);
        }

        $availabilityError = $this->validateAccountAvailability($account);
        if ($availabilityError !== null) {
            return $availabilityError;
        }

        $storedHash = $account->getPasswordHash();
        if ($storedHash === null) {
            return $this->error('LocalPasswordUnavailable', 'This account uses Clerk authentication. Continuing with Clerk sign-in.', 401);
        }

        if (!password_verify($passwordText, $storedHash)) {
            return $this->recordFailedAttempt($account);
        }

        return $this->recordSuccessfulLogin($account);
    }

    private function validateAccountAvailability(AccountEntity $account): ?array
    {
        if ($this->isAssignmentOnlyStaffAccount($account)) {
            return $this->error(
                'AccountAssignmentOnly',
                'This staff record is for task assignment only and cannot sign in to TechReserve.',
                403
            );
        }

        $accountStatus = strtolower(trim((string)$account->getStatus()));
        $isActiveInvitationAccount = in_array($accountStatus, ['active', 'approved', 'accepted'], true);
        if (!$account->getIsActive()) {
            if (in_array($accountStatus, ['verified', 'invited', 'pending'], true)) {
                return $this->error('AccountInvitationPending', 'Please finish the Clerk invitation sign-up from your email before signing in.', 403);
            }

            return $this->error('AccountDisabled', 'This account has been disabled. Please contact an administrator.', 403);
        }

        if (in_array($accountStatus, ['verified', 'invited'], true)) {
            return $this->error('AccountInvitationPending', 'Your invitation was sent and verified by the admin. Please finish the Clerk invitation sign-up from your email before signing in.', 403);
        }

        if ((!$account->getIsVerified() && !$isActiveInvitationAccount) || $accountStatus === 'pending') {
            return $this->error('AccountPendingApproval', 'Your account is still pending verification. Please wait for an administrator invitation.', 403);
        }

        if (!$isActiveInvitationAccount) {
            return $this->error('AccountInvitationPending', 'Please finish the Clerk invitation sign-up from your email before signing in.', 403);
        }

        $lockedUntil = $account->getLockedUntilTimestamp();
        if ($lockedUntil !== null && $lockedUntil > new \DateTime()) {
            return $this->error('AccountLocked', 'This account is temporarily locked due to too many failed login attempts.', 403);
        }

        return null;
    }

    private function isAssignmentOnlyStaffAccount(AccountEntity $account): bool
    {
        $roleDesignation = strtoupper(trim((string)$account->getRoleDesignation()));

        return $roleDesignation === 'ROLE_STAFF'
            && $account->getClerkUserId() === null
            && $account->getPasswordHash() === null;
    }

    private function recordFailedAttempt(AccountEntity $account): array
    {
        $failedAttempts = $account->getFailedLoginAttempts() + 1;
        $account->setFailedLoginAttempts($failedAttempts);

        if ($failedAttempts >= 5) {
            $account->setLockedUntilTimestamp(new \DateTime('+15 minutes'));
        }

        return $this->persistAccount($account)
            ?? $this->error('AuthenticationFailed', 'Invalid email address or password.', 401);
    }

    private function recordSuccessfulLogin(AccountEntity $account): array
    {
        $account->setFailedLoginAttempts(0);
        $account->setLockedUntilTimestamp(null);
        $account->setLastLoginTimestamp(new \DateTime());

        $persistError = $this->persistAccount($account);
        if ($persistError !== null) {
            return $persistError;
        }

        return [
            'success' => true,
            'data' => [
                'token' => $this->buildLocalToken($account),
                'account' => $this->buildAccountResponse($account),
            ],
        ];
    }

    private function persistAccount(AccountEntity $account): ?array
    {
        try {
            $this->accountRepository->persistAccount($account);
            return null;
        } catch (\Throwable) {
            return $this->error('DatabaseUnavailable', 'The authentication database is currently unavailable. Please make sure the TechReserve database service is running.', 503);
        }
    }

    private function buildLocalToken(AccountEntity $account): string
    {
        return base64_encode(json_encode([
            'accountId' => $account->getAccountIdentifier(),
            'email' => $account->getEmailAddress(),
            'role' => $account->getRoleDesignation(),
            'exp' => time() + 86400,
        ]));
    }

    private function buildAccountResponse(AccountEntity $account): array
    {
        $profilePhotoData = $this->connection->fetchOne(
            'SELECT profile_photo_data FROM accounts WHERE account_identifier = :accountIdentifier',
            ['accountIdentifier' => $account->getAccountIdentifier()],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        return [
            'accountIdentifier' => $account->getAccountIdentifier(),
            'firstName' => $account->getFirstName(),
            'lastName' => $account->getLastName(),
            'emailAddress' => $account->getEmailAddress(),
            'username' => $account->getUsername(),
            'roleDesignation' => $account->getRoleDesignation(),
            'clerkUserId' => $account->getClerkUserId(),
            'status' => $account->getStatus(),
            'isVerified' => $account->getIsVerified(),
            'verificationStatus' => $account->getVerificationStatus(),
            'isApproved' => $account->getIsApproved(),
            'isActive' => $account->getIsActive(),
            'invitedAt' => $account->getInvitedAt()?->format('Y-m-d H:i:s'),
            'approvedAt' => $account->getApprovedAt()?->format('Y-m-d H:i:s'),
            'profilePhotoData' => $profilePhotoData ? (string)$profilePhotoData : null,
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
