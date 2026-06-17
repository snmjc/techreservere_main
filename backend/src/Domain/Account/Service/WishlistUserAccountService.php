<?php

namespace App\Domain\Account\Service;

use App\Shared\Utils\AccountUsername;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class WishlistUserAccountService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AccountConflictLookupService $accountConflictLookupService,
        private readonly AccountInputValidationService $accountInputValidationService
    ) {
    }

    public function create(array $requestBody): array
    {
        $payload = $this->normalizeRequestBody($requestBody);
        $validationError = $this->validatePayload($payload);

        if ($validationError !== null) {
            return $this->error('ValidationError', $validationError, 422);
        }

        $existingEmailAccount = $this->accountConflictLookupService->findByEmail($payload['emailAddress']);
        if ($existingEmailAccount && $this->canReuseExistingEmailAccount($existingEmailAccount)) {
            return $this->updateExistingUserAccount($existingEmailAccount, $payload);
        }

        $duplicateError = $this->findDuplicateError($payload, $existingEmailAccount);
        if ($duplicateError !== null) {
            return $duplicateError;
        }

        return $this->insertUserAccount($payload);
    }

    private function normalizeRequestBody(array $requestBody): array
    {
        $role = trim($requestBody['role'] ?? 'Student');
        $roleDesignation = strtoupper(trim((string)($requestBody['roleDesignation'] ?? 'ROLE_BORROWER')));

        return [
            'lastName' => trim($requestBody['lastName'] ?? ''),
            'firstName' => trim($requestBody['firstName'] ?? ''),
            'emailAddress' => strtolower(trim($requestBody['emailAddress'] ?? '')),
            'username' => AccountUsername::fromEmail((string)($requestBody['emailAddress'] ?? '')),
            'idNumber' => trim($requestBody['idNumber'] ?? ''),
            'roleDesignation' => $roleDesignation,
            'role' => $role,
            'passwordText' => (string)($requestBody['passwordText'] ?? $requestBody['password'] ?? ''),
            'roleLabel' => strtolower($role) === 'faculty' ? 'Faculty' : 'Student',
        ];
    }

    private function validatePayload(array $payload): ?string
    {
        if (
            $payload['lastName'] === ''
            || $payload['firstName'] === ''
            || $payload['emailAddress'] === ''
            || $payload['idNumber'] === ''
            || $payload['role'] === ''
            || $payload['passwordText'] === ''
        ) {
            return 'Last name, first name, email, ID number, role, and password are required.';
        }

        if (!filter_var($payload['emailAddress'], FILTER_VALIDATE_EMAIL)) {
            return 'Please provide a valid email address.';
        }

        if (!$this->accountInputValidationService->isInstitutionalUserEmail($payload['emailAddress'])) {
            return 'User account must use a valid @fit.edu.ph or @feutech.edu.ph email address.';
        }

        if (!$this->accountInputValidationService->isValidIdNumber($payload['idNumber'])) {
            return 'ID number must be exactly 10 digits.';
        }

        if ($payload['roleDesignation'] !== 'ROLE_BORROWER') {
            return 'Only ROLE_BORROWER can be created from the user tab.';
        }

        return null;
    }

    private function findDuplicateError(array $payload, ?array $existingEmailAccount = null): ?array
    {
        if ($existingEmailAccount) {
            return $this->duplicateError('DuplicateAccount', 'email', $existingEmailAccount, 'email');
        }

        $existingIdNumberAccount = $this->accountConflictLookupService->findByIdNumber($payload['idNumber']);
        if ($existingIdNumberAccount) {
            return $this->duplicateError('DuplicateIdNumber', 'ID number', $existingIdNumberAccount, 'idNumber');
        }

        return null;
    }

    private function canReuseExistingEmailAccount(array $account): bool
    {
        $status = strtolower(trim((string)($account['status'] ?? 'pending')));
        $isApproved = $this->toDatabaseBoolean($account['is_approved'] ?? false);

        return !$isApproved && !in_array($status, ['approved', 'rejected', 'denied', 'disabled'], true);
    }

    private function updateExistingUserAccount(array $existingEmailAccount, array $payload): array
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $accountIdentifier = (int)($existingEmailAccount['account_identifier'] ?? 0);

        try {
            $this->connection->executeStatement(
                'UPDATE accounts
                 SET last_name = :lastName,
                     first_name = :firstName,
                     username = :username,
                     role_designation = :roleDesignation,
                     id_number = :idNumber,
                     department = :department,
                     clerk_user_id = NULL,
                     password_hash = :passwordHash,
                     status = :status,
                     is_verified = :isVerified,
                     verification_status = :verificationStatus,
                     invitation_status = :invitationStatus,
                     is_approved = :isApproved,
                     invited_at = NULL,
                     approved_at = NULL,
                     is_active = :isActive,
                     updated_timestamp = :updatedTimestamp
                 WHERE account_identifier = :accountIdentifier',
                $this->buildReusableParameters($payload, $now, $accountIdentifier),
                $this->buildReusableTypes()
            );

            return $this->success($this->buildUserAccountPayload($payload, $accountIdentifier, $now));
        } catch (\Throwable $exception) {
            return $this->error(
                'UpdateUserAccountFailed',
                'Failed to update the existing user account request: ' . $exception->getMessage(),
                500
            );
        }
    }

    private function insertUserAccount(array $payload): array
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        try {
            $this->connection->executeStatement(
                'INSERT INTO accounts
                    (last_name, first_name, email_address, username, role_designation, id_number, department,
                     contact_number, clerk_user_id, password_hash, status, is_verified, verification_status,
                     invitation_status, is_approved, invited_at, approved_at, is_active,
                     failed_login_attempts, created_timestamp, updated_timestamp)
                 VALUES
                    (:lastName, :firstName, :emailAddress, :username, :roleDesignation, :idNumber, :department,
                     :contactNumber, :clerkUserId, :passwordHash, :status, :isVerified, :verificationStatus,
                     :invitationStatus, :isApproved, :invitedAt, :approvedAt, :isActive,
                     :failedLoginAttempts, :createdTimestamp, :updatedTimestamp)',
                $this->buildInsertParameters($payload, $now),
                $this->buildInsertTypes()
            );

            return $this->success(
                $this->buildUserAccountPayload($payload, (int)$this->connection->lastInsertId(), $now),
                201
            );
        } catch (\Throwable $exception) {
            return $this->error(
                'CreateUserAccountFailed',
                'Failed to create user account: ' . $exception->getMessage(),
                500
            );
        }
    }

    private function buildInsertParameters(array $payload, string $now): array
    {
        return [
            'lastName' => $payload['lastName'],
            'firstName' => $payload['firstName'],
            'emailAddress' => $payload['emailAddress'],
            'username' => $payload['username'],
            'roleDesignation' => $payload['roleDesignation'],
            'idNumber' => $payload['idNumber'],
            'department' => $payload['roleLabel'],
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
            'failedLoginAttempts' => 0,
            'createdTimestamp' => $now,
            'updatedTimestamp' => $now,
        ];
    }

    private function buildInsertTypes(): array
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
            'failedLoginAttempts' => ParameterType::INTEGER,
            'createdTimestamp' => ParameterType::STRING,
            'updatedTimestamp' => ParameterType::STRING,
        ];
    }

    private function buildReusableParameters(array $payload, string $now, int $accountIdentifier): array
    {
        return [
            'lastName' => $payload['lastName'],
            'firstName' => $payload['firstName'],
            'username' => $payload['username'],
            'roleDesignation' => $payload['roleDesignation'],
            'idNumber' => $payload['idNumber'],
            'department' => $payload['roleLabel'],
            'passwordHash' => password_hash($payload['passwordText'], PASSWORD_BCRYPT),
            'status' => 'pending',
            'isVerified' => false,
            'verificationStatus' => 'unverified',
            'invitationStatus' => 'not_sent',
            'isApproved' => false,
            'isActive' => true,
            'updatedTimestamp' => $now,
            'accountIdentifier' => $accountIdentifier,
        ];
    }

    private function buildReusableTypes(): array
    {
        return [
            'lastName' => ParameterType::STRING,
            'firstName' => ParameterType::STRING,
            'username' => ParameterType::STRING,
            'roleDesignation' => ParameterType::STRING,
            'idNumber' => ParameterType::STRING,
            'department' => ParameterType::STRING,
            'passwordHash' => ParameterType::STRING,
            'status' => ParameterType::STRING,
            'isVerified' => ParameterType::BOOLEAN,
            'verificationStatus' => ParameterType::STRING,
            'invitationStatus' => ParameterType::STRING,
            'isApproved' => ParameterType::BOOLEAN,
            'isActive' => ParameterType::BOOLEAN,
            'updatedTimestamp' => ParameterType::STRING,
            'accountIdentifier' => ParameterType::INTEGER,
        ];
    }

    private function buildUserAccountPayload(array $payload, int $accountIdentifier, string $now): array
    {
        return [
            'accountIdentifier' => $accountIdentifier,
            'idNumber' => $payload['idNumber'],
            'lastName' => $payload['lastName'],
            'firstName' => $payload['firstName'],
            'emailAddress' => $payload['emailAddress'],
            'username' => $payload['username'],
            'roleDesignation' => $payload['roleDesignation'],
            'roleLabel' => 'User: ' . $payload['roleLabel'],
            'accountType' => 'User',
            'accountStatus' => 'unverified',
            'status' => 'pending',
            'isVerified' => false,
            'verificationStatus' => 'unverified',
            'invitationStatus' => 'not_sent',
            'isApproved' => false,
            'registeredAt' => $now,
            'inviteSentAt' => null,
            'inviteExpiresAt' => null,
            'inviteAcceptedAt' => null,
        ];
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

    private function duplicateError(string $errorCode, string $label, array $account, string $conflictType): array
    {
        return $this->error(
            $errorCode,
            $this->accountConflictLookupService->buildDuplicateAccountMessage($label, $account),
            409,
            ['conflict' => $this->accountConflictLookupService->normalizeConflict($account, $conflictType)]
        );
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
