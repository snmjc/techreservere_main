<?php

namespace App\Domain\Reservation\Contract;

class ReservationResponseContract
{
    // ===== AI GENERATED: ReservationResponseContract =====
    // Purpose: Inter-domain communication shape for reservation data
    // Inputs: none
    // Returns: none
    // Flow:
    // 1. Used by Task and ReleaseReturn domains
    // 2. Prevents entity exposure across domain boundaries

    public int $reservationIdentifier;
    public string $reservationCode;
    public string $currentStatus;
    public string $organizationName;

    public function __construct(int $reservationIdentifier, string $reservationCode, string $currentStatus, string $organizationName)
    {
        $this->reservationIdentifier = $reservationIdentifier;
        $this->reservationCode = $reservationCode;
        $this->currentStatus = $currentStatus;
        $this->organizationName = $organizationName;
    }
}
