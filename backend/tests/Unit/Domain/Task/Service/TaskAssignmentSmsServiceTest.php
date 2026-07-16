<?php

namespace App\Tests\Unit\Domain\Task\Service;

use App\Domain\Task\Service\TaskAssignmentSmsService;
use App\Domain\Task\Service\SmsMessageLogService;
use App\Domain\Task\Service\TaskAssignmentTemplateService;
use App\Domain\Notification\Service\NotificationDispatchService;
use App\Shared\Exceptions\DomainValidationException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TaskAssignmentSmsServiceTest extends TestCase
{
    private mixed $originalApiKey;
    private mixed $originalDeviceId;
    private mixed $originalTextBeeApiKey;
    private mixed $originalTextBeeDeviceId;
    private mixed $originalApiKeyServer;
    private mixed $originalDeviceIdServer;
    private mixed $originalTextBeeApiKeyServer;
    private mixed $originalTextBeeDeviceIdServer;
    private SmsMessageLogService $smsMessageLogService;
    private TaskAssignmentTemplateService $taskAssignmentTemplateService;
    private NotificationDispatchService $notificationDispatchService;

    protected function setUp(): void
    {
        $this->originalApiKey = $_ENV['API_KEY'] ?? null;
        $this->originalDeviceId = $_ENV['DEVICE_ID'] ?? null;
        $this->originalTextBeeApiKey = $_ENV['TEXTBEE_API_KEY'] ?? null;
        $this->originalTextBeeDeviceId = $_ENV['TEXTBEE_DEVICE_ID'] ?? null;
        $this->originalApiKeyServer = $_SERVER['API_KEY'] ?? null;
        $this->originalDeviceIdServer = $_SERVER['DEVICE_ID'] ?? null;
        $this->originalTextBeeApiKeyServer = $_SERVER['TEXTBEE_API_KEY'] ?? null;
        $this->originalTextBeeDeviceIdServer = $_SERVER['TEXTBEE_DEVICE_ID'] ?? null;
        $this->smsMessageLogService = $this->createMock(SmsMessageLogService::class);
        $this->taskAssignmentTemplateService = new TaskAssignmentTemplateService();
        $this->notificationDispatchService = $this->createMock(NotificationDispatchService::class);
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironmentValue('API_KEY', $this->originalApiKey);
        $this->restoreEnvironmentValue('DEVICE_ID', $this->originalDeviceId);
        $this->restoreEnvironmentValue('TEXTBEE_API_KEY', $this->originalTextBeeApiKey);
        $this->restoreEnvironmentValue('TEXTBEE_DEVICE_ID', $this->originalTextBeeDeviceId);
        $this->restoreServerValue('API_KEY', $this->originalApiKeyServer);
        $this->restoreServerValue('DEVICE_ID', $this->originalDeviceIdServer);
        $this->restoreServerValue('TEXTBEE_API_KEY', $this->originalTextBeeApiKeyServer);
        $this->restoreServerValue('TEXTBEE_DEVICE_ID', $this->originalTextBeeDeviceIdServer);
    }

    public function testMessageMatchesFacilitiesOfficeAssignmentFormat(): void
    {
        $service = new TaskAssignmentSmsService(
            $this->createMock(HttpClientInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->smsMessageLogService,
            $this->taskAssignmentTemplateService,
            $this->notificationDispatchService
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
            "TechReserve: hi! Alex Santos.\n\n"
            . "You have task on Jun 30, 2026 10:00 AM, Academic Preparation: TR-2026-010.\n"
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
            ->with(false)
            ->willReturn('{"data":{"_id":"sms_1234567890","message":"Assignment message","recipients":["+639171234567"],"status":"PENDING","createdAt":"2023-09-15T14:23:45Z"}}');
        $response->expects($this->once())->method('getStatusCode')->willReturn(200);

        $this->smsMessageLogService
            ->expects($this->once())
            ->method('recordSuccess')
            ->with(
                'task_assignment',
                ['+639171234567'],
                $this->stringContains('TechReserve: hi! Alex Santos.'),
                $this->callback(static fn (array $payload): bool => ($payload['data']['_id'] ?? null) === 'sms_1234567890'),
                44,
                12
            );

        $this->notificationDispatchService
            ->expects($this->once())
            ->method('sendNotification')
            ->with(
                12,
                'TechReserve Task Assignment',
                $this->stringContains('Academic Preparation'),
                'maintenance'
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
                        && str_contains((string)($options['json']['message'] ?? ''), 'TechReserve: hi! Alex Santos.');
                })
            )
            ->willReturn($response);

        $service = new TaskAssignmentSmsService(
            $httpClient,
            $this->createMock(LoggerInterface::class),
            $this->smsMessageLogService,
            $this->taskAssignmentTemplateService,
            $this->notificationDispatchService
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
        $response->expects($this->once())->method('getContent')->with(false)->willReturn('{"success":true}');
        $response->expects($this->once())->method('getStatusCode')->willReturn(200);

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
            $this->smsMessageLogService,
            $this->taskAssignmentTemplateService,
            $this->notificationDispatchService
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
            $this->smsMessageLogService,
            $this->taskAssignmentTemplateService,
            $this->notificationDispatchService
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
        $response->expects($this->once())->method('getContent')->with(false)->willReturn('{"success":true}');
        $response->expects($this->once())->method('getStatusCode')->willReturn(200);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.textbee.dev/api/v1/gateway/devices/test-device/send-sms',
                $this->callback(static function (array $options): bool {
                    return ($options['json']['message'] ?? null) ===
                        "TechReserve: hi! <Assigned Staff>.\n\n"
                        . "You have task on <Due Date>, <Task Name>: <Reservation Code>.\n"
                        . "<Reservation Purpose>\n\n"
                        . "If you can't please do contact the Facilities Office for changing of staff";
                })
            )
            ->willReturn($response);

        $service = new TaskAssignmentSmsService(
            $httpClient,
            $this->createMock(LoggerInterface::class),
            $this->smsMessageLogService,
            $this->taskAssignmentTemplateService,
            $this->notificationDispatchService
        );

        $delivery = $service->sendTestSms('09171234567', '');

        $this->assertStringContainsString('<Assigned Staff>', $delivery['message']);
        $this->assertStringContainsString('<Reservation Purpose>', $delivery['message']);
    }

    public function testManualTestSmsAcceptsNamespacedTextBeeEnvironmentVariables(): void
    {
        unset($_ENV['API_KEY'], $_ENV['DEVICE_ID']);
        $_ENV['TEXTBEE_API_KEY'] = 'namespaced-api-key';
        $_ENV['TEXTBEE_DEVICE_ID'] = 'namespaced-device';

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getContent')->with(false)->willReturn('{"success":true}');
        $response->expects($this->once())->method('getStatusCode')->willReturn(200);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.textbee.dev/api/v1/gateway/devices/namespaced-device/send-sms',
                $this->callback(static function (array $options): bool {
                    return ($options['headers']['x-api-key'] ?? null) === 'namespaced-api-key';
                })
            )
            ->willReturn($response);

        $service = new TaskAssignmentSmsService(
            $httpClient,
            $this->createMock(LoggerInterface::class),
            $this->smsMessageLogService,
            $this->taskAssignmentTemplateService,
            $this->notificationDispatchService
        );

        $delivery = $service->sendTestSms('09171234567', 'Custom test message');

        $this->assertSame('+639171234567', $delivery['recipient']);
    }

    public function testManualTestSmsReturnsProviderFailureReason(): void
    {
        $_ENV['API_KEY'] = 'test-api-key';
        $_ENV['DEVICE_ID'] = 'test-device';

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getContent')->with(false)->willReturn(
            '{"message":"Device is offline","data":{"status":"FAILED"}}'
        );
        $response->expects($this->once())->method('getStatusCode')->willReturn(200);

        $this->smsMessageLogService
            ->expects($this->once())
            ->method('recordFailure')
            ->with(
                'manual_test',
                ['+639171234567'],
                'Custom test message',
                'Device is offline',
                $this->callback(static fn (array $payload): bool => ($payload['message'] ?? null) === 'Device is offline'),
                null,
                null
            );

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $service = new TaskAssignmentSmsService(
            $httpClient,
            $this->createMock(LoggerInterface::class),
            $this->smsMessageLogService,
            $this->taskAssignmentTemplateService,
            $this->notificationDispatchService
        );

        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('Device is offline');

        $service->sendTestSms('09171234567', 'Custom test message');
    }

    public function testManualTestSmsAcceptsServerEnvironmentVariablesWhenEnvArrayIsEmpty(): void
    {
        unset($_ENV['API_KEY'], $_ENV['DEVICE_ID'], $_ENV['TEXTBEE_API_KEY'], $_ENV['TEXTBEE_DEVICE_ID']);
        $_SERVER['TEXTBEE_API_KEY'] = 'server-api-key';
        $_SERVER['TEXTBEE_DEVICE_ID'] = 'server-device';

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getContent')->with(false)->willReturn('{"success":true}');
        $response->expects($this->once())->method('getStatusCode')->willReturn(200);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.textbee.dev/api/v1/gateway/devices/server-device/send-sms',
                $this->callback(static function (array $options): bool {
                    return ($options['headers']['x-api-key'] ?? null) === 'server-api-key';
                })
            )
            ->willReturn($response);

        $service = new TaskAssignmentSmsService(
            $httpClient,
            $this->createMock(LoggerInterface::class),
            $this->smsMessageLogService,
            $this->taskAssignmentTemplateService,
            $this->notificationDispatchService
        );

        $delivery = $service->sendTestSms('09171234567', 'Custom test message');

        $this->assertSame('+639171234567', $delivery['recipient']);
    }

    private function restoreEnvironmentValue(string $name, mixed $value): void
    {
        if ($value === null) {
            unset($_ENV[$name]);
            return;
        }

        $_ENV[$name] = $value;
    }

    private function restoreServerValue(string $name, mixed $value): void
    {
        if ($value === null) {
            unset($_SERVER[$name]);
            return;
        }

        $_SERVER[$name] = $value;
    }
}
