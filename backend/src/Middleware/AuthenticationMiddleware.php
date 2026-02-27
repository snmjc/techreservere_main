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
        '/health',
        '/health/db',
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

        if ($this->isPublicRoute($currentPath)) {
            return;
        }

        $authorizationHeader = $request->headers->get('Authorization', '');

        if (empty($authorizationHeader) || !str_starts_with($authorizationHeader, 'Bearer ')) {
            $requestEvent->setResponse(new JsonResponse([
                'errorCode' => 'AuthenticationRequired',
                'errorMessage' => 'Missing or invalid Authorization header.',
            ], 401, ['Access-Control-Allow-Origin' => '*']));
            return;
        }

        $bearerToken = substr($authorizationHeader, 7);

        try {
            $normalizedIdentity = $this->clerkTokenVerifier->verifyTokenAndGetIdentity($bearerToken);
            $request->attributes->set('authenticatedIdentity', $normalizedIdentity);
        } catch (ClerkVerificationFailedException $exception) {
            $requestEvent->setResponse(new JsonResponse([
                'errorCode' => 'AuthenticationFailed',
                'errorMessage' => 'Token verification failed.',
            ], 401, ['Access-Control-Allow-Origin' => '*']));
        }
    }

    private function isPublicRoute(string $currentPath): bool
    {
        foreach (self::PUBLIC_ROUTES as $publicRoute) {
            if ($currentPath === $publicRoute) {
                return true;
            }
        }

        if (str_starts_with($currentPath, '/_profiler') || str_starts_with($currentPath, '/_wdt')) {
            return true;
        }

        return false;
    }
}
