<?php

namespace App\Domain\Task\Service;

use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;
use Symfony\Component\HttpFoundation\Request;

class TaskMutationCommandService
{
    public function __construct(
        private readonly TaskManagementService $taskManagementService,
        private readonly TaskReadService $taskReadService,
        private readonly TaskHistoryLogService $taskHistoryLogService,
        private readonly TaskMutationPayloadService $taskMutationPayloadService,
        private readonly TaskAssignmentSmsService $taskAssignmentSmsService
    ) {
    }

    public function createTask(Request $request, array $body): array
    {
        $preflightError = $this->taskMutationPayloadService->validateMutationPreflight($request, $body, 'creating');
        if ($preflightError !== null) {
            return $preflightError;
        }

        try {
            $linkedRecords = $this->taskMutationPayloadService->resolveLinkedRecords($body);
            $dto = $this->taskManagementService->createTask(
                $this->taskMutationPayloadService->buildTaskMutationRequest($body, $linkedRecords)
            );
            $this->taskHistoryLogService->syncHistoryLog(
                $dto->taskIdentifier,
                $linkedRecords->reservationIdentifier,
                $linkedRecords->assignedToAccountId
            );
            $task = $this->taskReadService->fetchTaskById($dto->taskIdentifier);
            $warning = is_array($task)
                ? $this->taskAssignmentSmsService->notifyOnAssignmentChange(null, $task)
                : null;

            return $this->success([
                'task' => $task,
                'warning' => $warning,
            ], 201);
        } catch (DomainValidationException $exception) {
            return $this->error('TaskValidationFailed', $exception->getMessage(), 422);
        }
    }

    public function updateTask(int $taskIdentifier, Request $request, array $body): array
    {
        $preflightError = $this->taskMutationPayloadService->validateMutationPreflight($request, $body, 'updating');
        if ($preflightError !== null) {
            return $preflightError;
        }

        try {
            $previousTask = $this->taskReadService->fetchTaskById($taskIdentifier);
            $linkedRecords = $this->taskMutationPayloadService->resolveLinkedRecords($body);
            $dto = $this->taskManagementService->updateTask(
                $taskIdentifier,
                $this->taskMutationPayloadService->buildTaskMutationRequest($body, $linkedRecords)
            );
            $this->taskHistoryLogService->syncHistoryLog(
                $dto->taskIdentifier,
                $linkedRecords->reservationIdentifier,
                $linkedRecords->assignedToAccountId
            );
            $task = $this->taskReadService->fetchTaskById($dto->taskIdentifier);
            $warning = is_array($task)
                ? $this->taskAssignmentSmsService->notifyOnAssignmentChange($previousTask, $task)
                : null;

            return $this->success([
                'task' => $task,
                'warning' => $warning,
            ]);
        } catch (DomainNotFoundException $exception) {
            return $this->error('TaskNotFound', $exception->getMessage(), 404);
        } catch (DomainValidationException $exception) {
            return $this->error('TaskValidationFailed', $exception->getMessage(), 422);
        }
    }

    public function deleteTask(int $taskIdentifier, Request $request, array $body): array
    {
        $securityError = $this->taskMutationPayloadService->validateDeleteSecurity($request, $body);
        if ($securityError !== null) {
            return $securityError;
        }

        try {
            $this->taskManagementService->deleteTask($taskIdentifier);
        } catch (DomainNotFoundException $exception) {
            return $this->error('TaskNotFound', $exception->getMessage(), 404);
        }

        return $this->success([
            'message' => 'Task assignment deleted.',
            'taskIdentifier' => $taskIdentifier,
        ]);
    }

    private function success(array $data, int $status = 200): array
    {
        return ['success' => true, 'status' => $status, 'data' => $data];
    }

    private function error(string $errorCode, string $message, int $status): array
    {
        return [
            'success' => false,
            'errorCode' => $errorCode,
            'message' => $message,
            'status' => $status,
        ];
    }
}
