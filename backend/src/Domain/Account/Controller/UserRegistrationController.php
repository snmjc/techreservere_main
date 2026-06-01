<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\Service\UserRegistrationWorkflowService;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/users')]
class UserRegistrationController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(private readonly UserRegistrationWorkflowService $workflowService)
    {
    }

    #[Route('/register', name: 'user_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->register($this->jsonBody($request)),
            'RegistrationFailed',
            'Failed to register account.'
        );
    }

    #[Route('/me', name: 'get_my_account', methods: ['GET'])]
    public function me(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->getCurrentAccount($request->headers->get('Authorization', '')),
            'InvalidToken',
            'Clerk token verification failed.'
        );
    }

    #[Route('/pending', name: 'list_pending_users', methods: ['GET'])]
    public function listPendingUsers(): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->listPendingUsers(),
            'PendingUsersFailed',
            'Unable to load pending users.'
        );
    }

    #[Route('/wishlist', name: 'list_wishlist_users', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function listWishlistUsers(): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->listWishlistUsers(),
            'WishlistUsersFailed',
            'Unable to load wishlist users.'
        );
    }

    #[Route('/wishlist/admin-accounts', name: 'create_wishlist_admin_account', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function createWishlistAdminAccount(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->createWishlistAdminAccount($this->jsonBody($request)),
            'CreateAdminAccountFailed',
            'Failed to create admin account.'
        );
    }

    #[Route('/wishlist/user-accounts', name: 'create_wishlist_user_account', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function createWishlistUserAccount(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->createWishlistUserAccount($this->jsonBody($request)),
            'CreateUserAccountFailed',
            'Failed to create user account.'
        );
    }

    #[Route('/signup-requests', name: 'create_public_signup_request', methods: ['POST'])]
    public function createPublicSignupRequest(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->createPublicSignupRequest($this->jsonBody($request)),
            'CreateSignupRequestFailed',
            'Failed to create signup request.'
        );
    }

    #[Route('/wishlist/employee-accounts', name: 'create_wishlist_employee_account', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function createWishlistEmployeeAccount(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->createWishlistEmployeeAccount($this->jsonBody($request)),
            'CreateEmployeeAccountFailed',
            'Failed to create employee account.'
        );
    }

    #[Route('/{accountIdentifier}/approve', name: 'approve_user', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function approveUser(Request $request, int $accountIdentifier): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->approveUser(
                $accountIdentifier,
                $this->jsonBody($request),
                $request->attributes->get('authenticatedIdentity', []),
                $request->headers->get('Authorization', '')
            ),
            'ApproveAccountFailed',
            'Unable to send the invitation.'
        );
    }

    #[Route('/{accountIdentifier}/reject', name: 'reject_user', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function rejectUser(int $accountIdentifier, Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->rejectUser($accountIdentifier, $this->jsonBody($request)),
            'RejectUserFailed',
            'Unable to reject this request.'
        );
    }

    #[Route('/{accountIdentifier}/delete-request', name: 'delete_wishlist_account_request', methods: ['DELETE'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function deleteWishlistAccountRequest(int $accountIdentifier, Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->deleteWishlistAccountRequest(
                $accountIdentifier,
                $this->jsonBody($request),
                $request->attributes->get('authenticatedIdentity', []),
                $request->headers->get('Authorization', '')
            ),
            'DeleteAccountRequestFailed',
            'Unable to delete account request.'
        );
    }

    #[Route('/invite', name: 'invite_user', methods: ['POST'])]
    public function inviteUser(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->inviteUser($this->jsonBody($request)),
            'InviteUserFailed',
            'Unable to send invitation.'
        );
    }

    private function jsonBody(Request $request): array
    {
        $requestBody = json_decode($request->getContent(), true);

        return is_array($requestBody) ? $requestBody : [];
    }

    private function serviceResultResponse(array $result, string $fallbackErrorCode, string $fallbackMessage): JsonResponse
    {
        if (($result['success'] ?? false) !== true) {
            return $this->createErrorResponse(
                (string)($result['errorCode'] ?? $fallbackErrorCode),
                (string)($result['message'] ?? $fallbackMessage),
                (int)($result['status'] ?? 500),
                $result['extra'] ?? []
            );
        }

        return $this->createSuccessResponse($result['data'] ?? [], (int)($result['status'] ?? 200));
    }
}
