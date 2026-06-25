export function createEmptyReport() {
  const emptyUtilization = createEmptyUtilizationReport();

  return {
    forecast: createEmptyForecastReport(),
    riskDistribution: createEmptyRiskReport(),
    optimizationMetrics: [],
    utilizationByCategory: emptyUtilization.items,
    utilizationComparisonByCategory: emptyUtilization.comparisonItems,
    topEquipment: emptyUtilization.topEquipment,
    possibleBorrowedEquipment: emptyUtilization.possibleBorrowedEquipment,
    summary: createEmptySummaryReport(),
  };
}

export function createEmptyForecastReport() {
  return {
    actualSeries: [],
    forecastSeries: [],
    historySeries: [],
    peakDate: null,
    peakValue: 0,
    growthPercent: 0,
  };
}

export function createEmptyRiskReport() {
  return {
    bands: [],
    topRiskFactors: [],
    highRiskEquipment: [],
    safeRate: 0,
  };
}

export function createEmptyUtilizationReport() {
  return {
    items: [],
    comparisonItems: [],
    topEquipment: [],
    possibleBorrowedEquipment: [],
  };
}

export function createEmptySummaryReport() {
  return {
    totalEquipment: 0,
    activeReservations: 0,
    pendingRequests: 0,
    completedThisPeriod: 0,
    generatedAt: 'N/A',
  };
}

export function hasRiskDistribution(riskDistribution) {
  return Array.isArray(riskDistribution?.bands) && riskDistribution.bands.length > 0;
}

export function normalizeStoredAnalyticsResponse(response) {
  const analyticsRun = response?.analyticsServiceResponse || response || {};
  const payloadByType = buildAnalyticsPayloadMap(analyticsRun.results);
  const requiredPayloads = ['forecast', 'readiness', 'allocation'];
  const missingPayloads = requiredPayloads.filter((key) => !payloadByType[key]);
  if (missingPayloads.length > 0) {
    throw new Error(`FastAPI analytics response is missing: ${missingPayloads.join(', ')}.`);
  }

  const normalized = createEmptyReport();

  normalized.forecast = normalizeForecastPayload(resolveForecastPayload(payloadByType));
  normalized.riskDistribution = normalizeReadinessPayload(resolveReadinessPayload(payloadByType));

  const allocationAnalytics = normalizeAllocationPayload(resolveAllocationPayload(payloadByType), analyticsRun);
  normalized.optimizationMetrics = allocationAnalytics.optimizationMetrics;
  normalized.utilizationByCategory = allocationAnalytics.utilizationByCategory;
  normalized.utilizationComparisonByCategory = allocationAnalytics.utilizationComparisonByCategory;
  normalized.topEquipment = allocationAnalytics.topEquipment;
  normalized.possibleBorrowedEquipment = allocationAnalytics.possibleBorrowedEquipment;
  normalized.summary = allocationAnalytics.summary;

  return normalized;
}

function buildAnalyticsPayloadMap(resultList) {
  if (resultList && !Array.isArray(resultList) && typeof resultList === 'object') {
    return Object.fromEntries(
      Object.entries(resultList).map(([resultType, payload]) => [
        String(resultType).toLowerCase(),
        payload || {},
      ]),
    );
  }

  if (!Array.isArray(resultList)) {
    return {};
  }

  return Object.fromEntries(
    resultList.map((result) => [
      String(result?.result_type || result?.resultType || '').toLowerCase(),
      result?.result_payload || result?.resultPayload || {},
    ]),
  );
}

function resolveForecastPayload(payloadByType) {
  return payloadByType.sarima || payloadByType.forecast || {};
}

function resolveReadinessPayload(payloadByType) {
  return payloadByType.random_forest || payloadByType.readiness || {};
}

function resolveAllocationPayload(payloadByType) {
  return payloadByType.binary_linear_programming || payloadByType.allocation || {};
}

function normalizeForecastPayload(forecastPayload) {
  const forecastPeak = forecastPayload.forecastPeak || forecastPayload.forecast_peak || {};
  const summary = forecastPayload.summary || {};

  return {
    actualSeries: (forecastPayload.actualSeries || forecastPayload.actual_series || []).map(normalizeSeriesPoint),
    forecastSeries: (forecastPayload.forecastSeries || forecastPayload.forecast_series || []).map(normalizeSeriesPoint),
    historySeries: (forecastPayload.historySeries || forecastPayload.history_series || []).map(normalizeSeriesPoint),
    peakDate: forecastPayload.peakDate || forecastPayload.peak_date || forecastPeak.date || '',
    peakValue: Number(forecastPayload.peakValue || forecastPayload.peak_value || forecastPeak.value || 0),
    growthPercent: Number(
      forecastPayload.growthPercent
      || forecastPayload.growth_percent
      || summary.expectedChangePercent
      || summary.expected_change_percent
      || 0
    ),
  };
}

function normalizeReadinessPayload(readinessPayload) {
  return {
    bands: normalizeBands(readinessPayload),
    topRiskFactors: readinessPayload.topRiskFactors || readinessPayload.top_risk_factors || [],
    highRiskEquipment: readinessPayload.highRiskEquipment || readinessPayload.high_risk_equipment || [],
    safeRate: Number(readinessPayload.safeRate || readinessPayload.safe_rate || 0),
  };
}

