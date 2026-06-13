<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\Service\InvitationAcceptanceService;
use App\Shared\Traits\RequestPayloadTrait;
use App\Shared\Traits\ServiceResultResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/invitations')]
class InvitationAcceptanceController
{
    use RequestPayloadTrait;
    use ServiceResultResponseTrait;

    public function __construct(private readonly InvitationAcceptanceService $invitationAcceptanceService)
    {
    }

    #[Route('/accept', name: 'accept_public_invitation', methods: ['POST'])]
    public function accept(Request $request): JsonResponse
    {
        $payload = $this->jsonBody($request);

        return $this->serviceResultResponse(
            $this->invitationAcceptanceService->accept((string)($payload['token'] ?? '')),
            'AcceptInvitationFailed',
            'Unable to accept the invitation.'
        );
    }
}
