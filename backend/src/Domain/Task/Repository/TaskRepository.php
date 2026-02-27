<?php

namespace App\Domain\Task\Repository;

use App\Domain\Task\Entity\TaskEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskEntity::class);
    }

    /** @return TaskEntity[] */
    public function findAllTasks(): array { return $this->findAll(); }

    /** @return TaskEntity[] */
    public function findByReservationIdentifier(int $reservationIdentifier): array
    {
        return $this->findBy(['reservationIdentifier' => $reservationIdentifier]);
    }

    /** @return TaskEntity[] */
    public function findByAssignedAccount(int $assignedToAccountId): array
    {
        return $this->findBy(['assignedToAccountId' => $assignedToAccountId]);
    }

    /** @return TaskEntity[] */
    public function findByTaskStatus(string $taskStatus): array
    {
        return $this->findBy(['taskStatus' => $taskStatus]);
    }

    public function persistTask(TaskEntity $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }
}
