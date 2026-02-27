<?php

namespace App\Domain\Dashboard\DTO;

class DashboardSummaryResponseDTO
{
    // ===== AI GENERATED: DashboardSummaryResponseDTO =====
    // Purpose: Response DTO for dashboard summary data

    public int $totalAccounts;
    public int $totalEquipment;
    public int $totalReservations;
    public int $pendingReservations;

    public function __construct(int $totalAccounts, int $totalEquipment, int $totalReservations, int $pendingReservations)
    {
        $this->totalAccounts = $totalAccounts;
        $this->totalEquipment = $totalEquipment;
        $this->totalReservations = $totalReservations;
        $this->pendingReservations = $pendingReservations;
    }

    public function toResponseArray(): array
    {
        return [
            'totalAccounts' => $this->totalAccounts,
            'totalEquipment' => $this->totalEquipment,
            'totalReservations' => $this->totalReservations,
            'pendingReservations' => $this->pendingReservations,
        ];
    }
}
