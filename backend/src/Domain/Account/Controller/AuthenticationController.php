<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\Repository\AccountRepository;
use App\Shared\Traits\JsonResponseTrait;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/auth')]
class AuthenticationController
{
    use JsonResponseTrait;

    private AccountRepository $accountRepository;
    private Connection $connection;

    public function __construct(AccountRepository $accountRepository, Connection $connection)
    {
        $this->accountRepository = $accountRepository;
        $this->connection = $connection;
    }

    #[Route('/login', name: 'auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];

        $emailAddress = trim($requestBody['emailAddress'] ?? '');
        $passwordText = $requestBody['passwordText'] ?? '';

        if (empty($emailAddress) || empty($passwordText)) {
            return $this->createErrorResponse(
                'ValidationError',
                'Email address and password are required.',
                400
            );
        }

        try {
            $accountEntity = $this->accountRepository->findOneByEmailAddress($emailAddress);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse(
                'DatabaseUnavailable',
                'The authentication database is currently unavailable. Please make sure the TechReserve database service is running.',
                503
            );
        }

        if ($accountEntity === null) {
            return $this->createErrorResponse(
                'AuthenticationFailed',
                'Invalid email address or password.',
                401
            );
        }

        if (!$accountEntity->getIsActive()) {
            return $this->createErrorResponse(
                'AccountDisabled',
                'This account has been disabled. Please contact an administrator.',
                403
            );
        }

        $lockedUntil = $accountEntity->getLockedUntilTimestamp();
        if ($lockedUntil !== null && $lockedUntil > new \DateTime()) {
            return $this->createErrorResponse(
                'AccountLocked',
                'This account is temporarily locked due to too many failed login attempts.',
                403
            );
        }

        $storedHash = $accountEntity->getPasswordHash();

        if ($storedHash === null) {
            return $this->createErrorResponse(
                'LocalPasswordUnavailable',
                'This account uses Clerk authentication. Continuing with Clerk sign-in.',
                401
            );
        }

        if (!password_verify($passwordText, $storedHash)) {
            $failedAttempts = $accountEntity->getFailedLoginAttempts() + 1;
            $accountEntity->setFailedLoginAttempts($failedAttempts);

            if ($failedAttempts >= 5) {
                $accountEntity->setLockedUntilTimestamp(new \DateTime('+15 minutes'));
            }

            try {
                $this->accountRepository->persistAccount($accountEntity);
            } catch (\Throwable $exception) {
                return $this->createErrorResponse(
                    'DatabaseUnavailable',
                    'The authentication database is currently unavailable. Please make sure the TechReserve database service is running.',
                    503
                );
            }

            return $this->createErrorResponse(
                'AuthenticationFailed',
                'Invalid email address or password.',
                401
            );
        }

        $accountEntity->setFailedLoginAttempts(0);
        $accountEntity->setLockedUntilTimestamp(null);
        $accountEntity->setLastLoginTimestamp(new \DateTime());
        try {
            $this->accountRepository->persistAccount($accountEntity);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse(
                'DatabaseUnavailable',
                'The authentication database is currently unavailable. Please make sure the TechReserve database service is running.',
                503
            );
        }

        $tokenPayload = base64_encode(json_encode([
            'accountId' => $accountEntity->getAccountIdentifier(),
            'email' => $accountEntity->getEmailAddress(),
            'role' => $accountEntity->getRoleDesignation(),
            'exp' => time() + 86400,
        ]));

        return $this->createSuccessResponse([
            'token' => $tokenPayload,
            'account' => [
                'accountIdentifier' => $accountEntity->getAccountIdentifier(),
                'firstName' => $accountEntity->getFirstName(),
                'lastName' => $accountEntity->getLastName(),
                'emailAddress' => $accountEntity->getEmailAddress(),
                'roleDesignation' => $accountEntity->getRoleDesignation(),
            ],
        ]);
    }

    #[Route('/register', name: 'auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            // Handle both JSON and FormData
            $contentType = $request->headers->get('Content-Type', '');
            
            if (strpos($contentType, 'application/json') !== false) {
                $requestBody = json_decode($request->getContent(), true) ?? [];
                $firstName = trim($requestBody['firstName'] ?? '');
                $lastName = trim($requestBody['lastName'] ?? '');
                $emailAddress = trim($requestBody['emailAddress'] ?? '');
                $passwordText = $requestBody['passwordText'] ?? '';
            } else {
                // Handle FormData
                $firstName = trim($request->request->get('firstName', ''));
                $lastName = trim($request->request->get('lastName', ''));
                $emailAddress = trim($request->request->get('emailAddress', ''));
                $passwordText = $request->request->get('passwordText', '');
            }

            if (empty($firstName) || empty($lastName) || empty($emailAddress) || empty($passwordText)) {
                return $this->createErrorResponse(
                    'ValidationError',
                    'First name, last name, email address, and password are required.',
                    400
                );
            }

            if (strlen($passwordText) < 8) {
                return $this->createErrorResponse(
                    'ValidationError',
                    'Password must be at least 8 characters long.',
                    400
                );
            }

            $existingAccount = $this->accountRepository->findOneByEmailAddress($emailAddress);

            if ($existingAccount !== null) {
                return $this->createErrorResponse(
                    'DuplicateAccount',
                    'An account with this email address already exists.',
                    409
                );
            }

            $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
            $passwordHash = password_hash($passwordText, PASSWORD_BCRYPT, ['cost' => 4]);

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
                    'passwordHash' => $passwordHash,
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

            $accountIdentifier = (int)$this->connection->lastInsertId();

            return $this->createSuccessResponse([
                'message' => 'Account registered successfully.',
                'account' => [
                    'accountIdentifier' => $accountIdentifier,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'emailAddress' => $emailAddress,
                    'roleDesignation' => 'ROLE_BORROWER',
                ],
            ], 201);
        } catch (\Exception $exception) {
            error_log('Registration error: ' . $exception->getMessage());
            return $this->createErrorResponse(
                'RegistrationError',
                'An error occurred during registration. Please try again.',
                500
            );
        }
    }
}
