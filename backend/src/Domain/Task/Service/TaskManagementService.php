<?php

namespace App\Domain\Task\Service;

use App\Domain\Task\DTO\TaskResponseDTO;
use App\Domain\Task\Entity\TaskEntity;
use App\Domain\Task\Repository\TaskRepository;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;

class TaskManagementService
{
    private TaskRepository $taskRepository;

    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    // ===== AI GENERATED: createTask =====
    // Purpose: Create a new task linked to a reservation
    // Inputs: task fields
    // Returns: TaskResponseDTO

    public function createTask(string $taskTitle, ?string $taskDescription, string $taskType, ?int $reservationIdentifier, ?int $assignedToAccountId, ?string $dueDateTimestamp): TaskResponseDTO
    {
        if (empty($taskTitle)) {
            throw new DomainValidationException('Task title is required.');
        }

        $entity = new TaskEntity();
        $entity->setTaskTitle($taskTitle);
        $entity->setTaskDescription($taskDescription);
        $entity->setTaskType($taskType);
        $entity->setReservationIdentifier($reservationIdentifier);
        $entity->setAssignedToAccountId($assignedToAccountId);

        if ($dueDateTimestamp !== null) {
            $entity->setDueDateTimestamp(new \DateTime($dueDateTimestamp));
        }

        $this->taskRepository->persistTask($entity);
        return $this->transformEntityToDTO($entity);
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

        $allowedStatuses = ['Pending', 'In Progress', 'Completed', 'Cancelled'];
        if (!in_array($newStatus, $allowedStatuses, true)) {
            throw new DomainValidationException('Invalid task status: ' . $newStatus);
        }

        $entity->setTaskStatus($newStatus);
        $this->taskRepository->persistTask($entity);
        return $this->transformEntityToDTO($entity);
    }

    /** @return TaskResponseDTO[] */
    public function getAllTasks(): array
    {
        $entities = $this->taskRepository->findAllTasks();
        return array_map(fn($e) => $this->transformEntityToDTO($e), $entities);
    }

    /** @return TaskResponseDTO[] */
    public function getTasksByReservation(int $reservationIdentifier): array
    {
        $entities = $this->taskRepository->findByReservationIdentifier($reservationIdentifier);
        return array_map(fn($e) => $this->transformEntityToDTO($e), $entities);
    }

    private function transformEntityToDTO(TaskEntity $entity): TaskResponseDTO
    {
        return new TaskResponseDTO(
            taskIdentifier: $entity->getTaskIdentifier(),
            reservationIdentifier: $entity->getReservationIdentifier(),
            taskTitle: $entity->getTaskTitle(),
            taskDescription: $entity->getTaskDescription(),
            taskType: $entity->getTaskType(),
            taskStatus: $entity->getTaskStatus(),
            assignedToAccountId: $entity->getAssignedToAccountId(),
            dueDateTimestamp: $entity->getDueDateTimestamp()?->format(\DateTime::ATOM),
            createdTimestamp: $entity->getCreatedTimestamp()->format(\DateTime::ATOM)
        );
    }
}
