<?php

namespace App\Domain\Account\Service;

use App\Domain\Account\DTO\AccountUpdateRequestDTO;
use App\Domain\AuditLog\Service\AuditLogRecordService;
use Psr\Log\LoggerInterface;
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
        private readonly AuditLogRecordService $auditLogRecordService,
        private readonly AdminSecurityConfirmationService $adminSecurityConfirmationService,
        private readonly AuthenticatedAccountResolver $authenticatedAccountResolver,
        private readonly LoggerInterface $logger
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

    public function getAccountsByType(string $accountType): array
    {
        $normalizedType = strtolower(trim($accountType));
        if (!in_array($normalizedType, ['admin', 'user', 'employee'], true)) {
            return $this->error('AccountTypeInvalid', 'Unsupported account type.', 422);
        }

        return $this->success([
            'accountType' => $normalizedType,
            'accounts' => $this->accountReadService->getAcceptedAccountsByType($normalizedType),
        ]);
    }

    public function getAccountById(int $accountIdentifier): array
    {
        $account = $this->accountReadService->getMappedAccountById($accountIdentifier);
        if ($account === null) {
            return $this->error('AccountNotFound', 'Account not found.', 404);
        }

        return $this->success(['account' => $account]);
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
        return $this->buildEmployeeWorkLogsResponse($accountIdentifier);
    }

    public function getMyEmployeeWorkLogs(Request $request): array
    {
        return $this->buildEmployeeWorkLogsResponse($this->resolveAuthenticatedAccountIdentifier($request));
    }

    private function buildEmployeeWorkLogsResponse(int $accountIdentifier): array
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

    public function updateAdminAccountDetails(int $accountIdentifier, Request $request, array $requestBody): array
    {
        $beforeAccount = $this->accountReadService->getMappedAccountById($accountIdentifier);
        try {
            $result = $this->adminAccountDetailsService->updateDetails($accountIdentifier, $requestBody);
        } catch (\Throwable $exception) {
            return $this->error('AccountUpdateFailed', 'Unable to update account: ' . $exception->getMessage(), 500);
        }

        if (($result['success'] ?? false) === true) {
            $this->tryRecordAuditLog(
                $this->resolveAuthenticatedAccountIdentifier($request),
                'ACCOUNT_UPDATED',
                'account',
                $accountIdentifier,
                $this->buildAccountUpdateAuditDetails($beforeAccount, $result['data']['account'] ?? [])
            );
        }

        return $result;
    }

    public function updateAccountAccess(int $accountIdentifier, Request $request, array $requestBody): array
    {
        $beforeAccount = $this->accountReadService->getMappedAccountById($accountIdentifier);
        try {
            $result = $this->accountAccessService->updateAccess(
                $accountIdentifier,
                (bool)($requestBody['isActive'] ?? false),
                $this->resolveAuthenticatedAccountIdentifier($request),
                (string)($requestBody['confirmedAdminEmail'] ?? '')
            );
        } catch (\Throwable $exception) {
            return $this->error('AccountAccessUpdateFailed', 'Unable to update account access: ' . $exception->getMessage(), 500);
        }

        if (($result['success'] ?? false) === true) {
            $isActive = (bool)($requestBody['isActive'] ?? false);
            $this->tryRecordAuditLog(
                $this->resolveAuthenticatedAccountIdentifier($request),
                $isActive ? 'ACCOUNT_REACTIVATED' : 'ACCOUNT_DEACTIVATED',
                'account',
                $accountIdentifier,
                $this->buildAccountAccessAuditDetails($beforeAccount, $result['data']['account'] ?? [], $isActive)
            );
        }

        return $result;
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

        $mappedAccount = $this->accountReadService->getMappedAccountById($accountIdentifier);

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

        $this->tryRecordAuditLog(
            $authenticatedAdminId,
            'ACCOUNT_DELETED',
            'account',
            $accountIdentifier,
            $this->buildAccountDeletionAuditDetails($mappedAccount, $account)
        );

        return $this->success([
            'message' => 'Account deleted.',
            'accountIdentifier' => $accountIdentifier,
        ]);
    }

    private function buildAccountUpdateAuditDetails(?array $beforeAccount, array $afterAccount): array
    {
        return [
            'before' => $this->filterAccountAuditSnapshot($beforeAccount),
            'after' => $this->filterAccountAuditSnapshot($afterAccount),
            'changedFields' => $this->detectChangedFields(
                $beforeAccount,
                $afterAccount,
                ['firstName', 'lastName', 'contactNumber', 'profilePhotoData']
            ),
        ];
    }

    private function buildAccountAccessAuditDetails(?array $beforeAccount, array $afterAccount, bool $isActive): array
    {
        return [
            'action' => $isActive ? 'reactivate' : 'deactivate',
            'before' => $this->filterAccountAuditSnapshot($beforeAccount),
            'after' => $this->filterAccountAuditSnapshot($afterAccount),
            'changedFields' => $this->detectChangedFields(
                $beforeAccount,
                $afterAccount,
                ['accountStatus', 'isActive']
            ),
        ];
    }

    private function buildAccountDeletionAuditDetails(?array $mappedAccount, array $rawAccount): array
    {
        return [
            'deletedAccount' => $this->filterAccountAuditSnapshot($mappedAccount),
            'raw' => [
                'emailAddress' => (string)($rawAccount['email_address'] ?? ''),
                'roleDesignation' => (string)($rawAccount['role_designation'] ?? ''),
                'status' => (string)($rawAccount['status'] ?? ''),
                'clerkUserId' => (string)($rawAccount['clerk_user_id'] ?? ''),
            ],
        ];
    }

    private function filterAccountAuditSnapshot(?array $account): array
    {
        if (!$account) {
            return [];
        }

        return [
            'accountIdentifier' => $account['accountIdentifier'] ?? null,
            'idNumber' => $account['rawIdNumber'] ?? $account['idNumber'] ?? null,
            'firstName' => $account['firstName'] ?? null,
            'lastName' => $account['lastName'] ?? null,
            'fullName' => $account['fullName'] ?? null,
            'emailAddress' => $account['emailAddress'] ?? null,
            'contactNumber' => $account['contactNumber'] ?? null,
            'accountType' => $account['accountType'] ?? null,
            'roleLabel' => $account['roleLabel'] ?? null,
            'roleDesignation' => $account['roleDesignation'] ?? null,
            'accountStatus' => $account['accountStatus'] ?? null,
            'isActive' => $account['isActive'] ?? null,
        ];
    }

    private function detectChangedFields(?array $beforeAccount, array $afterAccount, array $fields): array
    {
        $changes = [];

        foreach ($fields as $field) {
            $before = $beforeAccount[$field] ?? null;
            $after = $afterAccount[$field] ?? null;

            if ($before !== $after) {
                $changes[$field] = [
                    'before' => $this->formatAuditFieldValue($field, $before),
                    'after' => $this->formatAuditFieldValue($field, $after),
                ];
            }
        }

        return $changes;
    }

    private function formatAuditFieldValue(string $field, mixed $value): mixed
    {
        if ($field === 'profilePhotoData') {
            return $value ? '[updated]' : null;
        }

        return $value;
    }

    private function tryRecordAuditLog(
        ?int $performedByAccountId,
        string $actionPerformed,
        string $targetEntityType,
        ?int $targetEntityIdentifier = null,
        ?array $changeDetails = null
    ): void {
        try {
            $this->auditLogRecordService->recordAuditLog(
                $performedByAccountId,
                $actionPerformed,
                $targetEntityType,
                $targetEntityIdentifier,
                $changeDetails
            );
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to record account audit log.', [
                'performedByAccountId' => $performedByAccountId,
                'actionPerformed' => $actionPerformed,
                'targetEntityType' => $targetEntityType,
                'targetEntityIdentifier' => $targetEntityIdentifier,
                'error' => $exception->getMessage(),
            ]);
        }
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
