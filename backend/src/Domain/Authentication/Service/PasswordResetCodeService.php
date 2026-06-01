<?php

namespace App\Domain\Authentication\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class PasswordResetCodeService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AuthenticationClerkService $authenticationClerkService,
        private readonly PasswordResetEmailService $passwordResetEmailService,
        private readonly PasswordPolicyService $passwordPolicyService
    ) {
    }

    public function requestReset(string $emailAddress): array
    {
        $account = $this->connection->fetchAssociative(
            'SELECT account_identifier, first_name, last_name, email_address
             FROM accounts
             WHERE LOWER(email_address) = LOWER(:emailAddress)
             LIMIT 1',
            ['emailAddress' => $emailAddress],
            ['emailAddress' => ParameterType::STRING]
        );

        if (!$account) {
            return $this->error('AccountNotFound', 'No TechReserve account was found for this email address.', 404);
        }

        $clerkUser = $this->authenticationClerkService->findUserByEmail($emailAddress);
        if ($clerkUser === null) {
            return $this->error('ClerkAccountNotFound', 'No Clerk account was found for this email address.', 422);
        }

        $code = $this->storeResetCode($emailAddress, (string)$clerkUser['id']);
        if (!$this->passwordResetEmailService->sendResetCode($account, $emailAddress, $code)) {
            return $this->error('EmailSendFailed', 'Unable to send the reset code email. Please check the mailer configuration.', 503);
        }

        return $this->success(['message' => 'Password reset code sent.']);
    }

    public function confirmReset(string $emailAddress, string $code, string $newPassword, string $confirmPassword): array
    {
        if ($newPassword !== $confirmPassword) {
            return $this->error('ValidationError', 'New password and confirmation password do not match.', 422);
        }

        if (!$this->passwordPolicyService->isStrongPassword($newPassword)) {
            return $this->error('ValidationError', 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.', 422);
        }

        $reset = $this->fetchResetCode($emailAddress);
        if (!$this->isUsableResetCode($reset)) {
            return $this->error('InvalidResetCode', 'Invalid or expired reset code.', 422);
        }

        if (!password_verify($code, (string)$reset['code_hash'])) {
            $this->incrementAttempts($emailAddress);
            return $this->error('InvalidResetCode', 'Invalid or expired reset code.', 422);
        }

        $clerkUserId = (string)$reset['clerk_user_id'];
        if (!$this->authenticationClerkService->updatePassword($clerkUserId, $newPassword)) {
            return $this->error('ClerkPasswordUpdateFailed', 'Unable to update the Clerk password for this account.', 502);
        }

        $updatedRows = $this->updateAccountPassword($emailAddress, $clerkUserId, $newPassword);
        if ($updatedRows === 0) {
            return $this->error('AccountNotFound', 'Account not found.', 404);
        }

        $this->deleteResetCode($emailAddress);

        return $this->success(['message' => 'Password reset successfully.']);
    }

    private function storeResetCode(string $emailAddress, string $clerkUserId): string
    {
        $this->ensurePasswordResetTable();
        $code = (string)random_int(100000, 999999);
        $now = new \DateTimeImmutable();

        $this->deleteResetCode($emailAddress);
        $this->connection->executeStatement(
            'INSERT INTO password_reset_codes
                (email_address, clerk_user_id, code_hash, attempts, expires_at, created_timestamp)
             VALUES
                (:emailAddress, :clerkUserId, :codeHash, 0, :expiresAt, :createdTimestamp)',
            [
                'emailAddress' => $emailAddress,
                'clerkUserId' => $clerkUserId,
                'codeHash' => password_hash($code, PASSWORD_BCRYPT),
                'expiresAt' => $now->modify('+15 minutes')->format('Y-m-d H:i:s'),
                'createdTimestamp' => $now->format('Y-m-d H:i:s'),
            ],
            [
                'emailAddress' => ParameterType::STRING,
                'clerkUserId' => ParameterType::STRING,
                'codeHash' => ParameterType::STRING,
                'expiresAt' => ParameterType::STRING,
                'createdTimestamp' => ParameterType::STRING,
            ]
        );

        return $code;
    }

    private function fetchResetCode(string $emailAddress): array|false
    {
        $this->ensurePasswordResetTable();
        return $this->connection->fetchAssociative(
            'SELECT email_address, clerk_user_id, code_hash, attempts, expires_at
             FROM password_reset_codes
             WHERE LOWER(email_address) = LOWER(:emailAddress)
             LIMIT 1',
            ['emailAddress' => $emailAddress],
            ['emailAddress' => ParameterType::STRING]
        );
    }

    private function isUsableResetCode(array|false $reset): bool
    {
        if (!$reset || (int)$reset['attempts'] >= 5) {
            return false;
        }

        return new \DateTimeImmutable((string)$reset['expires_at']) >= new \DateTimeImmutable();
    }

    private function incrementAttempts(string $emailAddress): void
    {
        $this->connection->executeStatement(
            'UPDATE password_reset_codes SET attempts = attempts + 1 WHERE LOWER(email_address) = LOWER(:emailAddress)',
            ['emailAddress' => $emailAddress],
            ['emailAddress' => ParameterType::STRING]
        );
    }

    private function updateAccountPassword(string $emailAddress, string $clerkUserId, string $newPassword): int
    {
        return $this->connection->executeStatement(
            'UPDATE accounts
             SET password_hash = :passwordHash,
                 clerk_user_id = COALESCE(NULLIF(clerk_user_id, \'\'), :clerkUserId),
                 updated_timestamp = :updatedTimestamp
             WHERE LOWER(email_address) = LOWER(:emailAddress)',
            [
                'passwordHash' => password_hash($newPassword, PASSWORD_BCRYPT),
                'clerkUserId' => $clerkUserId,
                'updatedTimestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'emailAddress' => $emailAddress,
            ],
            [
                'passwordHash' => ParameterType::STRING,
                'clerkUserId' => ParameterType::STRING,
                'updatedTimestamp' => ParameterType::STRING,
                'emailAddress' => ParameterType::STRING,
            ]
        );
    }

    private function deleteResetCode(string $emailAddress): void
    {
        $this->connection->executeStatement(
            'DELETE FROM password_reset_codes WHERE LOWER(email_address) = LOWER(:emailAddress)',
            ['emailAddress' => $emailAddress],
            ['emailAddress' => ParameterType::STRING]
        );
    }

    private function ensurePasswordResetTable(): void
    {
        $this->connection->executeStatement('CREATE TABLE IF NOT EXISTS password_reset_codes (
            email_address VARCHAR(100) PRIMARY KEY,
            clerk_user_id VARCHAR(255) NOT NULL,
            code_hash VARCHAR(255) NOT NULL,
            attempts INT NOT NULL DEFAULT 0,
            expires_at TIMESTAMP NOT NULL,
            created_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }

    private function success(array $data): array
    {
        return ['success' => true, 'data' => $data];
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
