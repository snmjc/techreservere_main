<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\Service\UserRegistrationWorkflowService;
use App\Domain\Account\Service\AccountSupportingDocumentService;
use App\Shared\Traits\RequestPayloadTrait;
use App\Shared\Traits\ServiceResultResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/users')]
#[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
class WishlistAccountRequestController
{
    use RequestPayloadTrait;
    use ServiceResultResponseTrait;

    public function __construct(
        private readonly UserRegistrationWorkflowService $workflowService,
        private readonly AccountSupportingDocumentService $accountSupportingDocumentService
    )
    {
    }

    #[Route('/wishlist', name: 'list_wishlist_users', methods: ['GET'])]
    public function listWishlistUsers(): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->listWishlistUsers(),
            'WishlistUsersFailed',
            'Unable to load wishlist users.'
        );
    }

    #[Route('/wishlist/admin-accounts', name: 'create_wishlist_admin_account', methods: ['POST'])]
    public function createWishlistAdminAccount(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->createWishlistAdminAccount(
                $this->jsonBody($request),
                $request->attributes->get('authenticatedIdentity', []),
                $request->headers->get('Authorization', '')
            ),
            'CreateAdminAccountFailed',
            'Failed to create admin account.'
        );
    }

    #[Route('/wishlist/user-accounts', name: 'create_wishlist_user_account', methods: ['POST'])]
    public function createWishlistUserAccount(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->createWishlistUserAccount($this->jsonBody($request)),
            'CreateUserAccountFailed',
            'Failed to create user account.'
        );
    }

    #[Route('/wishlist/employee-accounts', name: 'create_wishlist_employee_account', methods: ['POST'])]
    public function createWishlistEmployeeAccount(Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->createWishlistEmployeeAccount($this->jsonBody($request)),
            'CreateEmployeeAccountFailed',
            'Failed to create employee account.'
        );
    }

    #[Route('/{accountIdentifier}/approve', name: 'approve_user', methods: ['POST'])]
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

    #[Route('/{accountIdentifier}/verify-email', name: 'verify_email_and_approve_user', methods: ['POST'])]
    public function verifyEmailAndApproveUser(Request $request, int $accountIdentifier): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->verifyEmailAndApproveUser(
                $accountIdentifier,
                $this->jsonBody($request),
                $request->attributes->get('authenticatedIdentity', []),
                $request->headers->get('Authorization', '')
            ),
            'VerifyEmailApprovalFailed',
            'Unable to approve verified email.'
        );
    }

    #[Route('/{accountIdentifier}/reject', name: 'reject_user', methods: ['POST'])]
    public function rejectUser(int $accountIdentifier, Request $request): JsonResponse
    {
        return $this->serviceResultResponse(
            $this->workflowService->rejectUser(
                $accountIdentifier,
                $this->jsonBody($request),
                $request->attributes->get('authenticatedIdentity', []),
                $request->headers->get('Authorization', '')
            ),
            'RejectUserFailed',
            'Unable to reject this request.'
        );
    }

    #[Route('/{accountIdentifier}/delete-request', name: 'delete_wishlist_account_request', methods: ['DELETE'])]
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

    #[Route('/{accountIdentifier}/supporting-document', name: 'download_wishlist_supporting_document', methods: ['GET'])]
    public function downloadSupportingDocument(int $accountIdentifier): BinaryFileResponse|JsonResponse
    {
        $document = $this->accountSupportingDocumentService->getSupportingDocumentByAccountIdentifier($accountIdentifier);
        if (!$document) {
            return new JsonResponse([
                'errorCode' => 'SupportingDocumentNotFound',
                'errorMessage' => 'Supporting document not found.',
            ], 404);
        }

        $absolutePath = $this->accountSupportingDocumentService->resolveAbsoluteFilePath($document);
        if ($absolutePath === null) {
            return new JsonResponse([
                'errorCode' => 'SupportingDocumentMissing',
                'errorMessage' => 'Supporting document file is no longer available.',
            ], 404);
        }

        $response = new BinaryFileResponse($absolutePath);
        $response->headers->set('Content-Type', (string)($document['signup_supporting_document_mime_type'] ?? 'application/octet-stream'));
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            (string)($document['signup_supporting_document_name'] ?? 'supporting-document')
        );

        return $response;
    }
}
