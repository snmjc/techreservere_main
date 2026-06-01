<?php

namespace App\Domain\Account\Controller;

use App\Domain\Authentication\Service\AuthenticationWorkflowService;
use App\Shared\Traits\RequestPayloadTrait;
use App\Shared\Traits\ServiceResultResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/auth')]
class AuthenticationPasswordResetController
{
    use RequestPayloadTrait;
    use ServiceResultResponseTrait;

    public function __construct(private readonly AuthenticationWorkflowService $workflowService)
    {
    }

    #[Route('/password-reset/request', name: 'auth_password_reset_request', methods: ['POST'])]
    public function requestPasswordReset(Request $request): JsonResponse
    {
        $requestBody = $this->jsonBody($request);
        $emailAddress = strtolower(trim((string)($requestBody['emailAddress'] ?? '')));

        if ($emailAddress === '' || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            return $this->createErrorResponse('ValidationError', 'A valid email address is required.', 422);
        }

        return $this->serviceResultResponse($this->workflowService->requestPasswordReset($emailAddress));
    }

    #[Route('/password-reset/confirm', name: 'auth_password_reset_confirm', methods: ['POST'])]
    public function confirmPasswordReset(Request $request): JsonResponse
    {
        $requestBody = $this->jsonBody($request);
        $emailAddress = strtolower(trim((string)($requestBody['emailAddress'] ?? '')));
        $code = trim((string)($requestBody['code'] ?? ''));
        $newPassword = (string)($requestBody['newPassword'] ?? '');
        $confirmPassword = (string)($requestBody['confirmPassword'] ?? '');

        if ($emailAddress === '' || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL) || $code === '') {
            return $this->createErrorResponse('ValidationError', 'Email address and reset code are required.', 422);
        }

        return $this->serviceResultResponse(
            $this->workflowService->confirmPasswordReset($emailAddress, $code, $newPassword, $confirmPassword)
        );
    }
}
