<?php

namespace App\Shared\Exceptions;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthenticationFailedException extends UnauthorizedHttpException
{
    // ===== AI GENERATED: AuthenticationFailedException =====
    // Purpose: Thrown when Clerk token verification fails
    // Inputs: errorMessage (string), previous (\Throwable|null)
    // Returns: void
    // Flow:
    // 1. Extends Symfony UnauthorizedHttpException
    // 2. Used by AuthenticationMiddleware
    // 3. Automatically maps to 401 HTTP response

    public function __construct(string $errorMessage = 'Authentication failed.', ?\Throwable $previous = null)
    {
        parent::__construct('Bearer', $errorMessage, $previous);
    }
}
