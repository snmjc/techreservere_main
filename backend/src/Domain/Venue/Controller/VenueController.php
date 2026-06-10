<?php

namespace App\Domain\Venue\Controller;

use App\Domain\Account\Service\AdminSecurityConfirmationService;
use App\Domain\Account\Service\AuthenticatedAccountResolver;
use App\Domain\Venue\Service\VenueManagementService;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;
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

    public function __construct(
        VenueManagementService $venueManagementService,
        private readonly AdminSecurityConfirmationService $adminSecurityConfirmationService,
        private readonly AuthenticatedAccountResolver $authenticatedAccountResolver
    ) {
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

    #[Route('/{venueIdentifier}', name: 'venue_get_by_id', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER, RoleConstants::ROLE_BORROWER])]
    public function getVenueById(int $venueIdentifier): JsonResponse
    {
        try {
            $venueDTO = $this->venueManagementService->getVenueById($venueIdentifier);

            return $this->createSuccessResponse($venueDTO->toResponseArray());
        } catch (DomainNotFoundException $exception) {
            return $this->createErrorResponse('VenueNotFound', $exception->getMessage(), 404);
        }
    }

    #[Route('', name: 'venue_create', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function createVenue(Request $request): JsonResponse
    {
        try {
            $body = json_decode($request->getContent(), true) ?? [];
            $dto = $this->venueManagementService->createVenue(
                $body['venueName'] ?? '',
                $body['venueLocation'] ?? null,
                $body['floorLevel'] ?? null,
                isset($body['capacityLimit']) ? (int)$body['capacityLimit'] : null,
                $body['availabilityDate'] ?? null,
                $body['operationalStatus'] ?? null,
                $body['description'] ?? null,
                $body['imageUrl'] ?? $body['photoData'] ?? null
            );

            return $this->createSuccessResponse($dto->toResponseArray(), 201);
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse('VenueValidationFailed', $exception->getMessage(), 422);
        }
    }

    #[Route('/{venueIdentifier}', name: 'venue_update', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateVenue(int $venueIdentifier, Request $request): JsonResponse
    {
        try {
            $body = json_decode($request->getContent(), true) ?? [];
            $dto = $this->venueManagementService->updateVenue(
                $venueIdentifier,
                $body['venueName'] ?? '',
                $body['venueLocation'] ?? null,
                $body['floorLevel'] ?? null,
                isset($body['capacityLimit']) ? (int)$body['capacityLimit'] : null,
                $body['availabilityDate'] ?? null,
                $body['operationalStatus'] ?? null,
                $body['description'] ?? null,
                $body['imageUrl'] ?? $body['photoData'] ?? null
            );

            return $this->createSuccessResponse($dto->toResponseArray());
        } catch (DomainNotFoundException $exception) {
            return $this->createErrorResponse('VenueNotFound', $exception->getMessage(), 404);
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse('VenueValidationFailed', $exception->getMessage(), 422);
        }
    }

    #[Route('/{venueIdentifier}', name: 'venue_delete', methods: ['DELETE'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function deleteVenue(int $venueIdentifier, Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];
        $securityError = $this->adminSecurityConfirmationService->validateAdminCredentials(
            $this->authenticatedAccountResolver->resolveAccountIdentifier($request),
            (string)($body['confirmedAdminEmail'] ?? ''),
            (string)($body['confirmedAdminPassword'] ?? ''),
            'deleting'
        );

        if ($securityError !== null) {
            return $this->createErrorResponse('SecurityConfirmationFailed', $securityError, 422);
        }

        try {
            $this->venueManagementService->deleteVenue($venueIdentifier);

            return $this->createSuccessResponse(['message' => 'Venue deleted successfully']);
        } catch (DomainNotFoundException $exception) {
            return $this->createErrorResponse('VenueNotFound', $exception->getMessage(), 404);
        }
    }
}
