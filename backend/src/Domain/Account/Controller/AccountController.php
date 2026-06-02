<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\Service\AccountWorkflowService;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/accounts')]
class AccountController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(private readonly AccountWorkflowService $workflowService)
    {
    }

    #[Route('/me', name: 'account_get_my_profile', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_STAFF, RoleConstants::ROLE_DEVELOPER])]
    public function getMyProfile(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->getMyProfile($request->attributes->get('authenticatedIdentity', []))
        );
    }

    #[Route('/me/settings', name: 'account_get_my_settings', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_STAFF, RoleConstants::ROLE_DEVELOPER])]
    public function getMySettings(Request $request): JsonResponse
    {
        return $this->serviceResultResponse($this->workflowService->getMySettings($request));
    }

    #[Route('/me/settings', name: 'account_update_my_settings', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_STAFF, RoleConstants::ROLE_DEVELOPER])]
    public function updateMySettings(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->updateMySettings($request, $this->decodedJson($request))
        );
    }

    #[Route('/me/password', name: 'account_update_my_password', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_STAFF, RoleConstants::ROLE_DEVELOPER])]
    public function updateMyPassword(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->updateMyPassword($request, $this->decodedJson($request))
        );
    }

    #[Route('/me/password/sync-from-clerk', name: 'account_sync_clerk_password', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_STAFF, RoleConstants::ROLE_DEVELOPER])]
    public function syncPasswordFromClerk(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->syncPasswordFromClerk($request, $this->decodedJson($request))
        );
    }

    #[Route('', name: 'account_get_all', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getAllAccounts(): JsonResponse
    {
        return $this->serviceResultResponse($this->workflowService->getAllAccounts());
    }

    #[Route('/{accountIdentifier}', name: 'account_get_by_id', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getAccountById(int $accountIdentifier): JsonResponse
    {
        return $this->serviceResultResponse($this->workflowService->getAccountById($accountIdentifier));
    }

    #[Route('/{accountIdentifier}', name: 'account_update', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateAccount(int $accountIdentifier, Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->updateAccount($accountIdentifier, $this->jsonBody($request))
        );
    }

    #[Route('/me/work-logs', name: 'account_my_employee_work_logs', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_STAFF, RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getMyEmployeeWorkLogs(Request $request): JsonResponse
    {
        return $this->serviceResultResponse($this->workflowService->getMyEmployeeWorkLogs($request));
    }

    #[Route('/{accountIdentifier}/work-logs', name: 'account_employee_work_logs', requirements: ['accountIdentifier' => '\d+'], methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getEmployeeWorkLogs(int $accountIdentifier): JsonResponse
    {
        return $this->serviceResultResponse($this->workflowService->getEmployeeWorkLogs($accountIdentifier));
    }

    #[Route('/{accountIdentifier}/admin-details', name: 'account_update_admin_details', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateAdminAccountDetails(int $accountIdentifier, Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->updateAdminAccountDetails($accountIdentifier, $this->jsonBody($request))
        );
    }

    #[Route('/{accountIdentifier}/access', name: 'account_update_access', requirements: ['accountIdentifier' => '\d+'], methods: ['PATCH'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateAccountAccess(int $accountIdentifier, Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->updateAccountAccess($accountIdentifier, $request, $this->jsonBody($request))
        );
    }

    #[Route('/{accountIdentifier}', name: 'account_delete', requirements: ['accountIdentifier' => '\d+'], methods: ['DELETE'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function deleteAccount(int $accountIdentifier, Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->deleteAccount($accountIdentifier, $request, $this->jsonBody($request))
        );
    }

    private function jsonBody(Request $request): array
    {
        $requestBody = $this->decodedJson($request);

        return is_array($requestBody) ? $requestBody : [];
    }

    private function decodedJson(Request $request): mixed
    {
        return json_decode($request->getContent(), true);
    }

    private function serviceResultResponse(array $result): JsonResponse
    {
        if (($result['success'] ?? false) !== true) {
            return $this->createErrorResponse(
                (string)($result['errorCode'] ?? 'RequestFailed'),
                (string)($result['message'] ?? 'Unable to complete the request.'),
                (int)($result['status'] ?? 500),
                $result['extra'] ?? []
            );
        }

        return $this->createSuccessResponse($result['data'] ?? [], (int)($result['status'] ?? 200));
    }
}
