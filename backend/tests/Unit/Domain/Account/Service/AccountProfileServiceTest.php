<?php

namespace App\Tests\Unit\Domain\Account\Service;

use App\Domain\Account\DTO\AccountProfileResponseDTO;
use App\Domain\Account\Entity\AccountEntity;
use App\Domain\Account\Repository\AccountRepository;
use App\Domain\Account\Service\AccountProfileService;
use App\Shared\Exceptions\DomainNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

// ===== AI GENERATED: AccountProfileServiceTest =====
// Purpose: Unit tests for AccountProfileService
// Tests: getProfileByEmail, getProfileById, getAllProfiles, not-found paths

class AccountProfileServiceTest extends TestCase
{
    private AccountRepository|MockObject $accountRepository;
    private AccountProfileService $service;

    protected function setUp(): void
    {
        $this->accountRepository = $this->createMock(AccountRepository::class);
        $this->service = new AccountProfileService($this->accountRepository);
    }

    public function testGetProfileByEmailReturnsDTO(): void
    {
        $entity = $this->createAccountEntity(1, 'Doe', 'John', 'john@techreserve.edu.ph', 'Admin');

        $this->accountRepository
            ->expects($this->once())
            ->method('findOneByEmailAddress')
            ->with('john@techreserve.edu.ph')
            ->willReturn($entity);

        $result = $this->service->getProfileByEmail('john@techreserve.edu.ph');

        $this->assertInstanceOf(AccountProfileResponseDTO::class, $result);
        $this->assertSame(1, $result->accountIdentifier);
        $this->assertSame('John', $result->firstName);
        $this->assertSame('Doe', $result->lastName);
        $this->assertSame('john@techreserve.edu.ph', $result->emailAddress);
        $this->assertSame('Admin', $result->roleDesignation);
    }

    public function testGetProfileByEmailThrowsNotFoundWhenMissing(): void
    {
        $this->accountRepository
            ->expects($this->once())
            ->method('findOneByEmailAddress')
            ->with('missing@example.com')
            ->willReturn(null);

        $this->expectException(DomainNotFoundException::class);
        $this->service->getProfileByEmail('missing@example.com');
    }

    public function testGetProfileByIdReturnsDTO(): void
    {
        $entity = $this->createAccountEntity(5, 'Smith', 'Jane', 'jane@techreserve.edu.ph', 'Borrower');

        $this->accountRepository
            ->expects($this->once())
            ->method('find')
            ->with(5)
            ->willReturn($entity);

        $result = $this->service->getProfileById(5);

        $this->assertInstanceOf(AccountProfileResponseDTO::class, $result);
        $this->assertSame(5, $result->accountIdentifier);
        $this->assertSame('jane@techreserve.edu.ph', $result->emailAddress);
    }

    public function testGetProfileByIdThrowsNotFoundWhenMissing(): void
    {
        $this->accountRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(DomainNotFoundException::class);
        $this->service->getProfileById(999);
    }

    public function testGetAllProfilesReturnsArrayOfDTOs(): void
    {
        $entity1 = $this->createAccountEntity(1, 'A', 'User', 'a@techreserve.edu.ph', 'Admin');
        $entity2 = $this->createAccountEntity(2, 'B', 'User', 'b@techreserve.edu.ph', 'Borrower');

        $this->accountRepository
            ->expects($this->once())
            ->method('findAllAccounts')
            ->willReturn([$entity1, $entity2]);

        $results = $this->service->getAllProfiles();

        $this->assertCount(2, $results);
        $this->assertInstanceOf(AccountProfileResponseDTO::class, $results[0]);
        $this->assertInstanceOf(AccountProfileResponseDTO::class, $results[1]);
        $this->assertSame(1, $results[0]->accountIdentifier);
        $this->assertSame(2, $results[1]->accountIdentifier);
    }

    public function testGetAllProfilesReturnsEmptyArrayWhenNoAccounts(): void
    {
        $this->accountRepository
            ->expects($this->once())
            ->method('findAllAccounts')
            ->willReturn([]);

        $results = $this->service->getAllProfiles();

        $this->assertIsArray($results);
        $this->assertCount(0, $results);
    }

    private function createAccountEntity(int $id, string $lastName, string $firstName, string $email, string $role): AccountEntity
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

        return $entity;
    }
}
