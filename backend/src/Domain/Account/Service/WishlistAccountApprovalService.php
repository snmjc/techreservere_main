<?php

namespace App\Domain\Account\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class WishlistAccountApprovalService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AccountAcceptanceEmailService $accountAcceptanceEmailService,
        private readonly AccountClerkProvisioningService $accountClerkProvisioningService
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
        $clerkUserId = $this->findExistingClerkUserId((string)$account['email_address']);
        $this->approveAccountRow($accountIdentifier, $now, $clerkUserId);

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
                    department, id_number, status, is_approved,
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
               AND LOWER(COALESCE(status, 'pending')) NOT IN ('approved', 'rejected', 'disabled')",
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

        if (!empty($account['invite_accepted_at'])) {
            return $this->error('InviteAlreadyAccepted', 'This account invitation has already been accepted.', 409);
        }

        if ($accountStatus === 'invited' && empty($account['invite_expires_at'])) {
            return $this->error(
                'InviteAlreadySent',
                'This account is already marked as invited. Resend is only available after the invitation expires.',
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
            'This account already has an active invitation. Resend is only available after the invitation expires.',
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
        $invitationDraft = $this->buildInvitationDraft();
        $useBrandedMailer = $this->accountAcceptanceEmailService->shouldUseBrandedMailer();

        try {
            $clerkInvitation = $this->accountClerkProvisioningService->sendInvitation($account, $invitationDraft['redirectUrl'], !$useBrandedMailer);
        } catch (\Throwable $exception) {
            return $this->error(
                'ClerkInvitationFailed',
                'Clerk could not send the invitation: ' . $exception->getMessage(),
                502
            );
        }

        $clerkInvitationUrl = (string)($clerkInvitation['url'] ?? '');
        $mailerError = $this->sendBrandedEmailIfNeeded($useBrandedMailer, $account, $clerkInvitationUrl);
        if ($mailerError !== null) {
            return $mailerError;
        }

        $invitationContext = $this->buildInvitationContext($account, $invitedBy, $invitationDraft);
        $databaseError = $this->recordInvitation($invitationContext);
        if ($databaseError !== null) {
            return $databaseError;
        }

        return $this->success($this->buildInvitationSuccessPayload($invitationContext, $clerkInvitation, $clerkInvitationUrl));
    }

    private function sendBrandedEmailIfNeeded(bool $useBrandedMailer, array $account, string $clerkInvitationUrl): ?array
    {
        if ($useBrandedMailer && $clerkInvitationUrl === '') {
            return $this->error(
                'ClerkInvitationMissingUrl',
                'Clerk created the invitation but did not return an invitation URL.',
                502
            );
        }

        if (!$useBrandedMailer) {
            return null;
        }

        $emailResult = $this->accountAcceptanceEmailService->sendAcceptedAccountEmail($account, $clerkInvitationUrl);
        if (!$emailResult['sent']) {
            return $this->error(
                'InvitationEmailFailed',
                'Clerk created the invitation, but the Outlook email could not be sent: ' . $emailResult['error'],
                502
            );
        }

        return null;
    }

    private function recordInvitation(array $context): ?array
    {
        $this->connection->beginTransaction();

        try {
            $this->approveAccountRow($context['accountIdentifier'], $context['draft']['createdAt'], $context['clerkUserId']);

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
                'Clerk sent the invitation, but the database could not record it: ' . $exception->getMessage(),
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
            'clerkUserId' => $this->findExistingClerkUserId((string)$account['email_address']),
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
            'status' => 'pending',
            'expiresAt' => $invitationDraft['expiresAt']->format('Y-m-d H:i:sP'),
            'createdAt' => $invitationDraft['createdAt']->format('Y-m-d H:i:sP'),
            'acceptedAt' => null,
        ];
    }

    private function approveAccountRow(int $accountIdentifier, \DateTimeImmutable $updatedAt, ?string $clerkUserId): void
    {
        $this->connection->executeStatement(
            'UPDATE accounts
             SET status = :status,
                 is_approved = :isApproved,
                 is_active = :isActive,
                 clerk_user_id = COALESCE(NULLIF(clerk_user_id, \'\'), :clerkUserId),
                 updated_timestamp = :updatedTimestamp
             WHERE account_identifier = :accountIdentifier',
            [
                'status' => 'approved',
                'isApproved' => true,
                'isActive' => true,
                'clerkUserId' => $clerkUserId,
                'updatedTimestamp' => $updatedAt->format('Y-m-d H:i:s'),
                'accountIdentifier' => $accountIdentifier,
            ],
            [
                'status' => ParameterType::STRING,
                'isApproved' => ParameterType::BOOLEAN,
                'isActive' => ParameterType::BOOLEAN,
                'clerkUserId' => $clerkUserId === null ? ParameterType::NULL : ParameterType::STRING,
                'updatedTimestamp' => ParameterType::STRING,
                'accountIdentifier' => ParameterType::INTEGER,
            ]
        );
    }

    private function buildInvitationDraft(): array
    {
        $createdAt = new \DateTimeImmutable();
        $frontendUrl = rtrim((string)($_ENV['FRONTEND_URL'] ?? 'https://techreserve.farahkenawy.codes'), '/');

        return [
            'createdAt' => $createdAt,
            'expiresAt' => $createdAt->modify('+7 days'),
            'token' => bin2hex(random_bytes(24)),
            'redirectUrl' => $frontendUrl . '/clerk-login',
        ];
    }

    private function buildInvitationSuccessPayload(
        array $context,
        array $clerkInvitation,
        string $clerkInvitationUrl
    ): array {
        $account = $context['account'];
        $invitationDraft = $context['draft'];

        return [
            'message' => 'Invitation sent successfully.',
            'account' => $this->buildApprovedAccountPayload($account),
            'invitation' => [
                'emailAddress' => (string)$account['email_address'],
                'role' => (string)$account['role_designation'],
                'status' => 'pending',
                'token' => $invitationDraft['token'],
                'clerkInvitationId' => $clerkInvitation['id'] ?? null,
                'sentAt' => $invitationDraft['createdAt']->format('Y-m-d\TH:i:sP'),
                'expiresAt' => $invitationDraft['expiresAt']->format('Y-m-d\TH:i:sP'),
                'acceptedAt' => null,
                'redirectUrl' => $invitationDraft['redirectUrl'],
                'invitationUrl' => $clerkInvitationUrl,
                'sentBy' => $context['invitedBy'],
                'emailSent' => true,
                'movesToManageAccounts' => true,
            ],
        ];
    }

    private function buildApprovedAccountPayload(array $account): array
    {
        return [
            'accountIdentifier' => (int)$account['account_identifier'],
            'emailAddress' => (string)$account['email_address'],
            'roleDesignation' => (string)$account['role_designation'],
            'status' => 'approved',
            'isApproved' => true,
            'isActive' => true,
        ];
    }

    private function findExistingClerkUserId(string $emailAddress): ?string
    {
        try {
            return $this->accountClerkProvisioningService->findUserIdByEmail($emailAddress);
        } catch (\Throwable) {
            return null;
        }
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
