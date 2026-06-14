<?php

namespace App\Domain\Analytics\Controller;

use App\Domain\Dashboard\Service\DashboardAggregationService;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/reports-analytics')]
class ReportsAnalyticsController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly DashboardAggregationService $dashboardAggregationService
    ) {
    }

    #[Route('', name: 'reports_analytics_overview', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getReportsAnalytics(Request $request): JsonResponse
    {
        [$startDate, $endDate] = $this->resolveDateRange($request, 16);
        $reportData = $this->dashboardAggregationService->getAdminReportsAnalytics($startDate, $endDate);

        return $this->createSuccessResponse($reportData);
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
