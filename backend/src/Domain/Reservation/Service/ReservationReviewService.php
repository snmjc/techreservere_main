<?php

namespace App\Domain\Reservation\Service;

use App\Domain\Reservation\DTO\ReservationResponseDTO;
use App\Domain\Reservation\Entity\ReservationEntity;
use App\Domain\Reservation\Repository\ReservationRepository;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;

class ReservationReviewService
{
    private ReservationRepository $reservationRepository;

    public function __construct(ReservationRepository $reservationRepository)
    {
        $this->reservationRepository = $reservationRepository;
    }

    // ===== AI GENERATED: getAllReservations =====
    // Purpose: Retrieve all reservations (Admin view)
    // Inputs: none
    // Returns: ReservationResponseDTO[]

    /** @return ReservationResponseDTO[] */
    public function getAllReservations(): array
    {
        $entities = $this->reservationRepository->findAllReservations();
        return array_map(fn($entity) => $this->transformEntityToDTO($entity), $entities); // entity → DTO map
    }

    // ===== AI GENERATED: getReservationsByBorrower =====
    // Purpose: Retrieve reservations for specific borrower (own only)
    // Inputs: borrowerAccountId (int)
    // Returns: ReservationResponseDTO[]

    /** @return ReservationResponseDTO[] */
    public function getReservationsByBorrower(int $borrowerAccountId): array
    {
        $entities = $this->reservationRepository->findByBorrowerAccountId($borrowerAccountId);
        return array_map(fn($entity) => $this->transformEntityToDTO($entity), $entities); // entity → DTO map
    }

    // ===== AI GENERATED: updateReservationStatus =====
    // Purpose: Admin approves/rejects/requests revision on a reservation
    // Inputs: reservationIdentifier (int), newStatus (string), rejectionReason (string|null)
    // Returns: ReservationResponseDTO
    // Flow:
    // 1. Find reservation
    // 2. Validate status transition
    // 3. Update and persist

    public function updateReservationStatus(int $reservationIdentifier, string $newStatus, ?string $rejectionReason = null): ReservationResponseDTO
    {
        $entity = $this->reservationRepository->find($reservationIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Reservation not found: ' . $reservationIdentifier);
        }

        $allowedStatuses = ['Approved', 'Rejected', 'Request Revision', 'Prepared', 'Deployed', 'Returned', 'Completed', 'Cancelled'];
        if (!in_array($newStatus, $allowedStatuses, true)) {
            throw new DomainValidationException('Invalid status: ' . $newStatus);
        }

        if ($newStatus === 'Rejected' && empty($rejectionReason)) {
            throw new DomainValidationException('Rejection reason is mandatory.');
        }

        $entity->setCurrentStatus($newStatus);
        if ($rejectionReason !== null) {
            $entity->setRejectionReason($rejectionReason);
        }

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
