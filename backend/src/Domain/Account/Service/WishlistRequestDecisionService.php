<?php

namespace App\Domain\Account\Service;

use App\Domain\Account\Repository\AccountRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class WishlistRequestDecisionService
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly Connection $connection,
        private readonly AdminSecurityConfirmationService $adminSecurityConfirmationService,
        private readonly AccountSupportingDocumentService $accountSupportingDocumentService
    ) {
    }

    public function reject(
        int $accountIdentifier,
        string $confirmedAdminEmail,
        int $authenticatedAdminId,
        string $confirmedAdminPassword
    ): array
    {
        $account = $this->accountRepository->find($accountIdentifier);
        if ($account === null) {
            return $this->error('UserNotFound', 'User not found.', 404);
        }

        $credentialError = $this->adminSecurityConfirmationService->validateAdminCredentials(
            $authenticatedAdminId,
            $confirmedAdminEmail,
            $confirmedAdminPassword,
            'denying'
        );
        if ($credentialError !== null) {
            return $this->error('SecurityConfirmationFailed', $credentialError, 422);
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
        string $confirmedAdminEmail,
        int $authenticatedAdminId,
        string $confirmedAdminPassword
    ): array {
        $account = $this->findAccountRequest($accountIdentifier);
        if (!$account) {
            return $this->error('UserNotFound', 'User not found.', 404);
        }

        $credentialError = $this->adminSecurityConfirmationService->validateAdminCredentials(
            $authenticatedAdminId,
            $confirmedAdminEmail,
            $confirmedAdminPassword,
            'deleting'
        );
        if ($credentialError !== null) {
            return $this->error('SecurityConfirmationFailed', $credentialError, 422);
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

    private function deleteRequestRows(int $accountIdentifier, string $emailAddress): array
    {
        $document = $this->accountSupportingDocumentService->getSupportingDocumentByAccountIdentifier($accountIdentifier);
        $relativePath = !empty($document['signup_supporting_document_path'])
            ? (string)$document['signup_supporting_document_path']
            : null;

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

        if ($relativePath !== null) {
            $this->accountSupportingDocumentService->deleteStoredDocumentByPath($relativePath);
        }

        return $this->success([
            'message' => 'Account request deleted successfully.',
            'accountIdentifier' => $accountIdentifier,
        ]);
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
