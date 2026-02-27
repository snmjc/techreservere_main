<?php

namespace App\Domain\Notification\DTO;

class NotificationResponseDTO
{
    // ===== AI GENERATED: NotificationResponseDTO =====
    // Purpose: Response DTO for notification data

    public int $notificationIdentifier;
    public int $recipientAccountId;
    public string $notificationTitle;
    public ?string $notificationMessage;
    public string $notificationType;
    public bool $isRead;
    public string $createdTimestamp;

    public function __construct(int $notificationIdentifier, int $recipientAccountId, string $notificationTitle, ?string $notificationMessage, string $notificationType, bool $isRead, string $createdTimestamp)
    {
        $this->notificationIdentifier = $notificationIdentifier;
        $this->recipientAccountId = $recipientAccountId;
        $this->notificationTitle = $notificationTitle;
        $this->notificationMessage = $notificationMessage;
        $this->notificationType = $notificationType;
        $this->isRead = $isRead;
        $this->createdTimestamp = $createdTimestamp;
    }

    public function toResponseArray(): array
    {
        return [
            'notificationIdentifier' => $this->notificationIdentifier,
            'recipientAccountId' => $this->recipientAccountId,
            'notificationTitle' => $this->notificationTitle,
            'notificationMessage' => $this->notificationMessage,
            'notificationType' => $this->notificationType,
            'isRead' => $this->isRead,
            'createdTimestamp' => $this->createdTimestamp,
        ];
    }
}
