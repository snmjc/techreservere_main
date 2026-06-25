<?php

namespace App\Domain\Reservation\Service;

use App\Domain\Account\Repository\AccountRepository;
use App\Domain\Equipment\Repository\EquipmentRepository;
use App\Domain\Notification\Service\NotificationDispatchService;
use App\Domain\Reservation\DTO\ReservationCreateRequestDTO;
use App\Domain\Reservation\DTO\ReservationResponseDTO;
use App\Domain\Reservation\Entity\ReservationEntity;
use App\Domain\Reservation\Repository\ReservationRepository;
use App\Domain\Venue\Entity\VenueEntity;
use App\Domain\Venue\Repository\VenueRepository;
use App\Shared\Exceptions\DomainValidationException;
use App\Shared\Utils\AppClock;
use App\Shared\Utils\RoleConstants;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class ReservationCreateService
{
    private const BUSINESS_START_MINUTES = 420;
    private const BUSINESS_END_MINUTES = 1140;

    private ReservationRepository $reservationRepository;
    private bool $reservationSchemaEnsured = false;

    public function __construct(
        ReservationRepository $reservationRepository,
        private readonly Connection $connection,
        private readonly ReservationBookingPolicyService $reservationBookingPolicyService,
        private readonly NotificationDispatchService $notificationDispatchService,
        private readonly AccountRepository $accountRepository,
        private readonly VenueRepository $venueRepository,
        private readonly EquipmentRepository $equipmentRepository
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
        $today = AppClock::now()->setTime(0, 0);
        $activityTitle = trim($requestDTO->organizationName);
        $activityType = trim($requestDTO->activityType);
        $purposeDescription = trim($requestDTO->purposeDescription);
        $borrowerRemarks = trim((string)($requestDTO->borrowerRemarks ?? ''));

        if ($activityTitle === '') {
            throw new DomainValidationException('Organization name is required.');
        }
        if (mb_strlen($activityTitle) > 120 || mb_strlen($activityType) > 120) {
            throw new DomainValidationException('Activity name or title must be 120 characters or fewer.');
        }
        if ($purposeDescription === '') {
            throw new DomainValidationException('Purpose is required.');
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
            $eventDateTime = (new \DateTimeImmutable($requestDTO->eventDateTime))
                ->setTimezone(AppClock::timezone());
            $endDateTime = (new \DateTimeImmutable($requestDTO->endDateTime))
                ->setTimezone(AppClock::timezone());
        } catch (\Throwable) {
            throw new DomainValidationException('Reservation time range is invalid.');
        }

        if ($endDateTime <= $eventDateTime) {
            throw new DomainValidationException('Reservation end time must be after the start time.');
        }

        if (!$this->isAllowedReservationTimeSlot($eventDateTime) || !$this->isAllowedReservationTimeSlot($endDateTime)) {
            throw new DomainValidationException('Activity time must be between 7:00 AM and 7:00 PM using :00 or :30 increments.');
        }

        if ($eventDateTime < $today || $endDateTime < $today) {
            throw new DomainValidationException('Reservation dates must not be earlier than today.');
        }

        $this->reservationBookingPolicyService->validateReservationWindow($requestDTO, $eventDateTime, $endDateTime);
        $this->validateSelectedVenue($requestDTO, $eventDateTime, $endDateTime);
        $this->validateRequestedEquipment($requestDTO->requestedEquipmentList);

        $reservationCode = $this->generateReservationCode();

        $entity = new ReservationEntity();
        $entity->setReservationCode($reservationCode);
        $entity->setBorrowerAccountId($borrowerAccountId);
        $entity->setOrganizationName($activityTitle);
        $entity->setVenueIdentifier($requestDTO->venueIdentifier);
        $entity->setRequestedEquipmentList($requestDTO->requestedEquipmentList);
        $entity->setRequestedQuantity($requestDTO->requestedQuantity);
        $entity->setEventDateTime($eventDateTime);
        $entity->setEndDateTime($endDateTime);
        $entity->setPurposeDescription($purposeDescription);
        $entity->setActivityType($activityType === '' ? $activityTitle : $activityType);
        $entity->setBorrowerRemarks($borrowerRemarks !== '' ? $borrowerRemarks : null);
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
            borrowerRemarks: $entity->getBorrowerRemarks(),
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
            borrowerRemarks: $entity->getBorrowerRemarks(),
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

        foreach (['end_date_time', 'updated_timestamp', 'borrower_remarks'] as $expectedColumn) {
            if (!in_array($expectedColumn, $columnNames, true)) {
                $missingColumns[] = $expectedColumn;
            }
        }

        if (in_array('borrower_remarks', $missingColumns, true)) {
            try {
                $this->connection->executeStatement('ALTER TABLE reservations ADD COLUMN borrower_remarks TEXT DEFAULT NULL');
                $missingColumns = array_values(array_filter(
                    $missingColumns,
                    static fn (string $column): bool => $column !== 'borrower_remarks'
                ));
            } catch (\Throwable $exception) {
                error_log(sprintf(
                    'Reservation Creation - Unable to add borrower_remarks column at runtime [%s]: %s',
                    $exception::class,
                    $exception->getMessage()
                ));
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

        try {
            $this->reservationRepository->persistReservation($entity);
            return;
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'Reservation Creation - Repository persist failed [%s]: %s',
                $exception::class,
                $exception->getMessage()
            ));
        }

        $this->persistReservationViaLegacyInsert($entity);
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
            'borrower_remarks' => $entity->getBorrowerRemarks(),
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
            "SELECT column_name, data_type, udt_name, table_schema
             FROM information_schema.columns
             WHERE table_name = 'reservations'
             ORDER BY CASE
                 WHEN table_schema = CURRENT_SCHEMA() THEN 0
                 WHEN table_schema = 'public' THEN 1
                 ELSE 2
             END,
             ordinal_position ASC"
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

    private function persistReservationViaLegacyInsert(ReservationEntity $entity): void
    {
        $requestedEquipmentJson = json_encode($entity->getRequestedEquipmentList(), JSON_THROW_ON_ERROR);
        $supportingDocuments = $entity->getSupportingDocuments();
        $supportingDocumentsJson = $supportingDocuments === null ? null : json_encode($supportingDocuments, JSON_THROW_ON_ERROR);

        $row = $this->connection->fetchAssociative(
            'INSERT INTO reservations (
                reservation_code,
                borrower_account_id,
                organization_name,
                venue_identifier,
                requested_equipment_list,
                requested_quantity,
                event_date_time,
                purpose_description,
                activity_type,
                borrower_remarks,
                current_status,
                priority_level,
                rejection_reason,
                supporting_documents,
                submission_timestamp
            ) VALUES (
                :reservation_code,
                :borrower_account_id,
                :organization_name,
                :venue_identifier,
                CAST(:requested_equipment_list AS JSONB),
                :requested_quantity,
                :event_date_time,
                :purpose_description,
                :activity_type,
                :borrower_remarks,
                :current_status,
                :priority_level,
                :rejection_reason,
                :supporting_documents,
                :submission_timestamp
            ) RETURNING reservation_identifier, submission_timestamp',
            [
                'reservation_code' => $entity->getReservationCode(),
                'borrower_account_id' => $entity->getBorrowerAccountId(),
                'organization_name' => $entity->getOrganizationName(),
                'venue_identifier' => $entity->getVenueIdentifier(),
                'requested_equipment_list' => $requestedEquipmentJson,
                'requested_quantity' => $entity->getRequestedQuantity(),
                'event_date_time' => $entity->getEventDateTime()->format('Y-m-d H:i:s'),
                'purpose_description' => $entity->getPurposeDescription(),
                'activity_type' => $entity->getActivityType(),
                'borrower_remarks' => $entity->getBorrowerRemarks(),
                'current_status' => $entity->getCurrentStatus(),
                'priority_level' => $entity->getPriorityLevel(),
                'rejection_reason' => $entity->getRejectionReason(),
                'supporting_documents' => $supportingDocumentsJson,
                'submission_timestamp' => $entity->getSubmissionTimestamp()->format('Y-m-d H:i:s'),
            ],
            [
                'reservation_code' => ParameterType::STRING,
                'borrower_account_id' => ParameterType::INTEGER,
                'organization_name' => ParameterType::STRING,
                'venue_identifier' => $entity->getVenueIdentifier() === null ? ParameterType::NULL : ParameterType::INTEGER,
                'requested_equipment_list' => ParameterType::STRING,
                'requested_quantity' => ParameterType::INTEGER,
                'event_date_time' => ParameterType::STRING,
                'purpose_description' => ParameterType::STRING,
                'activity_type' => ParameterType::STRING,
                'borrower_remarks' => $entity->getBorrowerRemarks() === null ? ParameterType::NULL : ParameterType::STRING,
                'current_status' => ParameterType::STRING,
                'priority_level' => $entity->getPriorityLevel() === null ? ParameterType::NULL : ParameterType::STRING,
                'rejection_reason' => $entity->getRejectionReason() === null ? ParameterType::NULL : ParameterType::STRING,
                'supporting_documents' => $supportingDocumentsJson === null ? ParameterType::NULL : ParameterType::STRING,
                'submission_timestamp' => ParameterType::STRING,
            ]
        );

        if ($row === false || $row === []) {
            throw new \RuntimeException('Legacy reservation insert did not return a reservation identifier.');
        }

        $this->hydratePersistedReservationEntity($entity, $row);
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

    private function validateSelectedVenue(
        ReservationCreateRequestDTO $requestDTO,
        \DateTimeInterface $eventDateTime,
        \DateTimeInterface $endDateTime
    ): void
    {
        if ($requestDTO->venueIdentifier === null) {
            return;
        }

        $venue = $this->venueRepository->find($requestDTO->venueIdentifier);
        if ($venue === null) {
            throw new DomainValidationException('Selected room is no longer available.');
        }

        if (!$this->isVenueBookable($venue)) {
            throw new DomainValidationException('Selected room is no longer available.');
        }

        $capacityLimit = (int)($venue->getCapacityLimit() ?? 0);
        if ($capacityLimit <= 0 || $requestDTO->requestedQuantity > $capacityLimit) {
            throw new DomainValidationException(sprintf(
                'Participant count exceeds the selected room capacity of %d.',
                max($capacityLimit, 0)
            ));
        }

        $overlappingReservations = $this->reservationRepository->findVenueReservationsOverlappingRange(
            [$requestDTO->venueIdentifier],
            $eventDateTime,
            $endDateTime
        );

        if ($overlappingReservations !== []) {
            throw new DomainValidationException('Selected room is no longer available for the requested time.');
        }
    }

    private function isVenueBookable(VenueEntity $venue): bool
    {
        if ($venue->getOperationalStatus() !== 'Active') {
            return false;
        }

        if ($venue->getAvailabilityStatus() !== 'Available') {
            return false;
        }

        $availabilityDate = $venue->getAvailabilityDate();
        if ($availabilityDate === null) {
            return true;
        }

        $availableOn = \DateTimeImmutable::createFromInterface($availabilityDate)
            ->setTimezone(AppClock::timezone())
            ->setTime(0, 0);
        $today = AppClock::now()->setTime(0, 0);

        return $availableOn <= $today;
    }

    private function validateRequestedEquipment(array $requestedEquipmentList): void
    {
        $seenEquipmentIdentifiers = [];

        foreach ($requestedEquipmentList as $equipmentItem) {
            $equipmentIdentifier = (int)($equipmentItem['equipmentIdentifier'] ?? 0);
            $requestedQuantity = (int)($equipmentItem['quantity'] ?? $equipmentItem['selectedQuantity'] ?? 0);

            if ($equipmentIdentifier <= 0) {
                throw new DomainValidationException('Each requested equipment item must include a valid equipment identifier.');
            }

            if ($requestedQuantity <= 0) {
                throw new DomainValidationException('Each requested equipment quantity must be at least 1.');
            }

            if (isset($seenEquipmentIdentifiers[$equipmentIdentifier])) {
                throw new DomainValidationException('Duplicate equipment selections are not allowed.');
            }
            $seenEquipmentIdentifiers[$equipmentIdentifier] = true;

            $equipment = $this->equipmentRepository->find($equipmentIdentifier);
            if ($equipment === null) {
                throw new DomainValidationException('Selected equipment was not found.');
            }

            $availableQuantity = $equipment->getAvailableQuantity();
            $equipmentState = strtolower(trim($equipment->getEquipmentState()));
            $operationalStatus = strtolower(trim($equipment->getOperationalStatus()));
            $isAvailable = $availableQuantity > 0
                && ($equipmentState === 'available' || $operationalStatus === 'active' || $operationalStatus === 'available');

            if (!$isAvailable) {
                throw new DomainValidationException(sprintf('Selected equipment "%s" is not available.', $equipment->getEquipmentName()));
            }

            if ($requestedQuantity > $availableQuantity) {
                throw new DomainValidationException(sprintf(
                    'Requested quantity for "%s" exceeds the available quantity of %d.',
                    $equipment->getEquipmentName(),
                    $availableQuantity
                ));
            }
        }
    }

    private function isAllowedReservationTimeSlot(\DateTimeInterface $dateTime): bool
    {
        $minutes = ((int)$dateTime->format('G') * 60) + (int)$dateTime->format('i');
        $minutePart = (int)$dateTime->format('i');

        return in_array($minutePart, [0, 30], true)
            && $minutes >= self::BUSINESS_START_MINUTES
            && $minutes <= self::BUSINESS_END_MINUTES;
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
