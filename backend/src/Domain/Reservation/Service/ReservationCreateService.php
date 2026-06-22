<?php

namespace App\Domain\Reservation\Service;

use App\Domain\Account\Repository\AccountRepository;
use App\Domain\Notification\Service\NotificationDispatchService;
use App\Domain\Reservation\DTO\ReservationCreateRequestDTO;
use App\Domain\Reservation\DTO\ReservationResponseDTO;
use App\Domain\Reservation\Entity\ReservationEntity;
use App\Domain\Reservation\Repository\ReservationRepository;
use App\Shared\Exceptions\DomainValidationException;
use App\Shared\Utils\RoleConstants;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class ReservationCreateService
{
    private ReservationRepository $reservationRepository;
    private bool $reservationSchemaEnsured = false;

    public function __construct(
        ReservationRepository $reservationRepository,
        private readonly Connection $connection,
        private readonly ReservationBookingPolicyService $reservationBookingPolicyService,
        private readonly NotificationDispatchService $notificationDispatchService,
        private readonly AccountRepository $accountRepository
    ) {
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

        $this->reservationBookingPolicyService->validateReservationWindow($requestDTO, $eventDateTime, $endDateTime);

        foreach ($requestDTO->requestedEquipmentList as $equipmentItem) {
            $requestedQuantity = (int)($equipmentItem['quantity'] ?? 0);
            if ($requestedQuantity <= 0) {
                throw new DomainValidationException('Each requested equipment quantity must be at least 1.');
            }
        }

        $reservationCode = $this->generateReservationCode();

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

        $this->persistReservationWithFallback($entity);
        $this->notifyAdminsOfSubmittedReservation($entity);

        try {
            return $this->transformEntityToDTO($entity);
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'Reservation Creation - Response mapping fallback [%s]: %s',
                $exception::class,
                $exception->getMessage()
            ));

            return $this->buildFallbackResponseDTO($entity);
        }
    }

    private function transformEntityToDTO(ReservationEntity $entity): ReservationResponseDTO
    {
        return new ReservationResponseDTO(
            reservationIdentifier: $entity->getReservationIdentifier(),
            reservationCode: $entity->getReservationCode(),
            borrowerAccountId: $entity->getBorrowerAccountId(),
            organizationName: $entity->getOrganizationName(),
            venueIdentifier: $entity->getVenueIdentifier(),
            venueName: null,
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

    private function buildFallbackResponseDTO(ReservationEntity $entity): ReservationResponseDTO
    {
        $eventDateTime = $entity->getEventDateTime();
        $endDateTime = $entity->getEndDateTime() ?? $eventDateTime;
        $submissionTimestamp = $entity->getSubmissionTimestamp() ?? new \DateTime();

        return new ReservationResponseDTO(
            reservationIdentifier: (int)($entity->getReservationIdentifier() ?? 0),
            reservationCode: $entity->getReservationCode(),
            borrowerAccountId: $entity->getBorrowerAccountId(),
            organizationName: $entity->getOrganizationName(),
            venueIdentifier: $entity->getVenueIdentifier(),
            venueName: null,
            requestedEquipmentList: $entity->getRequestedEquipmentList(),
            requestedQuantity: $entity->getRequestedQuantity(),
            eventDateTime: $eventDateTime->format(\DateTime::ATOM),
            endDateTime: $endDateTime->format(\DateTime::ATOM),
            activityTimeRange: $this->buildActivityTimeRange($entity),
            purposeDescription: $entity->getPurposeDescription(),
            activityType: $entity->getActivityType(),
            currentStatus: $entity->getCurrentStatus(),
            priorityLevel: $entity->getPriorityLevel(),
            rejectionReason: $entity->getRejectionReason(),
            supportingDocuments: $entity->getSupportingDocuments(),
            submissionTimestamp: $submissionTimestamp->format(\DateTime::ATOM)
        );
    }

    private function ensureReservationSchemaReady(): void
    {
        if ($this->reservationSchemaEnsured) {
            return;
        }

        $columns = $this->connection->fetchAllAssociative(
            "SELECT column_name, data_type
             FROM information_schema.columns
             WHERE table_schema = CURRENT_SCHEMA()
               AND table_name = 'reservations'"
        );

        if ($columns === []) {
            $this->reservationSchemaEnsured = true;
            return;
        }

        $missingColumns = [];
        $columnNames = array_map(
            static fn (array $column): string => strtolower((string)($column['column_name'] ?? '')),
            $columns
        );

        foreach (['end_date_time', 'updated_timestamp'] as $expectedColumn) {
            if (!in_array($expectedColumn, $columnNames, true)) {
                $missingColumns[] = $expectedColumn;
            }
        }

        if ($missingColumns !== []) {
            error_log(sprintf(
                'Reservation Creation - Legacy schema detected, continuing without runtime migration. Missing columns: %s',
                implode(', ', $missingColumns)
            ));
        }

        $this->reservationSchemaEnsured = true;
    }

    private function persistReservationWithFallback(ReservationEntity $entity): void
    {
        try {
            $this->persistReservationViaConnection($entity);
            return;
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'Reservation Creation - Direct persist failed [%s]: %s',
                $exception::class,
                $exception->getMessage()
            ));
        }

        $this->reservationRepository->persistReservation($entity);
    }

    private function generateReservationCode(): string
    {
        try {
            return $this->reservationRepository->generateReservationCode();
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'Reservation Creation - Repository code generation failed [%s]: %s',
                $exception::class,
                $exception->getMessage()
            ));
        }

        $currentYear = date('Y');
        $countResult = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM reservations WHERE reservation_code LIKE :yearPrefix',
            ['yearPrefix' => 'TR-' . $currentYear . '-%'],
            ['yearPrefix' => ParameterType::STRING]
        );

        return sprintf('TR-%s-%03d', $currentYear, ((int)$countResult) + 1);
    }

    private function persistReservationViaConnection(ReservationEntity $entity): void
    {
        $columnsByName = $this->fetchReservationColumnsByName();
        if ($columnsByName === []) {
            throw new \RuntimeException('Reservations table schema is unavailable for fallback persistence.');
        }

        $requestedEquipmentJson = json_encode($entity->getRequestedEquipmentList(), JSON_THROW_ON_ERROR);
        $supportingDocuments = $entity->getSupportingDocuments();
        $supportingDocumentsJson = $supportingDocuments === null ? null : json_encode($supportingDocuments, JSON_THROW_ON_ERROR);

        $rawValues = [
            'reservation_code' => $entity->getReservationCode(),
            'borrower_account_id' => $entity->getBorrowerAccountId(),
            'organization_name' => $entity->getOrganizationName(),
            'venue_identifier' => $entity->getVenueIdentifier(),
            'requested_equipment_list' => $requestedEquipmentJson,
            'requested_quantity' => $entity->getRequestedQuantity(),
            'event_date_time' => $entity->getEventDateTime()->format('Y-m-d H:i:s'),
            'end_date_time' => $entity->getEndDateTime()?->format('Y-m-d H:i:s'),
            'purpose_description' => $entity->getPurposeDescription(),
            'activity_type' => $entity->getActivityType(),
            'current_status' => $entity->getCurrentStatus(),
            'priority_level' => $entity->getPriorityLevel(),
            'rejection_reason' => $entity->getRejectionReason(),
            'supporting_documents' => $supportingDocumentsJson,
            'submission_timestamp' => $entity->getSubmissionTimestamp()->format('Y-m-d H:i:s'),
            'updated_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        $insertColumns = [];
        $placeholders = [];
        $parameters = [];
        $parameterTypes = [];

        foreach ($rawValues as $columnName => $value) {
            if (!isset($columnsByName[$columnName])) {
                continue;
            }

            $insertColumns[] = $columnName;
            $placeholders[] = $this->buildInsertPlaceholder($columnName, $columnsByName[$columnName]);
            $parameters[$columnName] = $value;
            $parameterTypes[$columnName] = $this->resolveParameterType($value);
        }

        if ($insertColumns === []) {
            throw new \RuntimeException('No compatible reservation columns were found for fallback persistence.');
        }

        $sql = sprintf(
            'INSERT INTO reservations (%s) VALUES (%s) RETURNING reservation_identifier, submission_timestamp',
            implode(', ', $insertColumns),
            implode(', ', $placeholders)
        );

        $row = $this->connection->fetchAssociative($sql, $parameters, $parameterTypes);
        if ($row === false || $row === []) {
            throw new \RuntimeException('Fallback reservation insert did not return a reservation identifier.');
        }

        $this->hydratePersistedReservationEntity($entity, $row);
    }

    private function fetchReservationColumnsByName(): array
    {
        $columns = $this->connection->fetchAllAssociative(
            "SELECT column_name, data_type, udt_name
             FROM information_schema.columns
             WHERE table_schema = CURRENT_SCHEMA()
               AND table_name = 'reservations'"
        );

        $columnsByName = [];
        foreach ($columns as $column) {
            $columnName = strtolower(trim((string)($column['column_name'] ?? '')));
            if ($columnName === '') {
                continue;
            }

            $dataType = strtolower(trim((string)($column['data_type'] ?? '')));
            $udtName = strtolower(trim((string)($column['udt_name'] ?? '')));
            $columnsByName[$columnName] = $udtName !== '' ? $udtName : $dataType;
        }

        return $columnsByName;
    }

    private function buildInsertPlaceholder(string $columnName, string $columnType): string
    {
        $placeholder = ':' . $columnName;

        if (str_contains($columnType, 'jsonb')) {
            return 'CAST(' . $placeholder . ' AS JSONB)';
        }

        if (str_contains($columnType, 'json')) {
            return 'CAST(' . $placeholder . ' AS JSON)';
        }

        return $placeholder;
    }

    private function resolveParameterType(mixed $value): int
    {
        if ($value === null) {
            return ParameterType::NULL;
        }

        if (is_int($value)) {
            return ParameterType::INTEGER;
        }

        return ParameterType::STRING;
    }

    private function hydratePersistedReservationEntity(ReservationEntity $entity, array $row): void
    {
        $reservationIdentifier = (int)($row['reservation_identifier'] ?? 0);
        if ($reservationIdentifier > 0) {
            $identifierProperty = new \ReflectionProperty($entity, 'reservationIdentifier');
            $identifierProperty->setAccessible(true);
            $identifierProperty->setValue($entity, $reservationIdentifier);
        }

        $submissionTimestamp = (string)($row['submission_timestamp'] ?? '');
        if ($submissionTimestamp !== '') {
            $submissionProperty = new \ReflectionProperty($entity, 'submissionTimestamp');
            $submissionProperty->setAccessible(true);
            $submissionProperty->setValue($entity, new \DateTime($submissionTimestamp));
        }
    }

    private function notifyAdminsOfSubmittedReservation(ReservationEntity $reservation): void
    {
        try {
            $adminAccounts = $this->accountRepository->findActiveApprovedAccountsByRoles([RoleConstants::ROLE_ADMIN]);
            if ($adminAccounts === []) {
                return;
            }

            $title = 'New Reservation Request';
            $message = sprintf(
                '%s submitted %s scheduled for %s.',
                $this->resolveBorrowerDisplayName($reservation->getBorrowerAccountId()),
                $this->describeReservationResources($reservation),
                $reservation->getEventDateTime()->format('F j, Y g:i A')
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
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'Reservation Creation - Admin notification skipped [%s]: %s',
                $exception::class,
                $exception->getMessage()
            ));
        }
    }

    private function describeReservationResources(ReservationEntity $reservation): string
    {
        $hasVenue = $reservation->getVenueIdentifier() !== null;
        $equipmentItems = $reservation->getRequestedEquipmentList();
        $hasEquipment = is_array($equipmentItems) && $equipmentItems !== [];

        if ($hasVenue && $hasEquipment) {
            return 'a venue and equipment request';
        }

        if ($hasVenue) {
            return 'a venue request';
        }

        if ($hasEquipment) {
            return 'an equipment request';
        }

        return 'a reservation request';
    }

    private function resolveBorrowerDisplayName(int $borrowerAccountId): string
    {
        $borrowerAccount = $this->accountRepository->find($borrowerAccountId);
        $borrowerName = trim(sprintf(
            '%s %s',
            (string)($borrowerAccount?->getFirstName() ?? ''),
            (string)($borrowerAccount?->getLastName() ?? '')
        ));

        return $borrowerName !== '' ? $borrowerName : 'A borrower';
    }
}
