<?php

namespace App\Domain\Reservation\Service;

use App\Domain\Account\Repository\AccountRepository;
use App\Domain\Notification\Service\NotificationDispatchService;
use App\Domain\Reservation\DTO\ReservationResponseDTO;
use App\Domain\Reservation\Entity\ReservationEntity;
use App\Domain\Reservation\Repository\ReservationRepository;
use App\Domain\Venue\Repository\VenueRepository;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;
use App\Shared\Utils\RoleConstants;

class ReservationReviewService
{
    private ReservationRepository $reservationRepository;
    private AccountRepository $accountRepository;
    private NotificationDispatchService $notificationDispatchService;
    private VenueRepository $venueRepository;

    public function __construct(
        ReservationRepository $reservationRepository,
        AccountRepository $accountRepository,
        NotificationDispatchService $notificationDispatchService,
        VenueRepository $venueRepository
    )
    {
        $this->reservationRepository = $reservationRepository;
        $this->accountRepository = $accountRepository;
        $this->notificationDispatchService = $notificationDispatchService;
        $this->venueRepository = $venueRepository;
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

        $entity->setCurrentStatus($newStatus);
        if ($rejectionReason !== null) {
            $entity->setRejectionReason($rejectionReason);
        }

        $this->reservationRepository->persistReservation($entity);
        $this->notifyBorrowerOfStatusChange($entity, $newStatus, $rejectionReason);

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
        $this->notifyAdminsOfBorrowerCancellation($entity);

        return $this->transformEntityToDTO($entity);
    }

    private function transformEntityToDTO(ReservationEntity $entity): ReservationResponseDTO
    {
        [$borrowerFirstName, $borrowerLastName, $borrowerFullName] = $this->resolveBorrowerNames($entity);
        $venueName = $this->resolveVenueName($entity);

        return new ReservationResponseDTO(
            reservationIdentifier: $entity->getReservationIdentifier(),
            reservationCode: $entity->getReservationCode(),
            borrowerAccountId: $entity->getBorrowerAccountId(),
            organizationName: $entity->getOrganizationName(),
            venueIdentifier: $entity->getVenueIdentifier(),
            venueName: $venueName,
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
            borrowerFullName: $borrowerFullName
        );
    }

    private function resolveVenueName(ReservationEntity $entity): ?string
    {
        $venueIdentifier = $entity->getVenueIdentifier();
        if ($venueIdentifier === null || $venueIdentifier <= 0) {
            return null;
        }

        $venue = $this->venueRepository->find($venueIdentifier);
        if ($venue === null) {
            return null;
        }

        $venueName = trim((string)$venue->getVenueName());
        return $venueName === '' ? null : $venueName;
    }

    private function resolveBorrowerNames(ReservationEntity $entity): array
    {
        $borrower = $this->accountRepository->find($entity->getBorrowerAccountId());
        $firstName = trim((string)($borrower?->getFirstName() ?? ''));
        $lastName = trim((string)($borrower?->getLastName() ?? ''));
        $fullName = trim(sprintf('%s %s', $firstName, $lastName));

        if ($fullName === '') {
            $fullName = trim($entity->getOrganizationName());
        }

        if ($fullName === '') {
            $fullName = 'User';
        }

        return [$firstName, $lastName, $fullName];
    }

    private function buildActivityTimeRange(ReservationEntity $entity): string
    {
        $startDateTime = $entity->getEventDateTime();
        $endDateTime = $entity->getEndDateTime() ?? $startDateTime;

        return sprintf('%s-%s', $startDateTime->format('H:i'), $endDateTime->format('H:i'));
    }

    private function notifyBorrowerOfStatusChange(ReservationEntity $entity, string $newStatus, ?string $reason): void
    {
        $borrowerAccountId = $entity->getBorrowerAccountId();
        if ($borrowerAccountId <= 0) {
            return;
        }

        $title = sprintf('Reservation %s', $newStatus);
        $message = match ($newStatus) {
            'Approved' => sprintf(
                'Your reservation %s was approved for %s.',
                $entity->getReservationCode(),
                $entity->getEventDateTime()->format('F j, Y g:i A')
            ),
            'Rejected' => sprintf(
                'Your reservation %s was rejected%s.',
                $entity->getReservationCode(),
                $reason ? ': ' . $reason : ''
            ),
            'Prepared', 'Deployed' => sprintf(
                'Your reservation %s is now active for today.',
                $entity->getReservationCode()
            ),
            'Completed' => sprintf(
                'Your reservation %s has been completed.',
                $entity->getReservationCode()
            ),
            'Cancelled' => sprintf(
                'Your reservation %s was cancelled%s.',
                $entity->getReservationCode(),
                $reason ? ': ' . $reason : ''
            ),
            default => sprintf(
                'Your reservation %s is now marked as %s.',
                $entity->getReservationCode(),
                $newStatus
            ),
        };

        $this->notificationDispatchService->sendNotification(
            $borrowerAccountId,
            $title,
            $message,
            'Reservation'
        );
    }

    private function notifyAdminsOfBorrowerCancellation(ReservationEntity $entity): void
    {
        $adminAccounts = $this->accountRepository->findActiveApprovedAccountsByRoles([RoleConstants::ROLE_ADMIN]);
        if ($adminAccounts === []) {
            return;
        }

        $title = 'Reservation Cancelled';
        $message = sprintf(
            '%s cancelled reservation %s scheduled for %s.',
            $this->resolveBorrowerNames($entity)[2],
            $entity->getReservationCode(),
            $entity->getEventDateTime()->format('F j, Y g:i A')
        );

        foreach ($adminAccounts as $adminAccount) {
            $recipientAccountId = (int)($adminAccount->getAccountIdentifier() ?? 0);
            if ($recipientAccountId <= 0) {
                continue;
            }

            $this->notificationDispatchService->sendNotification(
                $recipientAccountId,
                $title,
                $message,
                'Reservation'
            );
        }
    }
}
