<?php

namespace App\Domain\Support\Controller;

use App\Domain\Account\Service\AuthenticatedAccountResolver;
use App\Domain\Support\Service\SupportCenterService;
use App\Shared\Exceptions\DomainValidationException;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/support')]
class SupportCenterController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly SupportCenterService $supportCenterService,
        private readonly AuthenticatedAccountResolver $authenticatedAccountResolver
    ) {
    }

    #[Route('/feedback', name: 'support_feedback_list', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function listFeedback(Request $request): JsonResponse
    {
        $accountIdentifier = $this->authenticatedAccountResolver->resolveAccountIdentifier($request);
        $resolvedRole = (string) $request->attributes->get('resolvedRole', RoleConstants::ROLE_BORROWER);

        return $this->createSuccessResponse([
            'feedback' => $this->supportCenterService->listFeedback($accountIdentifier, $resolvedRole),
        ]);
    }

    #[Route('/feedback', name: 'support_feedback_create', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function createFeedback(Request $request): JsonResponse
    {
        try {
            $accountIdentifier = $this->authenticatedAccountResolver->resolveAccountIdentifier($request);
            $payload = json_decode($request->getContent(), true) ?? [];

            return $this->createSuccessResponse(
                $this->supportCenterService->createFeedback($accountIdentifier, $payload),
                201
            );
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse($exception->getErrorType(), $exception->getMessage(), 422);
        }
    }

    #[Route('/feedback/{feedbackIdentifier}/status', name: 'support_feedback_status', methods: ['PATCH'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateFeedbackStatus(int $feedbackIdentifier, Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true) ?? [];
            return $this->createSuccessResponse(
                $this->supportCenterService->updateFeedbackStatus($feedbackIdentifier, $payload)
            );
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse($exception->getErrorType(), $exception->getMessage(), 422);
        }
    }

    #[Route('/damage-reports', name: 'support_damage_report_list', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function listDamageReports(Request $request): JsonResponse
    {
        $accountIdentifier = $this->authenticatedAccountResolver->resolveAccountIdentifier($request);
        $resolvedRole = (string) $request->attributes->get('resolvedRole', RoleConstants::ROLE_BORROWER);

        return $this->createSuccessResponse([
            'damageReports' => $this->supportCenterService->listDamageReports($accountIdentifier, $resolvedRole),
        ]);
    }

    #[Route('/damage-reports', name: 'support_damage_report_create', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function createDamageReport(Request $request): JsonResponse
    {
        try {
            $accountIdentifier = $this->authenticatedAccountResolver->resolveAccountIdentifier($request);
            $payload = json_decode($request->getContent(), true) ?? [];

            return $this->createSuccessResponse(
                $this->supportCenterService->createDamageReport($accountIdentifier, $payload),
                201
            );
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse($exception->getErrorType(), $exception->getMessage(), 422);
        }
    }

    #[Route('/damage-reports/{damageReportIdentifier}/status', name: 'support_damage_report_status', methods: ['PATCH'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateDamageReportStatus(int $damageReportIdentifier, Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true) ?? [];
            return $this->createSuccessResponse(
                $this->supportCenterService->updateDamageReportStatus($damageReportIdentifier, $payload)
            );
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse($exception->getErrorType(), $exception->getMessage(), 422);
        }
    }
}
