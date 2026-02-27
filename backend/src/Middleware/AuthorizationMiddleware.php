<?php

namespace App\Middleware;

use App\Shared\Utils\RequiresRoles;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class AuthorizationMiddleware
{
    // ===== AI GENERATED: AuthorizationMiddleware =====
    // Purpose: Enforce RBAC by reading route metadata and validating user role
    // Inputs: RequestEvent
    // Returns: void (blocks request if role insufficient)
    // Flow:
    // 1. Read authenticated identity from request
    // 2. Resolve user role via RoleResolver
    // 3. Read required roles from controller attribute
    // 4. Reject if role not in allowed list

    private RoleResolver $roleResolver;

    public function __construct(RoleResolver $roleResolver)
    {
        $this->roleResolver = $roleResolver;
    }

    // ===== AI GENERATED: onKernelRequest =====
    // Purpose: Check RBAC before controller executes
    // Inputs: RequestEvent
    // Returns: void
    // Flow:
    // 1. Skip if no identity attached (handled by AuthenticationMiddleware)
    // 2. Resolve role from identity
    // 3. Read RequiresRoles attribute from controller
    // 4. Block with 403 if role not allowed

    public function onKernelRequest(RequestEvent $requestEvent): void
    {
        $request = $requestEvent->getRequest();
        $authenticatedIdentity = $request->attributes->get('authenticatedIdentity');

        if ($authenticatedIdentity === null) {
            return;
        }

        $resolvedRole = $this->roleResolver->resolveRoleFromIdentity($authenticatedIdentity);
        $request->attributes->set('resolvedRole', $resolvedRole);

        $controllerCallable = $request->attributes->get('_controller');

        if (!is_string($controllerCallable) || !str_contains($controllerCallable, '::')) {
            return;
        }

        [$controllerClass, $methodName] = explode('::', $controllerCallable);

        try {
            $reflectionMethod = new \ReflectionMethod($controllerClass, $methodName);
            $roleAttributes = $reflectionMethod->getAttributes(RequiresRoles::class);

            if (empty($roleAttributes)) {
                $reflectionClass = new \ReflectionClass($controllerClass);
                $roleAttributes = $reflectionClass->getAttributes(RequiresRoles::class);
            }

            if (empty($roleAttributes)) {
                return;
            }

            /** @var RequiresRoles $requiresRoles */
            $requiresRoles = $roleAttributes[0]->newInstance();

            if (!in_array($resolvedRole, $requiresRoles->allowedRoles, true)) {
                $requestEvent->setResponse(new JsonResponse([
                    'errorCode' => 'AuthorizationDenied',
                    'errorMessage' => 'Insufficient permissions for this resource.',
                ], 403, ['Access-Control-Allow-Origin' => '*']));
            }
        } catch (\ReflectionException $exception) {
            return;
        }
    }
}
