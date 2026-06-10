<?php

namespace App\Domain\Account\Service;

use App\Shared\Utils\AccountUsername;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;

class ClerkWebhookUserSyncService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger
    ) {
    }

    public function sync(array $userData): void
    {
        $clerkUserId = trim((string)($userData['id'] ?? ''));
        $emailAddress = $this->resolvePrimaryEmailAddress($userData);
        $metadata = $this->extractRelevantMetadata($userData);
        $accountIdentifier = $this->resolveMetadataAccountIdentifier($metadata);
        $firstName = trim((string)($userData['first_name'] ?? $metadata['first_name'] ?? ''));
        $lastName = trim((string)($userData['last_name'] ?? $metadata['last_name'] ?? ''));
        $roleDesignation = trim((string)($metadata['role_designation'] ?? ''));

        if ($clerkUserId === '' || $emailAddress === '') {
            $this->logger->warning('Clerk webhook skipped because account identifiers were incomplete.', [
                'clerkUserId' => $clerkUserId,
                'emailAddress' => $emailAddress,
            ]);
            return;
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->connection->beginTransaction();

        try {
            $matchedAccount = $this->findTargetAccount($accountIdentifier, $emailAddress, $clerkUserId);
            if ($matchedAccount === null) {
                $this->connection->rollBack();
                $this->logger->warning('Clerk webhook could not match a PostgreSQL account.', [
                    'clerkUserId' => $clerkUserId,
                    'emailAddress' => $emailAddress,
                    'accountIdentifier' => $accountIdentifier,
                ]);
                return;
            }

            $targetAccountIdentifier = (int)$matchedAccount['account_identifier'];
            $roleFromDatabase = trim((string)($matchedAccount['role_designation'] ?? ''));

            $updatedRows = $this->connection->executeStatement(
                "UPDATE accounts
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
                     is_approved = TRUE,
                     status = 'approved',
                     approved_at = COALESCE(approved_at, :approvedAt),
                     updated_timestamp = :updatedTimestamp
                 WHERE account_identifier = :accountIdentifier
                   AND (clerk_user_id IS NULL OR clerk_user_id = '' OR clerk_user_id = :clerkUserId)",
                [
                    'clerkUserId' => $clerkUserId,
                    'username' => AccountUsername::fromEmail($emailAddress),
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'roleDesignation' => $roleFromDatabase !== '' ? $roleFromDatabase : $roleDesignation,
                    'approvedAt' => $now,
                    'updatedTimestamp' => $now,
                    'accountIdentifier' => $targetAccountIdentifier,
                ],
                [
                    'clerkUserId' => ParameterType::STRING,
                    'username' => ParameterType::STRING,
                    'firstName' => ParameterType::STRING,
                    'lastName' => ParameterType::STRING,
                    'roleDesignation' => ParameterType::STRING,
                    'approvedAt' => ParameterType::STRING,
                    'updatedTimestamp' => ParameterType::STRING,
                    'accountIdentifier' => ParameterType::INTEGER,
                ]
            );

            if ($updatedRows === 0) {
                $this->connection->rollBack();
                $this->logger->warning('Clerk webhook found account but could not link Clerk user ID.', [
                    'clerkUserId' => $clerkUserId,
                    'emailAddress' => $emailAddress,
                    'accountIdentifier' => $targetAccountIdentifier,
                ]);
                return;
            }

            $this->markLatestInvitationAccepted($emailAddress, $now);
            $this->connection->commit();

            $this->logger->info('Clerk account linked and approved.', [
                'accountIdentifier' => $targetAccountIdentifier,
                'clerkUserId' => $clerkUserId,
                'emailAddress' => $emailAddress,
            ]);
        } catch (\Throwable $exception) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            $this->logger->error('Clerk webhook sync failed.', [
                'clerkUserId' => $clerkUserId,
                'emailAddress' => $emailAddress,
                'accountIdentifier' => $accountIdentifier,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
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
                'SELECT account_identifier, role_designation
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
            'SELECT account_identifier, role_designation
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
}
