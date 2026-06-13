<?php

namespace App\Domain\Account\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;

class WishlistAccountApprovalService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AccountClerkProvisioningService $accountClerkProvisioningService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function approve(int $accountIdentifier, array $requestBody, int $authenticatedAdminId): array
    {
        $adminResult = $this->resolveConfirmedAdminEmail($requestBody, $authenticatedAdminId, 'sending the invite');
        if (!$adminResult['success']) return $adminResult;

        $account = $this->findInvitationEligibleAccount($accountIdentifier);
        if (!$account) {
            return $this->error(
                'WishlistAccountNotFound',
                'This email is not in the Requests Hub database or is no longer eligible for invitation.',
                404
            );
        }

        $stateError = $this->validateInvitationState($account);
        if ($stateError !== null) {
            return $stateError;
        }

        return $this->sendAndRecordInvitation($account, $adminResult['emailAddress']);
    }

    public function verifyEmailAndApprove(int $accountIdentifier, array $requestBody, int $authenticatedAdminId): array
    {
        $adminResult = $this->resolveConfirmedAdminEmail($requestBody, $authenticatedAdminId, 'approving access');
        if (!$adminResult['success']) return $adminResult;

        $account = $this->findEmailVerifiedAccount($accountIdentifier);
        if (!$account) {
            return $this->error(
                'WishlistAccountNotFound',
                'This account is not ready for email verification approval.',
                404
            );
        }

        $now = new \DateTimeImmutable();
        $this->approveAccountRow($accountIdentifier, $now, null);

        return $this->success([
            'message' => 'Email verified and account approved.',
            'account' => $this->buildApprovedAccountPayload($account),
        ]);
    }

    private function resolveConfirmedAdminEmail(array $requestBody, int $authenticatedAdminId, string $actionLabel): array
    {
        $confirmedAdminEmail = $this->normalizeEmailForConfirmation((string)($requestBody['confirmedAdminEmail'] ?? ''));
        if ($confirmedAdminEmail === '') {
            return $this->error(
                'SecurityConfirmationFailed',
                'Please type the responsible admin email before sending the invite.',
                422
            );
        }

        $confirmedAdmin = $this->findConfirmedAdmin($authenticatedAdminId);
        $adminEmailAddress = $this->normalizeEmailForConfirmation((string)($confirmedAdmin['email_address'] ?? ''));

        if (!$confirmedAdmin || $confirmedAdminEmail !== $adminEmailAddress) {
            return $this->error(
                'SecurityConfirmationFailed',
                sprintf('Please type your exact admin email before %s.', $actionLabel),
                422
            );
        }

        return [
            'success' => true,
            'emailAddress' => $adminEmailAddress,
        ];
    }

    private function findConfirmedAdmin(int $authenticatedAdminId): array|false
    {
        return $this->connection->fetchAssociative(
            "SELECT account_identifier, email_address
             FROM accounts
             WHERE account_identifier = :accountIdentifier
               AND role_designation IN ('ROLE_ADMIN', 'ADMIN')
               AND COALESCE(is_active, TRUE) = TRUE
             LIMIT 1",
            ['accountIdentifier' => $authenticatedAdminId],
            ['accountIdentifier' => ParameterType::INTEGER]
        );
    }

    private function findInvitationEligibleAccount(int $accountIdentifier): array|false
    {
        return $this->connection->fetchAssociative(
            "SELECT account_identifier, email_address, role_designation, first_name, last_name,
                    department, id_number, status, is_approved, is_verified,
                    verification_status, invitation_status, clerk_user_id,
                    latest_invitation.created_at AS invite_sent_at,
                    latest_invitation.expires_at AS invite_expires_at,
                    latest_invitation.accepted_at AS invite_accepted_at
             FROM accounts
             LEFT JOIN LATERAL (
                SELECT created_at, expires_at, accepted_at
                FROM invitations
                WHERE LOWER(email) = LOWER(accounts.email_address)
                ORDER BY created_at DESC
                LIMIT 1
             ) latest_invitation ON TRUE
             WHERE account_identifier = :accountIdentifier
               AND COALESCE(is_approved, FALSE) = FALSE
               AND LOWER(COALESCE(status, 'pending')) NOT IN ('active', 'approved', 'accepted', 'rejected', 'disabled', 'deleted', 'archived')",
            ['accountIdentifier' => $accountIdentifier],
            ['accountIdentifier' => ParameterType::INTEGER]
        );
    }

    private function findEmailVerifiedAccount(int $accountIdentifier): array|false
    {
        return $this->connection->fetchAssociative(
            "SELECT accounts.account_identifier, accounts.email_address, accounts.role_designation,
                    accounts.status, accounts.is_approved,
                    latest_invitation.accepted_at AS invite_accepted_at
             FROM accounts
             LEFT JOIN LATERAL (
                SELECT accepted_at
                FROM invitations
                WHERE LOWER(email) = LOWER(accounts.email_address)
                ORDER BY created_at DESC
                LIMIT 1
             ) latest_invitation ON TRUE
             WHERE accounts.account_identifier = :accountIdentifier
               AND COALESCE(accounts.is_approved, FALSE) = FALSE
               AND LOWER(COALESCE(accounts.status, 'pending')) = 'invited'
               AND latest_invitation.accepted_at IS NOT NULL",
            ['accountIdentifier' => $accountIdentifier],
            ['accountIdentifier' => ParameterType::INTEGER]
        );
    }

    private function validateInvitationState(array $account): ?array
    {
        $accountStatus = strtolower((string)($account['status'] ?? 'pending'));
        $invitationStatus = strtolower((string)($account['invitation_status'] ?? 'not_sent'));

        if (!empty($account['invite_accepted_at'])) {
            return $this->error('InviteAlreadyAccepted', 'This account invitation has already been accepted.', 409);
        }

        if (in_array($accountStatus, ['approved', 'active'], true)) {
            return $this->error('InviteAlreadyAccepted', 'This account is already approved.', 409);
        }

        if ($accountStatus === 'invited' && $invitationStatus === 'sent' && empty($account['invite_expires_at'])) {
            return $this->error(
                'InviteAlreadySent',
                'This account is already marked as invited. Resend becomes available after 7 days.',
                409
            );
        }

        $existingInviteExpiresAt = $this->resolveInviteExpiresAt($account);
        if ($existingInviteExpiresAt === null) {
            return null;
        }

        if ($existingInviteExpiresAt === false) {
            return $this->invalidInviteStatusError();
        }

        if ($existingInviteExpiresAt >= new \DateTimeImmutable()) {
            return $this->activeInviteError();
        }

        return null;
    }

    private function resolveInviteExpiresAt(array $account): \DateTimeImmutable|false|null
    {
        if (empty($account['invite_expires_at'])) {
            return null;
        }

        try {
            return new \DateTimeImmutable((string)$account['invite_expires_at']);
        } catch (\Throwable) {
            return false;
        }
    }

    private function activeInviteError(): array
    {
        return $this->error(
            'InviteAlreadySent',
            'This account already has an active invitation. Resend becomes available after 7 days.',
            409
        );
    }

    private function invalidInviteStatusError(): array
    {
        return $this->error(
            'InviteStatusInvalid',
            'The existing invitation status could not be verified. Please refresh Requests Hub and try again.',
            409
        );
    }

    private function sendAndRecordInvitation(array $account, string $invitedBy): array
    {
        $redirectUrl = $this->buildClerkInvitationRedirectUrl();
        try {
            $clerkInvitation = $this->accountClerkProvisioningService->sendInvitation($account, $redirectUrl, true);
        } catch (\Throwable $exception) {
            return $this->error(
                'InvitationEmailFailed',
                'The Clerk invitation could not be sent: ' . $exception->getMessage(),
                502
            );
        }

        $invitationDraft = $this->buildInvitationDraft($clerkInvitation, $redirectUrl);
        $invitationContext = $this->buildInvitationContext($account, $invitedBy, $invitationDraft);
        $databaseError = $this->recordInvitation($invitationContext);
        if ($databaseError !== null) {
            return $databaseError;
        }

        $this->logger->info('Invitation sent and recorded.', [
            'accountIdentifier' => $invitationContext['accountIdentifier'],
            'emailAddress' => (string)$account['email_address'],
            'invitationToken' => $invitationDraft['token'],
            'invitedBy' => $invitedBy,
        ]);

        return $this->success($this->buildInvitationSuccessPayload($invitationContext));
    }

    private function recordInvitation(array $context): ?array
    {
        $this->connection->beginTransaction();

        try {
            $this->markAccountAsInvited($context['accountIdentifier'], $context['draft']['createdAt']);

            $this->connection->executeStatement(
                'INSERT INTO invitations
                    (email, invited_by, organization, invitation_token, status, expires_at, created_at, accepted_at)
                 VALUES
                    (:email, :invitedBy, :organization, :invitationToken, :status, :expiresAt, :createdAt, :acceptedAt)',
                $this->buildInvitationInsertParameters($context),
                [
                    'email' => ParameterType::STRING,
                    'invitedBy' => ParameterType::STRING,
                    'organization' => ParameterType::STRING,
                    'invitationToken' => ParameterType::STRING,
                    'status' => ParameterType::STRING,
                    'expiresAt' => ParameterType::STRING,
                    'createdAt' => ParameterType::STRING,
                    'acceptedAt' => ParameterType::NULL,
                ]
            );

            $this->connection->commit();
            return null;
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            return $this->error(
                'ApproveAccountFailed',
                'The invitation email was sent, but the database could not record it: ' . $exception->getMessage(),
                500
            );
        }
    }

    private function buildInvitationContext(array $account, string $invitedBy, array $invitationDraft): array
    {
        return [
            'account' => $account,
            'accountIdentifier' => (int)$account['account_identifier'],
            'invitedBy' => $invitedBy,
            'draft' => $invitationDraft,
        ];
    }

    private function buildInvitationInsertParameters(array $context): array
    {
        $account = $context['account'];
        $invitationDraft = $context['draft'];

        return [
            'email' => (string)$account['email_address'],
            'invitedBy' => $context['invitedBy'],
            'organization' => 'TechReserve',
            'invitationToken' => $invitationDraft['token'],
            'status' => 'sent',
            'expiresAt' => $invitationDraft['expiresAt']->format('Y-m-d H:i:sP'),
            'createdAt' => $invitationDraft['createdAt']->format('Y-m-d H:i:sP'),
            'acceptedAt' => null,
        ];
    }

    private function markAccountAsInvited(int $accountIdentifier, \DateTimeImmutable $updatedAt): void
    {
        $this->connection->executeStatement(
            'UPDATE accounts
             SET status = :status,
                 is_verified = :isVerified,
                 verification_status = :verificationStatus,
                 invitation_status = :invitationStatus,
                 is_approved = :isApproved,
                 is_active = :isActive,
                 invited_at = :invitedAt,
                 approved_at = NULL,
                 updated_timestamp = :updatedTimestamp
             WHERE account_identifier = :accountIdentifier',
            [
                'status' => 'invited',
                'isVerified' => false,
                'verificationStatus' => 'unverified',
                'invitationStatus' => 'sent',
                'isApproved' => false,
                'isActive' => false,
                'invitedAt' => $updatedAt->format('Y-m-d H:i:s'),
                'updatedTimestamp' => $updatedAt->format('Y-m-d H:i:s'),
                'accountIdentifier' => $accountIdentifier,
            ],
            [
                'status' => ParameterType::STRING,
                'isVerified' => ParameterType::BOOLEAN,
                'verificationStatus' => ParameterType::STRING,
                'invitationStatus' => ParameterType::STRING,
                'isApproved' => ParameterType::BOOLEAN,
                'isActive' => ParameterType::BOOLEAN,
                'invitedAt' => ParameterType::STRING,
                'updatedTimestamp' => ParameterType::STRING,
                'accountIdentifier' => ParameterType::INTEGER,
            ]
        );
    }

    private function approveAccountRow(int $accountIdentifier, \DateTimeImmutable $updatedAt, ?string $clerkUserId): void
    {
        $this->connection->executeStatement(
            'UPDATE accounts
             SET status = :status,
                 invitation_status = :invitationStatus,
                 is_active = :isActive,
                 approved_at = COALESCE(approved_at, :approvedAt),
                 clerk_user_id = COALESCE(NULLIF(clerk_user_id, \'\'), :clerkUserId),
                 updated_timestamp = :updatedTimestamp
             WHERE account_identifier = :accountIdentifier',
            [
                'status' => 'active',
                'invitationStatus' => 'accepted',
                'isActive' => true,
                'approvedAt' => $updatedAt->format('Y-m-d H:i:s'),
                'clerkUserId' => $clerkUserId,
                'updatedTimestamp' => $updatedAt->format('Y-m-d H:i:s'),
                'accountIdentifier' => $accountIdentifier,
            ],
            [
                'status' => ParameterType::STRING,
                'invitationStatus' => ParameterType::STRING,
                'isActive' => ParameterType::BOOLEAN,
                'approvedAt' => ParameterType::STRING,
                'clerkUserId' => $clerkUserId === null ? ParameterType::NULL : ParameterType::STRING,
                'updatedTimestamp' => ParameterType::STRING,
                'accountIdentifier' => ParameterType::INTEGER,
            ]
        );
    }

    private function buildInvitationDraft(array $clerkInvitation, string $redirectUrl): array
    {
        $createdAt = $this->normalizeClerkDateTime(
            $clerkInvitation['created_at']
            ?? $clerkInvitation['createdAt']
            ?? null
        ) ?? new \DateTimeImmutable();
        $expiresAt = $this->normalizeClerkDateTime(
            $clerkInvitation['expires_at']
            ?? $clerkInvitation['expiresAt']
            ?? null
        ) ?? $createdAt->modify('+7 days');
        $invitationId = trim((string)($clerkInvitation['id'] ?? ''));
        $ticket = trim((string)($clerkInvitation['ticket'] ?? ''));
        $token = $ticket !== '' ? $ticket : ($invitationId !== '' ? $invitationId : bin2hex(random_bytes(24)));

        return [
            'createdAt' => $createdAt,
            'expiresAt' => $expiresAt,
            'token' => $token,
            'clerkInvitationId' => $invitationId !== '' ? $invitationId : null,
            'redirectUrl' => $redirectUrl,
            'invitationUrl' => $this->resolveClerkInvitationUrl($clerkInvitation, $redirectUrl),
        ];
    }

    private function buildInvitationSuccessPayload(array $context): array
    {
        $account = $context['account'];
        $invitationDraft = $context['draft'];

        return [
            'message' => 'Invitation sent successfully.',
            'account' => $this->buildInvitedAccountPayload($account),
            'invitation' => [
                'emailAddress' => (string)$account['email_address'],
                'role' => (string)$account['role_designation'],
                'status' => 'invited',
                'token' => $invitationDraft['token'],
                'clerkInvitationId' => $invitationDraft['clerkInvitationId'],
                'sentAt' => $invitationDraft['createdAt']->format('Y-m-d\TH:i:sP'),
                'expiresAt' => $invitationDraft['expiresAt']->format('Y-m-d\TH:i:sP'),
                'acceptedAt' => null,
                'redirectUrl' => $invitationDraft['redirectUrl'],
                'invitationUrl' => $invitationDraft['invitationUrl'],
                'sentBy' => $context['invitedBy'],
                'emailSent' => true,
                'movesToManageAccounts' => false,
            ],
        ];
    }

    private function buildClerkInvitationRedirectUrl(): string
    {
        $frontendUrl = rtrim((string)($_ENV['FRONTEND_URL'] ?? 'https://techreserve.farahkenawy.codes'), '/');
        return $frontendUrl . '/clerk-login';
    }

    private function resolveClerkInvitationUrl(array $clerkInvitation, string $redirectUrl): string
    {
        $url = trim((string)($clerkInvitation['url'] ?? $clerkInvitation['invitation_url'] ?? ''));
        if ($url !== '') {
            return $url;
        }

        $ticket = trim((string)($clerkInvitation['ticket'] ?? ''));
        if ($ticket === '') {
            return $redirectUrl;
        }

        $separator = str_contains($redirectUrl, '?') ? '&' : '?';
        return $redirectUrl . $separator . '__clerk_status=sign_up&__clerk_ticket=' . rawurlencode($ticket);
    }

    private function normalizeClerkDateTime(mixed $value): ?\DateTimeImmutable
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

                return new \DateTimeImmutable('@' . $timestamp);
            }

            return new \DateTimeImmutable((string)$value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildInvitedAccountPayload(array $account): array
    {
        return [
            'accountIdentifier' => (int)$account['account_identifier'],
            'emailAddress' => (string)$account['email_address'],
            'roleDesignation' => (string)$account['role_designation'],
            'accountStatus' => 'invited',
            'isVerified' => false,
            'verificationStatus' => 'unverified',
            'invitationStatus' => 'sent',
            'status' => 'invited',
            'isApproved' => false,
            'isActive' => false,
            'invitedAt' => (new \DateTimeImmutable())->format('Y-m-d\TH:i:sP'),
        ];
    }

    private function buildApprovedAccountPayload(array $account): array
    {
        return [
            'accountIdentifier' => (int)$account['account_identifier'],
            'emailAddress' => (string)$account['email_address'],
            'roleDesignation' => (string)$account['role_designation'],
            'status' => 'active',
            'invitationStatus' => 'accepted',
            'isActive' => true,
        ];
    }

    private function normalizeEmailForConfirmation(string $emailAddress): string
    {
        $normalizedEmailAddress = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\s]+/u', '', $emailAddress) ?? $emailAddress;
        return strtolower(trim($normalizedEmailAddress));
    }

    private function success(array $data, int $status = 200): array
    {
        return [
            'success' => true,
            'status' => $status,
            'data' => $data,
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
