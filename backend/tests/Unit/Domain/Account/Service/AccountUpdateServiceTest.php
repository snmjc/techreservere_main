<?php

namespace App\Tests\Unit\Domain\Account\Service;

use App\Domain\Account\DTO\AccountProfileResponseDTO;
use App\Domain\Account\DTO\AccountUpdateRequestDTO;
use App\Domain\Account\Entity\AccountEntity;
use App\Domain\Account\Repository\AccountRepository;
use App\Domain\Account\Service\AccountUpdateService;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;
use App\Shared\Utils\RoleConstants;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

// ===== AI GENERATED: AccountUpdateServiceTest =====
// Purpose: Unit tests for AccountUpdateService
// Tests: updateAccountProfile success, not-found, invalid role

#[AllowMockObjectsWithoutExpectations]
class AccountUpdateServiceTest extends TestCase
{
    private AccountRepository|MockObject $accountRepository;
    private AccountUpdateService $service;

    protected function setUp(): void
    {
        $this->accountRepository = $this->createMock(AccountRepository::class);
        $this->service = new AccountUpdateService($this->accountRepository);
    }

    public function testUpdateProfileSuccessfully(): void
    {
        $entity = $this->createRealAccountEntity(1, 'Doe', 'John', 'john@techreserve.edu.ph', RoleConstants::ROLE_BORROWER);

        $this->accountRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($entity);

        $this->accountRepository
            ->expects($this->once())
            ->method('persistAccount')
            ->with($entity);

        $updateDTO = new AccountUpdateRequestDTO(
            contactNumber: '09171234567',
            roleDesignation: null
        );

        $result = $this->service->updateAccountProfile(1, $updateDTO);

        $this->assertInstanceOf(AccountProfileResponseDTO::class, $result);
        $this->assertSame(1, $result->accountIdentifier);
    }

    public function testUpdateProfileThrowsNotFoundWhenMissing(): void
    {
        $this->accountRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $updateDTO = new AccountUpdateRequestDTO(
            contactNumber: '09171234567',
            roleDesignation: null
        );

        $this->expectException(DomainNotFoundException::class);
        $this->service->updateAccountProfile(999, $updateDTO);
    }

    public function testUpdateProfileWithInvalidRoleThrowsValidationError(): void
    {
        $entity = $this->createRealAccountEntity(1, 'Doe', 'John', 'john@techreserve.edu.ph', RoleConstants::ROLE_BORROWER);

        $this->accountRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($entity);

        $updateDTO = new AccountUpdateRequestDTO(
            contactNumber: null,
            roleDesignation: 'InvalidRole'
        );

        $this->expectException(DomainValidationException::class);
        $this->service->updateAccountProfile(1, $updateDTO);
    }

    public function testUpdateProfileWithValidRoleUpdatesRole(): void
    {
        $entity = $this->createRealAccountEntity(2, 'Smith', 'Jane', 'jane@techreserve.edu.ph', RoleConstants::ROLE_ADMIN);

        $entity
            ->expects($this->once())
            ->method('setRoleDesignation')
            ->with(RoleConstants::ROLE_ADMIN)
            ->willReturnSelf();

        $this->accountRepository
            ->expects($this->once())
            ->method('find')
            ->with(2)
            ->willReturn($entity);

        $this->accountRepository
            ->expects($this->once())
            ->method('persistAccount')
            ->with($entity);

        $updateDTO = new AccountUpdateRequestDTO(
            contactNumber: null,
            roleDesignation: RoleConstants::ROLE_ADMIN
        );

        $result = $this->service->updateAccountProfile(2, $updateDTO);

        $this->assertInstanceOf(AccountProfileResponseDTO::class, $result);
        $this->assertSame(RoleConstants::ROLE_ADMIN, $result->roleDesignation);
    }

    private function createRealAccountEntity(int $id, string $lastName, string $firstName, string $email, string $role): AccountEntity
    {
        $entity = $this->createMock(AccountEntity::class);
        $entity->method('getAccountIdentifier')->willReturn($id);
        $entity->method('getLastName')->willReturn($lastName);
        $entity->method('getFirstName')->willReturn($firstName);
        $entity->method('getEmailAddress')->willReturn($email);
        $entity->method('getRoleDesignation')->willReturn($role);
        $entity->method('getContactNumber')->willReturn('09000000000');
        $entity->method('getClerkUserId')->willReturn('clerk_' . $id);
        $entity->method('getIsActive')->willReturn(true);
        $entity->method('getCreatedTimestamp')->willReturn(new \DateTime('2025-01-01'));
        $entity->method('getUpdatedTimestamp')->willReturn(new \DateTime('2025-01-01'));
        $entity->method('setContactNumber')->willReturnSelf();
        $entity->method('setRoleDesignation')->willReturnSelf();

        return $entity;
    }
}
