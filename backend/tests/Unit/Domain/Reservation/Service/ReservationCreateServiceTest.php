<?php

namespace App\Tests\Unit\Domain\Reservation\Service;

use App\Domain\Account\Repository\AccountRepository;
use App\Domain\Equipment\Entity\EquipmentEntity;
use App\Domain\Equipment\Repository\EquipmentRepository;
use App\Domain\Notification\Service\NotificationDispatchService;
use App\Domain\Reservation\DTO\ReservationCreateRequestDTO;
use App\Domain\Reservation\Entity\ReservationEntity;
use App\Domain\Reservation\Repository\ReservationRepository;
use App\Domain\Reservation\Service\ReservationBookingPolicyService;
use App\Domain\Reservation\Service\ReservationCreateService;
use App\Domain\Venue\Entity\VenueEntity;
use App\Domain\Venue\Repository\VenueRepository;
use App\Shared\Exceptions\DomainValidationException;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ReservationCreateServiceTest extends TestCase
{
    private ReservationRepository|MockObject $reservationRepository;
    private Connection|MockObject $connection;
    private ReservationBookingPolicyService|MockObject $reservationBookingPolicyService;
    private NotificationDispatchService|MockObject $notificationDispatchService;
    private AccountRepository|MockObject $accountRepository;
    private VenueRepository|MockObject $venueRepository;
    private EquipmentRepository|MockObject $equipmentRepository;
    private int $equipmentAvailableQuantity = 10;
    private string $equipmentState = 'Available';
    private string $equipmentOperationalStatus = 'Available';
    private int $venueCapacityLimit = 500;
    private string $venueAvailabilityStatus = 'Available';
    private string $venueOperationalStatus = 'Active';
    private ?\DateTimeInterface $venueAvailabilityDate = null;
    private array $overlappingVenueReservations = [];
    private ReservationCreateService $service;

    protected function setUp(): void
    {
        $this->reservationRepository = $this->createMock(ReservationRepository::class);
        $this->reservationRepository
            ->method('findVenueReservationsOverlappingRange')
            ->willReturnCallback(fn (): array => $this->overlappingVenueReservations);
        $this->connection = $this->createMock(Connection::class);
        $this->reservationBookingPolicyService = $this->createMock(ReservationBookingPolicyService::class);
        $this->notificationDispatchService = $this->createMock(NotificationDispatchService::class);
        $this->accountRepository = $this->createMock(AccountRepository::class);
        $this->venueRepository = $this->createMock(VenueRepository::class);
        $this->venueRepository
            ->method('find')
            ->willReturnCallback(fn (): VenueEntity|MockObject => $this->createVenueEntity());
        $this->equipmentRepository = $this->createMock(EquipmentRepository::class);
        $this->equipmentRepository
            ->method('find')
            ->willReturnCallback(fn (): EquipmentEntity|MockObject => $this->createEquipmentEntity());
        $this->service = new ReservationCreateService(
            $this->reservationRepository,
            $this->connection,
            $this->reservationBookingPolicyService,
            $this->notificationDispatchService,
            $this->accountRepository,
            $this->venueRepository,
            $this->equipmentRepository
        );

        $schemaReadyProperty = new \ReflectionProperty($this->service, 'reservationSchemaEnsured');
        $schemaReadyProperty->setAccessible(true);
        $schemaReadyProperty->setValue($this->service, true);
    }

    public function testCreateReservationRejectsParticipantCountAboveFiveHundred(): void
    {
        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('Participant count must be between 1 and 500.');

        $this->service->createReservation(10, new ReservationCreateRequestDTO(
            organizationName: 'Capstone Defense',
            venueIdentifier: 1,
            requestedEquipmentList: [],
            requestedQuantity: 501,
            eventDateTime: $this->buildIsoDateTime('+1 day 09:00'),
            endDateTime: $this->buildIsoDateTime('+1 day 10:00'),
            purposeDescription: 'Academic',
            activityType: 'Defense',
            supportingDocuments: null
        ));
    }

    public function testCreateReservationPropagatesBookingWindowValidation(): void
    {
        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('Reservations are currently limited to the active booking period.');

        $this->reservationBookingPolicyService
            ->expects($this->once())
            ->method('validateReservationWindow')
            ->willThrowException(new DomainValidationException('Reservations are currently limited to the active booking period.'));

        $this->service->createReservation(10, new ReservationCreateRequestDTO(
            organizationName: 'Capstone Defense',
            venueIdentifier: 1,
            requestedEquipmentList: [],
            requestedQuantity: 100,
            eventDateTime: $this->buildIsoDateTime('+90 days 09:00'),
            endDateTime: $this->buildIsoDateTime('+90 days 10:00'),
            purposeDescription: 'Academic',
            activityType: 'Defense',
            supportingDocuments: null
        ));
    }

    public function testCreateReservationRejectsEquipmentQuantityBelowOne(): void
    {
        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('Each requested equipment quantity must be at least 1.');

        $this->service->createReservation(10, new ReservationCreateRequestDTO(
            organizationName: 'Capstone Defense',
            venueIdentifier: 1,
            requestedEquipmentList: [
                ['equipmentIdentifier' => 9, 'name' => 'Projector', 'quantity' => 0],
            ],
            requestedQuantity: 100,
            eventDateTime: $this->buildIsoDateTime('+1 day 09:00'),
            endDateTime: $this->buildIsoDateTime('+1 day 10:00'),
            purposeDescription: 'Academic',
            activityType: 'Defense',
            supportingDocuments: null
        ));
    }

    public function testCreateReservationRejectsEquipmentQuantityAboveAvailability(): void
    {
        $this->equipmentAvailableQuantity = 2;

        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('Requested quantity for "Projector" exceeds the available quantity of 2.');

        $this->service->createReservation(10, new ReservationCreateRequestDTO(
            organizationName: 'Capstone Defense',
            venueIdentifier: 1,
            requestedEquipmentList: [
                ['equipmentIdentifier' => 9, 'name' => 'Projector', 'quantity' => 3],
            ],
            requestedQuantity: 100,
            eventDateTime: $this->buildIsoDateTime('+1 day 09:00'),
            endDateTime: $this->buildIsoDateTime('+1 day 10:00'),
            purposeDescription: 'Academic',
            activityType: 'Defense',
            supportingDocuments: null
        ));
    }

    public function testCreateReservationRejectsUnavailableEquipment(): void
    {
        $this->equipmentAvailableQuantity = 0;
        $this->equipmentState = 'Unavailable';
        $this->equipmentOperationalStatus = 'Unavailable';

        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('Selected equipment "Projector" is not available.');

        $this->service->createReservation(10, new ReservationCreateRequestDTO(
            organizationName: 'Capstone Defense',
            venueIdentifier: 1,
            requestedEquipmentList: [
                ['equipmentIdentifier' => 9, 'name' => 'Projector', 'quantity' => 1],
            ],
            requestedQuantity: 100,
            eventDateTime: $this->buildIsoDateTime('+1 day 09:00'),
            endDateTime: $this->buildIsoDateTime('+1 day 10:00'),
            purposeDescription: 'Academic',
            activityType: 'Defense',
            supportingDocuments: null
        ));
    }

    public function testCreateReservationRejectsParticipantCountAboveSelectedVenueCapacity(): void
    {
        $this->venueCapacityLimit = 30;

        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('Participant count exceeds the selected room capacity of 30.');

        $this->service->createReservation(10, new ReservationCreateRequestDTO(
            organizationName: 'Capstone Defense',
            venueIdentifier: 1,
            requestedEquipmentList: [],
            requestedQuantity: 31,
            eventDateTime: $this->buildIsoDateTime('+1 day 09:00'),
            endDateTime: $this->buildIsoDateTime('+1 day 10:00'),
            purposeDescription: 'Academic',
            activityType: 'Defense',
            supportingDocuments: null
        ));
    }

    public function testCreateReservationRejectsSelectedVenueWithOverlappingReservation(): void
    {
        $this->overlappingVenueReservations = [$this->createMock(ReservationEntity::class)];

        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('Selected room is no longer available for the requested time.');

        $this->service->createReservation(10, new ReservationCreateRequestDTO(
            organizationName: 'Capstone Defense',
            venueIdentifier: 1,
            requestedEquipmentList: [],
            requestedQuantity: 30,
            eventDateTime: $this->buildIsoDateTime('+1 day 09:00'),
            endDateTime: $this->buildIsoDateTime('+1 day 10:00'),
            purposeDescription: 'Academic',
            activityType: 'Defense',
            supportingDocuments: null
        ));
    }

    public function testCreateReservationRejectsUnavailableSelectedVenue(): void
    {
        $this->venueAvailabilityStatus = 'Unavailable';

        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('Selected room is no longer available.');

        $this->service->createReservation(10, new ReservationCreateRequestDTO(
            organizationName: 'Capstone Defense',
            venueIdentifier: 1,
            requestedEquipmentList: [],
            requestedQuantity: 30,
            eventDateTime: $this->buildIsoDateTime('+1 day 09:00'),
            endDateTime: $this->buildIsoDateTime('+1 day 10:00'),
            purposeDescription: 'Academic',
            activityType: 'Defense',
            supportingDocuments: null
        ));
    }

    public function testCreateReservationDoesNotRunRiskySchemaConversionsDuringSubmission(): void
    {
        $schemaReadyProperty = new \ReflectionProperty($this->service, 'reservationSchemaEnsured');
        $schemaReadyProperty->setAccessible(true);
        $schemaReadyProperty->setValue($this->service, false);

        $this->connection
            ->expects($this->exactly(2))
            ->method('fetchAllAssociative')
            ->willReturn($this->buildReservationColumnRows());

        $this->connection
            ->expects($this->never())
            ->method('executeStatement');

        $this->reservationRepository
            ->expects($this->once())
            ->method('generateReservationCode')
            ->willReturn('TR-2026-001');

        $this->reservationRepository
            ->expects($this->never())
            ->method('persistReservation');

        $this->connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'reservation_identifier' => 1,
                'submission_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);

        $this->accountRepository
            ->expects($this->once())
            ->method('findActiveApprovedAccountsByRoles')
            ->willReturn([]);

        $response = $this->service->createReservation(10, new ReservationCreateRequestDTO(
            organizationName: 'Capstone Defense',
            venueIdentifier: null,
            requestedEquipmentList: [
                ['equipmentIdentifier' => 9, 'name' => 'Projector', 'quantity' => 1],
            ],
            requestedQuantity: 100,
            eventDateTime: $this->buildIsoDateTime('+1 day 09:00'),
            endDateTime: $this->buildIsoDateTime('+1 day 10:00'),
            purposeDescription: 'Academic',
            activityType: 'Defense',
            supportingDocuments: ['endorsement.pdf']
        ));

        $this->assertSame('TR-2026-001', $response->reservationCode);
    }

    public function testCreateReservationStillSucceedsWhenAdminNotificationFails(): void
    {
        $this->reservationRepository
            ->expects($this->once())
            ->method('generateReservationCode')
            ->willReturn('TR-2026-001');

        $this->reservationRepository
            ->expects($this->never())
            ->method('persistReservation');

        $this->connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn($this->buildReservationColumnRows());

        $this->connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'reservation_identifier' => 1,
                'submission_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);

        $this->accountRepository
            ->expects($this->once())
            ->method('findActiveApprovedAccountsByRoles')
            ->willThrowException(new \RuntimeException('accounts.is_approved column does not exist'));

        $this->notificationDispatchService
            ->expects($this->never())
            ->method('sendNotification');

        $response = $this->service->createReservation(10, new ReservationCreateRequestDTO(
            organizationName: 'Capstone Defense',
            venueIdentifier: 1,
            requestedEquipmentList: [],
            requestedQuantity: 100,
            eventDateTime: $this->buildIsoDateTime('+1 day 09:00'),
            endDateTime: $this->buildIsoDateTime('+1 day 10:00'),
            purposeDescription: 'Academic',
            activityType: 'Defense',
            supportingDocuments: null
        ));

        $this->assertSame('TR-2026-001', $response->reservationCode);
    }

    public function testCreateReservationFallsBackToLegacyInsertWhenModernPersistenceFails(): void
    {
        $this->reservationRepository
            ->expects($this->once())
            ->method('generateReservationCode')
            ->willReturn('TR-2026-001');

        $this->connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([]);

        $this->reservationRepository
            ->expects($this->once())
            ->method('persistReservation')
            ->willThrowException(new \RuntimeException('Unknown column end_date_time'));

        $this->connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->with(
                $this->stringContains('INSERT INTO reservations'),
                $this->arrayHasKey('reservation_code'),
                $this->arrayHasKey('reservation_code')
            )
            ->willReturn([
                'reservation_identifier' => 77,
                'submission_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);

        $this->accountRepository
            ->expects($this->once())
            ->method('findActiveApprovedAccountsByRoles')
            ->willReturn([]);

        $response = $this->service->createReservation(10, new ReservationCreateRequestDTO(
            organizationName: 'Capstone Defense',
            venueIdentifier: 1,
            requestedEquipmentList: [],
            requestedQuantity: 100,
            eventDateTime: $this->buildIsoDateTime('+1 day 09:00'),
            endDateTime: $this->buildIsoDateTime('+1 day 10:00'),
            purposeDescription: 'Academic',
            activityType: 'Defense',
            supportingDocuments: ['endorsement.pdf']
        ));

        $this->assertSame(77, $response->reservationIdentifier);
        $this->assertSame('TR-2026-001', $response->reservationCode);
    }

    private function buildIsoDateTime(string $modifier): string
    {
        return (new \DateTimeImmutable($modifier))->format(\DateTimeInterface::ATOM);
    }

    private function buildReservationColumnRows(): array
    {
        return [
            ['column_name' => 'reservation_identifier', 'data_type' => 'integer', 'udt_name' => 'int4'],
            ['column_name' => 'reservation_code', 'data_type' => 'character varying', 'udt_name' => 'varchar'],
            ['column_name' => 'borrower_account_id', 'data_type' => 'integer', 'udt_name' => 'int4'],
            ['column_name' => 'organization_name', 'data_type' => 'character varying', 'udt_name' => 'varchar'],
            ['column_name' => 'venue_identifier', 'data_type' => 'integer', 'udt_name' => 'int4'],
            ['column_name' => 'requested_equipment_list', 'data_type' => 'json', 'udt_name' => 'json'],
            ['column_name' => 'requested_quantity', 'data_type' => 'integer', 'udt_name' => 'int4'],
            ['column_name' => 'event_date_time', 'data_type' => 'timestamp without time zone', 'udt_name' => 'timestamp'],
            ['column_name' => 'end_date_time', 'data_type' => 'timestamp without time zone', 'udt_name' => 'timestamp'],
            ['column_name' => 'purpose_description', 'data_type' => 'character varying', 'udt_name' => 'varchar'],
            ['column_name' => 'activity_type', 'data_type' => 'character varying', 'udt_name' => 'varchar'],
            ['column_name' => 'current_status', 'data_type' => 'character varying', 'udt_name' => 'varchar'],
            ['column_name' => 'priority_level', 'data_type' => 'character varying', 'udt_name' => 'varchar'],
            ['column_name' => 'rejection_reason', 'data_type' => 'text', 'udt_name' => 'text'],
            ['column_name' => 'supporting_documents', 'data_type' => 'json', 'udt_name' => 'json'],
            ['column_name' => 'submission_timestamp', 'data_type' => 'timestamp without time zone', 'udt_name' => 'timestamp'],
            ['column_name' => 'updated_timestamp', 'data_type' => 'timestamp without time zone', 'udt_name' => 'timestamp'],
        ];
    }

    private function createVenueEntity(): VenueEntity|MockObject
    {
        $venue = $this->createMock(VenueEntity::class);
        $venue->method('getCapacityLimit')->willReturnCallback(fn (): int => $this->venueCapacityLimit);
        $venue->method('getAvailabilityStatus')->willReturnCallback(fn (): string => $this->venueAvailabilityStatus);
        $venue->method('getOperationalStatus')->willReturnCallback(fn (): string => $this->venueOperationalStatus);
        $venue->method('getAvailabilityDate')->willReturnCallback(fn (): ?\DateTimeInterface => $this->venueAvailabilityDate);

        return $venue;
    }

    private function createEquipmentEntity(): EquipmentEntity|MockObject
    {
        $equipment = $this->createMock(EquipmentEntity::class);
        $equipment->method('getEquipmentName')->willReturn('Projector');
        $equipment->method('getAvailableQuantity')->willReturnCallback(fn (): int => $this->equipmentAvailableQuantity);
        $equipment->method('getEquipmentState')->willReturnCallback(fn (): string => $this->equipmentState);
        $equipment->method('getOperationalStatus')->willReturnCallback(fn (): string => $this->equipmentOperationalStatus);

        return $equipment;
    }
}
