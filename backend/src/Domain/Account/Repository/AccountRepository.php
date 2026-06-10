<?php

namespace App\Domain\Account\Repository;

use App\Domain\Account\Entity\AccountEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\ParameterType;
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
        return $this->createQueryBuilder('account')
            ->where('LOWER(account.emailAddress) = :emailAddress')
            ->setParameter('emailAddress', strtolower(trim($emailAddress)))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByEmailAddressOrUsername(string $identifier): ?AccountEntity
    {
        $normalizedIdentifier = strtolower(trim($identifier));

        return $this->createQueryBuilder('account')
            ->where('LOWER(account.emailAddress) = :identifier')
            ->orWhere('LOWER(account.username) = :identifier')
            ->setParameter('identifier', $normalizedIdentifier)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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

    public function upsertClerkAccount(array $attributes): array
    {
        return $this->connection()->transactional(function (Connection $connection) use ($attributes): array {
            $existing = $this->findWebhookTargetAccountForUpdate(
                $connection,
                (string)$attributes['clerkUserId'],
                (string)$attributes['emailAddress']
            );

            if ($existing !== null) {
                $connection->executeStatement(
                    "UPDATE accounts
                     SET clerk_user_id = :clerkUserId,
                         email_address = :emailAddress,
                         first_name = :firstName,
                         last_name = :lastName,
                         username = COALESCE(NULLIF(username, ''), :username),
                         is_active = :isActive,
                         is_approved = :isApproved,
                         status = :status,
                         updated_timestamp = :updatedTimestamp
                     WHERE account_identifier = :accountIdentifier",
                    [
                        'clerkUserId' => (string)$attributes['clerkUserId'],
                        'emailAddress' => (string)$attributes['emailAddress'],
                        'firstName' => $this->preferIncomingOrExisting($attributes['firstName'] ?? '', $existing['first_name'] ?? ''),
                        'lastName' => $this->preferIncomingOrExisting($attributes['lastName'] ?? '', $existing['last_name'] ?? ''),
                        'username' => (string)$attributes['username'],
                        'isActive' => (bool)($attributes['isActive'] ?? true),
                        'isApproved' => (bool)($attributes['isApproved'] ?? true),
                        'status' => (string)$attributes['status'],
                        'updatedTimestamp' => $this->now(),
                        'accountIdentifier' => (int)$existing['account_identifier'],
                    ],
                    [
                        'clerkUserId' => ParameterType::STRING,
                        'emailAddress' => ParameterType::STRING,
                        'firstName' => ParameterType::STRING,
                        'lastName' => ParameterType::STRING,
                        'username' => ParameterType::STRING,
                        'isActive' => ParameterType::BOOLEAN,
                        'isApproved' => ParameterType::BOOLEAN,
                        'status' => ParameterType::STRING,
                        'updatedTimestamp' => ParameterType::STRING,
                        'accountIdentifier' => ParameterType::INTEGER,
                    ]
                );

                return $this->fetchWebhookAccountById($connection, (int)$existing['account_identifier']) + [
                    'created' => false,
                    'previousStatus' => (string)($existing['status'] ?? ''),
                ];
            }

            try {
                $connection->executeStatement(
                    'INSERT INTO accounts
                        (email_address, first_name, last_name, username, role_designation, clerk_user_id,
                         is_active, is_approved, status, failed_login_attempts, created_timestamp, updated_timestamp)
                     VALUES
                        (:emailAddress, :firstName, :lastName, :username, :roleDesignation, :clerkUserId,
                         :isActive, :isApproved, :status, :failedLoginAttempts, :createdTimestamp, :updatedTimestamp)',
                    [
                        'emailAddress' => (string)$attributes['emailAddress'],
                        'firstName' => $this->fallbackName((string)($attributes['firstName'] ?? '')),
                        'lastName' => $this->fallbackName((string)($attributes['lastName'] ?? '')),
                        'username' => (string)$attributes['username'],
                        'roleDesignation' => (string)($attributes['roleDesignation'] ?? 'ROLE_BORROWER'),
                        'clerkUserId' => (string)$attributes['clerkUserId'],
                        'isActive' => (bool)($attributes['isActive'] ?? true),
                        'isApproved' => (bool)($attributes['isApproved'] ?? true),
                        'status' => (string)$attributes['status'],
                        'failedLoginAttempts' => 0,
                        'createdTimestamp' => $this->now(),
                        'updatedTimestamp' => $this->now(),
                    ],
                    [
                        'emailAddress' => ParameterType::STRING,
                        'firstName' => ParameterType::STRING,
                        'lastName' => ParameterType::STRING,
                        'username' => ParameterType::STRING,
                        'roleDesignation' => ParameterType::STRING,
                        'clerkUserId' => ParameterType::STRING,
                        'isActive' => ParameterType::BOOLEAN,
                        'isApproved' => ParameterType::BOOLEAN,
                        'status' => ParameterType::STRING,
                        'failedLoginAttempts' => ParameterType::INTEGER,
                        'createdTimestamp' => ParameterType::STRING,
                        'updatedTimestamp' => ParameterType::STRING,
                    ]
                );
            } catch (UniqueConstraintViolationException) {
                $existingAfterConflict = $this->findWebhookTargetAccountForUpdate(
                    $connection,
                    (string)$attributes['clerkUserId'],
                    (string)$attributes['emailAddress']
                );

                if ($existingAfterConflict === null) {
                    throw new \RuntimeException('Clerk account upsert hit a unique constraint, but no matching account could be reloaded.');
                }

                $connection->executeStatement(
                    "UPDATE accounts
                     SET clerk_user_id = :clerkUserId,
                         first_name = :firstName,
                         last_name = :lastName,
                         email_address = :emailAddress,
                         username = COALESCE(NULLIF(username, ''), :username),
                         is_active = :isActive,
                         is_approved = :isApproved,
                         status = :status,
                         updated_timestamp = :updatedTimestamp
                     WHERE account_identifier = :accountIdentifier",
                    [
                        'clerkUserId' => (string)$attributes['clerkUserId'],
                        'firstName' => $this->preferIncomingOrExisting($attributes['firstName'] ?? '', $existingAfterConflict['first_name'] ?? ''),
                        'lastName' => $this->preferIncomingOrExisting($attributes['lastName'] ?? '', $existingAfterConflict['last_name'] ?? ''),
                        'emailAddress' => (string)$attributes['emailAddress'],
                        'username' => (string)$attributes['username'],
                        'isActive' => (bool)($attributes['isActive'] ?? true),
                        'isApproved' => (bool)($attributes['isApproved'] ?? true),
                        'status' => (string)$attributes['status'],
                        'updatedTimestamp' => $this->now(),
                        'accountIdentifier' => (int)$existingAfterConflict['account_identifier'],
                    ],
                    [
                        'clerkUserId' => ParameterType::STRING,
                        'firstName' => ParameterType::STRING,
                        'lastName' => ParameterType::STRING,
                        'emailAddress' => ParameterType::STRING,
                        'username' => ParameterType::STRING,
                        'isActive' => ParameterType::BOOLEAN,
                        'isApproved' => ParameterType::BOOLEAN,
                        'status' => ParameterType::STRING,
                        'updatedTimestamp' => ParameterType::STRING,
                        'accountIdentifier' => ParameterType::INTEGER,
                    ]
                );

                return $this->fetchWebhookAccountById($connection, (int)$existingAfterConflict['account_identifier']) + [
                    'created' => false,
                    'previousStatus' => (string)($existingAfterConflict['status'] ?? ''),
                ];
            }

            $inserted = $this->findWebhookTargetAccountForUpdate(
                $connection,
                (string)$attributes['clerkUserId'],
                (string)$attributes['emailAddress']
            );

            if ($inserted === null) {
                throw new \RuntimeException('Unable to load the inserted Clerk account.');
            }

            return $this->fetchWebhookAccountById($connection, (int)$inserted['account_identifier']) + [
                'created' => true,
                'previousStatus' => '',
            ];
        });
    }

    public function updateClerkAccountFromWebhook(array $attributes): ?array
    {
        return $this->connection()->transactional(function (Connection $connection) use ($attributes): ?array {
            $clerkUserId = trim((string)($attributes['clerkUserId'] ?? ''));
            if ($clerkUserId === '') {
                return null;
            }

            $existing = $this->findWebhookTargetAccountForUpdate(
                $connection,
                $clerkUserId,
                ''
            );

            if ($existing === null) {
                return null;
            }

            $connection->executeStatement(
                "UPDATE accounts
                 SET clerk_user_id = COALESCE(NULLIF(clerk_user_id, ''), :clerkUserId),
                     email_address = :emailAddress,
                     first_name = :firstName,
                     last_name = :lastName,
                     updated_timestamp = :updatedTimestamp
                 WHERE account_identifier = :accountIdentifier",
                [
                    'clerkUserId' => (string)$attributes['clerkUserId'],
                    'emailAddress' => (string)$attributes['emailAddress'],
                    'firstName' => $this->preferIncomingOrExisting($attributes['firstName'] ?? '', $existing['first_name'] ?? ''),
                    'lastName' => $this->preferIncomingOrExisting($attributes['lastName'] ?? '', $existing['last_name'] ?? ''),
                    'updatedTimestamp' => $this->now(),
                    'accountIdentifier' => (int)$existing['account_identifier'],
                ],
                [
                    'clerkUserId' => ParameterType::STRING,
                    'emailAddress' => ParameterType::STRING,
                    'firstName' => ParameterType::STRING,
                    'lastName' => ParameterType::STRING,
                    'updatedTimestamp' => ParameterType::STRING,
                    'accountIdentifier' => ParameterType::INTEGER,
                ]
            );

            return $this->fetchWebhookAccountById($connection, (int)$existing['account_identifier']) + [
                'created' => false,
                'previousStatus' => (string)($existing['status'] ?? ''),
            ];
        });
    }

    public function softDeleteClerkAccount(string $clerkUserId, ?string $emailAddress = null): ?array
    {
        return $this->connection()->transactional(function (Connection $connection) use ($clerkUserId, $emailAddress): ?array {
            $existing = $this->findWebhookTargetAccountForUpdate(
                $connection,
                $clerkUserId,
                $emailAddress ?? ''
            );

            if ($existing === null) {
                return null;
            }

            $connection->executeStatement(
                "UPDATE accounts
                 SET is_active = FALSE,
                     status = :status,
                     updated_timestamp = :updatedTimestamp
                 WHERE account_identifier = :accountIdentifier",
                [
                    'status' => 'inactive',
                    'updatedTimestamp' => $this->now(),
                    'accountIdentifier' => (int)$existing['account_identifier'],
                ],
                [
                    'status' => ParameterType::STRING,
                    'updatedTimestamp' => ParameterType::STRING,
                    'accountIdentifier' => ParameterType::INTEGER,
                ]
            );

            return $this->fetchWebhookAccountById($connection, (int)$existing['account_identifier']) + [
                'created' => false,
                'previousStatus' => (string)($existing['status'] ?? ''),
            ];
        });
    }

    private function connection(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function findWebhookTargetAccountForUpdate(Connection $connection, string $clerkUserId, string $emailAddress): ?array
    {
        $normalizedEmail = strtolower(trim($emailAddress));
        $normalizedClerkUserId = trim($clerkUserId);

        if ($normalizedClerkUserId === '' && $normalizedEmail === '') {
            return null;
        }

        $sql = "SELECT account_identifier, email_address, first_name, last_name, role_designation, status, clerk_user_id
                FROM accounts
                WHERE (:clerkUserId <> '' AND clerk_user_id = :clerkUserId)
                   OR (:emailAddress <> '' AND LOWER(email_address) = :emailAddress)
                ORDER BY CASE WHEN clerk_user_id = :clerkUserId THEN 0 ELSE 1 END, account_identifier ASC
                LIMIT 1
                FOR UPDATE";

        $row = $connection->fetchAssociative(
            $sql,
            [
                'clerkUserId' => $normalizedClerkUserId,
                'emailAddress' => $normalizedEmail,
            ],
            [
                'clerkUserId' => ParameterType::STRING,
                'emailAddress' => ParameterType::STRING,
            ]
        );

        return $row ?: null;
    }

    private function fetchWebhookAccountById(Connection $connection, int $accountIdentifier): array
    {
        $row = $connection->fetchAssociative(
            'SELECT account_identifier, email_address, first_name, last_name, role_designation, clerk_user_id, status, is_active, is_approved
             FROM accounts
             WHERE account_identifier = :accountIdentifier',
            ['accountIdentifier' => $accountIdentifier],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        if (!$row) {
            throw new \RuntimeException('Unable to fetch synchronized Clerk account.');
        }

        return [
            'accountIdentifier' => (int)$row['account_identifier'],
            'emailAddress' => (string)$row['email_address'],
            'firstName' => (string)($row['first_name'] ?? ''),
            'lastName' => (string)($row['last_name'] ?? ''),
            'roleDesignation' => (string)$row['role_designation'],
            'clerkUserId' => $row['clerk_user_id'] ? (string)$row['clerk_user_id'] : null,
            'status' => (string)$row['status'],
            'isActive' => $this->toDatabaseBoolean($row['is_active'] ?? false),
            'isApproved' => $this->toDatabaseBoolean($row['is_approved'] ?? false),
        ];
    }

    private function preferIncomingOrExisting(mixed $incoming, mixed $existing): string
    {
        $incomingText = trim((string)$incoming);
        return $incomingText !== '' ? $incomingText : trim((string)$existing);
    }

    private function fallbackName(string $value): string
    {
        $trimmed = trim($value);
        return $trimmed !== '' ? $trimmed : 'Clerk';
    }

    private function now(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    private function toDatabaseBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        return in_array(strtolower(trim((string)$value)), ['1', 't', 'true', 'yes'], true);
    }
}
