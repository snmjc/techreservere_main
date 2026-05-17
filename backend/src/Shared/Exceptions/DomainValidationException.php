<?php

namespace App\Shared\Exceptions;

class DomainValidationException extends \Exception implements \Throwable
{
    // ===== AI GENERATED: DomainValidationException =====
    // Purpose: Generic domain-level validation exception
    // Inputs: errorMessage (string), previous (\Throwable|null)
    // Returns: void
    // Flow:
    // 1. Extends Symfony BadRequestHttpException
    // 2. Used when DTO validation fails at Service layer
    // 3. Automatically maps to 400 HTTP response

    public function __construct(string $errorMessage = 'Validation failed.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($errorMessage, $code, $previous);
    }
}
