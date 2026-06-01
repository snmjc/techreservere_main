<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\DTO\AccountUpdateRequestDTO;
use App\Domain\Account\Service\AccountDeletionService;
use App\Domain\Account\Service\AccountLifecyclePolicyService;
use App\Domain\Account\Service\AccountProfileService;
use App\Domain\Account\Service\AccountReadService;
use App\Domain\Account\Service\AccountSettingsValidationService;
use App\Domain\Account\Service\AccountUpdateService;
use App\Domain\Account\Service\AdminSecurityConfirmationService;
use App\Domain\Account\Service\AuthenticatedAccountResolver;
use App\Domain\Account\Service\StaffInfoWriterService;
use App\Domain\Authentication\Service\PasswordPolicyService;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\DatabaseBoolean;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/accounts')]
class AccountController extends AbstractController
{
    use JsonResponseTrait;

    private AccountProfileService $accountProfileService;
    private AccountLifecyclePolicyService $accountLifecyclePolicyService;
    private AccountReadService $accountReadService;
    private AccountSettingsValidationService $accountSettingsValidationService;
    private AccountUpdateService $accountUpdateService;
    private AccountDeletionService $accountDeletionService;
    private AdminSecurityConfirmationService $adminSecurityConfirmationService;
    private AuthenticatedAccountResolver $authenticatedAccountResolver;
    private StaffInfoWriterService $staffInfoWriterService;
    private Connection $connection;
    private PasswordPolicyService $passwordPolicyService;

    public function __construct(
        AccountProfileService $accountProfileService,
        AccountLifecyclePolicyService $accountLifecyclePolicyService,
        AccountReadService $accountReadService,
        AccountSettingsValidationService $accountSettingsValidationService,
        AccountUpdateService $accountUpdateService,
        AccountDeletionService $accountDeletionService,
        AdminSecurityConfirmationService $adminSecurityConfirmationService,
        AuthenticatedAccountResolver $authenticatedAccountResolver,
        StaffInfoWriterService $staffInfoWriterService,
        Connection $connection,
        PasswordPolicyService $passwordPolicyService
    ) {
        $this->accountProfileService = $accountProfileService;
        $this->accountLifecyclePolicyService = $accountLifecyclePolicyService;
        $this->accountReadService = $accountReadService;
        $this->accountSettingsValidationService = $accountSettingsValidationService;
        $this->accountUpdateService = $accountUpdateService;
        $this->accountDeletionService = $accountDeletionService;
        $this->adminSecurityConfirmationService = $adminSecurityConfirmationService;
        $this->authenticatedAccountResolver = $authenticatedAccountResolver;
        $this->staffInfoWriterService = $staffInfoWriterService;
        $this->connection = $connection;
        $this->passwordPolicyService = $passwordPolicyService;
    }

    #[Route('/me', name: 'account_get_my_profile', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function getMyProfile(Request $request): JsonResponse
    {
        $authenticatedIdentity = $request->attributes->get('authenticatedIdentity');
        $emailAddress = $authenticatedIdentity['emailAddress'] ?? '';

        $profileDTO = $this->accountProfileService->getAccountProfileByEmail($emailAddress);

        return $this->createSuccessResponse($profileDTO->toResponseArray());
    }

    #[Route('/me/settings', name: 'account_get_my_settings', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function getMySettings(Request $request): JsonResponse
    {
        $accountIdentifier = $this->resolveAuthenticatedAccountIdentifier($request);

        if ($accountIdentifier <= 0) {
            return $this->createErrorResponse('AuthenticationRequired', 'Unable to identify the signed-in account.', 401);
        }

        $account = $this->accountReadService->getSettingsAccountById($accountIdentifier);

        if (!$account) {
            return $this->createErrorResponse('AccountNotFound', 'Account not found.', 404);
        }

        return $this->createSuccessResponse([
            'account' => $account,
        ]);
    }

