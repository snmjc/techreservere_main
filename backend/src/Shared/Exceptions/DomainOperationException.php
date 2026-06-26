<?php

namespace App\Shared\Exceptions;

class DomainOperationException extends \RuntimeException implements \Throwable
{
    public function __construct(
        string $errorMessage = 'Operation failed.',
        private readonly string $errorType = 'OperationFailed',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($errorMessage, $code, $previous);
    }

    public function getErrorType(): string
    {
        return trim($this->errorType) !== '' ? $this->errorType : 'OperationFailed';
    }
}
