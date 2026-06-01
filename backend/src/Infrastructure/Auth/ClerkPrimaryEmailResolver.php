<?php

namespace App\Infrastructure\Auth;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ClerkPrimaryEmailResolver
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function resolve(string $clerkUserId): string
    {
        $clerkSecretKey = $_ENV['CLERK_SECRET_KEY'] ?? '';
        if ($clerkSecretKey === '') {
            return '';
        }

        try {
            $response = $this->httpClient->request('GET', $this->clerkApiBaseUrl() . '/v1/users/' . rawurlencode($clerkUserId), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $clerkSecretKey,
                    'Accept' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() >= 400) {
                return '';
            }

            return $this->extractPrimaryEmailAddress($response->toArray(false));
        } catch (\Throwable) {
            return '';
        }
    }

    private function clerkApiBaseUrl(): string
    {
        return $_ENV['CLERK_API_BASE_URL'] ?? 'https://api.clerk.com';
    }

    private function extractPrimaryEmailAddress(array $userData): string
    {
        $primaryEmailAddressId = (string)($userData['primary_email_address_id'] ?? '');

        foreach (($userData['email_addresses'] ?? []) as $emailAddress) {
            if ((string)($emailAddress['id'] ?? '') === $primaryEmailAddressId) {
                return trim((string)($emailAddress['email_address'] ?? ''));
            }
        }

        return trim((string)($userData['email_addresses'][0]['email_address'] ?? ''));
    }
}
