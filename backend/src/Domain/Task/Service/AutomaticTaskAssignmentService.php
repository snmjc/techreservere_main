<?php

namespace App\Domain\Task\Service;

use App\Domain\Reservation\Entity\ReservationEntity;
use App\Domain\Task\DTO\TaskMutationRequestDTO;
use App\Shared\Exceptions\DomainValidationException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class AutomaticTaskAssignmentService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly TaskManagementService $taskManagementService,
        private readonly TaskReadService $taskReadService,
        private readonly TaskHistoryLogService $taskHistoryLogService,
        private readonly TaskAssignmentSmsService $taskAssignmentSmsService,
        private readonly TaskAssignmentTemplateService $taskAssignmentTemplateService
    ) {
    }

    public function prepareStaffAssignment(ReservationEntity $reservation): ?int
    {
        $reservationIdentifier = (int)($reservation->getReservationIdentifier() ?? 0);
        if ($reservationIdentifier <= 0) {
            throw new DomainValidationException('The reservation must be saved before a task can be assigned.');
        }

        if ($this->hasExistingTask($reservationIdentifier)) {
            return null;
        }

        $rangeStart = \DateTimeImmutable::createFromInterface($reservation->getEventDateTime());
        $rangeEnd = \DateTimeImmutable::createFromInterface($reservation->getEndDateTime() ?? $reservation->getEventDateTime());
        if ($rangeEnd <= $rangeStart) {
            $rangeEnd = $rangeStart->modify('+1 minute');
        }

        $assignedToAccountId = $this->findLowestWorkloadAvailableStaff($rangeStart, $rangeEnd);
        if ($assignedToAccountId === null) {
            throw new DomainValidationException(
                'No active staff member is available during this reservation schedule. Adjust existing assignments or add active staff before approving.'
            );
        }

        return $assignedToAccountId;
    }

    public function createTaskForApproval(ReservationEntity $reservation, ?int $assignedToAccountId): ?string
    {
        $reservationIdentifier = (int)($reservation->getReservationIdentifier() ?? 0);
        if ($reservationIdentifier <= 0 || $assignedToAccountId === null || $this->hasExistingTask($reservationIdentifier)) {
            return null;
        }

        $templateVariables = $this->taskAssignmentTemplateService->buildReservationContext($reservation);
        $taskTitle = $this->taskAssignmentTemplateService->renderTaskTitle($templateVariables);
        $taskDescription = $this->taskAssignmentTemplateService->renderTaskDescription($templateVariables);
        $taskType = $this->taskAssignmentTemplateService->renderTaskType($templateVariables);

        $dto = $this->taskManagementService->createTask(new TaskMutationRequestDTO(
            taskTitle: $taskTitle,
            taskDescription: $taskDescription,
            taskType: $taskType,
            taskStatus: 'Pending',
            reservationIdentifier: $reservationIdentifier,
            assignedToAccountId: $assignedToAccountId,
            dueDateTimestamp: $reservation->getEventDateTime()->format(\DateTimeInterface::ATOM),
            preparationStartTimestamp: $reservation->getEventDateTime()->format(\DateTimeInterface::ATOM),
            preparationEndTimestamp: ($reservation->getEndDateTime() ?? $reservation->getEventDateTime())->format(\DateTimeInterface::ATOM)
        ));

        $this->taskHistoryLogService->syncHistoryLog(
            $dto->taskIdentifier,
            $reservationIdentifier,
            $assignedToAccountId
        );

        $task = $this->taskReadService->fetchTaskById($dto->taskIdentifier);

        return is_array($task)
            ? $this->taskAssignmentSmsService->notifyOnAssignmentChange(null, $task)
            : 'Task assignment was created, but its SMS details could not be loaded.';
    }

    private function hasExistingTask(int $reservationIdentifier): bool
    {
        return (bool)$this->connection->fetchOne(
            'SELECT 1 FROM tasks WHERE reservation_identifier = :reservationIdentifier LIMIT 1',
            ['reservationIdentifier' => $reservationIdentifier],
            ['reservationIdentifier' => ParameterType::INTEGER]
        );
    }

    private function findLowestWorkloadAvailableStaff(
        \DateTimeImmutable $rangeStart,
        \DateTimeImmutable $rangeEnd
    ): ?int {
        $accountIdentifier = $this->connection->fetchOne(
            "SELECT accounts.account_identifier
             FROM accounts
             LEFT JOIN tasks workload_tasks
               ON workload_tasks.assigned_to_account_id = accounts.account_identifier
              AND LOWER(COALESCE(workload_tasks.task_status, '')) IN ('pending', 'in progress')
             WHERE accounts.role_designation = 'ROLE_STAFF'
               AND COALESCE(accounts.is_active, TRUE) = TRUE
               AND NOT EXISTS (
                   SELECT 1
                   FROM tasks conflicting_tasks
                   LEFT JOIN reservations conflicting_reservations
                     ON conflicting_reservations.reservation_identifier = conflicting_tasks.reservation_identifier
                   WHERE conflicting_tasks.assigned_to_account_id = accounts.account_identifier
                     AND LOWER(COALESCE(conflicting_tasks.task_status, '')) IN ('pending', 'in progress')
                     AND COALESCE(
                           conflicting_tasks.preparation_start_timestamp,
                           conflicting_reservations.event_date_time,
                           conflicting_tasks.due_date_timestamp
                         ) < :rangeEnd
                     AND COALESCE(
                           conflicting_tasks.preparation_end_timestamp,
                           conflicting_reservations.end_date_time,
                           conflicting_tasks.due_date_timestamp + INTERVAL '1 minute',
                           conflicting_reservations.event_date_time + INTERVAL '1 minute'
                         ) > :rangeStart
               )
             GROUP BY accounts.account_identifier
             ORDER BY COUNT(workload_tasks.task_identifier) ASC, accounts.account_identifier ASC
             LIMIT 1",
            [
                'rangeStart' => $rangeStart->format('Y-m-d H:i:s'),
                'rangeEnd' => $rangeEnd->format('Y-m-d H:i:s'),
            ],
            [
                'rangeStart' => ParameterType::STRING,
                'rangeEnd' => ParameterType::STRING,
            ]
        );

        if ($accountIdentifier === false || $accountIdentifier === null) {
            return null;
        }

        $normalizedIdentifier = (int)$accountIdentifier;
        return $normalizedIdentifier > 0 ? $normalizedIdentifier : null;
    }
}
