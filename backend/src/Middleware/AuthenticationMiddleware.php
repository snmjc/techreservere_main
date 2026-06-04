<?php

namespace App\Middleware;

use App\Infrastructure\Auth\ClerkTokenVerifier;
use App\Infrastructure\Auth\ClerkVerificationFailedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class AuthenticationMiddleware
{
    // ===== AI GENERATED: AuthenticationMiddleware =====
    // Purpose: Extract and verify Clerk bearer token from request header
    // Inputs: RequestEvent
    // Returns: void (attaches identity to request attributes or rejects)
    // Flow:
    // 1. Check if route requires authentication
    // 2. Extract Authorization header
    // 3. Verify token via Infrastructure/Auth
    // 4. Attach normalized identity to request attributes

    private ClerkTokenVerifier $clerkTokenVerifier;
    private AuthenticationPublicRouteMatcher $publicRouteMatcher;

    public function __construct(
        ClerkTokenVerifier $clerkTokenVerifier,
        ?AuthenticationPublicRouteMatcher $publicRouteMatcher = null
    )
    {
        $this->clerkTokenVerifier = $clerkTokenVerifier;
        $this->publicRouteMatcher = $publicRouteMatcher ?? new AuthenticationPublicRouteMatcher();
    }

    // ===== AI GENERATED: onKernelRequest =====
    // Purpose: Intercept incoming request and verify authentication
    // Inputs: RequestEvent
    // Returns: void
    // Flow:
    // 1. Skip public routes and profiler paths
    // 2. Extract bearer token from Authorization header
    // 3. Call ClerkTokenVerifier
    // 4. Store identity in request attributes

    public function onKernelRequest(RequestEvent $requestEvent): void
    {
        $request = $requestEvent->getRequest();
        $httpMethod = $request->getMethod();

        // Always allow CORS preflight requests through (handled by CorsMiddleware).
        if ($httpMethod === 'OPTIONS') {
            return;
        }

        $normalizedPath = $this->publicRouteMatcher->normalizePath($request->getPathInfo());

        if ($this->publicRouteMatcher->isPublicRoute($normalizedPath, $httpMethod)) {
            return;
        }

        $authorizationHeader = $request->headers->get('Authorization', '');
        if (empty($authorizationHeader) || !str_starts_with($authorizationHeader, 'Bearer ')) {
            $requestEvent->setResponse($this->authenticationRequiredResponse(
                $httpMethod,
                $normalizedPath,
                $request->headers->get('Origin', 'n/a')
            ));
            return;
        }

        $authenticationError = $this->authenticateBearerToken(substr($authorizationHeader, 7), $request);
        if ($authenticationError !== null) {
            $requestEvent->setResponse($authenticationError);
        }
    }

    private function authenticationRequiredResponse(string $httpMethod, string $path, string $origin): JsonResponse
    {
        error_log(sprintf(
            'AuthenticationRequired: Missing/invalid Authorization header for %s %s (Origin: %s)',
            $httpMethod,
            $path,
            $origin
        ));

        return new JsonResponse([
            'errorCode' => 'AuthenticationRequired',
            'errorMessage' => 'Missing or invalid Authorization header.',
            'path' => $path,
            'method' => $httpMethod,
        ], 401, ['Access-Control-Allow-Origin' => '*']);
    }

    private function authenticateBearerToken(string $bearerToken, \Symfony\Component\HttpFoundation\Request $request): ?JsonResponse
    {
        try {
            $normalizedIdentity = $this->clerkTokenVerifier->verifyTokenAndGetIdentity($bearerToken);
            $request->attributes->set('authenticatedIdentity', $normalizedIdentity);
            error_log('Authentication successful for account: ' . ($normalizedIdentity['accountIdentifier'] ?? 'unknown'));
            return null;
        } catch (ClerkVerificationFailedException $exception) {
            error_log('Token verification failed: ' . $exception->getMessage());
            return new JsonResponse([
                'errorCode' => 'AuthenticationFailed',
                'errorMessage' => 'Token verification failed.',
            ], 401, ['Access-Control-Allow-Origin' => '*']);
        }
    }
}
