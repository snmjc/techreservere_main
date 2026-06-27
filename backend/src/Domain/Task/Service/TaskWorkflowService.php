<?php

namespace App\Domain\Task\Service;

use App\Shared\Exceptions\DomainValidationException;
use Symfony\Component\HttpFoundation\Request;

class TaskWorkflowService
{
    public function __construct(
        private readonly TaskManagementService $taskManagementService,
        private readonly TaskReadService $taskReadService,
        private readonly TaskMutationCommandService $taskMutationCommandService,
        private readonly TaskAssignmentSmsService $taskAssignmentSmsService,
        private readonly TaskAssignmentTemplateService $taskAssignmentTemplateService
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

    public function sendTestSms(array $body): array
    {
        try {
            $delivery = $this->taskAssignmentSmsService->sendTestSms(
                (string)($body['phoneNumber'] ?? ''),
                isset($body['message']) ? (string)$body['message'] : null
            );

            return $this->success([
                'message' => 'Test SMS submitted to TextBee.',
                'delivery' => $delivery,
            ]);
        } catch (DomainValidationException $exception) {
            return [
                'success' => false,
                'errorCode' => 'TestSmsFailed',
                'message' => $exception->getMessage(),
                'status' => 422,
            ];
        }
    }

    public function getTaskTemplate(): array
    {
        return $this->success([
            'template' => $this->taskAssignmentTemplateService->getTemplate(),
        ]);
    }

    public function updateTaskTemplate(array $body): array
    {
        try {
            return $this->success([
                'template' => $this->taskAssignmentTemplateService->updateTemplate($body),
            ]);
        } catch (DomainValidationException $exception) {
            return [
                'success' => false,
                'errorCode' => 'TaskTemplateValidationFailed',
                'message' => $exception->getMessage(),
                'status' => 422,
            ];
        }
    }

    private function success(array $data, int $status = 200): array
    {
        return ['success' => true, 'status' => $status, 'data' => $data];
    }
}
