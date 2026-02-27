<?php

namespace App\Infrastructure\Auth;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ClerkTokenVerifier
{
    // ===== AI GENERATED: ClerkTokenVerifier =====
    // Purpose: Verify Clerk JWT token and return normalized identity
    // Inputs: bearerToken (string)
    // Returns: array with clerkUserId, emailAddress, roleDesignation
    // Flow:
    // 1. Decode JWT token via Clerk JWKS endpoint
    // 2. Validate token signature and expiry
    // 3. Return normalized identity array

    private string $clerkSecretKey;
    private string $clerkApiBaseUrl;
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->clerkSecretKey = $_ENV['CLERK_SECRET_KEY'] ?? '';
        $this->clerkApiBaseUrl = $_ENV['CLERK_API_BASE_URL'] ?? 'https://api.clerk.com';
    }

    // ===== AI GENERATED: verifyTokenAndGetIdentity =====
    // Purpose: Verify bearer token and extract user identity from Clerk
    // Inputs: bearerToken (string)
    // Returns: array{clerkUserId: string, emailAddress: string, firstName: string, lastName: string}
    // Flow:
    // 1. Call Clerk API to verify session/user
    // 2. Normalize response to internal shape
    // 3. Throw ClerkVerificationFailedException on failure

    public function verifyTokenAndGetIdentity(string $bearerToken): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->clerkApiBaseUrl . '/v1/users/me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $bearerToken,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new ClerkVerificationFailedException('Clerk token verification returned status: ' . $statusCode);
            }

            $userData = $response->toArray();

            return [
                'clerkUserId' => $userData['id'] ?? '',
                'emailAddress' => $userData['email_addresses'][0]['email_address'] ?? '',
                'firstName' => $userData['first_name'] ?? '',
                'lastName' => $userData['last_name'] ?? '',
            ];
        } catch (ClerkVerificationFailedException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new ClerkVerificationFailedException(
                'Clerk token verification failed: ' . $exception->getMessage(),
                $exception
            );
        }
    }
}
