<?php

namespace App\Tests\Unit\Domain\Task\Service;

use App\Domain\Task\Service\TaskAssignmentSmsService;
use App\Domain\Task\Service\SmsMessageLogService;
use App\Shared\Exceptions\DomainValidationException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TaskAssignmentSmsServiceTest extends TestCase
{
    private mixed $originalApiKey;
    private mixed $originalDeviceId;
    private SmsMessageLogService $smsMessageLogService;

    protected function setUp(): void
    {
        $this->originalApiKey = $_ENV['API_KEY'] ?? null;
        $this->originalDeviceId = $_ENV['DEVICE_ID'] ?? null;
        $this->smsMessageLogService = $this->createMock(SmsMessageLogService::class);
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironmentValue('API_KEY', $this->originalApiKey);
        $this->restoreEnvironmentValue('DEVICE_ID', $this->originalDeviceId);
    }

    public function testMessageMatchesFacilitiesOfficeAssignmentFormat(): void
    {
        $service = new TaskAssignmentSmsService(
            $this->createMock(HttpClientInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->smsMessageLogService
        );
        $method = new \ReflectionMethod($service, 'buildMessageBody');

        $message = $method->invoke($service, [
            'assignedStaffName' => 'Alex Santos',
            'taskTitle' => 'Academic Preparation',
            'reservationCode' => 'TR-2026-010',
            'taskDescription' => 'Prepare the requested equipment.',
            'dueDateTimestamp' => '2026-06-30T10:00:00+08:00',
            'eventDateTime' => '2026-06-30T10:00:00+08:00',
            'endDateTime' => '2026-06-30T11:00:00+08:00',
        ]);

        $this->assertSame(
            "hi! Alex Santos.\n\n"
            . "You have task on Jun 30, 2026 10:00 AM - Jun 30, 2026 11:00 AM, Academic Preparation: TR-2026-010.\n"
            . "Prepare the requested equipment.\n\n"
            . "If you can't please do contact the Facilities Office for changing of staff",
            $message
        );
    }

    public function testAssignmentIsSentThroughTextBee(): void
    {
        $_ENV['API_KEY'] = 'test-api-key';
        $_ENV['DEVICE_ID'] = 'test-device';

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{"data":{"_id":"sms_1234567890","message":"Assignment message","recipients":["+639171234567"],"status":"PENDING","createdAt":"2023-09-15T14:23:45Z"}}');

        $this->smsMessageLogService
            ->expects($this->once())
            ->method('recordSuccess')
            ->with(
                'task_assignment',
                ['+639171234567'],
                $this->stringContains('hi! Alex Santos.'),
                $this->callback(static fn (array $payload): bool => ($payload['data']['_id'] ?? null) === 'sms_1234567890'),
                44,
                12
            );

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.textbee.dev/api/v1/gateway/devices/test-device/send-sms',
                $this->callback(static function (array $options): bool {
                    return ($options['headers']['x-api-key'] ?? null) === 'test-api-key'
                        && ($options['json']['recipients'] ?? null) === ['+639171234567']
                        && str_contains((string)($options['json']['message'] ?? ''), 'hi! Alex Santos.');
                })
            )
            ->willReturn($response);

        $service = new TaskAssignmentSmsService(
            $httpClient,
            $this->createMock(LoggerInterface::class),
            $this->smsMessageLogService
        );

        $warning = $service->notifyOnAssignmentChange(null, [
            'taskIdentifier' => 44,
            'assignedToAccountId' => 12,
            'assignedStaffPhone' => '09171234567',
            'assignedStaffName' => 'Alex Santos',
            'taskTitle' => 'Academic Preparation',
            'reservationCode' => 'TR-2026-010',
            'taskDescription' => 'Prepare the requested equipment.',
            'dueDateTimestamp' => '2026-06-30T10:00:00+08:00',
            'eventDateTime' => '2026-06-30T10:00:00+08:00',
            'endDateTime' => '2026-06-30T11:00:00+08:00',
        ]);

        $this->assertNull($warning);
    }

    public function testManualTestSmsIsSentThroughTextBee(): void
    {
        $_ENV['API_KEY'] = 'test-api-key';
        $_ENV['DEVICE_ID'] = 'test-device';

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getContent')->willReturn('{"success":true}');

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.textbee.dev/api/v1/gateway/devices/test-device/send-sms',
                $this->callback(static function (array $options): bool {
                    return ($options['json']['recipients'] ?? null) === ['+639171234567']
                        && ($options['json']['message'] ?? null) === 'Custom test message';
                })
            )
            ->willReturn($response);

        $service = new TaskAssignmentSmsService(
            $httpClient,
            $this->createMock(LoggerInterface::class),
            $this->smsMessageLogService
        );

        $delivery = $service->sendTestSms('09171234567', 'Custom test message');

        $this->assertSame('+639171234567', $delivery['recipient']);
        $this->assertSame('Custom test message', $delivery['message']);
    }

    public function testManualTestSmsRejectsInvalidPhoneNumber(): void
    {
        $service = new TaskAssignmentSmsService(
            $this->createMock(HttpClientInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->smsMessageLogService
        );

        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('Phone number must be a valid Philippine mobile number');

        $service->sendTestSms('1234', 'Test');
    }

    public function testManualTestSmsUsesAssignmentTemplateWhenMessageIsBlank(): void
    {
        $_ENV['API_KEY'] = 'test-api-key';
        $_ENV['DEVICE_ID'] = 'test-device';

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getContent')->willReturn('{"success":true}');

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.textbee.dev/api/v1/gateway/devices/test-device/send-sms',
                $this->callback(static function (array $options): bool {
                    return ($options['json']['message'] ?? null) ===
                        "hi! <Assigned Staff>.\n\n"
                        . "You have task on <Due Date>, <Task Name>: <Reservation Code>.\n"
                        . "<Reservation Purpose>\n\n"
                        . "If you can't please do contact the Facilities Office for changing of staff";
                })
            )
            ->willReturn($response);

        $service = new TaskAssignmentSmsService(
            $httpClient,
            $this->createMock(LoggerInterface::class),
            $this->smsMessageLogService
        );

        $delivery = $service->sendTestSms('09171234567', '');

        $this->assertStringContainsString('<Assigned Staff>', $delivery['message']);
        $this->assertStringContainsString('<Reservation Purpose>', $delivery['message']);
    }

    private function restoreEnvironmentValue(string $name, mixed $value): void
    {
        if ($value === null) {
            unset($_ENV[$name]);
            return;
        }

        $_ENV[$name] = $value;
    }
}
