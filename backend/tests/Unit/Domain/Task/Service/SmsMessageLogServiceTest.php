<?php

namespace App\Tests\Unit\Domain\Task\Service;

use App\Domain\Task\Service\SmsMessageLogService;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SmsMessageLogServiceTest extends TestCase
{
    private Connection|MockObject $connection;
    private SmsMessageLogService $service;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->service = new SmsMessageLogService($this->connection);
    }

    public function testRecordSuccessStoresTextBeeResponseFields(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with(
                $this->stringContains('INSERT INTO sms_message_logs'),
                $this->callback(static function (array $parameters): bool {
                    return $parameters['providerMessageId'] === 'sms_1234567890'
                        && $parameters['deliveryStatus'] === 'PENDING'
                        && $parameters['providerCreatedAt'] === '2023-09-15T14:23:45+00:00'
                        && json_decode($parameters['recipients'], true) === ['+1234567890']
                        && json_decode($parameters['responsePayload'], true)['data']['_id'] === 'sms_1234567890';
                }),
                $this->callback(static fn (mixed $types): bool => is_array($types))
            )
            ->willReturn(1);

        $this->service->recordSuccess(
            'manual_test',
            ['+1234567890'],
            'Hello from TextBee!',
            [
                'data' => [
                    '_id' => 'sms_1234567890',
                    'message' => 'Hello from TextBee!',
                    'recipients' => ['+1234567890'],
                    'status' => 'PENDING',
                    'createdAt' => '2023-09-15T14:23:45Z',
                ],
            ]
        );
    }

    public function testRecordFailureStoresAttemptAndError(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with(
                $this->stringContains('INSERT INTO sms_message_logs'),
                $this->callback(static function (array $parameters): bool {
                    return $parameters['messageSource'] === 'task_assignment'
                        && $parameters['deliveryStatus'] === 'FAILED'
                        && $parameters['errorMessage'] === 'Gateway unavailable'
                        && $parameters['taskIdentifier'] === 44;
                }),
                $this->callback(static fn (mixed $types): bool => is_array($types))
            )
            ->willReturn(1);

        $this->service->recordFailure(
            'task_assignment',
            ['+639171234567'],
            'Assignment message',
            'Gateway unavailable',
            null,
            44,
            12
        );
    }
}
