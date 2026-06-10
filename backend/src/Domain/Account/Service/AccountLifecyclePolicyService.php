<?php

namespace App\Domain\Account\Service;

class AccountLifecyclePolicyService
{
    public function resolveAccountStatus(bool $isActive, string $status, bool $isApproved): string
    {
        $normalized = strtolower(trim($status));
        if (!$isActive || in_array($normalized, ['disabled', 'inactive', 'suspended'], true)) {
            return 'Disabled';
        }

        if ($normalized === 'pending' || $normalized === 'invited' || !$isApproved) {
            return 'Pending';
        }

        return 'Active';
    }

    public function buildActionPermissions(string $accountStatus, bool $isApproved): array
    {
        return [
            'view' => true,
            'update' => $this->canUpdateAccount($accountStatus),
            'disable' => $this->canDisableAccount($accountStatus),
            'activate' => $this->canActivateAccount($accountStatus),
        ];
    }

    public function canUpdateAccount(string $accountStatus): bool
    {
        return $accountStatus === 'Active';
    }

    public function canDisableAccount(string $accountStatus): bool
    {
        return $accountStatus === 'Active';
    }

    public function canActivateAccount(string $accountStatus): bool
    {
        return $accountStatus === 'Disabled';
    }
}
