<?php

namespace App\Infrastructure\Auth;

use App\Domain\Account\Entity\AccountEntity;
use App\Shared\Utils\RoleDesignationNormalizer;

class AccountIdentityBuilder
{
    public function validateApprovedAccount(AccountEntity $account): void
    {
        $normalizedStatus = strtolower(trim((string)$account->getStatus()));

        if (!in_array($normalizedStatus, ['active', 'approved', 'accepted'], true)) {
            throw new ClerkVerificationFailedException('Account status is ' . $account->getStatus() . '. Only active invitation accounts can access the system.');
        }

        if (!$account->getIsActive()) {
            throw new ClerkVerificationFailedException('Account is disabled. Please contact an administrator.');
        }
    }

    public function build(AccountEntity $account, ?string $clerkUserId = null): array
    {
        $identity = [
            'accountIdentifier' => $account->getAccountIdentifier(),
            'emailAddress' => $account->getEmailAddress(),
            'firstName' => $account->getFirstName(),
            'lastName' => $account->getLastName(),
            'roleDesignation' => $account->getRoleDesignation(),
            'status' => $account->getStatus(),
            'isApproved' => $account->getIsApproved(),
            'isVerified' => $account->getIsVerified(),
        ];

        if ($clerkUserId !== null) {
            $identity['clerkUserId'] = $clerkUserId;
        }

        $identity['roleDesignation'] = RoleDesignationNormalizer::normalize($identity['roleDesignation']);

        return $identity;
    }
}
