<?php

namespace App\Infrastructure\Auth;

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

    private string $clerkApiBaseUrl;
    private string $clerkJwtIssuer;
    private LocalTokenIdentityResolver $localTokenIdentityResolver;
    private ClerkTokenIdentityResolver $clerkTokenIdentityResolver;

    public function __construct(
        LocalTokenIdentityResolver $localTokenIdentityResolver,
        ClerkTokenIdentityResolver $clerkTokenIdentityResolver
    )
    {
        $this->localTokenIdentityResolver = $localTokenIdentityResolver;
        $this->clerkTokenIdentityResolver = $clerkTokenIdentityResolver;
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
        try {
            $localIdentity = $this->localTokenIdentityResolver->resolve($bearerToken);
            if ($localIdentity !== null) {
                return $localIdentity;
            }
        } catch (\Throwable) {
        }
        
        try {
            return $this->clerkTokenIdentityResolver->resolve($bearerToken);
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
