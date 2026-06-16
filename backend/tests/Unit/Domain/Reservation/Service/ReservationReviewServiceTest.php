<?php

namespace App\Tests\Unit\Domain\Reservation\Service;

use App\Domain\Reservation\Entity\ReservationEntity;
use App\Domain\Reservation\Repository\ReservationRepository;
use App\Domain\Reservation\Service\ReservationReviewService;
use App\Shared\Exceptions\DomainValidationException;
use App\Shared\Utils\RoleConstants;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ReservationReviewServiceTest extends TestCase
{
    private ReservationRepository|MockObject $reservationRepository;
    private ReservationReviewService $service;

    protected function setUp(): void
    {
        $this->reservationRepository = $this->createMock(ReservationRepository::class);
        $this->service = new ReservationReviewService($this->reservationRepository);
    }

    public function testAdminCanReadAnyReservationDetail(): void
    {
        $this->reservationRepository
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($this->createReservationEntity(25));

        $reservation = $this->service->getReservationByIdForRole(10, RoleConstants::ROLE_ADMIN, 99);

        $this->assertSame(10, $reservation->reservationIdentifier);
        $this->assertSame(25, $reservation->borrowerAccountId);
    }

    public function testBorrowerCanReadOwnReservationDetail(): void
    {
        $this->reservationRepository
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($this->createReservationEntity(25));

        $reservation = $this->service->getReservationByIdForRole(10, RoleConstants::ROLE_BORROWER, 25);

        $this->assertSame(10, $reservation->reservationIdentifier);
        $this->assertSame(25, $reservation->borrowerAccountId);
    }

    public function testBorrowerCannotReadAnotherBorrowerReservationDetail(): void
    {
        $this->reservationRepository
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($this->createReservationEntity(25));

        $this->expectException(DomainValidationException::class);

        $this->service->getReservationByIdForRole(10, RoleConstants::ROLE_BORROWER, 41);
    }

    public function testDeveloperCannotReadAllReservationDetailsByRole(): void
    {
        $this->reservationRepository
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($this->createReservationEntity(25));

        $this->expectException(DomainValidationException::class);

        $this->service->getReservationByIdForRole(10, RoleConstants::ROLE_DEVELOPER, 25);
    }

    private function createReservationEntity(int $borrowerAccountId): ReservationEntity|MockObject
    {
        $reservation = $this->createMock(ReservationEntity::class);
        $reservation->method('getReservationIdentifier')->willReturn(10);
        $reservation->method('getReservationCode')->willReturn('TR-2026-010');
        $reservation->method('getBorrowerAccountId')->willReturn($borrowerAccountId);
        $reservation->method('getOrganizationName')->willReturn('Borrower Organization');
        $reservation->method('getVenueIdentifier')->willReturn(3);
        $reservation->method('getRequestedEquipmentList')->willReturn([]);
        $reservation->method('getRequestedQuantity')->willReturn(1);
        $reservation->method('getEventDateTime')->willReturn(new \DateTimeImmutable('2026-06-16T10:00:00+00:00'));
        $reservation->method('getEndDateTime')->willReturn(new \DateTimeImmutable('2026-06-16T11:00:00+00:00'));
        $reservation->method('getPurposeDescription')->willReturn('Class activity');
        $reservation->method('getActivityType')->willReturn('Academic');
        $reservation->method('getCurrentStatus')->willReturn('Approved');
        $reservation->method('getPriorityLevel')->willReturn('Normal');
        $reservation->method('getRejectionReason')->willReturn(null);
        $reservation->method('getSupportingDocuments')->willReturn(null);
        $reservation->method('getSubmissionTimestamp')->willReturn(new \DateTimeImmutable('2026-06-15T10:00:00+00:00'));

        return $reservation;
    }
}
