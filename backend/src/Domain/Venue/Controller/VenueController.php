<?php

namespace App\Domain\Venue\Controller;

use App\Domain\Venue\Service\VenueManagementService;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/venues')]
class VenueController extends AbstractController
{
    use JsonResponseTrait;

    private VenueManagementService $venueManagementService;

    public function __construct(VenueManagementService $venueManagementService)
    {
        $this->venueManagementService = $venueManagementService;
    }

    #[Route('', name: 'venue_list', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function listVenues(Request $request): JsonResponse
    {
        $role = $request->attributes->get('resolvedRole', '');
        $dtos = ($role === RoleConstants::ROLE_BORROWER)
            ? $this->venueManagementService->getAvailableVenues()
            : $this->venueManagementService->getAllVenues();

        $responseList = array_map(fn($dto) => $dto->toResponseArray(), $dtos);
        return $this->createSuccessResponse(['venues' => $responseList]);
    }

    #[Route('', name: 'venue_create', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function createVenue(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];
        $dto = $this->venueManagementService->createVenue(
            $body['venueName'] ?? '',
            $body['venueLocation'] ?? null,
            isset($body['capacityLimit']) ? (int)$body['capacityLimit'] : null
        );
        return $this->createSuccessResponse($dto->toResponseArray(), 201);
    }
}
