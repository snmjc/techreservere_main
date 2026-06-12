<?php

namespace App\Tests\Unit\Domain\Dashboard\Service;

use App\Domain\Account\Service\AccountReadService;
use App\Domain\Dashboard\Service\DashboardAggregationService;
use App\Domain\Equipment\Entity\EquipmentEntity;
use App\Domain\Equipment\Repository\EquipmentRepository;
use App\Domain\Reservation\Entity\ReservationEntity;
use App\Domain\Reservation\Repository\ReservationRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DashboardAggregationServiceTest extends TestCase
{
    private AccountReadService|MockObject $accountReadService;
    private EquipmentRepository|MockObject $equipmentRepository;
    private ReservationRepository|MockObject $reservationRepository;
    private DashboardAggregationService $service;

    protected function setUp(): void
    {
        $this->accountReadService = $this->createMock(AccountReadService::class);
        $this->equipmentRepository = $this->createMock(EquipmentRepository::class);
        $this->reservationRepository = $this->createMock(ReservationRepository::class);

        $this->service = new DashboardAggregationService(
            $this->accountReadService,
            $this->equipmentRepository,
            $this->reservationRepository
        );
    }

    public function testAdminDashboardSummaryCountsOnlyAcceptedManageAccountsUsers(): void
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
                $this->createEquipmentEntity(10, 4),
                $this->createEquipmentEntity(5, 2),
            ]);

        $this->reservationRepository
            ->expects($this->once())
            ->method('findAllReservations')
            ->willReturn([
                $this->createReservationEntity('Pending Review', 10),
                $this->createReservationEntity('Approved', 11),
                $this->createReservationEntity('Prepared', 12),
                $this->createReservationEntity('Returned', null),
            ]);

        $summary = $this->service->getAdminDashboardSummary();

        $this->assertSame(4, $summary['totalAccounts']);
        $this->assertSame(2, $summary['totalEquipment']);
        $this->assertSame(4, $summary['totalReservations']);
        $this->assertSame(1, $summary['pendingReservations']);
        $this->assertSame(2, $summary['approvedReservations']);
        $this->assertSame(1, $summary['activeReservations']);
        $this->assertSame(1, $summary['completedReservations']);
        $this->assertSame(9, $summary['activeEquipmentCount']);
        $this->assertSame(2, $summary['activeFacilityUsageCount']);
        $this->assertSame(60.0, $summary['equipmentUtilizationRate']);
    }

    private function createEquipmentEntity(int $totalQuantity, int $availableQuantity): EquipmentEntity|MockObject
    {
        $equipment = $this->createMock(EquipmentEntity::class);
        $equipment->method('getTotalQuantity')->willReturn($totalQuantity);
        $equipment->method('getAvailableQuantity')->willReturn($availableQuantity);

        return $equipment;
    }

    private function createReservationEntity(string $status, ?int $venueIdentifier): ReservationEntity|MockObject
    {
        $reservation = $this->createMock(ReservationEntity::class);
        $reservation->method('getCurrentStatus')->willReturn($status);
        $reservation->method('getVenueIdentifier')->willReturn($venueIdentifier);

        return $reservation;
    }
}
