<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\Repository\AccountRepository;
use App\Shared\Traits\JsonResponseTrait;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/v1/auth')]
class AuthenticationController
{
    use JsonResponseTrait;

    private AccountRepository $accountRepository;
    private Connection $connection;
    private MailerInterface $mailer;
    private HttpClientInterface $httpClient;

    public function __construct(
        AccountRepository $accountRepository,
        Connection $connection,
        MailerInterface $mailer,
        HttpClientInterface $httpClient
    ) {
        $this->accountRepository = $accountRepository;
        $this->connection = $connection;
        $this->mailer = $mailer;
        $this->httpClient = $httpClient;
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

        $accountStatus = strtolower(trim((string)$accountEntity->getStatus()));
        if (!$accountEntity->getIsApproved() || $accountStatus !== 'approved') {
            return $this->createErrorResponse(
                'AccountPendingApproval',
                'Your account is pending administrator approval. Please wait for an invitation before signing in.',
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

        $profilePhotoData = $this->connection->fetchOne(
            'SELECT profile_photo_data FROM accounts WHERE account_identifier = :accountIdentifier',
            ['accountIdentifier' => $accountEntity->getAccountIdentifier()],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        return $this->createSuccessResponse([
            'token' => $tokenPayload,
            'account' => [
                'accountIdentifier' => $accountEntity->getAccountIdentifier(),
                'firstName' => $accountEntity->getFirstName(),
                'lastName' => $accountEntity->getLastName(),
                'emailAddress' => $accountEntity->getEmailAddress(),
                'roleDesignation' => $accountEntity->getRoleDesignation(),
                'profilePhotoData' => $profilePhotoData ? (string)$profilePhotoData : null,
            ],
        ]);
    }

    #[Route('/clerk-login-preflight', name: 'auth_clerk_login_preflight', methods: ['POST'])]
    public function clerkLoginPreflight(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $emailAddress = strtolower(trim((string)($requestBody['emailAddress'] ?? '')));

        if ($emailAddress === '' || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            return $this->createErrorResponse(
                'ValidationError',
                'A valid email address is required.',
                422
            );
        }

        $account = $this->connection->fetchAssociative(
            "SELECT account_identifier, email_address, status, is_approved, is_active
             FROM accounts
             WHERE LOWER(email_address) = LOWER(:emailAddress)
             LIMIT 1",
            ['emailAddress' => $emailAddress],
            ['emailAddress' => ParameterType::STRING]
        );

        if (!$account) {
            return $this->createErrorResponse(
                'AccountPendingInvitation',
                'Please wait for an administrator invitation before signing in.',
                403
            );
        }

        $isActive = $this->toDatabaseBoolean($account['is_active'] ?? true);
        if (!$isActive) {
            return $this->createErrorResponse(
                'AccountDisabled',
                'This account has been disabled. Please contact an administrator.',
                403
            );
        }

        $status = strtolower(trim((string)($account['status'] ?? 'pending')));
        $isApproved = $this->toDatabaseBoolean($account['is_approved'] ?? false);
        if ($isApproved && $status === 'approved') {
            return $this->createSuccessResponse([
                'canSignIn' => true,
                'accountStatus' => $status,
            ]);
        }

        if (in_array($status, ['rejected', 'denied'], true)) {
            return $this->createErrorResponse(
                'AccountRejected',
                'This account request was denied. Please contact the administrator.',
                403
            );
        }

        $invitation = $this->connection->fetchAssociative(
            "SELECT status, expires_at, accepted_at
             FROM invitations
             WHERE LOWER(email) = LOWER(:emailAddress)
             ORDER BY created_at DESC
             LIMIT 1",
            ['emailAddress' => $emailAddress],
            ['emailAddress' => ParameterType::STRING]
        );

        if ($status === 'invited' && $this->isActiveOrAcceptedInvitation($invitation ?: null)) {
            return $this->createSuccessResponse([
                'canSignIn' => true,
                'accountStatus' => $status,
            ]);
        }

        return $this->createErrorResponse(
            'AccountPendingInvitation',
            'Your account request is still pending. Please wait for an administrator invitation before signing in.',
            403
        );
    }

    #[Route('/password-reset/request', name: 'auth_password_reset_request', methods: ['POST'])]
    public function requestPasswordReset(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $emailAddress = strtolower(trim((string)($requestBody['emailAddress'] ?? '')));

        if ($emailAddress === '' || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            return $this->createErrorResponse('ValidationError', 'A valid email address is required.', 422);
        }

        $account = $this->connection->fetchAssociative(
            'SELECT account_identifier, first_name, last_name, email_address
             FROM accounts
             WHERE LOWER(email_address) = LOWER(:emailAddress)
             LIMIT 1',
            ['emailAddress' => $emailAddress],
            ['emailAddress' => ParameterType::STRING]
        );

        if (!$account) {
            return $this->createErrorResponse('AccountNotFound', 'No TechReserve account was found for this email address.', 404);
        }

        $clerkUser = $this->findClerkUserByEmail($emailAddress);
        if ($clerkUser === null) {
            return $this->createErrorResponse('ClerkAccountNotFound', 'No Clerk account was found for this email address.', 422);
        }

        $this->ensurePasswordResetTable();

        $code = (string)random_int(100000, 999999);
        $now = new \DateTimeImmutable();
        $expiresAt = $now->modify('+15 minutes')->format('Y-m-d H:i:s');

        $this->connection->executeStatement(
            'DELETE FROM password_reset_codes WHERE LOWER(email_address) = LOWER(:emailAddress)',
            ['emailAddress' => $emailAddress],
            ['emailAddress' => ParameterType::STRING]
        );

        $this->connection->executeStatement(
            'INSERT INTO password_reset_codes
                (email_address, clerk_user_id, code_hash, attempts, expires_at, created_timestamp)
             VALUES
                (:emailAddress, :clerkUserId, :codeHash, 0, :expiresAt, :createdTimestamp)',
            [
                'emailAddress' => $emailAddress,
                'clerkUserId' => (string)$clerkUser['id'],
                'codeHash' => password_hash($code, PASSWORD_BCRYPT),
                'expiresAt' => $expiresAt,
                'createdTimestamp' => $now->format('Y-m-d H:i:s'),
            ],
            [
                'emailAddress' => ParameterType::STRING,
                'clerkUserId' => ParameterType::STRING,
                'codeHash' => ParameterType::STRING,
                'expiresAt' => ParameterType::STRING,
                'createdTimestamp' => ParameterType::STRING,
            ]
        );

        try {
            $recipientName = trim((string)$account['first_name'] . ' ' . (string)$account['last_name']);
            $email = (new Email())
                ->from($_ENV['MAILER_FROM'] ?? 'noreply@techreserve.feutech.edu.ph')
                ->to($emailAddress)
                ->subject('Your TechReserve password reset code')
                ->html($this->buildPasswordResetEmailHtml($recipientName !== '' ? $recipientName : $emailAddress, $code));

            $this->mailer->send($email);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse('EmailSendFailed', 'Unable to send the reset code email. Please check the mailer configuration.', 503);
        }

        return $this->createSuccessResponse([
            'message' => 'Password reset code sent.',
        ]);
    }

    #[Route('/password-reset/confirm', name: 'auth_password_reset_confirm', methods: ['POST'])]
    public function confirmPasswordReset(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $emailAddress = strtolower(trim((string)($requestBody['emailAddress'] ?? '')));
        $code = trim((string)($requestBody['code'] ?? ''));
        $newPassword = (string)($requestBody['newPassword'] ?? '');
        $confirmPassword = (string)($requestBody['confirmPassword'] ?? '');

        if ($emailAddress === '' || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL) || $code === '') {
            return $this->createErrorResponse('ValidationError', 'Email address and reset code are required.', 422);
        }

        if ($newPassword !== $confirmPassword) {
            return $this->createErrorResponse('ValidationError', 'New password and confirmation password do not match.', 422);
        }

        if (!$this->isStrongPassword($newPassword)) {
            return $this->createErrorResponse('ValidationError', 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.', 422);
        }

        $this->ensurePasswordResetTable();
        $reset = $this->connection->fetchAssociative(
            'SELECT email_address, clerk_user_id, code_hash, attempts, expires_at
             FROM password_reset_codes
             WHERE LOWER(email_address) = LOWER(:emailAddress)
             LIMIT 1',
            ['emailAddress' => $emailAddress],
            ['emailAddress' => ParameterType::STRING]
        );

        if (!$reset) {
            return $this->createErrorResponse('InvalidResetCode', 'Invalid or expired reset code.', 422);
        }

        if ((int)$reset['attempts'] >= 5 || new \DateTimeImmutable((string)$reset['expires_at']) < new \DateTimeImmutable()) {
            return $this->createErrorResponse('InvalidResetCode', 'Invalid or expired reset code.', 422);
        }

        if (!password_verify($code, (string)$reset['code_hash'])) {
            $this->connection->executeStatement(
                'UPDATE password_reset_codes SET attempts = attempts + 1 WHERE LOWER(email_address) = LOWER(:emailAddress)',
                ['emailAddress' => $emailAddress],
                ['emailAddress' => ParameterType::STRING]
            );
            return $this->createErrorResponse('InvalidResetCode', 'Invalid or expired reset code.', 422);
        }

        $clerkUserId = (string)$reset['clerk_user_id'];
        if (!$this->updateClerkPassword($clerkUserId, $newPassword)) {
            return $this->createErrorResponse('ClerkPasswordUpdateFailed', 'Unable to update the Clerk password for this account.', 502);
        }

        $updatedRows = $this->connection->executeStatement(
            'UPDATE accounts
             SET password_hash = :passwordHash,
                 clerk_user_id = COALESCE(NULLIF(clerk_user_id, \'\'), :clerkUserId),
                 updated_timestamp = :updatedTimestamp
             WHERE LOWER(email_address) = LOWER(:emailAddress)',
            [
                'passwordHash' => password_hash($newPassword, PASSWORD_BCRYPT),
                'clerkUserId' => $clerkUserId,
                'updatedTimestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'emailAddress' => $emailAddress,
            ],
            [
                'passwordHash' => ParameterType::STRING,
                'clerkUserId' => ParameterType::STRING,
                'updatedTimestamp' => ParameterType::STRING,
                'emailAddress' => ParameterType::STRING,
            ]
        );

        if ($updatedRows === 0) {
            return $this->createErrorResponse('AccountNotFound', 'Account not found.', 404);
        }

        $this->connection->executeStatement(
            'DELETE FROM password_reset_codes WHERE LOWER(email_address) = LOWER(:emailAddress)',
            ['emailAddress' => $emailAddress],
            ['emailAddress' => ParameterType::STRING]
        );

        return $this->createSuccessResponse([
            'message' => 'Password reset successfully.',
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

    private function ensurePasswordResetTable(): void
    {
        $this->connection->executeStatement('CREATE TABLE IF NOT EXISTS password_reset_codes (
            email_address VARCHAR(100) PRIMARY KEY,
            clerk_user_id VARCHAR(255) NOT NULL,
            code_hash VARCHAR(255) NOT NULL,
            attempts INT NOT NULL DEFAULT 0,
            expires_at TIMESTAMP NOT NULL,
            created_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }

    private function isActiveOrAcceptedInvitation(?array $invitation): bool
    {
        if ($invitation === null) {
            return false;
        }

        if (!empty($invitation['accepted_at'])) {
            return true;
        }

        $status = strtolower((string)($invitation['status'] ?? 'pending'));
        if (in_array($status, ['accepted', 'completed'], true)) {
            return true;
        }

        if (in_array($status, ['expired', 'rejected', 'denied'], true)) {
            return false;
        }

        try {
            $expiresAt = new \DateTimeImmutable((string)$invitation['expires_at']);
        } catch (\Throwable) {
            return false;
        }

        return $expiresAt >= new \DateTimeImmutable();
    }

    private function toDatabaseBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        $normalized = strtolower(trim((string)$value));
        return in_array($normalized, ['1', 'true', 't', 'yes', 'y'], true);
    }

    private function findClerkUserByEmail(string $emailAddress): ?array
    {
        $secretKey = trim((string)($_ENV['CLERK_SECRET_KEY'] ?? ''));
        if ($secretKey === '') {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', ($_ENV['CLERK_API_BASE_URL'] ?? 'https://api.clerk.com') . '/v1/users', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secretKey,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'email_address' => $emailAddress,
                ],
            ]);

            if ($response->getStatusCode() >= 400) {
                return null;
            }

            $data = $response->toArray(false);
            if (isset($data['id'])) {
                return $data;
            }

            if (isset($data[0]['id'])) {
                return $data[0];
            }

            if (isset($data['data'][0]['id'])) {
                return $data['data'][0];
            }
        } catch (\Throwable $exception) {
            return null;
        }

        return null;
    }

    private function updateClerkPassword(string $clerkUserId, string $newPassword): bool
    {
        $secretKey = trim((string)($_ENV['CLERK_SECRET_KEY'] ?? ''));
        if ($secretKey === '') {
            return false;
        }

        try {
            $response = $this->httpClient->request('PATCH', ($_ENV['CLERK_API_BASE_URL'] ?? 'https://api.clerk.com') . '/v1/users/' . rawurlencode($clerkUserId), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secretKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'password' => $newPassword,
                ],
            ]);

            return $response->getStatusCode() < 400;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    private function isStrongPassword(string $password): bool
    {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password) === 1;
    }

    private function buildPasswordResetEmailHtml(string $recipientName, string $code): string
    {
        return sprintf(
            '<div style="font-family:Arial,sans-serif;color:#1f2937;line-height:1.5;">
                <h2 style="color:#007a4d;">TechReserve Password Reset</h2>
                <p>Hello %s,</p>
                <p>Use this code to reset your TechReserve password:</p>
                <p style="font-size:28px;font-weight:800;letter-spacing:4px;color:#111827;">%s</p>
                <p>This code expires in 15 minutes.</p>
                <p>If you did not request this, you can ignore this email.</p>
            </div>',
            htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($code, ENT_QUOTES, 'UTF-8')
        );
    }
}
