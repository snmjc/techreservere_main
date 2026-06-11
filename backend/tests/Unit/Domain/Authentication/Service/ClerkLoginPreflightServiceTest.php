<?php

namespace App\Tests\Unit\Domain\Authentication\Service;

use App\Domain\Account\Service\ClerkInvitationSyncService;
use App\Domain\Authentication\Service\ClerkLoginPreflightService;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ClerkLoginPreflightServiceTest extends TestCase
{
    private Connection|MockObject $connection;
    private ClerkInvitationSyncService|MockObject $clerkInvitationSyncService;
    private ClerkLoginPreflightService $service;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->clerkInvitationSyncService = $this->createMock(ClerkInvitationSyncService::class);
        $this->service = new ClerkLoginPreflightService($this->connection, $this->clerkInvitationSyncService);
    }

    public function testCheckReturnsSuccessAfterAcceptedInvitationSyncActivatesAccount(): void
    {
        $emailAddress = 'borrower@example.com';

        $this->connection
            ->expects($this->exactly(2))
            ->method('fetchAssociative')
            ->willReturnOnConsecutiveCalls(
                [
                    'account_identifier' => 41,
                    'email_address' => $emailAddress,
                    'username' => 'borrower',
                    'clerk_user_id' => '',
                    'status' => 'verified',
                    'is_approved' => false,
                    'is_verified' => true,
                    'is_active' => true,
                ],
                [
                    'account_identifier' => 41,
                    'email_address' => $emailAddress,
                    'status' => 'active',
                    'is_approved' => true,
                    'is_verified' => true,
                    'clerk_user_id' => 'user_clerk_123',
                    'is_active' => true,
                ],
            );

        $this->clerkInvitationSyncService
            ->expects($this->once())
            ->method('syncAcceptedInvitationForEmail')
            ->with($emailAddress, '');

        $result = $this->service->check($emailAddress);

        $this->assertTrue($result['success']);
        $this->assertSame(true, $result['data']['canSignIn']);
        $this->assertSame('active', $result['data']['accountStatus']);
    }
}
