<?php

namespace App\Tests\Unit\Domain\Task\Service;

use App\Domain\Reservation\Entity\ReservationEntity;
use App\Domain\Task\DTO\TaskResponseDTO;
use App\Domain\Task\Service\AutomaticTaskAssignmentService;
use App\Domain\Task\Service\TaskAssignmentSmsService;
use App\Domain\Task\Service\TaskHistoryLogService;
use App\Domain\Task\Service\TaskManagementService;
use App\Domain\Task\Service\TaskReadService;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AutomaticTaskAssignmentServiceTest extends TestCase
{
    private Connection|MockObject $connection;
    private TaskManagementService|MockObject $taskManagementService;
    private TaskReadService|MockObject $taskReadService;
    private TaskHistoryLogService|MockObject $taskHistoryLogService;
    private TaskAssignmentSmsService|MockObject $taskAssignmentSmsService;
    private AutomaticTaskAssignmentService $service;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->taskManagementService = $this->createMock(TaskManagementService::class);
        $this->taskReadService = $this->createMock(TaskReadService::class);
        $this->taskHistoryLogService = $this->createMock(TaskHistoryLogService::class);
        $this->taskAssignmentSmsService = $this->createMock(TaskAssignmentSmsService::class);
        $this->service = new AutomaticTaskAssignmentService(
            $this->connection,
            $this->taskManagementService,
            $this->taskReadService,
            $this->taskHistoryLogService,
            $this->taskAssignmentSmsService
        );
    }

    public function testPrepareStaffAssignmentReturnsLowestWorkloadAvailableStaff(): void
    {
        $this->connection
            ->expects($this->exactly(2))
            ->method('fetchOne')
            ->willReturnOnConsecutiveCalls(false, 12);

        $this->assertSame(12, $this->service->prepareStaffAssignment($this->createReservation()));
    }

    public function testExistingReservationTaskPreventsDuplicateAutomaticTask(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('fetchOne')
            ->willReturn(1);

        $this->assertNull($this->service->prepareStaffAssignment($this->createReservation()));
    }

    public function testCreateTaskForApprovalPersistsHistoryAndSendsSms(): void
    {
        $reservation = $this->createReservation();
        $taskResponse = new TaskResponseDTO(
            44,
            10,
            'Academic Preparation',
            'Class activity',
            'Preparation',
            'Pending',
            12,
            '2026-06-30T10:00:00+08:00',
            '2026-06-30T10:00:00+08:00',
            '2026-06-30T11:00:00+08:00',
            '2026-06-26T10:00:00+08:00'
        );
        $task = ['taskIdentifier' => 44, 'assignedToAccountId' => 12];

        $this->connection
            ->expects($this->once())
            ->method('fetchOne')
            ->willReturn(false);
        $this->taskManagementService
            ->expects($this->once())
            ->method('createTask')
            ->with($this->callback(static function ($request): bool {
                return $request->taskTitle === 'Academic Preparation'
                    && $request->taskDescription === 'Class activity'
                    && $request->reservationIdentifier === 10
                    && $request->assignedToAccountId === 12;
            }))
            ->willReturn($taskResponse);
        $this->taskHistoryLogService
            ->expects($this->once())
            ->method('syncHistoryLog')
            ->with(44, 10, 12);
        $this->taskReadService
            ->expects($this->once())
            ->method('fetchTaskById')
            ->with(44)
            ->willReturn($task);
        $this->taskAssignmentSmsService
            ->expects($this->once())
            ->method('notifyOnAssignmentChange')
            ->with(null, $task)
            ->willReturn(null);

        $this->assertNull($this->service->createTaskForApproval($reservation, 12));
    }

    private function createReservation(): ReservationEntity|MockObject
    {
        $reservation = $this->createMock(ReservationEntity::class);
        $reservation->method('getReservationIdentifier')->willReturn(10);
        $reservation->method('getActivityType')->willReturn('Academic');
        $reservation->method('getPurposeDescription')->willReturn('Class activity');
        $reservation->method('getEventDateTime')->willReturn(new \DateTimeImmutable('2026-06-30T10:00:00+08:00'));
        $reservation->method('getEndDateTime')->willReturn(new \DateTimeImmutable('2026-06-30T11:00:00+08:00'));

        return $reservation;
    }
}
