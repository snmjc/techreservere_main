<?php

namespace App\Domain\ReleaseReturn\Repository;

use App\Domain\ReleaseReturn\Entity\ReleaseReturnEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReleaseReturnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReleaseReturnEntity::class);
    }

    /** @return ReleaseReturnEntity[] */
    public function findByReservationIdentifier(int $reservationIdentifier): array
    {
        return $this->findBy(['reservationIdentifier' => $reservationIdentifier]);
    }

    /** @return ReleaseReturnEntity[] */
    public function findAllReleaseReturns(): array { return $this->findAll(); }

    public function persistReleaseReturn(ReleaseReturnEntity $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }
}
