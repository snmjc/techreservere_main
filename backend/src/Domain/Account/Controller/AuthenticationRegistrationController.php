<?php

namespace App\Domain\Account\Controller;

use App\Domain\Authentication\Service\AuthenticationWorkflowService;
use App\Shared\Traits\RequestPayloadTrait;
use App\Shared\Traits\ServiceResultResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/auth')]
class AuthenticationRegistrationController
{
    use RequestPayloadTrait;
    use ServiceResultResponseTrait;

    public function __construct(private readonly AuthenticationWorkflowService $workflowService)
    {
    }

    #[Route('/register', name: 'auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            $requestBody = $this->registrationBody($request);

            return $this->serviceResultResponse(
                $this->workflowService->register(
                    $requestBody['firstName'],
                    $requestBody['lastName'],
                    $requestBody['emailAddress'],
                    $requestBody['passwordText']
                )
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

    private function registrationBody(Request $request): array
    {
        if (strpos($request->headers->get('Content-Type', ''), 'application/json') !== false) {
            $requestBody = $this->jsonBody($request);

            return [
                'firstName' => trim($requestBody['firstName'] ?? ''),
                'lastName' => trim($requestBody['lastName'] ?? ''),
                'emailAddress' => trim($requestBody['emailAddress'] ?? ''),
                'passwordText' => $requestBody['passwordText'] ?? '',
            ];
        }

        return [
            'firstName' => trim($request->request->get('firstName', '')),
            'lastName' => trim($request->request->get('lastName', '')),
            'emailAddress' => trim($request->request->get('emailAddress', '')),
            'passwordText' => $request->request->get('passwordText', ''),
        ];
    }
}
