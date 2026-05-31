<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\Repository\AccountRepository;
use App\Domain\Account\Entity\AccountEntity;
use App\Domain\Account\Service\AccountConflictLookupService;
use App\Domain\Account\Service\AccountInputValidationService;
use App\Domain\Account\Service\Wishlist\WishlistAccountReadService;
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

    private const DEFAULT_ADMIN_PASSWORD = 'admin123';

    private const ADMIN_EMAIL_ALLOWLIST = [
        'smmojica@fit.edu.ph',
    ];

    private AccountRepository $accountRepository;
    private HttpClientInterface $httpClient;
    private Connection $connection;
    private MailerInterface $mailer;
    private WishlistAccountReadService $wishlistAccountReadService;
    private AccountConflictLookupService $accountConflictLookupService;
    private AccountInputValidationService $accountInputValidationService;

    public function __construct(
        AccountRepository $accountRepository,
        HttpClientInterface $httpClient,
        Connection $connection,
        MailerInterface $mailer,
        WishlistAccountReadService $wishlistAccountReadService,
        AccountConflictLookupService $accountConflictLookupService,
        AccountInputValidationService $accountInputValidationService
    )
    {
        $this->accountRepository = $accountRepository;
        $this->httpClient = $httpClient;
        $this->connection = $connection;
        $this->mailer = $mailer;
        $this->wishlistAccountReadService = $wishlistAccountReadService;
        $this->accountConflictLookupService = $accountConflictLookupService;
        $this->accountInputValidationService = $accountInputValidationService;
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
            $existingRole = strtoupper(trim($existingEmailAccount->getRoleDesignation()));
            $existingIsAdmin = in_array($existingRole, ['ADMIN', 'ROLE_ADMIN'], true);
            $latestInvitation = $this->findLatestInvitationForEmail($emailAddress);
            $hasAcceptableInvitation = $this->isAcceptableInvitation($latestInvitation);
            $nextRole = $existingIsAdmin ? 'ROLE_ADMIN' : $role;
            $nextIsApproved = $existingIsApproved || $isApproved || $existingIsAdmin || $hasAcceptableInvitation;
            $nextIsActive = $nextIsApproved ? $existingIsActive : true;
            $nextStatus = $nextIsApproved ? ($nextIsActive ? 'approved' : 'disabled') : $existingStatus;
            if ($nextStatus === '') {
                $nextStatus = $status;
            }

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
                    'roleDesignation' => $nextRole,
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

            if ($hasAcceptableInvitation && $nextIsApproved) {
                $this->markLatestInvitationAccepted((string)$emailAddress, $now);
            }

            return $this->createSuccessResponse([
                'message' => 'Account linked to Clerk successfully.',
                'account' => [
                    'accountIdentifier' => $existingEmailAccount->getAccountIdentifier(),
                    'clerkUserId' => $clerkUserId,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'emailAddress' => $emailAddress,
                    'roleDesignation' => $nextRole,
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

        $profilePhotoData = $this->connection->fetchOne(
            'SELECT profile_photo_data FROM accounts WHERE account_identifier = :accountIdentifier',
            ['accountIdentifier' => $account->getAccountIdentifier()]
        );

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
                'profilePhotoData'  => $profilePhotoData ? (string)$profilePhotoData : null,
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
        $users = $this->wishlistAccountReadService->getWishlistAccounts();

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

        if ($lastName === '' || $firstName === '' || $emailAddress === '' || $idNumber === '') {
            return $this->createErrorResponse(
                'ValidationError',
                'Last name, first name, email, and ID number are required.',
                422
            );
        }

        if (!$this->accountInputValidationService->isValidPersonName($lastName)) {
            return $this->createErrorResponse(
                'ValidationError',
                'Last name must have at least 2 letters and cannot contain numbers or symbols.',
                422
            );
        }

        if (!$this->accountInputValidationService->isValidPersonName($firstName)) {
            return $this->createErrorResponse(
                'ValidationError',
                'First name must have at least 2 letters and cannot contain numbers or symbols.',
                422
            );
        }

        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            return $this->createErrorResponse('ValidationError', 'Please provide a valid email address.', 422);
        }

        if (!$this->accountInputValidationService->isInstitutionalAdminEmail($emailAddress)) {
            return $this->createErrorResponse(
                'ValidationError',
                'Admin account must use a valid institutional email address.',
                422
            );
        }

        $lastName = $this->accountInputValidationService->normalizePersonName($lastName);
        $firstName = $this->accountInputValidationService->normalizePersonName($firstName);

        $existingEmailAccount = $this->accountConflictLookupService->findByEmail($emailAddress);

        if ($existingEmailAccount) {
            return $this->createErrorResponse(
                'DuplicateAccount',
                $this->accountConflictLookupService->buildDuplicateAccountMessage('email', $existingEmailAccount),
                409,
                ['conflict' => $this->accountConflictLookupService->normalizeConflict($existingEmailAccount, 'email')]
            );
        }

        $existingIdNumberAccount = $this->accountConflictLookupService->findByIdNumber($idNumber);

        if ($existingIdNumberAccount) {
            return $this->createErrorResponse(
                'DuplicateIdNumber',
                $this->accountConflictLookupService->buildDuplicateAccountMessage('ID number', $existingIdNumberAccount),
                409,
                ['conflict' => $this->accountConflictLookupService->normalizeConflict($existingIdNumberAccount, 'idNumber')]
            );
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $defaultAdminPassword = (string)($_ENV['DEFAULT_ADMIN_PASSWORD'] ?? self::DEFAULT_ADMIN_PASSWORD);
        $defaultAdminPasswordHash = password_hash($defaultAdminPassword, PASSWORD_BCRYPT);

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
                    'passwordHash' => $defaultAdminPasswordHash,
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
            $this->upsertStaffInfo($accountIdentifier, $idNumber, $firstName, $lastName, $phone, $role, null);

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
                'hasDefaultPassword' => true,
                'defaultPasswordLabel' => $defaultAdminPassword,
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

        $existingEmailAccount = $this->accountConflictLookupService->findByEmail($emailAddress);

        if ($existingEmailAccount) {
            return $this->createErrorResponse(
                'DuplicateAccount',
                $this->accountConflictLookupService->buildDuplicateAccountMessage('email', $existingEmailAccount),
                409,
                ['conflict' => $this->accountConflictLookupService->normalizeConflict($existingEmailAccount, 'email')]
            );
        }

        $existingIdNumberAccount = $this->accountConflictLookupService->findByIdNumber($idNumber);

        if ($existingIdNumberAccount) {
            return $this->createErrorResponse(
                'DuplicateIdNumber',
                $this->accountConflictLookupService->buildDuplicateAccountMessage('ID number', $existingIdNumberAccount),
                409,
                ['conflict' => $this->accountConflictLookupService->normalizeConflict($existingIdNumberAccount, 'idNumber')]
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
        $supportingDocumentName = trim((string)($requestBody['supportingDocumentName'] ?? ''));
        $supportingDocumentMimeType = trim((string)($requestBody['supportingDocumentMimeType'] ?? ''));
        $supportingDocumentData = trim((string)($requestBody['supportingDocumentData'] ?? ''));

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

        if ($roleLabel === 'Student' && $supportingDocumentName === '') {
            return $this->createErrorResponse('ValidationError', 'PDF proof is required for student signup requests.', 422);
        }

        if ($roleLabel === 'Student' && !$this->isPdfSupportingDocument($supportingDocumentName, $supportingDocumentMimeType, $supportingDocumentData)) {
            return $this->createErrorResponse('ValidationError', 'Student proof must be uploaded as a PDF file.', 422);
        }

        if ($supportingDocumentName !== '' && $supportingDocumentData === '') {
            return $this->createErrorResponse('ValidationError', 'Supporting file data is missing.', 422);
        }

        if ($supportingDocumentData !== '' && strlen($supportingDocumentData) > 7000000) {
            return $this->createErrorResponse('ValidationError', 'Supporting file is too large. Please upload a file up to 5 MB.', 422);
        }

        $existingEmailAccount = $this->accountConflictLookupService->findByEmail($emailAddress);
        if ($existingEmailAccount) {
            if ($this->isReusablePendingSignupRequest($existingEmailAccount, $idNumber)) {
                $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
                try {
                    $clerkUserId = $this->ensureClerkSignupUser($emailAddress, $firstName, $lastName, $passwordText, $roleLabel, $idNumber);
                } catch (\Throwable $exception) {
                    return $this->createErrorResponse(
                        'ClerkSignupUserFailed',
                        'Clerk could not create or update this signup account: ' . $exception->getMessage(),
                        502
                    );
                }

                $this->connection->executeStatement(
                    'UPDATE accounts
                     SET last_name = :lastName,
                         first_name = :firstName,
                         department = :department,
                         clerk_user_id = :clerkUserId,
                         password_hash = :passwordHash,
                         signup_supporting_document_name = :supportingDocumentName,
                         signup_supporting_document_mime_type = :supportingDocumentMimeType,
                         signup_supporting_document_data = :supportingDocumentData,
                         status = :status,
                         is_approved = :isApproved,
                         is_active = :isActive,
                         created_timestamp = :createdTimestamp,
                         updated_timestamp = :updatedTimestamp
                     WHERE account_identifier = :accountIdentifier',
                    [
                        'lastName' => $lastName,
                        'firstName' => $firstName,
                        'department' => $roleLabel,
                        'clerkUserId' => $clerkUserId,
                        'passwordHash' => password_hash($passwordText, PASSWORD_BCRYPT),
                        'supportingDocumentName' => $supportingDocumentName ?: null,
                        'supportingDocumentMimeType' => $supportingDocumentMimeType ?: null,
                        'supportingDocumentData' => $supportingDocumentData ?: null,
                        'status' => 'pending',
                        'isApproved' => false,
                        'isActive' => true,
                        'createdTimestamp' => $now,
                        'updatedTimestamp' => $now,
                        'accountIdentifier' => (int)$existingEmailAccount['account_identifier'],
                    ],
                    [
                        'lastName' => ParameterType::STRING,
                        'firstName' => ParameterType::STRING,
                        'department' => ParameterType::STRING,
                        'clerkUserId' => ParameterType::STRING,
                        'passwordHash' => ParameterType::STRING,
                        'supportingDocumentName' => $supportingDocumentName === '' ? ParameterType::NULL : ParameterType::STRING,
                        'supportingDocumentMimeType' => $supportingDocumentMimeType === '' ? ParameterType::NULL : ParameterType::STRING,
                        'supportingDocumentData' => $supportingDocumentData === '' ? ParameterType::NULL : ParameterType::STRING,
                        'status' => ParameterType::STRING,
                        'isApproved' => ParameterType::BOOLEAN,
                        'isActive' => ParameterType::BOOLEAN,
                        'createdTimestamp' => ParameterType::STRING,
                        'updatedTimestamp' => ParameterType::STRING,
                        'accountIdentifier' => ParameterType::INTEGER,
                    ]
                );

                return $this->createSuccessResponse([
                    'accountIdentifier' => (int)$existingEmailAccount['account_identifier'],
                    'idNumber' => $idNumber,
                    'lastName' => $lastName,
                    'firstName' => $firstName,
                    'emailAddress' => $emailAddress,
                    'clerkUserId' => $clerkUserId,
                    'roleDesignation' => 'ROLE_BORROWER',
                    'roleLabel' => 'User: ' . $roleLabel,
                    'accountType' => 'User',
                    'accountStatus' => 'pending',
                    'isApproved' => false,
                    'registeredAt' => $now,
                    'reusedPendingRequest' => true,
                ]);
            }

            return $this->createErrorResponse(
                'DuplicateAccount',
                $this->accountConflictLookupService->buildDuplicateAccountMessage('email', $existingEmailAccount),
                409,
                ['conflict' => $this->accountConflictLookupService->normalizeConflict($existingEmailAccount, 'email')]
            );
        }

        $existingIdNumberAccount = $this->accountConflictLookupService->findByIdNumber($idNumber);
        if ($existingIdNumberAccount) {
            return $this->createErrorResponse(
                'DuplicateIdNumber',
                $this->accountConflictLookupService->buildDuplicateAccountMessage('ID number', $existingIdNumberAccount),
                409,
                ['conflict' => $this->accountConflictLookupService->normalizeConflict($existingIdNumberAccount, 'idNumber')]
            );
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $passwordHash = password_hash($passwordText, PASSWORD_BCRYPT);

        try {
            $clerkUserId = $this->ensureClerkSignupUser($emailAddress, $firstName, $lastName, $passwordText, $roleLabel, $idNumber);

            $this->connection->executeStatement(
                'INSERT INTO accounts
                    (last_name, first_name, email_address, role_designation, id_number, department,
                     contact_number, clerk_user_id, password_hash, status, is_approved, is_active,
                     signup_supporting_document_name, signup_supporting_document_mime_type, signup_supporting_document_data,
                     failed_login_attempts, created_timestamp, updated_timestamp)
                 VALUES
                    (:lastName, :firstName, :emailAddress, :roleDesignation, :idNumber, :department,
                     :contactNumber, :clerkUserId, :passwordHash, :status, :isApproved, :isActive,
                     :supportingDocumentName, :supportingDocumentMimeType, :supportingDocumentData,
                     :failedLoginAttempts, :createdTimestamp, :updatedTimestamp)',
                [
                    'lastName' => $lastName,
                    'firstName' => $firstName,
                    'emailAddress' => $emailAddress,
                    'roleDesignation' => 'ROLE_BORROWER',
                    'idNumber' => $idNumber,
                    'department' => $roleLabel,
                    'contactNumber' => null,
                    'clerkUserId' => $clerkUserId,
                    'passwordHash' => $passwordHash,
                    'status' => 'pending',
                    'isApproved' => false,
                    'isActive' => true,
                    'supportingDocumentName' => $supportingDocumentName ?: null,
                    'supportingDocumentMimeType' => $supportingDocumentMimeType ?: null,
                    'supportingDocumentData' => $supportingDocumentData ?: null,
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
                    'clerkUserId' => ParameterType::STRING,
                    'passwordHash' => ParameterType::STRING,
                    'status' => ParameterType::STRING,
                    'isApproved' => ParameterType::BOOLEAN,
                    'isActive' => ParameterType::BOOLEAN,
                    'supportingDocumentName' => $supportingDocumentName === '' ? ParameterType::NULL : ParameterType::STRING,
                    'supportingDocumentMimeType' => $supportingDocumentMimeType === '' ? ParameterType::NULL : ParameterType::STRING,
                    'supportingDocumentData' => $supportingDocumentData === '' ? ParameterType::NULL : ParameterType::STRING,
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
                'clerkUserId' => $clerkUserId,
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
        $phone = trim($requestBody['phone'] ?? $requestBody['phoneNumber'] ?? $requestBody['phone_number'] ?? $requestBody['contactNumber'] ?? '');
        $idNumber = trim($requestBody['idNumber'] ?? $requestBody['workIdNumber'] ?? $requestBody['work_id_number'] ?? '');
        $role = 'Maintenance Staff';

        if ($lastName === '' || $firstName === '' || $phone === '' || $idNumber === '') {
            return $this->createErrorResponse(
                'ValidationError',
                'Last name, first name, phone number, and Work ID number are required.',
                422
            );
        }

        if (!$this->isValidStaffName($firstName) || !$this->isValidStaffName($lastName)) {
            return $this->createErrorResponse('ValidationError', 'First name and last name must have at least 2 letters and cannot contain numbers or symbols.', 422);
        }

        $phone = preg_replace('/\D+/', '', $phone);
        if (!preg_match('/^9\d{9}$/', $phone)) {
            return $this->createErrorResponse('ValidationError', 'Phone number must be exactly 10 digits and begin with 9.', 422);
        }

        if ($emailAddress === '') {
            $emailAddress = $this->buildStaffEmailAddress($idNumber);
        }

        $existingEmailAccount = $this->accountConflictLookupService->findByEmail($emailAddress);

        if ($existingEmailAccount) {
            return $this->createErrorResponse(
                'DuplicateAccount',
                $this->accountConflictLookupService->buildDuplicateAccountMessage('email', $existingEmailAccount),
                409,
                ['conflict' => $this->accountConflictLookupService->normalizeConflict($existingEmailAccount, 'email')]
            );
        }

        $existingIdNumberAccount = $this->accountConflictLookupService->findByIdNumber($idNumber);

        if ($existingIdNumberAccount) {
            return $this->createErrorResponse(
                'DuplicateIdNumber',
                $this->accountConflictLookupService->buildDuplicateAccountMessage('ID number', $existingIdNumberAccount),
                409,
                ['conflict' => $this->accountConflictLookupService->normalizeConflict($existingIdNumberAccount, 'idNumber')]
            );
        }

        $existingPhoneAccount = $this->accountConflictLookupService->findStaffByPhone($phone);

        if ($existingPhoneAccount) {
            return $this->createErrorResponse(
                'DuplicatePhoneNumber',
                $this->accountConflictLookupService->buildDuplicateAccountMessage('phone number', $existingPhoneAccount),
                409,
                ['conflict' => $this->accountConflictLookupService->normalizeConflict($existingPhoneAccount, 'phone')]
            );
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

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
                    'passwordHash' => null,
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
                    'passwordHash' => ParameterType::NULL,
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
        $confirmedAdminEmail = $this->normalizeEmailForConfirmation((string)($requestBody['confirmedAdminEmail'] ?? ''));
        $authenticatedIdentity = $request->attributes->get('authenticatedIdentity', []);
        $authenticatedAdminId = $this->resolveAuthenticatedAccountIdentifier($request, $authenticatedIdentity);

        if ($confirmedAdminEmail === '') {
            return $this->createErrorResponse(
                'SecurityConfirmationFailed',
                'Please type the responsible admin email before sending the invite.',
                422
            );
        }

        $confirmedAdmin = $this->connection->fetchAssociative(
            "SELECT account_identifier, email_address
             FROM accounts
             WHERE account_identifier = :accountIdentifier
               AND role_designation IN ('ROLE_ADMIN', 'ADMIN')
               AND COALESCE(is_active, TRUE) = TRUE
             LIMIT 1",
            ['accountIdentifier' => $authenticatedAdminId],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        $invitedBy = $this->normalizeEmailForConfirmation((string)($confirmedAdmin['email_address'] ?? ''));

        if (!$confirmedAdmin || $confirmedAdminEmail !== $invitedBy) {
            return $this->createErrorResponse(
                'SecurityConfirmationFailed',
                'Please type your exact admin email before sending the invite.',
                422
            );
        }

        $account = $this->connection->fetchAssociative(
            "SELECT account_identifier, email_address, role_designation, first_name, last_name,
                    department, id_number, status, is_approved,
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
             WHERE account_identifier = :accountIdentifier
               AND COALESCE(is_approved, FALSE) = FALSE
               AND LOWER(COALESCE(status, 'pending')) NOT IN ('approved', 'rejected', 'disabled')",
            ['accountIdentifier' => $accountIdentifier],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        if (!$account) {
            return $this->createErrorResponse(
                'WishlistAccountNotFound',
                'This email is not in the Requests Hub database or is no longer eligible for invitation.',
                404
            );
        }

        $now = new \DateTimeImmutable();
        $accountStatus = strtolower((string)($account['status'] ?? 'pending'));
        if (!empty($account['invite_accepted_at'])) {
            return $this->createErrorResponse(
                'InviteAlreadyAccepted',
                'This account invitation has already been accepted.',
                409
            );
        }

        if ($accountStatus === 'invited' && empty($account['invite_expires_at'])) {
            return $this->createErrorResponse(
                'InviteAlreadySent',
                'This account is already marked as invited. Resend is only available after the invitation expires.',
                409
            );
        }

        if (!empty($account['invite_expires_at'])) {
            try {
                $existingInviteExpiresAt = new \DateTimeImmutable((string)$account['invite_expires_at']);
                if ($existingInviteExpiresAt >= $now) {
                    return $this->createErrorResponse(
                        'InviteAlreadySent',
                        'This account already has an active invitation. Resend is only available after the invitation expires.',
                        409
                    );
                }
            } catch (\Throwable) {
                return $this->createErrorResponse(
                    'InviteStatusInvalid',
                    'The existing invitation status could not be verified. Please refresh Requests Hub and try again.',
                    409
                );
            }
        }

        $expiresAt = $now->modify('+7 days');
        $invitationToken = bin2hex(random_bytes(24));
        $frontendUrl = rtrim((string)($_ENV['FRONTEND_URL'] ?? 'http://localhost:5173'), '/');
        $redirectUrl = $frontendUrl . '/clerk-login';
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
    public function rejectUser(int $accountIdentifier, Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $confirmEmail = $this->normalizeEmailForConfirmation((string)($requestBody['confirmEmail'] ?? ''));
        $account = $this->accountRepository->find($accountIdentifier);

        if ($account === null) {
            return $this->createErrorResponse(
                'UserNotFound',
                'User not found.',
                404
            );
        }

        if ($confirmEmail === '' || $confirmEmail !== $this->normalizeEmailForConfirmation($account->getEmailAddress())) {
            return $this->createErrorResponse(
                'DenyConfirmationFailed',
                'Please type the exact email address to deny this request.',
                422
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

    #[Route('/{accountIdentifier}/delete-request', name: 'delete_wishlist_account_request', methods: ['DELETE'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function deleteWishlistAccountRequest(int $accountIdentifier, Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $confirmEmail = $this->normalizeEmailForConfirmation((string)($requestBody['confirmEmail'] ?? ''));
        $confirmedAdminPassword = (string)($requestBody['confirmedAdminPassword'] ?? '');

        $account = $this->connection->fetchAssociative(
            "SELECT account_identifier, email_address, status, is_approved
             FROM accounts
             WHERE account_identifier = :accountIdentifier
             LIMIT 1",
            ['accountIdentifier' => $accountIdentifier],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        if (!$account) {
            return $this->createErrorResponse('UserNotFound', 'User not found.', 404);
        }

        if ($confirmEmail === '' || $confirmEmail !== $this->normalizeEmailForConfirmation((string)$account['email_address'])) {
            return $this->createErrorResponse(
                'DeleteConfirmationFailed',
                'Please type the exact email address to delete this request.',
                422
            );
        }

        $authenticatedIdentity = $request->attributes->get('authenticatedIdentity', []);
        $authenticatedAdminId = $this->resolveAuthenticatedAccountIdentifier($request, $authenticatedIdentity);
        $adminPasswordConfirmationError = $this->validateResponsibleAdminPassword(
            $authenticatedAdminId,
            $confirmedAdminPassword,
            'deleting'
        );
        if ($adminPasswordConfirmationError !== null) {
            return $adminPasswordConfirmationError;
        }

        if ($this->toDatabaseBoolean($account['is_approved'] ?? false) || strtolower((string)$account['status']) === 'approved') {
            return $this->createErrorResponse(
                'DeleteRequestNotAllowed',
                'Approved accounts must be deleted from Manage Accounts.',
                403
            );
        }

        $this->connection->beginTransaction();
        try {
            $this->connection->executeStatement(
                'DELETE FROM invitations WHERE LOWER(email) = LOWER(:emailAddress)',
                ['emailAddress' => (string)$account['email_address']],
                ['emailAddress' => ParameterType::STRING]
            );

            $this->connection->executeStatement(
                'DELETE FROM staff_info WHERE account_identifier = :accountIdentifier',
                ['accountIdentifier' => $accountIdentifier],
                ['accountIdentifier' => ParameterType::INTEGER]
            );

            $this->connection->executeStatement(
                'DELETE FROM accounts WHERE account_identifier = :accountIdentifier',
                ['accountIdentifier' => $accountIdentifier],
                ['accountIdentifier' => ParameterType::INTEGER]
            );

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            return $this->createErrorResponse(
                'DeleteAccountRequestFailed',
                'Unable to delete account request: ' . $exception->getMessage(),
                500
            );
        }

        return $this->createSuccessResponse([
            'message' => 'Account request deleted successfully.',
            'accountIdentifier' => $accountIdentifier,
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

    private function ensureClerkSignupUser(
        string $emailAddress,
        string $firstName,
        string $lastName,
        string $password,
        string $roleLabel,
        string $idNumber
    ): string {
        $existingClerkUser = $this->findClerkUserByEmail($emailAddress);
        if ($existingClerkUser !== null) {
            $clerkUserId = (string)$existingClerkUser['id'];
            $this->updateClerkSignupUser($clerkUserId, $firstName, $lastName, $password, $roleLabel, $idNumber);
            return $clerkUserId;
        }

        return $this->createClerkSignupUser($emailAddress, $firstName, $lastName, $password, $roleLabel, $idNumber);
    }

    private function findClerkUserByEmail(string $emailAddress): ?array
    {
        $clerkSecretKey = trim((string)($_ENV['CLERK_SECRET_KEY'] ?? ''));
        if ($clerkSecretKey === '') {
            throw new \RuntimeException('CLERK_SECRET_KEY is not configured.');
        }

        $clerkApiBaseUrl = rtrim((string)($_ENV['CLERK_API_BASE_URL'] ?? 'https://api.clerk.com'), '/');
        $response = $this->httpClient->request('GET', $clerkApiBaseUrl . '/v1/users', [
            'headers' => [
                'Authorization' => 'Bearer ' . $clerkSecretKey,
                'Accept' => 'application/json',
            ],
            'query' => [
                'email_address' => $emailAddress,
            ],
        ]);

        if ($response->getStatusCode() >= 400) {
            $payload = $response->toArray(false);
            $message = $payload['errors'][0]['long_message']
                ?? $payload['errors'][0]['message']
                ?? $payload['message']
                ?? 'Clerk user lookup failed.';
            throw new \RuntimeException($message);
        }

        $payload = $response->toArray(false);
        if (isset($payload['id'])) {
            return $payload;
        }

        if (isset($payload[0]['id'])) {
            return $payload[0];
        }

        if (isset($payload['data'][0]['id'])) {
            return $payload['data'][0];
        }

        return null;
    }

    private function createClerkSignupUser(
        string $emailAddress,
        string $firstName,
        string $lastName,
        string $password,
        string $roleLabel,
        string $idNumber
    ): string {
        $clerkSecretKey = trim((string)($_ENV['CLERK_SECRET_KEY'] ?? ''));
        if ($clerkSecretKey === '') {
            throw new \RuntimeException('CLERK_SECRET_KEY is not configured.');
        }

        $clerkApiBaseUrl = rtrim((string)($_ENV['CLERK_API_BASE_URL'] ?? 'https://api.clerk.com'), '/');
        $response = $this->httpClient->request('POST', $clerkApiBaseUrl . '/v1/users', [
            'headers' => [
                'Authorization' => 'Bearer ' . $clerkSecretKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'email_address' => [$emailAddress],
                'first_name' => $firstName,
                'last_name' => $lastName,
                'password' => $password,
                'public_metadata' => [
                    'techreserve_account_type' => 'User',
                    'techreserve_role_designation' => 'ROLE_BORROWER',
                    'techreserve_role_label' => $roleLabel,
                    'techreserve_id_number' => $idNumber,
                    'techreserve_approval_status' => 'pending',
                ],
            ],
        ]);

        $payload = $response->toArray(false);
        if ($response->getStatusCode() >= 400) {
            $existingClerkUser = $this->findClerkUserByEmail($emailAddress);
            if ($existingClerkUser !== null) {
                return (string)$existingClerkUser['id'];
            }

            $message = $payload['errors'][0]['long_message']
                ?? $payload['errors'][0]['message']
                ?? $payload['message']
                ?? 'Clerk user creation failed.';
            throw new \RuntimeException($message);
        }

        $clerkUserId = (string)($payload['id'] ?? '');
        if ($clerkUserId === '') {
            throw new \RuntimeException('Clerk created the user but did not return a user ID.');
        }

        return $clerkUserId;
    }

    private function updateClerkSignupUser(
        string $clerkUserId,
        string $firstName,
        string $lastName,
        string $password,
        string $roleLabel,
        string $idNumber
    ): void {
        $clerkSecretKey = trim((string)($_ENV['CLERK_SECRET_KEY'] ?? ''));
        if ($clerkSecretKey === '' || $clerkUserId === '') {
            return;
        }

        $clerkApiBaseUrl = rtrim((string)($_ENV['CLERK_API_BASE_URL'] ?? 'https://api.clerk.com'), '/');
        $response = $this->httpClient->request('PATCH', $clerkApiBaseUrl . '/v1/users/' . rawurlencode($clerkUserId), [
            'headers' => [
                'Authorization' => 'Bearer ' . $clerkSecretKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'password' => $password,
                'public_metadata' => [
                    'techreserve_account_type' => 'User',
                    'techreserve_role_designation' => 'ROLE_BORROWER',
                    'techreserve_role_label' => $roleLabel,
                    'techreserve_id_number' => $idNumber,
                    'techreserve_approval_status' => 'pending',
                ],
            ],
        ]);

        if ($response->getStatusCode() >= 400) {
            $payload = $response->toArray(false);
            $message = $payload['errors'][0]['long_message']
                ?? $payload['errors'][0]['message']
                ?? $payload['message']
                ?? 'Clerk user update failed.';
            throw new \RuntimeException($message);
        }
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

    private function isPdfSupportingDocument(string $documentName, string $mimeType, string $documentData): bool
    {
        $lowerName = strtolower($documentName);
        $lowerMimeType = strtolower($mimeType);

        return str_ends_with($lowerName, '.pdf')
            && ($lowerMimeType === '' || $lowerMimeType === 'application/pdf')
            && str_starts_with($documentData, 'data:application/pdf;base64,');
    }

    private function isValidStaffName(string $name): bool
    {
        $normalizedName = trim($name);
        $letterCount = preg_match_all('/[A-Za-z]/', $normalizedName);

        return $letterCount >= 2 && preg_match('/^[A-Za-z ]+$/', $normalizedName) === 1;
    }

    private function buildStaffEmailAddress(string $idNumber): string
    {
        $normalizedIdNumber = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $idNumber) ?: bin2hex(random_bytes(4)));
        return 'staff-' . $normalizedIdNumber . '@techreserve.feu.edu.ph';
    }

    private function upsertStaffInfo(
        int $accountIdentifier,
        string $employeeIdNumber,
        string $firstName,
        string $lastName,
        string $phoneNumber,
        string $role,
        ?string $imageUrl
    ): void {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->connection->executeStatement(
            'INSERT INTO staff_info
                (account_identifier, employee_id_number, first_name, last_name, phone_number, role, image_url, created_timestamp, updated_timestamp)
             VALUES
                (:accountIdentifier, :employeeIdNumber, :firstName, :lastName, :phoneNumber, :role, :imageUrl, :createdTimestamp, :updatedTimestamp)
             ON CONFLICT (account_identifier) WHERE account_identifier IS NOT NULL
             DO UPDATE SET
                employee_id_number = EXCLUDED.employee_id_number,
                first_name = EXCLUDED.first_name,
                last_name = EXCLUDED.last_name,
                phone_number = EXCLUDED.phone_number,
                role = EXCLUDED.role,
                image_url = EXCLUDED.image_url,
                updated_timestamp = EXCLUDED.updated_timestamp',
            [
                'accountIdentifier' => $accountIdentifier,
                'employeeIdNumber' => $employeeIdNumber,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'phoneNumber' => $phoneNumber,
                'role' => $role,
                'imageUrl' => $imageUrl,
                'createdTimestamp' => $now,
                'updatedTimestamp' => $now,
            ],
            [
                'accountIdentifier' => ParameterType::INTEGER,
                'employeeIdNumber' => ParameterType::STRING,
                'firstName' => ParameterType::STRING,
                'lastName' => ParameterType::STRING,
                'phoneNumber' => ParameterType::STRING,
                'role' => ParameterType::STRING,
                'imageUrl' => $imageUrl === null ? ParameterType::NULL : ParameterType::STRING,
                'createdTimestamp' => ParameterType::STRING,
                'updatedTimestamp' => ParameterType::STRING,
            ]
        );
    }

    private function isReusablePendingSignupRequest(array $account, string $idNumber): bool
    {
        $isApproved = $this->toDatabaseBoolean($account['is_approved'] ?? false);
        $status = strtolower((string)($account['status'] ?? ''));
        $existingIdNumber = trim((string)($account['id_number'] ?? ''));

        return !$isApproved
            && $status === 'pending'
            && $existingIdNumber === $idNumber;
    }

    private function findLatestInvitationForEmail(string $emailAddress): ?array
    {
        $invitation = $this->connection->fetchAssociative(
            'SELECT id, status, expires_at, accepted_at
             FROM invitations
             WHERE LOWER(email) = LOWER(:emailAddress)
             ORDER BY created_at DESC
             LIMIT 1',
            ['emailAddress' => $emailAddress],
            ['emailAddress' => ParameterType::STRING]
        );

        return $invitation ?: null;
    }

    private function isAcceptableInvitation(?array $invitation): bool
    {
        if ($invitation === null) {
            return false;
        }

        if (!empty($invitation['accepted_at'])) {
            return true;
        }

        $status = strtolower((string)($invitation['status'] ?? 'pending'));
        if (in_array($status, ['accepted', 'expired', 'rejected', 'denied'], true)) {
            return false;
        }

        try {
            $expiresAt = new \DateTimeImmutable((string)$invitation['expires_at']);
        } catch (\Throwable) {
            return false;
        }

        return $expiresAt >= new \DateTimeImmutable();
    }

    private function markLatestInvitationAccepted(string $emailAddress, string $acceptedAt): void
    {
        $this->connection->executeStatement(
            "UPDATE invitations
             SET status = 'accepted',
                 accepted_at = COALESCE(accepted_at, :acceptedAt)
             WHERE id = (
                SELECT id
                FROM invitations
                WHERE LOWER(email) = LOWER(:emailAddress)
                ORDER BY created_at DESC
                LIMIT 1
             )",
            [
                'emailAddress' => $emailAddress,
                'acceptedAt' => $acceptedAt,
            ],
            [
                'emailAddress' => ParameterType::STRING,
                'acceptedAt' => ParameterType::STRING,
            ]
        );
    }

    private function normalizeEmailForConfirmation(string $emailAddress): string
    {
        $normalizedEmailAddress = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\s]+/u', '', $emailAddress) ?? $emailAddress;
        return strtolower(trim($normalizedEmailAddress));
    }

    private function validateResponsibleAdminPassword(int $authenticatedAdminId, string $confirmedAdminPassword, string $actionName): ?JsonResponse
    {
        if ($authenticatedAdminId <= 0) {
            return $this->createErrorResponse(
                'SecurityConfirmationFailed',
                sprintf('Please sign in as an admin before %s this request.', $actionName),
                422
            );
        }

        if (trim($confirmedAdminPassword) === '') {
            return $this->createErrorResponse(
                'SecurityConfirmationFailed',
                sprintf('Please type the responsible admin password before %s this request.', $actionName),
                422
            );
        }

        $confirmedAdmin = $this->connection->fetchAssociative(
            "SELECT password_hash
             FROM accounts
             WHERE account_identifier = :accountIdentifier
               AND role_designation IN ('ROLE_ADMIN', 'ADMIN')
               AND COALESCE(is_active, TRUE) = TRUE
             LIMIT 1",
            ['accountIdentifier' => $authenticatedAdminId],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        $passwordHash = (string)($confirmedAdmin['password_hash'] ?? '');
        if (!$confirmedAdmin || $passwordHash === '' || !password_verify($confirmedAdminPassword, $passwordHash)) {
            return $this->createErrorResponse(
                'SecurityConfirmationFailed',
                sprintf('Please type your exact admin password before %s this request.', $actionName),
                422
            );
        }

        return null;
    }

    private function resolveAuthenticatedAccountIdentifier(Request $request, array $authenticatedIdentity): int
    {
        $accountIdentifier = (int)($authenticatedIdentity['accountIdentifier'] ?? 0);
        if ($accountIdentifier > 0) {
            return $accountIdentifier;
        }

        $authorizationHeader = $request->headers->get('Authorization', '');
        if (!str_starts_with($authorizationHeader, 'Bearer ')) {
            return 0;
        }

        $bearerToken = substr($authorizationHeader, 7);
        $decodedLocalToken = json_decode(base64_decode($bearerToken, true) ?: '', true);
        if (is_array($decodedLocalToken) && isset($decodedLocalToken['accountId'])) {
            return (int)$decodedLocalToken['accountId'];
        }

        $decodedJwtPayload = $this->decodeJwtPayloadWithoutVerification($bearerToken);
        $clerkUserId = (string)($decodedJwtPayload['sub'] ?? '');
        if ($clerkUserId === '') {
            return 0;
        }

        $account = $this->accountRepository->findOneByClerkUserId($clerkUserId);

        return $account ? (int)$account->getAccountIdentifier() : 0;
    }

    private function decodeJwtPayloadWithoutVerification(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) < 2) {
            return [];
        }

        $payload = strtr($parts[1], '-_', '+/');
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $decodedPayload = base64_decode($payload, true);
        if ($decodedPayload === false) {
            return [];
        }

        $data = json_decode($decodedPayload, true);

        return is_array($data) ? $data : [];
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
