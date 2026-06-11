<?php

namespace App\Domain\Account\Service;

use App\Shared\Utils\AccountUsername;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;

class ClerkWebhookUserSyncService
{
    private const ACCOUNT_LINK_SQL = "UPDATE accounts
        SET clerk_user_id = :clerkUserId,
            username = COALESCE(NULLIF(username, ''), :username),
            first_name = CASE WHEN :firstName = '' THEN first_name ELSE :firstName END,
            last_name = CASE WHEN :lastName = '' THEN last_name ELSE :lastName END,
            role_designation = CASE
               WHEN COALESCE(NULLIF(role_designation, ''), '') <> '' THEN role_designation
               WHEN :roleDesignation = '' THEN role_designation
               ELSE :roleDesignation
            END,
            is_verified = TRUE,
            verification_status = 'verified',
            invitation_status = 'accepted',
            is_approved = TRUE,
            is_active = TRUE,
            status = 'active',
            approved_at = COALESCE(approved_at, :approvedAt),
            updated_timestamp = :updatedTimestamp
        WHERE account_identifier = :accountIdentifier
          AND (clerk_user_id IS NULL OR clerk_user_id = '' OR clerk_user_id = :clerkUserId)";

    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger
    ) {
    }

    public function sync(array $userData): void
    {
        $syncContext = $this->buildSyncContext($userData);

        if (!$this->hasRequiredIdentifiers($syncContext)) {
            $this->logIncompleteIdentifiers($syncContext);
            return;
        }

        $this->connection->beginTransaction();

        try {
            $matchedAccount = $this->findTargetAccount(
                $syncContext['accountIdentifier'],
                $syncContext['emailAddress'],
                $syncContext['clerkUserId']
            );

            if ($matchedAccount === null) {
                $this->rollBackAndLogMissingAccount($syncContext);
                return;
            }

            if (!$this->canActivateMatchedAccount($matchedAccount)) {
                $this->connection->rollBack();
                $this->logger->warning('Clerk webhook ignored because the matched account is still pending invitation.', [
                    'clerkUserId' => $syncContext['clerkUserId'],
                    'emailAddress' => $syncContext['emailAddress'],
                    'accountIdentifier' => (int)($matchedAccount['account_identifier'] ?? 0),
                    'status' => (string)($matchedAccount['status'] ?? ''),
                    'isVerified' => (bool)($matchedAccount['is_verified'] ?? false),
                ]);
                return;
            }

            $targetAccountIdentifier = (int)$matchedAccount['account_identifier'];
            $updatedRows = $this->linkApprovedAccount($syncContext, $matchedAccount, $targetAccountIdentifier);

            if ($updatedRows === 0) {
                $this->rollBackAndLogUnlinkedAccount($syncContext, $targetAccountIdentifier);
                return;
            }

            $this->markLatestInvitationAccepted($syncContext['emailAddress'], $syncContext['timestamp']);
            $this->connection->commit();

            $this->logger->info('Clerk account linked and activated.', [
                'accountIdentifier' => $targetAccountIdentifier,
                'clerkUserId' => $syncContext['clerkUserId'],
                'emailAddress' => $syncContext['emailAddress'],
            ]);
        } catch (\Throwable $exception) {
            $this->rollBackAndLogException($syncContext, $exception);
            throw $exception;
        }
    }

    private function buildSyncContext(array $userData): array
    {
        $metadata = $this->extractRelevantMetadata($userData);

        return [
            'clerkUserId' => trim((string)($userData['id'] ?? '')),
            'emailAddress' => $this->resolvePrimaryEmailAddress($userData),
            'accountIdentifier' => $this->resolveMetadataAccountIdentifier($metadata),
            'firstName' => trim((string)($userData['first_name'] ?? $metadata['first_name'] ?? '')),
            'lastName' => trim((string)($userData['last_name'] ?? $metadata['last_name'] ?? '')),
            'roleDesignation' => trim((string)($metadata['role_designation'] ?? '')),
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }

    private function hasRequiredIdentifiers(array $syncContext): bool
    {
        return $syncContext['clerkUserId'] !== '' && $syncContext['emailAddress'] !== '';
    }

    private function logIncompleteIdentifiers(array $syncContext): void
    {
        $this->logger->warning('Clerk webhook skipped because account identifiers were incomplete.', [
            'clerkUserId' => $syncContext['clerkUserId'],
            'emailAddress' => $syncContext['emailAddress'],
        ]);
    }

    private function rollBackAndLogMissingAccount(array $syncContext): void
    {
        $this->connection->rollBack();
        $this->logger->warning('Clerk webhook could not match a PostgreSQL account.', [
            'clerkUserId' => $syncContext['clerkUserId'],
            'emailAddress' => $syncContext['emailAddress'],
            'accountIdentifier' => $syncContext['accountIdentifier'],
        ]);
    }

    private function rollBackAndLogUnlinkedAccount(array $syncContext, int $targetAccountIdentifier): void
    {
        $this->connection->rollBack();
        $this->logger->warning('Clerk webhook found account but could not link Clerk user ID.', [
            'clerkUserId' => $syncContext['clerkUserId'],
            'emailAddress' => $syncContext['emailAddress'],
            'accountIdentifier' => $targetAccountIdentifier,
        ]);
    }

    private function rollBackAndLogException(array $syncContext, \Throwable $exception): void
    {
        if ($this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }

        $this->logger->error('Clerk webhook sync failed.', [
            'clerkUserId' => $syncContext['clerkUserId'],
            'emailAddress' => $syncContext['emailAddress'],
            'accountIdentifier' => $syncContext['accountIdentifier'],
            'error' => $exception->getMessage(),
        ]);
    }

    private function linkApprovedAccount(array $syncContext, array $matchedAccount, int $targetAccountIdentifier): int
    {
        $parameters = $this->buildAccountLinkParameters($syncContext, $matchedAccount, $targetAccountIdentifier);

        return $this->connection->executeStatement(
            self::ACCOUNT_LINK_SQL,
            $parameters,
            $this->buildAccountLinkTypes()
        );
    }

    private function buildAccountLinkParameters(array $syncContext, array $matchedAccount, int $targetAccountIdentifier): array
    {
        $roleFromDatabase = trim((string)($matchedAccount['role_designation'] ?? ''));

        return [
            'clerkUserId' => $syncContext['clerkUserId'],
            'username' => AccountUsername::fromEmail($syncContext['emailAddress']),
            'firstName' => $syncContext['firstName'],
            'lastName' => $syncContext['lastName'],
            'roleDesignation' => $roleFromDatabase !== '' ? $roleFromDatabase : $syncContext['roleDesignation'],
            'approvedAt' => $syncContext['timestamp'],
            'updatedTimestamp' => $syncContext['timestamp'],
            'accountIdentifier' => $targetAccountIdentifier,
        ];
    }

    private function buildAccountLinkTypes(): array
    {
        return [
            'clerkUserId' => ParameterType::STRING,
            'username' => ParameterType::STRING,
            'firstName' => ParameterType::STRING,
            'lastName' => ParameterType::STRING,
            'roleDesignation' => ParameterType::STRING,
            'approvedAt' => ParameterType::STRING,
            'updatedTimestamp' => ParameterType::STRING,
            'accountIdentifier' => ParameterType::INTEGER,
        ];
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
                'acceptedAt' => $acceptedAt,
                'emailAddress' => $emailAddress,
            ],
            [
                'acceptedAt' => ParameterType::STRING,
                'emailAddress' => ParameterType::STRING,
            ]
        );
    }

    private function extractRelevantMetadata(array $userData): array
    {
        $publicMetadata = is_array($userData['public_metadata'] ?? null) ? $userData['public_metadata'] : [];
        $unsafeMetadata = is_array($userData['unsafe_metadata'] ?? null) ? $userData['unsafe_metadata'] : [];
        $privateMetadata = is_array($userData['private_metadata'] ?? null) ? $userData['private_metadata'] : [];

        return array_merge($publicMetadata, $unsafeMetadata, $privateMetadata);
    }

    private function resolveMetadataAccountIdentifier(array $metadata): ?int
    {
        $candidate = $metadata['account_id'] ?? $metadata['techreserve_account_identifier'] ?? null;
        $accountIdentifier = (int)$candidate;

        return $accountIdentifier > 0 ? $accountIdentifier : null;
    }

    private function findTargetAccount(?int $accountIdentifier, string $emailAddress, string $clerkUserId): ?array
    {
        if ($accountIdentifier !== null) {
            $account = $this->connection->fetchAssociative(
                'SELECT account_identifier, role_designation, status, is_verified
                 FROM accounts
                 WHERE account_identifier = :accountIdentifier
                 LIMIT 1',
                ['accountIdentifier' => $accountIdentifier],
                ['accountIdentifier' => ParameterType::INTEGER]
            );

            if ($account !== false) {
                return $account;
            }
        }

        $account = $this->connection->fetchAssociative(
            'SELECT account_identifier, role_designation, status, is_verified
             FROM accounts
             WHERE LOWER(email_address) = LOWER(:emailAddress)
                OR clerk_user_id = :clerkUserId
             ORDER BY created_timestamp DESC
             LIMIT 1',
            [
                'emailAddress' => $emailAddress,
                'clerkUserId' => $clerkUserId,
            ],
            [
                'emailAddress' => ParameterType::STRING,
                'clerkUserId' => ParameterType::STRING,
            ]
        );

        return $account !== false ? $account : null;
    }

    private function canActivateMatchedAccount(array $matchedAccount): bool
    {
        $status = strtolower(trim((string)($matchedAccount['status'] ?? 'pending')));
        $isVerified = $this->toDatabaseBoolean($matchedAccount['is_verified'] ?? false);

        if (in_array($status, ['active', 'approved', 'accepted'], true)) {
            return true;
        }

        return $isVerified && in_array($status, ['verified', 'invited'], true);
    }

    private function resolvePrimaryEmailAddress(array $userData): string
    {
        $primaryEmailAddressId = (string)($userData['primary_email_address_id'] ?? '');
        $emailAddresses = is_array($userData['email_addresses'] ?? null) ? $userData['email_addresses'] : [];

        foreach ($emailAddresses as $emailAddress) {
            if ((string)($emailAddress['id'] ?? '') === $primaryEmailAddressId) {
                return trim((string)($emailAddress['email_address'] ?? ''));
            }
        }

        return trim((string)($emailAddresses[0]['email_address'] ?? ''));
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
