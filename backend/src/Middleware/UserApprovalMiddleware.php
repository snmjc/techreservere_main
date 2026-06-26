<?php

namespace App\Middleware;

use App\Infrastructure\Auth\ClerkVerificationFailedException;
use App\Infrastructure\Auth\ClerkTokenVerifier;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserApprovalMiddleware implements EventSubscriberInterface
{
    private ClerkTokenVerifier $clerkTokenVerifier;

    public function __construct(ClerkTokenVerifier $clerkTokenVerifier)
    {
        $this->clerkTokenVerifier = $clerkTokenVerifier;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Skip middleware for public routes
        $publicRoutes = [
            '/health',
            '/health/db',
            '/api/v1/auth/login',
            '/api/v1/auth/register',
            '/api/v1/clerk/webhook',
            '/api/clerk/webhook',
            '/api/v1/equipment',
            '/api/v1/venues',
            '/api/v1/dashboard/summary',
            '/api/v1/users/register',
            '/api/v1/users/signup-requests',
        ];

        foreach ($publicRoutes as $route) {
            if (str_starts_with($request->getPathInfo(), $route)) {
                return;
            }
        }

        // Skip middleware for development mode
        if ($_ENV['APP_ENV'] === 'dev') {
            return;
        }

        // Get authorization header
        $authorizationHeader = $request->headers->get('Authorization');

        if (empty($authorizationHeader) || !str_starts_with($authorizationHeader, 'Bearer ')) {
            return; // Let AuthenticationMiddleware handle missing auth
        }

        // Extract token
        $token = substr($authorizationHeader, 7);

        try {
            // Verify Clerk token and check approval status
            $identity = $this->clerkTokenVerifier->verifyTokenAndGetIdentity($token);
            $status = strtolower(trim((string)($identity['status'] ?? 'pending')));
            $isActiveInvitationAccount = in_array($status, ['active', 'approved', 'accepted'], true);

            // Block only pending/unverified accounts. Legacy active rows may not have is_verified populated yet.
            if (isset($identity['isVerified']) && !$identity['isVerified'] && !$isActiveInvitationAccount) {
                $event->setResponse(new JsonResponse([
                    'error' => 'AccountNotApproved',
                    'message' => 'Your account is pending verification. Please wait for an administrator invitation.',
                ], 403));
                return;
            }

            // Check account status
            if (isset($identity['status']) && !$isActiveInvitationAccount) {
                $event->setResponse(new JsonResponse([
                    'error' => 'AccountNotApproved',
                    'message' => 'Your account status is ' . $identity['status'] . '. Only active invitation accounts can access the system.',
                ], 403));
                return;
            }
        } catch (ClerkVerificationFailedException $e) {
            // Let AuthenticationMiddleware handle verification errors
            return;
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 32], // After authentication middleware
        ];
    }
}
