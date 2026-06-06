<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create an admin account in Clerk and PostgreSQL.',
)]
class CreateAdminCommand extends Command
{
    private const DEFAULT_ALLOWED_ADMIN_EMAIL_DOMAINS = [
        'feutech.edu.ph',
        'fit.edu.ph',
    ];

    private Connection $connection;
    private HttpClientInterface $httpClient;

    public function __construct(Connection $connection, HttpClientInterface $httpClient)
    {
        parent::__construct();
        $this->connection = $connection;
        $this->httpClient = $httpClient;
    }

    protected function configure(): void
    {
        $this
            ->addOption('email',      null, InputOption::VALUE_REQUIRED, 'Admin email address')
            ->addOption('firstName',  null, InputOption::VALUE_REQUIRED, 'Admin first name')
            ->addOption('lastName',   null, InputOption::VALUE_REQUIRED, 'Admin last name')
            ->addOption('password',   null, InputOption::VALUE_REQUIRED, 'Admin password (min 8 chars)')
            ->addOption('department', null, InputOption::VALUE_OPTIONAL, 'Department', 'IT Administration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('TechReserve — Create Admin Account');

        $email      = $input->getOption('email');
        $firstName  = $input->getOption('firstName');
        $lastName   = $input->getOption('lastName');
        $password   = $input->getOption('password');
        $department = $input->getOption('department');

        // Prompt for missing values
        if (!$email)     $email     = $io->ask('Admin email address');
        if (!$firstName) $firstName = $io->ask('First name');
        if (!$lastName)  $lastName  = $io->ask('Last name');
        if (!$password)  $password  = $io->askHidden('Password (min 8 chars)');

        if (empty($email) || empty($firstName) || empty($lastName) || empty($password)) {
            $io->error('All fields (email, firstName, lastName, password) are required.');
            return Command::FAILURE;
        }

        if (!$this->isAllowedAdminEmail($email)) {
            $io->error('Admin account must use @feutech.edu.ph or the temporary @fit.edu.ph domain only.');
            return Command::FAILURE;
        }

        $clerkSecretKey = $_ENV['CLERK_SECRET_KEY'] ?? '';
        $clerkApiBase   = $_ENV['CLERK_API_BASE_URL'] ?? 'https://api.clerk.com';

        if (empty($clerkSecretKey)) {
            $io->error('CLERK_SECRET_KEY is not set in environment variables.');
            return Command::FAILURE;
        }

        // Check duplicate email via raw SQL
        $existing = $this->connection->fetchOne(
            'SELECT account_identifier FROM accounts WHERE email_address = ?',
            [$email]
        );
        if ($existing !== false) {
            $io->error("An account with email '{$email}' already exists in PostgreSQL.");
            return Command::FAILURE;
        }

        // Step 1: Create Clerk user
        $io->section('Step 1: Creating Clerk user...');
        try {
            $clerkResponse = $this->httpClient->request('POST', $clerkApiBase . '/v1/users', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $clerkSecretKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'email_address'   => [$email],
                    'first_name'      => $firstName,
                    'last_name'       => $lastName,
                    'password'        => $password,
                    'public_metadata' => ['role' => 'ROLE_ADMIN'],
                ],
            ]);

            $statusCode = $clerkResponse->getStatusCode();
            $clerkData  = $clerkResponse->toArray(false);

            if ($statusCode !== 200 && $statusCode !== 201) {
                $errors = $clerkData['errors'] ?? [['message' => 'Unknown Clerk error']];
                $io->error('Clerk user creation failed: ' . ($errors[0]['long_message'] ?? $errors[0]['message']));
                return Command::FAILURE;
            }

            $clerkUserId = $clerkData['id'];
            $io->success("Clerk user created — ID: {$clerkUserId}");
        } catch (\Throwable $e) {
            $io->error('Failed to call Clerk API: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Step 2: Insert admin into PostgreSQL via raw DBAL SQL
        $io->section('Step 2: Saving admin to PostgreSQL...');
        try {
            $now = (new \DateTime())->format('Y-m-d H:i:s');
            $this->connection->executeStatement(
                'INSERT INTO accounts
                    (last_name, first_name, email_address, role_designation, department,
                     clerk_user_id, status, is_approved, is_active,
                     failed_login_attempts, created_timestamp, updated_timestamp)
                 VALUES
                    (:lastName, :firstName, :emailAddress, :roleDesignation, :department,
                     :clerkUserId, :status, :isApproved, :isActive,
                     :failedLoginAttempts, :createdTimestamp, :updatedTimestamp)',
                [
                    'lastName' => $lastName,
                    'firstName' => $firstName,
                    'emailAddress' => $email,
                    'roleDesignation' => 'ROLE_ADMIN',
                    'department' => $department,
                    'clerkUserId' => $clerkUserId,
                    'status' => 'approved',
                    'isApproved' => true,
                    'isActive' => true,
                    'failedLoginAttempts' => 0,
                    'createdTimestamp' => $now,
                    'updatedTimestamp' => $now,
                ],
                [
                    'lastName' => ParameterType::STRING,
                    'firstName' => ParameterType::STRING,
                    'emailAddress' => ParameterType::STRING,
                    'roleDesignation' => ParameterType::STRING,
                    'department' => ParameterType::STRING,
                    'clerkUserId' => ParameterType::STRING,
                    'status' => ParameterType::STRING,
                    'isApproved' => ParameterType::BOOLEAN,
                    'isActive' => ParameterType::BOOLEAN,
                    'failedLoginAttempts' => ParameterType::INTEGER,
                    'createdTimestamp' => ParameterType::STRING,
                    'updatedTimestamp' => ParameterType::STRING,
                ]
            );

            $newId = $this->connection->lastInsertId();
            $io->success("Admin account saved to PostgreSQL — ID: {$newId}");
        } catch (\Throwable $e) {
            $io->error('Failed to save to PostgreSQL: ' . $e->getMessage());
            $io->warning("Clerk user '{$clerkUserId}' was created but PostgreSQL insert failed.");
            return Command::FAILURE;
        }

        $io->section('Admin account created successfully!');
        $io->table(
            ['Field', 'Value'],
            [
                ['Clerk User ID', $clerkUserId],
                ['Name',         "{$firstName} {$lastName}"],
                ['Email',        $email],
                ['Role',         'ROLE_ADMIN'],
                ['Department',   $department],
                ['Status',       'approved'],
            ]
        );

        $io->note('You can now log in at /clerk-login with email: ' . $email);

        return Command::SUCCESS;
    }

    private function isAllowedAdminEmail(string $emailAddress): bool
    {
        $normalizedEmailAddress = strtolower(trim($emailAddress));
        if (!filter_var($normalizedEmailAddress, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $domain = substr(strrchr($normalizedEmailAddress, '@') ?: '', 1);
        if ($domain === '') {
            return false;
        }

        return in_array($domain, self::DEFAULT_ALLOWED_ADMIN_EMAIL_DOMAINS, true);
    }
}
