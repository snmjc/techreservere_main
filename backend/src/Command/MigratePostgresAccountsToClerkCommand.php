<?php

namespace App\Command;

use App\Domain\Account\Service\ClerkPostgresMigrationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-postgres-accounts-to-clerk',
    description: 'Creates or links Clerk users for PostgreSQL accounts whose clerk_user_id is empty.',
)]
class MigratePostgresAccountsToClerkCommand extends Command
{
    public function __construct(private readonly ClerkPostgresMigrationService $clerkPostgresMigrationService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Only process a single email address.')
            ->addOption('batch-size', null, InputOption::VALUE_OPTIONAL, 'How many accounts to process per batch.', 50)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Resolve Clerk matches without updating PostgreSQL.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('TechReserve PostgreSQL to Clerk Migration');

        $emailAddress = $input->getOption('email');
        $batchSize = (int)$input->getOption('batch-size');
        $dryRun = (bool)$input->getOption('dry-run');

        $summary = $this->clerkPostgresMigrationService->migrate(
            is_string($emailAddress) ? $emailAddress : null,
            $batchSize,
            $dryRun
        );

        $io->table(
            ['Account ID', 'Email', 'Result', 'Details', 'Clerk User ID'],
            array_map(
                static fn (array $row): array => [
                    $row['accountIdentifier'] ?? '',
                    $row['emailAddress'] ?? '',
                    $row['result'] ?? '',
                    $row['details'] ?? '',
                    $row['clerkUserId'] ?? '',
                ],
                $summary['rows']
            )
        );

        $io->section('Summary');
        $io->listing([
            sprintf('Processed: %d', (int)$summary['processed']),
            sprintf('Updated locally: %d', (int)$summary['updated']),
            sprintf('Matched existing Clerk users: %d', (int)$summary['matchedExisting']),
            sprintf('Created Clerk users: %d', (int)$summary['createdInClerk']),
            sprintf('Skipped: %d', (int)$summary['skipped']),
            sprintf('Errors: %d', (int)$summary['errors']),
        ]);

        if ((int)$summary['errors'] > 0) {
            $io->warning('Some accounts failed to migrate. Review the table output and logs before retrying.');
            return Command::FAILURE;
        }

        $io->success($dryRun ? 'Dry run completed.' : 'PostgreSQL to Clerk migration completed.');
        return Command::SUCCESS;
    }
}
