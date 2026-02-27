<?php

namespace App\Domain\Account\Service;

use App\Domain\Account\DTO\AccountProfileResponseDTO;
use App\Domain\Account\Entity\AccountEntity;
use App\Domain\Account\Repository\AccountRepository;
use App\Shared\Exceptions\DomainNotFoundException;

class AccountProfileService
{
    private AccountRepository $accountRepository;

    public function __construct(AccountRepository $accountRepository)
    {
        $this->accountRepository = $accountRepository;
    }

    // ===== AI GENERATED: getAccountProfileByEmail =====
    // Purpose: Retrieve account profile by email address
    // Inputs: emailAddress (string)
    // Returns: AccountProfileResponseDTO
    // Flow:
    // 1. Query repository by email
    // 2. Throw DomainNotFoundException if not found
    // 3. Transform entity to DTO

    public function getAccountProfileByEmail(string $emailAddress): AccountProfileResponseDTO
    {
        $accountEntity = $this->accountRepository->findOneByEmailAddress($emailAddress);

        if ($accountEntity === null) {
            throw new DomainNotFoundException('Account not found for email: ' . $emailAddress);
        }

        return $this->transformEntityToResponseDTO($accountEntity);
    }

    // ===== AI GENERATED: getAccountProfileById =====
    // Purpose: Retrieve account profile by account identifier
    // Inputs: accountIdentifier (int)
    // Returns: AccountProfileResponseDTO
    // Flow:
    // 1. Query repository by ID
    // 2. Throw DomainNotFoundException if not found
    // 3. Transform entity to DTO

    public function getAccountProfileById(int $accountIdentifier): AccountProfileResponseDTO
    {
        $accountEntity = $this->accountRepository->find($accountIdentifier);

        if ($accountEntity === null) {
            throw new DomainNotFoundException('Account not found for identifier: ' . $accountIdentifier);
        }

        return $this->transformEntityToResponseDTO($accountEntity);
    }

    // ===== AI GENERATED: getAllAccountProfiles =====
    // Purpose: Retrieve all account profiles (Admin only)
    // Inputs: none
    // Returns: AccountProfileResponseDTO[]
    // Flow:
    // 1. Query all accounts from repository
    // 2. Transform each entity to DTO
    // 3. Return array of DTOs

    /**
     * @return AccountProfileResponseDTO[]
     */
    public function getAllAccountProfiles(): array
    {
        $accountEntities = $this->accountRepository->findAllAccounts();
        $responseDTOs = [];

        foreach ($accountEntities as $accountEntity) { // entity → DTO loop
            $responseDTOs[] = $this->transformEntityToResponseDTO($accountEntity);
        }

        return $responseDTOs;
    }

    // ===== AI GENERATED: transformEntityToResponseDTO =====
    // Purpose: Map AccountEntity properties to AccountProfileResponseDTO
    // Inputs: accountEntity (AccountEntity)
    // Returns: AccountProfileResponseDTO
    // Flow:
    // 1. Extract properties from entity
    // 2. Format timestamps
    // 3. Return DTO

    private function transformEntityToResponseDTO(AccountEntity $accountEntity): AccountProfileResponseDTO
    {
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
