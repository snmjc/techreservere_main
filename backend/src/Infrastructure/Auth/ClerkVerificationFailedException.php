<?php

namespace App\Infrastructure\Auth;

class ClerkVerificationFailedException extends \RuntimeException
{
    // ===== AI GENERATED: ClerkVerificationFailedException =====
    // Purpose: Exception for Clerk token verification failures
    // Inputs: errorMessage (string), previous (\Throwable|null)
    // Returns: void
    // Flow:
    // 1. Thrown when Clerk API returns error
    // 2. Caught by AuthenticationMiddleware
    // 3. Mapped to 401 response

    public function __construct(string $errorMessage = 'Clerk verification failed.', ?\Throwable $previous = null)
    {
        parent::__construct($errorMessage, 0, $previous);
    }
}
