<?php

namespace App\Domain\Reservation\Controller;

use App\Domain\Reservation\DTO\ReservationCreateRequestDTO;
use App\Domain\Reservation\Service\ReservationCreateService;
use App\Domain\Reservation\Service\ReservationReviewService;
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
        $identity = $request->attributes->get('authenticatedIdentity');
        $requestBody = json_decode($request->getContent(), true) ?? [];

        $createDTO = new ReservationCreateRequestDTO(
            organizationName: $requestBody['organizationName'] ?? '',
            venueIdentifier: $requestBody['venueIdentifier'] ?? null,
            requestedEquipmentList: $requestBody['requestedEquipmentList'] ?? [],
            requestedQuantity: (int)($requestBody['requestedQuantity'] ?? 0),
            eventDateTime: $requestBody['eventDateTime'] ?? '',
            purposeDescription: $requestBody['purposeDescription'] ?? '',
            activityType: $requestBody['activityType'] ?? '',
            supportingDocuments: $requestBody['supportingDocuments'] ?? null
        );

        $borrowerAccountId = $identity['accountIdentifier'] ?? 0;
        $responseDTO = $this->reservationCreateService->createReservation($borrowerAccountId, $createDTO);

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
        $resolvedRole = $request->attributes->get('resolvedRole', '');
        $identity = $request->attributes->get('authenticatedIdentity');

        if ($resolvedRole === RoleConstants::ROLE_BORROWER) {
            $borrowerAccountId = $identity['accountIdentifier'] ?? 0;
            $dtos = $this->reservationReviewService->getReservationsByBorrower($borrowerAccountId);
        } else {
            $dtos = $this->reservationReviewService->getAllReservations();
        }

        $responseList = array_map(fn($dto) => $dto->toResponseArray(), $dtos); // DTO → array map
        return $this->createSuccessResponse(['reservations' => $responseList]);
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
