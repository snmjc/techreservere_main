<?php

namespace App\Domain\Dashboard\Service;

use App\Domain\Account\Service\AccountReadService;
use App\Domain\Equipment\Entity\EquipmentEntity;
use App\Domain\Equipment\Repository\EquipmentRepository;
use App\Domain\ReleaseReturn\Entity\ReleaseReturnEntity;
use App\Domain\ReleaseReturn\Repository\ReleaseReturnRepository;
use App\Domain\Reservation\Entity\ReservationEntity;
use App\Domain\Reservation\Repository\ReservationRepository;
use App\Domain\Venue\Entity\VenueEntity;
use App\Domain\Venue\Repository\VenueRepository;

class DashboardAggregationService
{
    public function __construct(
        private readonly AccountReadService $accountReadService,
        private readonly EquipmentRepository $equipmentRepository,
        private readonly ReservationRepository $reservationRepository,
        private readonly VenueRepository $venueRepository,
        private readonly ReleaseReturnRepository $releaseReturnRepository
    ) {
    }

    public function getAdminDashboardSummary(): array
    {
        $accounts = $this->accountReadService->getAcceptedAccounts();
        $equipment = $this->equipmentRepository->findAllEquipment();
        $reservations = $this->reservationRepository->findAllReservations();
        $releaseReturns = $this->releaseReturnRepository->findAllReleaseReturns();

        $totalAccounts = count($accounts);
        $totalEquipment = count($equipment);
        $totalReservations = count($reservations);
        $pendingReservations = $this->countReservationsByStatuses($reservations, ['Pending', 'Pending Review']);
        $approvedReservations = $this->countReservationsByStatuses($reservations, ['Approved', 'Prepared']);
        $activeReservations = $this->countReservationsByStatuses($reservations, ['Prepared', 'Deployed']);
        $completedReservations = $this->countReservationsByStatuses($reservations, ['Completed', 'Returned']);

        [$totalEquipmentUnits, $availableEquipmentUnits] = $this->summarizeEquipmentInventory($equipment);
        $activeEquipmentCount = max(0, $totalEquipmentUnits - $availableEquipmentUnits);
        $activeFacilityUsageCount = $this->countActiveFacilityReservations($reservations);
        $equipmentUtilizationRate = $totalEquipmentUnits > 0
            ? round(($activeEquipmentCount / $totalEquipmentUnits) * 100, 1)
            : 0.0;

        return [
            'totalAccounts' => $totalAccounts,
            'totalEquipment' => $totalEquipment,
            'totalReservations' => $totalReservations,
            'pendingReservations' => $pendingReservations,
            'approvedReservations' => $approvedReservations,
            'activeReservations' => $activeReservations,
            'completedReservations' => $completedReservations,
            'activeEquipmentCount' => $activeEquipmentCount,
            'activeFacilityUsageCount' => $activeFacilityUsageCount,
            'overdueEquipment' => count($this->buildOverdueReservationMap($reservations, $releaseReturns)),
            'equipmentUtilizationRate' => $equipmentUtilizationRate,
        ];
    }

