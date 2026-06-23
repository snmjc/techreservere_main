<?php

namespace App\Domain\Reservation\Controller;

use App\Domain\Reservation\Service\ReservationPolicyConfigService;
use App\Shared\Exceptions\DomainValidationException;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/reservation-policy')]
class ReservationPolicyController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(private readonly ReservationPolicyConfigService $reservationPolicyConfigService)
    {
    }

    #[Route('/booking-window', name: 'reservation_policy_booking_window_get', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function getBookingWindow(): JsonResponse
    {
        return $this->createSuccessResponse($this->reservationPolicyConfigService->getBookingWindow());
    }

    #[Route('/booking-window', name: 'reservation_policy_booking_window_put', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateBookingWindow(Request $request): JsonResponse
    {
        try {
            $body = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR) ?? [];

            return $this->createSuccessResponse(
                $this->reservationPolicyConfigService->saveBookingWindow($body)
            );
        } catch (\JsonException) {
            return $this->createErrorResponse('ReservationPolicyInvalidPayload', 'Reservation policy body must be valid JSON.', 400);
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse('ReservationPolicyValidationFailed', $exception->getMessage(), 422);
        }
    }

    #[Route('/class-schedules', name: 'reservation_policy_class_schedules_list', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function listClassSchedules(Request $request): JsonResponse
    {
        $venueIdentifier = $request->query->getInt('venueIdentifier');
        $dateFrom = $request->query->get('dateFrom');
        $dateTo = $request->query->get('dateTo');

        return $this->createSuccessResponse([
            'scheduleBlocks' => $this->reservationPolicyConfigService->listClassScheduleBlocks(
                $venueIdentifier > 0 ? $venueIdentifier : null,
                is_string($dateFrom) ? $dateFrom : null,
                is_string($dateTo) ? $dateTo : null
            ),
        ]);
    }

    #[Route('/class-schedules', name: 'reservation_policy_class_schedules_create', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function createClassSchedule(Request $request): JsonResponse
    {
        try {
            $body = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR) ?? [];

            return $this->createSuccessResponse(
                $this->reservationPolicyConfigService->createClassScheduleBlock($body),
                201
            );
        } catch (\JsonException) {
            return $this->createErrorResponse('ReservationScheduleInvalidPayload', 'Class schedule body must be valid JSON.', 400);
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse('ReservationScheduleValidationFailed', $exception->getMessage(), 422);
        }
    }

    #[Route('/class-schedules/{scheduleBlockIdentifier}', name: 'reservation_policy_class_schedules_update', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateClassSchedule(int $scheduleBlockIdentifier, Request $request): JsonResponse
    {
        try {
            $body = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR) ?? [];

            return $this->createSuccessResponse(
                $this->reservationPolicyConfigService->updateClassScheduleBlock($scheduleBlockIdentifier, $body)
            );
        } catch (\JsonException) {
            return $this->createErrorResponse('ReservationScheduleInvalidPayload', 'Class schedule body must be valid JSON.', 400);
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse('ReservationScheduleValidationFailed', $exception->getMessage(), 422);
        }
    }

    #[Route('/class-schedules/{scheduleBlockIdentifier}', name: 'reservation_policy_class_schedules_delete', methods: ['DELETE'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function deleteClassSchedule(int $scheduleBlockIdentifier): JsonResponse
    {
        $this->reservationPolicyConfigService->deleteClassScheduleBlock($scheduleBlockIdentifier);

        return $this->createSuccessResponse(['message' => 'Class schedule block deleted successfully.']);
    }
}
