<?php

namespace App\Domain\Account\DTO;

class AccountUpdateRequestDTO
{
    // ===== AI GENERATED: AccountUpdateRequestDTO =====
    // Purpose: Request DTO for updating account profile
    // Inputs: contactNumber (string|null), roleDesignation (string|null)
    // Returns: none (transport object)
    // Flow:
    // 1. Validated at controller level
    // 2. Passed to AccountUpdateService

    public ?string $contactNumber;
    public ?string $roleDesignation;

    public function __construct(?string $contactNumber = null, ?string $roleDesignation = null)
    {
        $this->contactNumber = $contactNumber;
        $this->roleDesignation = $roleDesignation;
    }
}
