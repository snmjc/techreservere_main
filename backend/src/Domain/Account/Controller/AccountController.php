<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\DTO\AccountUpdateRequestDTO;
use App\Domain\Account\Service\AccountAccessService;
use App\Domain\Account\Service\AccountDeletionService;
use App\Domain\Account\Service\AccountProfileService;
use App\Domain\Account\Service\AccountReadService;
use App\Domain\Account\Service\AccountSelfService;
use App\Domain\Account\Service\AccountSettingsValidationService;
use App\Domain\Account\Service\AccountUpdateService;
use App\Domain\Account\Service\AdminAccountDetailsService;
use App\Domain\Account\Service\AdminSecurityConfirmationService;
use App\Domain\Account\Service\AuthenticatedAccountResolver;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/accounts')]
class AccountController extends AbstractController
{
    use JsonResponseTrait;

    private AccountProfileService $accountProfileService;
    private AccountReadService $accountReadService;
    private AccountSelfService $accountSelfService;
    private AccountUpdateService $accountUpdateService;
    private AccountAccessService $accountAccessService;
    private AdminAccountDetailsService $adminAccountDetailsService;
    private AccountDeletionService $accountDeletionService;
    private AdminSecurityConfirmationService $adminSecurityConfirmationService;
    private AuthenticatedAccountResolver $authenticatedAccountResolver;

    public function __construct(
        AccountProfileService $accountProfileService,
        AccountReadService $accountReadService,
        AccountSelfService $accountSelfService,
        AccountUpdateService $accountUpdateService,
        AccountAccessService $accountAccessService,
        AdminAccountDetailsService $adminAccountDetailsService,
        AccountDeletionService $accountDeletionService,
        AdminSecurityConfirmationService $adminSecurityConfirmationService,
        AuthenticatedAccountResolver $authenticatedAccountResolver
    ) {
        $this->accountProfileService = $accountProfileService;
        $this->accountReadService = $accountReadService;
        $this->accountSelfService = $accountSelfService;
        $this->accountUpdateService = $accountUpdateService;
        $this->accountAccessService = $accountAccessService;
        $this->adminAccountDetailsService = $adminAccountDetailsService;
        $this->accountDeletionService = $accountDeletionService;
        $this->adminSecurityConfirmationService = $adminSecurityConfirmationService;
        $this->authenticatedAccountResolver = $authenticatedAccountResolver;
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
        return $this->serviceResultResponse(
            $this->accountSelfService->getSettings($this->resolveAuthenticatedAccountIdentifier($request))
        );
    }

    #[Route('/me/settings', name: 'account_update_my_settings', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function updateMySettings(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->accountSelfService->updateSettings(
                $this->resolveAuthenticatedAccountIdentifier($request),
                json_decode($request->getContent(), true)
            )
        );
    }

    #[Route('/me/password', name: 'account_update_my_password', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function updateMyPassword(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->accountSelfService->updatePassword(
                $this->resolveAuthenticatedAccountIdentifier($request),
                json_decode($request->getContent(), true)
            )
        );
    }

    #[Route('/me/password/sync-from-clerk', name: 'account_sync_clerk_password', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function syncPasswordFromClerk(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->accountSelfService->syncPasswordFromClerk(
                $this->resolveAuthenticatedAccountIdentifier($request),
                json_decode($request->getContent(), true)
            )
        );
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
        return $this->serviceResultResponse(
            $this->adminAccountDetailsService->updateDetails($accountIdentifier, $requestBody)
        );
    }

    #[Route('/{accountIdentifier}/access', name: 'account_update_access', requirements: ['accountIdentifier' => '\d+'], methods: ['PATCH'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateAccountAccess(int $accountIdentifier, Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        return $this->serviceResultResponse(
            $this->accountAccessService->updateAccess(
                $accountIdentifier,
                (bool)($requestBody['isActive'] ?? false),
                $this->resolveAuthenticatedAccountIdentifier($request),
                (string)($requestBody['confirmedAdminEmail'] ?? '')
            )
        );
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

    private function serviceResultResponse(array $result): JsonResponse
    {
        if (($result['success'] ?? false) !== true) {
            return $this->createErrorResponse(
                (string)($result['errorCode'] ?? 'RequestFailed'),
                (string)($result['message'] ?? 'Unable to complete the request.'),
                (int)($result['status'] ?? 500),
                $result['extra'] ?? []
            );
        }

        return $this->createSuccessResponse($result['data'] ?? [], (int)($result['status'] ?? 200));
    }
}