    public function getAdminDashboardOverview(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $range = $this->normalizeRange($startDate, $endDate);
        $accounts = $this->accountReadService->getAcceptedAccounts();
        $equipment = $this->equipmentRepository->findAllEquipment();
        $venues = $this->venueRepository->findAllVenues();
        $submissionReservations = $this->reservationRepository->findBySubmissionDateRange($range['start'], $range['end']);
        $eventReservations = $this->reservationRepository->findByEventDateRange($range['start'], $range['end']);
        $relevantReservations = $this->mergeReservationsByIdentifier($submissionReservations, $eventReservations);
        $allReservations = $this->reservationRepository->findAllReservations();
        $releaseReturnsInRange = $this->releaseReturnRepository->findByProcessedDateRange($range['start'], $range['end']);
        $allReleaseReturns = $this->releaseReturnRepository->findAllReleaseReturns();
        $overdueReservations = $this->buildOverdueReservationMap($allReservations, $allReleaseReturns);

        [$totalEquipmentUnits, $availableEquipmentUnits] = $this->summarizeEquipmentInventory($equipment);
        $demandByDay = $this->buildDailyDemandMap($submissionReservations, $range['start'], $range['end']);
        $equipmentUsageByDay = $this->buildDailyEquipmentUsageMap($eventReservations, $range['start'], $range['end']);
        $resourceUtilization = $this->buildResourceUtilizationSeries($range['start'], $range['end'], $demandByDay, $equipmentUsageByDay, $totalEquipmentUnits);

        $facilityUsageCount = count(array_filter(
            $relevantReservations,
            static fn (ReservationEntity $reservation): bool => $reservation->getVenueIdentifier() !== null
                && !in_array(self::normalizeStatus($reservation->getCurrentStatus()), ['cancelled', 'rejected'], true)
        ));
        $equipmentUtilizationRate = $totalEquipmentUnits > 0
            ? round((max(0, $totalEquipmentUnits - $availableEquipmentUnits) / $totalEquipmentUnits) * 100, 1)
            : 0.0;
        $facilityUtilizationRate = count($venues) > 0
            ? round((count($this->countDistinctVenueUsage($relevantReservations)) / count($venues)) * 100, 1)
            : 0.0;

        return [
            'dateRange' => [
                'startDate' => $range['start']->format('Y-m-d'),
                'endDate' => $range['end']->format('Y-m-d'),
                'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ],
            'summary' => [
                'totalAccounts' => count($accounts),
                'pendingReservations' => $this->countReservationsByStatuses($relevantReservations, ['Pending', 'Pending Review']),
                'approvedReservations' => $this->countReservationsByStatuses($relevantReservations, ['Approved', 'Prepared']),
                'activeEquipmentCount' => max(0, $totalEquipmentUnits - $availableEquipmentUnits),
                'activeFacilityCount' => $facilityUsageCount,
                'overdueEquipmentCount' => $this->countOverdueReservationsInRange($overdueReservations, $range['start'], $range['end']),
            ],
            'resourceUtilization' => $resourceUtilization,
            'groupedStats' => [
                'equipmentUtilizationRate' => $equipmentUtilizationRate,
                'activeUsers' => count($this->collectDistinctBorrowerIds($relevantReservations)),
                'facilityUtilizationRate' => $facilityUtilizationRate,
                'averageLeadTimeHours' => $this->calculateAverageLeadTimeHours($relevantReservations),
            ],
            'facilityStatus' => $this->buildFacilityStatus($venues, $relevantReservations),
            'readinessAlerts' => $this->buildReadinessAlerts($equipment, $overdueReservations, $range['start'], $range['end']),
            'systemActivity' => $this->buildSystemActivity($allReservations, $relevantReservations, $releaseReturnsInRange, $equipment, $overdueReservations, $range['start'], $range['end']),
        ];
    }

    public function getAdminReportsAnalytics(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $range = $this->normalizeRange($startDate, $endDate);
        $previousRange = $this->buildPreviousRange($range['start'], $range['end']);
        $previousYearRange = [
            'start' => $range['start']->modify('-1 year'),
            'end' => $range['end']->modify('-1 year'),
        ];
        $equipment = $this->equipmentRepository->findAllEquipment();
        $submissionReservations = $this->reservationRepository->findBySubmissionDateRange($range['start'], $range['end']);
        $eventReservations = $this->reservationRepository->findByEventDateRange($range['start'], $range['end']);
        $relevantReservations = $this->mergeReservationsByIdentifier($submissionReservations, $eventReservations);
        $previousSubmissionReservations = $this->reservationRepository->findBySubmissionDateRange($previousRange['start'], $previousRange['end']);
        $previousEventReservations = $this->reservationRepository->findByEventDateRange($previousRange['start'], $previousRange['end']);
        $previousYearEventReservations = $this->reservationRepository->findByEventDateRange($previousYearRange['start'], $previousYearRange['end']);
        $previousRelevantReservations = $this->mergeReservationsByIdentifier($previousSubmissionReservations, $previousEventReservations);
        $releaseReturnsInRange = $this->releaseReturnRepository->findByProcessedDateRange($range['start'], $range['end']);
        $previousReleaseReturns = $this->releaseReturnRepository->findByProcessedDateRange($previousRange['start'], $previousRange['end']);
        $overdueReservations = $this->buildOverdueReservationMap(
            $this->reservationRepository->findAllReservations(),
            $this->releaseReturnRepository->findAllReleaseReturns()
        );

        $forecast = $this->buildForecastData($eventReservations, $previousYearEventReservations, $range['start'], $range['end']);
        $equipmentUsageMap = $this->buildEquipmentUsageMap($relevantReservations, $overdueReservations);
        $previousEquipmentUsageMap = $this->buildEquipmentUsageMap($previousRelevantReservations, []);
        $riskDistribution = $this->buildRiskDistribution($equipment, $equipmentUsageMap, $overdueReservations);
        $currentPending = $this->countReservationsByStatuses($relevantReservations, ['Pending', 'Pending Review']);
        $previousPending = $this->countReservationsByStatuses($previousRelevantReservations, ['Pending', 'Pending Review']);
        $currentResolvedRate = $this->calculateResolvedRate($relevantReservations);
        $previousResolvedRate = $this->calculateResolvedRate($previousRelevantReservations);
        $currentEquipmentUtilization = $this->calculateRequestedEquipmentUtilization($equipment, $equipmentUsageMap);
        $previousEquipmentUtilization = $this->calculateRequestedEquipmentUtilization($equipment, $previousEquipmentUsageMap);

        return [
            'dateRange' => [
                'startDate' => $range['start']->format('Y-m-d'),
                'endDate' => $range['end']->format('Y-m-d'),
                'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ],
            'forecast' => $forecast,
            'riskDistribution' => $riskDistribution,
            'optimizationMetrics' => [
                [
                    'label' => 'Conflict Reduction',
                    'note' => 'vs. previous period',
                    'value' => round($this->calculateConflictReductionDelta($relevantReservations, $previousRelevantReservations), 1),
                    'icon' => 'CR',
                    'tone' => 'tree',
                ],
                [
                    'label' => 'Equipment Utilization',
                    'note' => 'vs. previous period',
                    'value' => round($currentEquipmentUtilization - $previousEquipmentUtilization, 1),
                    'icon' => 'EU',
                    'tone' => 'box',
                ],
                [
                    'label' => 'Constraint Satisfaction',
                    'note' => 'requests resolved',
                    'value' => round($currentResolvedRate, 1),
                    'icon' => 'CS',
                    'tone' => 'check',
                ],
                [
                    'label' => 'Unassigned Requests',
                    'note' => 'vs. previous period',
                    'value' => round($this->calculateDirectionalDelta($previousPending, $currentPending, true), 1),
                    'icon' => 'UR',
                    'tone' => 'alert',
                ],
            ],
            'utilizationByCategory' => $this->buildUtilizationByCategory($equipment, $equipmentUsageMap),
            'topEquipment' => $this->buildTopEquipment($equipment, $equipmentUsageMap),
            'summary' => [
                'totalEquipment' => count($equipment),
                'activeReservations' => $this->countReservationsByStatuses($relevantReservations, ['Approved', 'Prepared', 'Deployed']),
                'pendingRequests' => $currentPending,
                'completedThisPeriod' => $this->countReservationsByStatuses($relevantReservations, ['Completed', 'Returned']),
                'generatedAt' => (new \DateTimeImmutable())->format('M j, Y g:i A'),
            ],
            'releaseReturnActivity' => [
                'releaseCount' => $this->countReleaseReturnsByType($releaseReturnsInRange, 'release'),
                'returnCount' => $this->countReleaseReturnsByType($releaseReturnsInRange, 'return'),
                'releaseCountPrevious' => $this->countReleaseReturnsByType($previousReleaseReturns, 'release'),
                'returnCountPrevious' => $this->countReleaseReturnsByType($previousReleaseReturns, 'return'),
            ],
        ];
    }

    private function normalizeRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $normalizedStart = $startDate->setTime(0, 0, 0);
        $normalizedEnd = $endDate->setTime(23, 59, 59);

        if ($normalizedStart > $normalizedEnd) {
            [$normalizedStart, $normalizedEnd] = [$normalizedEnd->setTime(0, 0, 0), $normalizedStart->setTime(23, 59, 59)];
        }

        return ['start' => $normalizedStart, 'end' => $normalizedEnd];
    }

    private function buildPreviousRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $days = max(1, (int) $startDate->diff($endDate)->format('%a') + 1);
        $previousEnd = $startDate->modify('-1 day')->setTime(23, 59, 59);
        $previousStart = $previousEnd->modify('-' . ($days - 1) . ' days')->setTime(0, 0, 0);

        return ['start' => $previousStart, 'end' => $previousEnd];
    }

