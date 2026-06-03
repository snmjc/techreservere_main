<?php

namespace App\Domain\Account\Service;

use App\Shared\Utils\AccountUsername;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class WishlistAdminAccountService
{
    private const DEFAULT_ADMIN_PASSWORD = 'admin123';

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

        $payload['lastName'] = $this->accountInputValidationService->normalizePersonName($payload['lastName']);
        $payload['firstName'] = $this->accountInputValidationService->normalizePersonName($payload['firstName']);

        $duplicateError = $this->findDuplicateError($payload);
        if ($duplicateError !== null) {
            return $duplicateError;
        }

        return $this->insertAdminAccount($payload);
    }

    private function normalizeRequestBody(array $requestBody): array
    {
        return [
            'lastName' => trim($requestBody['lastName'] ?? ''),
            'firstName' => trim($requestBody['firstName'] ?? ''),
            'emailAddress' => strtolower(trim($requestBody['emailAddress'] ?? '')),
            'username' => AccountUsername::fromEmail((string)($requestBody['emailAddress'] ?? '')),
            'idNumber' => trim($requestBody['idNumber'] ?? ''),
        ];
    }

    private function validatePayload(array $payload): ?string
    {
        if ($payload['lastName'] === '' || $payload['firstName'] === '' || $payload['emailAddress'] === '' || $payload['idNumber'] === '') {
            return 'Last name, first name, email, and ID number are required.';
        }

        if (!$this->accountInputValidationService->isValidPersonName($payload['lastName'])) {
            return 'Last name must have at least 2 letters and cannot contain numbers or symbols.';
        }

        if (!$this->accountInputValidationService->isValidPersonName($payload['firstName'])) {
            return 'First name must have at least 2 letters and cannot contain numbers or symbols.';
        }

        if (!filter_var($payload['emailAddress'], FILTER_VALIDATE_EMAIL)) {
            return 'Please provide a valid email address.';
        }

        if (!$this->accountInputValidationService->isInstitutionalAdminEmail($payload['emailAddress'])) {
            return 'Admin account must use a valid institutional email address.';
        }

        return null;
    }

    private function findDuplicateError(array $payload): ?array
    {
        $existingEmailAccount = $this->accountConflictLookupService->findByEmail($payload['emailAddress']);
        if ($existingEmailAccount) {
            return $this->duplicateError('DuplicateAccount', 'email', $existingEmailAccount, 'email');
        }

        $existingIdNumberAccount = $this->accountConflictLookupService->findByIdNumber($payload['idNumber']);
        if ($existingIdNumberAccount) {
            return $this->duplicateError('DuplicateIdNumber', 'ID number', $existingIdNumberAccount, 'idNumber');
        }

        return null;
    }

    private function insertAdminAccount(array $payload): array
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $defaultAdminPassword = (string)($_ENV['DEFAULT_ADMIN_PASSWORD'] ?? self::DEFAULT_ADMIN_PASSWORD);

        try {
            $this->connection->executeStatement(
                'INSERT INTO accounts
                    (last_name, first_name, email_address, username, role_designation, id_number, department,
                     contact_number, clerk_user_id, password_hash, status, is_approved, is_active,
                     failed_login_attempts, created_timestamp, updated_timestamp)
                 VALUES
                    (:lastName, :firstName, :emailAddress, :username, :roleDesignation, :idNumber, :department,
                     :contactNumber, :clerkUserId, :passwordHash, :status, :isApproved, :isActive,
                     :failedLoginAttempts, :createdTimestamp, :updatedTimestamp)',
                $this->buildInsertParameters($payload, $defaultAdminPassword, $now),
                $this->buildInsertTypes()
            );

            return $this->success([
                'accountIdentifier' => (int)$this->connection->lastInsertId(),
                'idNumber' => $payload['idNumber'],
                'lastName' => $payload['lastName'],
                'firstName' => $payload['firstName'],
                'emailAddress' => $payload['emailAddress'],
                'username' => $payload['username'],
                'roleDesignation' => 'ROLE_ADMIN',
                'roleLabel' => 'Admin',
                'accountType' => 'Admin',
                'accountStatus' => 'pending',
                'hasDefaultPassword' => true,
                'defaultPasswordLabel' => $defaultAdminPassword,
                'isApproved' => false,
                'registeredAt' => $now,
                'inviteSentAt' => null,
                'inviteExpiresAt' => null,
                'inviteAcceptedAt' => null,
            ], 201);
        } catch (\Throwable $exception) {
            return $this->error(
                'CreateAdminAccountFailed',
                'Failed to create admin account: ' . $exception->getMessage(),
                500
            );
        }
    }

    private function buildInsertParameters(array $payload, string $defaultAdminPassword, string $now): array
    {
        return [
            'lastName' => $payload['lastName'],
            'firstName' => $payload['firstName'],
            'emailAddress' => $payload['emailAddress'],
            'username' => $payload['username'],
            'roleDesignation' => 'ROLE_ADMIN',
            'idNumber' => $payload['idNumber'],
            'department' => 'Administration',
            'contactNumber' => null,
            'clerkUserId' => null,
            'passwordHash' => password_hash($defaultAdminPassword, PASSWORD_BCRYPT),
            'status' => 'pending',
            'isApproved' => false,
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
            'isApproved' => ParameterType::BOOLEAN,
            'isActive' => ParameterType::BOOLEAN,
            'failedLoginAttempts' => ParameterType::INTEGER,
            'createdTimestamp' => ParameterType::STRING,
            'updatedTimestamp' => ParameterType::STRING,
        ];
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
        return ['success' => true, 'status' => $status, 'data' => $data];
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
