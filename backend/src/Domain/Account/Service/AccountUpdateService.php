<?php

namespace App\Domain\Account\Service;

use App\Domain\Account\DTO\AccountProfileResponseDTO;
use App\Domain\Account\DTO\AccountUpdateRequestDTO;
use App\Domain\Account\Repository\AccountRepository;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;
use App\Shared\Utils\RoleConstants;

class AccountUpdateService
{
    private AccountRepository $accountRepository;

    public function __construct(AccountRepository $accountRepository)
    {
        $this->accountRepository = $accountRepository;
    }

    // ===== AI GENERATED: updateAccountProfile =====
    // Purpose: Update account profile fields (contact number, role)
    // Inputs: accountIdentifier (int), updateRequestDTO (AccountUpdateRequestDTO)
    // Returns: AccountProfileResponseDTO
    // Flow:
    // 1. Find account by ID
    // 2. Validate update fields
    // 3. Apply changes and persist
    // 4. Return updated DTO

    public function updateAccountProfile(
        int $accountIdentifier,
        AccountUpdateRequestDTO $updateRequestDTO
    ): AccountProfileResponseDTO {
        $accountEntity = $this->accountRepository->find($accountIdentifier);

        if ($accountEntity === null) {
            throw new DomainNotFoundException('Account not found for identifier: ' . $accountIdentifier);
        }

        if ($updateRequestDTO->contactNumber !== null) {
            if (strlen($updateRequestDTO->contactNumber) > 20) {
                throw new DomainValidationException('Contact number exceeds maximum length of 20 characters.');
            }
            $accountEntity->setContactNumber($updateRequestDTO->contactNumber);
        }

        if ($updateRequestDTO->roleDesignation !== null) {
            if (!in_array($updateRequestDTO->roleDesignation, RoleConstants::ALL_ROLES, true)) {
                throw new DomainValidationException('Invalid role designation: ' . $updateRequestDTO->roleDesignation);
            }
            $accountEntity->setRoleDesignation($updateRequestDTO->roleDesignation);
        }

        $this->accountRepository->persistAccount($accountEntity);

        return new AccountProfileResponseDTO(
            accountIdentifier: $accountEntity->getAccountIdentifier(),
            lastName: $accountEntity->getLastName(),
            firstName: $accountEntity->getFirstName(),
            emailAddress: $accountEntity->getEmailAddress(),
            roleDesignation: $accountEntity->getRoleDesignation(),
            contactNumber: $accountEntity->getContactNumber(),
            lastLoginTimestamp: $accountEntity->getLastLoginTimestamp()?->format(\DateTime::ATOM),
            createdTimestamp: $accountEntity->getCreatedTimestamp()->format(\DateTime::ATOM),
            isActive: $accountEntity->getIsActive()
        );
    }
}
