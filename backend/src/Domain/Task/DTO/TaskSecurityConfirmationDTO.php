<?php

namespace App\Domain\Task\DTO;

final readonly class TaskSecurityConfirmationDTO
{
    public function __construct(
        public int $authenticatedAdminId,
        public string $confirmedAdminEmail,
        public string $confirmedAdminPassword,
        public string $actionName
    ) {
    }
}
