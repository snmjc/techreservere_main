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
        $confirmedAdminEmail = $this->normalizeEmailForConfirmation((string)($requestBody['confirmedAdminEmail'] ?? ''));
        $adminError = $this->validateAdminConfirmation($confirmedAdminEmail, $authenticatedAdminId);

        if ($adminError !== null) {
            return $adminError;
        }

        $confirmedAdmin = $this->findConfirmedAdmin($authenticatedAdminId);
        $invitedBy = $this->normalizeEmailForConfirmation((string)($confirmedAdmin['email_address'] ?? ''));

        if (!$confirmedAdmin || $confirmedAdminEmail !== $invitedBy) {
            return $this->error(
                'SecurityConfirmationFailed',
                'Please type your exact admin email before sending the invite.',
                422
            );
        }

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

        return $this->sendAndRecordInvitation($account, $accountIdentifier, $invitedBy);
    }

    private function validateAdminConfirmation(string $confirmedAdminEmail, int $authenticatedAdminId): ?array
    {
        if ($confirmedAdminEmail === '') {
            return $this->error(
                'SecurityConfirmationFailed',
                'Please type the responsible admin email before sending the invite.',
                422
            );
        }

        return null;
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

    private function validateInvitationState(array $account): ?array
    {
        $now = new \DateTimeImmutable();
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

        if (empty($account['invite_expires_at'])) {
            return null;
        }

        try {
            $existingInviteExpiresAt = new \DateTimeImmutable((string)$account['invite_expires_at']);
            if ($existingInviteExpiresAt >= $now) {
                return $this->error(
                    'InviteAlreadySent',
                    'This account already has an active invitation. Resend is only available after the invitation expires.',
                    409
                );
            }
        } catch (\Throwable) {
            return $this->error(
                'InviteStatusInvalid',
                'The existing invitation status could not be verified. Please refresh Requests Hub and try again.',
                409
            );
        }

        return null;
    }

    private function sendAndRecordInvitation(array $account, int $accountIdentifier, string $invitedBy): array
    {
        $now = new \DateTimeImmutable();
        $expiresAt = $now->modify('+7 days');
        $invitationToken = bin2hex(random_bytes(24));
        $frontendUrl = rtrim((string)($_ENV['FRONTEND_URL'] ?? 'http://localhost:5173'), '/');
        $redirectUrl = $frontendUrl . '/clerk-login';
        $useBrandedMailer = $this->accountAcceptanceEmailService->shouldUseBrandedMailer();

        try {
            $clerkInvitation = $this->accountClerkProvisioningService->sendInvitation($account, $redirectUrl, !$useBrandedMailer);
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

        $databaseError = $this->recordInvitation($account, $accountIdentifier, $invitedBy, $invitationToken, $now, $expiresAt);
        if ($databaseError !== null) {
            return $databaseError;
        }

        return $this->success([
            'message' => 'Invitation sent successfully.',
            'account' => [
                'accountIdentifier' => (int)$account['account_identifier'],
                'emailAddress' => (string)$account['email_address'],
                'roleDesignation' => (string)$account['role_designation'],
                'status' => 'invited',
                'isApproved' => false,
            ],
            'invitation' => [
                'emailAddress' => (string)$account['email_address'],
                'role' => (string)$account['role_designation'],
                'status' => 'pending',
                'token' => $invitationToken,
                'clerkInvitationId' => $clerkInvitation['id'] ?? null,
                'sentAt' => $now->format('Y-m-d\TH:i:sP'),
                'expiresAt' => $expiresAt->format('Y-m-d\TH:i:sP'),
                'acceptedAt' => null,
                'redirectUrl' => $redirectUrl,
                'invitationUrl' => $clerkInvitationUrl,
                'sentBy' => $invitedBy,
                'emailSent' => true,
            ],
        ]);
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

    private function recordInvitation(
        array $account,
        int $accountIdentifier,
        string $invitedBy,
        string $invitationToken,
        \DateTimeImmutable $now,
        \DateTimeImmutable $expiresAt
    ): ?array {
        $this->connection->beginTransaction();

        try {
            $this->connection->executeStatement(
                'UPDATE accounts
                 SET status = :status, is_approved = :isApproved, updated_timestamp = :updatedTimestamp
                 WHERE account_identifier = :accountIdentifier',
                [
                    'status' => 'invited',
                    'isApproved' => false,
                    'updatedTimestamp' => $now->format('Y-m-d H:i:s'),
                    'accountIdentifier' => $accountIdentifier,
                ],
                [
                    'status' => ParameterType::STRING,
                    'isApproved' => ParameterType::BOOLEAN,
                    'updatedTimestamp' => ParameterType::STRING,
                    'accountIdentifier' => ParameterType::INTEGER,
                ]
            );

            $this->connection->executeStatement(
                'INSERT INTO invitations
                    (email, invited_by, organization, invitation_token, status, expires_at, created_at, accepted_at)
                 VALUES
                    (:email, :invitedBy, :organization, :invitationToken, :status, :expiresAt, :createdAt, :acceptedAt)',
                [
                    'email' => (string)$account['email_address'],
                    'invitedBy' => $invitedBy,
                    'organization' => 'TechReserve',
                    'invitationToken' => $invitationToken,
                    'status' => 'pending',
                    'expiresAt' => $expiresAt->format('Y-m-d H:i:sP'),
                    'createdAt' => $now->format('Y-m-d H:i:sP'),
                    'acceptedAt' => null,
                ],
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
