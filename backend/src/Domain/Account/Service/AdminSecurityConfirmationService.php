<?php

namespace App\Domain\Account\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class AdminSecurityConfirmationService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AccountSettingsValidationService $accountSettingsValidationService
    ) {
    }

    public function validateAdminEmail(int $authenticatedAdminId, string $confirmedAdminEmail, string $actionName): ?string
    {
        $normalizedConfirmedAdminEmail = $this->accountSettingsValidationService->normalizeEmailForConfirmation($confirmedAdminEmail);

        if ($normalizedConfirmedAdminEmail === '' || $authenticatedAdminId <= 0) {
            return sprintf('Please type the responsible admin email before %s the account.', $actionName);
        }

        $confirmedAdmin = $this->fetchActivePrivilegedAccount($authenticatedAdminId, false);
        if (
            !$confirmedAdmin
            || $normalizedConfirmedAdminEmail !== $this->normalizeEmailAddress((string)($confirmedAdmin['email_address'] ?? ''))
        ) {
            return sprintf('Please type your exact admin email before %s this account.', $actionName);
        }

        return null;
    }

    public function validateAdminCredentials(int $authenticatedAdminId, string $confirmedAdminEmail, string $confirmedAdminPassword, string $actionName): ?string
    {
        $emailError = $this->validateAdminEmail($authenticatedAdminId, $confirmedAdminEmail, $actionName);
        if ($emailError !== null) {
            return $emailError;
        }

        if (trim($confirmedAdminPassword) === '') {
            return sprintf('Please type the responsible admin password before %s the account.', $actionName);
        }

        $confirmedAdmin = $this->fetchActivePrivilegedAccount($authenticatedAdminId, true);
        $passwordHash = (string)($confirmedAdmin['password_hash'] ?? '');
        if ($passwordHash === '' || !password_verify($confirmedAdminPassword, $passwordHash)) {
            return sprintf('Please type your exact admin password before %s this account.', $actionName);
        }

        return null;
    }

    private function fetchActivePrivilegedAccount(int $accountIdentifier, bool $includePasswordHash): array|false
    {
        $fields = $includePasswordHash ? 'email_address, password_hash' : 'email_address';

        return $this->connection->fetchAssociative(
            "SELECT {$fields}
             FROM accounts
             WHERE account_identifier = :accountIdentifier
               AND role_designation IN ('ROLE_ADMIN', 'ADMIN', 'ROLE_DEVELOPER', 'DEVELOPER')
               AND COALESCE(is_active, TRUE) = TRUE
             LIMIT 1",
            ['accountIdentifier' => $accountIdentifier],
            ['accountIdentifier' => ParameterType::INTEGER]
        );
    }

    private function normalizeEmailAddress(string $emailAddress): string
    {
        return $this->accountSettingsValidationService->normalizeEmailForConfirmation($emailAddress);
    }
}
