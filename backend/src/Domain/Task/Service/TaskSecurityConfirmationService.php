<?php

namespace App\Domain\Task\Service;

use App\Domain\Task\DTO\TaskSecurityConfirmationDTO;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class TaskSecurityConfirmationService
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function validateResponsibleAdminCredentials(TaskSecurityConfirmationDTO $confirmation): ?array
    {
        $normalizedConfirmedAdminEmail = $this->normalizeEmailForConfirmation($confirmation->confirmedAdminEmail);

        if ($normalizedConfirmedAdminEmail === '' || $confirmation->authenticatedAdminId <= 0) {
            return $this->error(
                sprintf('Please type the responsible admin email before %s this task assignment.', $confirmation->actionName)
            );
        }

        if (trim($confirmation->confirmedAdminPassword) === '') {
            return $this->error(
                sprintf('Please type the responsible admin password before %s this task assignment.', $confirmation->actionName)
            );
        }

        return $this->validateConfirmedAdmin($confirmation, $normalizedConfirmedAdminEmail);
    }

    private function validateConfirmedAdmin(TaskSecurityConfirmationDTO $confirmation, string $normalizedConfirmedAdminEmail): ?array
    {
        $confirmedAdmin = $this->connection->fetchAssociative(
            "SELECT email_address, password_hash
             FROM accounts
             WHERE account_identifier = :accountIdentifier
               AND role_designation IN ('ROLE_ADMIN', 'ADMIN')
               AND COALESCE(is_active, TRUE) = TRUE
             LIMIT 1",
            ['accountIdentifier' => $confirmation->authenticatedAdminId],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        if (!$confirmedAdmin || $normalizedConfirmedAdminEmail !== $this->confirmedAdminEmail($confirmedAdmin)) {
            return $this->error(
                sprintf('Please type your exact admin email before %s this task assignment.', $confirmation->actionName)
            );
        }

        if (!$this->passwordMatches($confirmedAdmin, $confirmation->confirmedAdminPassword)) {
            return $this->error(
                sprintf('Please type your exact admin password before %s this task assignment.', $confirmation->actionName)
            );
        }

        return null;
    }

    private function confirmedAdminEmail(array $confirmedAdmin): string
    {
        return $this->normalizeEmailForConfirmation((string)($confirmedAdmin['email_address'] ?? ''));
    }

    private function passwordMatches(array $confirmedAdmin, string $confirmedAdminPassword): bool
    {
        $passwordHash = (string)($confirmedAdmin['password_hash'] ?? '');

        return $passwordHash !== '' && password_verify($confirmedAdminPassword, $passwordHash);
    }

    private function normalizeEmailForConfirmation(string $emailAddress): string
    {
        return strtolower(trim(preg_replace('/\s+/', '', $emailAddress) ?? ''));
    }

    private function error(string $message): array
    {
        return [
            'success' => false,
            'errorCode' => 'SecurityConfirmationFailed',
            'message' => $message,
            'status' => 422,
        ];
    }
}
