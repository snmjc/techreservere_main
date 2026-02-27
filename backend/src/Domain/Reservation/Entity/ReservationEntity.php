<?php

namespace App\Domain\Reservation\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Domain\Reservation\Repository\ReservationRepository::class)]
#[ORM\Table(name: 'reservations')]
#[ORM\HasLifecycleCallbacks]
class ReservationEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $reservationIdentifier = null;

    #[ORM\Column(type: Types::STRING, length: 20, unique: true)]
    private string $reservationCode = '';

    #[ORM\Column(type: Types::INTEGER)]
    private int $borrowerAccountId = 0;

    #[ORM\Column(type: Types::STRING, length: 200)]
    private string $organizationName = '';

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $venueIdentifier = null;

    #[ORM\Column(type: Types::JSON)]
    private array $requestedEquipmentList = [];

    #[ORM\Column(type: Types::INTEGER)]
    private int $requestedQuantity = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $eventDateTime;

    #[ORM\Column(type: Types::STRING, length: 200)]
    private string $purposeDescription = '';

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $activityType = '';

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $currentStatus = 'Pending Review';

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $priorityLevel = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $supportingDocuments = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $submissionTimestamp;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedTimestamp;

    public function __construct()
    {
        $this->eventDateTime = new \DateTime();
        $this->submissionTimestamp = new \DateTime();
        $this->updatedTimestamp = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedTimestamp = new \DateTime();
    }

    public function getReservationIdentifier(): ?int { return $this->reservationIdentifier; }
    public function getReservationCode(): string { return $this->reservationCode; }
    public function setReservationCode(string $reservationCode): self { $this->reservationCode = $reservationCode; return $this; }
    public function getBorrowerAccountId(): int { return $this->borrowerAccountId; }
    public function setBorrowerAccountId(int $borrowerAccountId): self { $this->borrowerAccountId = $borrowerAccountId; return $this; }
    public function getOrganizationName(): string { return $this->organizationName; }
    public function setOrganizationName(string $organizationName): self { $this->organizationName = $organizationName; return $this; }
    public function getVenueIdentifier(): ?int { return $this->venueIdentifier; }
    public function setVenueIdentifier(?int $venueIdentifier): self { $this->venueIdentifier = $venueIdentifier; return $this; }
    public function getRequestedEquipmentList(): array { return $this->requestedEquipmentList; }
    public function setRequestedEquipmentList(array $requestedEquipmentList): self { $this->requestedEquipmentList = $requestedEquipmentList; return $this; }
    public function getRequestedQuantity(): int { return $this->requestedQuantity; }
    public function setRequestedQuantity(int $requestedQuantity): self { $this->requestedQuantity = $requestedQuantity; return $this; }
    public function getEventDateTime(): \DateTimeInterface { return $this->eventDateTime; }
    public function setEventDateTime(\DateTimeInterface $eventDateTime): self { $this->eventDateTime = $eventDateTime; return $this; }
    public function getPurposeDescription(): string { return $this->purposeDescription; }
    public function setPurposeDescription(string $purposeDescription): self { $this->purposeDescription = $purposeDescription; return $this; }
    public function getActivityType(): string { return $this->activityType; }
    public function setActivityType(string $activityType): self { $this->activityType = $activityType; return $this; }
    public function getCurrentStatus(): string { return $this->currentStatus; }
    public function setCurrentStatus(string $currentStatus): self { $this->currentStatus = $currentStatus; return $this; }
    public function getPriorityLevel(): ?string { return $this->priorityLevel; }
    public function setPriorityLevel(?string $priorityLevel): self { $this->priorityLevel = $priorityLevel; return $this; }
    public function getRejectionReason(): ?string { return $this->rejectionReason; }
    public function setRejectionReason(?string $rejectionReason): self { $this->rejectionReason = $rejectionReason; return $this; }
    public function getSupportingDocuments(): ?array { return $this->supportingDocuments; }
    public function setSupportingDocuments(?array $supportingDocuments): self { $this->supportingDocuments = $supportingDocuments; return $this; }
    public function getSubmissionTimestamp(): \DateTimeInterface { return $this->submissionTimestamp; }
    public function getUpdatedTimestamp(): \DateTimeInterface { return $this->updatedTimestamp; }
}
