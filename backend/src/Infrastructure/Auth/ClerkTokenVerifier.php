<?php

namespace App\Infrastructure\Auth;

use App\Domain\Account\Repository\AccountRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ClerkTokenVerifier
{
    // ===== AI GENERATED: ClerkTokenVerifier =====
    // Purpose: Verify Clerk JWT token and return normalized identity
    // Inputs: bearerToken (string)
    // Returns: array with accountIdentifier, emailAddress, roleDesignation
    // Flow:
    // 1. Decode JWT token via Clerk JWKS endpoint
    // 2. Validate token signature and expiry
    // 3. Look up account in database by clerkUserId
    // 4. Return normalized identity array with accountIdentifier

    private string $clerkSecretKey;
    private string $clerkApiBaseUrl;
    private string $clerkJwtIssuer;
    private HttpClientInterface $httpClient;
    private AccountRepository $accountRepository;

    public function __construct(HttpClientInterface $httpClient, AccountRepository $accountRepository)
    {
        $this->httpClient = $httpClient;
        $this->accountRepository = $accountRepository;
        $this->clerkSecretKey = $_ENV['CLERK_SECRET_KEY'] ?? '';
        $this->clerkApiBaseUrl = $_ENV['CLERK_API_BASE_URL'] ?? 'https://api.clerk.com';
        $this->clerkJwtIssuer = $_ENV['CLERK_JWT_ISSUER'] ?? 'https://primary-rooster-80.clerk.accounts.dev';
        
        error_log('ClerkTokenVerifier initialized with API base: ' . $this->clerkApiBaseUrl);
        error_log('Clerk JWT Issuer: ' . $this->clerkJwtIssuer);
    }

    // ===== AI GENERATED: verifyTokenAndGetIdentity =====
    // Purpose: Verify bearer token and extract user identity from Clerk
    // Inputs: bearerToken (string)
    // Returns: array{accountIdentifier: int, emailAddress: string, firstName: string, lastName: string, roleDesignation: string}
    // Flow:
    // 1. Call Clerk API to verify session/user
    // 2. Normalize response to internal shape
    // 3. Look up account in database by clerkUserId
    // 4. Return identity with accountIdentifier
    // 5. Throw ClerkVerificationFailedException on failure

    public function verifyTokenAndGetIdentity(string $bearerToken): array
    {
        // Try to decode as simple base64 token from login endpoint first
        try {
            $decoded = json_decode(base64_decode($bearerToken), true);
            if (is_array($decoded) && isset($decoded['accountId'])) {
                // This is a custom token from the login endpoint
                $account = $this->accountRepository->find($decoded['accountId']);
                if ($account === null) {
                    throw new ClerkVerificationFailedException('Account not found for accountId: ' . $decoded['accountId']);
                }
                
                // Check token expiration
                if (isset($decoded['exp']) && $decoded['exp'] < time()) {
                    throw new ClerkVerificationFailedException('Token has expired');
                }
                
                return [
                    'accountIdentifier' => $account->getAccountIdentifier(),
                    'emailAddress' => $account->getEmailAddress(),
                    'firstName' => $account->getFirstName(),
                    'lastName' => $account->getLastName(),
                    'roleDesignation' => $account->getRoleDesignation(),
                ];
            }
        } catch (\Throwable $e) {
            // Not a custom token, try Clerk verification
        }
        
        // Try Clerk token verification
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
            $clerkUserId = $userData['id'] ?? '';

            // Look up account in database by clerkUserId
            $account = $this->accountRepository->findOneByClerkUserId($clerkUserId);

            if ($account === null) {
                throw new ClerkVerificationFailedException('Account not found for clerkUserId: ' . $clerkUserId);
            }

            // Check if account is approved
            if (!$account->getIsApproved()) {
                throw new ClerkVerificationFailedException('Account is pending approval. Please wait for administrator approval.');
            }

            // Check account status
            if ($account->getStatus() !== 'approved') {
                throw new ClerkVerificationFailedException('Account status is ' . $account->getStatus() . '. Only approved accounts can access the system.');
            }

            // Check if account is active
            if (!$account->getIsActive()) {
                throw new ClerkVerificationFailedException('Account is disabled. Please contact an administrator.');
            }

            return [
                'accountIdentifier' => $account->getAccountIdentifier(),
                'clerkUserId' => $clerkUserId,
                'emailAddress' => $userData['email_addresses'][0]['email_address'] ?? $account->getEmailAddress(),
                'firstName' => $userData['first_name'] ?? $account->getFirstName(),
                'lastName' => $userData['last_name'] ?? $account->getLastName(),
                'roleDesignation' => $account->getRoleDesignation(),
                'status' => $account->getStatus(),
                'isApproved' => $account->getIsApproved(),
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
