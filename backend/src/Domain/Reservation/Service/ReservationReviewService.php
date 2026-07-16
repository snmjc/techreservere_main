<?php

namespace App\Domain\Reservation\Service;

use App\Domain\Account\Entity\AccountEntity;
use App\Domain\Account\Repository\AccountRepository;
use App\Domain\Notification\Service\NotificationDispatchService;
use App\Domain\Reservation\DTO\ReservationResponseDTO;
use App\Domain\Reservation\Entity\ReservationEntity;
use App\Domain\Reservation\Repository\ReservationRepository;
use App\Domain\Task\Service\AutomaticTaskAssignmentService;
use App\Domain\Venue\Repository\VenueRepository;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;
use App\Shared\Utils\RoleConstants;

class ReservationReviewService
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly AccountRepository $accountRepository,
        private readonly NotificationDispatchService $notificationDispatchService,
        private readonly VenueRepository $venueRepository,
        private readonly AutomaticTaskAssignmentService $automaticTaskAssignmentService,
        private readonly ReservationMetadataService $reservationMetadataService
    ) {
    }

    /** @return ReservationResponseDTO[] */
    public function getAllReservations(): array
    {
        $this->reservationMetadataService->ensureSchemaReady();
        $entities = $this->reservationRepository->findAllReservations();
        $metadataByReservation = $this->reservationMetadataService->fetchMetadataByReservationIds(
            array_map(static fn (ReservationEntity $entity): int => (int) $entity->getReservationIdentifier(), $entities)
        );

        return array_map(
            fn (ReservationEntity $entity): ReservationResponseDTO => $this->transformEntityToDTO(
                $entity,
                $metadataByReservation[(int) $entity->getReservationIdentifier()] ?? []
            ),
            $entities
        );
    }

    /** @return ReservationResponseDTO[] */
    public function getReservationsByBorrower(int $borrowerAccountId): array
    {
        $this->reservationMetadataService->ensureSchemaReady();
        $entities = $this->reservationRepository->findByBorrowerAccountId($borrowerAccountId);
        $metadataByReservation = $this->reservationMetadataService->fetchMetadataByReservationIds(
            array_map(static fn (ReservationEntity $entity): int => (int) $entity->getReservationIdentifier(), $entities)
        );

        return array_map(
            fn (ReservationEntity $entity): ReservationResponseDTO => $this->transformEntityToDTO(
                $entity,
                $metadataByReservation[(int) $entity->getReservationIdentifier()] ?? []
            ),
            $entities
        );
    }

    public function getReservationByIdForRole(int $reservationIdentifier, string $resolvedRole, int $accountIdentifier): ReservationResponseDTO
    {
        $entity = $this->reservationRepository->find($reservationIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Reservation not found: ' . $reservationIdentifier);
        }

        if (in_array($resolvedRole, [RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER], true)) {
            return $this->transformEntityToDTO(
                $entity,
                $this->reservationMetadataService->fetchMetadataByReservationIds([$reservationIdentifier])[$reservationIdentifier] ?? []
            );
        }

        if ($resolvedRole !== RoleConstants::ROLE_BORROWER || $entity->getBorrowerAccountId() !== $accountIdentifier) {
            throw new DomainValidationException('You are not allowed to access this reservation.');
        }

        return $this->transformEntityToDTO(
            $entity,
            $this->reservationMetadataService->fetchMetadataByReservationIds([$reservationIdentifier])[$reservationIdentifier] ?? []
        );
    }

    public function updateReservationStatus(int $reservationIdentifier, string $newStatus, ?string $rejectionReason = null): ReservationResponseDTO
    {
        $entity = $this->reservationRepository->find($reservationIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Reservation not found: ' . $reservationIdentifier);
        }

        $newStatus = $this->normalizeReservationStatusLabel($newStatus);
        $allowedStatuses = ['Approved', 'Rejected', 'Request Revision', 'Prepared', 'Deployed', 'Active', 'Returned', 'Completed', 'Cancelled'];
        if (!in_array($newStatus, $allowedStatuses, true)) {
            throw new DomainValidationException('Invalid status: ' . $newStatus);
        }

        $normalizedReason = $rejectionReason === null ? null : trim($rejectionReason);

        $previousStatus = $entity->getCurrentStatus();
        $isNewApproval = $newStatus === 'Approved' && $previousStatus !== 'Approved';
        $assignedToAccountId = null;

        if ($isNewApproval) {
            $this->validateVenueApprovalConflict($entity);
            $assignedToAccountId = $this->automaticTaskAssignmentService->prepareStaffAssignment($entity);
        }

        $entity->setCurrentStatus($newStatus);
        if ($normalizedReason !== null && $normalizedReason !== '') {
            $entity->setRejectionReason($normalizedReason);
        }

        $this->reservationRepository->persistReservation($entity);
        $this->reservationMetadataService->updateStatusRemarks($reservationIdentifier, $newStatus, $normalizedReason);

        if ($isNewApproval) {
            $this->automaticTaskAssignmentService->createTaskForApproval($entity, $assignedToAccountId);
        }

        $this->notifyBorrowerOfStatusChange($entity, $newStatus, $normalizedReason);

        return $this->transformEntityToDTO(
            $entity,
            $this->reservationMetadataService->fetchMetadataByReservationIds([$reservationIdentifier])[$reservationIdentifier] ?? []
        );
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

        $newStatus = $this->normalizeReservationStatusLabel($newStatus);

        if (in_array($resolvedRole, [RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER], true)) {
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

        $borrower = $this->accountRepository->find($accountIdentifier);
        $this->reservationMetadataService->updateStatusRemarks(
            $reservationIdentifier,
            'Cancelled',
            $reason ?: 'Cancelled by requester',
            $borrower?->getAccountIdentifier(),
            $this->formatActorName($borrower),
            $borrower?->getRoleDesignation()
        );
        $this->notifyAdminsOfBorrowerCancellation($entity);

        return $this->transformEntityToDTO(
            $entity,
            $this->reservationMetadataService->fetchMetadataByReservationIds([$reservationIdentifier])[$reservationIdentifier] ?? []
        );
    }

    private function normalizeReservationStatusLabel(string $status): string
    {
        $normalizedStatus = strtolower(trim($status));

        return match ($normalizedStatus) {
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'request revision' => 'Request Revision',
            'prepared' => 'Prepared',
            'deployed' => 'Deployed',
            'returned' => 'Returned',
            'completed' => 'Completed',
            'cancelled', 'canceled' => 'Cancelled',
            'active' => 'Active',
            default => trim($status),
        };
    }

    private function transformEntityToDTO(ReservationEntity $entity, array $metadata = []): ReservationResponseDTO
    {
        [$borrowerFirstName, $borrowerLastName, $borrowerFullName, $borrowerEmailAddress, $borrowerContactNumber] = $this->resolveBorrowerDetails($entity);
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
            borrowerRemarks: $entity->getBorrowerRemarks(),
            adminRemarks: $metadata['adminRemarks'] ?? null,
            approvalRemarks: $metadata['approvalRemarks'] ?? null,
            denialReason: $metadata['denialReason'] ?? null,
            cancellationReason: $metadata['cancellationReason'] ?? null,
            completionRemarks: $metadata['completionRemarks'] ?? null,
            manualOverrideReason: $metadata['manualOverrideReason'] ?? null,
            currentStatus: $entity->getCurrentStatus(),
            priorityLevel: $entity->getPriorityLevel(),
            rejectionReason: $entity->getRejectionReason(),
            supportingDocuments: $entity->getSupportingDocuments(),
            submissionTimestamp: $entity->getSubmissionTimestamp()->format(\DateTime::ATOM),
            remarkEvents: is_array($metadata['remarkEvents'] ?? null) ? $metadata['remarkEvents'] : [],
            borrowerFirstName: $borrowerFirstName,
            borrowerLastName: $borrowerLastName,
            borrowerFullName: $borrowerFullName,
            borrowerEmailAddress: $borrowerEmailAddress,
            borrowerContactNumber: $borrowerContactNumber
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

        $venueName = trim((string) $venue->getVenueName());
        return $venueName === '' ? null : $venueName;
    }

    private function resolveBorrowerDetails(ReservationEntity $entity): array
    {
        $borrower = $this->accountRepository->find($entity->getBorrowerAccountId());
        $firstName = trim((string) ($borrower?->getFirstName() ?? ''));
        $lastName = trim((string) ($borrower?->getLastName() ?? ''));
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

    private function formatActorName(?AccountEntity $account): ?string
    {
        if ($account === null) {
            return null;
        }

        $fullName = trim(sprintf('%s %s', $account->getFirstName(), $account->getLastName()));
        return $fullName === '' ? $account->getEmailAddress() : $fullName;
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
            $this->resolveBorrowerDetails($entity)[2],
            $entity->getReservationCode(),
            $entity->getEventDateTime()->format('F j, Y g:i A')
        );

        foreach ($adminAccounts as $adminAccount) {
            $recipientAccountId = (int) ($adminAccount->getAccountIdentifier() ?? 0);
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
