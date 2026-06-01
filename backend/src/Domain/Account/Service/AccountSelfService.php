<?php

namespace App\Domain\Account\Service;

use App\Domain\Authentication\Service\PasswordPolicyService;
use Doctrine\DBAL\Connection;

class AccountSelfService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AccountReadService $accountReadService,
        private readonly AccountSettingsValidationService $accountSettingsValidationService,
        private readonly PasswordPolicyService $passwordPolicyService
    ) {
    }

    public function getSettings(int $accountIdentifier): array
    {
        if ($accountIdentifier <= 0) {
            return $this->error('AuthenticationRequired', 'Unable to identify the signed-in account.', 401);
        }

        $account = $this->accountReadService->getSettingsAccountById($accountIdentifier);
        if (!$account) {
            return $this->error('AccountNotFound', 'Account not found.', 404);
        }

        return $this->success(['account' => $account]);
    }

    public function updateSettings(int $accountIdentifier, mixed $requestBody): array
    {
        if ($accountIdentifier <= 0) {
            return $this->error('AuthenticationRequired', 'Unable to identify the signed-in account.', 401);
        }

        if (!is_array($requestBody)) {
            return $this->error('ValidationError', 'Invalid request body.', 422);
        }

        $settings = $this->normalizeSettingsRequest($requestBody);
        $validationError = $this->accountSettingsValidationService->validateEditableAccountSettings(
            $settings['firstName'],
            $settings['lastName'],
            $settings['contactNumber'],
            $settings['profilePhotoData']
        );

        if ($validationError !== null) {
            return $this->error('ValidationError', $validationError, 422);
        }

        $updatedRows = $this->connection->update(
            'accounts',
            $this->buildAccountSettingsUpdateFields($settings),
            ['account_identifier' => $accountIdentifier]
        );

        if ($updatedRows === 0 && !$this->accountReadService->getSettingsAccountById($accountIdentifier)) {
            return $this->error('AccountNotFound', 'Account not found.', 404);
        }

        return $this->success([
            'message' => 'Account settings updated.',
            'account' => $this->accountReadService->getSettingsAccountById($accountIdentifier),
        ]);
    }

    public function updatePassword(int $accountIdentifier, mixed $requestBody): array
    {
        if ($accountIdentifier <= 0) {
            return $this->error('AuthenticationRequired', 'Unable to identify the signed-in account.', 401);
        }

        if (!is_array($requestBody)) {
            return $this->error('ValidationError', 'Invalid request body.', 422);
        }

        $passwords = [
            'currentPassword' => (string)($requestBody['currentPassword'] ?? ''),
            'newPassword' => (string)($requestBody['newPassword'] ?? ''),
            'confirmPassword' => (string)($requestBody['confirmPassword'] ?? ''),
        ];

        $validationError = $this->validateLocalPasswordUpdate($passwords);
        if ($validationError !== null) {
            return $this->error('ValidationError', $validationError, 422);
        }

        $account = $this->connection->fetchAssociative(
            'SELECT account_identifier, password_hash FROM accounts WHERE account_identifier = :accountIdentifier',
            ['accountIdentifier' => $accountIdentifier]
        );

        if (!$account) {
            return $this->error('AccountNotFound', 'Account not found.', 404);
        }

        $passwordHash = (string)($account['password_hash'] ?? '');
        $passwordError = $this->validateStoredPassword($passwordHash, $passwords);
        if ($passwordError !== null) {
            return $passwordError;
        }

        $this->updatePasswordHash($accountIdentifier, $passwords['newPassword']);

        return $this->success(['message' => 'Password updated.']);
    }

    public function syncPasswordFromClerk(int $accountIdentifier, mixed $requestBody): array
    {
        if ($accountIdentifier <= 0) {
            return $this->error('AuthenticationRequired', 'Unable to identify the signed-in account.', 401);
        }

        if (!is_array($requestBody)) {
            return $this->error('ValidationError', 'Invalid request body.', 422);
        }

        $newPassword = (string)($requestBody['newPassword'] ?? '');
        if (!$this->passwordPolicyService->isStrongPassword($newPassword)) {
            return $this->error('ValidationError', 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.', 422);
        }

        $updatedRows = $this->updatePasswordHash($accountIdentifier, $newPassword);
        if ($updatedRows === 0) {
            return $this->error('AccountNotFound', 'Account not found.', 404);
        }

        return $this->success(['message' => 'Password synced from Clerk.']);
    }

    private function normalizeSettingsRequest(array $requestBody): array
    {
        $contactNumber = preg_replace('/\D+/', '', (string)($requestBody['contactNumber'] ?? '')) ?? '';
        if (str_starts_with($contactNumber, '09')) {
            $contactNumber = substr($contactNumber, 1);
        }

        return [
            'lastName' => $this->accountSettingsValidationService->normalizePersonName((string)($requestBody['lastName'] ?? '')),
            'firstName' => $this->accountSettingsValidationService->normalizePersonName((string)($requestBody['firstName'] ?? '')),
            'contactNumber' => $contactNumber,
            'profilePhotoData' => array_key_exists('profilePhotoData', $requestBody)
                ? trim((string)$requestBody['profilePhotoData'])
                : null,
        ];
    }

    private function buildAccountSettingsUpdateFields(array $settings): array
    {
        $updateFields = [
            'last_name' => $settings['lastName'],
            'first_name' => $settings['firstName'],
            'contact_number' => $settings['contactNumber'],
            'updated_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        if ($settings['profilePhotoData'] !== null && $settings['profilePhotoData'] !== '') {
            $updateFields['profile_photo_data'] = $settings['profilePhotoData'];
        }

        return $updateFields;
    }

    private function validateLocalPasswordUpdate(array $passwords): ?string
    {
        if ($passwords['currentPassword'] === '' || $passwords['newPassword'] === '' || $passwords['confirmPassword'] === '') {
            return 'Current password, new password, and confirmation are required.';
        }

        if ($passwords['currentPassword'] === $passwords['newPassword']) {
            return 'New password must be different from the current password.';
        }

        if ($passwords['newPassword'] !== $passwords['confirmPassword']) {
            return 'New password and confirmation password do not match.';
        }

        if (!$this->passwordPolicyService->isStrongPassword($passwords['newPassword'])) {
            return 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.';
        }

        return null;
    }

    private function validateStoredPassword(string $passwordHash, array $passwords): ?array
    {
        if ($passwordHash === '') {
            return $this->error('PasswordUpdateUnavailable', 'This account does not have a local password to update.', 422);
        }

        if (!password_verify($passwords['currentPassword'], $passwordHash)) {
            return $this->error('InvalidPassword', 'Current password is incorrect.', 422);
        }

        if (password_verify($passwords['newPassword'], $passwordHash)) {
            return $this->error('ValidationError', 'New password must be different from the current password.', 422);
        }

        return null;
    }

    private function updatePasswordHash(int $accountIdentifier, string $newPassword): int
    {
        return $this->connection->update(
            'accounts',
            [
                'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT),
                'updated_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ],
            ['account_identifier' => $accountIdentifier]
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
