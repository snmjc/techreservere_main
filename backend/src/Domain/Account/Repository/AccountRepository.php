<?php

namespace App\Domain\Account\Repository;

use App\Domain\Account\Entity\AccountEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountEntity::class);
    }

    // ===== AI GENERATED: findOneByEmailAddress =====
    // Purpose: Find a single account by email address
    // Inputs: emailAddress (string)
    // Returns: AccountEntity|null
    // Flow:
    // 1. Query accounts table by email_address column
    // 2. Return entity or null

    public function findOneByEmailAddress(string $emailAddress): ?AccountEntity
    {
        return $this->findOneBy(['emailAddress' => $emailAddress]);
    }

    // ===== AI GENERATED: findOneByClerkUserId =====
    // Purpose: Find a single account by Clerk user ID
    // Inputs: clerkUserId (string)
    // Returns: AccountEntity|null
    // Flow:
    // 1. Query accounts table by clerk_user_id column
    // 2. Return entity or null

    public function findOneByClerkUserId(string $clerkUserId): ?AccountEntity
    {
        return $this->findOneBy(['clerkUserId' => $clerkUserId]);
    }

    // ===== AI GENERATED: findAllAccounts =====
    // Purpose: Retrieve all account entities
    // Inputs: none
    // Returns: AccountEntity[]
    // Flow:
    // 1. Query all records from accounts table
    // 2. Return array of entities

    /**
     * @return AccountEntity[]
     */
    public function findAllAccounts(): array
    {
        return $this->findAll();
    }

    // ===== AI GENERATED: persistAccount =====
    // Purpose: Persist a new or updated account entity
    // Inputs: accountEntity (AccountEntity)
    // Returns: void
    // Flow:
    // 1. Persist entity via EntityManager
    // 2. Flush changes to database

    public function persistAccount(AccountEntity $accountEntity): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($accountEntity);
        $entityManager->flush();
    }

    // ===== AI GENERATED: removeAccount =====
    // Purpose: Remove an account entity from the database
    // Inputs: accountEntity (AccountEntity)
    // Returns: void
    // Flow:
    // 1. Remove entity via EntityManager
    // 2. Flush changes to database

    public function removeAccount(AccountEntity $accountEntity): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($accountEntity);
        $entityManager->flush();
    }
}
