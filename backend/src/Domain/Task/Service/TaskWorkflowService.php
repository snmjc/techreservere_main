<?php

namespace App\Domain\Task\Service;

use Symfony\Component\HttpFoundation\Request;

class TaskWorkflowService
{
    public function __construct(
        private readonly TaskManagementService $taskManagementService,
        private readonly TaskReadService $taskReadService,
        private readonly TaskMutationCommandService $taskMutationCommandService
    ) {
    }

    public function listTasks(): array
    {
        return $this->success(['tasks' => $this->taskReadService->fetchTaskRows()]);
    }

    public function createTask(Request $request, array $body): array
    {
        return $this->taskMutationCommandService->createTask($request, $body);
    }

    public function updateTask(int $taskIdentifier, Request $request, array $body): array
    {
        return $this->taskMutationCommandService->updateTask($taskIdentifier, $request, $body);
    }

    public function deleteTask(int $taskIdentifier, Request $request, array $body): array
    {
        return $this->taskMutationCommandService->deleteTask($taskIdentifier, $request, $body);
    }

    public function updateTaskStatus(int $taskIdentifier, array $body): array
    {
        $dto = $this->taskManagementService->updateTaskStatus($taskIdentifier, $body['taskStatus'] ?? '');

        return $this->success($dto->toResponseArray());
    }

    public function getTasksByReservation(int $reservationIdentifier): array
    {
        return $this->success([
            'tasks' => $this->taskReadService->fetchTaskRowsByReservation($reservationIdentifier),
        ]);
    }

    private function success(array $data, int $status = 200): array
    {
        return ['success' => true, 'status' => $status, 'data' => $data];
    }
}
