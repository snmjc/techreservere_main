<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\Service\UserRegistrationWorkflowService;
use App\Shared\Traits\RequestPayloadTrait;
use App\Shared\Traits\ServiceResultResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/users')]
class PublicSignupRequestController
{
    use RequestPayloadTrait;
    use ServiceResultResponseTrait;

    public function __construct(private readonly UserRegistrationWorkflowService $workflowService)
    {
    }

    #[Route('/signup-requests', name: 'create_public_signup_request', methods: ['POST'])]
    public function createPublicSignupRequest(Request $request): JsonResponse
    {
        $requestBody = $request->request->all();
        if ($requestBody === []) {
            $requestBody = $this->jsonBody($request);
        }

        return $this->serviceResultResponse(
            $this->workflowService->createPublicSignupRequest(
                $requestBody,
                $request->files->get('supportingDocument')
            ),
            'CreateSignupRequestFailed',
            'Failed to create signup request.'
        );
    }

    #[Route('/invite', name: 'invite_user', methods: ['POST'])]
    public function inviteUser(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->inviteUser($this->jsonBody($request)),
            'InviteUserFailed',
            'Unable to send invitation.'
        );
    }
}
