<?php

namespace App\Domain\Account\Service;

use App\Domain\Account\Repository\AccountRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class WishlistRequestDecisionService
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly Connection $connection
    ) {
    }

    public function reject(
        int $accountIdentifier,
        string $confirmEmail,
        int $authenticatedAdminId,
        string $confirmedAdminPassword
    ): array
    {
        $account = $this->accountRepository->find($accountIdentifier);
        if ($account === null) {
            return $this->error('UserNotFound', 'User not found.', 404);
        }

        if ($this->normalizeEmailForConfirmation($confirmEmail) === '' || $this->normalizeEmailForConfirmation($confirmEmail) !== $this->normalizeEmailForConfirmation($account->getEmailAddress())) {
            return $this->error(
                'DenyConfirmationFailed',
                'Please type the exact email address to deny this request.',
                422
            );
        }

        $adminPasswordError = $this->validateResponsibleAdminPassword($authenticatedAdminId, $confirmedAdminPassword, 'denying');
        if ($adminPasswordError !== null) {
            return $adminPasswordError;
        }

        $account->setStatus('rejected');
        $account->setIsApproved(false);
        $this->accountRepository->persistAccount($account);

        return $this->success([
            'message' => 'User rejected successfully.',
            'account' => [
                'accountIdentifier' => $account->getAccountIdentifier(),
                'status' => $account->getStatus(),
                'isApproved' => $account->getIsApproved(),
            ],
        ]);
    }

    public function deleteRequest(
        int $accountIdentifier,
        string $confirmEmail,
        int $authenticatedAdminId,
        string $confirmedAdminPassword
    ): array {
        $account = $this->findAccountRequest($accountIdentifier);
        if (!$account) {
            return $this->error('UserNotFound', 'User not found.', 404);
        }

        if ($this->normalizeEmailForConfirmation($confirmEmail) === '' || $this->normalizeEmailForConfirmation($confirmEmail) !== $this->normalizeEmailForConfirmation((string)$account['email_address'])) {
            return $this->error(
                'DeleteConfirmationFailed',
                'Please type the exact email address to delete this request.',
                422
            );
        }

        $adminPasswordError = $this->validateResponsibleAdminPassword($authenticatedAdminId, $confirmedAdminPassword, 'deleting');
        if ($adminPasswordError !== null) {
            return $adminPasswordError;
        }

        if ($this->toDatabaseBoolean($account['is_approved'] ?? false) || strtolower((string)$account['status']) === 'approved') {
            return $this->error(
                'DeleteRequestNotAllowed',
                'Approved accounts must be deleted from Manage Accounts.',
                403
            );
        }

        return $this->deleteRequestRows($accountIdentifier, (string)$account['email_address']);
    }

    private function findAccountRequest(int $accountIdentifier): array|false
    {
        return $this->connection->fetchAssociative(
            "SELECT account_identifier, email_address, status, is_approved
             FROM accounts
             WHERE account_identifier = :accountIdentifier
             LIMIT 1",
            ['accountIdentifier' => $accountIdentifier],
            ['accountIdentifier' => ParameterType::INTEGER]
        );
    }

    private function validateResponsibleAdminPassword(int $authenticatedAdminId, string $confirmedAdminPassword, string $actionName): ?array
    {
        if ($authenticatedAdminId <= 0) {
            return $this->error(
                'SecurityConfirmationFailed',
                sprintf('Please sign in as an admin before %s this request.', $actionName),
                422
            );
        }

        if (trim($confirmedAdminPassword) === '') {
            return $this->error(
                'SecurityConfirmationFailed',
                sprintf('Please type the responsible admin password before %s this request.', $actionName),
                422
            );
        }

        $confirmedAdmin = $this->connection->fetchAssociative(
            "SELECT password_hash
             FROM accounts
             WHERE account_identifier = :accountIdentifier
               AND role_designation IN ('ROLE_ADMIN', 'ADMIN')
               AND COALESCE(is_active, TRUE) = TRUE
             LIMIT 1",
            ['accountIdentifier' => $authenticatedAdminId],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        $passwordHash = (string)($confirmedAdmin['password_hash'] ?? '');
        if (!$confirmedAdmin || $passwordHash === '' || !password_verify($confirmedAdminPassword, $passwordHash)) {
            return $this->error(
                'SecurityConfirmationFailed',
                sprintf('Please type your exact admin password before %s this request.', $actionName),
                422
            );
        }

        return null;
    }

    private function deleteRequestRows(int $accountIdentifier, string $emailAddress): array
    {
        $this->connection->beginTransaction();
        try {
            $this->connection->executeStatement(
                'DELETE FROM invitations WHERE LOWER(email) = LOWER(:emailAddress)',
                ['emailAddress' => $emailAddress],
                ['emailAddress' => ParameterType::STRING]
            );

            $this->connection->executeStatement(
                'DELETE FROM staff_info WHERE account_identifier = :accountIdentifier',
                ['accountIdentifier' => $accountIdentifier],
                ['accountIdentifier' => ParameterType::INTEGER]
            );

            $this->connection->executeStatement(
                'DELETE FROM accounts WHERE account_identifier = :accountIdentifier',
                ['accountIdentifier' => $accountIdentifier],
                ['accountIdentifier' => ParameterType::INTEGER]
            );

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            return $this->error(
                'DeleteAccountRequestFailed',
                'Unable to delete account request: ' . $exception->getMessage(),
                500
            );
        }

        return $this->success([
            'message' => 'Account request deleted successfully.',
            'accountIdentifier' => $accountIdentifier,
        ]);
    }

    private function normalizeEmailForConfirmation(string $emailAddress): string
    {
        $normalizedEmailAddress = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\s]+/u', '', $emailAddress) ?? $emailAddress;
        return strtolower(trim($normalizedEmailAddress));
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

    private function success(array $data, int $status = 200): array
    {
        return ['success' => true, 'status' => $status, 'data' => $data];
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
