<?php

namespace App\Shared\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DomainNotFoundException extends NotFoundHttpException
{
    // ===== AI GENERATED: DomainNotFoundException =====
    // Purpose: Generic domain-level "not found" exception
    // Inputs: errorMessage (string), previous (\Throwable|null)
    // Returns: void
    // Flow:
    // 1. Extends Symfony NotFoundHttpException
    // 2. Used by Domain Services when entity lookup fails
    // 3. Automatically maps to 404 HTTP response

    public function __construct(string $errorMessage = 'Resource not found.', ?\Throwable $previous = null)
    {
        parent::__construct($errorMessage, $previous);
    }
}
