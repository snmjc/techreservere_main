<?php

namespace App\Domain\Reservation\Service;

use App\Domain\Reservation\DTO\ReservationCreateRequestDTO;
use App\Domain\Reservation\DTO\ReservationResponseDTO;
use App\Domain\Reservation\Entity\ReservationEntity;
use App\Domain\Reservation\Repository\ReservationRepository;
use App\Shared\Exceptions\DomainValidationException;

class ReservationCreateService
{
    private ReservationRepository $reservationRepository;

    public function __construct(ReservationRepository $reservationRepository)
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
        if (empty($requestDTO->organizationName)) {
            throw new DomainValidationException('Organization name is required.');
        }
        if (empty($requestDTO->requestedEquipmentList)) {
            throw new DomainValidationException('At least one equipment must be requested.');
        }
        if (empty($requestDTO->eventDateTime)) {
            throw new DomainValidationException('Event date and time is required.');
        }

        $reservationCode = $this->reservationRepository->generateReservationCode();

        $entity = new ReservationEntity();
        $entity->setReservationCode($reservationCode);
        $entity->setBorrowerAccountId($borrowerAccountId);
        $entity->setOrganizationName($requestDTO->organizationName);
        $entity->setVenueIdentifier($requestDTO->venueIdentifier);
        $entity->setRequestedEquipmentList($requestDTO->requestedEquipmentList);
        $entity->setRequestedQuantity($requestDTO->requestedQuantity);
        $entity->setEventDateTime(new \DateTime($requestDTO->eventDateTime));
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
            purposeDescription: $entity->getPurposeDescription(),
            activityType: $entity->getActivityType(),
            currentStatus: $entity->getCurrentStatus(),
            priorityLevel: $entity->getPriorityLevel(),
            rejectionReason: $entity->getRejectionReason(),
            supportingDocuments: $entity->getSupportingDocuments(),
            submissionTimestamp: $entity->getSubmissionTimestamp()->format(\DateTime::ATOM)
        );
    }
}
