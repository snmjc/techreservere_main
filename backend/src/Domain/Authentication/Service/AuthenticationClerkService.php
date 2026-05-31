<?php

namespace App\Domain\Authentication\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AuthenticationClerkService
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function findUserByEmail(string $emailAddress): ?array
    {
        $secretKey = $this->clerkSecretKey();
        if ($secretKey === null) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', $this->clerkApiBaseUrl() . '/v1/users', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secretKey,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'email_address' => $emailAddress,
                ],
            ]);

            if ($response->getStatusCode() >= 400) {
                return null;
            }

            return $this->extractUserFromPayload($response->toArray(false));
        } catch (\Throwable) {
            return null;
        }
    }

    public function updatePassword(string $clerkUserId, string $newPassword): bool
    {
        $secretKey = $this->clerkSecretKey();
        if ($secretKey === null) {
            return false;
        }

        try {
            $response = $this->httpClient->request('PATCH', $this->clerkApiBaseUrl() . '/v1/users/' . rawurlencode($clerkUserId), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secretKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'password' => $newPassword,
                ],
            ]);

            return $response->getStatusCode() < 400;
        } catch (\Throwable) {
            return false;
        }
    }

    public function deleteUser(string $clerkUserId): void
    {
        $secretKey = $this->clerkSecretKey();
        if ($secretKey === null || $clerkUserId === '') {
            return;
        }

        try {
            $this->httpClient->request('DELETE', $this->clerkApiBaseUrl() . '/v1/users/' . rawurlencode($clerkUserId), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secretKey,
                    'Accept' => 'application/json',
                ],
            ]);
        } catch (\Throwable) {
        }
    }

    private function clerkSecretKey(): ?string
    {
        $secretKey = trim((string)($_ENV['CLERK_SECRET_KEY'] ?? ''));
        return $secretKey !== '' ? $secretKey : null;
    }

    private function clerkApiBaseUrl(): string
    {
        return rtrim((string)($_ENV['CLERK_API_BASE_URL'] ?? 'https://api.clerk.com'), '/');
    }

    private function extractUserFromPayload(array $payload): ?array
    {
        if (isset($payload['id'])) {
            return $payload;
        }

        if (isset($payload[0]['id'])) {
            return $payload[0];
        }

        if (isset($payload['data'][0]['id'])) {
            return $payload['data'][0];
        }

        return null;
    }
}
