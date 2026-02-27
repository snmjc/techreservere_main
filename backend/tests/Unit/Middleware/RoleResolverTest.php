<?php

namespace App\Tests\Unit\Middleware;

use App\Domain\Account\Entity\AccountEntity;
use App\Domain\Account\Repository\AccountRepository;
use App\Middleware\RoleResolver;
use App\Shared\Utils\RoleConstants;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

// ===== AI GENERATED: RoleResolverTest =====
// Purpose: Unit tests for RoleResolver middleware component
// Tests: resolve admin role, resolve borrower role, default when missing, default when empty email

class RoleResolverTest extends TestCase
{
    private AccountRepository|MockObject $accountRepository;
    private RoleResolver $roleResolver;

    protected function setUp(): void
    {
        $this->accountRepository = $this->createMock(AccountRepository::class);
        $this->roleResolver = new RoleResolver($this->accountRepository);
    }

    public function testResolvesAdminRoleFromDatabase(): void
    {
        $entity = $this->createMock(AccountEntity::class);
        $entity->method('getRoleDesignation')->willReturn(RoleConstants::ROLE_ADMIN);

        $this->accountRepository
            ->expects($this->once())
            ->method('findOneByEmailAddress')
            ->with('admin@techreserve.edu.ph')
            ->willReturn($entity);

        $role = $this->roleResolver->resolveRoleFromIdentity([
            'emailAddress' => 'admin@techreserve.edu.ph',
        ]);

        $this->assertSame(RoleConstants::ROLE_ADMIN, $role);
    }

    public function testResolvesBorrowerRoleFromDatabase(): void
    {
        $entity = $this->createMock(AccountEntity::class);
        $entity->method('getRoleDesignation')->willReturn(RoleConstants::ROLE_BORROWER);

        $this->accountRepository
            ->expects($this->once())
            ->method('findOneByEmailAddress')
            ->with('student@techreserve.edu.ph')
            ->willReturn($entity);

        $role = $this->roleResolver->resolveRoleFromIdentity([
            'emailAddress' => 'student@techreserve.edu.ph',
        ]);

        $this->assertSame(RoleConstants::ROLE_BORROWER, $role);
    }

    public function testDefaultsToBorrowerWhenAccountNotFound(): void
    {
        $this->accountRepository
            ->expects($this->once())
            ->method('findOneByEmailAddress')
            ->with('unknown@example.com')
            ->willReturn(null);

        $role = $this->roleResolver->resolveRoleFromIdentity([
            'emailAddress' => 'unknown@example.com',
        ]);

        $this->assertSame(RoleConstants::ROLE_BORROWER, $role);
    }

    public function testDefaultsToBorrowerWhenEmailIsEmpty(): void
    {
        $this->accountRepository
            ->expects($this->never())
            ->method('findOneByEmailAddress');

        $role = $this->roleResolver->resolveRoleFromIdentity([
            'emailAddress' => '',
        ]);

        $this->assertSame(RoleConstants::ROLE_BORROWER, $role);
    }

    public function testDefaultsToBorrowerWhenEmailKeyMissing(): void
    {
        $this->accountRepository
            ->expects($this->never())
            ->method('findOneByEmailAddress');

        $role = $this->roleResolver->resolveRoleFromIdentity([]);

        $this->assertSame(RoleConstants::ROLE_BORROWER, $role);
    }
}
