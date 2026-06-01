<?php

namespace App\Domain\Account\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\HttpFoundation\Request;

class AuthenticatedAccountResolver
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function resolveAccountIdentifier(Request $request): int
    {
        $authenticatedIdentity = $request->attributes->get('authenticatedIdentity', []);
        $accountIdentifier = (int)($authenticatedIdentity['accountIdentifier'] ?? 0);

        if ($accountIdentifier > 0) {
            return $accountIdentifier;
        }

        $authorizationHeader = (string)$request->headers->get('Authorization', '');
        if (!str_starts_with($authorizationHeader, 'Bearer ')) {
            return 0;
        }

        $token = trim(substr($authorizationHeader, 7));
        if ($token === '') {
            return 0;
        }

        $localPayload = json_decode(base64_decode($token, true) ?: '', true);
        if (is_array($localPayload)) {
            $accountIdentifier = (int)($localPayload['accountId'] ?? $localPayload['accountIdentifier'] ?? 0);
            if ($accountIdentifier > 0) {
                return $accountIdentifier;
            }
        }

        $clerkUserId = trim((string)($this->decodeJwtPayloadWithoutVerification($token)['sub'] ?? ''));
        if ($clerkUserId === '') {
            return 0;
        }

        return (int)$this->connection->fetchOne(
            'SELECT account_identifier FROM accounts WHERE clerk_user_id = :clerkUserId LIMIT 1',
            ['clerkUserId' => $clerkUserId],
            ['clerkUserId' => ParameterType::STRING]
        );
    }

    private function decodeJwtPayloadWithoutVerification(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) < 2) {
            return [];
        }

        $payload = strtr($parts[1], '-_', '+/');
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $decodedPayload = base64_decode($payload, true);
        if ($decodedPayload === false) {
            return [];
        }

        $data = json_decode($decodedPayload, true);
        return is_array($data) ? $data : [];
    }
}
