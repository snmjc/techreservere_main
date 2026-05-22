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

#[Route('/api/v1/accounts')]
class AccountController extends AbstractController
{
    use JsonResponseTrait;

    private AccountProfileService $accountProfileService;
    private AccountUpdateService $accountUpdateService;
    private Connection $connection;

    public function __construct(
        AccountProfileService $accountProfileService,
        AccountUpdateService $accountUpdateService,
        Connection $connection
    ) {
        $this->accountProfileService = $accountProfileService;
        $this->accountUpdateService = $accountUpdateService;
        $this->connection = $connection;
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

    #[Route('', name: 'account_get_all', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getAllAccounts(): JsonResponse
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT account_identifier, id_number, last_name, first_name, email_address, role_designation,
                    department, contact_number, status, is_approved, is_active, created_timestamp,
                    last_login_timestamp,
                    latest_invitation.created_at AS invite_sent_at,
                    latest_invitation.expires_at AS invite_expires_at,
                    latest_invitation.accepted_at AS invite_accepted_at
             FROM accounts
             LEFT JOIN LATERAL (
                SELECT created_at, expires_at, accepted_at
                FROM invitations
                WHERE LOWER(email) = LOWER(accounts.email_address)
                ORDER BY created_at DESC
                LIMIT 1
             ) latest_invitation ON TRUE
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

        $idNumber = trim($requestBody['idNumber'] ?? '');
        $lastName = trim($requestBody['lastName'] ?? '');
        $firstName = trim($requestBody['firstName'] ?? '');
        $emailAddress = strtolower(trim($requestBody['emailAddress'] ?? ''));
        $accountType = trim((string)($requestBody['accountType'] ?? 'Admin'));
        $roleLabel = trim((string)($requestBody['roleLabel'] ?? 'Admin'));
        $contactNumber = trim((string)($requestBody['contactNumber'] ?? ''));
        $roleDesignation = $this->resolveRoleDesignationForAccountType($accountType, (string)($requestBody['roleDesignation'] ?? 'ROLE_ADMIN'));
        $department = $this->resolveDepartmentForAccountType($accountType, $roleLabel);

        if (($currentMappedAccount['accountType'] ?? '') === 'Employee') {
            if (!in_array($accountType, ['Admin', 'Employee'], true)) {
                return $this->createErrorResponse('ValidationError', 'Employee accounts can only be updated as Admin or Employee.', 422);
            }

            if (strcasecmp($accountType, 'Employee') === 0 && preg_match('/^(user|student|faculty)$/i', $roleLabel) === 1) {
                return $this->createErrorResponse('ValidationError', 'Employee account role cannot be set to User, Student, or Faculty.', 422);
            }
        }

        if ($idNumber === '' || $lastName === '' || $firstName === '' || $emailAddress === '') {
            return $this->createErrorResponse('ValidationError', 'ID number, last name, first name, and email are required.', 422);
        }

        if (strcasecmp($accountType, 'Employee') === 0 && $contactNumber === '') {
            return $this->createErrorResponse('ValidationError', 'Phone is required for employee accounts.', 422);
        }

        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            return $this->createErrorResponse('ValidationError', 'Please provide a valid email address.', 422);
        }

        $duplicateEmail = $this->connection->fetchOne(
            'SELECT 1 FROM accounts WHERE LOWER(email_address) = LOWER(:emailAddress) AND account_identifier <> :accountIdentifier',
            ['emailAddress' => $emailAddress, 'accountIdentifier' => $accountIdentifier]
        );

        if ($duplicateEmail) {
            return $this->createErrorResponse('DuplicateAccount', 'An account with this email already exists.', 409);
        }

        $duplicateIdNumber = $this->connection->fetchOne(
            'SELECT 1 FROM accounts WHERE id_number = :idNumber AND account_identifier <> :accountIdentifier',
            ['idNumber' => $idNumber, 'accountIdentifier' => $accountIdentifier]
        );

        if ($duplicateIdNumber) {
            return $this->createErrorResponse('DuplicateIdNumber', 'An account with this ID number already exists.', 409);
        }

        $updatedRows = $this->connection->update('accounts', [
            'id_number' => $idNumber,
            'last_name' => $lastName,
            'first_name' => $firstName,
            'email_address' => $emailAddress,
            'role_designation' => $roleDesignation,
            'department' => $department,
            'contact_number' => $contactNumber !== '' ? $contactNumber : null,
            'updated_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ], ['account_identifier' => $accountIdentifier]);

        if ($updatedRows === 0) {
            return $this->createErrorResponse('AccountNotFound', 'Account not found.', 404);
        }

        return $this->createSuccessResponse([
            'message' => 'Changes saved.',
            'account' => $this->getMappedAccountById($accountIdentifier),
        ]);
    }

    #[Route('/{accountIdentifier}/access', name: 'account_update_access', methods: ['PATCH'])]
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

        if ($isActive && !$this->canActivateAccount($currentStatus, $currentIsApproved)) {
            return $this->createErrorResponse(
                'AccountActionNotAllowed',
                'Only disabled verified accounts can be activated.',
                403,
                ['actionRules' => $this->buildActionPermissions($currentStatus, $currentIsApproved)]
            );
        }

        if (!$isActive && !$this->canDisableAccount($currentStatus)) {
            return $this->createErrorResponse(
                'AccountActionNotAllowed',
                'Only active or pending accounts can be disabled.',
                403,
                ['actionRules' => $this->buildActionPermissions($currentStatus, $currentIsApproved)]
            );
        }

        $nextStatus = $isActive ? 'approved' : ($currentIsApproved ? 'disabled' : 'pending');

        $this->connection->executeStatement(
            'UPDATE accounts
             SET is_active = :isActive, status = :status, updated_timestamp = :updatedTimestamp
             WHERE account_identifier = :accountIdentifier',
            [
                'isActive' => $isActive,
                'status' => $nextStatus,
                'updatedTimestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'accountIdentifier' => $accountIdentifier,
            ],
            [
                'isActive' => ParameterType::BOOLEAN,
                'status' => ParameterType::STRING,
                'updatedTimestamp' => ParameterType::STRING,
                'accountIdentifier' => ParameterType::INTEGER,
            ]
        );

        return $this->createSuccessResponse([
            'message' => $isActive ? 'Account activated.' : 'Account disabled.',
            'account' => $this->getMappedAccountById($accountIdentifier),
        ]);
    }

