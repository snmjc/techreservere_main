<?php

namespace App\Tests\Unit\Infrastructure\Auth;

use App\Domain\Account\Entity\AccountEntity;
use App\Infrastructure\Auth\AccountIdentityBuilder;
use App\Infrastructure\Auth\ClerkVerificationFailedException;
use PHPUnit\Framework\TestCase;

class AccountIdentityBuilderTest extends TestCase
{
    public function testAllowsApprovedAccountStatusRegardlessOfCasing(): void
    {
        $account = (new AccountEntity())
            ->setStatus('Approved')
            ->setIsActive(true);

        $builder = new AccountIdentityBuilder();

        $builder->validateApprovedAccount($account);

        $this->addToAssertionCount(1);
    }

    public function testRejectsDisabledAccountEvenWhenApproved(): void
    {
        $account = (new AccountEntity())
            ->setStatus('Active')
            ->setIsActive(false);

        $builder = new AccountIdentityBuilder();

        $this->expectException(ClerkVerificationFailedException::class);
        $this->expectExceptionMessage('Account is disabled');

        $builder->validateApprovedAccount($account);
    }
}
