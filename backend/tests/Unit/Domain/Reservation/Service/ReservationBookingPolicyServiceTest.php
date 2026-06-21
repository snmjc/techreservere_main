<?php

namespace App\Tests\Unit\Domain\Reservation\Service;

use App\Domain\Reservation\DTO\ReservationCreateRequestDTO;
use App\Domain\Reservation\Service\ReservationBookingPolicyService;
use App\Domain\Venue\Entity\VenueEntity;
use App\Domain\Venue\Repository\VenueRepository;
use App\Shared\Exceptions\DomainValidationException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ReservationBookingPolicyServiceTest extends TestCase
{
    private VenueRepository|MockObject $venueRepository;
    private ReservationBookingPolicyService $service;

    protected function setUp(): void
    {
        $this->venueRepository = $this->createMock(VenueRepository::class);
        $this->service = new ReservationBookingPolicyService($this->venueRepository);
    }

    public function testRejectsStandardReservationBeyondCurrentBookingWindow(): void
    {
        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('Reservations are currently limited to the active booking period');

        $start = $this->currentTermEnd()->modify('+1 day')->setTime(9, 0);
        $end = $start->modify('+2 hours');

        $this->service->validateReservationWindow(
            $this->buildRequest(),
            $start,
            $end
        );
    }

    public function testAllowsExtendedWindowForInstitutionalEquipmentRequest(): void
    {
        $start = $this->currentTermEnd()->modify('+1 day')->setTime(9, 0);
        $end = $start->modify('+2 hours');

        $this->service->validateReservationWindow(
            $this->buildRequest(
                venueIdentifier: null,
                purposeDescription: 'Institutional event for commencement exercises'
            ),
            $start,
            $end
        );

        $this->addToAssertionCount(1);
    }

    public function testRejectsExtendedWindowForRestrictedVenueTypes(): void
    {
        $venue = new VenueEntity();
        $venue->setVenueName('AVR 1');

        $this->venueRepository
            ->expects($this->once())
            ->method('find')
            ->with(7)
            ->willReturn($venue);

        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('Extended-term reservations are not allowed for classrooms, AVR rooms, or case rooms.');

        $start = $this->currentTermEnd()->modify('+1 day')->setTime(9, 0);
        $end = $start->modify('+2 hours');

        $this->service->validateReservationWindow(
            $this->buildRequest(
                venueIdentifier: 7,
                purposeDescription: 'RSO general assembly for officers'
            ),
            $start,
            $end
        );
    }

    private function buildRequest(
        ?int $venueIdentifier = 1,
        string $purposeDescription = 'Academic activity'
    ): ReservationCreateRequestDTO {
        return new ReservationCreateRequestDTO(
            organizationName: 'Capstone Defense',
            venueIdentifier: $venueIdentifier,
            requestedEquipmentList: [],
            requestedQuantity: 100,
            eventDateTime: '',
            endDateTime: '',
            purposeDescription: $purposeDescription,
            activityType: 'Defense',
            supportingDocuments: null
        );
    }

    private function currentTermEnd(): \DateTimeImmutable
    {
        $today = new \DateTimeImmutable('today');
        $month = (int)$today->format('n');
        $year = (int)$today->format('Y');

        if ($month >= 1 && $month <= 5) {
            return new \DateTimeImmutable(sprintf('%d-05-31 23:59:59', $year));
        }

        if ($month >= 6 && $month <= 7) {
            return new \DateTimeImmutable(sprintf('%d-07-31 23:59:59', $year));
        }

        return new \DateTimeImmutable(sprintf('%d-12-31 23:59:59', $year));
    }
}
