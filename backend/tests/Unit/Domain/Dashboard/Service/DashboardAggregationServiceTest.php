<?php

namespace App\Tests\Unit\Domain\Dashboard\Service;

use App\Domain\Account\Service\AccountReadService;
use App\Domain\Dashboard\Service\DashboardAggregationService;
use App\Domain\Equipment\Entity\EquipmentEntity;
use App\Domain\Equipment\Repository\EquipmentRepository;
use App\Domain\ReleaseReturn\Repository\ReleaseReturnRepository;
use App\Domain\Reservation\Entity\ReservationEntity;
use App\Domain\Reservation\Repository\ReservationRepository;
use App\Domain\Venue\Repository\VenueRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DashboardAggregationServiceTest extends TestCase
{
    private AccountReadService|MockObject $accountReadService;
    private EquipmentRepository|MockObject $equipmentRepository;
    private ReservationRepository|MockObject $reservationRepository;
    private VenueRepository|MockObject $venueRepository;
    private ReleaseReturnRepository|MockObject $releaseReturnRepository;
    private DashboardAggregationService $service;

    protected function setUp(): void
    {
        $this->accountReadService = $this->createMock(AccountReadService::class);
        $this->equipmentRepository = $this->createMock(EquipmentRepository::class);
        $this->reservationRepository = $this->createMock(ReservationRepository::class);
        $this->venueRepository = $this->createMock(VenueRepository::class);
        $this->releaseReturnRepository = $this->createMock(ReleaseReturnRepository::class);

        $this->service = new DashboardAggregationService(
            $this->accountReadService,
            $this->equipmentRepository,
            $this->reservationRepository,
            $this->venueRepository,
            $this->releaseReturnRepository
        );
    }

    public function testAdminDashboardSummaryIncludesMaintenanceEquipmentCount(): void
    {
        $manageAccountsUsers = [
            ['accountIdentifier' => 1, 'accountType' => 'Admin'],
            ['accountIdentifier' => 2, 'accountType' => 'User'],
            ['accountIdentifier' => 3, 'accountType' => 'User'],
            ['accountIdentifier' => 4, 'accountType' => 'User'],
        ];

        $this->accountReadService
            ->expects($this->once())
            ->method('getAcceptedAccounts')
            ->willReturn($manageAccountsUsers);

        $this->equipmentRepository
            ->expects($this->once())
            ->method('findAllEquipment')
            ->willReturn([
                $this->createEquipmentEntity(10, 4, 'Available', 'Available'),
                $this->createEquipmentEntity(5, 2, 'Under Maintenance', 'Under Maintenance'),
                $this->createEquipmentEntity(2, 0, 'Unavailable', 'Unavailable'),
            ]);

        $this->reservationRepository
            ->expects($this->once())
            ->method('findAllReservations')
            ->willReturn([
                $this->createReservationEntity('Pending Review', 10, '2026-06-30', '2026-06-30'),
                $this->createReservationEntity('Approved', 11, '2026-06-28', '2026-06-28'),
                $this->createReservationEntity('Prepared', 12, '2026-06-26', '2026-06-26'),
                $this->createReservationEntity('Returned', null, '2026-06-24', '2026-06-24'),
            ]);

        $this->releaseReturnRepository
            ->expects($this->once())
            ->method('findAllReleaseReturns')
            ->willReturn([]);

        $summary = $this->service->getAdminDashboardSummary();

        $this->assertSame(4, $summary['totalAccounts']);
        $this->assertSame(3, $summary['totalEquipment']);
        $this->assertSame(4, $summary['totalReservations']);
        $this->assertSame(1, $summary['pendingReservations']);
        $this->assertSame(1, $summary['maintenanceEquipmentCount']);
        $this->assertSame(13, $summary['activeEquipmentCount']);
        $this->assertSame(1, $summary['activeFacilityUsageCount']);
        $this->assertSame(76.5, $summary['equipmentUtilizationRate']);
    }

    public function testAdminDashboardOverviewExposesMaintenanceSummaryAndAlert(): void
    {
        $rangeStart = new \DateTimeImmutable('2026-06-20 00:00:00');
        $rangeEnd = new \DateTimeImmutable('2026-06-26 23:59:59');

        $this->accountReadService
            ->expects($this->once())
            ->method('getAcceptedAccounts')
            ->willReturn([]);

        $this->equipmentRepository
            ->expects($this->once())
            ->method('findAllEquipment')
            ->willReturn([
                $this->createEquipmentEntity(3, 1, 'Under Maintenance', 'Maintenance'),
                $this->createEquipmentEntity(4, 4, 'Available', 'Active'),
                $this->createEquipmentEntity(2, 0, 'Retired', 'Retired'),
            ]);

        $this->venueRepository
            ->expects($this->once())
            ->method('findAllVenues')
            ->willReturn([]);

        $this->reservationRepository
            ->method('findBySubmissionDateRange')
            ->willReturn([]);

        $this->reservationRepository
            ->method('findByEventDateRange')
            ->willReturn([]);

        $this->reservationRepository
            ->method('findAllReservations')
            ->willReturn([]);

        $this->releaseReturnRepository
            ->method('findByProcessedDateRange')
            ->willReturn([]);

        $this->releaseReturnRepository
            ->method('findAllReleaseReturns')
            ->willReturn([]);

        $overview = $this->service->getAdminDashboardOverview($rangeStart, $rangeEnd);

        $this->assertSame(1, $overview['summary']['maintenanceEquipmentCount']);
        $this->assertSame('Equipment under maintenance', $overview['readinessAlerts'][2]['title']);
        $this->assertSame(1, $overview['readinessAlerts'][2]['count']);
    }

    private function createEquipmentEntity(
        int $totalQuantity,
        int $availableQuantity,
        string $equipmentState,
        string $operationalStatus
    ): EquipmentEntity|MockObject {
        $equipment = $this->createMock(EquipmentEntity::class);
        $equipment->method('getTotalQuantity')->willReturn($totalQuantity);
        $equipment->method('getAvailableQuantity')->willReturn($availableQuantity);
        $equipment->method('getEquipmentState')->willReturn($equipmentState);
        $equipment->method('getOperationalStatus')->willReturn($operationalStatus);

        return $equipment;
    }

    private function createReservationEntity(
        string $status,
        ?int $venueIdentifier,
        string $eventDate,
        string $submissionDate
    ): ReservationEntity|MockObject {
        $reservation = $this->createMock(ReservationEntity::class);
        $reservation->method('getCurrentStatus')->willReturn($status);
        $reservation->method('getVenueIdentifier')->willReturn($venueIdentifier);
        $reservation->method('getEventDateTime')->willReturn(new \DateTimeImmutable($eventDate . ' 09:00:00', new \DateTimeZone('Asia/Manila')));
        $reservation->method('getEndDateTime')->willReturn(new \DateTimeImmutable($eventDate . ' 12:00:00', new \DateTimeZone('Asia/Manila')));
        $reservation->method('getSubmissionTimestamp')->willReturn(new \DateTimeImmutable($submissionDate . ' 08:00:00', new \DateTimeZone('Asia/Manila')));

        return $reservation;
    }
}
