<?php

namespace App\Domain\ReleaseReturn\Contract;

class ReleaseReturnResponseContract
{
    // ===== AI GENERATED: ReleaseReturnResponseContract =====
    // Purpose: Inter-domain communication shape for release/return data

    public int $releaseReturnIdentifier;
    public int $reservationIdentifier;
    public string $transactionType;
    public string $conditionStatus;

    public function __construct(int $releaseReturnIdentifier, int $reservationIdentifier, string $transactionType, string $conditionStatus)
    {
        $this->releaseReturnIdentifier = $releaseReturnIdentifier;
        $this->reservationIdentifier = $reservationIdentifier;
        $this->transactionType = $transactionType;
        $this->conditionStatus = $conditionStatus;
    }
}
