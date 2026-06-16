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
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/venues')]
class VenueController extends AbstractController
{
    use JsonResponseTrait;

    private const MAX_VENUE_REQUEST_BYTES = 1_800_000;

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
        $selectedDate = $request->query->get('selectedDate');
        $startTime = $request->query->get('startTime');
        $endTime = $request->query->get('endTime');
        $dtos = ($role === RoleConstants::ROLE_BORROWER)
            ? $this->venueManagementService->getAvailableVenues($selectedDate, $startTime, $endTime)
            : $this->venueManagementService->getAllVenues($selectedDate, $startTime, $endTime);

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
            $body = $this->decodeRequestBody($request);
            $dto = $this->venueManagementService->createVenue(
                $body['venueName'] ?? '',
                $body['venueLocation'] ?? null,
                $body['floorLevel'] ?? null,
                isset($body['capacityLimit']) ? (int)$body['capacityLimit'] : null,
                $body['availabilityDate'] ?? null,
                $body['operationalStatus'] ?? null,
                $body['availabilityStatus'] ?? null,
                $body['description'] ?? null,
                $body['imageUrl'] ?? null
            );

            return $this->createSuccessResponse($dto->toResponseArray(), 201);
        } catch (\JsonException) {
            return $this->createErrorResponse('VenueInvalidPayload', 'Venue request body must be valid JSON.', Response::HTTP_BAD_REQUEST);
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse('VenueValidationFailed', $exception->getMessage(), 422);
        } catch (UniqueConstraintViolationException $exception) {
            return $this->createErrorResponse('VenueConflict', 'Venue name already exists.', 409);
        } catch (\Throwable $exception) {
            $this->logVenueFailure('create', $request, $exception);
            return $this->createErrorResponse('VenueCreateFailed', 'Unable to create venue at this time.', 500);
        }
    }

    #[Route('/{venueIdentifier}', name: 'venue_update', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateVenue(int $venueIdentifier, Request $request): JsonResponse
    {
        try {
            $body = $this->decodeRequestBody($request);
            $dto = $this->venueManagementService->updateVenue(
                $venueIdentifier,
                $body['venueName'] ?? '',
                $body['venueLocation'] ?? null,
                $body['floorLevel'] ?? null,
                isset($body['capacityLimit']) ? (int)$body['capacityLimit'] : null,
                $body['availabilityDate'] ?? null,
                $body['operationalStatus'] ?? null,
                $body['availabilityStatus'] ?? null,
                $body['description'] ?? null,
                $body['imageUrl'] ?? null,
                array_key_exists('imageUrl', $body)
            );

            return $this->createSuccessResponse($dto->toResponseArray());
        } catch (\JsonException) {
            return $this->createErrorResponse('VenueInvalidPayload', 'Venue request body must be valid JSON.', Response::HTTP_BAD_REQUEST);
        } catch (DomainNotFoundException $exception) {
            return $this->createErrorResponse('VenueNotFound', $exception->getMessage(), 404);
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse('VenueValidationFailed', $exception->getMessage(), 422);
        } catch (UniqueConstraintViolationException $exception) {
            return $this->createErrorResponse('VenueConflict', 'Venue name already exists.', 409);
        } catch (\Throwable $exception) {
            $this->logVenueFailure('update', $request, $exception);
            return $this->createErrorResponse('VenueUpdateFailed', 'Unable to update venue at this time.', 500);
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

    private function decodeRequestBody(Request $request): array
    {
        $rawBody = trim((string) $request->getContent());
        if ($rawBody === '') {
            return [];
        }

        if (strlen($rawBody) > self::MAX_VENUE_REQUEST_BYTES) {
            throw new DomainValidationException('Venue request is too large. Please upload a smaller JPG image.');
        }

        return json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
    }

    private function logVenueFailure(string $action, Request $request, \Throwable $exception): void
    {
        $rawBody = (string) $request->getContent();
        $decodedBody = json_decode($rawBody, true);
        $payload = is_array($decodedBody) ? $decodedBody : [];
        $imageValue = (string) ($payload['imageUrl'] ?? '');

        error_log(sprintf(
            'Venue %s failed: %s | context=%s',
            $action,
            $exception->getMessage(),
            json_encode([
                'contentLength' => strlen($rawBody),
                'venueName' => $payload['venueName'] ?? null,
                'hasImage' => $imageValue !== '',
                'imageLength' => strlen($imageValue),
                'operationalStatus' => $payload['operationalStatus'] ?? null,
                'availabilityStatus' => $payload['availabilityStatus'] ?? null,
                'availabilityDate' => $payload['availabilityDate'] ?? null,
            ], JSON_UNESCAPED_SLASHES)
        ));
    }
}
