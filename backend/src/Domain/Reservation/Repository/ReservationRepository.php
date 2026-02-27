<?php

namespace App\Domain\Reservation\Repository;

use App\Domain\Reservation\Entity\ReservationEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReservationEntity::class);
    }

    /** @return ReservationEntity[] */
    public function findAllReservations(): array { return $this->findAll(); }

    /** @return ReservationEntity[] */
    public function findByBorrowerAccountId(int $borrowerAccountId): array { return $this->findBy(['borrowerAccountId' => $borrowerAccountId]); }

    /** @return ReservationEntity[] */
    public function findByCurrentStatus(string $currentStatus): array { return $this->findBy(['currentStatus' => $currentStatus]); }

    public function findOneByReservationCode(string $reservationCode): ?ReservationEntity { return $this->findOneBy(['reservationCode' => $reservationCode]); }

    public function persistReservation(ReservationEntity $entity): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($entity);
        $entityManager->flush();
    }

    // ===== AI GENERATED: generateReservationCode =====
    // Purpose: Generate unique reservation code in TR-YYYY-NNN format
    // Inputs: none
    // Returns: string
    // Flow:
    // 1. Get current year
    // 2. Count existing reservations for that year
    // 3. Return formatted code

    public function generateReservationCode(): string
    {
        $currentYear = date('Y');
        $countResult = $this->createQueryBuilder('rsrv')
            ->select('COUNT(rsrv.reservationIdentifier)')
            ->where('rsrv.reservationCode LIKE :yearPrefix')
            ->setParameter('yearPrefix', 'TR-' . $currentYear . '-%')
            ->getQuery()
            ->getSingleScalarResult();

        $nextNumber = ((int)$countResult) + 1;
        return sprintf('TR-%s-%03d', $currentYear, $nextNumber);
    }
}