function normalizeAllocationPayload(allocationPayload, response) {
  return {
    optimizationMetrics: normalizeOptimizationMetrics(allocationPayload),
    utilizationByCategory: allocationPayload.utilizationByCategory || allocationPayload.utilization_by_category || [],
    utilizationComparisonByCategory: allocationPayload.utilizationComparisonByCategory
      || allocationPayload.utilization_comparison_by_category
      || [],
    topEquipment: allocationPayload.topEquipment || allocationPayload.top_equipment || [],
    possibleBorrowedEquipment: allocationPayload.possibleBorrowedEquipment
      || allocationPayload.possible_borrowed_equipment
      || [],
    summary: normalizeAllocationSummary(allocationPayload, response),
  };
}

function normalizeAllocationSummary(allocationPayload, response) {
  return {
    totalEquipment: Number(allocationPayload.summary?.totalEquipment || allocationPayload.summary?.total_equipment || 0),
    activeReservations: Number(allocationPayload.summary?.activeReservations || allocationPayload.summary?.active_reservations || 0),
    pendingRequests: Number(allocationPayload.summary?.pendingRequests || allocationPayload.summary?.pending_requests || 0),
    completedThisPeriod: Number(allocationPayload.summary?.completedThisPeriod || allocationPayload.summary?.completed_this_period || 0),
    generatedAt: response?.completedAt || response?.startedAt || response?.run?.started_at || response?.run?.startedAt || 'N/A',
  };
}

function normalizeSeriesPoint(item) {
  return {
    date: item?.date || item?.label || '',
    label: item?.label || item?.date || '',
    value: Number(item?.value || 0),
  };
}

function normalizeBands(readinessPayload) {
  const sourceBands = readinessPayload.bands || readinessPayload.riskBands || readinessPayload.risk_bands || [];
  if (Array.isArray(sourceBands) && sourceBands.length > 0) {
    return sourceBands;
  }

  const records = Array.isArray(readinessPayload.records) ? readinessPayload.records : [];
  const counts = {
    'High Risk': 0,
    'Medium Risk': 0,
    'Low Risk': 0,
    'Very Low Risk': 0,
  };

  records.forEach((record) => {
    const ratio = Number(record?.availabilityRatio || 0);
    if (ratio >= 0.8) counts['Very Low Risk'] += 1;
    else if (ratio >= 0.5) counts['Low Risk'] += 1;
    else if (ratio >= 0.25) counts['Medium Risk'] += 1;
    else counts['High Risk'] += 1;
  });

  return [
    { label: 'High Risk', count: counts['High Risk'], color: '#ef4444' },
    { label: 'Medium Risk', count: counts['Medium Risk'], color: '#f59e0b' },
    { label: 'Low Risk', count: counts['Low Risk'], color: '#facc15' },
    { label: 'Very Low Risk', count: counts['Very Low Risk'], color: '#16a34a' },
  ];
}

function normalizeOptimizationMetrics(allocationPayload) {
  const sourceMetrics = allocationPayload.optimizationMetrics || allocationPayload.optimization_metrics || [];
  if (Array.isArray(sourceMetrics) && sourceMetrics.length > 0) {
    return sourceMetrics;
  }

  const allocationPlan = allocationPayload.allocationPlan || allocationPayload.allocation_plan || [];
  const hasAllocationData = Array.isArray(allocationPlan)
    || Array.isArray(allocationPayload.utilizationByCategory)
    || Array.isArray(allocationPayload.utilization_by_category)
    || Number(allocationPayload.fulfilledCount || allocationPayload.fulfilled_count || 0) > 0
    || Number(allocationPayload.pendingRequestCount || allocationPayload.pending_request_count || 0) > 0;

  if (!hasAllocationData) {
    return [];
  }

  const currentUtilization = averageMetricValue(
    allocationPayload.utilizationByCategory || allocationPayload.utilization_by_category || [],
  );
  const previousUtilization = averageMetricValue(
    allocationPayload.utilizationComparisonByCategory || allocationPayload.utilization_comparison_by_category || [],
  );
  const fulfilledCount = Number(allocationPayload.fulfilledCount || allocationPayload.fulfilled_count || 0);
  const pendingCount = Number(allocationPayload.pendingRequestCount || allocationPayload.pending_request_count || 0);
  const totalDecisionCount = fulfilledCount + pendingCount;
  const resolvedRate = totalDecisionCount > 0 ? (fulfilledCount / totalDecisionCount) * 100 : 0;

  return [
    {
      label: 'Conflict Reduction',
      note: 'stored scenario',
      value: 0,
      icon: 'CR',
      tone: 'tree',
    },
    {
      label: 'Equipment Utilization',
      note: 'vs. last year same days',
      value: roundMetricValue(currentUtilization - previousUtilization),
      icon: 'EU',
      tone: 'box',
    },
    {
      label: 'Constraint Satisfaction',
      note: 'requests resolved',
      value: roundMetricValue(resolvedRate),
      icon: 'CS',
      tone: 'check',
    },
    {
      label: 'Unassigned Requests',
      note: 'pending stored requests',
      value: pendingCount,
      icon: 'UR',
      tone: 'alert',
    },
  ];
}

function averageMetricValue(items) {
  if (!Array.isArray(items) || items.length === 0) {
    return 0;
  }

  return items.reduce((sum, item) => sum + Number(item?.value || 0), 0) / items.length;
}

function roundMetricValue(value) {
  return Math.round(Number(value || 0) * 10) / 10;
}
