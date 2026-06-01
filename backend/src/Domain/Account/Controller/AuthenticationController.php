<?php

namespace App\Domain\Account\Controller;

use App\Domain\Authentication\Service\AuthenticationLoginService;
use App\Domain\Authentication\Service\AuthenticationRegistrationService;
use App\Domain\Authentication\Service\ClerkLoginPreflightService;
use App\Domain\Authentication\Service\PasswordResetCodeService;
use App\Shared\Traits\JsonResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/auth')]
class AuthenticationController
{
    use JsonResponseTrait;

    private AuthenticationLoginService $authenticationLoginService;
    private ClerkLoginPreflightService $clerkLoginPreflightService;
    private PasswordResetCodeService $passwordResetCodeService;
    private AuthenticationRegistrationService $authenticationRegistrationService;

    public function __construct(
        AuthenticationLoginService $authenticationLoginService,
        ClerkLoginPreflightService $clerkLoginPreflightService,
        PasswordResetCodeService $passwordResetCodeService,
        AuthenticationRegistrationService $authenticationRegistrationService
    ) {
        $this->authenticationLoginService = $authenticationLoginService;
        $this->clerkLoginPreflightService = $clerkLoginPreflightService;
        $this->passwordResetCodeService = $passwordResetCodeService;
        $this->authenticationRegistrationService = $authenticationRegistrationService;
    }

    #[Route('/login', name: 'auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];

        $emailAddress = trim($requestBody['emailAddress'] ?? '');
        $passwordText = $requestBody['passwordText'] ?? '';

        if (empty($emailAddress) || empty($passwordText)) {
            return $this->createErrorResponse(
                'ValidationError',
                'Email address and password are required.',
                400
            );
        }

        return $this->serviceResultToResponse($this->authenticationLoginService->login($emailAddress, $passwordText));
    }

    #[Route('/clerk-login-preflight', name: 'auth_clerk_login_preflight', methods: ['POST'])]
    public function clerkLoginPreflight(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $emailAddress = strtolower(trim((string)($requestBody['emailAddress'] ?? '')));

        if ($emailAddress === '' || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            return $this->createErrorResponse(
                'ValidationError',
                'A valid email address is required.',
                422
            );
        }

        return $this->serviceResultToResponse($this->clerkLoginPreflightService->check($emailAddress));
    }

    #[Route('/password-reset/request', name: 'auth_password_reset_request', methods: ['POST'])]
    public function requestPasswordReset(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $emailAddress = strtolower(trim((string)($requestBody['emailAddress'] ?? '')));

        if ($emailAddress === '' || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            return $this->createErrorResponse('ValidationError', 'A valid email address is required.', 422);
        }

        return $this->serviceResultToResponse($this->passwordResetCodeService->requestReset($emailAddress));
    }

    #[Route('/password-reset/confirm', name: 'auth_password_reset_confirm', methods: ['POST'])]
    public function confirmPasswordReset(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $emailAddress = strtolower(trim((string)($requestBody['emailAddress'] ?? '')));
        $code = trim((string)($requestBody['code'] ?? ''));
        $newPassword = (string)($requestBody['newPassword'] ?? '');
        $confirmPassword = (string)($requestBody['confirmPassword'] ?? '');

        if ($emailAddress === '' || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL) || $code === '') {
            return $this->createErrorResponse('ValidationError', 'Email address and reset code are required.', 422);
        }

        return $this->serviceResultToResponse($this->passwordResetCodeService->confirmReset($emailAddress, $code, $newPassword, $confirmPassword));
    }

    #[Route('/register', name: 'auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            $contentType = $request->headers->get('Content-Type', '');
            
            if (strpos($contentType, 'application/json') !== false) {
                $requestBody = json_decode($request->getContent(), true) ?? [];
                $firstName = trim($requestBody['firstName'] ?? '');
                $lastName = trim($requestBody['lastName'] ?? '');
                $emailAddress = trim($requestBody['emailAddress'] ?? '');
                $passwordText = $requestBody['passwordText'] ?? '';
            } else {
                // Handle FormData
                $firstName = trim($request->request->get('firstName', ''));
                $lastName = trim($request->request->get('lastName', ''));
                $emailAddress = trim($request->request->get('emailAddress', ''));
                $passwordText = $request->request->get('passwordText', '');
            }

            return $this->serviceResultToResponse(
                $this->authenticationRegistrationService->register($firstName, $lastName, $emailAddress, $passwordText)
            );
        } catch (\Exception $exception) {
            error_log('Registration error: ' . $exception->getMessage());
            return $this->createErrorResponse(
                'RegistrationError',
                'An error occurred during registration. Please try again.',
                500
            );
        }
    }

    private function serviceResultToResponse(array $result): JsonResponse
    {
        if (($result['success'] ?? false) === true) {
            return $this->createSuccessResponse($result['data'] ?? [], (int)($result['status'] ?? 200));
        }

        return $this->createErrorResponse(
            (string)($result['errorCode'] ?? 'RequestFailed'),
            (string)($result['message'] ?? 'Unable to complete the request.'),
            (int)($result['status'] ?? 500)
        );
    }
}
