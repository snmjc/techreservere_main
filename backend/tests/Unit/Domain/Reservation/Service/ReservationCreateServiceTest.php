<?php

namespace App\Tests\Unit\Domain\Reservation\Service;

use App\Domain\Reservation\DTO\ReservationCreateRequestDTO;
use App\Domain\Reservation\Repository\ReservationRepository;
use App\Domain\Reservation\Service\ReservationCreateService;
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
    private ReservationCreateService $service;

    protected function setUp(): void
    {
        $this->reservationRepository = $this->createMock(ReservationRepository::class);
        $this->connection = $this->createMock(Connection::class);
        $this->service = new ReservationCreateService($this->reservationRepository, $this->connection);

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

    public function testCreateReservationRejectsDatesBeyondCurrentYear(): void
    {
        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('Reservation dates must be within the current year.');

        $nextYear = (new \DateTimeImmutable('today'))->modify('first day of january next year');

        $this->service->createReservation(10, new ReservationCreateRequestDTO(
            organizationName: 'Capstone Defense',
            venueIdentifier: 1,
            requestedEquipmentList: [],
            requestedQuantity: 100,
            eventDateTime: $nextYear->setTime(9, 0)->format(\DateTimeInterface::ATOM),
            endDateTime: $nextYear->setTime(10, 0)->format(\DateTimeInterface::ATOM),
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

    private function buildIsoDateTime(string $modifier): string
    {
        return (new \DateTimeImmutable($modifier))->format(\DateTimeInterface::ATOM);
    }
}
