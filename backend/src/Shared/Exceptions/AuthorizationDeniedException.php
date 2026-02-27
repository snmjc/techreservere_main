<?php

namespace App\Shared\Exceptions;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthorizationDeniedException extends AccessDeniedHttpException
{
    // ===== AI GENERATED: AuthorizationDeniedException =====
    // Purpose: Thrown when user role does not match required role
    // Inputs: errorMessage (string), previous (\Throwable|null)
    // Returns: void
    // Flow:
    // 1. Extends Symfony AccessDeniedHttpException
    // 2. Used by AuthorizationMiddleware
    // 3. Automatically maps to 403 HTTP response

    public function __construct(string $errorMessage = 'Access denied.', ?\Throwable $previous = null)
    {
        parent::__construct($errorMessage, $previous);
    }
}
