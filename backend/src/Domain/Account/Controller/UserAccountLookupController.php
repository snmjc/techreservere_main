<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\Service\UserRegistrationWorkflowService;
use App\Shared\Traits\ServiceResultResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/users')]
class UserAccountLookupController
{
    use ServiceResultResponseTrait;

    public function __construct(private readonly UserRegistrationWorkflowService $workflowService)
    {
    }

    #[Route('/me', name: 'get_my_account', methods: ['GET'])]
    public function me(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->getCurrentAccount($request->headers->get('Authorization', '')),
            'InvalidToken',
            'Clerk token verification failed.'
        );
    }

    #[Route('/pending', name: 'list_pending_users', methods: ['GET'])]
    public function listPendingUsers(): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->listPendingUsers(),
            'PendingUsersFailed',
            'Unable to load pending users.'
        );
    }
}
