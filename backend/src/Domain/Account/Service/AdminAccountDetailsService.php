<?php

namespace App\Domain\Account\Service;

use App\Shared\Utils\DatabaseBoolean;
use Doctrine\DBAL\Connection;

class AdminAccountDetailsService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AccountLifecyclePolicyService $accountLifecyclePolicyService,
        private readonly AccountReadService $accountReadService,
        private readonly AccountSettingsValidationService $accountSettingsValidationService,
        private readonly StaffInfoWriterService $staffInfoWriterService
    ) {
    }

    public function updateDetails(int $accountIdentifier, array $requestBody): array
    {
        $existingAccount = $this->accountReadService->getAccountStateById($accountIdentifier);
        if (!$existingAccount) {
            return $this->error('AccountNotFound', 'Account not found.', 404);
        }

        $currentMappedAccount = $this->accountReadService->getMappedAccountById($accountIdentifier);
        $accountStatus = $this->resolveStatus($existingAccount);

        if (!$this->accountLifecyclePolicyService->canUpdateAccount($accountStatus)) {
            return $this->error(
                'AccountActionNotAllowed',
                'Only active accounts can be updated. Disabled accounts are read-only until reactivated, and pending accounts must be accepted before updates are allowed.',
                403,
                ['actionRules' => $this->accountLifecyclePolicyService->buildActionPermissions($accountStatus, DatabaseBoolean::toBool($existingAccount['is_approved'] ?? false))]
            );
        }

        $profile = $this->normalizeProfileRequest($requestBody);
        $validationError = $this->accountSettingsValidationService->validateEditableAccountSettings(
            $profile['firstName'],
            $profile['lastName'],
            $profile['contactNumber'],
            $profile['profilePhotoData'],
            $profile['profilePhotoName']
        );

        if ($validationError !== null) {
            return $this->error('ValidationError', $validationError, 422);
        }

        if (($currentMappedAccount['accountType'] ?? '') === 'Employee' && $this->accountReadService->hasDuplicateStaffPhone($profile['contactNumber'], $accountIdentifier)) {
            return $this->error('DuplicatePhoneNumber', 'This phone number is already used by another staff account.', 409);
        }

        $updatedRows = $this->connection->update(
            'accounts',
            $this->buildUpdateFields($profile),
            ['account_identifier' => $accountIdentifier]
        );

        if (($currentMappedAccount['accountType'] ?? '') === 'Employee') {
            $this->syncStaffInfo($accountIdentifier, $currentMappedAccount, $existingAccount, $profile);
        }

        if ($updatedRows === 0) {
            return $this->error('AccountNotFound', 'Account not found.', 404);
        }

        return $this->success([
            'message' => 'Changes saved.',
            'account' => $this->accountReadService->getMappedAccountById($accountIdentifier),
        ]);
    }

    private function normalizeProfileRequest(array $requestBody): array
    {
        $contactNumber = preg_replace('/\D+/', '', (string)($requestBody['contactNumber'] ?? '')) ?? '';
        if (str_starts_with($contactNumber, '09')) {
            $contactNumber = substr($contactNumber, 1);
        }

        return [
            'lastName' => $this->accountSettingsValidationService->normalizePersonName((string)($requestBody['lastName'] ?? '')),
            'firstName' => $this->accountSettingsValidationService->normalizePersonName((string)($requestBody['firstName'] ?? '')),
            'contactNumber' => $contactNumber,
            'profilePhotoName' => trim((string)($requestBody['profilePhotoName'] ?? '')),
            'profilePhotoData' => array_key_exists('profilePhotoData', $requestBody)
                ? trim((string)$requestBody['profilePhotoData'])
                : null,
        ];
    }

    private function buildUpdateFields(array $profile): array
    {
        $updateFields = [
            'last_name' => $profile['lastName'],
            'first_name' => $profile['firstName'],
            'contact_number' => $profile['contactNumber'],
            'updated_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        if ($profile['profilePhotoData'] !== null && $profile['profilePhotoData'] !== '') {
            $updateFields['profile_photo_data'] = $profile['profilePhotoData'];
        }

        return $updateFields;
    }

    private function syncStaffInfo(int $accountIdentifier, array $currentMappedAccount, array $existingAccount, array $profile): void
    {
        $this->staffInfoWriterService->upsertStaffInfo(
            $accountIdentifier,
            (string)($currentMappedAccount['rawIdNumber'] ?? $currentMappedAccount['idNumber'] ?? $existingAccount['id_number'] ?? ''),
            $profile['firstName'],
            $profile['lastName'],
            $profile['contactNumber'],
            (string)($currentMappedAccount['roleLabel'] ?? $existingAccount['department'] ?? 'Maintenance Staff'),
            ($profile['profilePhotoData'] !== null && $profile['profilePhotoData'] !== '') ? $profile['profilePhotoData'] : (string)($currentMappedAccount['profilePhotoData'] ?? '')
        );
    }

    private function resolveStatus(array $account): string
    {
        return $this->accountLifecyclePolicyService->resolveAccountStatus(
            DatabaseBoolean::toBool($account['is_active'] ?? false),
            (string)($account['status'] ?? ''),
            DatabaseBoolean::toBool($account['is_approved'] ?? false)
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
