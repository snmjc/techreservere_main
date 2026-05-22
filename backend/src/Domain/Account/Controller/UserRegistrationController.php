<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\Repository\AccountRepository;
use App\Domain\Account\Entity\AccountEntity;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/v1/users')]
class UserRegistrationController extends AbstractController
{
    use JsonResponseTrait;

    private const ADMIN_EMAIL_ALLOWLIST = [
        'smmojica@fit.edu.ph',
    ];

    private AccountRepository $accountRepository;
    private HttpClientInterface $httpClient;
    private Connection $connection;
    private MailerInterface $mailer;

    public function __construct(
        AccountRepository $accountRepository,
        HttpClientInterface $httpClient,
        Connection $connection,
        MailerInterface $mailer
    )
    {
        $this->accountRepository = $accountRepository;
        $this->httpClient = $httpClient;
        $this->connection = $connection;
        $this->mailer = $mailer;
    }

    #[Route('/register', name: 'user_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];

        $clerkUserId = trim($requestBody['clerkUserId'] ?? '');
        $firstName = trim($requestBody['firstName'] ?? '');
        $lastName = trim($requestBody['lastName'] ?? '');
        $emailAddress = trim($requestBody['emailAddress'] ?? '');
        $role = trim($requestBody['role'] ?? 'ROLE_BORROWER');
        $contactNumber = trim($requestBody['contactNumber'] ?? '');
        $department = trim($requestBody['department'] ?? '');
        $idNumber = trim($requestBody['idNumber'] ?? $requestBody['studentIdNumber'] ?? $contactNumber);
        $invitedBy = $requestBody['invitedBy'] ?? null;
        $role = $this->resolveRole($role, $emailAddress);
        $isAdminEmail = $this->isAdminEmail($emailAddress);
        $status = ($invitedBy || $isAdminEmail) ? 'approved' : 'pending';
        $isApproved = (bool) ($invitedBy || $isAdminEmail);

        if (empty($clerkUserId) || empty($firstName) || empty($lastName) || empty($emailAddress)) {
            return $this->createErrorResponse(
                'ValidationError',
                'clerkUserId, firstName, lastName, and emailAddress are required.',
                400
            );
        }

        // Check if user already exists
        $existingAccount = $this->accountRepository->findOneByClerkUserId($clerkUserId);
        if ($existingAccount !== null) {
            if ($isAdminEmail) {
                $this->connection->executeStatement(
                    'UPDATE accounts
                     SET role_designation = :roleDesignation,
                         status = :status,
                         is_approved = :isApproved,
                         is_active = :isActive,
                         updated_timestamp = :updatedTimestamp
                     WHERE account_identifier = :accountIdentifier',
                    [
                        'roleDesignation' => 'ROLE_ADMIN',
                        'status' => 'approved',
                        'isApproved' => true,
                        'isActive' => true,
                        'updatedTimestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                        'accountIdentifier' => $existingAccount->getAccountIdentifier(),
                    ],
                    [
                        'roleDesignation' => ParameterType::STRING,
                        'status' => ParameterType::STRING,
                        'isApproved' => ParameterType::BOOLEAN,
                        'isActive' => ParameterType::BOOLEAN,
                        'updatedTimestamp' => ParameterType::STRING,
                        'accountIdentifier' => ParameterType::INTEGER,
                    ]
                );

                return $this->createSuccessResponse([
                    'message' => 'Existing account promoted to admin.',
                    'account' => [
                        'accountIdentifier' => $existingAccount->getAccountIdentifier(),
                        'clerkUserId' => $existingAccount->getClerkUserId(),
                        'firstName' => $existingAccount->getFirstName(),
                        'lastName' => $existingAccount->getLastName(),
                        'emailAddress' => $existingAccount->getEmailAddress(),
                        'roleDesignation' => 'ROLE_ADMIN',
                        'status' => 'approved',
                        'isApproved' => true,
                    ],
                ]);
            }

            return $this->createSuccessResponse([
                'message' => 'Account already registered.',
                'account' => [
                    'accountIdentifier' => $existingAccount->getAccountIdentifier(),
                    'clerkUserId' => $existingAccount->getClerkUserId(),
                    'firstName' => $existingAccount->getFirstName(),
                    'lastName' => $existingAccount->getLastName(),
                    'emailAddress' => $existingAccount->getEmailAddress(),
                    'roleDesignation' => $existingAccount->getRoleDesignation(),
                    'status' => $existingAccount->getStatus(),
                    'isApproved' => $existingAccount->getIsApproved(),
                ],
            ]);
        }

        $existingEmailAccount = $this->accountRepository->findOneByEmailAddress($emailAddress);
        if ($existingEmailAccount !== null) {
            $existingStatus = strtolower($existingEmailAccount->getStatus());
            $existingIsApproved = $existingEmailAccount->getIsApproved();
            $existingIsActive = $existingEmailAccount->getIsActive();
            $nextStatus = $existingIsApproved ? ($existingIsActive ? 'approved' : 'disabled') : $existingStatus;
            if ($nextStatus === '') {
                $nextStatus = $status;
            }
            $nextIsApproved = $existingIsApproved || $isApproved;
            $nextIsActive = $existingIsApproved ? $existingIsActive : true;

            $now = (new \DateTime())->format('Y-m-d H:i:s');
            $this->connection->executeStatement(
                'UPDATE accounts
                 SET last_name = :lastName,
                     first_name = :firstName,
                     role_designation = :roleDesignation,
                     id_number = :idNumber,
                     department = :department,
                     contact_number = :contactNumber,
                     clerk_user_id = :clerkUserId,
                     status = :status,
                     is_approved = :isApproved,
                     is_active = :isActive,
                     updated_timestamp = :updatedTimestamp
                 WHERE account_identifier = :accountIdentifier',
                [
                    'lastName' => $lastName,
                    'firstName' => $firstName,
                    'roleDesignation' => $role,
                    'idNumber' => $idNumber ?: null,
                    'department' => $department ?: null,
                    'contactNumber' => $contactNumber ?: null,
                    'clerkUserId' => $clerkUserId,
                    'status' => $nextStatus,
                    'isApproved' => $nextIsApproved,
                    'isActive' => $nextIsActive,
                    'updatedTimestamp' => $now,
                    'accountIdentifier' => $existingEmailAccount->getAccountIdentifier(),
                ],
                [
                    'lastName' => ParameterType::STRING,
                    'firstName' => ParameterType::STRING,
                    'roleDesignation' => ParameterType::STRING,
                    'idNumber' => $idNumber === '' ? ParameterType::NULL : ParameterType::STRING,
                    'department' => $department === '' ? ParameterType::NULL : ParameterType::STRING,
                    'contactNumber' => $contactNumber === '' ? ParameterType::NULL : ParameterType::STRING,
                    'clerkUserId' => ParameterType::STRING,
                    'status' => ParameterType::STRING,
                    'isApproved' => ParameterType::BOOLEAN,
                    'isActive' => ParameterType::BOOLEAN,
                    'updatedTimestamp' => ParameterType::STRING,
                    'accountIdentifier' => ParameterType::INTEGER,
                ]
            );

            return $this->createSuccessResponse([
                'message' => 'Account linked to Clerk successfully.',
                'account' => [
                    'accountIdentifier' => $existingEmailAccount->getAccountIdentifier(),
                    'clerkUserId' => $clerkUserId,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'emailAddress' => $emailAddress,
                    'roleDesignation' => $role,
                    'status' => $nextStatus,
                    'isApproved' => $nextIsApproved,
                    'isActive' => $nextIsActive,
                ],
            ]);
        }

