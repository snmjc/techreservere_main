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

    public function createTask(TaskMutationRequestDTO $request): TaskResponseDTO
    {
        return $this->saveMutation(new TaskEntity(), $request);
    }

    public function updateTask(int $taskIdentifier, TaskMutationRequestDTO $request): TaskResponseDTO
    {
        return $this->saveMutation($this->findTaskOrFail($taskIdentifier), $request);
    }

    public function deleteTask(int $taskIdentifier): void
    {
        $this->taskRepository->deleteTask($this->findTaskOrFail($taskIdentifier));
    }

    public function updateTaskStatus(int $taskIdentifier, string $newStatus): TaskResponseDTO
    {
        $entity = $this->findTaskOrFail($taskIdentifier);
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
        $entity->setPreparationStartTimestamp($this->taskValidationService->parseDateTime($request->preparationStartTimestamp, 'Preparation start time is invalid.'));
        $entity->setPreparationEndTimestamp($this->taskValidationService->parseDateTime($request->preparationEndTimestamp, 'Preparation end time is invalid.'));
    }

    private function saveMutation(TaskEntity $entity, TaskMutationRequestDTO $request): TaskResponseDTO
    {
        $this->taskValidationService->validateMutation($request);
        $this->applyMutation($entity, $request);

        $this->taskRepository->persistTask($entity);
        return $this->taskResponseMapperService->transformEntityToDTO($entity);
    }

    private function findTaskOrFail(int $taskIdentifier): TaskEntity
    {
        $entity = $this->taskRepository->find($taskIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Task not found: ' . $taskIdentifier);
        }

        return $entity;
    }
}
