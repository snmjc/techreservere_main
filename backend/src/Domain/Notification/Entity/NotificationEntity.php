<?php

namespace App\Domain\Notification\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Domain\Notification\Repository\NotificationRepository::class)]
#[ORM\Table(name: 'notifications')]
class NotificationEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $notificationIdentifier = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $recipientAccountId = 0;

    #[ORM\Column(type: Types::STRING, length: 200)]
    private string $notificationTitle = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notificationMessage = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $notificationType = 'Info';

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isRead = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdTimestamp;

    public function __construct()
    {
        $this->createdTimestamp = new \DateTime();
    }

    public function getNotificationIdentifier(): ?int { return $this->notificationIdentifier; }
    public function getRecipientAccountId(): int { return $this->recipientAccountId; }
    public function setRecipientAccountId(int $val): self { $this->recipientAccountId = $val; return $this; }
    public function getNotificationTitle(): string { return $this->notificationTitle; }
    public function setNotificationTitle(string $val): self { $this->notificationTitle = $val; return $this; }
    public function getNotificationMessage(): ?string { return $this->notificationMessage; }
    public function setNotificationMessage(?string $val): self { $this->notificationMessage = $val; return $this; }
    public function getNotificationType(): string { return $this->notificationType; }
    public function setNotificationType(string $val): self { $this->notificationType = $val; return $this; }
    public function getIsRead(): bool { return $this->isRead; }
    public function setIsRead(bool $val): self { $this->isRead = $val; return $this; }
    public function getCreatedTimestamp(): \DateTimeInterface { return $this->createdTimestamp; }
}
