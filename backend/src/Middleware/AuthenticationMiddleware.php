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

    private const PUBLIC_ROUTES = [
        '/',
        '/favicon.ico',
        '/health',
        '/health/db',
        '/api/v1/auth/login',
        '/api/v1/auth/register',
        '/api/v1/auth/clerk-login-preflight',
        '/api/v1/auth/password-reset/request',
        '/api/v1/auth/password-reset/confirm',
        '/api/v1/clerk/webhook',
        '/api/clerk/webhook',
        '/api/v1/users/register',
        '/api/v1/users/me',
        '/api/v1/venues',
        '/api/v1/equipment',
    ];

    /**
     * Public routes limited to specific HTTP methods.
     * Keep this small to avoid accidentally exposing protected reads/actions.
     */
    private const PUBLIC_ROUTE_METHODS = [
        '/api/v1/pending-users' => ['POST'],
        '/api/v1/users/register' => ['POST'],
        '/api/v1/users/signup-requests' => ['POST'],
    ];

    private const PUBLIC_ROUTE_PREFIXES = [
        '/api/v1/users/register',
    ];

    private ClerkTokenVerifier $clerkTokenVerifier;

    public function __construct(ClerkTokenVerifier $clerkTokenVerifier)
    {
        $this->clerkTokenVerifier = $clerkTokenVerifier;
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
        $currentPath = $request->getPathInfo();
        $httpMethod = $request->getMethod();

        // Always allow CORS preflight requests through (handled by CorsMiddleware).
        if ($httpMethod === 'OPTIONS') {
            return;
        }

        // Normalize path so public route matching works across front-controller setups
        // (e.g. when requests come through "/index.php/...") and with trailing slashes.
        $normalizedPath = $currentPath;
        if (str_starts_with($normalizedPath, '/index.php')) {
            $normalizedPath = substr($normalizedPath, strlen('/index.php')) ?: '/';
        }
        if ($normalizedPath !== '/') {
            $normalizedPath = rtrim($normalizedPath, '/');
        }

        if ($this->isPublicRoute($normalizedPath, $httpMethod)) {
            return;
        }

        $authorizationHeader = $request->headers->get('Authorization', '');

        if (empty($authorizationHeader) || !str_starts_with($authorizationHeader, 'Bearer ')) {
            error_log(sprintf(
                'AuthenticationRequired: Missing/invalid Authorization header for %s %s (Origin: %s)',
                $httpMethod,
                $normalizedPath,
                $request->headers->get('Origin', 'n/a')
            ));
            $requestEvent->setResponse(new JsonResponse([
                'errorCode' => 'AuthenticationRequired',
                'errorMessage' => 'Missing or invalid Authorization header.',
                'path' => $normalizedPath,
                'method' => $httpMethod,
            ], 401, ['Access-Control-Allow-Origin' => '*']));
            return;
        }

        $bearerToken = substr($authorizationHeader, 7);

        try {
            $normalizedIdentity = $this->clerkTokenVerifier->verifyTokenAndGetIdentity($bearerToken);
            $request->attributes->set('authenticatedIdentity', $normalizedIdentity);
            error_log('Authentication successful for account: ' . ($normalizedIdentity['accountIdentifier'] ?? 'unknown'));
        } catch (ClerkVerificationFailedException $exception) {
            error_log('Token verification failed: ' . $exception->getMessage());
            $requestEvent->setResponse(new JsonResponse([
                'errorCode' => 'AuthenticationFailed',
                'errorMessage' => 'Token verification failed.',
            ], 401, ['Access-Control-Allow-Origin' => '*']));
        }
    }

    private function isPublicRoute(string $currentPath, string $httpMethod): bool
    {
        // Normalize for comparisons (defensive; caller already normalizes).
        if ($currentPath !== '/') {
            $currentPath = rtrim($currentPath, '/');
        }

        foreach (self::PUBLIC_ROUTES as $publicRoute) {
            if ($currentPath === $publicRoute) {
                return true;
            }
        }

        foreach (self::PUBLIC_ROUTE_METHODS as $publicRoute => $allowedMethods) {
            // Handle common variations like trailing slashes.
            $normalizedPublicRoute = $publicRoute !== '/' ? rtrim($publicRoute, '/') : $publicRoute;
            if ($currentPath === $normalizedPublicRoute && in_array($httpMethod, $allowedMethods, true)) {
                return true;
            }
        }

        foreach (self::PUBLIC_ROUTE_PREFIXES as $publicPrefix) {
            if (str_starts_with($currentPath, $publicPrefix)) {
                return true;
            }
        }

        if (($_ENV['APP_ENV'] ?? 'prod') === 'dev') {
            if (str_starts_with($currentPath, '/api/v1/users/wishlist')) {
                return true;
            }

            if (preg_match('#^/api/v1/users/[^/]+/(approve|reject)$#', $currentPath) === 1) {
                return true;
            }

            if (str_starts_with($currentPath, '/api/v1/accounts')) {
                return true;
            }
        }

        if (str_starts_with($currentPath, '/_profiler') || str_starts_with($currentPath, '/_wdt')) {
            return true;
        }

        return false;
    }
}
