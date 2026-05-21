<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\Repository\AccountRepository;
use App\Domain\Account\Entity\AccountEntity;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    public function __construct(AccountRepository $accountRepository, HttpClientInterface $httpClient, Connection $connection)
    {
        $this->accountRepository = $accountRepository;
        $this->httpClient = $httpClient;
        $this->connection = $connection;
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
                $this->connection->update('accounts', [
                    'role_designation' => 'ROLE_ADMIN',
                    'status' => 'approved',
                    'is_approved' => true,
                    'is_active' => true,
                    'updated_timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
                ], ['account_identifier' => $existingAccount->getAccountIdentifier()]);

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
            if ($existingEmailAccount->getClerkUserId() && $existingEmailAccount->getClerkUserId() !== $clerkUserId) {
                return $this->createErrorResponse(
                    'DuplicateAccount',
                    'An account with this email address already exists.',
                    409
                );
            }

            $now = (new \DateTime())->format('Y-m-d H:i:s');
            $this->connection->update('accounts', [
                'last_name' => $lastName,
                'first_name' => $firstName,
                'role_designation' => $role,
                'id_number' => $idNumber ?: null,
                'department' => $department,
                'contact_number' => $contactNumber,
                'clerk_user_id' => $clerkUserId,
                'status' => $status,
                'is_approved' => $isApproved,
                'is_active' => true,
                'updated_timestamp' => $now,
            ], ['account_identifier' => $existingEmailAccount->getAccountIdentifier()]);

            return $this->createSuccessResponse([
                'message' => 'Account linked to Clerk successfully.',
                'account' => [
                    'accountIdentifier' => $existingEmailAccount->getAccountIdentifier(),
                    'clerkUserId' => $clerkUserId,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'emailAddress' => $emailAddress,
                    'roleDesignation' => $role,
                    'status' => $status,
                    'isApproved' => $isApproved,
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
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $lastName, $firstName, $emailAddress, $role, $idNumber ?: null, $department,
                    $contactNumber, $clerkUserId, $status, $isApproved, true,
                    0, $now, $now,
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
                    department, status, is_approved, created_timestamp
             FROM accounts
             WHERE COALESCE(is_approved, FALSE) = FALSE
               AND LOWER(COALESCE(status, 'pending')) <> 'approved'
             ORDER BY created_timestamp DESC"
        );

        $users = array_map(function (array $row): array {
            $roleDesignation = (string)($row['role_designation'] ?? 'ROLE_BORROWER');
            $department = strtolower((string)($row['department'] ?? ''));
            $isAdmin = str_contains(strtoupper($roleDesignation), 'ADMIN');
            $isEmployee = !$isAdmin && (str_contains($department, 'faculty') || str_contains($department, 'employee'));
            $accountType = $isAdmin ? 'Admin' : ($isEmployee ? 'Employee' : 'User');
            $roleLabel = $isAdmin ? 'Admin' : ($isEmployee ? 'User: Faculty' : 'User: Student');

            return [
                'accountIdentifier' => (int)$row['account_identifier'],
                'idNumber' => $row['id_number'] ?: substr((string)$row['created_timestamp'], 0, 4) . str_pad((string)$row['account_identifier'], 4, '0', STR_PAD_LEFT),
                'lastName' => (string)$row['last_name'],
                'firstName' => (string)$row['first_name'],
                'emailAddress' => (string)$row['email_address'],
                'roleDesignation' => $roleDesignation,
                'roleLabel' => $roleLabel,
                'accountType' => $accountType,
                'accountStatus' => (string)($row['status'] ?? 'pending'),
                'isApproved' => (bool)$row['is_approved'],
                'registeredAt' => (string)$row['created_timestamp'],
                'inviteSentAt' => null,
                'inviteExpiresAt' => null,
                'inviteAcceptedAt' => null,
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

        $existingEmail = $this->connection->fetchOne(
            'SELECT 1 FROM accounts WHERE LOWER(email_address) = LOWER(:emailAddress)',
            ['emailAddress' => $emailAddress]
        );

        if ($existingEmail) {
            return $this->createErrorResponse('DuplicateAccount', 'An account with this email already exists.', 409);
        }

        $existingIdNumber = $this->connection->fetchOne(
            'SELECT 1 FROM accounts WHERE id_number = :idNumber',
            ['idNumber' => $idNumber]
        );

        if ($existingIdNumber) {
            return $this->createErrorResponse('DuplicateIdNumber', 'An account with this ID number already exists.', 409);
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $passwordHash = password_hash($passwordText, PASSWORD_BCRYPT);

        try {
            $this->connection->insert('accounts', [
                'last_name' => $lastName,
                'first_name' => $firstName,
                'email_address' => $emailAddress,
                'role_designation' => 'ROLE_ADMIN',
                'id_number' => $idNumber,
                'department' => 'Administration',
                'contact_number' => null,
                'clerk_user_id' => null,
                'password_hash' => $passwordHash,
                'status' => 'pending',
                'is_approved' => false,
                'is_active' => true,
                'failed_login_attempts' => 0,
                'created_timestamp' => $now,
                'updated_timestamp' => $now,
            ]);

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

    #[Route('/{accountIdentifier}/approve', name: 'approve_user', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function approveUser(int $accountIdentifier): JsonResponse
    {
        $account = $this->accountRepository->find($accountIdentifier);

        if ($account === null) {
            return $this->createErrorResponse(
                'UserNotFound',
                'User not found.',
                404
            );
        }

        $now = new \DateTimeImmutable();
        $expiresAt = $now->modify('+7 days');

        $account->setStatus('approved');
        $account->setIsApproved(true);
        $this->accountRepository->persistAccount($account);

        return $this->createSuccessResponse([
            'message' => 'User approved successfully.',
            'account' => [
                'accountIdentifier' => $account->getAccountIdentifier(),
                'status' => $account->getStatus(),
                'isApproved' => $account->getIsApproved(),
            ],
            'invitation' => [
                'emailAddress' => $account->getEmailAddress(),
                'role' => $account->getRoleDesignation(),
                'status' => 'sent',
                'sentAt' => $now->format('Y-m-d\TH:i:sP'),
                'expiresAt' => $expiresAt->format('Y-m-d\TH:i:sP'),
                'acceptedAt' => null,
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
}