    /**
     * @param ReservationEntity[] $reservations
     */
    private function countReservationsByStatuses(array $reservations, array $statuses): int
    {
        $normalizedStatuses = array_map([self::class, 'normalizeStatus'], $statuses);

        return count(array_filter(
            $reservations,
            static fn (ReservationEntity $reservation): bool => in_array(self::normalizeStatus($reservation->getCurrentStatus()), $normalizedStatuses, true)
        ));
    }

    /**
     * @param ReservationEntity[] $reservations
     */
    private function countActiveFacilityReservations(array $reservations): int
    {
        return count(array_filter(
            $reservations,
            static fn (ReservationEntity $reservation): bool => $reservation->getVenueIdentifier() !== null
                && in_array(self::normalizeStatus($reservation->getCurrentStatus()), ['approved', 'prepared', 'deployed'], true)
        ));
    }

    /**
     * @param ReservationEntity[] $submissionReservations
     */
    private function buildForecastData(array $submissionReservations, array $previousSubmissionReservations, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $currentDailyDemandMap = $this->buildDailyDemandMapByEventDate($submissionReservations, $startDate, $endDate);
        $previousYearStart = $startDate->modify('-1 year');
        $previousYearEnd = $endDate->modify('-1 year');
        $previousDailyDemandMap = $this->buildDailyDemandMapByEventDate($previousSubmissionReservations, $previousYearStart, $previousYearEnd);
        $currentLabels = array_keys($currentDailyDemandMap);
        $currentValues = array_values($currentDailyDemandMap);
        $previousValues = array_values($previousDailyDemandMap);
        $forecastValues = $previousValues;
        $peakIndex = $forecastValues === [] ? null : array_keys($forecastValues, max($forecastValues), true)[0];
        $currentTotal = array_sum($currentValues);
        $previousTotal = array_sum($previousValues);

        return [
            'actualSeries' => array_map(
                static fn (string $label, float|int $value): array => ['label' => $label, 'value' => (float) $value],
                $currentLabels,
                $currentValues
            ),
            'forecastSeries' => array_map(
                static fn (string $label, float|int $value): array => ['label' => $label, 'value' => (float) $value],
                $currentLabels,
                $forecastValues
            ),
            'peakDate' => $peakIndex === null ? null : $currentLabels[$peakIndex],
            'peakValue' => $peakIndex === null ? 0.0 : (float) $forecastValues[$peakIndex],
            'growthPercent' => round($this->calculateDirectionalDelta($previousTotal, $currentTotal), 1),
        ];
    }

