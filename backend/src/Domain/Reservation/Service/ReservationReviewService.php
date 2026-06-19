<?php

namespace App\Domain\Reservation\Service;

use App\Domain\Account\Repository\AccountRepository;
use App\Domain\Reservation\DTO\ReservationResponseDTO;
use App\Domain\Reservation\Entity\ReservationEntity;
use App\Domain\Reservation\Repository\ReservationRepository;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;
use App\Shared\Utils\RoleConstants;

class ReservationReviewService
{
    private ReservationRepository $reservationRepository;
    private AccountRepository $accountRepository;

    public function __construct(ReservationRepository $reservationRepository, AccountRepository $accountRepository)
    {
        $this->reservationRepository = $reservationRepository;
        $this->accountRepository = $accountRepository;
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

    public function getReservationByIdForRole(int $reservationIdentifier, string $resolvedRole, int $accountIdentifier): ReservationResponseDTO
    {
        $entity = $this->reservationRepository->find($reservationIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Reservation not found: ' . $reservationIdentifier);
        }

        if ($resolvedRole === RoleConstants::ROLE_ADMIN) {
            return $this->transformEntityToDTO($entity);
        }

        if ($resolvedRole !== RoleConstants::ROLE_BORROWER || $entity->getBorrowerAccountId() !== $accountIdentifier) {
            throw new DomainValidationException('You are not allowed to access this reservation.');
        }

        return $this->transformEntityToDTO($entity);
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

        // Rejection reason is optional - use default if not provided
        if ($newStatus === 'Rejected' && empty($rejectionReason)) {
            $rejectionReason = 'Rejected by administrator';
        }

        if ($newStatus === 'Approved') {
            $this->validateVenueApprovalConflict($entity);
        }

        $entity->setCurrentStatus($newStatus);
        if ($rejectionReason !== null) {
            $entity->setRejectionReason($rejectionReason);
        }

        $this->reservationRepository->persistReservation($entity);

        return $this->transformEntityToDTO($entity);
    }

    public function updateReservationStatusForActor(
        int $reservationIdentifier,
        string $newStatus,
        string $resolvedRole,
        int $accountIdentifier,
        ?string $reason = null
    ): ReservationResponseDTO {
        $entity = $this->reservationRepository->find($reservationIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Reservation not found: ' . $reservationIdentifier);
        }

        if ($resolvedRole === RoleConstants::ROLE_ADMIN) {
            return $this->updateReservationStatus($reservationIdentifier, $newStatus, $reason);
        }

        if ($resolvedRole !== RoleConstants::ROLE_BORROWER || $entity->getBorrowerAccountId() !== $accountIdentifier) {
            throw new DomainValidationException('You are not allowed to update this reservation.');
        }

        if ($newStatus !== 'Cancelled') {
            throw new DomainValidationException('Borrowers can only cancel their own reservation requests.');
        }

        $allowedBorrowerStatuses = ['Pending Review', 'Pending', 'Submitted'];
        if (!in_array($entity->getCurrentStatus(), $allowedBorrowerStatuses, true)) {
            throw new DomainValidationException('Only submitted or pending reservation requests can be cancelled.');
        }

        $entity->setCurrentStatus('Cancelled');
        $entity->setRejectionReason($reason ?: 'Cancelled by requester');

        $this->reservationRepository->persistReservation($entity);

        return $this->transformEntityToDTO($entity);
    }

    private function transformEntityToDTO(ReservationEntity $entity): ReservationResponseDTO
    {
        [$borrowerFirstName, $borrowerLastName, $borrowerFullName, $borrowerEmailAddress, $borrowerContactNumber] = $this->resolveBorrowerDetails($entity);

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
            submissionTimestamp: $entity->getSubmissionTimestamp()->format(\DateTime::ATOM),
            borrowerFirstName: $borrowerFirstName,
            borrowerLastName: $borrowerLastName,
            borrowerFullName: $borrowerFullName,
            borrowerEmailAddress: $borrowerEmailAddress,
            borrowerContactNumber: $borrowerContactNumber
        );
    }

    private function resolveBorrowerDetails(ReservationEntity $entity): array
    {
        $borrower = $this->accountRepository->find($entity->getBorrowerAccountId());
        $firstName = trim((string)($borrower?->getFirstName() ?? ''));
        $lastName = trim((string)($borrower?->getLastName() ?? ''));
        $fullName = trim(sprintf('%s %s', $firstName, $lastName));
        $emailAddress = $borrower?->getEmailAddress();
        $contactNumber = $borrower?->getContactNumber();

        if ($fullName === '') {
            $fullName = trim($entity->getOrganizationName());
        }

        if ($fullName === '') {
            $fullName = 'User';
        }

        return [$firstName, $lastName, $fullName, $emailAddress, $contactNumber];
    }

    private function buildActivityTimeRange(ReservationEntity $entity): string
    {
        $startDateTime = $entity->getEventDateTime();
        $endDateTime = $entity->getEndDateTime() ?? $startDateTime;

        return sprintf('%s-%s', $startDateTime->format('H:i'), $endDateTime->format('H:i'));
    }

    private function validateVenueApprovalConflict(ReservationEntity $entity): void
    {
        $venueIdentifier = $entity->getVenueIdentifier();
        if ($venueIdentifier === null) {
            return;
        }

        $rangeStart = $entity->getEventDateTime();
        $rangeEnd = $entity->getEndDateTime() ?? $rangeStart;
        if ($rangeEnd <= $rangeStart) {
            $rangeEnd = (clone $rangeStart)->modify('+1 minute');
        }

        $overlappingReservations = $this->reservationRepository->findAcceptedVenueReservationsOverlappingRange(
            $venueIdentifier,
            $rangeStart,
            $rangeEnd,
            $entity->getReservationIdentifier()
        );

        if ($overlappingReservations === []) {
            return;
        }

        $conflict = $overlappingReservations[0];
        $conflictEndDateTime = $conflict->getEndDateTime() ?? $conflict->getEventDateTime();

        throw new DomainValidationException(sprintf(
            'This venue is already approved for Reservation %s on %s from %s to %s.',
            $conflict->getReservationCode(),
            $conflict->getEventDateTime()->format('F j, Y'),
            $conflict->getEventDateTime()->format('g:i A'),
            $conflictEndDateTime->format('g:i A')
        ));
    }
}
