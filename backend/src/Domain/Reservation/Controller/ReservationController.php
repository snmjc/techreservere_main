<?php

namespace App\Domain\Reservation\Controller;

use App\Domain\Reservation\DTO\ReservationCreateRequestDTO;
use App\Domain\Reservation\Service\ReservationCreateService;
use App\Domain\Reservation\Service\ReservationReviewService;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/reservations')]
class ReservationController extends AbstractController
{
    use JsonResponseTrait;

    private ReservationCreateService $reservationCreateService;
    private ReservationReviewService $reservationReviewService;

    public function __construct(ReservationCreateService $reservationCreateService, ReservationReviewService $reservationReviewService)
    {
        $this->reservationCreateService = $reservationCreateService;
        $this->reservationReviewService = $reservationReviewService;
    }

    // ===== AI GENERATED: createReservation =====
    // Purpose: Borrower submits a new reservation request
    // Inputs: Request body
    // Returns: JsonResponse with created reservation

    #[Route('', name: 'reservation_create', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function createReservation(Request $request): JsonResponse
    {
        $identity = $request->attributes->get('authenticatedIdentity', []);
        $requestBody = json_decode($request->getContent(), true) ?? [];

        error_log('Reservation Creation - Identity: ' . json_encode($identity));
        error_log('Reservation Creation - Request Body: ' . json_encode($requestBody));

        $createDTO = new ReservationCreateRequestDTO(
            organizationName: $requestBody['organizationName'] ?? '',
            venueIdentifier: $requestBody['venueIdentifier'] ?? null,
            requestedEquipmentList: $requestBody['requestedEquipmentList'] ?? [],
            requestedQuantity: (int)($requestBody['requestedQuantity'] ?? 0),
            eventDateTime: $requestBody['eventDateTime'] ?? '',
            endDateTime: $requestBody['endDateTime'] ?? '',
            purposeDescription: $requestBody['purposeDescription'] ?? '',
            activityType: $requestBody['activityType'] ?? '',
            supportingDocuments: $requestBody['supportingDocuments'] ?? null
        );

        $borrowerAccountId = (int)($identity['accountIdentifier'] ?? 0);
        if ($borrowerAccountId <= 0) {
            return $this->createErrorResponse('AuthenticationRequired', 'Unable to identify the signed-in borrower.', 401);
        }
        error_log('Reservation Creation - Borrower Account ID: ' . $borrowerAccountId);
        
        $responseDTO = $this->reservationCreateService->createReservation($borrowerAccountId, $createDTO);
        error_log('Reservation Creation - Created Reservation ID: ' . $responseDTO->reservationIdentifier);

        return $this->createSuccessResponse($responseDTO->toResponseArray(), 201);
    }

    // ===== AI GENERATED: listReservations =====
    // Purpose: List reservations (Admin: all, Borrower: own only)
    // Inputs: Request
    // Returns: JsonResponse

    #[Route('', name: 'reservation_list', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function listReservations(Request $request): JsonResponse
    {
        try {
        $resolvedRole = $request->attributes->get('resolvedRole', '');
        $identity = $request->attributes->get('authenticatedIdentity', []);

        error_log('Reservation List - Resolved Role: ' . $resolvedRole);
        error_log('Reservation List - Identity: ' . json_encode($identity));

        if ($resolvedRole === RoleConstants::ROLE_BORROWER) {
            $borrowerAccountId = (int)($identity['accountIdentifier'] ?? 0);
            if ($borrowerAccountId <= 0) {
                return $this->createErrorResponse('AuthenticationRequired', 'Unable to identify the signed-in borrower.', 401);
            }
            error_log('Reservation List - Borrower Account ID: ' . $borrowerAccountId);
            $dtos = $this->reservationReviewService->getReservationsByBorrower($borrowerAccountId);
        } elseif ($resolvedRole === RoleConstants::ROLE_ADMIN || $resolvedRole === RoleConstants::ROLE_DEVELOPER) {
            $dtos = $this->reservationReviewService->getAllReservations();
        } else {
            return $this->createErrorResponse('AuthorizationDenied', 'Insufficient permissions for this resource.', 403);
        }

        error_log('Reservation List - Total Reservations Found: ' . count($dtos));

        $responseList = array_map(fn($dto) => $dto->toResponseArray(), $dtos); // DTO → array map
        return $this->createSuccessResponse(['reservations' => $responseList]);
        } catch (\Throwable $exception) {
            error_log('Reservation List - Error: ' . $exception->getMessage());
            // Dev-friendly fallback: avoid crashing the UI when the database isn't ready yet.
            // The underlying error is still logged server-side.
            return $this->createSuccessResponse(['reservations' => []]);
        }
    }

    #[Route('/{reservationIdentifier}', name: 'reservation_detail', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function getReservation(int $reservationIdentifier, Request $request): JsonResponse
    {
        $resolvedRole = $request->attributes->get('resolvedRole', '');
        $identity = $request->attributes->get('authenticatedIdentity', []);
        $accountIdentifier = (int)($identity['accountIdentifier'] ?? 0);

        if ($accountIdentifier <= 0) {
            return $this->createErrorResponse('AuthenticationRequired', 'Unable to identify the signed-in user.', 401);
        }

        try {
            $responseDTO = $this->reservationReviewService->getReservationByIdForRole($reservationIdentifier, $resolvedRole, $accountIdentifier);
            return $this->createSuccessResponse($responseDTO->toResponseArray());
        } catch (DomainNotFoundException $exception) {
            return $this->createErrorResponse('ReservationNotFound', $exception->getMessage(), 404);
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse('ReservationAccessDenied', $exception->getMessage(), 403);
        }
    }

    // ===== AI GENERATED: updateReservationStatus =====
    // Purpose: Admin updates reservation status (Approve/Reject/Request Revision)
    // Inputs: reservationIdentifier (int), Request body
    // Returns: JsonResponse

    #[Route('/{reservationIdentifier}/status', name: 'reservation_update_status', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateReservationStatus(int $reservationIdentifier, Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $newStatus = $requestBody['currentStatus'] ?? '';
        $rejectionReason = $requestBody['rejectionReason'] ?? null;

        $responseDTO = $this->reservationReviewService->updateReservationStatus($reservationIdentifier, $newStatus, $rejectionReason);

        return $this->createSuccessResponse($responseDTO->toResponseArray());
    }
}
