<?php

namespace App\Shared\Utils;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
class RequiresRoles
{
    // ===== AI GENERATED: RequiresRoles =====
    // Purpose: PHP attribute to declare required roles on controller methods
    // Inputs: allowedRoles (array of string)
    // Returns: none
    // Flow:
    // 1. Declared on controller method via #[RequiresRoles(['ROLE_ADMIN'])]
    // 2. Read by AuthorizationMiddleware via route metadata
    // 3. Middleware enforces role check before controller executes

    /** @var string[] */
    public array $allowedRoles;

    /**
     * @param string[] $allowedRoles
     */
    public function __construct(array $allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }
}
