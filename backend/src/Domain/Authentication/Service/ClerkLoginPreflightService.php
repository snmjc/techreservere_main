<?php

namespace App\Domain\Authentication\Service;

use App\Shared\Utils\DatabaseBoolean;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class ClerkLoginPreflightService
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function check(string $emailAddress): array
    {
        $account = $this->connection->fetchAssociative(
            "SELECT account_identifier, email_address, username, status, is_approved, is_active
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
        if (DatabaseBoolean::toBool($account['is_approved'] ?? false) && $status === 'approved') {
            return $this->success($status);
        }

        if (in_array($status, ['rejected', 'denied'], true)) {
            return $this->error('AccountRejected', 'This account request was denied. Please contact the administrator.', 403);
        }

        $invitation = $this->connection->fetchAssociative(
            "SELECT status, expires_at, accepted_at
             FROM invitations
             WHERE LOWER(email) = LOWER(:emailAddress)
             ORDER BY created_at DESC
             LIMIT 1",
            ['emailAddress' => $emailAddress],
            ['emailAddress' => ParameterType::STRING]
        );

        if ($status === 'invited' && $this->isActiveOrAcceptedInvitation($invitation ?: null)) {
            return $this->success($status);
        }

        return $this->error('AccountPendingInvitation', 'Your account request is still pending. Please wait for an administrator invitation before signing in.', 403);
    }

    private function isActiveOrAcceptedInvitation(?array $invitation): bool
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

        if (in_array($status, ['expired', 'rejected', 'denied'], true)) {
            return false;
        }

        try {
            return new \DateTimeImmutable((string)$invitation['expires_at']) >= new \DateTimeImmutable();
        } catch (\Throwable) {
            return false;
        }
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