    #[Route('/me/settings', name: 'account_update_my_settings', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function updateMySettings(Request $request): JsonResponse
    {
        $accountIdentifier = $this->resolveAuthenticatedAccountIdentifier($request);

        if ($accountIdentifier <= 0) {
            return $this->createErrorResponse('AuthenticationRequired', 'Unable to identify the signed-in account.', 401);
        }

        $requestBody = json_decode($request->getContent(), true);
        if (!is_array($requestBody)) {
            return $this->createErrorResponse('ValidationError', 'Invalid request body.', 422);
        }

        $lastName = $this->accountSettingsValidationService->normalizePersonName((string)($requestBody['lastName'] ?? ''));
        $firstName = $this->accountSettingsValidationService->normalizePersonName((string)($requestBody['firstName'] ?? ''));
        $contactNumber = preg_replace('/\D+/', '', (string)($requestBody['contactNumber'] ?? '')) ?? '';
        if (str_starts_with($contactNumber, '09')) {
            $contactNumber = substr($contactNumber, 1);
        }
        $profilePhotoData = array_key_exists('profilePhotoData', $requestBody)
            ? trim((string)$requestBody['profilePhotoData'])
            : null;

        $validationError = $this->accountSettingsValidationService->validateEditableAccountSettings($firstName, $lastName, $contactNumber, $profilePhotoData);
        if ($validationError !== null) {
            return $this->createErrorResponse('ValidationError', $validationError, 422);
        }

        $updateFields = [
            'last_name' => $lastName,
            'first_name' => $firstName,
            'contact_number' => $contactNumber,
            'updated_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        if ($profilePhotoData !== null && $profilePhotoData !== '') {
            $updateFields['profile_photo_data'] = $profilePhotoData;
        }

        $updatedRows = $this->connection->update(
            'accounts',
            $updateFields,
            ['account_identifier' => $accountIdentifier]
        );

        if ($updatedRows === 0 && !$this->accountReadService->getSettingsAccountById($accountIdentifier)) {
            return $this->createErrorResponse('AccountNotFound', 'Account not found.', 404);
        }

        return $this->createSuccessResponse([
            'message' => 'Account settings updated.',
            'account' => $this->accountReadService->getSettingsAccountById($accountIdentifier),
        ]);
    }