    /**
     * @param ReservationEntity[] $reservations
     * @return array<string, int>
     */
    private function buildDailyDemandMapByEventDate(array $reservations, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $series = [];
        foreach ($this->buildDateLabels($startDate, $endDate) as $dateKey) {
            $series[$dateKey] = 0;
        }

        foreach ($reservations as $reservation) {
            $dateKey = $reservation->getEventDateTime()->format('Y-m-d');
            if (array_key_exists($dateKey, $series)) {
                $series[$dateKey] += $this->sumRequestedEquipmentQuantity($reservation->getRequestedEquipmentList());
            }
        }

        return $series;
    }

    /**
     * @param ReservationEntity[] $reservations
     * @return array<string, int>
     */
    private function buildDailyDemandMap(array $reservations, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $series = [];
        foreach ($this->buildDateLabels($startDate, $endDate) as $dateKey) {
            $series[$dateKey] = 0;
        }

        foreach ($reservations as $reservation) {
            $dateKey = $reservation->getSubmissionTimestamp()->format('Y-m-d');
            if (array_key_exists($dateKey, $series)) {
                $series[$dateKey] += $this->sumRequestedEquipmentQuantity($reservation->getRequestedEquipmentList());
            }
        }

        return $series;
    }

    /**
     * @param ReservationEntity[] $reservations
     * @return array<string, int>
     */
    private function buildDailyEquipmentUsageMap(array $reservations, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $series = [];
        foreach ($this->buildDateLabels($startDate, $endDate) as $dateKey) {
            $series[$dateKey] = 0;
        }

        foreach ($reservations as $reservation) {
            $dateKey = $reservation->getEventDateTime()->format('Y-m-d');
            if (!array_key_exists($dateKey, $series)) {
                continue;
            }

            $series[$dateKey] += $this->sumRequestedEquipmentQuantity($reservation->getRequestedEquipmentList());
        }

        return $series;
    }

    /**
     * @param array<string, int> $dailyDemandMap
     * @param array<string, int> $equipmentUsageByDay
     * @return array<int, array<string, float|int|string>>
     */
    private function buildResourceUtilizationSeries(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate, array $dailyDemandMap, array $equipmentUsageByDay, int $totalEquipmentUnits): array
    {
        $series = [];

        foreach ($this->buildDateLabels($startDate, $endDate) as $dateKey) {
            $usage = $equipmentUsageByDay[$dateKey] ?? 0;
            $series[] = [
                'date' => $dateKey,
                'label' => (new \DateTimeImmutable($dateKey))->format('M j'),
                'demand' => $dailyDemandMap[$dateKey] ?? 0,
                'utilizationRate' => $totalEquipmentUnits > 0
                    ? round(min(100, ($usage / $totalEquipmentUnits) * 100), 1)
                    : 0.0,
            ];
        }

        return $series;
    }

    /**
     * @param VenueEntity[] $venues
     * @param ReservationEntity[] $reservations
     * @return array<int, array<string, int|string|float>>
     */
    private function buildFacilityStatus(array $venues, array $reservations): array
    {
        $grouped = [];

        foreach ($venues as $venue) {
            $type = $this->inferVenueType($venue->getVenueName());
            if (!isset($grouped[$type])) {
                $grouped[$type] = ['name' => $type, 'total' => 0, 'occupied' => 0];
            }

            $grouped[$type]['total']++;
        }

        $activeVenueIds = $this->countDistinctVenueUsage(array_filter(
            $reservations,
            static fn (ReservationEntity $reservation): bool => $reservation->getVenueIdentifier() !== null
                && !in_array(self::normalizeStatus($reservation->getCurrentStatus()), ['cancelled', 'rejected'], true)
        ));

        foreach ($activeVenueIds as $venueId => $_true) {
            foreach ($venues as $venue) {
                if ($venue->getVenueIdentifier() !== $venueId) {
                    continue;
                }

                $type = $this->inferVenueType($venue->getVenueName());
                if (isset($grouped[$type])) {
                    $grouped[$type]['occupied']++;
                }
                break;
            }
        }

        return array_values(array_map(function (array $group): array {
            $total = max(0, (int) $group['total']);
            $occupied = min($total, max(0, (int) $group['occupied']));
            $percent = $total > 0 ? round(($occupied / $total) * 100, 1) : 0.0;

            return [
                'name' => (string) $group['name'],
                'total' => $total,
                'occupied' => $occupied,
                'percent' => $percent,
                'statusLabel' => $this->describeFacilityOccupancy($percent),
            ];
        }, $grouped));
    }

