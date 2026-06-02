<?php

namespace App\Domain\Account\Controller;

use App\Domain\Authentication\Service\AuthenticationWorkflowService;
use App\Shared\Traits\RequestPayloadTrait;
use App\Shared\Traits\ServiceResultResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/auth')]
class AuthenticationController
{
    use RequestPayloadTrait;
    use ServiceResultResponseTrait;

    public function __construct(private readonly AuthenticationWorkflowService $workflowService)
    {
    }

    #[Route('/login', name: 'auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $requestBody = $this->jsonBody($request);

        $emailAddress = trim($requestBody['emailAddress'] ?? '');
        $passwordText = $requestBody['passwordText'] ?? '';

        if (empty($emailAddress) || empty($passwordText)) {
            return $this->createErrorResponse(
                'ValidationError',
                'Email address and password are required.',
                400
            );
        }

        return $this->serviceResultResponse($this->workflowService->login($emailAddress, $passwordText));
    }

    #[Route('/clerk-login-preflight', name: 'auth_clerk_login_preflight', methods: ['POST'])]
    public function clerkLoginPreflight(Request $request): JsonResponse
    {
        $requestBody = $this->jsonBody($request);
        $emailAddress = strtolower(trim((string)($requestBody['emailAddress'] ?? '')));

        if ($emailAddress === '' || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            return $this->createErrorResponse(
                'ValidationError',
                'A valid email address is required.',
                422
            );
        }

        return $this->serviceResultResponse($this->workflowService->clerkLoginPreflight($emailAddress));
    }
}
