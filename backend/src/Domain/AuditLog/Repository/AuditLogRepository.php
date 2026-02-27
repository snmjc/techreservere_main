<?php

namespace App\Domain\AuditLog\Repository;

use App\Domain\AuditLog\Entity\AuditLogEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLogEntity::class);
    }

    /** @return AuditLogEntity[] */
    public function findAllAuditLogs(): array
    {
        return $this->findBy([], ['occurredTimestamp' => 'DESC']);
    }

    /** @return AuditLogEntity[] */
    public function findByPerformedByAccountId(int $accountId): array
    {
        return $this->findBy(['performedByAccountId' => $accountId], ['occurredTimestamp' => 'DESC']);
    }

    public function persistAuditLog(AuditLogEntity $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }
}
