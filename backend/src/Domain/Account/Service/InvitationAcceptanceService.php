<?php

namespace App\Domain\Account\Service;

use App\Domain\Account\Entity\AccountEntity;
use App\Domain\Account\Repository\AccountRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class InvitationAcceptanceService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AccountRepository $accountRepository
    ) {
    }

    public function accept(string $invitationToken): array
    {
        $normalizedToken = trim($invitationToken);
        if ($normalizedToken === '') {
            return $this->error('ValidationError', 'Invitation token is required.', 422);
        }

        $invitation = $this->findInvitationByToken($normalizedToken);
        if ($invitation === null) {
            return $this->error('InvitationNotFound', 'This invitation link is invalid.', 404);
        }

        if ($this->isInvitationExpired($invitation)) {
            return $this->error('InvitationExpired', 'This invitation link has expired. Please request a new invite.', 410);
        }

        $account = $this->resolveInvitationAccount($invitation);
        if ($account === null) {
            return $this->error('AccountNotFound', 'No account is linked to this invitation.', 404);
        }

        if (!$account->getIsActive() || !in_array(strtolower($account->getStatus()), ['active', 'approved', 'accepted'], true)) {
            $this->activateInvitedAccount($account, $invitation);
            $account = $this->accountRepository->find($account->getAccountIdentifier()) ?? $account;
        }

        return [
            'success' => true,
            'data' => [
                'token' => $this->buildLocalToken($account),
                'account' => $this->buildAccountResponse($account),
                'invitation' => [
                    'acceptedAt' => (string)($invitation['accepted_at'] ?? date('Y-m-d H:i:sP')),
                    'emailAddress' => $account->getEmailAddress(),
                ],
            ],
        ];
    }

    private function findInvitationByToken(string $invitationToken): ?array
    {
        $invitation = $this->connection->fetchAssociative(
            'SELECT id, email, status, expires_at, accepted_at, created_at
             FROM invitations
             WHERE invitation_token = :invitationToken
             LIMIT 1',
            ['invitationToken' => $invitationToken],
            ['invitationToken' => ParameterType::STRING]
        );

        return $invitation ?: null;
    }

    private function resolveInvitationAccount(array $invitation): ?AccountEntity
    {
        $emailAddress = strtolower(trim((string)($invitation['email'] ?? '')));
        if ($emailAddress === '') {
            return null;
        }

        $invitedAt = $this->normalizeInvitationCreatedAt($invitation['created_at'] ?? null);
        if ($invitedAt !== null) {
            $matchingAccountIdentifier = $this->connection->fetchOne(
                'SELECT account_identifier
                 FROM accounts
                 WHERE LOWER(email_address) = :emailAddress
                   AND invited_at = :invitedAt
                 ORDER BY account_identifier DESC
                 LIMIT 1',
                [
                    'emailAddress' => $emailAddress,
                    'invitedAt' => $invitedAt,
                ],
                [
                    'emailAddress' => ParameterType::STRING,
                    'invitedAt' => ParameterType::STRING,
                ]
            );

            if ((int)$matchingAccountIdentifier > 0) {
                return $this->accountRepository->find((int)$matchingAccountIdentifier);
            }
        }

        $fallbackAccountIdentifier = $this->connection->fetchOne(
            "SELECT account_identifier
             FROM accounts
             WHERE LOWER(email_address) = :emailAddress
             ORDER BY
               CASE
                 WHEN invitation_status = 'sent' THEN 0
                 WHEN invitation_status = 'accepted' THEN 1
                 WHEN status IN ('invited', 'verified') THEN 2
                 WHEN status IN ('active', 'approved', 'accepted') THEN 3
                 ELSE 4
               END,
               COALESCE(invited_at, approved_at, updated_timestamp, created_timestamp) DESC,
               account_identifier DESC
             LIMIT 1",
            ['emailAddress' => $emailAddress],
            ['emailAddress' => ParameterType::STRING]
        );

        if ((int)$fallbackAccountIdentifier <= 0) {
            return null;
        }

        return $this->accountRepository->find((int)$fallbackAccountIdentifier);
    }

    private function normalizeInvitationCreatedAt(mixed $createdAt): ?string
    {
        $value = trim((string)($createdAt ?? ''));
        if ($value === '') {
            return null;
        }

        try {
            return (new \DateTimeImmutable($value))->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function isInvitationExpired(array $invitation): bool
    {
        try {
            $expiresAt = new \DateTimeImmutable((string)$invitation['expires_at']);
            return $expiresAt < new \DateTimeImmutable();
        } catch (\Throwable) {
            return true;
        }
    }

    private function activateInvitedAccount(AccountEntity $account, array $invitation): void
    {
        $acceptedAt = new \DateTimeImmutable();

        $this->connection->beginTransaction();

        try {
            $this->connection->executeStatement(
                'UPDATE accounts
                 SET status = :status,
                     is_verified = :isVerified,
                     verification_status = :verificationStatus,
                     invitation_status = :invitationStatus,
                     is_approved = :isApproved,
                     is_active = :isActive,
                     approved_at = COALESCE(approved_at, :approvedAt),
                     updated_timestamp = :updatedTimestamp
                 WHERE account_identifier = :accountIdentifier',
                [
                    'status' => 'active',
                    'isVerified' => true,
                    'verificationStatus' => 'verified',
                    'invitationStatus' => 'accepted',
                    'isApproved' => true,
                    'isActive' => true,
                    'approvedAt' => $acceptedAt->format('Y-m-d H:i:s'),
                    'updatedTimestamp' => $acceptedAt->format('Y-m-d H:i:s'),
                    'accountIdentifier' => $account->getAccountIdentifier(),
                ],
                [
                    'status' => ParameterType::STRING,
                    'isVerified' => ParameterType::BOOLEAN,
                    'verificationStatus' => ParameterType::STRING,
                    'invitationStatus' => ParameterType::STRING,
                    'isApproved' => ParameterType::BOOLEAN,
                    'isActive' => ParameterType::BOOLEAN,
                    'approvedAt' => ParameterType::STRING,
                    'updatedTimestamp' => ParameterType::STRING,
                    'accountIdentifier' => ParameterType::INTEGER,
                ]
            );

            $this->connection->executeStatement(
                'UPDATE invitations
                 SET status = :status,
                     accepted_at = COALESCE(accepted_at, :acceptedAt)
                 WHERE id = :invitationId',
                [
                    'status' => 'accepted',
                    'acceptedAt' => $acceptedAt->format('Y-m-d H:i:sP'),
                    'invitationId' => $invitation['id'],
                ],
                [
                    'status' => ParameterType::STRING,
                    'acceptedAt' => ParameterType::STRING,
                    'invitationId' => ParameterType::STRING,
                ]
            );

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
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
            'status' => 'active',
            'isVerified' => true,
            'verificationStatus' => 'verified',
            'isApproved' => true,
            'isActive' => true,
            'invitationStatus' => 'accepted',
            'invitedAt' => $account->getInvitedAt()?->format('Y-m-d H:i:s'),
            'approvedAt' => $account->getApprovedAt()?->format('Y-m-d H:i:s'),
            'profilePhotoData' => $profilePhotoData ? (string)$profilePhotoData : null,
        ];
    }

    private function error(string $errorCode, string $message, int $status): array
    {
        return [
            'success' => false,
            'errorCode' => $errorCode,
            'message' => $message,
            'status' => $status,
        ];
    }
}
