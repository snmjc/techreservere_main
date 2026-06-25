<?php

namespace App\Domain\Reservation\Repository;

use App\Domain\Reservation\Entity\ReservationEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
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

        $reservations = $this->createQueryBuilder('rsrv')
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

        if ($reservations !== []) {
            return $reservations;
        }

        return $this->findVenueReservationsByCalendarDateFallback($venueIdentifiers, $rangeStart, $rangeEnd);
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

    /** @return ReservationEntity[] */
    private function findVenueReservationsByCalendarDateFallback(array $venueIdentifiers, \DateTimeInterface $rangeStart, \DateTimeInterface $rangeEnd): array
    {
        $selectedDate = $rangeStart->format('Y-m-d');
        $reservationIdentifiers = $this->getEntityManager()->getConnection()->fetchFirstColumn(
            'SELECT reservation_identifier
             FROM reservations
             WHERE venue_identifier IN (:venueIdentifiers)
               AND LOWER(COALESCE(current_status, \'\')) NOT IN (:excludedStatuses)
               AND DATE(event_date_time) <= :selectedDate
               AND DATE(COALESCE(end_date_time, event_date_time)) >= :selectedDate
               AND event_date_time < :rangeEnd
             ORDER BY event_date_time ASC',
            [
                'venueIdentifiers' => $venueIdentifiers,
                'excludedStatuses' => ['rejected', 'cancelled', 'request revision'],
                'selectedDate' => $selectedDate,
                'rangeEnd' => $rangeEnd->format('Y-m-d H:i:s'),
            ],
            [
                'venueIdentifiers' => ArrayParameterType::INTEGER,
                'excludedStatuses' => ArrayParameterType::STRING,
                'selectedDate' => ParameterType::STRING,
                'rangeEnd' => ParameterType::STRING,
            ]
        );

        if ($reservationIdentifiers === []) {
            return [];
        }

        $reservations = $this->createQueryBuilder('rsrv')
            ->where('rsrv.reservationIdentifier IN (:reservationIdentifiers)')
            ->setParameter('reservationIdentifiers', array_map('intval', $reservationIdentifiers))
            ->orderBy('rsrv.eventDateTime', 'ASC')
            ->getQuery()
            ->getResult();

        error_log(sprintf(
            'Venue calendar fallback matched %d reservation(s) for %s.',
            count($reservations),
            $selectedDate
        ));

        return $reservations;
    }

    // ===== AI GENERATED: generateReservationCode =====
    // Purpose: Generate unique reservation code in TR-YYYY-NNN format
    // Inputs: none
    // Returns: string
    // Flow:
    // 1. Get current year
    // 2. Find the highest existing reservation suffix for that year
    // 3. Return formatted code

    public function generateReservationCode(): string
    {
        $currentYear = date('Y');
        $yearPrefix = 'TR-' . $currentYear . '-%';
        $suffixPattern = '^TR-' . $currentYear . '-([0-9]+)$';

        $maxSuffix = $this->getEntityManager()->getConnection()->fetchOne(
            'SELECT COALESCE(MAX(CAST(SUBSTRING(reservation_code FROM :suffixPattern) AS INTEGER)), 0)
             FROM reservations
             WHERE reservation_code LIKE :yearPrefix',
            [
                'suffixPattern' => $suffixPattern,
                'yearPrefix' => $yearPrefix,
            ],
            [
                'suffixPattern' => ParameterType::STRING,
                'yearPrefix' => ParameterType::STRING,
            ]
        );

        $nextNumber = ((int)$maxSuffix) + 1;
        $reservationCode = sprintf('TR-%s-%03d', $currentYear, $nextNumber);

        while ($this->findOneByReservationCode($reservationCode) !== null) {
            $nextNumber++;
            $reservationCode = sprintf('TR-%s-%03d', $currentYear, $nextNumber);
        }

        return $reservationCode;
    }
}
