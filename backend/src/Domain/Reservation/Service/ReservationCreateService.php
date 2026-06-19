<?php

namespace App\Domain\Reservation\Service;

use App\Domain\Reservation\DTO\ReservationCreateRequestDTO;
use App\Domain\Reservation\DTO\ReservationResponseDTO;
use App\Domain\Reservation\Entity\ReservationEntity;
use App\Domain\Reservation\Repository\ReservationRepository;
use App\Shared\Exceptions\DomainValidationException;
use Doctrine\DBAL\Connection;

class ReservationCreateService
{
    private ReservationRepository $reservationRepository;
    private bool $reservationSchemaEnsured = false;

    public function __construct(ReservationRepository $reservationRepository, private readonly Connection $connection)
    {
        $this->reservationRepository = $reservationRepository;
    }

    // ===== AI GENERATED: createReservation =====
    // Purpose: Create a new reservation from borrower submission
    // Inputs: borrowerAccountId (int), ReservationCreateRequestDTO
    // Returns: ReservationResponseDTO
    // Flow:
    // 1. Validate required fields
    // 2. Generate reservation code
    // 3. Create entity and persist
    // 4. Return response DTO

    public function createReservation(int $borrowerAccountId, ReservationCreateRequestDTO $requestDTO): ReservationResponseDTO
    {
        $this->ensureReservationSchemaReady();
        $today = new \DateTimeImmutable('today');
        $currentYearEnd = new \DateTimeImmutable($today->format('Y') . '-12-31 23:59:59');

        if (empty($requestDTO->organizationName)) {
            throw new DomainValidationException('Organization name is required.');
        }
        if ($requestDTO->requestedQuantity < 1 || $requestDTO->requestedQuantity > 500) {
            throw new DomainValidationException('Participant count must be between 1 and 500.');
        }
        if (empty($requestDTO->eventDateTime)) {
            throw new DomainValidationException('Event date and time is required.');
        }
        if (empty($requestDTO->endDateTime)) {
            throw new DomainValidationException('Reservation end time is required.');
        }

        try {
            $eventDateTime = new \DateTime($requestDTO->eventDateTime);
            $endDateTime = new \DateTime($requestDTO->endDateTime);
        } catch (\Throwable) {
            throw new DomainValidationException('Reservation time range is invalid.');
        }

        if ($endDateTime <= $eventDateTime) {
            throw new DomainValidationException('Reservation end time must be after the start time.');
        }

        if ($eventDateTime < $today || $endDateTime < $today) {
            throw new DomainValidationException('Reservation dates must not be earlier than today.');
        }

        if ($eventDateTime > $currentYearEnd || $endDateTime > $currentYearEnd) {
            throw new DomainValidationException('Reservation dates must be within the current year.');
        }

        foreach ($requestDTO->requestedEquipmentList as $equipmentItem) {
            $requestedQuantity = (int)($equipmentItem['quantity'] ?? 0);
            if ($requestedQuantity <= 0) {
                throw new DomainValidationException('Each requested equipment quantity must be at least 1.');
            }
        }

        $reservationCode = $this->reservationRepository->generateReservationCode();

        $entity = new ReservationEntity();
        $entity->setReservationCode($reservationCode);
        $entity->setBorrowerAccountId($borrowerAccountId);
        $entity->setOrganizationName($requestDTO->organizationName);
        $entity->setVenueIdentifier($requestDTO->venueIdentifier);
        $entity->setRequestedEquipmentList($requestDTO->requestedEquipmentList);
        $entity->setRequestedQuantity($requestDTO->requestedQuantity);
        $entity->setEventDateTime($eventDateTime);
        $entity->setEndDateTime($endDateTime);
        $entity->setPurposeDescription($requestDTO->purposeDescription);
        $entity->setActivityType($requestDTO->activityType);
        $entity->setCurrentStatus('Pending Review');
        $entity->setSupportingDocuments($requestDTO->supportingDocuments);

        $this->reservationRepository->persistReservation($entity);

        return $this->transformEntityToDTO($entity);
    }

    private function transformEntityToDTO(ReservationEntity $entity): ReservationResponseDTO
    {
        return new ReservationResponseDTO(
            reservationIdentifier: $entity->getReservationIdentifier(),
            reservationCode: $entity->getReservationCode(),
            borrowerAccountId: $entity->getBorrowerAccountId(),
            organizationName: $entity->getOrganizationName(),
            venueIdentifier: $entity->getVenueIdentifier(),
            requestedEquipmentList: $entity->getRequestedEquipmentList(),
            requestedQuantity: $entity->getRequestedQuantity(),
            eventDateTime: $entity->getEventDateTime()->format(\DateTime::ATOM),
            endDateTime: ($entity->getEndDateTime() ?? $entity->getEventDateTime())->format(\DateTime::ATOM),
            activityTimeRange: $this->buildActivityTimeRange($entity),
            purposeDescription: $entity->getPurposeDescription(),
            activityType: $entity->getActivityType(),
            currentStatus: $entity->getCurrentStatus(),
            priorityLevel: $entity->getPriorityLevel(),
            rejectionReason: $entity->getRejectionReason(),
            supportingDocuments: $entity->getSupportingDocuments(),
            submissionTimestamp: $entity->getSubmissionTimestamp()->format(\DateTime::ATOM)
        );
    }

    private function buildActivityTimeRange(ReservationEntity $entity): string
    {
        $startDateTime = $entity->getEventDateTime();
        $endDateTime = $entity->getEndDateTime() ?? $startDateTime;

        return sprintf('%s-%s', $startDateTime->format('H:i'), $endDateTime->format('H:i'));
    }

    private function ensureReservationSchemaReady(): void
    {
        if ($this->reservationSchemaEnsured) {
            return;
        }

        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist(['reservations'])) {
            $this->reservationSchemaEnsured = true;
            return;
        }

        $this->connection->executeStatement('ALTER TABLE reservations ADD COLUMN IF NOT EXISTS end_date_time TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->connection->executeStatement("UPDATE reservations SET end_date_time = event_date_time + INTERVAL '30 minutes' WHERE end_date_time IS NULL");

        $this->reservationSchemaEnsured = true;
    }
}