    /**
     * @param EquipmentEntity[] $equipment
     * @param array<int, ReservationEntity> $overdueReservations
     * @return array<int, array<string, int|string>>
     */
    private function buildReadinessAlerts(array $equipment, array $overdueReservations, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $lowStockCount = 0;
        $inactiveEquipmentCount = 0;

        foreach ($equipment as $equipmentRecord) {
            $totalQuantity = max(0, $equipmentRecord->getTotalQuantity());
            $availableQuantity = max(0, $equipmentRecord->getAvailableQuantity());
            $availabilityRatio = $totalQuantity > 0 ? $availableQuantity / $totalQuantity : 0.0;

            if ($totalQuantity > 0 && $availabilityRatio <= 0.2) {
                $lowStockCount++;
            }

            if (
                self::normalizeText($equipmentRecord->getEquipmentState()) !== 'available'
                || self::normalizeText($equipmentRecord->getOperationalStatus()) !== 'active'
            ) {
                $inactiveEquipmentCount++;
            }
        }

        return [
            [
                'severity' => 'High',
                'count' => $this->countOverdueReservationsInRange($overdueReservations, $startDate, $endDate),
                'className' => 'is-high',
                'title' => 'Released items awaiting return',
                'detail' => 'Reservations with release activity but no recorded return beyond the scheduled event date.',
            ],
            [
                'severity' => 'Medium',
                'count' => $lowStockCount,
                'className' => 'is-medium',
                'title' => 'Low stock equipment',
                'detail' => 'Equipment with available units at or below twenty percent of total inventory.',
            ],
            [
                'severity' => 'Low',
                'count' => $inactiveEquipmentCount,
                'className' => 'is-low',
                'title' => 'Equipment needing attention',
                'detail' => 'Equipment currently marked unavailable or inactive in inventory records.',
            ],
        ];
    }

    /**
     * @param ReservationEntity[] $allReservations
     * @param ReservationEntity[] $relevantReservations
     * @param ReleaseReturnEntity[] $releaseReturnsInRange
     * @param EquipmentEntity[] $equipment
     * @param array<int, ReservationEntity> $overdueReservations
     * @return array<int, array<string, int|string>>
     */
    private function buildSystemActivity(array $allReservations, array $relevantReservations, array $releaseReturnsInRange, array $equipment, array $overdueReservations, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $todayKey = (new \DateTimeImmutable('now'))->format('Y-m-d');
        $requestsToday = count(array_filter(
            $allReservations,
            static fn (ReservationEntity $reservation): bool => $reservation->getSubmissionTimestamp()->format('Y-m-d') === $todayKey
        ));
        $approvalCount = $this->countReservationsByStatuses($relevantReservations, ['Approved', 'Prepared', 'Deployed', 'Completed', 'Returned']);
        $releaseCount = $this->countReleaseReturnsByType($releaseReturnsInRange, 'release');
        $returnCount = $this->countReleaseReturnsByType($releaseReturnsInRange, 'return');
        $alertCount = array_sum(array_map(
            static fn (array $alert): int => (int) $alert['count'],
            $this->buildReadinessAlerts($equipment, $overdueReservations, $startDate, $endDate)
        ));

        return [
            [
                'label' => 'New requests today',
                'value' => $requestsToday,
                'meta' => 'Submitted across all reservation types today.',
            ],
            [
                'label' => 'Approvals processed',
                'value' => $approvalCount,
                'meta' => 'Requests currently approved or further along in the selected range.',
            ],
            [
                'label' => 'Equipment releases / returns',
                'value' => $releaseCount + $returnCount,
                'meta' => sprintf('%d released, %d returned', $releaseCount, $returnCount),
            ],
            [
                'label' => 'Readiness alerts generated',
                'value' => $alertCount,
                'meta' => 'Derived from overdue reservations, stock pressure, and inactive equipment.',
            ],
        ];
    }

    /**
     * @param EquipmentEntity[] $equipment
     * @param array<string, array{name:string,usageCount:int,totalQuantity:int,category:string,overdue:bool,issues:array<int, string>}> $equipmentUsageMap
     * @return array<string, mixed>
     */
    private function buildRiskDistribution(array $equipment, array $equipmentUsageMap, array $overdueReservations): array
    {
        $factorCounts = [
            'Low stock pressure' => 0,
            'Inactive availability state' => 0,
            'Overdue release linkage' => 0,
            'High usage frequency' => 0,
        ];
        $distribution = [
            ['label' => 'High Risk', 'count' => 0, 'color' => '#ef4444'],
            ['label' => 'Medium Risk', 'count' => 0, 'color' => '#f59e0b'],
            ['label' => 'Low Risk', 'count' => 0, 'color' => '#facc15'],
            ['label' => 'Very Low Risk', 'count' => 0, 'color' => '#16a34a'],
        ];

        foreach ($equipment as $equipmentRecord) {
            $nameKey = self::normalizeText($equipmentRecord->getEquipmentName());
            $usageRecord = $equipmentUsageMap[$nameKey] ?? [
                'usageCount' => 0,
                'overdue' => false,
            ];

            $score = 0;
            $totalQuantity = max(0, $equipmentRecord->getTotalQuantity());
            $availableQuantity = max(0, $equipmentRecord->getAvailableQuantity());
            $availabilityRatio = $totalQuantity > 0 ? $availableQuantity / $totalQuantity : 1.0;

            if ($totalQuantity > 0 && $availabilityRatio <= 0.2) {
                $score += 3;
                $factorCounts['Low stock pressure']++;
            }

            if (
                self::normalizeText($equipmentRecord->getEquipmentState()) !== 'available'
                || self::normalizeText($equipmentRecord->getOperationalStatus()) !== 'active'
            ) {
                $score += 3;
                $factorCounts['Inactive availability state']++;
            }

            if (!empty($usageRecord['overdue'])) {
                $score += 3;
                $factorCounts['Overdue release linkage']++;
            }

            if (($usageRecord['usageCount'] ?? 0) >= 3) {
                $score += 2;
                $factorCounts['High usage frequency']++;
            }

            if ($score >= 6) {
                $distribution[0]['count']++;
            } elseif ($score >= 4) {
                $distribution[1]['count']++;
            } elseif ($score >= 2) {
                $distribution[2]['count']++;
            } else {
                $distribution[3]['count']++;
            }
        }

        arsort($factorCounts);
        $topRiskFactors = array_slice(array_keys($factorCounts), 0, 4);
        $totalEquipment = array_sum(array_map(static fn (array $band): int => (int) $band['count'], $distribution));
        $safeEquipment = ($distribution[2]['count'] ?? 0) + ($distribution[3]['count'] ?? 0);
        $safeRate = $totalEquipment > 0 ? round(($safeEquipment / $totalEquipment) * 100) : 0;

        return [
            'bands' => $distribution,
            'topRiskFactors' => $topRiskFactors,
            'safeRate' => $safeRate,
            'overdueReservations' => count($overdueReservations),
        ];
    }

