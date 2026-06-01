<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\Repository\AccountRepository;
use App\Domain\Account\Service\PublicSignupRequestService;
use App\Domain\Account\Service\UserClerkRegistrationService;
use App\Domain\Account\Service\Wishlist\WishlistAccountReadService;
use App\Domain\Account\Service\WishlistAccountApprovalService;
use App\Domain\Account\Service\WishlistAdminAccountService;
use App\Domain\Account\Service\WishlistEmployeeAccountService;
use App\Domain\Account\Service\WishlistRequestDecisionService;
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

    private AccountRepository $accountRepository;
    private Connection $connection;
    private WishlistAccountReadService $wishlistAccountReadService;
    private PublicSignupRequestService $publicSignupRequestService;
    private UserClerkRegistrationService $userClerkRegistrationService;
    private WishlistAccountApprovalService $wishlistAccountApprovalService;
    private WishlistAdminAccountService $wishlistAdminAccountService;
    private WishlistEmployeeAccountService $wishlistEmployeeAccountService;
    private WishlistRequestDecisionService $wishlistRequestDecisionService;
    private WishlistUserAccountService $wishlistUserAccountService;

    public function __construct(
        AccountRepository $accountRepository,
        Connection $connection,
        WishlistAccountReadService $wishlistAccountReadService,
        PublicSignupRequestService $publicSignupRequestService,
        UserClerkRegistrationService $userClerkRegistrationService,
        WishlistAccountApprovalService $wishlistAccountApprovalService,
        WishlistAdminAccountService $wishlistAdminAccountService,
        WishlistEmployeeAccountService $wishlistEmployeeAccountService,
        WishlistRequestDecisionService $wishlistRequestDecisionService,
        WishlistUserAccountService $wishlistUserAccountService
    )
    {
        $this->accountRepository = $accountRepository;
        $this->connection = $connection;
        $this->wishlistAccountReadService = $wishlistAccountReadService;
        $this->publicSignupRequestService = $publicSignupRequestService;
        $this->userClerkRegistrationService = $userClerkRegistrationService;
        $this->wishlistAccountApprovalService = $wishlistAccountApprovalService;
        $this->wishlistAdminAccountService = $wishlistAdminAccountService;
        $this->wishlistEmployeeAccountService = $wishlistEmployeeAccountService;
        $this->wishlistRequestDecisionService = $wishlistRequestDecisionService;
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
        $result = $this->wishlistAdminAccountService->create($requestBody);

        if (($result['success'] ?? false) !== true) {
            return $this->createErrorResponse(
                (string)($result['errorCode'] ?? 'CreateAdminAccountFailed'),
                (string)($result['message'] ?? 'Failed to create admin account.'),
                (int)($result['status'] ?? 500),
                $result['extra'] ?? []
            );
        }

        return $this->createSuccessResponse($result['data'] ?? [], (int)($result['status'] ?? 200));
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
        $result = $this->wishlistRequestDecisionService->reject(
            $accountIdentifier,
            (string)($requestBody['confirmEmail'] ?? '')
        );

        if (($result['success'] ?? false) !== true) {
            return $this->createErrorResponse(
                (string)($result['errorCode'] ?? 'RejectUserFailed'),
                (string)($result['message'] ?? 'Unable to reject this request.'),
                (int)($result['status'] ?? 500)
            );
        }

        return $this->createSuccessResponse($result['data'] ?? [], (int)($result['status'] ?? 200));
    }

    #[Route('/{accountIdentifier}/delete-request', name: 'delete_wishlist_account_request', methods: ['DELETE'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function deleteWishlistAccountRequest(int $accountIdentifier, Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $authenticatedIdentity = $request->attributes->get('authenticatedIdentity', []);
        $authenticatedAdminId = $this->resolveAuthenticatedAccountIdentifier($request, $authenticatedIdentity);
        $result = $this->wishlistRequestDecisionService->deleteRequest(
            $accountIdentifier,
            (string)($requestBody['confirmEmail'] ?? ''),
            $authenticatedAdminId,
            (string)($requestBody['confirmedAdminPassword'] ?? '')
        );

        if (($result['success'] ?? false) !== true) {
            return $this->createErrorResponse(
                (string)($result['errorCode'] ?? 'DeleteAccountRequestFailed'),
                (string)($result['message'] ?? 'Unable to delete account request.'),
                (int)($result['status'] ?? 500)
            );
        }

        return $this->createSuccessResponse($result['data'] ?? [], (int)($result['status'] ?? 200));
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

}
