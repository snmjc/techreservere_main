<?php

namespace App\Domain\Dashboard\Service;

use App\Domain\Account\Repository\AccountRepository;
use App\Domain\Equipment\Repository\EquipmentRepository;
use App\Domain\Reservation\Repository\ReservationRepository;

class DashboardAggregationService
{
    private AccountRepository $accountRepository;
    private EquipmentRepository $equipmentRepository;
    private ReservationRepository $reservationRepository;

    public function __construct(
        AccountRepository $accountRepository,
        EquipmentRepository $equipmentRepository,
        ReservationRepository $reservationRepository
    ) {
        $this->accountRepository = $accountRepository;
        $this->equipmentRepository = $equipmentRepository;
        $this->reservationRepository = $reservationRepository;
    }

    // ===== AI GENERATED: getAdminDashboardSummary =====
    // Purpose: Aggregate key metrics for admin dashboard
    // Inputs: none
    // Returns: array with summary counts
    // Flow:
    // 1. Count accounts, equipment, reservations
    // 2. Count pending reservations
    // 3. Return summary array

    public function getAdminDashboardSummary(): array
    {
        $totalAccounts = count($this->accountRepository->findAllAccounts());
        $totalEquipment = count($this->equipmentRepository->findAllEquipment());
        $totalReservations = count($this->reservationRepository->findAllReservations());
        $pendingReservations = count($this->reservationRepository->findByCurrentStatus('Pending Review'));

        return [
            'totalAccounts' => $totalAccounts,
            'totalEquipment' => $totalEquipment,
            'totalReservations' => $totalReservations,
            'pendingReservations' => $pendingReservations,
        ];
    }
}
