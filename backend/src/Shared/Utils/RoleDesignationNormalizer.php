<?php

namespace App\Shared\Utils;

final class RoleDesignationNormalizer
{
    public static function normalize(?string $roleDesignation): string
    {
        $normalized = strtoupper(trim((string)$roleDesignation));

        return match ($normalized) {
            'ADMIN', RoleConstants::ROLE_ADMIN => RoleConstants::ROLE_ADMIN,
            'STAFF', 'EMPLOYEE', RoleConstants::ROLE_STAFF => RoleConstants::ROLE_STAFF,
            'BORROWER', 'USER', RoleConstants::ROLE_BORROWER => RoleConstants::ROLE_BORROWER,
            default => str_starts_with($normalized, 'ROLE_') ? $normalized : RoleConstants::ROLE_BORROWER,
        };
    }
}
