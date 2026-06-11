<?php

namespace App\Domain\Authentication\Service;

use App\Domain\Account\Service\ClerkInvitationSyncService;
use App\Shared\Utils\DatabaseBoolean;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class ClerkLoginPreflightService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ClerkInvitationSyncService $clerkInvitationSyncService
    )
    {
    }

    public function check(string $emailAddress): array
    {
        $account = $this->connection->fetchAssociative(
            "SELECT account_identifier, email_address, username, clerk_user_id, status, is_approved, is_verified, is_active
             FROM accounts
             WHERE LOWER(email_address) = LOWER(:emailAddress)
                OR LOWER(username) = LOWER(:emailAddress)
             LIMIT 1",
            ['emailAddress' => $emailAddress],
            ['emailAddress' => ParameterType::STRING]
        );

        if (!$account) {
            return $this->error('AccountPendingInvitation', 'Please wait for an administrator invitation before signing in.', 403);
        }

        if (!DatabaseBoolean::toBool($account['is_active'] ?? true)) {
            return $this->error('AccountDisabled', 'This account has been disabled. Please contact an administrator.', 403);
        }

        $status = strtolower(trim((string)($account['status'] ?? 'pending')));
        if (in_array($status, ['rejected', 'denied'], true)) {
            return $this->error('AccountRejected', 'This account request was denied. Please contact the administrator.', 403);
        }

        if (in_array($status, ['inactive', 'suspended'], true)) {
            return $this->error('AccountDisabled', 'This account is inactive. Please contact an administrator.', 403);
        }

        $this->clerkInvitationSyncService->syncAcceptedInvitationForEmail(
            (string)($account['email_address'] ?? $emailAddress),
            (string)($account['clerk_user_id'] ?? '')
        );

        $refreshedAccount = $this->connection->fetchAssociative(
            "SELECT account_identifier, email_address, status, is_approved, is_verified, clerk_user_id, is_active
             FROM accounts
             WHERE account_identifier = :accountIdentifier
             LIMIT 1",
            ['accountIdentifier' => (int)$account['account_identifier']],
            ['accountIdentifier' => ParameterType::INTEGER]
        ) ?: $account;

        $refreshedStatus = strtolower(trim((string)($refreshedAccount['status'] ?? $status)));
        if (DatabaseBoolean::toBool($refreshedAccount['is_active'] ?? true)
            && !empty($refreshedAccount['clerk_user_id'])
            && in_array($refreshedStatus, ['active', 'approved', 'accepted'], true)
        ) {
            return $this->success($refreshedStatus);
        }

        if (DatabaseBoolean::toBool($refreshedAccount['is_verified'] ?? false) && in_array($refreshedStatus, ['verified', 'invited'], true)) {
            return $this->error('AccountInvitationPending', 'Your invitation was sent and verified by the admin. Please finish the Clerk invitation sign-up from your email before signing in.', 403);
        }

        $invitation = $this->connection->fetchAssociative(
            "SELECT status, expires_at, accepted_at
             FROM invitations
             WHERE LOWER(email) = LOWER(:emailAddress)
             ORDER BY created_at DESC
             LIMIT 1",
            ['emailAddress' => (string)($refreshedAccount['email_address'] ?? $emailAddress)],
            ['emailAddress' => ParameterType::STRING]
        );

        if ($this->isAcceptedInvitation($invitation ?: null)) {
            return $this->error('AccountSyncPending', 'Your invitation was accepted, but your account is still syncing. Please try again in a moment.', 409);
        }

        if ($this->clerkInvitationSyncService->isExpiredInvitation($invitation ?: null)) {
            return $this->error('InvitationExpired', 'Your invitation has expired. Please contact an administrator for a new invite.', 403);
        }

        return $this->error('AccountPendingInvitation', 'Your account request is still pending. Please wait for an administrator invitation before signing in.', 403);
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
        if (in_array($status, ['accepted', 'completed'], true)) {
            return true;
        }

        return false;
    }

    private function success(string $status): array
    {
        return [
            'success' => true,
            'data' => [
                'canSignIn' => true,
                'accountStatus' => $status,
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