        // Create new account via DBAL raw SQL
        try {
            $now = (new \DateTime())->format('Y-m-d H:i:s');

            $this->connection->executeStatement(
                'INSERT INTO accounts
                    (last_name, first_name, email_address, role_designation, id_number, department,
                     contact_number, clerk_user_id, status, is_approved, is_active,
                     failed_login_attempts, created_timestamp, updated_timestamp)
                 VALUES
                    (:lastName, :firstName, :emailAddress, :roleDesignation, :idNumber, :department,
                     :contactNumber, :clerkUserId, :status, :isApproved, :isActive,
                     :failedLoginAttempts, :createdTimestamp, :updatedTimestamp)',
                [
                    'lastName' => $lastName,
                    'firstName' => $firstName,
                    'emailAddress' => $emailAddress,
                    'roleDesignation' => $role,
                    'idNumber' => $idNumber ?: null,
                    'department' => $department ?: null,
                    'contactNumber' => $contactNumber ?: null,
                    'clerkUserId' => $clerkUserId,
                    'status' => $status,
                    'isApproved' => $isApproved,
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
                    'idNumber' => $idNumber === '' ? ParameterType::NULL : ParameterType::STRING,
                    'department' => $department === '' ? ParameterType::NULL : ParameterType::STRING,
                    'contactNumber' => $contactNumber === '' ? ParameterType::NULL : ParameterType::STRING,
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

            return $this->createSuccessResponse([
                'message' => 'Account registered successfully.',
                'account' => [
                    'accountIdentifier' => (int)$newId,
                    'clerkUserId' => $clerkUserId,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'emailAddress' => $emailAddress,
                    'roleDesignation' => $role,
                    'status' => $status,
                    'isApproved' => $isApproved,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return $this->createErrorResponse(
                'RegistrationFailed',
                'Failed to register account: ' . $e->getMessage(),
                500
            );
        }
    }

    #[Route('/me', name: 'get_my_account', methods: ['GET'])]
    public function me(Request $request): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization', '');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->createErrorResponse('AuthRequired', 'Authorization header required.', 401);
        }

        $bearerToken = substr($authHeader, 7);

        try {
            $parts = explode('.', $bearerToken);
            if (count($parts) !== 3) {
                error_log('JWT decode: Invalid format, parts=' . count($parts));
                return $this->createErrorResponse('InvalidToken', 'Invalid JWT format.', 401);
            }
            $padded  = strtr($parts[1], '-_', '+/') . str_repeat('=', (4 - strlen($parts[1]) % 4) % 4);
            $payload = json_decode(base64_decode($padded), true);
            error_log('JWT decode: payload=' . json_encode($payload));
            if (!is_array($payload) || empty($payload['sub'])) {
                error_log('JWT decode: Missing sub claim');
                return $this->createErrorResponse('InvalidToken', 'JWT missing sub claim.', 401);
            }
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                error_log('JWT decode: Token expired');
                return $this->createErrorResponse('TokenExpired', 'Clerk session token has expired.', 401);
            }
            $clerkUserId = $payload['sub'];
            error_log('JWT decode: Success, clerkUserId=' . $clerkUserId);
        } catch (\Throwable $e) {
            error_log('JWT decode error: ' . $e->getMessage());
            return $this->createErrorResponse('InvalidToken', 'Clerk token verification failed.', 401);
        }

        $account = $this->accountRepository->findOneByClerkUserId($clerkUserId);

        if ($account === null) {
            return $this->createErrorResponse('AccountNotFound', 'No account registered for this Clerk user.', 404);
        }

        return $this->createSuccessResponse([
            'account' => [
                'accountIdentifier' => $account->getAccountIdentifier(),
                'clerkUserId'       => $account->getClerkUserId(),
                'firstName'         => $account->getFirstName(),
                'lastName'          => $account->getLastName(),
                'emailAddress'      => $account->getEmailAddress(),
                'roleDesignation'   => $account->getRoleDesignation(),
                'department'        => $account->getDepartment(),
                'contactNumber'     => $account->getContactNumber(),
                'status'            => $account->getStatus(),
                'isApproved'        => $account->getIsApproved(),
                'isActive'          => $account->getIsActive(),
                'createdTimestamp'  => $account->getCreatedTimestamp()->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    #[Route('/pending', name: 'list_pending_users', methods: ['GET'])]
    public function listPendingUsers(): JsonResponse
    {
        // Get all pending users
        $pendingUsers = $this->accountRepository->findBy([
            'status' => 'pending',
            'isApproved' => false
        ]);

        $users = [];
        foreach ($pendingUsers as $user) {
            $users[] = [
                'accountIdentifier' => $user->getAccountIdentifier(),
                'clerkUserId' => $user->getClerkUserId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'emailAddress' => $user->getEmailAddress(),
                'roleDesignation' => $user->getRoleDesignation(),
                'contactNumber' => $user->getContactNumber(),
                'status' => $user->getStatus(),
                'isApproved' => $user->getIsApproved(),
                'createdTimestamp' => $user->getCreatedTimestamp()->format('Y-m-d H:i:s'),
            ];
        }

        return $this->createSuccessResponse([
            'count' => count($users),
            'users' => $users,
        ]);
    }

    #[Route('/wishlist', name: 'list_wishlist_users', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function listWishlistUsers(): JsonResponse
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT account_identifier, id_number, last_name, first_name, email_address, role_designation,
                    department, contact_number, status, is_approved, created_timestamp,
                    latest_invitation.created_at AS invite_sent_at,
                    latest_invitation.expires_at AS invite_expires_at,
                    latest_invitation.accepted_at AS invite_accepted_at
             FROM accounts
             LEFT JOIN LATERAL (
                SELECT created_at, expires_at, accepted_at
                FROM invitations
                WHERE LOWER(email) = LOWER(accounts.email_address)
                ORDER BY created_at DESC
                LIMIT 1
             ) latest_invitation ON TRUE
             WHERE COALESCE(is_approved, FALSE) = FALSE
               AND LOWER(COALESCE(status, 'pending')) <> 'approved'
             ORDER BY created_timestamp DESC"
        );

        $users = array_map(function (array $row): array {
            $roleDesignation = (string)($row['role_designation'] ?? 'ROLE_BORROWER');
            $department = strtolower((string)($row['department'] ?? ''));
            $isAdmin = str_contains(strtoupper($roleDesignation), 'ADMIN');
            $normalizedRole = strtoupper($roleDesignation);
            $isEmployee = !$isAdmin && (
                str_contains($normalizedRole, 'STAFF') ||
                str_contains($normalizedRole, 'EMPLOYEE') ||
                str_contains($department, 'staff') ||
                str_contains($department, 'employee') ||
                str_contains($department, 'technical') ||
                str_contains($department, 'maintenance')
            );
            $accountType = $isAdmin ? 'Admin' : ($isEmployee ? 'Employee' : 'User');
            $employeeRoleLabel = str_contains($department, 'faculty') || str_contains($normalizedRole, 'FACULTY')
                ? 'Faculty'
                : ($department !== '' ? ucwords($department) : 'Technical Staff');
            $roleLabel = $isAdmin ? 'Admin' : ($isEmployee ? $employeeRoleLabel : 'User: Student');

            return [
                'accountIdentifier' => (int)$row['account_identifier'],
                'idNumber' => $row['id_number'] ?: substr((string)$row['created_timestamp'], 0, 4) . str_pad((string)$row['account_identifier'], 4, '0', STR_PAD_LEFT),
                'lastName' => (string)$row['last_name'],
                'firstName' => (string)$row['first_name'],
                'emailAddress' => (string)$row['email_address'],
                'contactNumber' => $row['contact_number'] ? (string)$row['contact_number'] : null,
                'roleDesignation' => $roleDesignation,
                'roleLabel' => $roleLabel,
                'accountType' => $accountType,
                'accountStatus' => (string)($row['status'] ?? 'pending'),
                'isApproved' => (bool)$row['is_approved'],
                'registeredAt' => (string)$row['created_timestamp'],
                'inviteSentAt' => $row['invite_sent_at'] ? (string)$row['invite_sent_at'] : null,
                'inviteExpiresAt' => $row['invite_expires_at'] ? (string)$row['invite_expires_at'] : null,
                'inviteAcceptedAt' => $row['invite_accepted_at'] ? (string)$row['invite_accepted_at'] : null,
            ];
        }, $rows);

        return $this->createSuccessResponse([
            'count' => count($users),
            'users' => $users,
        ]);
    }

    #[Route('/wishlist/admin-accounts', name: 'create_wishlist_admin_account', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function createWishlistAdminAccount(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];

        $lastName = trim($requestBody['lastName'] ?? '');
        $firstName = trim($requestBody['firstName'] ?? '');
        $emailAddress = strtolower(trim($requestBody['emailAddress'] ?? ''));
        $idNumber = trim($requestBody['idNumber'] ?? '');
        $passwordText = (string)($requestBody['passwordText'] ?? $requestBody['password'] ?? '');

        if ($lastName === '' || $firstName === '' || $emailAddress === '' || $idNumber === '' || $passwordText === '') {
            return $this->createErrorResponse(
                'ValidationError',
                'Last name, first name, email, ID number, and password are required.',
                422
            );
        }

        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            return $this->createErrorResponse('ValidationError', 'Please provide a valid email address.', 422);
        }

        $existingEmailAccount = $this->findAccountConflictByEmail($emailAddress);

        if ($existingEmailAccount) {
            return $this->createErrorResponse(
                'DuplicateAccount',
                $this->buildDuplicateAccountMessage('email', $existingEmailAccount),
                409,
                ['conflict' => $this->normalizeAccountConflict($existingEmailAccount, 'email')]
            );
        }

        $existingIdNumberAccount = $this->findAccountConflictByIdNumber($idNumber);

        if ($existingIdNumberAccount) {
            return $this->createErrorResponse(
                'DuplicateIdNumber',
                $this->buildDuplicateAccountMessage('ID number', $existingIdNumberAccount),
                409,
                ['conflict' => $this->normalizeAccountConflict($existingIdNumberAccount, 'idNumber')]
            );
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $passwordHash = password_hash($passwordText, PASSWORD_BCRYPT);

        try {
            $this->connection->executeStatement(
                'INSERT INTO accounts
                    (last_name, first_name, email_address, role_designation, id_number, department,
                     contact_number, clerk_user_id, password_hash, status, is_approved, is_active,
                     failed_login_attempts, created_timestamp, updated_timestamp)
                 VALUES
                    (:lastName, :firstName, :emailAddress, :roleDesignation, :idNumber, :department,
                     :contactNumber, :clerkUserId, :passwordHash, :status, :isApproved, :isActive,
                     :failedLoginAttempts, :createdTimestamp, :updatedTimestamp)',
                [
                    'lastName' => $lastName,
                    'firstName' => $firstName,
                    'emailAddress' => $emailAddress,
                    'roleDesignation' => 'ROLE_ADMIN',
                    'idNumber' => $idNumber,
                    'department' => 'Administration',
                    'contactNumber' => null,
                    'clerkUserId' => null,
                    'passwordHash' => $passwordHash,
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
                    'roleDesignation' => ParameterType::STRING,
                    'idNumber' => ParameterType::STRING,
                    'department' => ParameterType::STRING,
                    'contactNumber' => ParameterType::NULL,
                    'clerkUserId' => ParameterType::NULL,
                    'passwordHash' => ParameterType::STRING,
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
                'accountIdentifier' => $accountIdentifier,
                'idNumber' => $idNumber,
                'lastName' => $lastName,
                'firstName' => $firstName,
                'emailAddress' => $emailAddress,
                'roleDesignation' => 'ROLE_ADMIN',
                'roleLabel' => 'Admin',
                'accountType' => 'Admin',
                'accountStatus' => 'pending',
                'isApproved' => false,
                'registeredAt' => $now,
                'inviteSentAt' => null,
                'inviteExpiresAt' => null,
                'inviteAcceptedAt' => null,
            ], 201);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse(
                'CreateAdminAccountFailed',
                'Failed to create admin account: ' . $exception->getMessage(),
                500
            );
        }
    }

    #[Route('/wishlist/user-accounts', name: 'create_wishlist_user_account', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function createWishlistUserAccount(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];

        $lastName = trim($requestBody['lastName'] ?? '');
        $firstName = trim($requestBody['firstName'] ?? '');
        $emailAddress = strtolower(trim($requestBody['emailAddress'] ?? ''));
        $idNumber = trim($requestBody['idNumber'] ?? '');
        $role = trim($requestBody['role'] ?? 'Student');
        $passwordText = (string)($requestBody['passwordText'] ?? $requestBody['password'] ?? '');

        if ($lastName === '' || $firstName === '' || $emailAddress === '' || $idNumber === '' || $role === '' || $passwordText === '') {
            return $this->createErrorResponse(
                'ValidationError',
                'Last name, first name, email, ID number, role, and password are required.',
                422
            );
        }

        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            return $this->createErrorResponse('ValidationError', 'Please provide a valid email address.', 422);
        }

        $existingEmailAccount = $this->findAccountConflictByEmail($emailAddress);

        if ($existingEmailAccount) {
            return $this->createErrorResponse(
                'DuplicateAccount',
                $this->buildDuplicateAccountMessage('email', $existingEmailAccount),
                409,
                ['conflict' => $this->normalizeAccountConflict($existingEmailAccount, 'email')]
            );
        }

        $existingIdNumberAccount = $this->findAccountConflictByIdNumber($idNumber);

        if ($existingIdNumberAccount) {
            return $this->createErrorResponse(
                'DuplicateIdNumber',
                $this->buildDuplicateAccountMessage('ID number', $existingIdNumberAccount),
                409,
                ['conflict' => $this->normalizeAccountConflict($existingIdNumberAccount, 'idNumber')]
            );
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $passwordHash = password_hash($passwordText, PASSWORD_BCRYPT);
        $roleLabel = strtolower($role) === 'faculty' ? 'Faculty' : 'Student';

        try {
            $this->connection->executeStatement(
                'INSERT INTO accounts
                    (last_name, first_name, email_address, role_designation, id_number, department,
                     contact_number, clerk_user_id, password_hash, status, is_approved, is_active,
                     failed_login_attempts, created_timestamp, updated_timestamp)
                 VALUES
                    (:lastName, :firstName, :emailAddress, :roleDesignation, :idNumber, :department,
                     :contactNumber, :clerkUserId, :passwordHash, :status, :isApproved, :isActive,
                     :failedLoginAttempts, :createdTimestamp, :updatedTimestamp)',
                [
                    'lastName' => $lastName,
                    'firstName' => $firstName,
                    'emailAddress' => $emailAddress,
                    'roleDesignation' => 'ROLE_BORROWER',
                    'idNumber' => $idNumber,
                    'department' => $roleLabel,
                    'contactNumber' => null,
                    'clerkUserId' => null,
                    'passwordHash' => $passwordHash,
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
                    'roleDesignation' => ParameterType::STRING,
                    'idNumber' => ParameterType::STRING,
                    'department' => ParameterType::STRING,
                    'contactNumber' => ParameterType::NULL,
                    'clerkUserId' => ParameterType::NULL,
                    'passwordHash' => ParameterType::STRING,
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
                'accountIdentifier' => $accountIdentifier,
                'idNumber' => $idNumber,
                'lastName' => $lastName,
                'firstName' => $firstName,
                'emailAddress' => $emailAddress,
                'roleDesignation' => 'ROLE_BORROWER',
                'roleLabel' => 'User: ' . $roleLabel,
                'accountType' => 'User',
                'accountStatus' => 'pending',
                'isApproved' => false,
                'registeredAt' => $now,
                'inviteSentAt' => null,
                'inviteExpiresAt' => null,
                'inviteAcceptedAt' => null,
            ], 201);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse(
                'CreateUserAccountFailed',
                'Failed to create user account: ' . $exception->getMessage(),
                500
            );
        }
    }

    #[Route('/signup-requests', name: 'create_public_signup_request', methods: ['POST'])]
    public function createPublicSignupRequest(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];

        $lastName = trim($requestBody['lastName'] ?? '');
        $firstName = trim($requestBody['firstName'] ?? '');
        $emailAddress = strtolower(trim($requestBody['emailAddress'] ?? ''));
        $idNumber = trim($requestBody['idNumber'] ?? '');
        $role = trim($requestBody['role'] ?? 'Student');
        $department = trim($requestBody['department'] ?? $role);
        $passwordText = (string)($requestBody['passwordText'] ?? $requestBody['password'] ?? '');
        $confirmPasswordText = (string)($requestBody['confirmPasswordText'] ?? $requestBody['confirmPassword'] ?? $passwordText);
        $acceptedPrivacy = (bool)($requestBody['acceptedPrivacy'] ?? false);

        if ($lastName === '' || $firstName === '' || $emailAddress === '' || $idNumber === '' || $department === '' || $role === '' || $passwordText === '') {
            return $this->createErrorResponse('ValidationError', 'All signup fields are required.', 422);
        }

        if (!$acceptedPrivacy) {
            return $this->createErrorResponse('ValidationError', 'Data privacy confirmation is required.', 422);
        }

        if (!preg_match('/^[A-Za-z][A-Za-z .\'-]*$/', $firstName) || !preg_match('/^[A-Za-z][A-Za-z .\'-]*$/', $lastName)) {
            return $this->createErrorResponse('ValidationError', 'Names may only contain letters, spaces, periods, apostrophes, and hyphens.', 422);
        }

        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL) || !str_ends_with($emailAddress, '@fit.edu.ph')) {
            return $this->createErrorResponse('ValidationError', 'Please use a valid @fit.edu.ph email address.', 422);
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $passwordText)) {
            return $this->createErrorResponse('ValidationError', 'Password must be at least 8 characters and include uppercase letters, lowercase letters, and numbers.', 422);
        }

        if ($passwordText !== $confirmPasswordText) {
            return $this->createErrorResponse('ValidationError', 'Passwords do not match.', 422);
        }

        $roleLabel = strtolower($role) === 'faculty' ? 'Faculty' : 'Student';

        $existingEmailAccount = $this->findAccountConflictByEmail($emailAddress);
        if ($existingEmailAccount) {
            if ($this->isReusablePendingSignupRequest($existingEmailAccount, $idNumber)) {
                return $this->createSuccessResponse([
                    'accountIdentifier' => (int)$existingEmailAccount['account_identifier'],
                    'idNumber' => (string)$existingEmailAccount['id_number'],
                    'lastName' => (string)$existingEmailAccount['last_name'],
                    'firstName' => (string)$existingEmailAccount['first_name'],
                    'emailAddress' => (string)$existingEmailAccount['email_address'],
                    'roleDesignation' => 'ROLE_BORROWER',
                    'roleLabel' => 'User: ' . $roleLabel,
                    'accountType' => 'User',
                    'accountStatus' => 'pending',
                    'isApproved' => false,
                    'registeredAt' => (string)($existingEmailAccount['created_timestamp'] ?? ''),
                    'reusedPendingRequest' => true,
                ]);
            }

            return $this->createErrorResponse(
                'DuplicateAccount',
                $this->buildDuplicateAccountMessage('email', $existingEmailAccount),
                409,
                ['conflict' => $this->normalizeAccountConflict($existingEmailAccount, 'email')]
            );
        }

        $existingIdNumberAccount = $this->findAccountConflictByIdNumber($idNumber);
        if ($existingIdNumberAccount) {
            return $this->createErrorResponse(
                'DuplicateIdNumber',
                $this->buildDuplicateAccountMessage('ID number', $existingIdNumberAccount),
                409,
                ['conflict' => $this->normalizeAccountConflict($existingIdNumberAccount, 'idNumber')]
            );
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $passwordHash = password_hash($passwordText, PASSWORD_BCRYPT);

        try {
            $this->connection->executeStatement(
                'INSERT INTO accounts
                    (last_name, first_name, email_address, role_designation, id_number, department,
                     contact_number, clerk_user_id, password_hash, status, is_approved, is_active,
                     failed_login_attempts, created_timestamp, updated_timestamp)
                 VALUES
                    (:lastName, :firstName, :emailAddress, :roleDesignation, :idNumber, :department,
                     :contactNumber, :clerkUserId, :passwordHash, :status, :isApproved, :isActive,
                     :failedLoginAttempts, :createdTimestamp, :updatedTimestamp)',
                [
                    'lastName' => $lastName,
                    'firstName' => $firstName,
                    'emailAddress' => $emailAddress,
                    'roleDesignation' => 'ROLE_BORROWER',
                    'idNumber' => $idNumber,
                    'department' => $roleLabel,
                    'contactNumber' => null,
                    'clerkUserId' => null,
                    'passwordHash' => $passwordHash,
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
                    'roleDesignation' => ParameterType::STRING,
                    'idNumber' => ParameterType::STRING,
                    'department' => ParameterType::STRING,
                    'contactNumber' => ParameterType::NULL,
                    'clerkUserId' => ParameterType::NULL,
                    'passwordHash' => ParameterType::STRING,
                    'status' => ParameterType::STRING,
                    'isApproved' => ParameterType::BOOLEAN,
                    'isActive' => ParameterType::BOOLEAN,
                    'failedLoginAttempts' => ParameterType::INTEGER,
                    'createdTimestamp' => ParameterType::STRING,
                    'updatedTimestamp' => ParameterType::STRING,
                ]
            );

            return $this->createSuccessResponse([
                'accountIdentifier' => (int)$this->connection->lastInsertId(),
                'idNumber' => $idNumber,
                'lastName' => $lastName,
                'firstName' => $firstName,
                'emailAddress' => $emailAddress,
                'roleDesignation' => 'ROLE_BORROWER',
                'roleLabel' => 'User: ' . $roleLabel,
                'accountType' => 'User',
                'accountStatus' => 'pending',
                'isApproved' => false,
                'registeredAt' => $now,
            ], 201);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse(
                'CreateSignupRequestFailed',
                'Failed to create signup request: ' . $exception->getMessage(),
                500
            );
        }
    }

    #[Route('/wishlist/employee-accounts', name: 'create_wishlist_employee_account', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function createWishlistEmployeeAccount(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];

        $lastName = trim($requestBody['lastName'] ?? '');
        $firstName = trim($requestBody['firstName'] ?? '');
        $emailAddress = strtolower(trim($requestBody['emailAddress'] ?? ''));
        $phone = trim($requestBody['phone'] ?? $requestBody['contactNumber'] ?? '');
        $idNumber = trim($requestBody['idNumber'] ?? '');
        $role = trim($requestBody['role'] ?? 'Maintenance Staff');
        $passwordText = (string)($requestBody['passwordText'] ?? $requestBody['password'] ?? '');

        if ($lastName === '' || $firstName === '' || $emailAddress === '' || $phone === '' || $idNumber === '' || $role === '' || $passwordText === '') {
            return $this->createErrorResponse(
                'ValidationError',
                'Last name, first name, email, phone, ID number, role, and password are required.',
                422
            );
        }

        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            return $this->createErrorResponse('ValidationError', 'Please provide a valid email address.', 422);
        }

        $existingEmailAccount = $this->findAccountConflictByEmail($emailAddress);

        if ($existingEmailAccount) {
            return $this->createErrorResponse(
                'DuplicateAccount',
                $this->buildDuplicateAccountMessage('email', $existingEmailAccount),
                409,
                ['conflict' => $this->normalizeAccountConflict($existingEmailAccount, 'email')]
            );
        }

        $existingIdNumberAccount = $this->findAccountConflictByIdNumber($idNumber);

        if ($existingIdNumberAccount) {
            return $this->createErrorResponse(
                'DuplicateIdNumber',
                $this->buildDuplicateAccountMessage('ID number', $existingIdNumberAccount),
                409,
                ['conflict' => $this->normalizeAccountConflict($existingIdNumberAccount, 'idNumber')]
            );
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $passwordHash = password_hash($passwordText, PASSWORD_BCRYPT);

        try {
            $this->connection->executeStatement(
                'INSERT INTO accounts
                    (last_name, first_name, email_address, role_designation, id_number, department,
                     contact_number, clerk_user_id, password_hash, status, is_approved, is_active,
                     failed_login_attempts, created_timestamp, updated_timestamp)
                 VALUES
                    (:lastName, :firstName, :emailAddress, :roleDesignation, :idNumber, :department,
                     :contactNumber, :clerkUserId, :passwordHash, :status, :isApproved, :isActive,
                     :failedLoginAttempts, :createdTimestamp, :updatedTimestamp)',
                [
                    'lastName' => $lastName,
                    'firstName' => $firstName,
                    'emailAddress' => $emailAddress,
                    'roleDesignation' => 'ROLE_STAFF',
                    'idNumber' => $idNumber,
                    'department' => $role,
                    'contactNumber' => $phone,
                    'clerkUserId' => null,
                    'passwordHash' => $passwordHash,
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
                    'roleDesignation' => ParameterType::STRING,
                    'idNumber' => ParameterType::STRING,
                    'department' => ParameterType::STRING,
                    'contactNumber' => ParameterType::STRING,
                    'clerkUserId' => ParameterType::NULL,
                    'passwordHash' => ParameterType::STRING,
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
                'accountIdentifier' => $accountIdentifier,
                'idNumber' => $idNumber,
                'lastName' => $lastName,
                'firstName' => $firstName,
                'emailAddress' => $emailAddress,
                'contactNumber' => $phone,
                'roleDesignation' => 'ROLE_STAFF',
                'roleLabel' => $role,
                'accountType' => 'Employee',
                'accountStatus' => 'pending',
                'isApproved' => false,
                'registeredAt' => $now,
                'inviteSentAt' => null,
                'inviteExpiresAt' => null,
                'inviteAcceptedAt' => null,
            ], 201);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse(
                'CreateEmployeeAccountFailed',
                'Failed to create employee account: ' . $exception->getMessage(),
                500
            );
        }
    }

    #[Route('/{accountIdentifier}/approve', name: 'approve_user', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function approveUser(Request $request, int $accountIdentifier): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $confirmedAdminEmail = strtolower(trim((string)($requestBody['confirmedAdminEmail'] ?? '')));
        $authenticatedIdentity = $request->attributes->get('authenticatedIdentity', []);
        $invitedBy = strtolower(trim((string)($authenticatedIdentity['emailAddress'] ?? '')));

        if ($confirmedAdminEmail === '' || $invitedBy === '' || $confirmedAdminEmail !== $invitedBy) {
            return $this->createErrorResponse(
                'SecurityConfirmationFailed',
                'Please type the responsible admin email before sending the invite.',
                422
            );
        }

        $account = $this->connection->fetchAssociative(
            "SELECT account_identifier, email_address, role_designation, first_name, last_name,
                    department, id_number, status, is_approved
             FROM accounts
             WHERE account_identifier = :accountIdentifier
               AND COALESCE(is_approved, FALSE) = FALSE
               AND LOWER(COALESCE(status, 'pending')) NOT IN ('approved', 'rejected', 'disabled')",
            ['accountIdentifier' => $accountIdentifier],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        if (!$account) {
            return $this->createErrorResponse(
                'WishlistAccountNotFound',
                'This email is not in the wishlist database or is no longer eligible for invitation.',
                404
            );
        }

        $now = new \DateTimeImmutable();
        $expiresAt = $now->modify('+7 days');
        $invitationToken = bin2hex(random_bytes(24));
        $frontendUrl = rtrim((string)($_ENV['FRONTEND_URL'] ?? 'http://localhost:5173'), '/');
        $redirectUrl = $frontendUrl . '/sign-up';
        $useBrandedMailer = !$this->isNullMailerDsn();

        try {
            $clerkInvitation = $this->sendClerkInvitation($account, $redirectUrl, !$useBrandedMailer);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse(
                'ClerkInvitationFailed',
                'Clerk could not send the invitation: ' . $exception->getMessage(),
                502
            );
        }

        $clerkInvitationUrl = (string)($clerkInvitation['url'] ?? '');
        if ($useBrandedMailer && $clerkInvitationUrl === '') {
            return $this->createErrorResponse(
                'ClerkInvitationMissingUrl',
                'Clerk created the invitation but did not return an invitation URL.',
                502
            );
        }

        if ($useBrandedMailer) {
            $emailResult = $this->sendAcceptedAccountEmail($account, $clerkInvitationUrl);
            if (!$emailResult['sent']) {
                return $this->createErrorResponse(
                    'InvitationEmailFailed',
                    'Clerk created the invitation, but the Outlook email could not be sent: ' . $emailResult['error'],
                    502
                );
            }
        }

        $this->connection->beginTransaction();

        try {
            $this->connection->executeStatement(
                'UPDATE accounts
                 SET status = :status, is_approved = :isApproved, updated_timestamp = :updatedTimestamp
                 WHERE account_identifier = :accountIdentifier',
                [
                    'status' => 'invited',
                    'isApproved' => false,
                    'updatedTimestamp' => $now->format('Y-m-d H:i:s'),
                    'accountIdentifier' => $accountIdentifier,
                ],
                [
                    'status' => ParameterType::STRING,
                    'isApproved' => ParameterType::BOOLEAN,
                    'updatedTimestamp' => ParameterType::STRING,
                    'accountIdentifier' => ParameterType::INTEGER,
                ]
            );

            $this->connection->executeStatement(
                'INSERT INTO invitations
                    (email, invited_by, organization, invitation_token, status, expires_at, created_at, accepted_at)
                 VALUES
                    (:email, :invitedBy, :organization, :invitationToken, :status, :expiresAt, :createdAt, :acceptedAt)',
                [
                    'email' => (string)$account['email_address'],
                    'invitedBy' => $invitedBy,
                    'organization' => 'TechReserve',
                    'invitationToken' => $invitationToken,
                    'status' => 'pending',
                    'expiresAt' => $expiresAt->format('Y-m-d H:i:sP'),
                    'createdAt' => $now->format('Y-m-d H:i:sP'),
                    'acceptedAt' => null,
                ],
                [
                    'email' => ParameterType::STRING,
                    'invitedBy' => ParameterType::STRING,
                    'organization' => ParameterType::STRING,
                    'invitationToken' => ParameterType::STRING,
                    'status' => ParameterType::STRING,
                    'expiresAt' => ParameterType::STRING,
                    'createdAt' => ParameterType::STRING,
                    'acceptedAt' => ParameterType::NULL,
                ]
            );

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            return $this->createErrorResponse(
                'ApproveAccountFailed',
                'Clerk sent the invitation, but the database could not record it: ' . $exception->getMessage(),
                500
            );
        }

        return $this->createSuccessResponse([
            'message' => 'Invitation sent successfully.',
            'account' => [
                'accountIdentifier' => (int)$account['account_identifier'],
                'emailAddress' => (string)$account['email_address'],
                'roleDesignation' => (string)$account['role_designation'],
                'status' => 'invited',
                'isApproved' => false,
            ],
            'invitation' => [
                'emailAddress' => (string)$account['email_address'],
                'role' => (string)$account['role_designation'],
                'status' => 'pending',
                'token' => $invitationToken,
                'clerkInvitationId' => $clerkInvitation['id'] ?? null,
                'sentAt' => $now->format('Y-m-d\TH:i:sP'),
                'expiresAt' => $expiresAt->format('Y-m-d\TH:i:sP'),
                'acceptedAt' => null,
                'redirectUrl' => $redirectUrl,
                'invitationUrl' => $clerkInvitationUrl,
                'sentBy' => $invitedBy,
                'emailSent' => true,
            ],
        ]);
    }

    #[Route('/{accountIdentifier}/reject', name: 'reject_user', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function rejectUser(int $accountIdentifier): JsonResponse
    {
        $account = $this->accountRepository->find($accountIdentifier);

        if ($account === null) {
            return $this->createErrorResponse(
                'UserNotFound',
                'User not found.',
                404
            );
        }

        $account->setStatus('rejected');
        $account->setIsApproved(false);
        $this->accountRepository->persistAccount($account);

        return $this->createSuccessResponse([
            'message' => 'User rejected successfully.',
            'account' => [
                'accountIdentifier' => $account->getAccountIdentifier(),
                'status' => $account->getStatus(),
                'isApproved' => $account->getIsApproved(),
            ],
        ]);
    }

    #[Route('/invite', name: 'invite_user', methods: ['POST'])]
    public function inviteUser(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];

        $emailAddress = trim($requestBody['emailAddress'] ?? '');
        $role = trim($requestBody['role'] ?? 'ROLE_BORROWER');
        $invitedBy = $requestBody['invitedBy'] ?? null;

        if (empty($emailAddress)) {
            return $this->createErrorResponse(
                'ValidationError',
                'emailAddress is required.',
                400
            );
        }

        // Check if user already exists
        $existingAccount = $this->accountRepository->findOneByEmailAddress($emailAddress);
        if ($existingAccount !== null) {
            return $this->createErrorResponse(
                'DuplicateAccount',
                'An account with this email address already exists.',
                409
            );
        }

        // In a real implementation, you would:
        // 1. Generate an invitation token
        // 2. Send an email with Clerk invitation link
        // 3. Store the invitation in the database

        // For now, return success with invitation details
        return $this->createSuccessResponse([
            'message' => 'Invitation sent successfully.',
            'invitation' => [
                'emailAddress' => $emailAddress,
                'role' => $role,
                'invitedBy' => $invitedBy,
                'status' => 'invited',
                'sentAt' => (new \DateTimeImmutable())->format('Y-m-d\TH:i:sP'),
                'expiresAt' => (new \DateTimeImmutable('+7 days'))->format('Y-m-d\TH:i:sP'),
            ],
        ]);
    }

    private function isAdminEmail(string $emailAddress): bool
    {
        return in_array(strtolower(trim($emailAddress)), self::ADMIN_EMAIL_ALLOWLIST, true);
    }

    private function sendClerkInvitation(array $account, string $redirectUrl, bool $notify): array
    {
        $clerkSecretKey = trim((string)($_ENV['CLERK_SECRET_KEY'] ?? ''));
        if ($clerkSecretKey === '') {
            throw new \RuntimeException('CLERK_SECRET_KEY is not configured.');
        }

        $clerkApiBaseUrl = rtrim((string)($_ENV['CLERK_API_BASE_URL'] ?? 'https://api.clerk.com'), '/');
        $emailAddress = (string)($account['email_address'] ?? '');
        $accountIdentifier = (int)($account['account_identifier'] ?? 0);

        $response = $this->httpClient->request('POST', $clerkApiBaseUrl . '/v1/invitations', [
            'headers' => [
                'Authorization' => 'Bearer ' . $clerkSecretKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'email_address' => $emailAddress,
                'redirect_url' => $redirectUrl,
                'notify' => $notify,
                'ignore_existing' => true,
                'expires_in_days' => 7,
                'public_metadata' => [
                    'techreserve_account_identifier' => $accountIdentifier,
                    'techreserve_role_designation' => (string)($account['role_designation'] ?? ''),
                    'techreserve_id_number' => (string)($account['id_number'] ?? ''),
                    'techreserve_department' => (string)($account['department'] ?? ''),
                ],
            ],
        ]);

        $statusCode = $response->getStatusCode();
        $payload = $response->toArray(false);

        if ($statusCode < 200 || $statusCode >= 300) {
            $message = $payload['errors'][0]['long_message']
                ?? $payload['errors'][0]['message']
                ?? $payload['message']
                ?? 'Clerk invitation request failed.';
            throw new \RuntimeException($message);
        }

        return $payload;
    }

    private function isNullMailerDsn(): bool
    {
        $mailerDsn = strtolower(trim((string)($_ENV['MAILER_DSN'] ?? '')));
        return $mailerDsn === '' || str_starts_with($mailerDsn, 'null://');
    }

    private function sendAcceptedAccountEmail(array $account, string $loginUrl): array
    {
        $emailAddress = (string)($account['email_address'] ?? '');
        $roleDesignation = (string)($account['role_designation'] ?? '');
        $department = (string)($account['department'] ?? '');
        $accountType = $this->resolveAcceptedEmailAccountType($roleDesignation, $department);
        $recipientName = trim((string)($account['first_name'] ?? '') . ' ' . (string)($account['last_name'] ?? ''));
        $recipientName = $recipientName !== '' ? $recipientName : $emailAddress;
        $subject = match ($accountType) {
            'Admin' => 'Welcome to TechReserve, Admin! Your Account is Verified and Ready to Use',
            'Employee' => 'Welcome to TechReserve, Employee! Your Account is Verified and Ready to Use',
            default => 'Your TechReserve Account is Verified and Ready to Use!',
        };

        try {
            $email = (new Email())
                ->from($_ENV['MAILER_FROM'] ?? 'noreply@techreserve.feutech.edu.ph')
                ->to($emailAddress)
                ->subject($subject)
                ->html($this->buildAcceptedAccountEmailHtml($recipientName, $loginUrl, $accountType));

            $this->mailer->send($email);

            return ['sent' => true, 'error' => null];
        } catch (\Throwable $exception) {
            return ['sent' => false, 'error' => $exception->getMessage()];
        }
    }

    private function buildAcceptedAccountEmailHtml(string $recipientName, string $loginUrl, string $accountType): string
    {
        $name = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');
        $isEmployee = $accountType === 'Employee';
        $isAdmin = $accountType === 'Admin';
        $headline = $isAdmin || $isEmployee
            ? 'Great news! Your account is<br>verified and ready to use.'
            : 'Great news,<br>your account is<br>verified and ready<br>to use!';
        $welcomePill = $isAdmin ? 'WELCOME, ADMIN!' : ($isEmployee ? 'HELLO!' : '');
        $intro = match ($accountType) {
            'Admin' => 'Your administrator account for TechReserve has been successfully verified.',
            'Employee' => 'Your employee account for TechReserve has been successfully verified.',
            default => 'We are happy to inform you that your account in TechReserve has been successfully verified.',
        };
        $body = match ($accountType) {
            'Admin' => 'You can now log in to the system to manage reservations, monitor resources, generate reports, and configure system settings with ease.',
            'Employee' => 'You can now log in to the system to view your tasks, manage reservations, and collaborate with your team.',
            default => 'You can now log in to your account and start reserving equipment or venues with ease.',
        };
        $features = match ($accountType) {
            'Admin' => [
                ['label' => 'Manage Reservations', 'text' => 'Review, approve, and manage all reservation requests.', 'icon' => 'MR'],
                ['label' => 'Monitor Resources', 'text' => 'View availability and usage of equipment and venues.', 'icon' => 'MO'],
                ['label' => 'Analytics & Reports', 'text' => 'Access insights and reports for decision-making.', 'icon' => 'AR'],
                ['label' => 'System Management', 'text' => 'Configure settings and manage administration.', 'icon' => 'SM'],
            ],
            'Employee' => [
                ['label' => 'View Assignments', 'text' => 'See tasks assigned to you and track your progress.', 'icon' => 'VA'],
                ['label' => 'Manage Reservations', 'text' => 'Check reservation details and related schedules.', 'icon' => 'MR'],
                ['label' => 'Stay Updated', 'text' => 'Receive notifications and important announcements.', 'icon' => 'SU'],
                ['label' => 'Work Together', 'text' => 'Coordinate with your team efficiently and effectively.', 'icon' => 'WT'],
            ],
            default => [
                ['label' => 'Reserve Venues', 'text' => '', 'icon' => 'RV'],
                ['label' => 'Reserve Equipment', 'text' => '', 'icon' => 'RE'],
                ['label' => 'Track Your Reservations', 'text' => '', 'icon' => 'TR'],
                ['label' => 'Get Real-time Updates', 'text' => '', 'icon' => 'RU'],
            ],
        };

        $featureCells = '';
        foreach ($features as $feature) {
            $featureCells .= $this->buildAcceptedEmailFeatureCell($feature['label'], $feature['text'], $feature['icon']);
        }

        $heroLabel = $isAdmin ? 'ADMIN VERIFIED' : ($isEmployee ? 'EMPLOYEE VERIFIED' : 'ACCOUNT VERIFIED');
        $heroVisual = '<td width="45%" align="center" style="padding:18px 20px;"><table role="presentation" width="230" cellpadding="0" cellspacing="0" style="width:230px;background:#eef8f1;border:1px solid #d8eadf;"><tr><td align="center" style="padding:24px 12px;"><table role="presentation" cellpadding="0" cellspacing="0"><tr><td align="center" width="68" height="68" style="width:68px;height:68px;background:#07834f;color:#ffffff;font-size:20px;font-weight:900;line-height:68px;">OK</td></tr></table><div style="font-size:12px;font-weight:800;color:#103f2b;margin-top:12px;">' . $heroLabel . '</div></td></tr></table></td>';
        $welcomeRow = $welcomePill !== ''
            ? '<tr><td align="center" style="padding:18px 34px 0;"><span style="display:inline-block;background:#dff3e8;color:#007a4d;padding:6px 18px;border-radius:999px;font-size:11px;font-weight:900;">' . $welcomePill . '</span></td></tr>'
            : '';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechReserve Account Verified</title>
</head>
<body style="margin:0;padding:0;background:#f6f8f7;font-family:Arial,Helvetica,sans-serif;color:#0f1f1a;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f8f7;padding:28px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="680" cellpadding="0" cellspacing="0" style="width:680px;max-width:100%;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 14px 36px rgba(6,56,34,0.14);">
          <tr>
            <td align="center" style="padding:24px 28px;border-bottom:1px solid #e2e8e5;">
              <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="width:54px;height:54px;border-radius:16px;background:#007a4d;color:#f6b801;text-align:center;font-size:24px;font-weight:900;">TR</td>
                  <td style="padding-left:12px;text-align:left;">
                    <div style="font-size:25px;font-weight:900;line-height:1;"><span style="color:#007a4d;">Tech</span><span style="color:#f6b801;">Reserve</span></div>
                    <div style="font-size:10px;letter-spacing:.8px;font-weight:800;color:#24332e;margin-top:4px;">EQUIPMENT &amp; VENUE RESERVATION SYSTEM</div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:0;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="55%" style="padding:34px 34px 22px;">
                    <div style="font-size:29px;line-height:1.1;font-weight:900;color:#073b2a;">{$headline}</div>
                  </td>
                  {$heroVisual}
                </tr>
              </table>
            </td>
          </tr>
          {$welcomeRow}
          <tr>
            <td style="padding:0 34px 10px;">
              <div style="font-size:15px;font-weight:900;color:#007a4d;margin-bottom:10px;">Hello {$name},</div>
              <div style="font-size:13px;line-height:1.7;color:#17221e;">{$intro}</div>
              <div style="font-size:13px;line-height:1.7;color:#17221e;margin-top:2px;">{$body}</div>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:16px 34px 14px;">
              <a href="{$url}" style="display:inline-block;min-width:210px;background:#007a4d;color:#ffffff;text-decoration:none;text-align:center;padding:13px 22px;border-radius:6px;font-size:14px;font-weight:900;">Log in to TechReserve</a>
            </td>
          </tr>
          <tr>
            <td style="padding:6px 34px 26px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #e2e8e5;">
                <tr>
                  {$featureCells}
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background:#00633f;color:#ffffff;padding:20px 28px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="font-size:12px;line-height:1.6;">
                    <div style="color:#f6b801;font-weight:900;">Need help?</div>
                    <div>Contact the System Administrator</div>
                    <div>techreserve@feutech.edu.ph</div>
                  </td>
                  <td align="right" style="font-size:12px;line-height:1.6;">
                    <div>Thank you,</div>
                    <div style="color:#f6b801;font-weight:900;">The TechReserve Team</div>
                    <div>FEU Institute of Technology</div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:14px;color:#6b7280;font-size:11px;">This is an automated message. Please do not reply to this email.</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    private function buildAcceptedEmailFeatureCell(string $label, string $text, string $icon): string
    {
        $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $safeText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $safeIcon = htmlspecialchars($icon, ENT_QUOTES, 'UTF-8');
        $description = $safeText !== '' ? '<div style="font-size:10px;line-height:1.4;color:#52605b;margin-top:5px;">' . $safeText . '</div>' : '';

        return <<<HTML
<td width="25%" align="center" valign="top" style="padding:16px 10px;border-right:1px solid #e2e8e5;">
  <div style="width:46px;height:46px;border-radius:50%;background:#dff3e8;color:#007a4d;line-height:46px;font-size:18px;font-weight:900;margin:0 auto 8px;">{$safeIcon}</div>
  <div style="font-size:11px;line-height:1.25;font-weight:900;color:#10231d;">{$safeLabel}</div>
  {$description}
</td>
HTML;
    }

    private function resolveAcceptedEmailAccountType(string $roleDesignation, string $department): string
    {
        $role = strtoupper($roleDesignation);

        if (str_contains($role, 'ADMIN')) {
            return 'Admin';
        }

        if ($this->isEmployeeAccount($roleDesignation, $department)) {
            return 'Employee';
        }

        return 'User';
    }

    private function isEmployeeAccount(string $roleDesignation, string $department): bool
    {
        $role = strtoupper($roleDesignation);
        $normalizedDepartment = strtolower($department);

        return str_contains($role, 'STAFF')
            || str_contains($role, 'EMPLOYEE')
            || str_contains($normalizedDepartment, 'staff')
            || str_contains($normalizedDepartment, 'employee')
            || str_contains($normalizedDepartment, 'technical')
            || str_contains($normalizedDepartment, 'maintenance')
            || str_contains($normalizedDepartment, 'support');
    }

    private function resolveRole(string $requestedRole, string $emailAddress): string
    {
        if ($this->isAdminEmail($emailAddress)) {
            return 'ROLE_ADMIN';
        }

        $role = strtoupper(trim($requestedRole));
        if ($role === 'ADMIN' || $role === 'ROLE_ADMIN') {
            return 'ROLE_ADMIN';
        }
        if ($role === 'BORROWER' || $role === 'ROLE_BORROWER') {
            return 'ROLE_BORROWER';
        }

        return str_starts_with($role, 'ROLE_') ? $role : 'ROLE_BORROWER';
    }

    private function findAccountConflictByEmail(string $emailAddress): ?array
    {
        $account = $this->connection->fetchAssociative(
            'SELECT account_identifier, id_number, first_name, last_name, email_address, role_designation,
                    department, status, is_approved, is_active, clerk_user_id, created_timestamp
             FROM accounts
             WHERE LOWER(email_address) = LOWER(:emailAddress)
             LIMIT 1',
            ['emailAddress' => $emailAddress],
            ['emailAddress' => ParameterType::STRING]
        );

        return $account ?: null;
    }

    private function findAccountConflictByIdNumber(string $idNumber): ?array
    {
        $account = $this->connection->fetchAssociative(
            'SELECT account_identifier, id_number, first_name, last_name, email_address, role_designation,
                    department, status, is_approved, is_active, clerk_user_id, created_timestamp
             FROM accounts
             WHERE id_number = :idNumber
             LIMIT 1',
            ['idNumber' => $idNumber],
            ['idNumber' => ParameterType::STRING]
        );

        return $account ?: null;
    }

    private function isReusablePendingSignupRequest(array $account, string $idNumber): bool
    {
        $isApproved = $this->toDatabaseBoolean($account['is_approved'] ?? false);
        $status = strtolower((string)($account['status'] ?? ''));
        $clerkUserId = trim((string)($account['clerk_user_id'] ?? ''));
        $existingIdNumber = trim((string)($account['id_number'] ?? ''));

        return !$isApproved
            && $status === 'pending'
            && $clerkUserId === ''
            && $existingIdNumber === $idNumber;
    }

    private function buildDuplicateAccountMessage(string $fieldName, array $account): string
    {
        $fullName = trim((string)($account['first_name'] ?? '') . ' ' . (string)($account['last_name'] ?? ''));
        $status = $this->formatConflictStatus(
            (string)($account['status'] ?? 'pending'),
            $this->toDatabaseBoolean($account['is_approved'] ?? false)
        );
        $accountType = $this->resolveConflictAccountType($account);

        return sprintf(
            'An account with this %s already exists: %s (%s, %s, %s). Check Manage Accounts or switch Wishlist filters.',
            $fieldName,
            $fullName !== '' ? $fullName : (string)($account['email_address'] ?? 'Unknown account'),
            (string)($account['email_address'] ?? 'No email'),
            $accountType,
            $status
        );
    }

    private function normalizeAccountConflict(array $account, string $matchedField): array
    {
        return [
            'matchedField' => $matchedField,
            'accountIdentifier' => (int)($account['account_identifier'] ?? 0),
            'idNumber' => $account['id_number'] ?? null,
            'firstName' => (string)($account['first_name'] ?? ''),
            'lastName' => (string)($account['last_name'] ?? ''),
            'emailAddress' => (string)($account['email_address'] ?? ''),
            'accountType' => $this->resolveConflictAccountType($account),
            'status' => (string)($account['status'] ?? 'pending'),
            'isApproved' => $this->toDatabaseBoolean($account['is_approved'] ?? false),
            'isActive' => $this->toDatabaseBoolean($account['is_active'] ?? false),
        ];
    }

    private function resolveConflictAccountType(array $account): string
    {
        $roleDesignation = strtoupper((string)($account['role_designation'] ?? ''));
        $department = strtolower((string)($account['department'] ?? ''));

        if (str_contains($roleDesignation, 'ADMIN')) {
            return 'Admin';
        }

        if (
            str_contains($roleDesignation, 'STAFF') ||
            str_contains($roleDesignation, 'EMPLOYEE') ||
            str_contains($department, 'staff') ||
            str_contains($department, 'employee') ||
            str_contains($department, 'technical') ||
            str_contains($department, 'maintenance')
        ) {
            return 'Employee';
        }

        return 'User';
    }

    private function formatConflictStatus(string $status, bool $isApproved): string
    {
        $normalizedStatus = strtolower($status);

        if ($isApproved || $normalizedStatus === 'approved') {
            return 'Verified';
        }

        if ($normalizedStatus === 'rejected') {
            return 'Denied';
        }

        if ($normalizedStatus === 'invited') {
            return 'Invite Sent';
        }

        return 'Unverified';
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
        return in_array($normalized, ['1', 't', 'true', 'yes'], true);
    }
}
