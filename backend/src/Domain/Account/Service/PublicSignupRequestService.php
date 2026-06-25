<?php

namespace App\Domain\Account\Service;

use App\Shared\Utils\AppClock;
use App\Shared\Utils\AccountUsername;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PublicSignupRequestService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AccountConflictLookupService $accountConflictLookupService,
        private readonly AccountInputValidationService $accountInputValidationService,
        private readonly SignupSupportingDocumentValidationService $signupSupportingDocumentValidationService,
        private readonly SignupSupportingDocumentStorageService $signupSupportingDocumentStorageService
    ) {
    }

    public function create(array $requestBody, ?UploadedFile $supportingDocumentFile = null): array
    {
        $payload = $this->normalizeRequestBody($requestBody);
        $validationError = $this->validateRequest($payload);

        if ($validationError !== null) {
            return $this->error('ValidationError', $validationError, 422);
        }

        $roleLabel = strtolower($payload['role']) === 'faculty' ? 'Faculty' : 'Student';
        $existingEmailAccount = $this->accountConflictLookupService->findByEmail($payload['emailAddress']);

        if ($existingEmailAccount) {
            return $this->handleExistingEmailAccount($existingEmailAccount, $payload, $roleLabel, $supportingDocumentFile);
        }

        $existingIdNumberAccount = $this->accountConflictLookupService->findByIdNumber($payload['idNumber']);
        if ($existingIdNumberAccount) {
            return $this->duplicateIdNumberError($existingIdNumberAccount);
        }

        $documentError = $this->signupSupportingDocumentValidationService->validateRequiredUpload($payload, $supportingDocumentFile);
        if ($documentError !== null) {
            return $this->error('ValidationError', $documentError, 422);
        }

        return $this->createNewSignupRequest($payload, $roleLabel, $supportingDocumentFile);
    }

    private function normalizeRequestBody(array $requestBody): array
    {
        $passwordText = (string)($requestBody['passwordText'] ?? $requestBody['password'] ?? '');

        return [
            'lastName' => trim($requestBody['lastName'] ?? ''),
            'firstName' => trim($requestBody['firstName'] ?? ''),
            'emailAddress' => strtolower(trim($requestBody['emailAddress'] ?? '')),
            'username' => AccountUsername::fromEmail((string)($requestBody['emailAddress'] ?? '')),
            'idNumber' => $this->accountInputValidationService->normalizeIdNumber((string)($requestBody['idNumber'] ?? '')),
            'role' => trim($requestBody['role'] ?? 'Student'),
            'department' => trim($requestBody['department'] ?? ($requestBody['role'] ?? 'Student')),
            'passwordText' => $passwordText,
            'confirmPasswordText' => (string)($requestBody['confirmPasswordText'] ?? $requestBody['confirmPassword'] ?? $passwordText),
            'acceptedPrivacy' => filter_var($requestBody['acceptedPrivacy'] ?? false, FILTER_VALIDATE_BOOL),
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

        if (!$this->accountInputValidationService->isValidPersonName($payload['firstName']) || !$this->accountInputValidationService->isValidPersonName($payload['lastName'])) {
            return 'Names may only contain letters and spaces.';
        }

        if (!$this->accountInputValidationService->isValidIdNumber($payload['idNumber'])) {
            return 'ID number must be exactly 9 digits.';
        }

        if (!$this->accountInputValidationService->isInstitutionalUserEmail($payload['emailAddress'])) {
            return 'Please use a valid @fit.edu.ph or @feutech.edu.ph email address.';
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $payload['passwordText'])) {
            return 'Password must be at least 8 characters and include uppercase letters, lowercase letters, and numbers.';
        }

        if ($payload['passwordText'] !== $payload['confirmPasswordText']) {
            return 'Passwords do not match.';
        }

        return null;
    }

    private function handleExistingEmailAccount(
        array $existingEmailAccount,
        array $payload,
        string $roleLabel,
        ?UploadedFile $supportingDocumentFile = null
    ): array
    {
        if ($this->isReusablePendingSignupRequest($existingEmailAccount, $payload['idNumber'])) {
            return $this->reusePendingSignupRequest($existingEmailAccount, $payload, $roleLabel, $supportingDocumentFile);
        }

        return $this->error(
            'DuplicateAccount',
            $this->accountConflictLookupService->buildDuplicateAccountMessage('email', $existingEmailAccount),
            409,
            ['conflict' => $this->accountConflictLookupService->normalizeConflict($existingEmailAccount, 'email')]
        );
    }

    private function reusePendingSignupRequest(
        array $existingEmailAccount,
        array $payload,
        string $roleLabel,
        ?UploadedFile $supportingDocumentFile = null
    ): array
    {
        $documentError = $this->signupSupportingDocumentValidationService->validateRequiredUpload($payload, $supportingDocumentFile);
        if ($documentError !== null) {
            return $this->error('ValidationError', $documentError, 422);
        }

        $now = AppClock::now()->format('Y-m-d H:i:s');
        $storedDocument = $this->storeSupportingDocumentIfPresent($payload, $supportingDocumentFile);
        if (($storedDocument['success'] ?? false) !== true) {
            return $storedDocument;
        }

        $existingFilePath = !empty($existingEmailAccount['signup_supporting_document_path'])
            ? (string)$existingEmailAccount['signup_supporting_document_path']
            : null;

        try {
            $this->connection->executeStatement(
                'UPDATE accounts
                 SET last_name = :lastName,
                     first_name = :firstName,
                     username = :username,
                     department = :department,
                     clerk_user_id = :clerkUserId,
                     password_hash = :passwordHash,
                     signup_supporting_document_name = :supportingDocumentName,
                     signup_supporting_document_mime_type = :supportingDocumentMimeType,
                     signup_supporting_document_path = :supportingDocumentPath,
                     signup_supporting_document_size_bytes = :supportingDocumentSizeBytes,
                     signup_supporting_document_uploaded_at = :supportingDocumentUploadedAt,
                     signup_supporting_document_verification_status = :supportingDocumentVerificationStatus,
                     status = :status,
                     is_verified = :isVerified,
                     verification_status = :verificationStatus,
                     invitation_status = :invitationStatus,
                     is_approved = :isApproved,
                     invited_at = :invitedAt,
                     approved_at = :approvedAt,
                     is_active = :isActive,
                     created_timestamp = :createdTimestamp,
                     updated_timestamp = :updatedTimestamp
                 WHERE account_identifier = :accountIdentifier',
                $this->buildReusableSignupParameters($existingEmailAccount, $payload, $now, $storedDocument['data']),
                $this->buildReusableSignupTypes($storedDocument['data'])
            );
        } catch (\Throwable $exception) {
            $this->deleteStoredDocument($storedDocument['data']['filePath'] ?? null);

            return $this->error(
                'CreateSignupRequestFailed',
                'Failed to update signup request: ' . $exception->getMessage(),
                500
            );
        }

        if (($storedDocument['data']['filePath'] ?? null) !== null) {
            $this->deleteStoredDocument($existingFilePath);
        }

        return $this->success([
            'accountIdentifier' => (int)$existingEmailAccount['account_identifier'],
            'idNumber' => $payload['idNumber'],
            'lastName' => $payload['lastName'],
            'firstName' => $payload['firstName'],
            'emailAddress' => $payload['emailAddress'],
            'clerkUserId' => null,
            'roleDesignation' => 'ROLE_BORROWER',
            'roleLabel' => 'User: ' . $roleLabel,
            'accountType' => 'User',
            'accountStatus' => 'unverified',
            'status' => 'pending',
            'isVerified' => false,
            'verificationStatus' => 'unverified',
            'invitationStatus' => 'not_sent',
            'isApproved' => false,
            'registeredAt' => $now,
            'reusedPendingRequest' => true,
        ]);
    }

    private function createNewSignupRequest(array $payload, string $roleLabel, ?UploadedFile $supportingDocumentFile): array
    {
        $now = AppClock::now()->format('Y-m-d H:i:s');
        $storedDocument = $this->storeSupportingDocumentIfPresent($payload, $supportingDocumentFile);
        if (($storedDocument['success'] ?? false) !== true) {
            return $storedDocument;
        }

        try {
            $this->connection->executeStatement(
                'INSERT INTO accounts
                    (last_name, first_name, email_address, username, role_designation, id_number, department,
                     contact_number, clerk_user_id, password_hash, status, is_approved, is_active,
                     is_verified, verification_status, invitation_status, invited_at, approved_at,
                     signup_supporting_document_name, signup_supporting_document_mime_type, signup_supporting_document_path,
                     signup_supporting_document_size_bytes, signup_supporting_document_uploaded_at, signup_supporting_document_verification_status,
                     failed_login_attempts, created_timestamp, updated_timestamp)
                VALUES
                    (:lastName, :firstName, :emailAddress, :username, :roleDesignation, :idNumber, :department,
                     :contactNumber, :clerkUserId, :passwordHash, :status, :isApproved, :isActive,
                     :isVerified, :verificationStatus, :invitationStatus, :invitedAt, :approvedAt,
                     :supportingDocumentName, :supportingDocumentMimeType, :supportingDocumentPath,
                     :supportingDocumentSizeBytes, :supportingDocumentUploadedAt, :supportingDocumentVerificationStatus,
                     :failedLoginAttempts, :createdTimestamp, :updatedTimestamp)',
                $this->buildNewSignupParameters($payload, $roleLabel, $now, $storedDocument['data']),
                $this->buildNewSignupTypes($storedDocument['data'])
            );

            return $this->success([
                'accountIdentifier' => (int)$this->connection->lastInsertId(),
                'idNumber' => $payload['idNumber'],
                'lastName' => $payload['lastName'],
                'firstName' => $payload['firstName'],
                'emailAddress' => $payload['emailAddress'],
                'username' => $payload['username'],
                'clerkUserId' => null,
                'roleDesignation' => 'ROLE_BORROWER',
                'roleLabel' => 'User: ' . $roleLabel,
                'accountType' => 'User',
                'accountStatus' => 'unverified',
                'status' => 'pending',
                'isVerified' => false,
                'verificationStatus' => 'unverified',
                'invitationStatus' => 'not_sent',
                'isApproved' => false,
                'registeredAt' => $now,
            ], 201);
        } catch (\Throwable $exception) {
            $this->deleteStoredDocument($storedDocument['data']['filePath'] ?? null);

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

    private function buildReusableSignupParameters(
        array $existingEmailAccount,
        array $payload,
        string $now,
        array $storedDocument
    ): array
    {
        return [
            'lastName' => $payload['lastName'],
            'firstName' => $payload['firstName'],
            'username' => $payload['username'],
            'department' => strtolower($payload['role']) === 'faculty' ? 'Faculty' : 'Student',
            'clerkUserId' => null,
            'passwordHash' => password_hash($payload['passwordText'], PASSWORD_BCRYPT),
            'supportingDocumentName' => $storedDocument['fileName'] ?? null,
            'supportingDocumentMimeType' => $storedDocument['fileType'] ?? null,
            'supportingDocumentPath' => $storedDocument['filePath'] ?? null,
            'supportingDocumentSizeBytes' => $storedDocument['fileSize'] ?? null,
            'supportingDocumentUploadedAt' => $storedDocument['uploadDate'] ?? null,
            'supportingDocumentVerificationStatus' => $storedDocument['verificationStatus'] ?? null,
            'status' => 'pending',
            'isVerified' => false,
            'verificationStatus' => 'unverified',
            'invitationStatus' => 'not_sent',
            'isApproved' => false,
            'invitedAt' => null,
            'approvedAt' => null,
            'isActive' => true,
            'createdTimestamp' => $now,
            'updatedTimestamp' => $now,
            'accountIdentifier' => (int)$existingEmailAccount['account_identifier'],
        ];
    }

    private function buildReusableSignupTypes(array $storedDocument): array
    {
        return [
            'lastName' => ParameterType::STRING,
            'firstName' => ParameterType::STRING,
            'username' => ParameterType::STRING,
            'department' => ParameterType::STRING,
            'clerkUserId' => ParameterType::NULL,
            'passwordHash' => ParameterType::STRING,
            'supportingDocumentName' => empty($storedDocument['fileName']) ? ParameterType::NULL : ParameterType::STRING,
            'supportingDocumentMimeType' => empty($storedDocument['fileType']) ? ParameterType::NULL : ParameterType::STRING,
            'supportingDocumentPath' => empty($storedDocument['filePath']) ? ParameterType::NULL : ParameterType::STRING,
            'supportingDocumentSizeBytes' => !isset($storedDocument['fileSize']) ? ParameterType::NULL : ParameterType::INTEGER,
            'supportingDocumentUploadedAt' => empty($storedDocument['uploadDate']) ? ParameterType::NULL : ParameterType::STRING,
            'supportingDocumentVerificationStatus' => empty($storedDocument['verificationStatus']) ? ParameterType::NULL : ParameterType::STRING,
            'status' => ParameterType::STRING,
            'isVerified' => ParameterType::BOOLEAN,
            'verificationStatus' => ParameterType::STRING,
            'invitationStatus' => ParameterType::STRING,
            'isApproved' => ParameterType::BOOLEAN,
            'invitedAt' => ParameterType::NULL,
            'approvedAt' => ParameterType::NULL,
            'isActive' => ParameterType::BOOLEAN,
            'createdTimestamp' => ParameterType::STRING,
            'updatedTimestamp' => ParameterType::STRING,
            'accountIdentifier' => ParameterType::INTEGER,
        ];
    }

    private function buildNewSignupParameters(array $payload, string $roleLabel, string $now, array $storedDocument): array
    {
        return [
            'lastName' => $payload['lastName'],
            'firstName' => $payload['firstName'],
            'emailAddress' => $payload['emailAddress'],
            'username' => $payload['username'],
            'roleDesignation' => 'ROLE_BORROWER',
            'idNumber' => $payload['idNumber'],
            'department' => $roleLabel,
            'contactNumber' => null,
            'clerkUserId' => null,
            'passwordHash' => password_hash($payload['passwordText'], PASSWORD_BCRYPT),
            'status' => 'pending',
            'isVerified' => false,
            'verificationStatus' => 'unverified',
            'invitationStatus' => 'not_sent',
            'isApproved' => false,
            'invitedAt' => null,
            'approvedAt' => null,
            'isActive' => true,
            'supportingDocumentName' => $storedDocument['fileName'] ?? null,
            'supportingDocumentMimeType' => $storedDocument['fileType'] ?? null,
            'supportingDocumentPath' => $storedDocument['filePath'] ?? null,
            'supportingDocumentSizeBytes' => $storedDocument['fileSize'] ?? null,
            'supportingDocumentUploadedAt' => $storedDocument['uploadDate'] ?? null,
            'supportingDocumentVerificationStatus' => $storedDocument['verificationStatus'] ?? null,
            'failedLoginAttempts' => 0,
            'createdTimestamp' => $now,
            'updatedTimestamp' => $now,
        ];
    }

    private function buildNewSignupTypes(array $storedDocument): array
    {
        return [
            'lastName' => ParameterType::STRING,
            'firstName' => ParameterType::STRING,
            'emailAddress' => ParameterType::STRING,
            'username' => ParameterType::STRING,
            'roleDesignation' => ParameterType::STRING,
            'idNumber' => ParameterType::STRING,
            'department' => ParameterType::STRING,
            'contactNumber' => ParameterType::NULL,
            'clerkUserId' => ParameterType::NULL,
            'passwordHash' => ParameterType::STRING,
            'status' => ParameterType::STRING,
            'isVerified' => ParameterType::BOOLEAN,
            'verificationStatus' => ParameterType::STRING,
            'invitationStatus' => ParameterType::STRING,
            'isApproved' => ParameterType::BOOLEAN,
            'invitedAt' => ParameterType::NULL,
            'approvedAt' => ParameterType::NULL,
            'isActive' => ParameterType::BOOLEAN,
            'supportingDocumentName' => empty($storedDocument['fileName']) ? ParameterType::NULL : ParameterType::STRING,
            'supportingDocumentMimeType' => empty($storedDocument['fileType']) ? ParameterType::NULL : ParameterType::STRING,
            'supportingDocumentPath' => empty($storedDocument['filePath']) ? ParameterType::NULL : ParameterType::STRING,
            'supportingDocumentSizeBytes' => !isset($storedDocument['fileSize']) ? ParameterType::NULL : ParameterType::INTEGER,
            'supportingDocumentUploadedAt' => empty($storedDocument['uploadDate']) ? ParameterType::NULL : ParameterType::STRING,
            'supportingDocumentVerificationStatus' => empty($storedDocument['verificationStatus']) ? ParameterType::NULL : ParameterType::STRING,
            'failedLoginAttempts' => ParameterType::INTEGER,
            'createdTimestamp' => ParameterType::STRING,
            'updatedTimestamp' => ParameterType::STRING,
        ];
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

    private function storeSupportingDocumentIfPresent(array $payload, ?UploadedFile $supportingDocumentFile): array
    {
        if ($supportingDocumentFile === null) {
            return $this->success([], 200);
        }

        try {
            return $this->success($this->signupSupportingDocumentStorageService->store(
                $supportingDocumentFile
            ));
        } catch (\Throwable $exception) {
            return $this->error(
                'SupportingDocumentUploadFailed',
                'Unable to upload the supporting document: ' . $exception->getMessage(),
                500
            );
        }
    }

    private function deleteStoredDocument(?string $relativePath): void
    {
        $this->signupSupportingDocumentStorageService->delete($relativePath);
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
