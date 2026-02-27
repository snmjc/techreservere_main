<?php

namespace App\Domain\Account\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Domain\Account\Repository\AccountRepository::class)]
#[ORM\Table(name: 'accounts')]
#[ORM\HasLifecycleCallbacks]
class AccountEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $accountIdentifier = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $lastName = '';

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $firstName = '';

    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    private string $emailAddress = '';

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $roleDesignation = 'ROLE_BORROWER';

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $contactNumber = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $clerkUserId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLoginTimestamp = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdTimestamp;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedTimestamp;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::INTEGER)]
    private int $failedLoginAttempts = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lockedUntilTimestamp = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $passwordHash = null;

    public function __construct()
    {
        $this->createdTimestamp = new \DateTime();
        $this->updatedTimestamp = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedTimestamp = new \DateTime();
    }

    public function getAccountIdentifier(): ?int
    {
        return $this->accountIdentifier;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(string $emailAddress): self
    {
        $this->emailAddress = $emailAddress;
        return $this;
    }

    public function getRoleDesignation(): string
    {
        return $this->roleDesignation;
    }

    public function setRoleDesignation(string $roleDesignation): self
    {
        $this->roleDesignation = $roleDesignation;
        return $this;
    }

    public function getContactNumber(): ?string
    {
        return $this->contactNumber;
    }

    public function setContactNumber(?string $contactNumber): self
    {
        $this->contactNumber = $contactNumber;
        return $this;
    }

    public function getClerkUserId(): ?string
    {
        return $this->clerkUserId;
    }

    public function setClerkUserId(?string $clerkUserId): self
    {
        $this->clerkUserId = $clerkUserId;
        return $this;
    }

    public function getLastLoginTimestamp(): ?\DateTimeInterface
    {
        return $this->lastLoginTimestamp;
    }

    public function setLastLoginTimestamp(?\DateTimeInterface $lastLoginTimestamp): self
    {
        $this->lastLoginTimestamp = $lastLoginTimestamp;
        return $this;
    }

    public function getCreatedTimestamp(): \DateTimeInterface
    {
        return $this->createdTimestamp;
    }

    public function getUpdatedTimestamp(): \DateTimeInterface
    {
        return $this->updatedTimestamp;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getFailedLoginAttempts(): int
    {
        return $this->failedLoginAttempts;
    }

    public function setFailedLoginAttempts(int $failedLoginAttempts): self
    {
        $this->failedLoginAttempts = $failedLoginAttempts;
        return $this;
    }

    public function getLockedUntilTimestamp(): ?\DateTimeInterface
    {
        return $this->lockedUntilTimestamp;
    }

    public function setLockedUntilTimestamp(?\DateTimeInterface $lockedUntilTimestamp): self
    {
        $this->lockedUntilTimestamp = $lockedUntilTimestamp;
        return $this;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(?string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }
}
