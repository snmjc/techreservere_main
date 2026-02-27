<?php

namespace App\Shared\Utils;

class RoleConstants
{
    // ===== AI GENERATED: RoleConstants =====
    // Purpose: Define centralized role constants for RBAC
    // Inputs: none
    // Returns: none
    // Flow:
    // 1. Define string constants for each role
    // 2. Used by Middleware and Controllers for role comparison

    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_BORROWER = 'ROLE_BORROWER';
    public const ROLE_DEVELOPER = 'ROLE_DEVELOPER';

    public const ALL_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_BORROWER,
        self::ROLE_DEVELOPER,
    ];
}
