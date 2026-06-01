<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\Repository\AccountRepository;
use App\Domain\Account\Service\AccountConflictLookupService;
use App\Domain\Account\Service\AccountInputValidationService;
use App\Domain\Account\Service\PublicSignupRequestService;
use App\Domain\Account\Service\UserClerkRegistrationService;
use App\Domain\Account\Service\Wishlist\WishlistAccountReadService;
use App\Domain\Account\Service\WishlistAccountApprovalService;
use App\Domain\Account\Service\WishlistEmployeeAccountService;
use App\Domain\Account\Service\WishlistUserAccountService;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/users')]
class UserRegistrationController extends AbstractController
{
    use JsonResponseTrait;

    private const DEFAULT_ADMIN_PASSWORD = 'admin123';

    private AccountRepository $accountRepository;
    private Connection $connection;
    private WishlistAccountReadService $wishlistAccountReadService;
    private AccountConflictLookupService $accountConflictLookupService;
    private AccountInputValidationService $accountInputValidationService;
    private PublicSignupRequestService $publicSignupRequestService;
    private UserClerkRegistrationService $userClerkRegistrationService;
    private WishlistAccountApprovalService $wishlistAccountApprovalService;
    private WishlistEmployeeAccountService $wishlistEmployeeAccountService;
    private WishlistUserAccountService $wishlistUserAccountService;

    public function __construct(
        AccountRepository $accountRepository,
        Connection $connection,
        WishlistAccountReadService $wishlistAccountReadService,
        AccountConflictLookupService $accountConflictLookupService,
        AccountInputValidationService $accountInputValidationService,
        PublicSignupRequestService $publicSignupRequestService,
        UserClerkRegistrationService $userClerkRegistrationService,
        WishlistAccountApprovalService $wishlistAccountApprovalService,
        WishlistEmployeeAccountService $wishlistEmployeeAccountService,
        WishlistUserAccountService $wishlistUserAccountService
    )
    {
        $this->accountRepository = $accountRepository;
        $this->connection = $connection;
        $this->wishlistAccountReadService = $wishlistAccountReadService;
        $this->accountConflictLookupService = $accountConflictLookupService;
        $this->accountInputValidationService = $accountInputValidationService;
        $this->publicSignupRequestService = $publicSignupRequestService;
        $this->userClerkRegistrationService = $userClerkRegistrationService;
        $this->wishlistAccountApprovalService = $wishlistAccountApprovalService;
        $this->wishlistEmployeeAccountService = $wishlistEmployeeAccountService;
        $this->wishlistUserAccountService = $wishlistUserAccountService;
    }

    #[Route('/register', name: 'user_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $result = $this->userClerkRegistrationService->register($requestBody);

        if (($result['success'] ?? false) !== true) {
            return $this->createErrorResponse(
                (string)($result['errorCode'] ?? 'RegistrationFailed'),
                (string)($result['message'] ?? 'Failed to register account.'),
                (int)($result['status'] ?? 500)
            );
        }

        return $this->createSuccessResponse($result['data'] ?? [], (int)($result['status'] ?? 200));
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
        $result = $this->wishlistUserAccountService->create($requestBody);

        if (($result['success'] ?? false) !== true) {
            return $this->createErrorResponse(
                (string)($result['errorCode'] ?? 'CreateUserAccountFailed'),
                (string)($result['message'] ?? 'Failed to create user account.'),
                (int)($result['status'] ?? 500),
                $result['extra'] ?? []
            );
        }

        return $this->createSuccessResponse($result['data'] ?? [], (int)($result['status'] ?? 200));
    }

    #[Route('/signup-requests', name: 'create_public_signup_request', methods: ['POST'])]
    public function createPublicSignupRequest(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $result = $this->publicSignupRequestService->create($requestBody);

        if (($result['success'] ?? false) !== true) {
            return $this->createErrorResponse(
                (string)($result['errorCode'] ?? 'CreateSignupRequestFailed'),
                (string)($result['message'] ?? 'Failed to create signup request.'),
                (int)($result['status'] ?? 500),
                $result['extra'] ?? []
            );
        }

        return $this->createSuccessResponse($result['data'] ?? [], (int)($result['status'] ?? 200));
    }

    #[Route('/wishlist/employee-accounts', name: 'create_wishlist_employee_account', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function createWishlistEmployeeAccount(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $result = $this->wishlistEmployeeAccountService->create($requestBody);

        if (($result['success'] ?? false) !== true) {
            return $this->createErrorResponse(
                (string)($result['errorCode'] ?? 'CreateEmployeeAccountFailed'),
                (string)($result['message'] ?? 'Failed to create employee account.'),
                (int)($result['status'] ?? 500),
                $result['extra'] ?? []
            );
        }

        return $this->createSuccessResponse($result['data'] ?? [], (int)($result['status'] ?? 200));
    }

    #[Route('/{accountIdentifier}/approve', name: 'approve_user', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function approveUser(Request $request, int $accountIdentifier): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $authenticatedIdentity = $request->attributes->get('authenticatedIdentity', []);
        $authenticatedAdminId = $this->resolveAuthenticatedAccountIdentifier($request, $authenticatedIdentity);
        $result = $this->wishlistAccountApprovalService->approve($accountIdentifier, $requestBody, $authenticatedAdminId);

        if (($result['success'] ?? false) !== true) {
            return $this->createErrorResponse(
                (string)($result['errorCode'] ?? 'ApproveAccountFailed'),
                (string)($result['message'] ?? 'Unable to send the invitation.'),
                (int)($result['status'] ?? 500)
            );
        }

        return $this->createSuccessResponse($result['data'] ?? [], (int)($result['status'] ?? 200));
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
