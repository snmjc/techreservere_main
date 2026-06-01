<?php

namespace App\Domain\Authentication\Service;

use App\Domain\Account\Repository\AccountRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class AuthenticationRegistrationService
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly Connection $connection
    ) {
    }

    public function register(string $firstName, string $lastName, string $emailAddress, string $passwordText): array
    {
        if ($firstName === '' || $lastName === '' || $emailAddress === '' || $passwordText === '') {
            return $this->error('ValidationError', 'First name, last name, email address, and password are required.', 400);
        }

        if (strlen($passwordText) < 8) {
            return $this->error('ValidationError', 'Password must be at least 8 characters long.', 400);
        }

        if ($this->accountRepository->findOneByEmailAddress($emailAddress) !== null) {
            return $this->error('DuplicateAccount', 'An account with this email address already exists.', 409);
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->connection->executeStatement(
            'INSERT INTO accounts
                (last_name, first_name, email_address, password_hash, role_designation,
                 status, is_approved, is_active, failed_login_attempts, created_timestamp, updated_timestamp)
             VALUES
                (:lastName, :firstName, :emailAddress, :passwordHash, :roleDesignation,
                 :status, :isApproved, :isActive, :failedLoginAttempts, :createdTimestamp, :updatedTimestamp)',
            [
                'lastName' => $lastName,
                'firstName' => $firstName,
                'emailAddress' => $emailAddress,
                'passwordHash' => password_hash($passwordText, PASSWORD_BCRYPT, ['cost' => 4]),
                'roleDesignation' => 'ROLE_BORROWER',
                'status' => 'pending',
                'isApproved' => false,
                'isActive' => true,
                'failedLoginAttempts' => 0,
                'createdTimestamp' => $now,
                'updatedTimestamp' => $now,
            ],
            [
                'lastName' => ParameterType::STRING,
                'firstName' => ParameterType::STRING,
                'emailAddress' => ParameterType::STRING,
                'passwordHash' => ParameterType::STRING,
                'roleDesignation' => ParameterType::STRING,
                'status' => ParameterType::STRING,
                'isApproved' => ParameterType::BOOLEAN,
                'isActive' => ParameterType::BOOLEAN,
                'failedLoginAttempts' => ParameterType::INTEGER,
                'createdTimestamp' => ParameterType::STRING,
                'updatedTimestamp' => ParameterType::STRING,
            ]
        );

        return [
            'success' => true,
            'status' => 201,
            'data' => [
                'message' => 'Account registered successfully.',
                'account' => [
                    'accountIdentifier' => (int)$this->connection->lastInsertId(),
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'emailAddress' => $emailAddress,
                    'roleDesignation' => 'ROLE_BORROWER',
                ],
            ],
        ];
    }

    private function error(string $code, string $message, int $status): array
    {
        return [
            'success' => false,
            'errorCode' => $code,
            'message' => $message,
            'status' => $status,
        ];
    }
}
