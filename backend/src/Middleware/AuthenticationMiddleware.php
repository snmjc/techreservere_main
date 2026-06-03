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
        $httpMethod = $request->getMethod();

        // Always allow CORS preflight requests through (handled by CorsMiddleware).
        if ($httpMethod === 'OPTIONS') {
            return;
        }

        $normalizedPath = $this->normalizeRequestPath($request->getPathInfo());

        if ($this->isPublicRoute($normalizedPath, $httpMethod)) {
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

    private function normalizeRequestPath(string $path): string
    {
        if (str_starts_with($path, '/index.php')) {
            $path = substr($path, strlen('/index.php')) ?: '/';
        }

        return $path === '/' ? $path : rtrim($path, '/');
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

    private function isPublicRoute(string $currentPath, string $httpMethod): bool
    {
        $currentPath = $this->normalizeRequestPath($currentPath);

        return $this->matchesPublicExactRoute($currentPath)
            || $this->matchesPublicMethodRoute($currentPath, $httpMethod)
            || $this->matchesPublicPrefixRoute($currentPath)
            || $this->matchesDevPublicRoute($currentPath)
            || $this->matchesSymfonyDebugRoute($currentPath);
    }

    private function matchesPublicExactRoute(string $path): bool
    {
        return in_array($path, self::PUBLIC_ROUTES, true);
    }

    private function matchesPublicMethodRoute(string $path, string $httpMethod): bool
    {
        foreach (self::PUBLIC_ROUTE_METHODS as $publicRoute => $allowedMethods) {
            if ($path === $this->normalizeRequestPath($publicRoute) && in_array($httpMethod, $allowedMethods, true)) {
                return true;
            }
        }

        return false;
    }

    private function matchesPublicPrefixRoute(string $path): bool
    {
        foreach (self::PUBLIC_ROUTE_PREFIXES as $publicPrefix) {
            if (str_starts_with($path, $publicPrefix)) {
                return true;
            }
        }

        return false;
    }

    private function matchesDevPublicRoute(string $path): bool
    {
        if (($_ENV['APP_ENV'] ?? 'prod') !== 'dev') {
            return false;
        }

        return str_starts_with($path, '/api/v1/users/wishlist')
            || preg_match('#^/api/v1/users/[^/]+/(approve|reject)$#', $path) === 1
            || str_starts_with($path, '/api/v1/accounts');
    }

    private function matchesSymfonyDebugRoute(string $path): bool
    {
        return str_starts_with($path, '/_profiler') || str_starts_with($path, '/_wdt');
    }
}
