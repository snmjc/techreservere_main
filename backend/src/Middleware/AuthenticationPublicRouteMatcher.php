<?php

namespace App\Middleware;

class AuthenticationPublicRouteMatcher
{
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

    private const PUBLIC_ROUTE_METHODS = [
        '/api/v1/pending-users' => ['POST'],
        '/api/v1/users/register' => ['POST'],
        '/api/v1/users/signup-requests' => ['POST'],
    ];

    private const PUBLIC_ROUTE_PREFIXES = [
        '/api/v1/users/register',
    ];

    public function isPublicRoute(string $path, string $httpMethod): bool
    {
        $path = $this->normalizePath($path);

        return in_array($path, self::PUBLIC_ROUTES, true)
            || $this->matchesMethodRoute($path, $httpMethod)
            || $this->matchesPrefixRoute($path)
            || $this->matchesDevRoute($path)
            || $this->matchesSymfonyDebugRoute($path);
    }

    public function normalizePath(string $path): string
    {
        if (str_starts_with($path, '/index.php')) {
            $path = substr($path, strlen('/index.php')) ?: '/';
        }

        return $path === '/' ? $path : rtrim($path, '/');
    }

    private function matchesMethodRoute(string $path, string $httpMethod): bool
    {
        foreach (self::PUBLIC_ROUTE_METHODS as $publicRoute => $allowedMethods) {
            if ($path === $this->normalizePath($publicRoute) && in_array($httpMethod, $allowedMethods, true)) {
                return true;
            }
        }

        return false;
    }

    private function matchesPrefixRoute(string $path): bool
    {
        foreach (self::PUBLIC_ROUTE_PREFIXES as $publicPrefix) {
            if (str_starts_with($path, $publicPrefix)) {
                return true;
            }
        }

        return false;
    }

    private function matchesDevRoute(string $path): bool
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
