<?php

namespace App\Domain\Account\Service;

use App\Shared\Utils\AccountUsername;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class WishlistEmployeeAccountService
{
    private const STAFF_ROLE_LABEL = 'Maintenance Staff';

    public function __construct(
        private readonly Connection $connection,
        private readonly AccountConflictLookupService $accountConflictLookupService,
        private readonly StaffInfoWriterService $staffInfoWriterService,
        private readonly AccountInputValidationService $accountInputValidationService
    ) {
    }

    public function create(array $requestBody): array
    {
        $payload = $this->normalizeRequestBody($requestBody);
        $validationError = $this->validateEmployeePayload($payload);

        if ($validationError !== null) {
            return $this->error('ValidationError', $validationError, 422);
        }

        $duplicateError = $this->findDuplicateError($payload);
        if ($duplicateError !== null) {
            return $duplicateError;
        }

        return $this->insertEmployeeAccount($payload);
    }

    private function normalizeRequestBody(array $requestBody): array
    {
        $idNumber = trim($requestBody['idNumber'] ?? $requestBody['workIdNumber'] ?? $requestBody['work_id_number'] ?? '');
        $emailAddress = strtolower(trim($requestBody['emailAddress'] ?? ''));

        if ($emailAddress === '') {
            $emailAddress = $this->buildStaffEmailAddress($idNumber);
        }

        return [
            'lastName' => trim($requestBody['lastName'] ?? ''),
            'firstName' => trim($requestBody['firstName'] ?? ''),
            'emailAddress' => $emailAddress,
            'username' => AccountUsername::fromEmail($emailAddress),
            'phone' => $this->normalizeStaffPhone(
                preg_replace('/\D+/', '', trim($requestBody['phone'] ?? $requestBody['phoneNumber'] ?? $requestBody['phone_number'] ?? $requestBody['contactNumber'] ?? ''))
            ),
            'idNumber' => $idNumber,
            'role' => self::STAFF_ROLE_LABEL,
        ];
    }

    private function validateEmployeePayload(array $payload): ?string
    {
        if ($payload['lastName'] === '' || $payload['firstName'] === '' || $payload['phone'] === '' || $payload['idNumber'] === '') {
            return 'Last name, first name, phone number, and Work ID number are required.';
        }

        if (!$this->isValidStaffName($payload['firstName']) || !$this->isValidStaffName($payload['lastName'])) {
            return 'First name and last name must have at least 2 letters and cannot contain numbers or symbols.';
        }

        if (!preg_match('/^9\d{9}$/', $payload['phone'])) {
            return 'Phone number must be exactly 10 digits and begin with 9.';
        }

        if (!$this->accountInputValidationService->isValidIdNumber($payload['idNumber'])) {
            return 'Work ID number must be exactly 10 digits.';
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

        $existingPhoneAccount = $this->accountConflictLookupService->findStaffByPhone($payload['phone']);
        if ($existingPhoneAccount) {
            return $this->duplicateError('DuplicatePhoneNumber', 'phone number', $existingPhoneAccount, 'phone');
        }

        return null;
    }

    private function insertEmployeeAccount(array $payload): array
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        try {
            $accountIdentifier = $this->connection->transactional(function (): int {
                $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

                $this->connection->executeStatement(
                    'INSERT INTO accounts
                        (last_name, first_name, email_address, username, role_designation, id_number, department,
                         contact_number, clerk_user_id, password_hash, status, is_approved, is_active,
                         failed_login_attempts, created_timestamp, updated_timestamp)
                     VALUES
                        (:lastName, :firstName, :emailAddress, :username, :roleDesignation, :idNumber, :department,
                         :contactNumber, :clerkUserId, :passwordHash, :status, :isApproved, :isActive,
                         :failedLoginAttempts, :createdTimestamp, :updatedTimestamp)',
                    $this->buildInsertParameters($payload, $now),
                    $this->buildInsertTypes()
                );

                $accountIdentifier = (int)$this->connection->lastInsertId();
                if ($accountIdentifier <= 0) {
                    throw new \RuntimeException('Unable to determine the new staff account identifier.');
                }

                $this->staffInfoWriterService->upsertStaffInfo(
                    $accountIdentifier,
                    $payload['idNumber'],
                    $payload['firstName'],
                    $payload['lastName'],
                    $payload['phone'],
                    $payload['role'],
                    null
                );

                return $accountIdentifier;
            });

            return $this->success([
                'accountIdentifier' => $accountIdentifier,
                'idNumber' => $payload['idNumber'],
                'lastName' => $payload['lastName'],
                'firstName' => $payload['firstName'],
                'emailAddress' => $payload['emailAddress'],
                'username' => $payload['username'],
                'contactNumber' => $payload['phone'],
                'roleDesignation' => 'ROLE_STAFF',
                'roleLabel' => $payload['role'],
                'accountType' => 'Employee',
                'accountStatus' => 'approved',
                'isApproved' => true,
                'loginEnabled' => false,
                'assignmentOnly' => true,
                'registeredAt' => $now,
                'inviteSentAt' => null,
                'inviteExpiresAt' => null,
                'inviteAcceptedAt' => null,
            ], 201);
        } catch (\Throwable $exception) {
            return $this->error(
                'CreateEmployeeAccountFailed',
                'Failed to create employee account: ' . $exception->getMessage(),
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
            'roleDesignation' => 'ROLE_STAFF',
            'idNumber' => $payload['idNumber'],
            'department' => $payload['role'],
            'contactNumber' => $payload['phone'],
            'clerkUserId' => null,
            'passwordHash' => null,
            'status' => 'approved',
            'isApproved' => true,
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
            'contactNumber' => ParameterType::STRING,
            'clerkUserId' => ParameterType::NULL,
            'passwordHash' => ParameterType::NULL,
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

    private function isValidStaffName(string $name): bool
    {
        $normalizedName = trim($name);
        $letterCount = preg_match_all('/[A-Za-z]/', $normalizedName);

        return $letterCount >= 2 && preg_match('/^[A-Za-z ]+$/', $normalizedName) === 1;
    }

    private function normalizeStaffPhone(string $phone): string
    {
        if (preg_match('/^09\d{9}$/', $phone) === 1) {
            return substr($phone, 1);
        }

        return $phone;
    }

    private function buildStaffEmailAddress(string $idNumber): string
    {
        $normalizedIdNumber = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $idNumber) ?: bin2hex(random_bytes(4)));
        return 'staff-' . $normalizedIdNumber . '@techreserve.feu.edu.ph';
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
