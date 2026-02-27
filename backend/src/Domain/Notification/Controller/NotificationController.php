<?php

namespace App\Domain\Notification\Controller;

use App\Domain\Notification\Service\NotificationDispatchService;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/notifications')]
class NotificationController extends AbstractController
{
    use JsonResponseTrait;

    private NotificationDispatchService $notificationDispatchService;

    public function __construct(NotificationDispatchService $notificationDispatchService)
    {
        $this->notificationDispatchService = $notificationDispatchService;
    }

    // ===== AI GENERATED: getMyNotifications =====
    // Purpose: Return notifications for authenticated user
    // Inputs: Request (with authenticatedIdentity)
    // Returns: JsonResponse

    #[Route('', name: 'notification_list_mine', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function getMyNotifications(Request $request): JsonResponse
    {
        $identity = $request->attributes->get('authenticatedIdentity');
        $recipientAccountId = $identity['accountIdentifier'] ?? 0;

        $dtos = $this->notificationDispatchService->getNotificationsForRecipient($recipientAccountId);
        $responseList = array_map(fn($dto) => $dto->toResponseArray(), $dtos);

        return $this->createSuccessResponse(['notifications' => $responseList]);
    }

    // ===== AI GENERATED: markNotificationAsRead =====
    // Purpose: Mark a specific notification as read
    // Inputs: notificationIdentifier (int)
    // Returns: JsonResponse

    #[Route('/{notificationIdentifier}/read', name: 'notification_mark_read', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function markNotificationAsRead(int $notificationIdentifier): JsonResponse
    {
        $this->notificationDispatchService->markAsRead($notificationIdentifier);
        return $this->createSuccessResponse(['message' => 'Notification marked as read.']);
    }
}
