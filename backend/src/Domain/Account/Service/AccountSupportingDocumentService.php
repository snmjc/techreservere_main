<?php

namespace App\Domain\Account\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class AccountSupportingDocumentService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly SignupSupportingDocumentStorageService $signupSupportingDocumentStorageService
    ) {
    }

    public function getSupportingDocumentByAccountIdentifier(int $accountIdentifier): array|false
    {
        return $this->connection->fetchAssociative(
            'SELECT account_identifier, signup_supporting_document_name, signup_supporting_document_mime_type,
                    signup_supporting_document_path, signup_supporting_document_size_bytes,
                    signup_supporting_document_uploaded_at, signup_supporting_document_verification_status
             FROM accounts
             WHERE account_identifier = :accountIdentifier
             LIMIT 1',
            ['accountIdentifier' => $accountIdentifier],
            ['accountIdentifier' => ParameterType::INTEGER]
        );
    }

    public function clearSupportingDocumentForAccount(int $accountIdentifier): void
    {
        $document = $this->getSupportingDocumentByAccountIdentifier($accountIdentifier);
        if ($document === false) {
            return;
        }

        $this->signupSupportingDocumentStorageService->delete((string)($document['signup_supporting_document_path'] ?? ''));

        $this->connection->executeStatement(
            'UPDATE accounts
             SET signup_supporting_document_name = NULL,
                 signup_supporting_document_mime_type = NULL,
                 signup_supporting_document_path = NULL,
                 signup_supporting_document_size_bytes = NULL,
                 signup_supporting_document_uploaded_at = NULL,
                 signup_supporting_document_verification_status = NULL
             WHERE account_identifier = :accountIdentifier',
            ['accountIdentifier' => $accountIdentifier],
            ['accountIdentifier' => ParameterType::INTEGER]
        );
    }

    public function deleteStoredDocumentByPath(?string $relativePath): void
    {
        $this->signupSupportingDocumentStorageService->delete($relativePath);
    }

    public function resolveAbsoluteFilePath(array $document): ?string
    {
        $relativePath = trim((string)($document['signup_supporting_document_path'] ?? ''));
        if ($relativePath === '') {
            return null;
        }

        $absolutePath = $this->signupSupportingDocumentStorageService->resolveAbsolutePath($relativePath);
        return is_file($absolutePath) ? $absolutePath : null;
    }
}