    #[Route('/me/password', name: 'account_update_my_password', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function updateMyPassword(Request $request): JsonResponse
    {
        $accountIdentifier = $this->resolveAuthenticatedAccountIdentifier($request);

        if ($accountIdentifier <= 0) {
            return $this->createErrorResponse('AuthenticationRequired', 'Unable to identify the signed-in account.', 401);
        }

        $requestBody = json_decode($request->getContent(), true);
        if (!is_array($requestBody)) {
            return $this->createErrorResponse('ValidationError', 'Invalid request body.', 422);
        }

        $currentPassword = (string)($requestBody['currentPassword'] ?? '');
        $newPassword = (string)($requestBody['newPassword'] ?? '');
        $confirmPassword = (string)($requestBody['confirmPassword'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            return $this->createErrorResponse('ValidationError', 'Current password, new password, and confirmation are required.', 422);
        }

        if ($currentPassword === $newPassword) {
            return $this->createErrorResponse('ValidationError', 'New password must be different from the current password.', 422);
        }

        if ($newPassword !== $confirmPassword) {
            return $this->createErrorResponse('ValidationError', 'New password and confirmation password do not match.', 422);
        }

        if (!$this->passwordPolicyService->isStrongPassword($newPassword)) {
            return $this->createErrorResponse('ValidationError', 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.', 422);
        }

        $account = $this->connection->fetchAssociative(
            'SELECT account_identifier, password_hash FROM accounts WHERE account_identifier = :accountIdentifier',
            ['accountIdentifier' => $accountIdentifier],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        if (!$account) {
            return $this->createErrorResponse('AccountNotFound', 'Account not found.', 404);
        }

        $passwordHash = (string)($account['password_hash'] ?? '');
        if ($passwordHash === '') {
            return $this->createErrorResponse('PasswordUpdateUnavailable', 'This account does not have a local password to update.', 422);
        }

        if (!password_verify($currentPassword, $passwordHash)) {
            return $this->createErrorResponse('InvalidPassword', 'Current password is incorrect.', 422);
        }

        if (password_verify($newPassword, $passwordHash)) {
            return $this->createErrorResponse('ValidationError', 'New password must be different from the current password.', 422);
        }

        $this->connection->update(
            'accounts',
            [
                'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT),
                'updated_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ],
            ['account_identifier' => $accountIdentifier]
        );

        return $this->createSuccessResponse([
            'message' => 'Password updated.',
        ]);
    }

    #[Route('/me/password/sync-from-clerk', name: 'account_sync_clerk_password', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function syncPasswordFromClerk(Request $request): JsonResponse
    {
        $accountIdentifier = $this->resolveAuthenticatedAccountIdentifier($request);

        if ($accountIdentifier <= 0) {
            return $this->createErrorResponse('AuthenticationRequired', 'Unable to identify the signed-in account.', 401);
        }

        $requestBody = json_decode($request->getContent(), true);
        if (!is_array($requestBody)) {
            return $this->createErrorResponse('ValidationError', 'Invalid request body.', 422);
        }

        $newPassword = (string)($requestBody['newPassword'] ?? '');

        if (!$this->passwordPolicyService->isStrongPassword($newPassword)) {
            return $this->createErrorResponse('ValidationError', 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.', 422);
        }

        $updatedRows = $this->connection->update(
            'accounts',
            [
                'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT),
                'updated_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ],
            ['account_identifier' => $accountIdentifier]
        );

        if ($updatedRows === 0) {
            return $this->createErrorResponse('AccountNotFound', 'Account not found.', 404);
        }

        return $this->createSuccessResponse([
            'message' => 'Password synced from Clerk.',
        ]);
    }

    #[Route('', name: 'account_get_all', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getAllAccounts(): JsonResponse
    {
        return $this->createSuccessResponse([
            'accounts' => $this->accountReadService->getAcceptedAccounts(),
        ]);
    }

    #[Route('/{accountIdentifier}', name: 'account_get_by_id', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getAccountById(int $accountIdentifier): JsonResponse
    {
        $profileDTO = $this->accountProfileService->getAccountProfileById($accountIdentifier);

        return $this->createSuccessResponse($profileDTO->toResponseArray());
    }

    #[Route('/{accountIdentifier}', name: 'account_update', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateAccount(int $accountIdentifier, Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];

        $updateDTO = new AccountUpdateRequestDTO(
            contactNumber: $requestBody['contactNumber'] ?? null,
            roleDesignation: $requestBody['roleDesignation'] ?? null
        );

        $updatedProfile = $this->accountUpdateService->updateAccountProfile($accountIdentifier, $updateDTO);

        return $this->createSuccessResponse($updatedProfile->toResponseArray());
    }

    #[Route('/{accountIdentifier}/work-logs', name: 'account_employee_work_logs', requirements: ['accountIdentifier' => '\d+'], methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getEmployeeWorkLogs(int $accountIdentifier): JsonResponse
    {
        $account = $this->accountReadService->getAccountStateById($accountIdentifier);
        if (!$account) {
            return $this->createErrorResponse('AccountNotFound', 'Account not found.', 404);
        }

        $mappedAccount = $this->accountReadService->getMappedAccountById($accountIdentifier);
        if (($mappedAccount['accountType'] ?? '') !== 'Employee') {
            return $this->createErrorResponse(
                'WorkLogsUnavailable',
                'Work logs are only available for employee accounts.',
                422
            );
        }

        return $this->createSuccessResponse([
            'account' => $mappedAccount,
            'workLogs' => $this->accountReadService->getEmployeeWorkLogs($accountIdentifier),
        ]);
    }

    #[Route('/{accountIdentifier}/admin-details', name: 'account_update_admin_details', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateAdminAccountDetails(int $accountIdentifier, Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $existingAccount = $this->accountReadService->getAccountStateById($accountIdentifier);

        if (!$existingAccount) {
            return $this->createErrorResponse('AccountNotFound', 'Account not found.', 404);
        }

        $currentMappedAccount = $this->accountReadService->getMappedAccountById($accountIdentifier);
        $accountStatus = $this->accountLifecyclePolicyService->resolveAccountStatus(
            DatabaseBoolean::toBool($existingAccount['is_active'] ?? false),
            (string)($existingAccount['status'] ?? ''),
            DatabaseBoolean::toBool($existingAccount['is_approved'] ?? false)
        );

        if (!$this->accountLifecyclePolicyService->canUpdateAccount($accountStatus)) {
            return $this->createErrorResponse(
                'AccountActionNotAllowed',
                'Only active accounts can be updated. Disabled accounts are read-only until reactivated, and pending accounts must be accepted before updates are allowed.',
                403,
                ['actionRules' => $this->accountLifecyclePolicyService->buildActionPermissions($accountStatus, DatabaseBoolean::toBool($existingAccount['is_approved'] ?? false))]
            );
        }

        $lastName = $this->accountSettingsValidationService->normalizePersonName((string)($requestBody['lastName'] ?? ''));
        $firstName = $this->accountSettingsValidationService->normalizePersonName((string)($requestBody['firstName'] ?? ''));
        $contactNumber = preg_replace('/\D+/', '', (string)($requestBody['contactNumber'] ?? '')) ?? '';
        if (str_starts_with($contactNumber, '09')) {
            $contactNumber = substr($contactNumber, 1);
        }
        $profilePhotoName = trim((string)($requestBody['profilePhotoName'] ?? ''));
        $profilePhotoData = array_key_exists('profilePhotoData', $requestBody)
            ? trim((string)$requestBody['profilePhotoData'])
            : null;

        $validationError = $this->accountSettingsValidationService->validateEditableAccountSettings($firstName, $lastName, $contactNumber, $profilePhotoData, $profilePhotoName);
        if ($validationError !== null) {
            return $this->createErrorResponse('ValidationError', $validationError, 422);
        }

        $updateFields = [
            'last_name' => $lastName,
            'first_name' => $firstName,
            'contact_number' => $contactNumber,
            'updated_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        if ($profilePhotoData !== null && $profilePhotoData !== '') {
            $updateFields['profile_photo_data'] = $profilePhotoData;
        }

        if (($currentMappedAccount['accountType'] ?? '') === 'Employee') {
            if ($this->accountReadService->hasDuplicateStaffPhone($contactNumber, $accountIdentifier)) {
                return $this->createErrorResponse('DuplicatePhoneNumber', 'This phone number is already used by another staff account.', 409);
            }
        }

        $updatedRows = $this->connection->update('accounts', $updateFields, ['account_identifier' => $accountIdentifier]);

        if (($currentMappedAccount['accountType'] ?? '') === 'Employee') {
            $this->staffInfoWriterService->upsertStaffInfo(
                $accountIdentifier,
                (string)($currentMappedAccount['rawIdNumber'] ?? $currentMappedAccount['idNumber'] ?? $existingAccount['id_number'] ?? ''),
                $firstName,
                $lastName,
                $contactNumber,
                (string)($currentMappedAccount['roleLabel'] ?? $existingAccount['department'] ?? 'Maintenance Staff'),
                ($profilePhotoData !== null && $profilePhotoData !== '') ? $profilePhotoData : (string)($currentMappedAccount['profilePhotoData'] ?? '')
            );
        }

        if ($updatedRows === 0) {
            return $this->createErrorResponse('AccountNotFound', 'Account not found.', 404);
        }

        return $this->createSuccessResponse([
            'message' => 'Changes saved.',
            'account' => $this->accountReadService->getMappedAccountById($accountIdentifier),
        ]);
    }

    #[Route('/{accountIdentifier}/access', name: 'account_update_access', requirements: ['accountIdentifier' => '\d+'], methods: ['PATCH'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateAccountAccess(int $accountIdentifier, Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $isActive = (bool)($requestBody['isActive'] ?? false);

        $account = $this->accountReadService->getAccountStateById($accountIdentifier);

        if (!$account) {
            return $this->createErrorResponse('AccountNotFound', 'Account not found.', 404);
        }

        $currentIsApproved = DatabaseBoolean::toBool($account['is_approved'] ?? false);
        $currentStatus = $this->accountLifecyclePolicyService->resolveAccountStatus(
            DatabaseBoolean::toBool($account['is_active'] ?? false),
            (string)($account['status'] ?? ''),
            $currentIsApproved
        );

        if ($isActive && !$this->accountLifecyclePolicyService->canActivateAccount($currentStatus)) {
            return $this->createErrorResponse(
                'AccountActionNotAllowed',
                'Only disabled accounts can be reactivated.',
                403,
                ['actionRules' => $this->accountLifecyclePolicyService->buildActionPermissions($currentStatus, $currentIsApproved)]
            );
        }

        if (!$isActive && !$this->accountLifecyclePolicyService->canDisableAccount($currentStatus)) {
            return $this->createErrorResponse(
                'AccountActionNotAllowed',
                'Only active accounts can be disabled.',
                403,
                ['actionRules' => $this->accountLifecyclePolicyService->buildActionPermissions($currentStatus, $currentIsApproved)]
            );
        }

        $securityConfirmationError = $this->validateResponsibleAdminEmail(
            $request,
            (string)($requestBody['confirmedAdminEmail'] ?? ''),
            $isActive ? 'reactivating' : 'deactivating'
        );
        if ($securityConfirmationError !== null) {
            return $securityConfirmationError;
        }

        $nextStatus = $isActive ? 'approved' : 'disabled';
        $nextIsApproved = $isActive ? true : $currentIsApproved;

        $this->connection->executeStatement(
            'UPDATE accounts
             SET is_active = :isActive, is_approved = :isApproved, status = :status, updated_timestamp = :updatedTimestamp
             WHERE account_identifier = :accountIdentifier',
            [
                'isActive' => $isActive,
                'isApproved' => $nextIsApproved,
                'status' => $nextStatus,
                'updatedTimestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'accountIdentifier' => $accountIdentifier,
            ],
            [
                'isActive' => ParameterType::BOOLEAN,
                'isApproved' => ParameterType::BOOLEAN,
                'status' => ParameterType::STRING,
                'updatedTimestamp' => ParameterType::STRING,
                'accountIdentifier' => ParameterType::INTEGER,
            ]
        );

        return $this->createSuccessResponse([
            'message' => $isActive ? 'Account reactivated.' : 'Account disabled.',
            'account' => $this->accountReadService->getMappedAccountById($accountIdentifier),
        ]);
    }

    #[Route('/{accountIdentifier}', name: 'account_delete', requirements: ['accountIdentifier' => '\d+'], methods: ['DELETE'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function deleteAccount(int $accountIdentifier, Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $account = $this->accountReadService->getAccountStateById($accountIdentifier);

        if (!$account) {
            return $this->createErrorResponse('AccountNotFound', 'Account not found.', 404);
        }

        $authenticatedAdminId = $this->resolveAuthenticatedAccountIdentifier($request);
        if ($authenticatedAdminId === $accountIdentifier) {
            return $this->createErrorResponse('AccountActionNotAllowed', 'You cannot delete your own signed-in account.', 403);
        }

        $securityConfirmationError = $this->validateResponsibleAdminCredentials(
            $authenticatedAdminId,
            (string)($requestBody['confirmedAdminEmail'] ?? ''),
            (string)($requestBody['confirmedAdminPassword'] ?? ''),
            'deleting'
        );
        if ($securityConfirmationError !== null) {
            return $securityConfirmationError;
        }

        try {
            $deletedRows = $this->accountDeletionService->deleteAccount($account, $accountIdentifier);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse(
                'DeleteAccountFailed',
                'Unable to delete account: ' . $exception->getMessage(),
                500
            );
        }

        if ($deletedRows === 0) {
            return $this->createErrorResponse('AccountNotFound', 'Account not found.', 404);
        }

        return $this->createSuccessResponse([
            'message' => 'Account deleted.',
            'accountIdentifier' => $accountIdentifier,
        ]);
    }

    private function validateResponsibleAdminEmail(Request $request, string $confirmedAdminEmail, string $actionName): ?JsonResponse
    {
        $authenticatedAdminId = $this->resolveAuthenticatedAccountIdentifier($request);
        return $this->securityConfirmationError(
            $this->adminSecurityConfirmationService->validateAdminEmail($authenticatedAdminId, $confirmedAdminEmail, $actionName)
        );
    }

    private function validateResponsibleAdminCredentials(int $authenticatedAdminId, string $confirmedAdminEmail, string $confirmedAdminPassword, string $actionName): ?JsonResponse
    {
        return $this->securityConfirmationError(
            $this->adminSecurityConfirmationService->validateAdminCredentials($authenticatedAdminId, $confirmedAdminEmail, $confirmedAdminPassword, $actionName)
        );
    }

    private function resolveAuthenticatedAccountIdentifier(Request $request): int
    {
        return $this->authenticatedAccountResolver->resolveAccountIdentifier($request);
    }

    private function securityConfirmationError(?string $message): ?JsonResponse
    {
        if ($message === null) {
            return null;
        }

        return $this->createErrorResponse('SecurityConfirmationFailed', $message, 422);
    }
}
