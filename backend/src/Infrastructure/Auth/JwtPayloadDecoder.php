<?php

namespace App\Infrastructure\Auth;

class JwtPayloadDecoder
{
    public function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new ClerkVerificationFailedException('Invalid JWT format.');
        }

        $padded = strtr($parts[1], '-_', '+/') . str_repeat('=', (4 - strlen($parts[1]) % 4) % 4);
        $payload = json_decode(base64_decode($padded), true);
        if (!is_array($payload)) {
            throw new ClerkVerificationFailedException('Failed to decode JWT payload.');
        }

        return $payload;
    }
}
