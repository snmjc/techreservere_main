<?php

namespace App\Domain\Account\Contract;

class AccountResponseContract
{
    // ===== AI GENERATED: AccountResponseContract =====
    // Purpose: Define inter-domain communication shape for account data
    // Inputs: none
    // Returns: none
    // Flow:
    // 1. Used when other domains need account information
    // 2. Prevents entity exposure across domain boundaries

    public int $accountIdentifier;
    public string $emailAddress;
    public string $roleDesignation;
    public string $fullName;

    public function __construct(
        int $accountIdentifier,
        string $emailAddress,
        string $roleDesignation,
        string $fullName
    ) {
        $this->accountIdentifier = $accountIdentifier;
        $this->emailAddress = $emailAddress;
        $this->roleDesignation = $roleDesignation;
        $this->fullName = $fullName;
    }
}
