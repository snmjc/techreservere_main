<?php

namespace App\Domain\Venue\Contract;

class VenueResponseContract
{
    // ===== AI GENERATED: VenueResponseContract =====
    // Purpose: Inter-domain communication shape for venue data

    public int $venueIdentifier;
    public string $venueName;
    public string $availabilityStatus;

    public function __construct(int $venueIdentifier, string $venueName, string $availabilityStatus)
    {
        $this->venueIdentifier = $venueIdentifier;
        $this->venueName = $venueName;
        $this->availabilityStatus = $availabilityStatus;
    }
}
