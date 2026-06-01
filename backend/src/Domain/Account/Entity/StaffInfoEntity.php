<?php

namespace App\Domain\Account\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'staff_info')]
#[ORM\HasLifecycleCallbacks]
class StaffInfoEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, unique: true)]
    private ?int $accountIdentifier = null;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    private string $employeeIdNumber = '';

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $firstName = '';

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $lastName = '';

    #[ORM\Column(type: Types::STRING, length: 20, unique: true)]
    private string $phoneNumber = '';

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $role = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $imageUrl = null;

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
    public function onPreUpdate(): void
    {
        $this->updatedTimestamp = new \DateTime();
    }
}
