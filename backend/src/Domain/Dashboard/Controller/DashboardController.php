<?php

namespace App\Domain\Dashboard\Controller;

use App\Domain\Dashboard\Service\DashboardAggregationService;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/dashboard')]
class DashboardController extends AbstractController
{
    use JsonResponseTrait;

    private DashboardAggregationService $dashboardAggregationService;

    public function __construct(DashboardAggregationService $dashboardAggregationService)
    {
        $this->dashboardAggregationService = $dashboardAggregationService;
    }

    // ===== AI GENERATED: getDashboardSummary =====
    // Purpose: Return aggregated dashboard metrics (Admin only)
    // Inputs: none
    // Returns: JsonResponse with dashboard data

    #[Route('/summary', name: 'dashboard_summary', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getDashboardSummary(): JsonResponse
    {
        $summaryData = $this->dashboardAggregationService->getAdminDashboardSummary();
        return $this->createSuccessResponse($summaryData);
    }

    #[Route('/overview', name: 'dashboard_overview', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getDashboardOverview(Request $request): JsonResponse
    {
        try {
            [$startDate, $endDate] = $this->resolveDateRange($request, 14);
            $overviewData = $this->dashboardAggregationService->getAdminDashboardOverview($startDate, $endDate);

            return $this->createSuccessResponse($overviewData);
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'Dashboard Overview - Error [%s]: %s in %s:%d',
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));

            return $this->createErrorResponse(
                'DashboardOverviewAggregationFailed',
                'Unable to load dashboard overview right now.',
                500
            );
        }
    }

    #[Route('/overview/{section}', name: 'dashboard_overview_section', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getDashboardOverviewSection(Request $request, string $section): JsonResponse
    {
        try {
            [$startDate, $endDate] = $this->resolveDateRange($request, 14);
            $sectionData = $this->dashboardAggregationService->getAdminDashboardOverviewSection($section, $startDate, $endDate);

            return $this->createSuccessResponse($sectionData);
        } catch (\InvalidArgumentException) {
            return $this->createErrorResponse('DashboardSectionNotFound', 'Unknown dashboard overview section.', 404);
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'Dashboard Overview Section - Error [%s:%s]: %s in %s:%d',
                $section,
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));

            return $this->createErrorResponse(
                'DashboardSectionAggregationFailed',
                'Unable to load this dashboard section right now.',
                500
            );
        }
    }

    // ===== AI GENERATED: getBorrowerDashboardSummary =====
    // Purpose: Return dashboard metrics for the authenticated borrower only
    // Inputs: none (uses authenticated user from token)
    // Returns: JsonResponse with borrower-specific dashboard data

    #[Route('/borrower/summary', name: 'borrower_dashboard_summary', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_BORROWER])]
    public function getBorrowerDashboardSummary(Request $request): JsonResponse
    {
        $identity = $request->attributes->get('authenticatedIdentity');
        $borrowerAccountId = $identity['accountIdentifier'] ?? 0;
        $summaryData = $this->dashboardAggregationService->getBorrowerDashboardSummary($borrowerAccountId);
        return $this->createSuccessResponse($summaryData);
    }

    /**
     * @return array{0:\DateTimeImmutable,1:\DateTimeImmutable}
     */
    private function resolveDateRange(Request $request, int $defaultDays): array
    {
        $endDate = $this->parseDate($request->query->get('endDate')) ?? new \DateTimeImmutable('today');
        $startDate = $this->parseDate($request->query->get('startDate')) ?? $endDate->modify(sprintf('-%d days', max(0, $defaultDays - 1)));

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }

    private function parseDate(?string $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable(trim($value));
        } catch (\Throwable) {
            return null;
        }
    }

}
