<?php

namespace App\Domain\Account\Service;

class ClerkApiErrorMessageResolver
{
    public function resolve(array $payload, string $fallback): string
    {
        return $payload['errors'][0]['long_message']
            ?? $payload['errors'][0]['message']
            ?? $payload['message']
            ?? $fallback;
    }
}
