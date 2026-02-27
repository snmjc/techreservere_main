<?php

namespace App\Domain\Venue\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Domain\Venue\Repository\VenueRepository::class)]
#[ORM\Table(name: 'venues')]
#[ORM\HasLifecycleCallbacks]
class VenueEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $venueIdentifier = null;

    #[ORM\Column(type: Types::STRING, length: 150)]
    private string $venueName = '';

    #[ORM\Column(type: Types::STRING, length: 200, nullable: true)]
    private ?string $venueLocation = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $capacityLimit = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $availabilityStatus = 'Available';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdTimestamp;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedTimestamp;

    public function __construct()
    {
        $this->createdTimestamp = new \DateTime();
        $this->updatedTimestamp = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void { $this->updatedTimestamp = new \DateTime(); }

    public function getVenueIdentifier(): ?int { return $this->venueIdentifier; }
    public function getVenueName(): string { return $this->venueName; }
    public function setVenueName(string $venueName): self { $this->venueName = $venueName; return $this; }
    public function getVenueLocation(): ?string { return $this->venueLocation; }
    public function setVenueLocation(?string $venueLocation): self { $this->venueLocation = $venueLocation; return $this; }
    public function getCapacityLimit(): ?int { return $this->capacityLimit; }
    public function setCapacityLimit(?int $capacityLimit): self { $this->capacityLimit = $capacityLimit; return $this; }
    public function getAvailabilityStatus(): string { return $this->availabilityStatus; }
    public function setAvailabilityStatus(string $availabilityStatus): self { $this->availabilityStatus = $availabilityStatus; return $this; }
    public function getCreatedTimestamp(): \DateTimeInterface { return $this->createdTimestamp; }
    public function getUpdatedTimestamp(): \DateTimeInterface { return $this->updatedTimestamp; }
}
