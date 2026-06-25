<?php

namespace App\Domain\Task\Controller;

use App\Domain\Task\Service\TaskWorkflowService;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/tasks')]
class TaskController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(private readonly TaskWorkflowService $taskWorkflowService)
    {
    }

    #[Route('', name: 'task_list', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function listTasks(): JsonResponse
    {
        return $this->serviceResultResponse($this->taskWorkflowService->listTasks());
    }

    #[Route('', name: 'task_create', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function createTask(Request $request): JsonResponse
    {
        return $this->serviceResultResponse($this->taskWorkflowService->createTask($request, $this->jsonBody($request)));
    }

    #[Route('/sms/test', name: 'task_test_sms', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function sendTestSms(Request $request): JsonResponse
    {
        return $this->serviceResultResponse($this->taskWorkflowService->sendTestSms($this->jsonBody($request)));
    }

    #[Route('/{taskIdentifier}', name: 'task_update', requirements: ['taskIdentifier' => '\d+'], methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateTask(int $taskIdentifier, Request $request): JsonResponse
    {
        return $this->serviceResultResponse($this->taskWorkflowService->updateTask($taskIdentifier, $request, $this->jsonBody($request)));
    }

    #[Route('/{taskIdentifier}', name: 'task_delete', requirements: ['taskIdentifier' => '\d+'], methods: ['DELETE'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function deleteTask(int $taskIdentifier, Request $request): JsonResponse
    {
        return $this->serviceResultResponse($this->taskWorkflowService->deleteTask($taskIdentifier, $request, $this->jsonBody($request)));
    }

    #[Route('/{taskIdentifier}/status', name: 'task_update_status', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateTaskStatus(int $taskIdentifier, Request $request): JsonResponse
    {
        return $this->serviceResultResponse($this->taskWorkflowService->updateTaskStatus($taskIdentifier, $this->jsonBody($request)));
    }

    #[Route('/reservation/{reservationIdentifier}', name: 'task_by_reservation', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getTasksByReservation(int $reservationIdentifier): JsonResponse
    {
        return $this->serviceResultResponse($this->taskWorkflowService->getTasksByReservation($reservationIdentifier));
    }

    private function jsonBody(Request $request): array
    {
        $requestBody = json_decode($request->getContent(), true);

        return is_array($requestBody) ? $requestBody : [];
    }

    private function serviceResultResponse(array $result): JsonResponse
    {
        if (($result['success'] ?? false) !== true) {
            return $this->createErrorResponse(
                (string)($result['errorCode'] ?? 'TaskRequestFailed'),
                (string)($result['message'] ?? 'Unable to complete task request.'),
                (int)($result['status'] ?? 500)
            );
        }

        return $this->createSuccessResponse($result['data'] ?? [], (int)($result['status'] ?? 200));
    }
}
