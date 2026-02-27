<?php

namespace App\Domain\Dashboard\Controller;

use App\Domain\Dashboard\Service\DashboardAggregationService;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
}
