<?php

namespace App\Domain\Account\Service;

use App\Domain\Account\DTO\AccountUpdateRequestDTO;
use Symfony\Component\HttpFoundation\Request;

class AccountWorkflowService
{
    public function __construct(
        private readonly AccountProfileService $accountProfileService,
        private readonly AccountReadService $accountReadService,
        private readonly AccountSelfService $accountSelfService,
        private readonly AccountUpdateService $accountUpdateService,
        private readonly AccountAccessService $accountAccessService,
        private readonly AdminAccountDetailsService $adminAccountDetailsService,
        private readonly AccountDeletionService $accountDeletionService,
        private readonly AdminSecurityConfirmationService $adminSecurityConfirmationService,
        private readonly AuthenticatedAccountResolver $authenticatedAccountResolver
    ) {
    }

    public function getMyProfile(array $authenticatedIdentity): array
    {
        $emailAddress = $authenticatedIdentity['emailAddress'] ?? '';
        $profileDTO = $this->accountProfileService->getAccountProfileByEmail($emailAddress);

        return $this->success($profileDTO->toResponseArray());
    }

    public function getMySettings(Request $request): array
    {
        return $this->accountSelfService->getSettings($this->resolveAuthenticatedAccountIdentifier($request));
    }

    public function updateMySettings(Request $request, mixed $requestBody): array
    {
        return $this->accountSelfService->updateSettings(
            $this->resolveAuthenticatedAccountIdentifier($request),
            $requestBody
        );
    }

    public function updateMyPassword(Request $request, mixed $requestBody): array
    {
        return $this->accountSelfService->updatePassword(
            $this->resolveAuthenticatedAccountIdentifier($request),
            $requestBody
        );
    }

    public function syncPasswordFromClerk(Request $request, mixed $requestBody): array
    {
        return $this->accountSelfService->syncPasswordFromClerk(
            $this->resolveAuthenticatedAccountIdentifier($request),
            $requestBody
        );
    }

    public function getAllAccounts(): array
    {
        return $this->success([
            'accounts' => $this->accountReadService->getAcceptedAccounts(),
        ]);
    }

    public function getAccountById(int $accountIdentifier): array
    {
        $profileDTO = $this->accountProfileService->getAccountProfileById($accountIdentifier);

        return $this->success($profileDTO->toResponseArray());
    }

    public function updateAccount(int $accountIdentifier, array $requestBody): array
    {
        $updateDTO = new AccountUpdateRequestDTO(
            contactNumber: $requestBody['contactNumber'] ?? null,
            roleDesignation: $requestBody['roleDesignation'] ?? null
        );
        $updatedProfile = $this->accountUpdateService->updateAccountProfile($accountIdentifier, $updateDTO);

        return $this->success($updatedProfile->toResponseArray());
    }

    public function getEmployeeWorkLogs(int $accountIdentifier): array
    {
        $account = $this->accountReadService->getAccountStateById($accountIdentifier);
        if (!$account) {
            return $this->error('AccountNotFound', 'Account not found.', 404);
        }

        $mappedAccount = $this->accountReadService->getMappedAccountById($accountIdentifier);
        if (($mappedAccount['accountType'] ?? '') !== 'Employee') {
            return $this->error(
                'WorkLogsUnavailable',
                'Work logs are only available for employee accounts.',
                422
            );
        }

        return $this->success([
            'account' => $mappedAccount,
            'workLogs' => $this->accountReadService->getEmployeeWorkLogs($accountIdentifier),
        ]);
    }

    public function updateAdminAccountDetails(int $accountIdentifier, array $requestBody): array
    {
        return $this->adminAccountDetailsService->updateDetails($accountIdentifier, $requestBody);
    }

    public function updateAccountAccess(int $accountIdentifier, Request $request, array $requestBody): array
    {
        return $this->accountAccessService->updateAccess(
            $accountIdentifier,
            (bool)($requestBody['isActive'] ?? false),
            $this->resolveAuthenticatedAccountIdentifier($request),
            (string)($requestBody['confirmedAdminEmail'] ?? '')
        );
    }

    public function deleteAccount(int $accountIdentifier, Request $request, array $requestBody): array
    {
        $account = $this->accountReadService->getAccountStateById($accountIdentifier);
        if (!$account) {
            return $this->error('AccountNotFound', 'Account not found.', 404);
        }

        $authenticatedAdminId = $this->resolveAuthenticatedAccountIdentifier($request);
        if ($authenticatedAdminId === $accountIdentifier) {
            return $this->error('AccountActionNotAllowed', 'You cannot delete your own signed-in account.', 403);
        }

        $securityError = $this->adminSecurityConfirmationService->validateAdminCredentials(
            $authenticatedAdminId,
            (string)($requestBody['confirmedAdminEmail'] ?? ''),
            (string)($requestBody['confirmedAdminPassword'] ?? ''),
            'deleting'
        );

        if ($securityError !== null) {
            return $this->error('SecurityConfirmationFailed', $securityError, 422);
        }

        try {
            $deletedRows = $this->accountDeletionService->deleteAccount($account, $accountIdentifier);
        } catch (\Throwable $exception) {
            return $this->error(
                'DeleteAccountFailed',
                'Unable to delete account: ' . $exception->getMessage(),
                500
            );
        }

        if ($deletedRows === 0) {
            return $this->error('AccountNotFound', 'Account not found.', 404);
        }

        return $this->success([
            'message' => 'Account deleted.',
            'accountIdentifier' => $accountIdentifier,
        ]);
    }

    private function resolveAuthenticatedAccountIdentifier(Request $request): int
    {
        return $this->authenticatedAccountResolver->resolveAccountIdentifier($request);
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
