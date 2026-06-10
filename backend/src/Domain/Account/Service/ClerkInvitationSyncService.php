<?php

namespace App\Domain\Account\Service;

use App\Shared\Utils\AppClock;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class ClerkInvitationSyncService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AccountClerkProvisioningService $accountClerkProvisioningService
    ) {
    }

    public function syncAcceptedInvitationForEmail(string $emailAddress, ?string $clerkUserId = null): bool
    {
        $normalizedEmailAddress = strtolower(trim($emailAddress));
        if ($normalizedEmailAddress === '') {
            return false;
        }

        $account = $this->findAccountByEmail($normalizedEmailAddress);
        if ($account === null) {
            return false;
        }

        $status = strtolower(trim((string)($account['status'] ?? 'pending')));
        if (in_array($status, ['inactive', 'disabled', 'rejected', 'denied', 'suspended'], true)) {
            return false;
        }

        $localInvitation = $this->findLatestLocalInvitation($normalizedEmailAddress);
        $clerkInvitation = $this->findLatestClerkInvitation($normalizedEmailAddress);
        $acceptedAt = $this->resolveAcceptedAt($localInvitation, $clerkInvitation);
        if ($acceptedAt === null) {
            return false;
        }

        $acceptedAtText = $acceptedAt->format('Y-m-d H:i:sP');
        $updatedTimestamp = AppClock::now()->format('Y-m-d H:i:s');
        $resolvedClerkUserId = trim((string)($clerkUserId ?? $account['clerk_user_id'] ?? ''));
        $nextStatus = 'approved';
        $nextIsApproved = true;

        $this->connection->beginTransaction();

        try {
            $localInvitationId = trim((string)($localInvitation['id'] ?? ''));
            if ($localInvitationId !== '') {
                $this->connection->executeStatement(
                    "UPDATE invitations
                     SET status = 'accepted',
                         accepted_at = COALESCE(accepted_at, :acceptedAt)
                     WHERE id = :invitationId",
                    [
                        'acceptedAt' => $acceptedAtText,
                        'invitationId' => $localInvitationId,
                    ],
                    [
                        'acceptedAt' => ParameterType::STRING,
                        'invitationId' => ParameterType::STRING,
                    ]
                );
            }

            $this->connection->executeStatement(
                "UPDATE accounts
                 SET status = :status,
                     is_approved = :isApproved,
                     is_active = TRUE,
                     clerk_user_id = COALESCE(NULLIF(clerk_user_id, ''), :clerkUserId),
                     updated_timestamp = :updatedTimestamp
                 WHERE account_identifier = :accountIdentifier",
                [
                    'status' => $nextStatus,
                    'isApproved' => $nextIsApproved,
                    'clerkUserId' => $resolvedClerkUserId !== '' ? $resolvedClerkUserId : null,
                    'updatedTimestamp' => $updatedTimestamp,
                    'accountIdentifier' => (int)$account['account_identifier'],
                ],
                [
                    'status' => ParameterType::STRING,
                    'isApproved' => ParameterType::BOOLEAN,
                    'clerkUserId' => $resolvedClerkUserId !== '' ? ParameterType::STRING : ParameterType::NULL,
                    'updatedTimestamp' => ParameterType::STRING,
                    'accountIdentifier' => ParameterType::INTEGER,
                ]
            );

            $this->connection->commit();
            return true;
        } catch (\Throwable) {
            $this->connection->rollBack();
            return false;
        }
    }

    public function isExpiredInvitation(?array $invitation): bool
    {
        if ($invitation === null) {
            return false;
        }

        $expiresAt = $this->normalizeTimestamp($invitation['expires_at'] ?? $invitation['expiresAt'] ?? null);
        if ($expiresAt === null) {
            return false;
        }

        return $expiresAt < AppClock::now();
    }

    public function reconcileAcceptedAccountsFromLocalInvitations(): void
    {
        $this->connection->executeStatement(
            "UPDATE accounts
             SET status = 'approved',
                 is_approved = TRUE,
                 is_active = TRUE,
                 updated_timestamp = :updatedTimestamp
             WHERE COALESCE(is_approved, FALSE) = FALSE
               AND COALESCE(NULLIF(clerk_user_id, ''), '') <> ''
               AND LOWER(COALESCE(status, 'pending')) NOT IN ('approved', 'inactive', 'disabled', 'rejected', 'denied', 'suspended')
               AND EXISTS (
                    SELECT 1
                    FROM invitations
                    WHERE LOWER(invitations.email) = LOWER(accounts.email_address)
                      AND invitations.accepted_at IS NOT NULL
               )",
            ['updatedTimestamp' => AppClock::now()->format('Y-m-d H:i:s')],
            ['updatedTimestamp' => ParameterType::STRING]
        );
    }

    private function findAccountByEmail(string $emailAddress): ?array
    {
        $account = $this->connection->fetchAssociative(
            "SELECT account_identifier, email_address, clerk_user_id, status, is_approved
             FROM accounts
             WHERE LOWER(email_address) = LOWER(:emailAddress)
             LIMIT 1",
            ['emailAddress' => $emailAddress],
            ['emailAddress' => ParameterType::STRING]
        );

        return $account ?: null;
    }

    private function findLatestLocalInvitation(string $emailAddress): ?array
    {
        $invitation = $this->connection->fetchAssociative(
            "SELECT id, status, created_at, expires_at, accepted_at
             FROM invitations
             WHERE LOWER(email) = LOWER(:emailAddress)
             ORDER BY created_at DESC
             LIMIT 1",
            ['emailAddress' => $emailAddress],
            ['emailAddress' => ParameterType::STRING]
        );

        return $invitation ?: null;
    }

    private function findLatestClerkInvitation(string $emailAddress): ?array
    {
        try {
            return $this->accountClerkProvisioningService->findLatestInvitationByEmail($emailAddress);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveAcceptedAt(?array $localInvitation, ?array $clerkInvitation): ?\DateTimeImmutable
    {
        $localAcceptedAt = $this->normalizeTimestamp($localInvitation['accepted_at'] ?? null);
        if ($localAcceptedAt !== null) {
            return $localAcceptedAt;
        }

        if (!$this->isAcceptedClerkInvitation($clerkInvitation)) {
            return null;
        }

        return $this->normalizeTimestamp(
            $clerkInvitation['accepted_at']
            ?? $clerkInvitation['acceptedAt']
            ?? $clerkInvitation['updated_at']
            ?? $clerkInvitation['updatedAt']
            ?? null
        ) ?? AppClock::now();
    }

    private function isAcceptedClerkInvitation(?array $clerkInvitation): bool
    {
        if ($clerkInvitation === null) {
            return false;
        }

        return strtolower(trim((string)($clerkInvitation['status'] ?? ''))) === 'accepted';
    }

    private function normalizeTimestamp(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                $timestamp = (int)$value;
                if ($timestamp > 1000000000000) {
                    $timestamp = (int)floor($timestamp / 1000);
                }

                return (new \DateTimeImmutable('@' . $timestamp))->setTimezone(AppClock::timezone());
            }

            return new \DateTimeImmutable((string)$value, AppClock::timezone());
        } catch (\Throwable) {
            return null;
        }
    }
}
