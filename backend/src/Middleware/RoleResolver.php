<?php

namespace App\Middleware;

use App\Domain\Account\Repository\AccountRepository;
use App\Shared\Utils\RoleConstants;

class RoleResolver
{
    // ===== AI GENERATED: RoleResolver =====
    // Purpose: Map authenticated identity to internal role
    // Inputs: authenticatedIdentity (array)
    // Returns: string (role constant)
    // Flow:
    // 1. Look up user in Account repository by email
    // 2. Return role from database record
    // 3. Default to ROLE_BORROWER if no explicit role found

    private AccountRepository $accountRepository;

    public function __construct(AccountRepository $accountRepository)
    {
        $this->accountRepository = $accountRepository;
    }

    // ===== AI GENERATED: resolveRoleFromIdentity =====
    // Purpose: Resolve internal role from Clerk identity
    // Inputs: authenticatedIdentity (array with emailAddress)
    // Returns: string (role constant)
    // Flow:
    // 1. Query account by email address
    // 2. Return stored role designation
    // 3. Default ROLE_BORROWER if account not found

    public function resolveRoleFromIdentity(array $authenticatedIdentity): string
    {
        $emailAddress = $authenticatedIdentity['emailAddress'] ?? '';

        if (empty($emailAddress)) {
            return RoleConstants::ROLE_BORROWER;
        }

        $accountEntity = $this->accountRepository->findOneByEmailAddress($emailAddress);

        if ($accountEntity === null) {
            return RoleConstants::ROLE_BORROWER;
        }

        return $accountEntity->getRoleDesignation();
    }
}
