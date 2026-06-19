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
    public function findBySubmissionDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('rsrv')
            ->where('rsrv.submissionTimestamp BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('rsrv.submissionTimestamp', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return ReservationEntity[] */
    public function findByEventDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('rsrv')
            ->where('rsrv.eventDateTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('rsrv.eventDateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return ReservationEntity[] */
    public function findVenueReservationsOverlappingRange(array $venueIdentifiers, \DateTimeInterface $rangeStart, \DateTimeInterface $rangeEnd): array
    {
        if ($venueIdentifiers === []) {
            return [];
        }

        return $this->createQueryBuilder('rsrv')
            ->where('rsrv.venueIdentifier IN (:venueIdentifiers)')
            ->andWhere('rsrv.currentStatus NOT IN (:excludedStatuses)')
            ->andWhere('rsrv.eventDateTime < :rangeEnd')
            ->andWhere('(rsrv.endDateTime IS NULL AND rsrv.eventDateTime >= :rangeStart) OR (rsrv.endDateTime IS NOT NULL AND rsrv.endDateTime > :rangeStart)')
            ->setParameter('venueIdentifiers', $venueIdentifiers)
            ->setParameter('excludedStatuses', ['Rejected', 'Cancelled', 'Request Revision'])
            ->setParameter('rangeStart', $rangeStart)
            ->setParameter('rangeEnd', $rangeEnd)
            ->orderBy('rsrv.eventDateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return ReservationEntity[] */
    public function findAcceptedVenueReservationsOverlappingRange(
        int $venueIdentifier,
        \DateTimeInterface $rangeStart,
        \DateTimeInterface $rangeEnd,
        ?int $excludeReservationIdentifier = null
    ): array {
        $queryBuilder = $this->createQueryBuilder('rsrv')
            ->where('rsrv.venueIdentifier = :venueIdentifier')
            ->andWhere('rsrv.currentStatus IN (:acceptedStatuses)')
            ->andWhere('rsrv.eventDateTime < :rangeEnd')
            ->andWhere('(rsrv.endDateTime IS NULL AND rsrv.eventDateTime >= :rangeStart) OR (rsrv.endDateTime IS NOT NULL AND rsrv.endDateTime > :rangeStart)')
            ->setParameter('venueIdentifier', $venueIdentifier)
            ->setParameter('acceptedStatuses', ['Approved', 'Prepared', 'Deployed'])
            ->setParameter('rangeStart', $rangeStart)
            ->setParameter('rangeEnd', $rangeEnd)
            ->orderBy('rsrv.eventDateTime', 'ASC');

        if ($excludeReservationIdentifier !== null) {
            $queryBuilder
                ->andWhere('rsrv.reservationIdentifier != :excludeReservationIdentifier')
                ->setParameter('excludeReservationIdentifier', $excludeReservationIdentifier);
        }

        return $queryBuilder->getQuery()->getResult();
    }

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
