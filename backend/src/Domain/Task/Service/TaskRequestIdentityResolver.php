<?php

namespace App\Domain\Task\Service;

use Symfony\Component\HttpFoundation\Request;

class TaskRequestIdentityResolver
{
    public function resolveAuthenticatedAccountIdentifier(Request $request): int
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

        return $this->resolveAccountIdentifierFromToken(trim(substr($authorizationHeader, 7)));
    }

    private function resolveAccountIdentifierFromToken(string $token): int
    {
        $localPayload = json_decode(base64_decode($token, true) ?: '', true);
        if (!is_array($localPayload)) {
            return 0;
        }

        return (int)($localPayload['accountId'] ?? $localPayload['accountIdentifier'] ?? 0);
    }
}
