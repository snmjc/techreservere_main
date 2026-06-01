<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\Service\UserRegistrationWorkflowService;
use App\Shared\Traits\RequestPayloadTrait;
use App\Shared\Traits\ServiceResultResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/users')]
class UserRegistrationController
{
    use RequestPayloadTrait;
    use ServiceResultResponseTrait;

    public function __construct(private readonly UserRegistrationWorkflowService $workflowService)
    {
    }

    #[Route('/register', name: 'user_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->register($this->jsonBody($request)),
            'RegistrationFailed',
            'Failed to register account.'
        );
    }
}
