<?php

namespace App\Domain\Notification\Repository;

use App\Domain\Notification\Entity\NotificationEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationEntity::class);
    }

    /** @return NotificationEntity[] */
    public function findByRecipientAccountId(int $recipientAccountId): array
    {
        return $this->findBy(['recipientAccountId' => $recipientAccountId], ['createdTimestamp' => 'DESC']);
    }

    /** @return NotificationEntity[] */
    public function findUnreadByRecipient(int $recipientAccountId): array
    {
        return $this->findBy(['recipientAccountId' => $recipientAccountId, 'isRead' => false], ['createdTimestamp' => 'DESC']);
    }

    public function persistNotification(NotificationEntity $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }
}
