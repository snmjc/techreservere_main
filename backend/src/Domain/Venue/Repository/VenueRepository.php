<?php

namespace App\Domain\Venue\Repository;

use App\Domain\Venue\Entity\VenueEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VenueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VenueEntity::class);
    }

    /** @return VenueEntity[] */
    public function findAllVenues(): array { return $this->findAll(); }

    /** @return VenueEntity[] */
    public function findAvailableVenues(): array { return $this->findBy(['availabilityStatus' => 'Available']); }

    public function persistVenue(VenueEntity $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }
}
