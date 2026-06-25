<?php

namespace App\Domain\Reservation\Controller;

use App\Domain\Reservation\DTO\ReservationCreateRequestDTO;
use App\Domain\Account\Service\AdminSecurityConfirmationService;
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
    private AdminSecurityConfirmationService $adminSecurityConfirmationService;

    public function __construct(
        ReservationCreateService $reservationCreateService,
        ReservationReviewService $reservationReviewService,
        AdminSecurityConfirmationService $adminSecurityConfirmationService
    )
    {
        $this->reservationCreateService = $reservationCreateService;
        $this->reservationReviewService = $reservationReviewService;
        $this->adminSecurityConfirmationService = $adminSecurityConfirmationService;
    }

    // ===== AI GENERATED: createReservation =====
    // Purpose: Borrower submits a new reservation request
    // Inputs: Request body
    // Returns: JsonResponse with created reservation

    #[Route('', name: 'reservation_create', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function createReservation(Request $request): JsonResponse
    {
        try {
            $identity = $request->attributes->get('authenticatedIdentity', []);
            $requestBody = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR) ?? [];

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
                borrowerRemarks: $requestBody['borrowerRemarks'] ?? null,
                supportingDocuments: $requestBody['supportingDocuments'] ?? null
            );

            $borrowerAccountId = (int)($identity['accountIdentifier'] ?? 0);
            if ($borrowerAccountId <= 0) {
                return $this->createErrorResponse('AuthenticationRequired', 'Unable to identify the signed-in borrower.', 401);
            }
            error_log('Reservation Creation - Borrower Account ID: ' . $borrowerAccountId);

            $responseDTO = $this->reservationCreateService->createReservation($borrowerAccountId, $createDTO);
            error_log('Reservation Creation - Created Reservation ID: ' . $responseDTO->reservationIdentifier);

            return $this->createSuccessResponse([
                'reservationIdentifier' => $responseDTO->reservationIdentifier,
                'reservationCode' => $responseDTO->reservationCode,
                'currentStatus' => $responseDTO->currentStatus,
            ], 201);
        } catch (\JsonException) {
            return $this->createErrorResponse('ReservationInvalidPayload', 'Reservation request body must be valid JSON.', 400);
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse('ReservationValidationFailed', $exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'Reservation Creation - Error [%s]: %s in %s:%d',
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));
            error_log('Reservation Creation - Trace: ' . $exception->getTraceAsString());
            return $this->createErrorResponse('ReservationCreateFailed', 'Unable to submit reservation at this time.', 500);
        }
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

        if ($resolvedRole === RoleConstants::ROLE_ADMIN) {
            $dtos = $this->reservationReviewService->getAllReservations();
        } elseif ($resolvedRole === RoleConstants::ROLE_BORROWER) {
            $borrowerAccountId = (int)($identity['accountIdentifier'] ?? 0);
            if ($borrowerAccountId <= 0) {
                return $this->createErrorResponse('AuthenticationRequired', 'Unable to identify the signed-in borrower.', 401);
            }
            error_log('Reservation List - Borrower Account ID: ' . $borrowerAccountId);
            $dtos = $this->reservationReviewService->getReservationsByBorrower($borrowerAccountId);
        } else {
            return $this->createErrorResponse('AuthorizationDenied', 'Insufficient permissions for this resource.', 403);
        }

        error_log('Reservation List - Total Reservations Found: ' . count($dtos));

        $responseList = array_map(fn($dto) => $dto->toResponseArray(), $dtos); // DTO → array map
        return $this->createSuccessResponse(['reservations' => $responseList]);
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'Reservation List - Error [%s]: %s in %s:%d',
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));
            return $this->createErrorResponse('ReservationListFailed', 'Unable to load reservations at this time.', 500);
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
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function updateReservationStatus(int $reservationIdentifier, Request $request): JsonResponse
    {
        try {
            $requestBody = json_decode($request->getContent(), true) ?? [];
            $newStatus = $requestBody['currentStatus'] ?? '';
            $rejectionReason = $requestBody['rejectionReason'] ?? null;
            $resolvedRole = (string)$request->attributes->get('resolvedRole', '');
            $identity = $request->attributes->get('authenticatedIdentity', []);
            $accountIdentifier = (int)($identity['accountIdentifier'] ?? 0);
            $confirmedAdminEmail = (string)($requestBody['confirmedAdminEmail'] ?? '');
            $confirmedAdminPassword = (string)($requestBody['confirmedAdminPassword'] ?? '');

            if (in_array($resolvedRole, [RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER], true)) {
                if ($newStatus === 'Approved') {
                    $emailError = $this->adminSecurityConfirmationService->validateAdminEmail(
                        $accountIdentifier,
                        $confirmedAdminEmail,
                        'approving'
                    );

                    if ($emailError !== null) {
                        return $this->createErrorResponse('SecurityConfirmationFailed', $emailError, 422);
                    }
                }

                if ($newStatus === 'Rejected') {
                    $credentialError = $this->adminSecurityConfirmationService->validateAdminCredentials(
                        $accountIdentifier,
                        $confirmedAdminEmail,
                        $confirmedAdminPassword,
                        'denying'
                    );

                    if ($credentialError !== null) {
                        return $this->createErrorResponse('SecurityConfirmationFailed', $credentialError, 422);
                    }
                }
            }

            $responseDTO = $this->reservationReviewService->updateReservationStatusForActor(
                $reservationIdentifier,
                $newStatus,
                $resolvedRole,
                $accountIdentifier,
                $rejectionReason
            );

            return $this->createSuccessResponse($responseDTO->toResponseArray());
        } catch (DomainNotFoundException $exception) {
            return $this->createErrorResponse('ReservationNotFound', $exception->getMessage(), 404);
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse('ReservationStatusUpdateDenied', $exception->getMessage(), 422);
        }
    }
}
