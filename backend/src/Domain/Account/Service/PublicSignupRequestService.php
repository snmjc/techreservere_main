<?php

namespace App\Domain\Account\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class PublicSignupRequestService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AccountClerkProvisioningService $accountClerkProvisioningService,
        private readonly AccountConflictLookupService $accountConflictLookupService
    ) {
    }

    public function create(array $requestBody): array
    {
        $payload = $this->normalizeRequestBody($requestBody);
        $validationError = $this->validateRequest($payload);

        if ($validationError !== null) {
            return $this->error('ValidationError', $validationError, 422);
        }

        $roleLabel = strtolower($payload['role']) === 'faculty' ? 'Faculty' : 'Student';
        $existingEmailAccount = $this->accountConflictLookupService->findByEmail($payload['emailAddress']);

        if ($existingEmailAccount) {
            return $this->handleExistingEmailAccount($existingEmailAccount, $payload, $roleLabel);
        }

        $existingIdNumberAccount = $this->accountConflictLookupService->findByIdNumber($payload['idNumber']);
        if ($existingIdNumberAccount) {
            return $this->duplicateIdNumberError($existingIdNumberAccount);
        }

        return $this->createNewSignupRequest($payload, $roleLabel);
    }

    private function normalizeRequestBody(array $requestBody): array
    {
        $passwordText = (string)($requestBody['passwordText'] ?? $requestBody['password'] ?? '');

        return [
            'lastName' => trim($requestBody['lastName'] ?? ''),
            'firstName' => trim($requestBody['firstName'] ?? ''),
            'emailAddress' => strtolower(trim($requestBody['emailAddress'] ?? '')),
            'idNumber' => trim($requestBody['idNumber'] ?? ''),
            'role' => trim($requestBody['role'] ?? 'Student'),
            'department' => trim($requestBody['department'] ?? ($requestBody['role'] ?? 'Student')),
            'passwordText' => $passwordText,
            'confirmPasswordText' => (string)($requestBody['confirmPasswordText'] ?? $requestBody['confirmPassword'] ?? $passwordText),
            'acceptedPrivacy' => (bool)($requestBody['acceptedPrivacy'] ?? false),
            'supportingDocumentName' => trim((string)($requestBody['supportingDocumentName'] ?? '')),
            'supportingDocumentMimeType' => trim((string)($requestBody['supportingDocumentMimeType'] ?? '')),
            'supportingDocumentData' => trim((string)($requestBody['supportingDocumentData'] ?? '')),
        ];
    }

    private function validateRequest(array $payload): ?string
    {
        if (
            $payload['lastName'] === ''
            || $payload['firstName'] === ''
            || $payload['emailAddress'] === ''
            || $payload['idNumber'] === ''
            || $payload['department'] === ''
            || $payload['role'] === ''
            || $payload['passwordText'] === ''
        ) {
            return 'All signup fields are required.';
        }

        if (!$payload['acceptedPrivacy']) {
            return 'Data privacy confirmation is required.';
        }

        if (!preg_match('/^[A-Za-z][A-Za-z .\'-]*$/', $payload['firstName']) || !preg_match('/^[A-Za-z][A-Za-z .\'-]*$/', $payload['lastName'])) {
            return 'Names may only contain letters, spaces, periods, apostrophes, and hyphens.';
        }

        if (!filter_var($payload['emailAddress'], FILTER_VALIDATE_EMAIL) || !str_ends_with($payload['emailAddress'], '@fit.edu.ph')) {
            return 'Please use a valid @fit.edu.ph email address.';
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $payload['passwordText'])) {
            return 'Password must be at least 8 characters and include uppercase letters, lowercase letters, and numbers.';
        }

        if ($payload['passwordText'] !== $payload['confirmPasswordText']) {
            return 'Passwords do not match.';
        }

        return $this->validateSupportingDocument($payload);
    }

    private function validateSupportingDocument(array $payload): ?string
    {
        $roleLabel = strtolower($payload['role']) === 'faculty' ? 'Faculty' : 'Student';

        if ($roleLabel === 'Student' && $payload['supportingDocumentName'] === '') {
            return 'PDF proof is required for student signup requests.';
        }

        if (
            $roleLabel === 'Student'
            && !$this->isPdfSupportingDocument(
                $payload['supportingDocumentName'],
                $payload['supportingDocumentMimeType'],
                $payload['supportingDocumentData']
            )
        ) {
            return 'Student proof must be uploaded as a PDF file.';
        }

        if ($payload['supportingDocumentName'] !== '' && $payload['supportingDocumentData'] === '') {
            return 'Supporting file data is missing.';
        }

        if ($payload['supportingDocumentData'] !== '' && strlen($payload['supportingDocumentData']) > 7000000) {
            return 'Supporting file is too large. Please upload a file up to 5 MB.';
        }

        return null;
    }

    private function handleExistingEmailAccount(array $existingEmailAccount, array $payload, string $roleLabel): array
    {
        if ($this->isReusablePendingSignupRequest($existingEmailAccount, $payload['idNumber'])) {
            return $this->reusePendingSignupRequest($existingEmailAccount, $payload, $roleLabel);
        }

        return $this->error(
            'DuplicateAccount',
            $this->accountConflictLookupService->buildDuplicateAccountMessage('email', $existingEmailAccount),
            409,
            ['conflict' => $this->accountConflictLookupService->normalizeConflict($existingEmailAccount, 'email')]
        );
    }

    private function reusePendingSignupRequest(array $existingEmailAccount, array $payload, string $roleLabel): array
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        try {
            $clerkUserId = $this->accountClerkProvisioningService->ensureSignupUser(
                $payload['emailAddress'],
                $payload['firstName'],
                $payload['lastName'],
                $payload['passwordText'],
                $roleLabel,
                $payload['idNumber']
            );
        } catch (\Throwable $exception) {
            return $this->error(
                'ClerkSignupUserFailed',
                'Clerk could not create or update this signup account: ' . $exception->getMessage(),
                502
            );
        }

        $this->connection->executeStatement(
            'UPDATE accounts
             SET last_name = :lastName,
                 first_name = :firstName,
                 department = :department,
                 clerk_user_id = :clerkUserId,
                 password_hash = :passwordHash,
                 signup_supporting_document_name = :supportingDocumentName,
                 signup_supporting_document_mime_type = :supportingDocumentMimeType,
                 signup_supporting_document_data = :supportingDocumentData,
                 status = :status,
                 is_approved = :isApproved,
                 is_active = :isActive,
                 created_timestamp = :createdTimestamp,
                 updated_timestamp = :updatedTimestamp
             WHERE account_identifier = :accountIdentifier',
            $this->buildReusableSignupParameters($existingEmailAccount, $payload, $clerkUserId, $now),
            $this->buildReusableSignupTypes($payload)
        );

        return $this->success([
            'accountIdentifier' => (int)$existingEmailAccount['account_identifier'],
            'idNumber' => $payload['idNumber'],
            'lastName' => $payload['lastName'],
            'firstName' => $payload['firstName'],
            'emailAddress' => $payload['emailAddress'],
            'clerkUserId' => $clerkUserId,
            'roleDesignation' => 'ROLE_BORROWER',
            'roleLabel' => 'User: ' . $roleLabel,
            'accountType' => 'User',
            'accountStatus' => 'pending',
            'isApproved' => false,
            'registeredAt' => $now,
            'reusedPendingRequest' => true,
        ]);
    }

    private function createNewSignupRequest(array $payload, string $roleLabel): array
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        try {
            $clerkUserId = $this->accountClerkProvisioningService->ensureSignupUser(
                $payload['emailAddress'],
                $payload['firstName'],
                $payload['lastName'],
                $payload['passwordText'],
                $roleLabel,
                $payload['idNumber']
            );

            $this->connection->executeStatement(
                'INSERT INTO accounts
                    (last_name, first_name, email_address, role_designation, id_number, department,
                     contact_number, clerk_user_id, password_hash, status, is_approved, is_active,
                     signup_supporting_document_name, signup_supporting_document_mime_type, signup_supporting_document_data,
                     failed_login_attempts, created_timestamp, updated_timestamp)
                 VALUES
                    (:lastName, :firstName, :emailAddress, :roleDesignation, :idNumber, :department,
                     :contactNumber, :clerkUserId, :passwordHash, :status, :isApproved, :isActive,
                     :supportingDocumentName, :supportingDocumentMimeType, :supportingDocumentData,
                     :failedLoginAttempts, :createdTimestamp, :updatedTimestamp)',
                $this->buildNewSignupParameters($payload, $roleLabel, $clerkUserId, $now),
                $this->buildNewSignupTypes($payload)
            );

            return $this->success([
                'accountIdentifier' => (int)$this->connection->lastInsertId(),
                'idNumber' => $payload['idNumber'],
                'lastName' => $payload['lastName'],
                'firstName' => $payload['firstName'],
                'emailAddress' => $payload['emailAddress'],
                'clerkUserId' => $clerkUserId,
                'roleDesignation' => 'ROLE_BORROWER',
                'roleLabel' => 'User: ' . $roleLabel,
                'accountType' => 'User',
                'accountStatus' => 'pending',
                'isApproved' => false,
                'registeredAt' => $now,
            ], 201);
        } catch (\Throwable $exception) {
            return $this->error(
                'CreateSignupRequestFailed',
                'Failed to create signup request: ' . $exception->getMessage(),
                500
            );
        }
    }

    private function duplicateIdNumberError(array $existingIdNumberAccount): array
    {
        return $this->error(
            'DuplicateIdNumber',
            $this->accountConflictLookupService->buildDuplicateAccountMessage('ID number', $existingIdNumberAccount),
            409,
            ['conflict' => $this->accountConflictLookupService->normalizeConflict($existingIdNumberAccount, 'idNumber')]
        );
    }

    private function buildReusableSignupParameters(array $existingEmailAccount, array $payload, string $clerkUserId, string $now): array
    {
        return [
            'lastName' => $payload['lastName'],
            'firstName' => $payload['firstName'],
            'department' => strtolower($payload['role']) === 'faculty' ? 'Faculty' : 'Student',
            'clerkUserId' => $clerkUserId,
            'passwordHash' => password_hash($payload['passwordText'], PASSWORD_BCRYPT),
            'supportingDocumentName' => $payload['supportingDocumentName'] ?: null,
            'supportingDocumentMimeType' => $payload['supportingDocumentMimeType'] ?: null,
            'supportingDocumentData' => $payload['supportingDocumentData'] ?: null,
            'status' => 'pending',
            'isApproved' => false,
            'isActive' => true,
            'createdTimestamp' => $now,
            'updatedTimestamp' => $now,
            'accountIdentifier' => (int)$existingEmailAccount['account_identifier'],
        ];
    }

    private function buildReusableSignupTypes(array $payload): array
    {
        return [
            'lastName' => ParameterType::STRING,
            'firstName' => ParameterType::STRING,
            'department' => ParameterType::STRING,
            'clerkUserId' => ParameterType::STRING,
            'passwordHash' => ParameterType::STRING,
            'supportingDocumentName' => $payload['supportingDocumentName'] === '' ? ParameterType::NULL : ParameterType::STRING,
            'supportingDocumentMimeType' => $payload['supportingDocumentMimeType'] === '' ? ParameterType::NULL : ParameterType::STRING,
            'supportingDocumentData' => $payload['supportingDocumentData'] === '' ? ParameterType::NULL : ParameterType::STRING,
            'status' => ParameterType::STRING,
            'isApproved' => ParameterType::BOOLEAN,
            'isActive' => ParameterType::BOOLEAN,
            'createdTimestamp' => ParameterType::STRING,
            'updatedTimestamp' => ParameterType::STRING,
            'accountIdentifier' => ParameterType::INTEGER,
        ];
    }

    private function buildNewSignupParameters(array $payload, string $roleLabel, string $clerkUserId, string $now): array
    {
        return [
            'lastName' => $payload['lastName'],
            'firstName' => $payload['firstName'],
            'emailAddress' => $payload['emailAddress'],
            'roleDesignation' => 'ROLE_BORROWER',
            'idNumber' => $payload['idNumber'],
            'department' => $roleLabel,
            'contactNumber' => null,
            'clerkUserId' => $clerkUserId,
            'passwordHash' => password_hash($payload['passwordText'], PASSWORD_BCRYPT),
            'status' => 'pending',
            'isApproved' => false,
            'isActive' => true,
            'supportingDocumentName' => $payload['supportingDocumentName'] ?: null,
            'supportingDocumentMimeType' => $payload['supportingDocumentMimeType'] ?: null,
            'supportingDocumentData' => $payload['supportingDocumentData'] ?: null,
            'failedLoginAttempts' => 0,
            'createdTimestamp' => $now,
            'updatedTimestamp' => $now,
        ];
    }

    private function buildNewSignupTypes(array $payload): array
    {
        return [
            'lastName' => ParameterType::STRING,
            'firstName' => ParameterType::STRING,
            'emailAddress' => ParameterType::STRING,
            'roleDesignation' => ParameterType::STRING,
            'idNumber' => ParameterType::STRING,
            'department' => ParameterType::STRING,
            'contactNumber' => ParameterType::NULL,
            'clerkUserId' => ParameterType::STRING,
            'passwordHash' => ParameterType::STRING,
            'status' => ParameterType::STRING,
            'isApproved' => ParameterType::BOOLEAN,
            'isActive' => ParameterType::BOOLEAN,
            'supportingDocumentName' => $payload['supportingDocumentName'] === '' ? ParameterType::NULL : ParameterType::STRING,
            'supportingDocumentMimeType' => $payload['supportingDocumentMimeType'] === '' ? ParameterType::NULL : ParameterType::STRING,
            'supportingDocumentData' => $payload['supportingDocumentData'] === '' ? ParameterType::NULL : ParameterType::STRING,
            'failedLoginAttempts' => ParameterType::INTEGER,
            'createdTimestamp' => ParameterType::STRING,
            'updatedTimestamp' => ParameterType::STRING,
        ];
    }

    private function isPdfSupportingDocument(string $documentName, string $mimeType, string $documentData): bool
    {
        $lowerName = strtolower($documentName);
        $lowerMimeType = strtolower($mimeType);

        return str_ends_with($lowerName, '.pdf')
            && ($lowerMimeType === '' || $lowerMimeType === 'application/pdf')
            && str_starts_with($documentData, 'data:application/pdf;base64,');
    }

    private function isReusablePendingSignupRequest(array $account, string $idNumber): bool
    {
        $isApproved = $this->toDatabaseBoolean($account['is_approved'] ?? false);
        $status = strtolower((string)($account['status'] ?? ''));
        $existingIdNumber = trim((string)($account['id_number'] ?? ''));

        return $status === 'pending'
            && !$isApproved
            && $existingIdNumber === $idNumber;
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
        return [
            'success' => true,
            'status' => $status,
            'data' => $data,
        ];
    }

    private function error(string $errorCode, string $message, int $status, array $extra = []): array
    {
        return [
            'success' => false,
            'errorCode' => $errorCode,
            'message' => $message,
            'status' => $status,
            'extra' => $extra,
        ];
    }
}
