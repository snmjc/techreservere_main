<?php

namespace App\Command;

use App\Domain\Account\Service\AccountClerkProvisioningService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:backfill-clerk-user-ids',
    description: 'Fill missing clerk_user_id values for approved accounts by matching Clerk users by email.',
)]
class BackfillClerkUserIdsCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AccountClerkProvisioningService $accountClerkProvisioningService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Only process a single email address.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be updated without writing to the database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('TechReserve Clerk User ID Backfill');

        $emailFilter = strtolower(trim((string)$input->getOption('email')));
        $dryRun = (bool)$input->getOption('dry-run');

        $accounts = $this->findAccountsToProcess($emailFilter);
        if ($accounts === []) {
            $io->success('No approved accounts with empty clerk_user_id were found for this scope.');
            return Command::SUCCESS;
        }

        $io->text(sprintf('Found %d approved account(s) with empty clerk_user_id.', count($accounts)));
        if ($dryRun) {
            $io->note('Dry run enabled. No database changes will be written.');
        }

        $updatedCount = 0;
        $notFoundCount = 0;
        $errorCount = 0;
        $rows = [];

        foreach ($accounts as $account) {
            $emailAddress = strtolower(trim((string)$account['email_address']));
            $accountIdentifier = (int)$account['account_identifier'];

            try {
                $clerkUserId = $this->accountClerkProvisioningService->findUserIdByEmail($emailAddress);
            } catch (\Throwable $exception) {
                $errorCount++;
                $rows[] = [$accountIdentifier, $emailAddress, 'lookup_error', $exception->getMessage()];
                continue;
            }

            if ($clerkUserId === null) {
                $notFoundCount++;
                $rows[] = [$accountIdentifier, $emailAddress, 'not_found', 'No Clerk user found for this email.'];
                continue;
            }

            if (!$dryRun) {
                $this->connection->executeStatement(
                    'UPDATE accounts
                     SET clerk_user_id = :clerkUserId,
                         updated_timestamp = :updatedTimestamp
                     WHERE account_identifier = :accountIdentifier
                       AND (clerk_user_id IS NULL OR clerk_user_id = \'\')',
                    [
                        'clerkUserId' => $clerkUserId,
                        'updatedTimestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                        'accountIdentifier' => $accountIdentifier,
                    ],
                    [
                        'clerkUserId' => ParameterType::STRING,
                        'updatedTimestamp' => ParameterType::STRING,
                        'accountIdentifier' => ParameterType::INTEGER,
                    ]
                );
            }

            $updatedCount++;
            $rows[] = [$accountIdentifier, $emailAddress, $dryRun ? 'would_update' : 'updated', $clerkUserId];
        }

        $io->table(
            ['Account ID', 'Email', 'Result', 'Details'],
            $rows
        );

        $io->section('Summary');
        $io->listing([
            sprintf('Updated: %d', $updatedCount),
            sprintf('No Clerk match: %d', $notFoundCount),
            sprintf('Lookup errors: %d', $errorCount),
        ]);

        if ($errorCount > 0) {
            $io->warning('Some Clerk lookups failed. Review the table above and retry after fixing the reported issue.');
            return Command::FAILURE;
        }

        $io->success($dryRun ? 'Dry run completed.' : 'Clerk user ID backfill completed.');
        return Command::SUCCESS;
    }

    /**
     * @return list<array{account_identifier:mixed,email_address:mixed}>
     */
    private function findAccountsToProcess(string $emailFilter): array
    {
        $sql = "SELECT account_identifier, email_address
                FROM accounts
                WHERE COALESCE(is_approved, FALSE) = TRUE
                  AND LOWER(COALESCE(status, '')) = 'approved'
                  AND COALESCE(is_active, TRUE) = TRUE
                  AND (clerk_user_id IS NULL OR clerk_user_id = '')";
        $parameters = [];
        $types = [];

        if ($emailFilter !== '') {
            $sql .= ' AND LOWER(email_address) = :emailAddress';
            $parameters['emailAddress'] = $emailFilter;
            $types['emailAddress'] = ParameterType::STRING;
        }

        $sql .= ' ORDER BY email_address ASC';

        /** @var list<array{account_identifier:mixed,email_address:mixed}> $accounts */
        $accounts = $this->connection->fetchAllAssociative($sql, $parameters, $types);
        return $accounts;
    }
}