    #[Route('/{accountIdentifier}', name: 'account_delete', methods: ['DELETE'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function deleteAccount(int $accountIdentifier, Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $confirmEmail = strtolower(trim((string)($requestBody['confirmEmail'] ?? '')));
        $account = $this->getAccountStateById($accountIdentifier);

        if (!$account) {
            return $this->createErrorResponse('AccountNotFound', 'Account not found.', 404);
        }

        $emailAddress = strtolower((string)($account['email_address'] ?? ''));
        if ($confirmEmail === '' || $confirmEmail !== $emailAddress) {
            return $this->createErrorResponse('DeleteConfirmationFailed', 'Please type the exact email address to delete this account.', 422);
        }

        $authenticatedIdentity = $request->attributes->get('authenticatedIdentity', []);
        $currentAccountId = (int)($authenticatedIdentity['accountIdentifier'] ?? 0);
        if ($currentAccountId === $accountIdentifier) {
            return $this->createErrorResponse('AccountActionNotAllowed', 'You cannot delete your own signed-in account.', 403);
        }

        $this->connection->beginTransaction();
        try {
            $this->connection->executeStatement(
                'DELETE FROM invitations WHERE LOWER(email) = LOWER(:emailAddress)',
                ['emailAddress' => (string)$account['email_address']],
                ['emailAddress' => ParameterType::STRING]
            );

            $deletedRows = $this->connection->executeStatement(
                'DELETE FROM accounts WHERE account_identifier = :accountIdentifier',
                ['accountIdentifier' => $accountIdentifier],
                ['accountIdentifier' => ParameterType::INTEGER]
            );

            $this->connection->commit();
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
            "SELECT account_identifier, id_number, last_name, first_name, email_address, role_designation,
                    department, contact_number, status, is_approved, is_active, created_timestamp,
                    last_login_timestamp,
                    latest_invitation.created_at AS invite_sent_at,
                    latest_invitation.expires_at AS invite_expires_at,
                    latest_invitation.accepted_at AS invite_accepted_at
             FROM accounts
             LEFT JOIN LATERAL (
                SELECT created_at, expires_at, accepted_at
                FROM invitations
                WHERE LOWER(email) = LOWER(accounts.email_address)
                ORDER BY created_at DESC
                LIMIT 1
             ) latest_invitation ON TRUE
             WHERE account_identifier = :accountIdentifier",
            ['accountIdentifier' => $accountIdentifier]
        );

        return $row ? $this->mapAccountRow($row) : null;
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

        return [
            'accountIdentifier' => (int)$row['account_identifier'],
            'idNumber' => $row['id_number'] ?: substr((string)$row['created_timestamp'], 0, 4) . str_pad((string)$row['account_identifier'], 4, '0', STR_PAD_LEFT),
            'lastName' => (string)$row['last_name'],
            'firstName' => (string)$row['first_name'],
            'emailAddress' => (string)$row['email_address'],
            'roleDesignation' => $this->normalizeRoleDesignation($roleDesignation),
            'roleLabel' => $roleLabel,
            'accountType' => $accountType,
            'accountStatus' => $accountStatus,
            'isActive' => $isActive,
            'isApproved' => $isApproved,
            'actionPermissions' => $this->buildActionPermissions($accountStatus, $isApproved),
            'contactNumber' => $row['contact_number'] ? (string)$row['contact_number'] : null,
            'createdTimestamp' => (string)$row['created_timestamp'],
            'lastLoginTimestamp' => $row['last_login_timestamp'] ? (string)$row['last_login_timestamp'] : null,
            'inviteSentAt' => $row['invite_sent_at'] ? (string)$row['invite_sent_at'] : null,
            'inviteExpiresAt' => $row['invite_expires_at'] ? (string)$row['invite_expires_at'] : null,
            'inviteAcceptedAt' => $row['invite_accepted_at'] ? (string)$row['invite_accepted_at'] : null,
        ];
    }

    private function normalizeRoleDesignation(string $roleDesignation): string
    {
        $normalized = strtoupper(trim($roleDesignation));
        if ($normalized === 'ADMIN') return RoleConstants::ROLE_ADMIN;
        if ($normalized === 'USER') return RoleConstants::ROLE_BORROWER;
        return $normalized ?: RoleConstants::ROLE_BORROWER;
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
            'SELECT account_identifier, status, is_approved, is_active
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
            'activate' => $this->canActivateAccount($accountStatus, $isApproved),
        ];
    }

    private function canUpdateAccount(string $accountStatus): bool
    {
        return $accountStatus === 'Active';
    }

    private function canDisableAccount(string $accountStatus): bool
    {
        return in_array($accountStatus, ['Active', 'Pending'], true);
    }

    private function canActivateAccount(string $accountStatus, bool $isApproved): bool
    {
        return $accountStatus === 'Disabled' && $isApproved;
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
