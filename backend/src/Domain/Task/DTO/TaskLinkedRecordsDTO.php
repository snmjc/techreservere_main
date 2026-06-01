<?php

namespace App\Domain\Task\DTO;

final readonly class TaskLinkedRecordsDTO
{
    public function __construct(
        public ?int $reservationIdentifier,
        public ?int $assignedToAccountId
    ) {
    }
}
