<?php

namespace App\Domain\Account\DTO;

class AccountProfileResponseDTO
{
    // ===== AI GENERATED: AccountProfileResponseDTO =====
    // Purpose: Response DTO for account profile data
    // Inputs: entity properties
    // Returns: normalized array
    // Flow:
    // 1. Maps entity properties to transport shape
    // 2. Returned by controller as JSON response

    public int $accountIdentifier;
    public string $lastName;
    public string $firstName;
    public string $emailAddress;
    public string $roleDesignation;
    public ?string $contactNumber;
    public ?string $lastLoginTimestamp;
    public string $createdTimestamp;
    public bool $isActive;

    public function __construct(
        int $accountIdentifier,
        string $lastName,
        string $firstName,
        string $emailAddress,
        string $roleDesignation,
        ?string $contactNumber,
        ?string $lastLoginTimestamp,
        string $createdTimestamp,
        bool $isActive
    ) {
        $this->accountIdentifier = $accountIdentifier;
        $this->lastName = $lastName;
        $this->firstName = $firstName;
        $this->emailAddress = $emailAddress;
        $this->roleDesignation = $roleDesignation;
        $this->contactNumber = $contactNumber;
        $this->lastLoginTimestamp = $lastLoginTimestamp;
        $this->createdTimestamp = $createdTimestamp;
        $this->isActive = $isActive;
    }

    public function toResponseArray(): array
    {
        return [
            'accountIdentifier' => $this->accountIdentifier,
            'lastName' => $this->lastName,
            'firstName' => $this->firstName,
            'emailAddress' => $this->emailAddress,
            'roleDesignation' => $this->roleDesignation,
            'contactNumber' => $this->contactNumber,
            'lastLoginTimestamp' => $this->lastLoginTimestamp,
            'createdTimestamp' => $this->createdTimestamp,
            'isActive' => $this->isActive,
        ];
    }
}