    /**
     * @param ReservationEntity[] $reservations
     * @param array<int, ReservationEntity> $overdueReservations
     * @return array<string, array{name:string,usageCount:int,totalQuantity:int,category:string,overdue:bool}>
     */
    private function buildEquipmentUsageMap(array $reservations, array $overdueReservations): array
    {
        $usageMap = [];

        foreach ($reservations as $reservation) {
            $equipmentEntries = $this->extractRequestedEquipmentEntries($reservation->getRequestedEquipmentList());
            foreach ($equipmentEntries as $entry) {
                $nameKey = self::normalizeText($entry['name']);
                if ($nameKey === '') {
                    continue;
                }

                if (!isset($usageMap[$nameKey])) {
                    $usageMap[$nameKey] = [
                        'name' => $entry['name'],
                        'usageCount' => 0,
                        'category' => 'Others',
                        'totalQuantity' => 0,
                        'overdue' => false,
                    ];
                }

                $usageMap[$nameKey]['usageCount'] += max(1, (int) $entry['quantity']);
                if ($reservation->getReservationIdentifier() !== null && isset($overdueReservations[$reservation->getReservationIdentifier()])) {
                    $usageMap[$nameKey]['overdue'] = true;
                }
            }
        }

        return $usageMap;
    }

    /**
     * @param EquipmentEntity[] $equipment
     * @param array<string, array{name:string,usageCount:int,totalQuantity:int,category:string,overdue:bool}> $equipmentUsageMap
     * @return array<int, array{label:string,value:float}>
     */
    private function buildUtilizationByCategory(array $equipment, array $equipmentUsageMap): array
    {
        $categoryUsage = [];
        $categoryTotals = [];

        foreach ($equipment as $equipmentRecord) {
            $nameKey = self::normalizeText($equipmentRecord->getEquipmentName());
            $category = $equipmentRecord->getEquipmentCategory() ?: 'Others';
            $categoryTotals[$category] = ($categoryTotals[$category] ?? 0) + max(0, $equipmentRecord->getTotalQuantity());

            if (!isset($categoryUsage[$category])) {
                $categoryUsage[$category] = 0;
            }

            $categoryUsage[$category] += $equipmentUsageMap[$nameKey]['usageCount'] ?? 0;
        }

        $result = [];
        foreach ($categoryTotals as $category => $total) {
            $result[] = [
                'label' => $category,
                'value' => $total > 0 ? round(min(100, (($categoryUsage[$category] ?? 0) / $total) * 100), 1) : 0.0,
            ];
        }

        usort($result, static fn (array $left, array $right): int => strcmp($left['label'], $right['label']));

        return $result;
    }

    /**
     * @param EquipmentEntity[] $equipment
     * @param array<string, array{name:string,usageCount:int,totalQuantity:int,category:string,overdue:bool}> $equipmentUsageMap
     * @return array<int, array{name:string,count:int,rate:float}>
     */
    private function buildTopEquipment(array $equipment, array $equipmentUsageMap): array
    {
        $inventoryLookup = [];
        foreach ($equipment as $equipmentRecord) {
            $inventoryLookup[self::normalizeText($equipmentRecord->getEquipmentName())] = $equipmentRecord;
        }

        $items = [];
        foreach ($equipmentUsageMap as $nameKey => $usageRecord) {
            $equipmentRecord = $inventoryLookup[$nameKey] ?? null;
            $totalQuantity = $equipmentRecord?->getTotalQuantity() ?? max(1, (int) $usageRecord['usageCount']);
            $items[] = [
                'name' => $usageRecord['name'],
                'count' => (int) $usageRecord['usageCount'],
                'rate' => round(min(100, ((int) $usageRecord['usageCount'] / max(1, $totalQuantity)) * 100), 1),
            ];
        }

        usort($items, static fn (array $left, array $right): int => $right['count'] <=> $left['count']);

        return array_slice($items, 0, 5);
    }

