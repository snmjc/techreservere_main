<?php

namespace App\Domain\Notification\Service;

use App\Domain\Notification\DTO\NotificationResponseDTO;
use App\Domain\Notification\Entity\NotificationEntity;
use App\Domain\Notification\Repository\NotificationRepository;

class NotificationDispatchService
{
    private NotificationRepository $notificationRepository;

    public function __construct(NotificationRepository $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    // ===== AI GENERATED: sendNotification =====
    // Purpose: Create and persist a notification for a recipient
    // Inputs: recipientAccountId, title, message, type
    // Returns: void

    public function sendNotification(int $recipientAccountId, string $notificationTitle, ?string $notificationMessage = null, string $notificationType = 'Info'): void
    {
        $entity = new NotificationEntity();
        $entity->setRecipientAccountId($recipientAccountId);
        $entity->setNotificationTitle($notificationTitle);
        $entity->setNotificationMessage($notificationMessage);
        $entity->setNotificationType($notificationType);

        $this->notificationRepository->persistNotification($entity);
    }

    // ===== AI GENERATED: getNotificationsForRecipient =====
    // Purpose: Retrieve notifications for authenticated user
    // Inputs: recipientAccountId (int)
    // Returns: NotificationResponseDTO[]

    /** @return NotificationResponseDTO[] */
    public function getNotificationsForRecipient(int $recipientAccountId): array
    {
        $entities = $this->notificationRepository->findByRecipientAccountId($recipientAccountId);
        return array_map(fn($e) => $this->transformEntityToDTO($e), $entities);
    }

    // ===== AI GENERATED: markAsRead =====
    // Purpose: Mark a notification as read
    // Inputs: notificationIdentifier (int)
    // Returns: void

    public function markAsRead(int $notificationIdentifier): void
    {
        $entity = $this->notificationRepository->find($notificationIdentifier);
        if ($entity !== null) {
            $entity->setIsRead(true);
            $this->notificationRepository->persistNotification($entity);
        }
    }

    private function transformEntityToDTO(NotificationEntity $entity): NotificationResponseDTO
    {
        return new NotificationResponseDTO(
            notificationIdentifier: $entity->getNotificationIdentifier(),
            recipientAccountId: $entity->getRecipientAccountId(),
            notificationTitle: $entity->getNotificationTitle(),
            notificationMessage: $entity->getNotificationMessage(),
            notificationType: $entity->getNotificationType(),
            isRead: $entity->getIsRead(),
            createdTimestamp: $entity->getCreatedTimestamp()->format(\DateTime::ATOM)
        );
    }
}
