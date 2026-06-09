<?php

namespace App\Domain\Account\Service;

use App\Shared\Utils\AccountUsername;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AccountClerkProvisioningService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly InvitationExpiryPolicyService $invitationExpiryPolicyService
    )
    {
    }

    public function sendInvitation(array $account, string $redirectUrl, bool $notify): array
    {
        $response = $this->httpClient->request('POST', $this->clerkApiBaseUrl() . '/v1/invitations', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->clerkSecretKey(),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'email_address' => (string)($account['email_address'] ?? ''),
                'redirect_url' => $redirectUrl,
                'notify' => $notify,
                'ignore_existing' => true,
                'expires_in_days' => $this->invitationExpiryPolicyService->clerkExpiresInDays(),
                'public_metadata' => [
                    'techreserve_account_identifier' => (int)($account['account_identifier'] ?? 0),
                    'techreserve_username' => AccountUsername::fromEmail((string)($account['email_address'] ?? '')),
                    'techreserve_role_designation' => (string)($account['role_designation'] ?? ''),
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

    public function revokeInvitation(string $invitationId): void
    {
        if (trim($invitationId) === '') {
            return;
        }

        $response = $this->httpClient->request('POST', $this->clerkApiBaseUrl() . '/v1/invitations/' . rawurlencode($invitationId) . '/revoke', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->clerkSecretKey(),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        if ($response->getStatusCode() >= 400) {
            $payload = $response->toArray(false);
            $errorMessage = $this->resolveClerkErrorMessage($payload, 'Clerk invitation revoke failed.');

            if ($this->isAlreadyRevokedInvitationError($errorMessage)) {
                return;
            }

            throw new \RuntimeException($errorMessage);
        }
    }

    public function findLatestInvitationByEmail(string $emailAddress): ?array
    {
        $response = $this->httpClient->request('GET', $this->clerkApiBaseUrl() . '/v1/invitations', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->clerkSecretKey(),
                'Accept' => 'application/json',
            ],
            'query' => [
                'query' => $emailAddress,
                'limit' => 1,
                'order_by' => '-created_at',
            ],
        ]);

        $payload = $response->toArray(false);
        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException($this->resolveClerkErrorMessage($payload, 'Clerk invitation lookup failed.'));
        }

        if (isset($payload['data'][0]) && is_array($payload['data'][0])) {
            return $payload['data'][0];
        }

        if (isset($payload[0]) && is_array($payload[0])) {
            return $payload[0];
        }

        if (isset($payload['id'])) {
            return $payload;
        }

        return null;
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

    public function ensureMigratedUser(array $account): array
    {
        $emailAddress = strtolower(trim((string)($account['emailAddress'] ?? '')));
        if ($emailAddress === '') {
            throw new \InvalidArgumentException('Cannot migrate a PostgreSQL account without an email address.');
        }

        $existingClerkUser = $this->findUserByEmail($emailAddress);
        if ($existingClerkUser !== null) {
            return [
                'clerkUserId' => (string)$existingClerkUser['id'],
                'created' => false,
            ];
        }

        $response = $this->httpClient->request('POST', $this->clerkApiBaseUrl() . '/v1/users', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->clerkSecretKey(),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => $this->buildMigrationUserPayload($account, $emailAddress),
        ]);

        $payload = $response->toArray(false);
        if ($response->getStatusCode() >= 400) {
            $existingAfterConflict = $this->findUserByEmail($emailAddress);
            if ($existingAfterConflict !== null) {
                return [
                    'clerkUserId' => (string)$existingAfterConflict['id'],
                    'created' => false,
                ];
            }

            throw new \RuntimeException($this->resolveClerkErrorMessage($payload, 'Clerk user migration failed.'));
        }

        $clerkUserId = trim((string)($payload['id'] ?? ''));
        if ($clerkUserId === '') {
            throw new \RuntimeException('Clerk created the migrated user but did not return a user ID.');
        }

        return [
            'clerkUserId' => $clerkUserId,
            'created' => true,
        ];
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

    private function buildMigrationUserPayload(array $account, string $emailAddress): array
    {
        $username = trim((string)($account['username'] ?? ''));
        $roleDesignation = trim((string)($account['roleDesignation'] ?? 'ROLE_BORROWER'));
        $status = trim((string)($account['status'] ?? 'pending'));
        $passwordHash = trim((string)($account['passwordHash'] ?? ''));
        $payload = [
            'email_address' => [$emailAddress],
            'username' => $username !== '' ? $username : AccountUsername::fromEmail($emailAddress),
            'first_name' => trim((string)($account['firstName'] ?? '')),
            'last_name' => trim((string)($account['lastName'] ?? '')),
            'external_id' => (string)($account['accountIdentifier'] ?? ''),
            'created_at' => $this->normalizeClerkCreatedAt($account['createdTimestamp'] ?? null),
            'skip_legal_checks' => true,
            'public_metadata' => [
                'techreserve_account_identifier' => (int)($account['accountIdentifier'] ?? 0),
                'techreserve_role_designation' => $roleDesignation,
                'techreserve_department' => (string)($account['department'] ?? ''),
                'techreserve_id_number' => (string)($account['idNumber'] ?? ''),
                'techreserve_status' => $status,
            ],
        ];

        if ($passwordHash !== '') {
            $payload['password_digest'] = $passwordHash;
            $payload['password_hasher'] = 'bcrypt';
        } else {
            $payload['skip_password_requirement'] = true;
        }

        return $payload;
    }

    private function normalizeClerkCreatedAt(mixed $value): string
    {
        try {
            if ($value instanceof \DateTimeInterface) {
                return $value->format(\DateTimeInterface::ATOM);
            }

            if (is_string($value) && trim($value) !== '') {
                return (new \DateTimeImmutable($value))->format(\DateTimeInterface::ATOM);
            }
        } catch (\Throwable) {
        }

        return (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
    }

    private function resolveClerkErrorMessage(array $payload, string $fallback): string
    {
        return $payload['errors'][0]['long_message']
            ?? $payload['errors'][0]['message']
            ?? $payload['message']
            ?? $fallback;
    }

    private function isAlreadyRevokedInvitationError(string $errorMessage): bool
    {
        $normalizedMessage = strtolower(trim($errorMessage));

        return str_contains($normalizedMessage, 'already revoked')
            || str_contains($normalizedMessage, 'has already been revoked')
            || str_contains($normalizedMessage, 'invitation is already revoked');
    }
}
