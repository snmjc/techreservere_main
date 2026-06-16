<?php

namespace App\Command;

use App\Domain\Venue\Service\VenueManagementService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:venue:create-smoke-test',
    description: 'Create and delete a test venue against the real database.',
)]
class VenueCreateSmokeTestCommand extends Command
{
    public function __construct(
        private readonly VenueManagementService $venueManagementService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $venueName = 'Smoke Test Venue ' . date('YmdHis');
        $createdVenueIdentifier = null;

        try {
            $createdVenue = $this->venueManagementService->createVenue(
                $venueName,
                'Smoke Test Floor',
                '18th Floor',
                10,
                date('Y-m-d'),
                'Active',
                'Available',
                'Temporary smoke test venue.',
                null
            );
            $createdVenueIdentifier = $createdVenue->venueIdentifier;

            $io->success(sprintf(
                'Venue create succeeded. ID=%d Name=%s',
                $createdVenueIdentifier,
                $createdVenue->venueName
            ));

            $this->venueManagementService->deleteVenue($createdVenueIdentifier);
            $io->success('Venue delete cleanup succeeded.');

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            if ($createdVenueIdentifier !== null) {
                try {
                    $this->venueManagementService->deleteVenue($createdVenueIdentifier);
                } catch (\Throwable) {
                }
            }

            $io->error('Venue smoke test failed: ' . $exception->getMessage());
            return Command::FAILURE;
        }
    }
}
