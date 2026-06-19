<?php

namespace App\Domain\Task\Service;

use App\Domain\Task\DTO\TaskResponseDTO;
use App\Domain\Task\Entity\TaskEntity;

class TaskResponseMapperService
{
    public function transformEntityToDTO(TaskEntity $entity): TaskResponseDTO
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
            preparationStartTimestamp: $entity->getPreparationStartTimestamp()?->format(\DateTime::ATOM),
            preparationEndTimestamp: $entity->getPreparationEndTimestamp()?->format(\DateTime::ATOM),
            createdTimestamp: $entity->getCreatedTimestamp()->format(\DateTime::ATOM)
        );
    }
}
