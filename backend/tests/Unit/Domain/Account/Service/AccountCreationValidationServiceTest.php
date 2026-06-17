<?php

namespace App\Tests\Unit\Domain\Account\Service;

use App\Domain\Account\Service\AccountInputValidationService;
use App\Domain\Account\Service\AdminSecurityConfirmationService;
use App\Domain\Account\Service\AccountConflictLookupService;
use App\Domain\Account\Service\WishlistAdminAccountService;
use App\Domain\Account\Service\WishlistEmployeeAccountService;
use App\Domain\Account\Service\WishlistUserAccountService;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AccountCreationValidationServiceTest extends TestCase
{
    private Connection|MockObject $connection;
    private AccountConflictLookupService|MockObject $accountConflictLookupService;
    private AdminSecurityConfirmationService|MockObject $adminSecurityConfirmationService;
    private AccountInputValidationService $accountInputValidationService;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->accountConflictLookupService = $this->createMock(AccountConflictLookupService::class);
        $this->adminSecurityConfirmationService = $this->createMock(AdminSecurityConfirmationService::class);
        $this->accountInputValidationService = new AccountInputValidationService();
    }

    public function testAdminCreationRejectsFitDomain(): void
    {
        $service = new WishlistAdminAccountService(
            $this->connection,
            $this->accountConflictLookupService,
            $this->accountInputValidationService,
            $this->adminSecurityConfirmationService
        );

        $result = $service->create([
            'idNumber' => '1234567890',
            'lastName' => 'Dela Cruz',
            'firstName' => 'Juan',
            'emailAddress' => 'admin@fit.edu.ph',
            'confirmedAdminEmail' => 'admin@feutech.edu.ph',
        ], 1);

        $this->assertFalse($result['success']);
        $this->assertSame('ValidationError', $result['errorCode']);
        $this->assertSame('Admin account must use a valid @feutech.edu.ph email address.', $result['message']);
    }

    public function testUserCreationRejectsNonTenDigitIdNumber(): void
    {
        $service = new WishlistUserAccountService(
            $this->connection,
            $this->accountConflictLookupService,
            $this->accountInputValidationService
        );

        $result = $service->create([
            'lastName' => 'Dela Cruz',
            'firstName' => 'Juan',
            'emailAddress' => 'juan@fit.edu.ph',
            'idNumber' => '123456789',
            'role' => 'Student',
            'passwordText' => 'Password1',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('ValidationError', $result['errorCode']);
        $this->assertSame('ID number must be exactly 10 digits.', $result['message']);
    }

    public function testEmployeeCreationRejectsNonTenDigitWorkId(): void
    {
        $service = new WishlistEmployeeAccountService(
            $this->connection,
            $this->accountConflictLookupService,
            $this->accountInputValidationService
        );

        $result = $service->create([
            'lastName' => 'Dela Cruz',
            'firstName' => 'Juan',
            'phone' => '9123456789',
            'idNumber' => '123456789',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('ValidationError', $result['errorCode']);
        $this->assertSame('Work ID number must be exactly 10 digits.', $result['message']);
    }
}
