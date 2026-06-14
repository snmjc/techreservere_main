<?php

namespace App\Domain\Account\Service;

use App\Shared\Utils\DatabaseBoolean;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class AccountAccessService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AccountLifecyclePolicyService $accountLifecyclePolicyService,
        private readonly AccountReadService $accountReadService,
        private readonly AdminSecurityConfirmationService $adminSecurityConfirmationService
    ) {
    }

    public function updateAccess(int $accountIdentifier, bool $isActive, int $authenticatedAdminId, string $confirmedAdminEmail): array
    {
        $account = $this->accountReadService->getAccountStateById($accountIdentifier);
        if (!$account) {
            return $this->error('AccountNotFound', 'Account not found.', 404);
        }

        $currentIsApproved = DatabaseBoolean::toBool($account['is_approved'] ?? false);
        $currentStatus = $this->accountLifecyclePolicyService->resolveAccountStatus(
            DatabaseBoolean::toBool($account['is_active'] ?? false),
            (string)($account['status'] ?? ''),
            $currentIsApproved
        );

        $actionError = $this->validateRequestedAction($isActive, $currentStatus, $currentIsApproved);
        if ($actionError !== null) {
            return $actionError;
        }

        $confirmationMessage = $this->adminSecurityConfirmationService->validateAdminEmail(
            $authenticatedAdminId,
            $confirmedAdminEmail,
            $isActive ? 'reactivating' : 'deactivating'
        );

        if ($confirmationMessage !== null) {
            return $this->error('SecurityConfirmationFailed', $confirmationMessage, 422);
        }

        $this->persistAccessChange($accountIdentifier, $isActive, $currentIsApproved);

        return $this->success([
            'message' => $isActive ? 'Account reactivated.' : 'Account disabled.',
            'account' => $this->accountReadService->getMappedAccountById($accountIdentifier),
        ]);
    }

    private function validateRequestedAction(bool $isActive, string $currentStatus, bool $currentIsApproved): ?array
    {
        if ($isActive && !$this->accountLifecyclePolicyService->canActivateAccount($currentStatus)) {
            return $this->error(
                'AccountActionNotAllowed',
                'Only disabled accounts can be reactivated.',
                403,
                ['actionRules' => $this->accountLifecyclePolicyService->buildActionPermissions($currentStatus, $currentIsApproved)]
            );
        }

        if (!$isActive && !$this->accountLifecyclePolicyService->canDisableAccount($currentStatus)) {
            return $this->error(
                'AccountActionNotAllowed',
                'Only active accounts can be disabled.',
                403,
                ['actionRules' => $this->accountLifecyclePolicyService->buildActionPermissions($currentStatus, $currentIsApproved)]
            );
        }

        return null;
    }

    private function persistAccessChange(int $accountIdentifier, bool $isActive, bool $currentIsApproved): void
    {
        $this->connection->executeStatement(
            'UPDATE accounts
             SET is_active = :isActive, is_approved = :isApproved, status = :status, updated_timestamp = :updatedTimestamp
             WHERE account_identifier = :accountIdentifier',
            [
                'isActive' => $isActive,
                'isApproved' => $isActive ? true : $currentIsApproved,
                'status' => $isActive ? 'approved' : 'disabled',
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
