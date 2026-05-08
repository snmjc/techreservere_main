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

    // ===== AI GENERATED: getBorrowerDashboardSummary =====
    // Purpose: Aggregate dashboard metrics for authenticated borrower only
    // Inputs: $user (authenticated user entity)
    // Returns: array with borrower-specific summary counts
    // Flow:
    // 1. Get user's ID
    // 2. Filter reservations by borrower_id
    // 3. Count by status (Active, Approved, Pending, Completed)
    // 4. Return summary array

    public function getBorrowerDashboardSummary($user): array
    {
        $userId = $user->getId();
        
        // Get all reservations for this borrower
        $userReservations = $this->reservationRepository->findByBorrowerAccountId($userId);
        
        // Count by status
        $activeReservations = 0;
        $approvedRequests = 0;
        $pendingRequests = 0;
        $completedReservations = 0;
        
        foreach ($userReservations as $reservation) {
            $status = $reservation->getCurrentStatus();
            switch ($status) {
                case 'Active':
                case 'Reserved':
                    $activeReservations++;
                    break;
                case 'Approved':
                    $approvedRequests++;
                    break;
                case 'Pending':
                case 'Pending Review':
                    $pendingRequests++;
                    break;
                case 'Completed':
                case 'Returned':
                    $completedReservations++;
                    break;
            }
        }

        return [
            'activeReservations' => $activeReservations,
            'approvedRequests' => $approvedRequests,
            'pendingRequests' => $pendingRequests,
            'completedReservations' => $completedReservations,
        ];
    }
}
