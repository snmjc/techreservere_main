<?php

namespace App\Domain\Account\Service;

use App\Shared\Utils\AccountUsername;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AccountClerkProvisioningService
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function sendInvitation(array $account, string $redirectUrl, bool $notify): array
    {
        $emailAddress = (string)($account['email_address'] ?? '');
        $accountIdentifier = (int)($account['account_identifier'] ?? 0);
        $roleDesignation = (string)($account['role_designation'] ?? '');
        $firstName = (string)($account['first_name'] ?? '');
        $lastName = (string)($account['last_name'] ?? '');

        $response = $this->httpClient->request('POST', $this->clerkApiBaseUrl() . '/v1/invitations', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->clerkSecretKey(),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'email_address' => $emailAddress,
                'redirect_url' => $redirectUrl,
                'notify' => $notify,
                'expires_in_days' => 7,
                'public_metadata' => [
                    'account_id' => $accountIdentifier,
                    'email_address' => $emailAddress,
                    'role_designation' => $roleDesignation,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'techreserve_account_identifier' => $accountIdentifier,
                    'techreserve_username' => AccountUsername::fromEmail($emailAddress),
                    'techreserve_role_designation' => $roleDesignation,
                    'techreserve_first_name' => $firstName,
                    'techreserve_last_name' => $lastName,
                    'techreserve_email_address' => $emailAddress,
                    'techreserve_id_number' => (string)($account['id_number'] ?? ''),
                    'techreserve_department' => (string)($account['department'] ?? ''),
                ],
            ],
        ]);

        $payload = $response->toArray(false);
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new \RuntimeException($this->resolveClerkErrorMessage($payload, 'Clerk invitation request failed.'));
        }

        return $payload;
    }

    public function ensureSignupUser(
        string $emailAddress,
        string $firstName,
        string $lastName,
        string $password,
        string $roleLabel,
        string $idNumber
    ): string {
        $existingClerkUser = $this->findUserByEmail($emailAddress);
        if ($existingClerkUser !== null) {
            $clerkUserId = (string)$existingClerkUser['id'];
            $this->updateSignupUser($clerkUserId, $emailAddress, $firstName, $lastName, $password, $roleLabel, $idNumber);
            return $clerkUserId;
        }

        return $this->createSignupUser($emailAddress, $firstName, $lastName, $password, $roleLabel, $idNumber);
    }

    public function findUserIdByEmail(string $emailAddress): ?string
    {
        $existingClerkUser = $this->findUserByEmail($emailAddress);
        if ($existingClerkUser === null) {
            return null;
        }

        $clerkUserId = trim((string)($existingClerkUser['id'] ?? ''));
        return $clerkUserId !== '' ? $clerkUserId : null;
    }

    private function findUserByEmail(string $emailAddress): ?array
    {
        $response = $this->httpClient->request('GET', $this->clerkApiBaseUrl() . '/v1/users', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->clerkSecretKey(),
                'Accept' => 'application/json',
            ],
            'query' => [
                'email_address' => $emailAddress,
            ],
        ]);

        $payload = $response->toArray(false);
        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException($this->resolveClerkErrorMessage($payload, 'Clerk user lookup failed.'));
        }

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

    private function createSignupUser(
        string $emailAddress,
        string $firstName,
        string $lastName,
        string $password,
        string $roleLabel,
        string $idNumber
    ): string {
        $response = $this->httpClient->request('POST', $this->clerkApiBaseUrl() . '/v1/users', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->clerkSecretKey(),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'email_address' => [$emailAddress],
                'username' => AccountUsername::fromEmail($emailAddress),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'password' => $password,
                'public_metadata' => $this->buildPendingUserMetadata($emailAddress, $roleLabel, $idNumber),
            ],
        ]);

        $payload = $response->toArray(false);
        if ($response->getStatusCode() >= 400) {
            $existingClerkUser = $this->findUserByEmail($emailAddress);
            if ($existingClerkUser !== null) {
                return (string)$existingClerkUser['id'];
            }

            throw new \RuntimeException($this->resolveClerkErrorMessage($payload, 'Clerk user creation failed.'));
        }

        $clerkUserId = (string)($payload['id'] ?? '');
        if ($clerkUserId === '') {
            throw new \RuntimeException('Clerk created the user but did not return a user ID.');
        }

        return $clerkUserId;
    }

    private function updateSignupUser(
        string $clerkUserId,
        string $emailAddress,
        string $firstName,
        string $lastName,
        string $password,
        string $roleLabel,
        string $idNumber
    ): void {
        if ($clerkUserId === '') {
            return;
        }

        $response = $this->httpClient->request('PATCH', $this->clerkApiBaseUrl() . '/v1/users/' . rawurlencode($clerkUserId), [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->clerkSecretKey(),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'password' => $password,
                'username' => AccountUsername::fromEmail($emailAddress),
                'public_metadata' => $this->buildPendingUserMetadata($emailAddress, $roleLabel, $idNumber),
            ],
        ]);

        if ($response->getStatusCode() >= 400) {
            $payload = $response->toArray(false);
            throw new \RuntimeException($this->resolveClerkErrorMessage($payload, 'Clerk user update failed.'));
        }
    }

    private function clerkSecretKey(): string
    {
        $clerkSecretKey = trim((string)($_ENV['CLERK_SECRET_KEY'] ?? ''));
        if ($clerkSecretKey === '') {
            throw new \RuntimeException('CLERK_SECRET_KEY is not configured.');
        }

        return $clerkSecretKey;
    }

    private function clerkApiBaseUrl(): string
    {
        return rtrim((string)($_ENV['CLERK_API_BASE_URL'] ?? 'https://api.clerk.com'), '/');
    }

    private function buildPendingUserMetadata(string $emailAddress, string $roleLabel, string $idNumber): array
    {
        return [
            'techreserve_account_type' => 'User',
            'techreserve_role_designation' => 'ROLE_BORROWER',
            'techreserve_role_label' => $roleLabel,
            'techreserve_username' => AccountUsername::fromEmail($emailAddress),
            'techreserve_id_number' => $idNumber,
            'techreserve_approval_status' => 'pending',
        ];
    }

    private function resolveClerkErrorMessage(array $payload, string $fallback): string
    {
        return $payload['errors'][0]['long_message']
            ?? $payload['errors'][0]['message']
            ?? $payload['message']
            ?? $fallback;
    }
}
