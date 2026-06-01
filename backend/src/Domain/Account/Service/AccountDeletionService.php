<?php

namespace App\Domain\Account\Service;

use App\Domain\Authentication\Service\AuthenticationClerkService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class AccountDeletionService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AuthenticationClerkService $authenticationClerkService
    ) {
    }

    public function deleteAccount(array $account, int $accountIdentifier): int
    {
        $this->connection->beginTransaction();

        try {
            $clerkUserId = trim((string)($account['clerk_user_id'] ?? ''));

            $this->connection->executeStatement(
                'DELETE FROM invitations WHERE LOWER(email) = LOWER(:emailAddress)',
                ['emailAddress' => (string)$account['email_address']],
                ['emailAddress' => ParameterType::STRING]
            );

            $this->connection->executeStatement(
                'DELETE FROM staff_info WHERE account_identifier = :accountIdentifier',
                ['accountIdentifier' => $accountIdentifier],
                ['accountIdentifier' => ParameterType::INTEGER]
            );

            $deletedRows = $this->connection->executeStatement(
                'DELETE FROM accounts WHERE account_identifier = :accountIdentifier',
                ['accountIdentifier' => $accountIdentifier],
                ['accountIdentifier' => ParameterType::INTEGER]
            );

            $this->connection->commit();

            if ($clerkUserId !== '') {
                $this->authenticationClerkService->deleteUser($clerkUserId);
            }

            return $deletedRows;
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }
}
