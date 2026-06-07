<?php

namespace App\Domain\Account\Service;

use App\Domain\AuditLog\Service\AuditLogRecordService;
use App\Shared\Utils\AppClock;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class WishlistAccountApprovalService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AccountAcceptanceEmailService $accountAcceptanceEmailService,
        private readonly AccountClerkProvisioningService $accountClerkProvisioningService,
        private readonly AdminSecurityConfirmationService $adminSecurityConfirmationService,
        private readonly AccountSupportingDocumentService $accountSupportingDocumentService,
        private readonly InvitationExpiryPolicyService $invitationExpiryPolicyService,
        private readonly AuditLogRecordService $auditLogRecordService
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

        $stateError = $this->validateInvitationState($account, $requestBody);
        if ($stateError !== null) {
            return $stateError;
        }

        return $this->sendAndRecordInvitation($account, $adminResult['emailAddress'], $authenticatedAdminId, $requestBody);
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

        $now = AppClock::now();
        $clerkUserId = $this->findExistingClerkUserId((string)$account['email_address']);
        $this->approveAccountRow($accountIdentifier, $now, $clerkUserId);
        $this->accountSupportingDocumentService->clearSupportingDocumentForAccount($accountIdentifier);

        return $this->success([
            'message' => 'Email verified and account approved.',
            'account' => $this->buildApprovedAccountPayload($account),
        ]);
    }

    private function resolveConfirmedAdminEmail(array $requestBody, int $authenticatedAdminId, string $actionLabel): array
    {
        $confirmedAdminEmail = $this->normalizeEmailForConfirmation((string)($requestBody['confirmedAdminEmail'] ?? ''));
        $emailError = $this->adminSecurityConfirmationService->validateAdminEmail(
            $authenticatedAdminId,
            $confirmedAdminEmail,
            $actionLabel
        );
        if ($emailError !== null) {
            return $this->error(
                'SecurityConfirmationFailed',
                $emailError,
                422
            );
        }

        return [
            'success' => true,
            'emailAddress' => $confirmedAdminEmail,
        ];
    }

    private function findInvitationEligibleAccount(int $accountIdentifier): array|false
    {
        return $this->connection->fetchAssociative(
            "SELECT accounts.account_identifier, accounts.email_address, accounts.role_designation, accounts.first_name, accounts.last_name,
                    accounts.department, accounts.id_number, accounts.status, accounts.is_approved,
                    latest_invitation.id AS invite_row_id,
                    latest_invitation.status AS invite_status,
                    latest_invitation.invitation_token AS invite_token,
                    latest_invitation.created_at AS invite_sent_at,
                    latest_invitation.expires_at AS invite_expires_at,
                    latest_invitation.accepted_at AS invite_accepted_at
             FROM accounts
             LEFT JOIN LATERAL (
                SELECT id, status, invitation_token, created_at, expires_at, accepted_at
                FROM invitations
                WHERE LOWER(email) = LOWER(accounts.email_address)
                ORDER BY created_at DESC
                LIMIT 1
             ) latest_invitation ON TRUE
             WHERE accounts.account_identifier = :accountIdentifier
               AND COALESCE(accounts.is_approved, FALSE) = FALSE
               AND LOWER(COALESCE(accounts.status, 'pending')) NOT IN ('approved', 'accepted', 'rejected', 'disabled')",
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

    private function validateInvitationState(array $account, array $requestBody = []): ?array
    {
        $accountStatus = strtolower((string)($account['status'] ?? 'pending'));
        $forceResend = (bool)($requestBody['forceResend'] ?? false);

        if (!empty($account['invite_accepted_at'])) {
            return $this->error('InviteAlreadyAccepted', 'This account invitation has already been accepted.', 409);
        }

        if (!$forceResend && $accountStatus === 'invited' && empty($account['invite_expires_at'])) {
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

        if (!$forceResend && $existingInviteExpiresAt >= AppClock::now()) {
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

    private function sendAndRecordInvitation(array $account, string $invitedBy, int $performedByAccountId, array $requestBody = []): array
    {
        $invitationDraft = $this->buildInvitationDraft($requestBody);
        $useBrandedMailer = $this->accountAcceptanceEmailService->shouldUseBrandedMailer();
        $isResend = !empty($account['invite_sent_at']);

        if ($isResend) {
            $revokeError = $this->revokePreviousInvitationIfNeeded($account);
            if ($revokeError !== null) {
                return $revokeError;
            }
        }

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

        $invitationRecord = $this->buildInvitationRecord($clerkInvitation, $invitationDraft);
        $invitationContext = $this->buildInvitationContext($account, $invitedBy, $invitationDraft);
        $invitationContext['invitationRecord'] = $invitationRecord;
        $databaseError = $this->recordInvitation($invitationContext);
        if ($databaseError !== null) {
            return $databaseError;
        }

        $this->recordInvitationAuditLog($performedByAccountId, $account, $invitedBy, $invitationRecord, $isResend);

        return $this->success($this->buildInvitationSuccessPayload($invitationContext, $clerkInvitation, $clerkInvitationUrl));
    }

    private function revokePreviousInvitationIfNeeded(array $account): ?array
    {
        $token = trim((string)($account['invite_token'] ?? ''));
        if ($token === '' || !str_starts_with($token, 'inv_')) {
            return null;
        }

        try {
            $this->accountClerkProvisioningService->revokeInvitation($token);

            $invitationRowId = trim((string)($account['invite_row_id'] ?? ''));
            if ($invitationRowId !== '') {
                $this->connection->executeStatement(
                    "UPDATE invitations
                     SET status = :status
                     WHERE id = :invitationId",
                    [
                        'status' => 'revoked',
                        'invitationId' => $invitationRowId,
                    ],
                    [
                        'status' => ParameterType::STRING,
                        'invitationId' => ParameterType::STRING,
                    ]
                );
            }
        } catch (\Throwable $exception) {
            return $this->error(
                'ClerkInvitationRevokeFailed',
                'The previous invitation could not be revoked before resending: ' . $exception->getMessage(),
                502
            );
        }

        return null;
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
            $this->markAccountAsInvited($context['accountIdentifier'], $context['invitationRecord']['createdAt'], $context['clerkUserId']);

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
                    'acceptedAt' => $context['invitationRecord']['acceptedAt'] === null ? ParameterType::NULL : ParameterType::STRING,
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
        $invitationRecord = $context['invitationRecord'];

        return [
            'email' => (string)$account['email_address'],
            'invitedBy' => $context['invitedBy'],
            'organization' => 'TechReserve',
            'invitationToken' => $invitationRecord['token'],
            'status' => $invitationRecord['status'],
            'expiresAt' => $invitationRecord['expiresAt']->format('Y-m-d H:i:sP'),
            'createdAt' => $invitationRecord['createdAt']->format('Y-m-d H:i:sP'),
            'acceptedAt' => $invitationRecord['acceptedAt']?->format('Y-m-d H:i:sP'),
        ];
    }

    private function markAccountAsInvited(int $accountIdentifier, \DateTimeImmutable $updatedAt, ?string $clerkUserId): void
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
                'status' => 'invited',
                'isApproved' => false,
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

    private function buildInvitationDraft(array $requestBody = []): array
    {
        $createdAt = AppClock::now();
        $redirectUrl = $this->resolveInvitationRedirectUrl($requestBody);

        return [
            'createdAt' => $createdAt,
            'expiresAt' => $this->invitationExpiryPolicyService->buildExpiresAt($createdAt),
            'token' => bin2hex(random_bytes(24)),
            'redirectUrl' => $redirectUrl,
        ];
    }

    private function resolveInvitationRedirectUrl(array $requestBody): string
    {
        $requestedRedirectUrl = trim((string)($requestBody['redirectUrl'] ?? ''));
        if ($this->isValidAbsoluteHttpUrl($requestedRedirectUrl)) {
            return $requestedRedirectUrl;
        }

        $frontendUrl = rtrim((string)($_ENV['FRONTEND_URL'] ?? 'https://techreserve.farahkenawy.codes'), '/');
        $fallbackRedirectUrl = $frontendUrl . '/clerk-login';

        return $this->isValidAbsoluteHttpUrl($fallbackRedirectUrl)
            ? $fallbackRedirectUrl
            : 'https://techreserve.farahkenawy.codes/clerk-login';
    }

    private function isValidAbsoluteHttpUrl(string $url): bool
    {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));

        return $scheme === 'https' || $scheme === 'http';
    }

    private function buildInvitationRecord(array $clerkInvitation, array $invitationDraft): array
    {
        $createdAt = $this->normalizeInvitationTimestamp(
            $clerkInvitation['created_at'] ?? $clerkInvitation['createdAt'] ?? null
        ) ?? $invitationDraft['createdAt'];
        $expiresAt = $this->normalizeInvitationTimestamp(
            $clerkInvitation['expires_at'] ?? $clerkInvitation['expiresAt'] ?? null
        );
        $resolvedExpiresAt = $this->invitationExpiryPolicyService->resolveStoredExpiresAt($invitationDraft['expiresAt'], $expiresAt);
        $acceptedAt = $this->normalizeInvitationTimestamp(
            $clerkInvitation['accepted_at'] ?? $clerkInvitation['acceptedAt'] ?? null
        );
        $status = strtolower(trim((string)($clerkInvitation['status'] ?? 'pending')));

        return [
            'token' => (string)($clerkInvitation['id'] ?? $invitationDraft['token']),
            'status' => $status !== '' ? $status : 'pending',
            'createdAt' => $createdAt,
            'expiresAt' => $resolvedExpiresAt,
            'acceptedAt' => $acceptedAt,
        ];
    }

    private function normalizeInvitationTimestamp(mixed $value): ?\DateTimeImmutable
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

    private function buildInvitationSuccessPayload(
        array $context,
        array $clerkInvitation,
        string $clerkInvitationUrl
    ): array {
        $account = $context['account'];
        $invitationDraft = $context['draft'];
        $invitationRecord = $context['invitationRecord'];

        return [
            'message' => 'Invitation sent successfully.',
            'account' => $this->buildInvitedAccountPayload($account),
            'invitation' => [
                'emailAddress' => (string)$account['email_address'],
                'role' => (string)$account['role_designation'],
                'status' => $invitationRecord['status'],
                'token' => $invitationRecord['token'],
                'clerkInvitationId' => $clerkInvitation['id'] ?? null,
                'sentAt' => $invitationRecord['createdAt']->format('Y-m-d\TH:i:sP'),
                'expiresAt' => $invitationRecord['expiresAt']->format('Y-m-d\TH:i:sP'),
                'acceptedAt' => $invitationRecord['acceptedAt']?->format('Y-m-d\TH:i:sP'),
                'redirectUrl' => $invitationDraft['redirectUrl'],
                'invitationUrl' => $clerkInvitationUrl,
                'sentBy' => $context['invitedBy'],
                'emailSent' => true,
                'movesToManageAccounts' => false,
            ],
        ];
    }

    private function buildInvitedAccountPayload(array $account): array
    {
        return [
            'accountIdentifier' => (int)$account['account_identifier'],
            'emailAddress' => (string)$account['email_address'],
            'roleDesignation' => (string)$account['role_designation'],
            'status' => 'invited',
            'isApproved' => false,
            'isActive' => true,
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

    private function recordInvitationAuditLog(int $performedByAccountId, array $account, string $invitedBy, array $invitationRecord, bool $isResend): void
    {
        try {
            $this->auditLogRecordService->recordAuditLog(
                $performedByAccountId,
                $isResend ? 'RESEND_INVITATION' : 'SEND_INVITATION',
                'invitation',
                (int)$account['account_identifier'],
                [
                    'emailAddress' => (string)$account['email_address'],
                    'invitedBy' => $invitedBy,
                    'expiresAt' => $invitationRecord['expiresAt']->format('Y-m-d H:i:sP'),
                    'sentAt' => $invitationRecord['createdAt']->format('Y-m-d H:i:sP'),
                    'policy' => $this->invitationExpiryPolicyService->currentPolicySummary(),
                ]
            );
        } catch (\Throwable) {
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
