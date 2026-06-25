<?php

namespace App\Domain\Account\Service;

use App\Shared\Utils\AppClock;
use App\Shared\Utils\AccountUsername;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class WishlistAdminAccountService
{
    private const DEFAULT_ADMIN_PASSWORD = 'admin123';

    public function __construct(
        private readonly Connection $connection,
        private readonly AccountConflictLookupService $accountConflictLookupService,
        private readonly AccountInputValidationService $accountInputValidationService,
        private readonly AdminSecurityConfirmationService $adminSecurityConfirmationService
    ) {
    }

    public function create(array $requestBody, int $authenticatedAdminId): array
    {
        $payload = $this->normalizeRequestBody($requestBody);
        $validationError = $this->validatePayload($payload);

        if ($validationError !== null) {
            return $this->error('ValidationError', $validationError, 422);
        }

        $securityError = $this->adminSecurityConfirmationService->validateAdminEmail(
            $authenticatedAdminId,
            $payload['confirmedAdminEmail'],
            'creating the admin'
        );
        if ($securityError !== null) {
            return $this->error('SecurityConfirmationFailed', $securityError, 422);
        }

        $payload['lastName'] = $this->accountInputValidationService->normalizePersonName($payload['lastName']);
        $payload['firstName'] = $this->accountInputValidationService->normalizePersonName($payload['firstName']);
        $payload['idNumber'] = $this->accountInputValidationService->normalizeIdNumber($payload['idNumber']);

        $duplicateError = $this->findDuplicateError($payload);
        if ($duplicateError !== null) {
            return $duplicateError;
        }

        return $this->createAdminAccount($payload);
    }

    private function normalizeRequestBody(array $requestBody): array
    {
        return [
            'idNumber' => trim((string)($requestBody['idNumber'] ?? '')),
            'lastName' => trim($requestBody['lastName'] ?? ''),
            'firstName' => trim($requestBody['firstName'] ?? ''),
            'emailAddress' => strtolower(trim($requestBody['emailAddress'] ?? '')),
            'username' => AccountUsername::fromEmail((string)($requestBody['emailAddress'] ?? '')),
            'roleDesignation' => strtoupper(trim((string)($requestBody['roleDesignation'] ?? 'ROLE_ADMIN'))),
            'confirmedAdminEmail' => strtolower(trim((string)($requestBody['confirmedAdminEmail'] ?? ''))),
        ];
    }

    private function validatePayload(array $payload): ?string
    {
        if ($payload['idNumber'] === '' || $payload['lastName'] === '' || $payload['firstName'] === '' || $payload['emailAddress'] === '') {
            return 'ID number, last name, first name, and email are required.';
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
            return 'Admin account must use a valid @feutech.edu.ph email address.';
        }

        if (!$this->accountInputValidationService->isValidIdNumber($payload['idNumber'])) {
            return 'ID number must be exactly 9 digits.';
        }

        if ($payload['roleDesignation'] !== 'ROLE_ADMIN') {
            return 'Only ROLE_ADMIN can be created from this modal.';
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

    private function createAdminAccount(array $payload): array
    {
        $now = AppClock::now()->format('Y-m-d H:i:s');

        try {
            $this->connection->executeStatement(
                'INSERT INTO accounts
                    (last_name, first_name, email_address, username, role_designation, id_number, department,
                     contact_number, clerk_user_id, password_hash, status, is_verified, verification_status,
                     invitation_status, is_approved, is_active, invited_at, approved_at,
                     failed_login_attempts, created_timestamp, updated_timestamp)
                 VALUES
                    (:lastName, :firstName, :emailAddress, :username, :roleDesignation, :idNumber, :department,
                     :contactNumber, :clerkUserId, :passwordHash, :status, :isVerified, :verificationStatus,
                     :invitationStatus, :isApproved, :isActive, :invitedAt, :approvedAt,
                     :failedLoginAttempts, :createdTimestamp, :updatedTimestamp)',
                $this->buildInsertParameters($payload, $now),
                $this->buildInsertTypes()
            );

            $createdAccountIdentifier = (int)$this->connection->lastInsertId();

            return $this->success([
                'message' => 'Admin request account created successfully.',
                'createdAccountIdentifier' => $createdAccountIdentifier,
                'defaultPassword' => self::DEFAULT_ADMIN_PASSWORD,
                'account' => [
                    'accountIdentifier' => $createdAccountIdentifier,
                    'idNumber' => $payload['idNumber'],
                    'lastName' => $payload['lastName'],
                    'firstName' => $payload['firstName'],
                    'emailAddress' => $payload['emailAddress'],
                    'username' => $payload['username'],
                    'roleDesignation' => 'ROLE_ADMIN',
                    'roleLabel' => 'Admin',
                    'accountType' => 'Admin',
                    'accountStatus' => 'unverified',
                    'status' => 'pending',
                    'isVerified' => false,
                    'verificationStatus' => 'unverified',
                    'invitationStatus' => 'not_sent',
                    'isApproved' => false,
                    'registeredAt' => $now,
                    'createdTimestamp' => $now,
                ],
            ], 201);
        } catch (\Throwable $exception) {
            return $this->error(
                'CreateAdminAccountFailed',
                'Failed to create admin account: ' . $exception->getMessage(),
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
            'roleDesignation' => 'ROLE_ADMIN',
            'idNumber' => $payload['idNumber'],
            'department' => 'Administration',
            'contactNumber' => null,
            'clerkUserId' => null,
            'passwordHash' => password_hash(self::DEFAULT_ADMIN_PASSWORD, PASSWORD_BCRYPT, ['cost' => 4]),
            'status' => 'pending',
            'isVerified' => false,
            'verificationStatus' => 'unverified',
            'invitationStatus' => 'not_sent',
            'isApproved' => false,
            'isActive' => true,
            'invitedAt' => null,
            'approvedAt' => null,
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
            'isActive' => ParameterType::BOOLEAN,
            'invitedAt' => ParameterType::NULL,
            'approvedAt' => ParameterType::NULL,
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
