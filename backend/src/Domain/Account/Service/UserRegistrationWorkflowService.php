<?php

namespace App\Domain\Account\Service;

use App\Domain\Account\Repository\AccountRepository;
use App\Domain\Account\Service\Wishlist\WishlistAccountReadService;
use App\Shared\Utils\AppClock;
use Doctrine\DBAL\Connection;

class UserRegistrationWorkflowService
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly Connection $connection,
        private readonly WishlistAccountReadService $wishlistAccountReadService,
        private readonly PublicSignupRequestService $publicSignupRequestService,
        private readonly UserClerkRegistrationService $userClerkRegistrationService,
        private readonly WishlistAccountApprovalService $wishlistAccountApprovalService,
        private readonly WishlistAdminAccountService $wishlistAdminAccountService,
        private readonly WishlistEmployeeAccountService $wishlistEmployeeAccountService,
        private readonly WishlistRequestDecisionService $wishlistRequestDecisionService,
        private readonly WishlistUserAccountService $wishlistUserAccountService,
        private readonly InvitationExpiryPolicyService $invitationExpiryPolicyService
    ) {
    }

    public function register(array $requestBody): array
    {
        return $this->userClerkRegistrationService->register($requestBody);
    }

    public function getCurrentAccount(string $authorizationHeader): array
    {
        if (!str_starts_with($authorizationHeader, 'Bearer ')) {
            return $this->error('AuthRequired', 'Authorization header required.', 401);
        }

        $account = $this->resolveAccountFromToken(substr($authorizationHeader, 7));
        if ($account === null) {
            return $this->error('AccountNotFound', 'No account registered for this authenticated user.', 404);
        }

        $profilePhotoData = $this->connection->fetchOne(
            'SELECT profile_photo_data FROM accounts WHERE account_identifier = :accountIdentifier',
            ['accountIdentifier' => $account->getAccountIdentifier()]
        );

        return $this->success([
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

    private function resolveAccountFromToken(string $bearerToken): ?\App\Domain\Account\Entity\AccountEntity
    {
        $localPayload = json_decode(base64_decode($bearerToken, true) ?: '', true);
        if (is_array($localPayload)) {
            if (isset($localPayload['exp']) && (int)$localPayload['exp'] < time()) {
                return null;
            }

            $accountIdentifier = (int)($localPayload['accountId'] ?? $localPayload['accountIdentifier'] ?? 0);
            if ($accountIdentifier > 0) {
                $localAccount = $this->accountRepository->find($accountIdentifier);
                if ($localAccount !== null) {
                    return $localAccount;
                }
            }
        }

        $clerkUserId = $this->resolveClerkUserId($bearerToken);
        if (($clerkUserId['success'] ?? false) !== true) {
            return null;
        }

        return $this->accountRepository->findOneByClerkUserId((string)$clerkUserId['data']['clerkUserId']);
    }

    public function listPendingUsers(): array
    {
        $users = [];
        $pendingUsers = $this->accountRepository->findBy([
            'status' => 'pending',
            'isApproved' => false,
        ]);

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

        return $this->success([
            'count' => count($users),
            'users' => $users,
        ]);
    }

    public function listWishlistUsers(): array
    {
        $users = $this->wishlistAccountReadService->getWishlistAccounts();

        return $this->success([
            'count' => count($users),
            'users' => $users,
        ]);
    }

    public function listWishlistUsersByType(string $accountType): array
    {
        $normalizedType = strtolower(trim($accountType));
        if (!in_array($normalizedType, ['admin', 'user', 'employee'], true)) {
            return $this->error('WishlistAccountTypeInvalid', 'Unsupported wishlist account type.', 422);
        }

        $users = $this->wishlistAccountReadService->getWishlistAccountsByType($normalizedType);

        return $this->success([
            'accountType' => $normalizedType,
            'count' => count($users),
            'users' => $users,
        ]);
    }

    public function createWishlistAdminAccount(array $requestBody, array $authenticatedIdentity, string $authorizationHeader): array
    {
        return $this->wishlistAdminAccountService->create(
            $requestBody,
            $this->resolveAuthenticatedAccountIdentifier($authenticatedIdentity, $authorizationHeader)
        );
    }

    public function createWishlistUserAccount(array $requestBody): array
    {
        return $this->wishlistUserAccountService->create($requestBody);
    }

    public function createPublicSignupRequest(array $requestBody, ?\Symfony\Component\HttpFoundation\File\UploadedFile $supportingDocumentFile = null): array
    {
        return $this->publicSignupRequestService->create($requestBody, $supportingDocumentFile);
    }

    public function createWishlistEmployeeAccount(array $requestBody): array
    {
        return $this->wishlistEmployeeAccountService->create($requestBody);
    }

    public function approveUser(int $accountIdentifier, array $requestBody, array $authenticatedIdentity, string $authorizationHeader): array
    {
        return $this->wishlistAccountApprovalService->approve(
            $accountIdentifier,
            $requestBody,
            $this->resolveAuthenticatedAccountIdentifier($authenticatedIdentity, $authorizationHeader)
        );
    }

    public function verifyEmailAndApproveUser(int $accountIdentifier, array $requestBody, array $authenticatedIdentity, string $authorizationHeader): array
    {
        return $this->wishlistAccountApprovalService->verifyEmailAndApprove(
            $accountIdentifier,
            $requestBody,
            $this->resolveAuthenticatedAccountIdentifier($authenticatedIdentity, $authorizationHeader)
        );
    }

    public function rejectUser(int $accountIdentifier, array $requestBody, array $authenticatedIdentity, string $authorizationHeader): array
    {
        return $this->wishlistRequestDecisionService->reject(
            $accountIdentifier,
            (string)($requestBody['confirmedAdminEmail'] ?? $requestBody['confirmEmail'] ?? ''),
            $this->resolveAuthenticatedAccountIdentifier($authenticatedIdentity, $authorizationHeader),
            (string)($requestBody['confirmedAdminPassword'] ?? '')
        );
    }

    public function deleteWishlistAccountRequest(int $accountIdentifier, array $requestBody, array $authenticatedIdentity, string $authorizationHeader): array
    {
        return $this->wishlistRequestDecisionService->deleteRequest(
            $accountIdentifier,
            (string)($requestBody['confirmedAdminEmail'] ?? $requestBody['confirmEmail'] ?? ''),
            $this->resolveAuthenticatedAccountIdentifier($authenticatedIdentity, $authorizationHeader),
            (string)($requestBody['confirmedAdminPassword'] ?? '')
        );
    }

    public function inviteUser(array $requestBody): array
    {
        $emailAddress = trim($requestBody['emailAddress'] ?? '');
        $role = trim($requestBody['role'] ?? 'ROLE_BORROWER');
        $invitedBy = $requestBody['invitedBy'] ?? null;
        $sentAt = AppClock::now();

        if (empty($emailAddress)) {
            return $this->error('ValidationError', 'emailAddress is required.', 400);
        }

        $existingAccount = $this->accountRepository->findOneByEmailAddress($emailAddress);
        if ($existingAccount !== null) {
            return $this->error('DuplicateAccount', 'An account with this email address already exists.', 409);
        }

        return $this->success([
            'message' => 'Invitation sent successfully.',
            'invitation' => [
                'emailAddress' => $emailAddress,
                'role' => $role,
                'invitedBy' => $invitedBy,
                'status' => 'invited',
                'sentAt' => $sentAt->format('Y-m-d\TH:i:sP'),
                'expiresAt' => $this->invitationExpiryPolicyService->buildExpiresAt($sentAt)->format('Y-m-d\TH:i:sP'),
            ],
        ]);
    }

    private function resolveClerkUserId(string $bearerToken): array
    {
        try {
            $parts = explode('.', $bearerToken);
            if (count($parts) !== 3) {
                error_log('JWT decode: Invalid format, parts=' . count($parts));
                return $this->error('InvalidToken', 'Invalid JWT format.', 401);
            }

            $padded = strtr($parts[1], '-_', '+/') . str_repeat('=', (4 - strlen($parts[1]) % 4) % 4);
            $payload = json_decode(base64_decode($padded), true);
            error_log('JWT decode: payload=' . json_encode($payload));

            if (!is_array($payload) || empty($payload['sub'])) {
                error_log('JWT decode: Missing sub claim');
                return $this->error('InvalidToken', 'JWT missing sub claim.', 401);
            }

            if (isset($payload['exp']) && $payload['exp'] < time()) {
                error_log('JWT decode: Token expired');
                return $this->error('TokenExpired', 'Clerk session token has expired.', 401);
            }

            error_log('JWT decode: Success, clerkUserId=' . $payload['sub']);
            return $this->success(['clerkUserId' => $payload['sub']]);
        } catch (\Throwable $exception) {
            error_log('JWT decode error: ' . $exception->getMessage());
            return $this->error('InvalidToken', 'Clerk token verification failed.', 401);
        }
    }

    private function resolveAuthenticatedAccountIdentifier(array $authenticatedIdentity, string $authorizationHeader): int
    {
        $accountIdentifier = (int)($authenticatedIdentity['accountIdentifier'] ?? 0);
        if ($accountIdentifier > 0) {
            return $accountIdentifier;
        }

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

    private function success(array $data, int $status = 200): array
    {
        return [
            'success' => true,
            'status' => $status,
            'data' => $data,
        ];
    }

    private function error(string $errorCode, string $message, int $status, array $extra = []): array
    {
        return [
            'success' => false,
            'errorCode' => $errorCode,
            'message' => $message,
            'status' => $status,
            'extra' => $extra,
        ];
    }
}
