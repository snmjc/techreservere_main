<?php

namespace App\Domain\Account\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;

class ClerkPostgresMigrationService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AccountClerkProvisioningService $accountClerkProvisioningService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function migrate(?string $emailAddress = null, int $batchSize = 50, bool $dryRun = false): array
    {
        $normalizedEmailAddress = $emailAddress !== null ? strtolower(trim($emailAddress)) : null;
        $batchSize = max(1, min($batchSize, 500));

        $summary = [
            'processed' => 0,
            'updated' => 0,
            'matchedExisting' => 0,
            'createdInClerk' => 0,
            'skipped' => 0,
            'errors' => 0,
            'rows' => [],
        ];

        do {
            $accounts = $this->findAccountsToMigrate($normalizedEmailAddress, $batchSize);
            foreach ($accounts as $account) {
                $summary['processed']++;

                try {
                    $result = $this->migrateAccount($account, $dryRun);
                    $summary['rows'][] = $result;

                    switch ((string)($result['result'] ?? '')) {
                        case 'updated_existing':
                            $summary['updated']++;
                            $summary['matchedExisting']++;
                            break;

                        case 'created_and_updated':
                            $summary['updated']++;
                            $summary['createdInClerk']++;
                            break;

                        case 'would_update_existing':
                            $summary['matchedExisting']++;
                            break;

                        case 'would_create_and_update':
                            $summary['createdInClerk']++;
                            break;

                        case 'skipped':
                            $summary['skipped']++;
                            break;
                    }
                } catch (\Throwable $exception) {
                    $summary['errors']++;
                    $summary['rows'][] = [
                        'accountIdentifier' => (int)$account['account_identifier'],
                        'emailAddress' => (string)$account['email_address'],
                        'result' => 'error',
                        'details' => $exception->getMessage(),
                    ];

                    $this->logger->error('Failed migrating PostgreSQL account to Clerk.', [
                        'accountIdentifier' => (int)$account['account_identifier'],
                        'emailAddress' => (string)$account['email_address'],
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
            if ($dryRun || $normalizedEmailAddress !== null) {
                break;
            }
        } while (count($accounts) === $batchSize);

        return $summary;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function findAccountsToMigrate(?string $emailAddress, int $batchSize): array
    {
        $sql = "SELECT account_identifier, email_address, first_name, last_name, username, role_designation,
                       id_number, department, password_hash, status, is_active, is_approved, created_timestamp
                FROM accounts
                WHERE (clerk_user_id IS NULL OR clerk_user_id = '')";
        $parameters = [];
        $types = [];

        if ($emailAddress !== null && $emailAddress !== '') {
            $sql .= ' AND LOWER(email_address) = :emailAddress';
            $parameters['emailAddress'] = $emailAddress;
            $types['emailAddress'] = ParameterType::STRING;
        }

        $sql .= ' ORDER BY account_identifier ASC LIMIT :batchSize';
        $parameters['batchSize'] = $batchSize;
        $types['batchSize'] = ParameterType::INTEGER;

        return $this->connection->fetchAllAssociative($sql, $parameters, $types);
    }

    /**
     * @param array<string, mixed> $account
     * @return array<string, mixed>
     */
    private function migrateAccount(array $account, bool $dryRun): array
    {
        $preparedPayload = $this->prepareMigrationPayload($account);
        $provisioningResult = $this->accountClerkProvisioningService->ensureMigratedUser($preparedPayload);

        if ($dryRun) {
            return [
                'accountIdentifier' => (int)$account['account_identifier'],
                'emailAddress' => (string)$account['email_address'],
                'clerkUserId' => $provisioningResult['clerkUserId'],
                'result' => $provisioningResult['created']
                    ? 'would_create_and_update'
                    : 'would_update_existing',
                'details' => $provisioningResult['created']
                    ? 'Would create Clerk user and persist returned user id.'
                    : 'Would link existing Clerk user by email and persist returned user id.',
            ];
        }

        $updatedRows = $this->connection->executeStatement(
            'UPDATE accounts
             SET clerk_user_id = :clerkUserId,
                 updated_timestamp = :updatedTimestamp
             WHERE account_identifier = :accountIdentifier
               AND (clerk_user_id IS NULL OR clerk_user_id = \'\')',
            [
                'clerkUserId' => $provisioningResult['clerkUserId'],
                'updatedTimestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'accountIdentifier' => (int)$account['account_identifier'],
            ],
            [
                'clerkUserId' => ParameterType::STRING,
                'updatedTimestamp' => ParameterType::STRING,
                'accountIdentifier' => ParameterType::INTEGER,
            ]
        );

        if ($updatedRows === 0) {
            return [
                'accountIdentifier' => (int)$account['account_identifier'],
                'emailAddress' => (string)$account['email_address'],
                'clerkUserId' => $provisioningResult['clerkUserId'],
                'result' => 'skipped',
                'details' => 'Skipped because another process linked this account first.',
            ];
        }

        return [
            'accountIdentifier' => (int)$account['account_identifier'],
            'emailAddress' => (string)$account['email_address'],
            'clerkUserId' => $provisioningResult['clerkUserId'],
            'result' => $provisioningResult['created'] ? 'created_and_updated' : 'updated_existing',
            'details' => $provisioningResult['created']
                ? 'Created Clerk user and stored returned user id.'
                : 'Matched existing Clerk user by email and stored returned user id.',
        ];
    }

    /**
     * @param array<string, mixed> $account
     * @return array<string, mixed>
     */
    private function prepareMigrationPayload(array $account): array
    {
        return [
            'accountIdentifier' => (int)$account['account_identifier'],
            'emailAddress' => strtolower(trim((string)$account['email_address'])),
            'firstName' => trim((string)($account['first_name'] ?? '')),
            'lastName' => trim((string)($account['last_name'] ?? '')),
            'username' => trim((string)($account['username'] ?? '')),
            'roleDesignation' => trim((string)($account['role_designation'] ?? 'ROLE_BORROWER')),
            'idNumber' => trim((string)($account['id_number'] ?? '')),
            'department' => trim((string)($account['department'] ?? '')),
            'passwordHash' => $account['password_hash'] ? (string)$account['password_hash'] : null,
            'status' => trim((string)($account['status'] ?? 'pending')),
            'isActive' => $this->toDatabaseBoolean($account['is_active'] ?? true),
            'isApproved' => $this->toDatabaseBoolean($account['is_approved'] ?? false),
            'createdTimestamp' => isset($account['created_timestamp'])
                ? (string)$account['created_timestamp']
                : (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
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
