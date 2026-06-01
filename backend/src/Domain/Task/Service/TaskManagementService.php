<?php

namespace App\Domain\Task\Service;

use App\Domain\Task\DTO\TaskMutationRequestDTO;
use App\Domain\Task\DTO\TaskResponseDTO;
use App\Domain\Task\Entity\TaskEntity;
use App\Domain\Task\Repository\TaskRepository;
use App\Shared\Exceptions\DomainNotFoundException;

class TaskManagementService
{
    private TaskRepository $taskRepository;
    private TaskResponseMapperService $taskResponseMapperService;
    private TaskValidationService $taskValidationService;

    public function __construct(
        TaskRepository $taskRepository,
        TaskResponseMapperService $taskResponseMapperService,
        TaskValidationService $taskValidationService
    ) {
        $this->taskRepository = $taskRepository;
        $this->taskResponseMapperService = $taskResponseMapperService;
        $this->taskValidationService = $taskValidationService;
    }

    // ===== AI GENERATED: createTask =====
    // Purpose: Create a new task linked to a reservation
    // Inputs: task fields
    // Returns: TaskResponseDTO

    public function createTask(TaskMutationRequestDTO $request): TaskResponseDTO
    {
        $this->taskValidationService->validateMutation($request);

        $entity = new TaskEntity();
        $this->applyMutation($entity, $request);

        $this->taskRepository->persistTask($entity);
        return $this->taskResponseMapperService->transformEntityToDTO($entity);
    }

    public function updateTask(int $taskIdentifier, TaskMutationRequestDTO $request): TaskResponseDTO
    {
        $entity = $this->taskRepository->find($taskIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Task not found: ' . $taskIdentifier);
        }

        $this->taskValidationService->validateMutation($request);
        $this->applyMutation($entity, $request);

        $this->taskRepository->persistTask($entity);
        return $this->taskResponseMapperService->transformEntityToDTO($entity);
    }

    public function deleteTask(int $taskIdentifier): void
    {
        $entity = $this->taskRepository->find($taskIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Task not found: ' . $taskIdentifier);
        }

        $this->taskRepository->deleteTask($entity);
    }

    // ===== AI GENERATED: updateTaskStatus =====
    // Purpose: Update task status
    // Inputs: taskIdentifier (int), newStatus (string)
    // Returns: TaskResponseDTO

    public function updateTaskStatus(int $taskIdentifier, string $newStatus): TaskResponseDTO
    {
        $entity = $this->taskRepository->find($taskIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Task not found: ' . $taskIdentifier);
        }

        $this->taskValidationService->validateStatus($newStatus);

        $entity->setTaskStatus($newStatus);
        $this->taskRepository->persistTask($entity);
        return $this->taskResponseMapperService->transformEntityToDTO($entity);
    }

    /** @return TaskResponseDTO[] */
    public function getAllTasks(): array
    {
        $entities = $this->taskRepository->findAllTasks();
        return array_map(fn($e) => $this->taskResponseMapperService->transformEntityToDTO($e), $entities);
    }

    /** @return TaskResponseDTO[] */
    public function getTasksByReservation(int $reservationIdentifier): array
    {
        $entities = $this->taskRepository->findByReservationIdentifier($reservationIdentifier);
        return array_map(fn($e) => $this->taskResponseMapperService->transformEntityToDTO($e), $entities);
    }

    private function applyMutation(TaskEntity $entity, TaskMutationRequestDTO $request): void
    {
        $entity->setTaskTitle(trim($request->taskTitle));
        $entity->setTaskDescription($this->taskValidationService->normalizeOptionalText($request->taskDescription));
        $entity->setTaskType(trim($request->taskType));
        $entity->setTaskStatus(trim($request->taskStatus));
        $entity->setReservationIdentifier($request->reservationIdentifier);
        $entity->setAssignedToAccountId($request->assignedToAccountId);
        $entity->setDueDateTimestamp($this->taskValidationService->parseDueDate($request->dueDateTimestamp));
    }
}