    /**
     * @param EquipmentEntity[] $equipment
     * @param array<string, array{name:string,usageCount:int,totalQuantity:int,category:string,overdue:bool}> $equipmentUsageMap
     */
    private function calculateRequestedEquipmentUtilization(array $equipment, array $equipmentUsageMap): float
    {
        [$totalEquipmentUnits] = $this->summarizeEquipmentInventory($equipment);
        $requestedQuantity = 0;
        foreach ($equipmentUsageMap as $usageRecord) {
            $requestedQuantity += (int) $usageRecord['usageCount'];
        }

        return $totalEquipmentUnits > 0 ? round(min(100, ($requestedQuantity / $totalEquipmentUnits) * 100), 1) : 0.0;
    }

    /**
     * @param ReservationEntity[] $reservations
     */
    private function calculateAverageLeadTimeHours(array $reservations): float
    {
        $totalHours = 0.0;
        $count = 0;

        foreach ($reservations as $reservation) {
            $leadSeconds = $reservation->getEventDateTime()->getTimestamp() - $reservation->getSubmissionTimestamp()->getTimestamp();
            if ($leadSeconds <= 0) {
                continue;
            }

            $totalHours += $leadSeconds / 3600;
            $count++;
        }

        return $count > 0 ? round($totalHours / $count, 1) : 0.0;
    }

    /**
     * @param ReservationEntity[] $reservations
     */
    private function calculateResolvedRate(array $reservations): float
    {
        $total = count($reservations);
        if ($total === 0) {
            return 0.0;
        }

        $resolved = $this->countReservationsByStatuses($reservations, ['Approved', 'Prepared', 'Deployed', 'Completed', 'Returned']);

        return round(($resolved / $total) * 100, 1);
    }

    /**
     * @param ReservationEntity[] $reservations
     */
    private function calculateConflictReductionDelta(array $reservations, array $previousReservations): float
    {
        $currentUnresolvedRate = $this->calculateUnresolvedRate($reservations);
        $previousUnresolvedRate = $this->calculateUnresolvedRate($previousReservations);

        if ($previousUnresolvedRate <= 0.0) {
            return $currentUnresolvedRate <= 0.0 ? 0.0 : -100.0;
        }

        return (($previousUnresolvedRate - $currentUnresolvedRate) / $previousUnresolvedRate) * 100;
    }

    /**
     * @param ReservationEntity[] $reservations
     */
    private function calculateUnresolvedRate(array $reservations): float
    {
        $total = count($reservations);
        if ($total === 0) {
            return 0.0;
        }

        $pending = $this->countReservationsByStatuses($reservations, ['Pending', 'Pending Review']);
        return ($pending / $total) * 100;
    }

    private function calculateDirectionalDelta(float|int $previousValue, float|int $currentValue, bool $inverse = false): float
    {
        $previous = (float) $previousValue;
        $current = (float) $currentValue;

        if ($previous === 0.0) {
            if ($current === 0.0) {
                return 0.0;
            }

            return $inverse ? -100.0 : 100.0;
        }

        $delta = (($current - $previous) / $previous) * 100;

        return $inverse ? -$delta : $delta;
    }

    /**
     * @param ReservationEntity[] $reservations
     * @param ReleaseReturnEntity[] $releaseReturns
     * @return array<int, ReservationEntity>
     */
    private function buildOverdueReservationMap(array $reservations, array $releaseReturns): array
    {
        $returnsByReservation = [];
        foreach ($releaseReturns as $releaseReturn) {
            $reservationId = $releaseReturn->getReservationIdentifier();
            $transactionType = self::normalizeText($releaseReturn->getTransactionType());

            if (!isset($returnsByReservation[$reservationId])) {
                $returnsByReservation[$reservationId] = ['released' => false, 'returned' => false];
            }

            if ($transactionType === 'release') {
                $returnsByReservation[$reservationId]['released'] = true;
            }
            if ($transactionType === 'return') {
                $returnsByReservation[$reservationId]['returned'] = true;
            }
        }

        $overdue = [];
        $now = new \DateTimeImmutable('now');

        foreach ($reservations as $reservation) {
            $reservationId = $reservation->getReservationIdentifier();
            if ($reservationId === null) {
                continue;
            }

            $releaseState = $returnsByReservation[$reservationId] ?? ['released' => false, 'returned' => false];
            if (!$releaseState['released'] || $releaseState['returned']) {
                continue;
            }

            if ($reservation->getEventDateTime() >= $now) {
                continue;
            }

            $overdue[$reservationId] = $reservation;
        }

        return $overdue;
    }

    /**
     * @param array<int, ReservationEntity> $overdueReservations
     */
    private function countOverdueReservationsInRange(array $overdueReservations, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): int
    {
        return count(array_filter(
            $overdueReservations,
            static fn (ReservationEntity $reservation): bool => $reservation->getEventDateTime() >= $startDate
                && $reservation->getEventDateTime() <= $endDate
        ));
    }

    /**
     * @param ReservationEntity[] $reservations
     * @return array<int, bool>
     */
    private function countDistinctVenueUsage(array $reservations): array
    {
        $venueIds = [];
        foreach ($reservations as $reservation) {
            if ($reservation->getVenueIdentifier() !== null) {
                $venueIds[(int) $reservation->getVenueIdentifier()] = true;
            }
        }

        return $venueIds;
    }

    /**
     * @param ReservationEntity[] $reservations
     * @return array<int, bool>
     */
    private function collectDistinctBorrowerIds(array $reservations): array
    {
        $borrowerIds = [];
        foreach ($reservations as $reservation) {
            $borrowerIds[$reservation->getBorrowerAccountId()] = true;
        }

        return $borrowerIds;
    }

