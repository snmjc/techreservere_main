<?php

namespace App\Domain\Reservation\Controller;

use App\Domain\Account\Service\AdminSecurityConfirmationService;
use App\Domain\Reservation\DTO\ReservationCreateRequestDTO;
use App\Domain\Reservation\Service\ReservationCreateService;
use App\Domain\Reservation\Service\ReservationReviewService;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainOperationException;
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

    public function __construct(
        private readonly ReservationCreateService $reservationCreateService,
        private readonly ReservationReviewService $reservationReviewService,
        private readonly AdminSecurityConfirmationService $adminSecurityConfirmationService
    ) {
    }

    #[Route('', name: 'reservation_create', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function createReservation(Request $request): JsonResponse
    {
        $identity = [];
        $requestBody = [];

        try {
            $identity = $request->attributes->get('authenticatedIdentity', []);
            $requestBody = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR) ?? [];

            $createDTO = new ReservationCreateRequestDTO(
                organizationName: $requestBody['organizationName'] ?? '',
                venueIdentifier: $requestBody['venueIdentifier'] ?? null,
                requestedEquipmentList: $requestBody['requestedEquipmentList'] ?? [],
                requestedQuantity: (int) ($requestBody['requestedQuantity'] ?? 0),
                eventDateTime: $requestBody['eventDateTime'] ?? '',
                endDateTime: $requestBody['endDateTime'] ?? '',
                purposeDescription: $requestBody['purposeDescription'] ?? '',
                activityType: $requestBody['activityType'] ?? '',
                borrowerRemarks: $requestBody['borrowerRemarks'] ?? null,
                supportingDocuments: $requestBody['supportingDocuments'] ?? null
            );

            $borrowerAccountId = (int) ($identity['accountIdentifier'] ?? 0);
            if ($borrowerAccountId <= 0) {
                return $this->createErrorResponse('AuthenticationRequired', 'Unable to identify the signed-in borrower.', 401);
            }

            $responseDTO = $this->reservationCreateService->createReservation($borrowerAccountId, $createDTO);
            $responsePayload = [
                'reservationIdentifier' => $responseDTO->reservationIdentifier,
                'reservationCode' => $responseDTO->reservationCode,
                'currentStatus' => $responseDTO->currentStatus,
            ];

            return $this->createSuccessResponse($responsePayload, 201);
        } catch (\JsonException) {
            return $this->createErrorResponse('ReservationInvalidPayload', 'Reservation request body must be valid JSON.', 400);
        } catch (DomainValidationException $exception) {
            $context = $this->buildReservationCreateContext($identity, $requestBody);
            $this->logReservationCreateFailure($exception, 'validation', $context);
            return $this->createErrorResponse($exception->getErrorType(), $exception->getMessage(), 422, [
                'failureBucket' => 'validation',
            ]);
        } catch (DomainOperationException $exception) {
            $context = $this->buildReservationCreateContext($identity, $requestBody);
            $this->logReservationCreateFailure($exception, 'persistence', $context);
            return $this->createErrorResponse($exception->getErrorType(), 'Unable to submit reservation at this time.', 500, [
                'failureBucket' => 'persistence',
            ]);
        } catch (\Throwable $exception) {
            $context = $this->buildReservationCreateContext($identity, $requestBody);
            $this->logReservationCreateFailure($exception, $this->classifyReservationCreateFailureBucket($exception), $context);
            $appEnvironment = strtolower((string) ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? $_ENV['APP_RUNTIME_ENV'] ?? 'dev'));
            $errorMessage = $appEnvironment === 'prod'
                ? 'Unable to submit reservation at this time.'
                : sprintf('Unable to submit reservation at this time. %s', $exception->getMessage());

            return $this->createErrorResponse('ReservationCreateFailed', $errorMessage, 500);
        }
    }

    #[Route('', name: 'reservation_list', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function listReservations(Request $request): JsonResponse
    {
        try {
            $resolvedRole = $request->attributes->get('resolvedRole', '');
            $identity = $request->attributes->get('authenticatedIdentity', []);

            if (in_array($resolvedRole, [RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER], true)) {
                $dtos = $this->reservationReviewService->getAllReservations();
            } elseif ($resolvedRole === RoleConstants::ROLE_BORROWER) {
                $borrowerAccountId = (int) ($identity['accountIdentifier'] ?? 0);
                if ($borrowerAccountId <= 0) {
                    return $this->createErrorResponse('AuthenticationRequired', 'Unable to identify the signed-in borrower.', 401);
                }
                $dtos = $this->reservationReviewService->getReservationsByBorrower($borrowerAccountId);
            } else {
                return $this->createErrorResponse('AuthorizationDenied', 'Insufficient permissions for this resource.', 403);
            }

            $responseList = $this->applyReservationFilters(
                array_map(static fn ($dto) => $dto->toResponseArray(), $dtos),
                $request
            );

            return $this->createSuccessResponse([
                'reservations' => $responseList,
                'summary' => [
                    'total' => count($responseList),
                    'pending' => count(array_filter($responseList, static fn (array $row): bool => str_contains(strtolower((string) ($row['currentStatus'] ?? '')), 'pending'))),
                    'approved' => count(array_filter($responseList, static fn (array $row): bool => strtolower((string) ($row['currentStatus'] ?? '')) === 'approved')),
                    'cancelled' => count(array_filter($responseList, static fn (array $row): bool => strtolower((string) ($row['currentStatus'] ?? '')) === 'cancelled')),
                ],
            ]);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse('ReservationListFailed', 'Unable to load reservations at this time.', 500);
        }
    }

    #[Route('/{reservationIdentifier}', name: 'reservation_detail', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function getReservation(int $reservationIdentifier, Request $request): JsonResponse
    {
        $resolvedRole = $request->attributes->get('resolvedRole', '');
        $identity = $request->attributes->get('authenticatedIdentity', []);
        $accountIdentifier = (int) ($identity['accountIdentifier'] ?? 0);

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

    #[Route('/{reservationIdentifier}/status', name: 'reservation_update_status', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function updateReservationStatus(int $reservationIdentifier, Request $request): JsonResponse
    {
        try {
            $requestBody = json_decode($request->getContent(), true) ?? [];
            $newStatus = $requestBody['currentStatus'] ?? '';
            $rejectionReason = $requestBody['rejectionReason'] ?? null;
            $resolvedRole = (string) $request->attributes->get('resolvedRole', '');
            $identity = $request->attributes->get('authenticatedIdentity', []);
            $accountIdentifier = (int) ($identity['accountIdentifier'] ?? 0);
            $confirmedAdminEmail = (string) ($requestBody['confirmedAdminEmail'] ?? '');
            $confirmedAdminPassword = (string) ($requestBody['confirmedAdminPassword'] ?? '');

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

    private function buildReservationCreateContext(array $identity, array $requestBody): array
    {
        $equipmentIdentifiers = [];
        foreach (($requestBody['requestedEquipmentList'] ?? []) as $equipmentItem) {
            $equipmentIdentifier = (int) ($equipmentItem['equipmentIdentifier'] ?? 0);
            if ($equipmentIdentifier > 0) {
                $equipmentIdentifiers[] = $equipmentIdentifier;
            }
        }

        $hasVenue = (int) ($requestBody['venueIdentifier'] ?? 0) > 0;
        $hasEquipment = $equipmentIdentifiers !== [];
        $reservationType = $hasVenue && $hasEquipment
            ? 'both'
            : ($hasVenue ? 'venue' : ($hasEquipment ? 'equipment' : 'unknown'));

        return [
            'accountId' => (int) ($identity['accountIdentifier'] ?? 0),
            'reservationType' => $reservationType,
            'venueIdentifier' => $hasVenue ? (int) $requestBody['venueIdentifier'] : null,
            'equipmentIdentifiers' => $equipmentIdentifiers,
            'requestedQuantity' => (int) ($requestBody['requestedQuantity'] ?? 0),
            'eventDateTime' => (string) ($requestBody['eventDateTime'] ?? ''),
            'endDateTime' => (string) ($requestBody['endDateTime'] ?? ''),
        ];
    }

    private function classifyReservationCreateFailureBucket(\Throwable $exception): string
    {
        if ($exception instanceof DomainValidationException) {
            return 'validation';
        }

        if ($exception instanceof DomainOperationException) {
            return 'persistence';
        }

        return 'unexpected';
    }

    private function logReservationCreateFailure(\Throwable $exception, string $failureBucket, array $context): void
    {
        error_log(sprintf(
            'Reservation Creation - Failure Bucket: %s | Context: %s | Error [%s]: %s',
            $failureBucket,
            json_encode($context),
            $exception::class,
            $exception->getMessage()
        ));
    }

    private function applyReservationFilters(array $reservations, Request $request): array
    {
        $search = strtolower(trim((string) $request->query->get('search', '')));
        $status = strtolower(trim((string) $request->query->get('status', '')));
        $organization = strtolower(trim((string) $request->query->get('organization', '')));
        $borrower = strtolower(trim((string) $request->query->get('borrower', '')));
        $startDate = trim((string) $request->query->get('startDate', ''));
        $endDate = trim((string) $request->query->get('endDate', ''));

        return array_values(array_filter($reservations, static function (array $reservation) use ($search, $status, $organization, $borrower, $startDate, $endDate): bool {
            if ($search !== '') {
                $haystack = strtolower(implode(' ', [
                    (string) ($reservation['reservationCode'] ?? ''),
                    (string) ($reservation['organizationName'] ?? ''),
                    (string) ($reservation['borrowerFullName'] ?? ''),
                    (string) ($reservation['venueName'] ?? ''),
                ]));
                if (!str_contains($haystack, $search)) {
                    return false;
                }
            }

            if ($status !== '' && strtolower((string) ($reservation['currentStatus'] ?? '')) !== $status) {
                return false;
            }

            if ($organization !== '' && !str_contains(strtolower((string) ($reservation['organizationName'] ?? '')), $organization)) {
                return false;
            }

            if ($borrower !== '' && !str_contains(strtolower((string) ($reservation['borrowerFullName'] ?? '')), $borrower)) {
                return false;
            }

            $eventDate = substr((string) ($reservation['eventDateTime'] ?? ''), 0, 10);
            if ($startDate !== '' && $eventDate !== '' && $eventDate < $startDate) {
                return false;
            }

            if ($endDate !== '' && $eventDate !== '' && $eventDate > $endDate) {
                return false;
            }

            return true;
        }));
    }
}
