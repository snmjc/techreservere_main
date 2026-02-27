<?php

namespace App\Domain\Task\Controller;

use App\Domain\Task\Service\TaskManagementService;
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

    private TaskManagementService $taskManagementService;

    public function __construct(TaskManagementService $taskManagementService)
    {
        $this->taskManagementService = $taskManagementService;
    }

    #[Route('', name: 'task_list', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function listTasks(): JsonResponse
    {
        $dtos = $this->taskManagementService->getAllTasks();
        $responseList = array_map(fn($dto) => $dto->toResponseArray(), $dtos);
        return $this->createSuccessResponse(['tasks' => $responseList]);
    }

    #[Route('', name: 'task_create', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function createTask(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];
        $dto = $this->taskManagementService->createTask(
            taskTitle: $body['taskTitle'] ?? '',
            taskDescription: $body['taskDescription'] ?? null,
            taskType: $body['taskType'] ?? 'Preparation',
            reservationIdentifier: isset($body['reservationIdentifier']) ? (int)$body['reservationIdentifier'] : null,
            assignedToAccountId: isset($body['assignedToAccountId']) ? (int)$body['assignedToAccountId'] : null,
            dueDateTimestamp: $body['dueDateTimestamp'] ?? null
        );
        return $this->createSuccessResponse($dto->toResponseArray(), 201);
    }

    #[Route('/{taskIdentifier}/status', name: 'task_update_status', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateTaskStatus(int $taskIdentifier, Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];
        $dto = $this->taskManagementService->updateTaskStatus($taskIdentifier, $body['taskStatus'] ?? '');
        return $this->createSuccessResponse($dto->toResponseArray());
    }

    #[Route('/reservation/{reservationIdentifier}', name: 'task_by_reservation', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getTasksByReservation(int $reservationIdentifier): JsonResponse
    {
        $dtos = $this->taskManagementService->getTasksByReservation($reservationIdentifier);
        $responseList = array_map(fn($dto) => $dto->toResponseArray(), $dtos);
        return $this->createSuccessResponse(['tasks' => $responseList]);
    }
}
