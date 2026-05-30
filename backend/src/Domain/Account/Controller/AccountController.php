<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\DTO\AccountUpdateRequestDTO;
use App\Domain\Account\Service\AccountProfileService;
use App\Domain\Account\Service\AccountUpdateService;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/v1/accounts')]
class AccountController extends AbstractController
{
    use JsonResponseTrait;

    private AccountProfileService $accountProfileService;
    private AccountUpdateService $accountUpdateService;
    private Connection $connection;
    private HttpClientInterface $httpClient;

    public function __construct(
        AccountProfileService $accountProfileService,
        AccountUpdateService $accountUpdateService,
        Connection $connection,
        HttpClientInterface $httpClient
    ) {
        $this->accountProfileService = $accountProfileService;
        $this->accountUpdateService = $accountUpdateService;
        $this->connection = $connection;
        $this->httpClient = $httpClient;
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

        $account = $this->getSettingsAccountById($accountIdentifier);

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

        $lastName = $this->normalizePersonName((string)($requestBody['lastName'] ?? ''));
        $firstName = $this->normalizePersonName((string)($requestBody['firstName'] ?? ''));
        $contactNumber = preg_replace('/\D+/', '', (string)($requestBody['contactNumber'] ?? '')) ?? '';
        if (str_starts_with($contactNumber, '09')) {
            $contactNumber = substr($contactNumber, 1);
        }
        $profilePhotoData = array_key_exists('profilePhotoData', $requestBody)
            ? trim((string)$requestBody['profilePhotoData'])
            : null;

        $validationError = $this->validateEditableAccountSettings($firstName, $lastName, $contactNumber, $profilePhotoData);
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

        if ($updatedRows === 0 && !$this->getSettingsAccountById($accountIdentifier)) {
            return $this->createErrorResponse('AccountNotFound', 'Account not found.', 404);
        }

        return $this->createSuccessResponse([
            'message' => 'Account settings updated.',
            'account' => $this->getSettingsAccountById($accountIdentifier),
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

        if (!$this->isStrongPassword($newPassword)) {
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

        if (!$this->isStrongPassword($newPassword)) {
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
        $rows = $this->connection->fetchAllAssociative(
            "WITH accepted_accounts AS (
                SELECT accounts.account_identifier, accounts.id_number, accounts.last_name, accounts.first_name,
                       accounts.email_address, accounts.role_designation, accounts.department, accounts.contact_number,
                       accounts.profile_photo_data,
                       staff_info.employee_id_number AS staff_employee_id_number,
                       staff_info.first_name AS staff_first_name,
                       staff_info.last_name AS staff_last_name,
                       staff_info.phone_number AS staff_phone_number,
                       staff_info.role AS staff_role,
                       staff_info.image_url AS staff_image_url,
                       accounts.status, accounts.is_approved, accounts.is_active, accounts.created_timestamp,
                       accounts.last_login_timestamp,
                       latest_invitation.created_at AS invite_sent_at,
                       latest_invitation.expires_at AS invite_expires_at,
                       latest_invitation.accepted_at AS invite_accepted_at
                FROM accounts
                LEFT JOIN staff_info ON staff_info.account_identifier = accounts.account_identifier
                LEFT JOIN LATERAL (
                   SELECT created_at, expires_at, accepted_at
                   FROM invitations
                   WHERE LOWER(email) = LOWER(accounts.email_address)
                   ORDER BY created_at DESC
                   LIMIT 1
                ) latest_invitation ON TRUE
                WHERE COALESCE(accounts.is_approved, FALSE) = TRUE
                  AND LOWER(COALESCE(accounts.status, 'pending')) IN ('approved', 'disabled')
                  AND (
                    latest_invitation.accepted_at IS NOT NULL
                    OR accounts.role_designation IN ('ROLE_ADMIN', 'ADMIN', 'ROLE_STAFF')
                  )
             ),
             deduped_by_email AS (
                SELECT DISTINCT ON (LOWER(email_address)) *
                FROM accepted_accounts
                ORDER BY LOWER(email_address), created_timestamp DESC, account_identifier DESC
             ),
             deduped_by_id AS (
                SELECT DISTINCT ON (COALESCE(NULLIF(id_number, ''), account_identifier::text)) *
                FROM deduped_by_email
                ORDER BY COALESCE(NULLIF(id_number, ''), account_identifier::text), created_timestamp DESC, account_identifier DESC
             ),
             deduped_by_phone AS (
                SELECT DISTINCT ON (COALESCE(NULLIF(contact_number, ''), account_identifier::text)) *
                FROM deduped_by_id
                ORDER BY COALESCE(NULLIF(contact_number, ''), account_identifier::text), created_timestamp DESC, account_identifier DESC
             )
             SELECT *
             FROM deduped_by_phone
             ORDER BY created_timestamp DESC"
        );

        return $this->createSuccessResponse([
            'accounts' => array_map(fn (array $row): array => $this->mapAccountRow($row), $rows),
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
        $account = $this->getAccountStateById($accountIdentifier);
        if (!$account) {
            return $this->createErrorResponse('AccountNotFound', 'Account not found.', 404);
        }

        $mappedAccount = $this->getMappedAccountById($accountIdentifier);
        if (($mappedAccount['accountType'] ?? '') !== 'Employee') {
            return $this->createErrorResponse(
                'WorkLogsUnavailable',
                'Work logs are only available for employee accounts.',
                422
            );
        }

        $rows = $this->connection->fetchAllAssociative(
            "SELECT
                history_logs.id AS history_log_id,
                history_logs.staff_id,
                history_logs.reservation_id,
                history_logs.task_assignment_id,
                tasks.task_identifier,
                tasks.task_title,
                tasks.task_description,
                tasks.task_type,
                tasks.task_status,
                tasks.assigned_to_account_id,
                tasks.due_date_timestamp,
                tasks.created_timestamp,
                tasks.updated_timestamp,
                reservations.reservation_identifier,
                reservations.reservation_code,
                reservations.organization_name,
                reservations.event_date_time,
                reservations.purpose_description,
                reservations.activity_type,
                reservations.current_status AS reservation_status,
                reservations.requested_equipment_list,
                reservations.requested_quantity,
                reservations.priority_level
             FROM history_logs
             INNER JOIN staff_info ON staff_info.id = history_logs.staff_id
             INNER JOIN tasks ON tasks.task_identifier = history_logs.task_assignment_id
             INNER JOIN reservations ON reservations.reservation_identifier = history_logs.reservation_id
             WHERE staff_info.account_identifier = :accountIdentifier
             ORDER BY COALESCE(tasks.due_date_timestamp, tasks.created_timestamp) DESC, history_logs.id DESC",
            ['accountIdentifier' => $accountIdentifier],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        return $this->createSuccessResponse([
            'account' => $mappedAccount,
            'workLogs' => array_map(fn (array $row): array => $this->mapEmployeeWorkLogRow($row), $rows),
        ]);
    }

    #[Route('/{accountIdentifier}/admin-details', name: 'account_update_admin_details', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateAdminAccountDetails(int $accountIdentifier, Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $existingAccount = $this->getAccountStateById($accountIdentifier);

        if (!$existingAccount) {
            return $this->createErrorResponse('AccountNotFound', 'Account not found.', 404);
        }

        $currentMappedAccount = $this->getMappedAccountById($accountIdentifier);
        $accountStatus = $this->resolveAccountStatus(
            $this->toDatabaseBoolean($existingAccount['is_active'] ?? false),
            (string)($existingAccount['status'] ?? ''),
            $this->toDatabaseBoolean($existingAccount['is_approved'] ?? false)
        );

        if (!$this->canUpdateAccount($accountStatus)) {
            return $this->createErrorResponse(
                'AccountActionNotAllowed',
                'Only active accounts can be updated. Disabled accounts are read-only until reactivated, and pending accounts must be accepted before updates are allowed.',
                403,
                ['actionRules' => $this->buildActionPermissions($accountStatus, $this->toDatabaseBoolean($existingAccount['is_approved'] ?? false))]
            );
        }

        $lastName = $this->normalizePersonName((string)($requestBody['lastName'] ?? ''));
        $firstName = $this->normalizePersonName((string)($requestBody['firstName'] ?? ''));
        $contactNumber = preg_replace('/\D+/', '', (string)($requestBody['contactNumber'] ?? '')) ?? '';
        if (str_starts_with($contactNumber, '09')) {
            $contactNumber = substr($contactNumber, 1);
        }
        $profilePhotoName = trim((string)($requestBody['profilePhotoName'] ?? ''));
        $profilePhotoData = array_key_exists('profilePhotoData', $requestBody)
            ? trim((string)$requestBody['profilePhotoData'])
            : null;

        $validationError = $this->validateEditableAccountSettings($firstName, $lastName, $contactNumber, $profilePhotoData, $profilePhotoName);
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
            $duplicateStaffPhone = $this->connection->fetchOne(
                'SELECT 1 FROM staff_info WHERE phone_number = :phoneNumber AND account_identifier <> :accountIdentifier',
                ['phoneNumber' => $contactNumber, 'accountIdentifier' => $accountIdentifier],
                ['phoneNumber' => ParameterType::STRING, 'accountIdentifier' => ParameterType::INTEGER]
            );

            if ($duplicateStaffPhone) {
                return $this->createErrorResponse('DuplicatePhoneNumber', 'This phone number is already used by another staff account.', 409);
            }
        }

        $updatedRows = $this->connection->update('accounts', $updateFields, ['account_identifier' => $accountIdentifier]);

        if (($currentMappedAccount['accountType'] ?? '') === 'Employee') {
            $this->upsertStaffInfo(
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
            'account' => $this->getMappedAccountById($accountIdentifier),
        ]);
    }

    #[Route('/{accountIdentifier}/access', name: 'account_update_access', requirements: ['accountIdentifier' => '\d+'], methods: ['PATCH'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateAccountAccess(int $accountIdentifier, Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $isActive = (bool)($requestBody['isActive'] ?? false);

        $account = $this->getAccountStateById($accountIdentifier);

        if (!$account) {
            return $this->createErrorResponse('AccountNotFound', 'Account not found.', 404);
        }

        $currentIsApproved = $this->toDatabaseBoolean($account['is_approved'] ?? false);
        $currentStatus = $this->resolveAccountStatus(
            $this->toDatabaseBoolean($account['is_active'] ?? false),
            (string)($account['status'] ?? ''),
            $currentIsApproved
        );

        if ($isActive && !$this->canActivateAccount($currentStatus)) {
            return $this->createErrorResponse(
                'AccountActionNotAllowed',
                'Only disabled accounts can be reactivated.',
                403,
                ['actionRules' => $this->buildActionPermissions($currentStatus, $currentIsApproved)]
            );
        }

        if (!$isActive && !$this->canDisableAccount($currentStatus)) {
            return $this->createErrorResponse(
                'AccountActionNotAllowed',
                'Only active accounts can be disabled.',
                403,
                ['actionRules' => $this->buildActionPermissions($currentStatus, $currentIsApproved)]
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
            'account' => $this->getMappedAccountById($accountIdentifier),
        ]);
    }

    #[Route('/{accountIdentifier}', name: 'account_delete', requirements: ['accountIdentifier' => '\d+'], methods: ['DELETE'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function deleteAccount(int $accountIdentifier, Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $account = $this->getAccountStateById($accountIdentifier);

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

        $this->connection->beginTransaction();
        try {
            $clerkUserId = trim((string)($account['clerk_user_id'] ?? ''));

            $this->connection->executeStatement(
                'DELETE FROM invitations WHERE LOWER(email) = LOWER(:emailAddress)',
                ['emailAddress' => (string)$account['email_address']],
                ['emailAddress' => ParameterType::STRING]
            );

            $this->connection->executeStatement(
                'DELETE FROM staff_info WHERE account_identifier = :accountIdentifier',
                ['accountIdentifier' => $accountIdentifier],
                ['accountIdentifier' => ParameterType::INTEGER]
            );

            $deletedRows = $this->connection->executeStatement(
                'DELETE FROM accounts WHERE account_identifier = :accountIdentifier',
                ['accountIdentifier' => $accountIdentifier],
                ['accountIdentifier' => ParameterType::INTEGER]
            );

            $this->connection->commit();

            if ($clerkUserId !== '') {
                $this->deleteClerkUser($clerkUserId);
            }
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
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

    private function getMappedAccountById(int $accountIdentifier): ?array
    {
        $row = $this->connection->fetchAssociative(
            "SELECT accounts.account_identifier, accounts.id_number, accounts.last_name, accounts.first_name, accounts.email_address, accounts.role_designation,
                    accounts.department, accounts.contact_number, accounts.status, accounts.is_approved, accounts.is_active, accounts.created_timestamp,
                    accounts.profile_photo_data,
                    staff_info.employee_id_number AS staff_employee_id_number,
                    staff_info.first_name AS staff_first_name,
                    staff_info.last_name AS staff_last_name,
                    staff_info.phone_number AS staff_phone_number,
                    staff_info.role AS staff_role,
                    staff_info.image_url AS staff_image_url,
                    accounts.last_login_timestamp,
                    latest_invitation.created_at AS invite_sent_at,
                    latest_invitation.expires_at AS invite_expires_at,
                    latest_invitation.accepted_at AS invite_accepted_at
             FROM accounts
             LEFT JOIN staff_info ON staff_info.account_identifier = accounts.account_identifier
             LEFT JOIN LATERAL (
                SELECT created_at, expires_at, accepted_at
                FROM invitations
                WHERE LOWER(email) = LOWER(accounts.email_address)
                ORDER BY created_at DESC
                LIMIT 1
             ) latest_invitation ON TRUE
             WHERE accounts.account_identifier = :accountIdentifier",
            ['accountIdentifier' => $accountIdentifier]
        );

        return $row ? $this->mapAccountRow($row) : null;
    }

    private function getSettingsAccountById(int $accountIdentifier): ?array
    {
        $row = $this->connection->fetchAssociative(
            "SELECT accounts.account_identifier, accounts.id_number, accounts.last_name, accounts.first_name, accounts.email_address, accounts.role_designation,
                    accounts.department, accounts.contact_number, accounts.status, accounts.is_approved, accounts.is_active, accounts.created_timestamp,
                    accounts.last_login_timestamp, accounts.profile_photo_data,
                    staff_info.employee_id_number AS staff_employee_id_number,
                    staff_info.first_name AS staff_first_name,
                    staff_info.last_name AS staff_last_name,
                    staff_info.phone_number AS staff_phone_number,
                    staff_info.role AS staff_role,
                    staff_info.image_url AS staff_image_url
             FROM accounts
             LEFT JOIN staff_info ON staff_info.account_identifier = accounts.account_identifier
             WHERE accounts.account_identifier = :accountIdentifier",
            ['accountIdentifier' => $accountIdentifier],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        if (!$row) {
            return null;
        }

        $mapped = $this->mapAccountRow($row + [
            'invite_sent_at' => null,
            'invite_expires_at' => null,
            'invite_accepted_at' => null,
        ]);

        $mapped['profilePhotoData'] = $row['profile_photo_data'] ? (string)$row['profile_photo_data'] : null;

        return $mapped;
    }

    private function mapAccountRow(array $row): array
    {
        $roleDesignation = (string)($row['role_designation'] ?? 'ROLE_BORROWER');
        $department = strtolower((string)($row['department'] ?? ''));
        $normalizedRole = strtoupper($roleDesignation);
        $isActive = $this->toDatabaseBoolean($row['is_active'] ?? false);
        $isApproved = $this->toDatabaseBoolean($row['is_approved'] ?? false);
        $accountStatus = $this->resolveAccountStatus($isActive, (string)($row['status'] ?? ''), $isApproved);
        $isAdmin = str_contains($normalizedRole, 'ADMIN') || strtolower($roleDesignation) === 'admin';
        $isEmployee = !$isAdmin && (
            str_contains($normalizedRole, 'STAFF') ||
            str_contains($normalizedRole, 'EMPLOYEE') ||
            str_contains($department, 'staff') ||
            str_contains($department, 'employee') ||
            str_contains($department, 'technical') ||
            str_contains($department, 'maintenance') ||
            str_contains($department, 'support')
        );

        $accountType = $isAdmin ? 'Admin' : ($isEmployee ? 'Employee' : 'User');
        $roleLabel = $this->resolveRoleLabelForAccountRow($row, $accountType);
        $idNumber = ($isEmployee && !empty($row['staff_employee_id_number']))
            ? (string)$row['staff_employee_id_number']
            : ($row['id_number'] ?: substr((string)$row['created_timestamp'], 0, 4) . str_pad((string)$row['account_identifier'], 4, '0', STR_PAD_LEFT));
        $firstName = ($isEmployee && !empty($row['staff_first_name'])) ? (string)$row['staff_first_name'] : (string)$row['first_name'];
        $lastName = ($isEmployee && !empty($row['staff_last_name'])) ? (string)$row['staff_last_name'] : (string)$row['last_name'];
        $contactNumber = ($isEmployee && !empty($row['staff_phone_number']))
            ? (string)$row['staff_phone_number']
            : ($row['contact_number'] ? (string)$row['contact_number'] : null);
        $profilePhotoData = ($isEmployee && !empty($row['staff_image_url']))
            ? (string)$row['staff_image_url']
            : (!empty($row['profile_photo_data']) ? (string)$row['profile_photo_data'] : null);

        return [
            'accountIdentifier' => (int)$row['account_identifier'],
            'idNumber' => $idNumber,
            'lastName' => $lastName,
            'firstName' => $firstName,
            'emailAddress' => (string)$row['email_address'],
            'roleDesignation' => $this->normalizeRoleDesignation($roleDesignation),
            'roleLabel' => ($isEmployee && !empty($row['staff_role'])) ? (string)$row['staff_role'] : $roleLabel,
            'accountType' => $accountType,
            'accountStatus' => $accountStatus,
            'isActive' => $isActive,
            'isApproved' => $isApproved,
            'actionPermissions' => $this->buildActionPermissions($accountStatus, $isApproved),
            'contactNumber' => $contactNumber,
            'profilePhotoData' => $profilePhotoData,
            'createdTimestamp' => (string)$row['created_timestamp'],
            'lastLoginTimestamp' => $row['last_login_timestamp'] ? (string)$row['last_login_timestamp'] : null,
            'inviteSentAt' => $row['invite_sent_at'] ? (string)$row['invite_sent_at'] : null,
            'inviteExpiresAt' => $row['invite_expires_at'] ? (string)$row['invite_expires_at'] : null,
            'inviteAcceptedAt' => $row['invite_accepted_at'] ? (string)$row['invite_accepted_at'] : null,
        ];
    }

    private function mapEmployeeWorkLogRow(array $row): array
    {
        $equipmentList = $this->decodeJsonList($row['requested_equipment_list'] ?? null);
        $reservationDetails = null;

        if (!empty($row['reservation_identifier'])) {
            $reservationDetails = [
                'reservationIdentifier' => (int)$row['reservation_identifier'],
                'reservationCode' => (string)($row['reservation_code'] ?? ''),
                'organizationName' => (string)($row['organization_name'] ?? ''),
                'eventDateTime' => $row['event_date_time'] ? (string)$row['event_date_time'] : null,
                'purposeDescription' => $row['purpose_description'] ? (string)$row['purpose_description'] : null,
                'activityType' => $row['activity_type'] ? (string)$row['activity_type'] : null,
                'status' => $row['reservation_status'] ? (string)$row['reservation_status'] : null,
                'requestedEquipmentList' => $equipmentList,
                'requestedQuantity' => isset($row['requested_quantity']) ? (int)$row['requested_quantity'] : null,
                'priorityLevel' => $row['priority_level'] ? (string)$row['priority_level'] : null,
            ];
        }

        return [
            'historyLogId' => isset($row['history_log_id']) ? (int)$row['history_log_id'] : null,
            'staffId' => isset($row['staff_id']) ? (int)$row['staff_id'] : null,
            'reservationId' => isset($row['reservation_id']) ? (int)$row['reservation_id'] : null,
            'taskAssignmentId' => isset($row['task_assignment_id']) ? (int)$row['task_assignment_id'] : null,
            'taskIdentifier' => (int)$row['task_identifier'],
            'taskName' => (string)$row['task_title'],
            'taskDescription' => $row['task_description'] ? (string)$row['task_description'] : null,
            'taskType' => (string)($row['task_type'] ?? ''),
            'status' => (string)($row['task_status'] ?? ''),
            'assignedToAccountId' => $row['assigned_to_account_id'] !== null ? (int)$row['assigned_to_account_id'] : null,
            'taskDateTime' => $row['due_date_timestamp'] ? (string)$row['due_date_timestamp'] : (string)($row['created_timestamp'] ?? ''),
            'dueDateTimestamp' => $row['due_date_timestamp'] ? (string)$row['due_date_timestamp'] : null,
            'createdTimestamp' => (string)($row['created_timestamp'] ?? ''),
            'updatedTimestamp' => (string)($row['updated_timestamp'] ?? ''),
            'reservationDetails' => $reservationDetails,
            'assignments' => [
                'assignedToAccountId' => $row['assigned_to_account_id'] !== null ? (int)$row['assigned_to_account_id'] : null,
                'assignmentType' => (string)($row['task_type'] ?? ''),
                'assignedTask' => (string)$row['task_title'],
            ],
            'fullTaskInformation' => [
                'description' => $row['task_description'] ? (string)$row['task_description'] : null,
                'type' => (string)($row['task_type'] ?? ''),
                'createdTimestamp' => (string)($row['created_timestamp'] ?? ''),
                'updatedTimestamp' => (string)($row['updated_timestamp'] ?? ''),
            ],
        ];
    }

    private function decodeJsonList(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string)($value ?? '[]'), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function upsertStaffInfo(
        int $accountIdentifier,
        string $employeeIdNumber,
        string $firstName,
        string $lastName,
        string $phoneNumber,
        string $role,
        ?string $imageUrl
    ): void {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->connection->executeStatement(
            'INSERT INTO staff_info
                (account_identifier, employee_id_number, first_name, last_name, phone_number, role, image_url, created_timestamp, updated_timestamp)
             VALUES
                (:accountIdentifier, :employeeIdNumber, :firstName, :lastName, :phoneNumber, :role, :imageUrl, :createdTimestamp, :updatedTimestamp)
             ON CONFLICT (account_identifier) WHERE account_identifier IS NOT NULL
             DO UPDATE SET
                employee_id_number = EXCLUDED.employee_id_number,
                first_name = EXCLUDED.first_name,
                last_name = EXCLUDED.last_name,
                phone_number = EXCLUDED.phone_number,
                role = EXCLUDED.role,
                image_url = COALESCE(EXCLUDED.image_url, staff_info.image_url),
                updated_timestamp = EXCLUDED.updated_timestamp',
            [
                'accountIdentifier' => $accountIdentifier,
                'employeeIdNumber' => $employeeIdNumber,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'phoneNumber' => $phoneNumber,
                'role' => $role,
                'imageUrl' => $imageUrl !== '' ? $imageUrl : null,
                'createdTimestamp' => $now,
                'updatedTimestamp' => $now,
            ],
            [
                'accountIdentifier' => ParameterType::INTEGER,
                'employeeIdNumber' => ParameterType::STRING,
                'firstName' => ParameterType::STRING,
                'lastName' => ParameterType::STRING,
                'phoneNumber' => ParameterType::STRING,
                'role' => ParameterType::STRING,
                'imageUrl' => $imageUrl === null || $imageUrl === '' ? ParameterType::NULL : ParameterType::STRING,
                'createdTimestamp' => ParameterType::STRING,
                'updatedTimestamp' => ParameterType::STRING,
            ]
        );
    }

    private function normalizeRoleDesignation(string $roleDesignation): string
    {
        $normalized = strtoupper(trim($roleDesignation));
        if ($normalized === 'ADMIN') return RoleConstants::ROLE_ADMIN;
        if ($normalized === 'USER') return RoleConstants::ROLE_BORROWER;
        return $normalized ?: RoleConstants::ROLE_BORROWER;
    }

    private function normalizeEmailForConfirmation(string $emailAddress): string
    {
        $normalizedEmailAddress = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\s]+/u', '', $emailAddress) ?? $emailAddress;
        return strtolower(trim($normalizedEmailAddress));
    }

    private function normalizePersonName(string $value): string
    {
        return preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);
    }

    private function validateEditableAccountSettings(
        string $firstName,
        string $lastName,
        string $contactNumber,
        ?string $profilePhotoData,
        string $profilePhotoName = ''
    ): ?string
    {
        if ($firstName === '' || $lastName === '' || $contactNumber === '') {
            return 'First name, last name, and phone number are required.';
        }

        if (!$this->isValidPersonName($firstName) || !$this->isValidPersonName($lastName)) {
            return 'First name and last name must contain letters and spaces only, with at least 2 characters each.';
        }

        if (preg_match('/^9\d{9}$/', $contactNumber) !== 1) {
            return 'Phone number must be 10 digits and begin with 9.';
        }

        if ($profilePhotoData !== null && $profilePhotoData !== '') {
            if ($profilePhotoName !== '' && !str_ends_with(strtolower($profilePhotoName), '.jpg')) {
                return 'Profile photo must be a .jpg image only.';
            }

            if (!$this->isValidJpegDataUrl($profilePhotoData)) {
                return 'Profile photo must be a .jpg image only.';
            }
        }

        return null;
    }

    private function isValidPersonName(string $value): bool
    {
        return mb_strlen($value) >= 2 && preg_match('/^[A-Za-z]+(?: [A-Za-z]+)*$/', $value) === 1;
    }

    private function isStrongPassword(string $password): bool
    {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password) === 1;
    }

    private function isValidJpegDataUrl(string $profilePhotoData): bool
    {
        if (preg_match('/^data:image\/jpeg;base64,([A-Za-z0-9+\/=\r\n]+)$/', $profilePhotoData, $matches) !== 1) {
            return false;
        }

        $base64Data = preg_replace('/\s+/', '', $matches[1]) ?? $matches[1];
        $binaryData = base64_decode($base64Data, true);

        if ($binaryData === false || strlen($binaryData) > 2 * 1024 * 1024) {
            return false;
        }

        return str_starts_with($binaryData, "\xFF\xD8\xFF");
    }

    private function validateResponsibleAdminEmail(Request $request, string $confirmedAdminEmail, string $actionName): ?JsonResponse
    {
        $normalizedConfirmedAdminEmail = $this->normalizeEmailForConfirmation($confirmedAdminEmail);
        $authenticatedAdminId = $this->resolveAuthenticatedAccountIdentifier($request);

        if ($normalizedConfirmedAdminEmail === '' || $authenticatedAdminId <= 0) {
            return $this->createErrorResponse(
                'SecurityConfirmationFailed',
                sprintf('Please type the responsible admin email before %s the account.', $actionName),
                422
            );
        }

        $confirmedAdmin = $this->connection->fetchAssociative(
            "SELECT email_address
             FROM accounts
             WHERE account_identifier = :accountIdentifier
               AND role_designation IN ('ROLE_ADMIN', 'ADMIN')
               AND COALESCE(is_active, TRUE) = TRUE
             LIMIT 1",
            ['accountIdentifier' => $authenticatedAdminId],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        $responsibleAdminEmail = $this->normalizeEmailForConfirmation((string)($confirmedAdmin['email_address'] ?? ''));
        if (!$confirmedAdmin || $normalizedConfirmedAdminEmail !== $responsibleAdminEmail) {
            return $this->createErrorResponse(
                'SecurityConfirmationFailed',
                sprintf('Please type your exact admin email before %s this account.', $actionName),
                422
            );
        }

        return null;
    }

    private function validateResponsibleAdminCredentials(int $authenticatedAdminId, string $confirmedAdminEmail, string $confirmedAdminPassword, string $actionName): ?JsonResponse
    {
        $normalizedConfirmedAdminEmail = $this->normalizeEmailForConfirmation($confirmedAdminEmail);

        if ($normalizedConfirmedAdminEmail === '' || $authenticatedAdminId <= 0) {
            return $this->createErrorResponse(
                'SecurityConfirmationFailed',
                sprintf('Please type the responsible admin email before %s the account.', $actionName),
                422
            );
        }

        if (trim($confirmedAdminPassword) === '') {
            return $this->createErrorResponse(
                'SecurityConfirmationFailed',
                sprintf('Please type the responsible admin password before %s the account.', $actionName),
                422
            );
        }

        $confirmedAdmin = $this->connection->fetchAssociative(
            "SELECT email_address, password_hash
             FROM accounts
             WHERE account_identifier = :accountIdentifier
               AND role_designation IN ('ROLE_ADMIN', 'ADMIN')
               AND COALESCE(is_active, TRUE) = TRUE
             LIMIT 1",
            ['accountIdentifier' => $authenticatedAdminId],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        $responsibleAdminEmail = $this->normalizeEmailForConfirmation((string)($confirmedAdmin['email_address'] ?? ''));
        if (!$confirmedAdmin || $normalizedConfirmedAdminEmail !== $responsibleAdminEmail) {
            return $this->createErrorResponse(
                'SecurityConfirmationFailed',
                sprintf('Please type your exact admin email before %s this account.', $actionName),
                422
            );
        }

        $passwordHash = (string)($confirmedAdmin['password_hash'] ?? '');
        if ($passwordHash === '' || !password_verify($confirmedAdminPassword, $passwordHash)) {
            return $this->createErrorResponse(
                'SecurityConfirmationFailed',
                sprintf('Please type your exact admin password before %s this account.', $actionName),
                422
            );
        }

        return null;
    }

    private function resolveAuthenticatedAccountIdentifier(Request $request): int
    {
        $authenticatedIdentity = $request->attributes->get('authenticatedIdentity', []);
        $accountIdentifier = (int)($authenticatedIdentity['accountIdentifier'] ?? 0);

        if ($accountIdentifier > 0) {
            return $accountIdentifier;
        }

        $authorizationHeader = (string)$request->headers->get('Authorization', '');
        if (!str_starts_with($authorizationHeader, 'Bearer ')) {
            return 0;
        }

        $token = trim(substr($authorizationHeader, 7));
        if ($token === '') {
            return 0;
        }

        $localPayload = json_decode(base64_decode($token, true) ?: '', true);
        if (is_array($localPayload)) {
            $accountIdentifier = (int)($localPayload['accountId'] ?? $localPayload['accountIdentifier'] ?? 0);
            if ($accountIdentifier > 0) {
                return $accountIdentifier;
            }
        }

        $jwtPayload = $this->decodeJwtPayloadWithoutVerification($token);
        $clerkUserId = trim((string)($jwtPayload['sub'] ?? ''));

        if ($clerkUserId === '') {
            return 0;
        }

        return (int)$this->connection->fetchOne(
            'SELECT account_identifier FROM accounts WHERE clerk_user_id = :clerkUserId LIMIT 1',
            ['clerkUserId' => $clerkUserId],
            ['clerkUserId' => ParameterType::STRING]
        );
    }

    private function decodeJwtPayloadWithoutVerification(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) < 2) {
            return [];
        }

        $payload = strtr($parts[1], '-_', '+/');
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $decodedPayload = base64_decode($payload, true);
        if ($decodedPayload === false) {
            return [];
        }

        $data = json_decode($decodedPayload, true);
        return is_array($data) ? $data : [];
    }

    private function resolveRoleDesignationForAccountType(string $accountType, string $fallbackRoleDesignation): string
    {
        if (strcasecmp($accountType, 'Admin') === 0) {
            return RoleConstants::ROLE_ADMIN;
        }

        if (strcasecmp($accountType, 'Employee') === 0) {
            return 'ROLE_STAFF';
        }

        return RoleConstants::ROLE_BORROWER;
    }

    private function resolveDepartmentForAccountType(string $accountType, string $roleLabel): ?string
    {
        $normalizedRoleLabel = trim($roleLabel);

        if (strcasecmp($accountType, 'Admin') === 0) {
            return 'Administration';
        }

        if ($normalizedRoleLabel === '') {
            return strcasecmp($accountType, 'Employee') === 0 ? 'Technical Staff' : 'Student';
        }

        return $normalizedRoleLabel;
    }

    private function resolveRoleLabelForAccountRow(array $row, string $accountType): string
    {
        if ($accountType === 'Admin') {
            return 'Admin';
        }

        $department = trim((string)($row['department'] ?? ''));
        if ($accountType === 'Employee') {
            return $department !== '' ? ucwords($department) : 'Technical Staff';
        }

        if (preg_match('/faculty/i', $department) === 1) {
            return 'Faculty';
        }

        return 'Student';
    }

    private function resolveAccountStatus(bool $isActive, string $status, bool $isApproved): string
    {
        $normalized = strtolower($status);
        if (!$isActive || $normalized === 'disabled') return 'Disabled';
        if ($normalized === 'pending' || !$isApproved) return 'Pending';
        return 'Active';
    }

    private function getAccountStateById(int $accountIdentifier): ?array
    {
        $account = $this->connection->fetchAssociative(
            'SELECT account_identifier, email_address, clerk_user_id, status, is_approved, is_active
             FROM accounts
             WHERE account_identifier = :accountIdentifier',
            ['accountIdentifier' => $accountIdentifier],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        return $account ?: null;
    }

    private function buildActionPermissions(string $accountStatus, bool $isApproved): array
    {
        return [
            'view' => true,
            'update' => $this->canUpdateAccount($accountStatus),
            'disable' => $this->canDisableAccount($accountStatus),
            'activate' => $this->canActivateAccount($accountStatus),
        ];
    }

    private function canUpdateAccount(string $accountStatus): bool
    {
        return $accountStatus === 'Active';
    }

    private function canDisableAccount(string $accountStatus): bool
    {
        return $accountStatus === 'Active';
    }

    private function canActivateAccount(string $accountStatus): bool
    {
        return $accountStatus === 'Disabled';
    }

    private function deleteClerkUser(string $clerkUserId): void
    {
        $secretKey = trim((string)($_ENV['CLERK_SECRET_KEY'] ?? ''));
        if ($secretKey === '') {
            return;
        }

        try {
            $this->httpClient->request('DELETE', ($_ENV['CLERK_API_BASE_URL'] ?? 'https://api.clerk.com') . '/v1/users/' . rawurlencode($clerkUserId), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secretKey,
                    'Accept' => 'application/json',
                ],
            ]);
        } catch (\Throwable) {
        }
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
}