    /**
     * @param EquipmentEntity[] $equipment
     * @return array{0:int,1:int}
     */
    private function summarizeEquipmentInventory(array $equipment): array
    {
        $totalEquipmentUnits = 0;
        $availableEquipmentUnits = 0;

        foreach ($equipment as $equipmentRecord) {
            $totalEquipmentUnits += max(0, $equipmentRecord->getTotalQuantity());
            $availableEquipmentUnits += max(0, $equipmentRecord->getAvailableQuantity());
        }

        return [$totalEquipmentUnits, $availableEquipmentUnits];
    }

    /**
     * @param ReservationEntity[] $reservations
     * @return array<int, ReservationEntity>
     */
    private function mergeReservationsByIdentifier(array ...$reservations): array
    {
        $merged = [];
        foreach ($reservations as $reservationSet) {
            foreach ($reservationSet as $reservation) {
                $reservationId = $reservation->getReservationIdentifier();
                if ($reservationId === null) {
                    continue;
                }

                $merged[$reservationId] = $reservation;
            }
        }

        ksort($merged);
        return array_values($merged);
    }

    /**
     * @return array<int, string>
     */
    private function buildDateLabels(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $dates = [];
        $cursor = $startDate;

        while ($cursor <= $endDate) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor = $cursor->modify('+1 day');
        }

        return $dates;
    }

    private function inferVenueType(string $venueName): string
    {
        $normalized = self::normalizeText($venueName);

        return match (true) {
            str_contains($normalized, 'classroom') => 'Classrooms',
            str_contains($normalized, 'laboratory'), str_contains($normalized, ' lab') => 'Laboratories',
            str_contains($normalized, 'audio visual'), str_contains($normalized, 'avr') => 'Audio Visual Room',
            str_contains($normalized, 'multipurpose'), str_contains($normalized, 'mpr') => 'Multipurpose Room',
            default => 'Other',
        };
    }

    private function describeFacilityOccupancy(float $percent): string
    {
        return match (true) {
            $percent >= 90 => 'Near full usage',
            $percent >= 60 => 'Moderate usage',
            $percent > 0 => 'Light usage',
            default => 'Currently available',
        };
    }

    /**
     * @return array<int, array{name:string,quantity:int}>
     */
    private function extractRequestedEquipmentEntries(array $requestedEquipmentList): array
    {
        $equipmentEntries = [];

        foreach ($requestedEquipmentList as $equipmentItem) {
            if (is_string($equipmentItem)) {
                $name = trim($equipmentItem);
                if ($name !== '') {
                    $equipmentEntries[] = ['name' => $name, 'quantity' => 1];
                }
                continue;
            }

            if (!is_array($equipmentItem)) {
                continue;
            }

            $name = trim((string) ($equipmentItem['name'] ?? $equipmentItem['equipmentName'] ?? ''));
            if ($name === '') {
                continue;
            }

            $quantity = (int) ($equipmentItem['quantity']
                ?? $equipmentItem['selectedQuantity']
                ?? $equipmentItem['itemCount']
                ?? 1);

            $equipmentEntries[] = [
                'name' => $name,
                'quantity' => max(1, $quantity),
            ];
        }

        return $equipmentEntries;
    }

    private function sumRequestedEquipmentQuantity(array $requestedEquipmentList): int
    {
        $quantity = 0;
        foreach ($this->extractRequestedEquipmentEntries($requestedEquipmentList) as $equipmentEntry) {
            $quantity += $equipmentEntry['quantity'];
        }

        return $quantity;
    }

    /**
     * @param ReleaseReturnEntity[] $releaseReturns
     */
    private function countReleaseReturnsByType(array $releaseReturns, string $transactionType): int
    {
        $normalizedType = self::normalizeText($transactionType);
        return count(array_filter(
            $releaseReturns,
            static fn (ReleaseReturnEntity $releaseReturn): bool => self::normalizeText($releaseReturn->getTransactionType()) === $normalizedType
        ));
    }

    private static function normalizeStatus(string $status): string
    {
        return self::normalizeText($status);
    }

    private static function normalizeText(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    public function getBorrowerDashboardSummary(int $borrowerAccountId): array
    {
        $userReservations = $this->reservationRepository->findByBorrowerAccountId($borrowerAccountId);

        $activeReservations = 0;
        $approvedRequests = 0;
        $pendingRequests = 0;
        $completedReservations = 0;

        foreach ($userReservations as $reservation) {
            $status = $reservation->getCurrentStatus();
            switch ($status) {
                case 'Prepared':
                case 'Deployed':
                    $activeReservations++;
                    break;
                case 'Approved':
                    $approvedRequests++;
                    break;
                case 'Pending':
                case 'Pending Review':
                    $pendingRequests++;
                    break;
                case 'Completed':
                case 'Returned':
                    $completedReservations++;
                    break;
            }
        }

        return [
            'activeReservations' => $activeReservations,
            'approvedRequests' => $approvedRequests,
            'pendingRequests' => $pendingRequests,
            'completedReservations' => $completedReservations,
        ];
    }
}
