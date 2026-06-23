<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="reports-analytics-page">
      <div ref="reportSurfaceRef" class="reports-export-surface">
        <header class="reports-analytics-header">
          <div>
            <p class="reports-analytics-kicker">Analytics Dashboard</p>
            <h1>Reports &amp; Analytics</h1>
            <p class="reports-analytics-source">{{ reportsSourceLabel }}</p>
          </div>

          <label class="reports-analytics-date-range">
            <span>Date Range:</span>
            <select v-model="selectedRangeKey">
              <option
                v-for="preset in ADMIN_ANALYTICS_RANGE_PRESETS"
                :key="preset.key"
                :value="preset.key"
              >
                {{ preset.label }}
              </option>
            </select>
            <small>{{ activeRangeLabel }}</small>
          </label>

        </header>

        <p v-if="reportsError" class="reports-inline-message is-error">{{ reportsError }}</p>
        <p v-else-if="isReportsLoading" class="reports-inline-message">Loading analytics report...</p>
        <p v-if="analyticsToastMessage" class="reports-inline-message is-success">{{ analyticsToastMessage }}</p>

        <div class="reports-model-grid">
          <article
            v-for="model in modelCards"
            :key="model.title"
            class="reports-model-card"
            :class="`reports-model-card--${model.tone}`"
          >
            <p>{{ model.number }}. {{ model.title }}</p>
            <h2>{{ model.subtitle }}</h2>
            <span>{{ model.description }}</span>
            <svg v-if="model.tone === 'blue'" viewBox="0 0 120 52" aria-hidden="true">
              <polyline points="5,42 18,35 29,38 41,23 53,29 65,18 76,22 88,10 99,35 114,31" />
            </svg>
            <svg v-else-if="model.tone === 'green'" viewBox="0 0 80 70" aria-hidden="true">
              <circle cx="38" cy="13" r="4" /><circle cx="22" cy="31" r="4" /><circle cx="53" cy="31" r="4" /><circle cx="14" cy="52" r="4" /><circle cx="40" cy="55" r="4" /><circle cx="65" cy="52" r="4" />
              <path d="M38 17 22 27M38 17l15 10M22 35l-8 13M53 35l12 13M22 35l18 16M53 35 40 51" />
            </svg>
            <svg v-else viewBox="0 0 82 64" aria-hidden="true">
              <circle cx="13" cy="46" r="4" /><circle cx="35" cy="34" r="4" /><circle cx="60" cy="18" r="4" /><circle cx="68" cy="49" r="4" />
              <path d="M17 44 31 36M39 32l17-11M62 22l5 23M38 36l26 11" />
            </svg>
          </article>
        </div>

        <section class="reports-panel reports-forecast-panel">
          <div class="reports-panel-heading">
            <div>
              <h2>Demand Forecasting (Operational Trend Model)</h2>
              <p>Forecasted equipment demand based on recent reservation volume.</p>
            </div>
          </div>

          <div class="reports-forecast-layout">
            <div class="reports-chart-card">
              <div class="reports-chart-legend">
                <span><i class="legend-dot legend-dot--actual"></i>Actual Demand</span>
                <span><i class="legend-dot legend-dot--forecast"></i>Forecasted Demand</span>
              </div>
              <div v-if="forecastSeries.length === 0" class="reports-inline-message">No reservation demand data is available for this range.</div>
              <svg v-else class="reports-line-chart" :viewBox="`0 0 ${forecastChart.width} ${forecastChart.height}`" role="img" aria-label="Demand forecasting line chart">
                <g class="reports-grid-lines">
                  <path
                    v-for="(line, index) in forecastChart.gridLinesY"
                    :key="`grid-y-${index}`"
                    :d="`M52 ${line.y}H730`"
                  />
                  <path
                    v-for="(line, index) in forecastChart.gridLinesX"
                    :key="`grid-x-${index}`"
                    :d="`M${line.x} 30V230`"
                  />
                </g>
                <g class="reports-axis-labels">
                  <text
                    v-for="(label, index) in forecastChart.yAxisLabels"
                    :key="`label-y-${index}`"
                    :x="label.x"
                    :y="label.y"
                  >
                    {{ label.value }}
                  </text>
                  <text
                    v-for="(label, index) in forecastChart.xAxisLabels"
                    :key="`label-x-${index}`"
                    :x="label.x"
                    :y="label.y"
                  >
                    {{ label.label }}
                  </text>
                </g>
                <polyline class="reports-line reports-line--actual" :points="forecastChart.actualPolylinePoints" />
                <polyline class="reports-line reports-line--forecast" :points="forecastChart.forecastPolylinePoints" />
              </svg>
            </div>

            <aside class="reports-insights-card">
              <h3>Forecast Insights</h3>
              <dl>
                <div>
                  <dt>Forecasted Peak</dt>
                  <dd>{{ peakDateLabel }}<br><strong>{{ formatMetricNumber(forecastData.peakValue, 1) }} requests</strong></dd>
                </div>
                <div>
                  <dt>Expected Growth</dt>
                  <dd><strong :class="{ positive: Number(forecastData.growthPercent || 0) >= 0 }">{{ formatMetricDelta(forecastData.growthPercent, 1) }}</strong><br>from previous period</dd>
                </div>
                <div>
                  <dt>Generated At</dt>
                  <dd><strong>{{ reportGeneratedAt }}</strong></dd>
                </div>
              </dl>
            </aside>
          </div>
        </section>

        <div class="reports-two-column">
          <section class="reports-panel">
            <h2>Readiness Risk Detection (Operational Risk Bands)</h2>
            <p>Risk level distribution across tracked equipment inventory.</p>
            <div class="reports-risk-layout">
              <div class="reports-donut" :style="riskDonutStyle" aria-label="Risk level distribution">
                <span>{{ safeRateLabel }}</span>
              </div>
              <ul class="reports-risk-list">
                <li v-for="risk in riskBands" :key="risk.label">
                  <i :style="{ background: risk.color }"></i>
                  <span>{{ risk.label }}</span>
                  <strong>{{ risk.count }} equipment</strong>
                </li>
              </ul>
              <div class="reports-top-risk-card">
                <h3>Top Risk Factors</h3>
                <ol>
                  <li v-for="factor in topRiskFactors" :key="factor">{{ factor }}</li>
                </ol>
              </div>
            </div>
          </section>

          <section class="reports-panel">
            <h2>Resource Allocation Optimization (Operational Efficiency)</h2>
            <p>Efficiency indicators derived from request throughput and inventory usage.</p>
            <div class="reports-optimization-list">
              <article v-for="metric in optimizationMetrics" :key="metric.label">
                <span :class="`reports-metric-icon reports-metric-icon--${metric.tone}`">{{ metric.icon }}</span>
                <div>
                  <strong>{{ metric.label }}</strong>
                  <small>{{ metric.note }}</small>
                </div>
                <em :class="{ negative: Number(metric.value || 0) < 0 }">{{ formatMetricDelta(metric.value, 1) }}</em>
              </article>
            </div>
          </section>
        </div>

        <div class="reports-bottom-grid">
          <section class="reports-panel">
            <h2>Equipment Utilization Overview</h2>
            <div v-if="utilizationItems.length === 0" class="reports-inline-message">No category utilization data is available yet.</div>
            <div v-else class="reports-bar-chart">
              <div v-for="item in utilizationItems" :key="item.label">
                <span>{{ formatMetricNumber(item.value, 0) }}%</span>
                <i :style="{ height: `${Math.max(12, Number(item.value || 0))}%` }"></i>
                <small>{{ item.label }}</small>
              </div>
            </div>
          </section>

          <section class="reports-panel">
            <h2>Top Frequently Used Equipment</h2>
            <table class="reports-equipment-table">
              <thead>
                <tr><th>Equipment</th><th>Usage Count</th><th>Utilization Rate</th></tr>
              </thead>
              <tbody>
                <tr v-if="topEquipment.length === 0">
                  <td colspan="3">No equipment requests were recorded in the selected range.</td>
                </tr>
                <tr v-for="item in topEquipment" :key="item.name">
                  <td>{{ item.name }}</td>
                  <td>{{ formatMetricNumber(item.count, 0) }}</td>
                  <td>{{ formatMetricNumber(item.rate, 1) }}%</td>
                </tr>
              </tbody>
            </table>
          </section>

          <section class="reports-panel reports-summary-panel">
            <h2>System Summary</h2>
            <dl>
              <div v-for="item in summaryItems" :key="item.label">
                <dt>{{ item.label }}</dt>
                <dd>{{ item.value }}</dd>
              </div>
            </dl>
          </section>
        </div>
      </div>

      <div class="reports-actions">
        <p v-if="pdfError" class="reports-inline-message is-error">{{ pdfError }}</p>
        <p v-if="analyticsToastMessage" class="reports-inline-message is-success">{{ analyticsToastMessage }}</p>
        <button class="reports-generate-button" type="button" :disabled="isExporting || isReportsLoading" @click="handleGeneratePdf">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
            <path d="M14 2v6h6" />
            <path d="M9 15h6" />
            <path d="M9 18h6" />
          </svg>
          {{ isExporting ? 'Generating PDF...' : 'Generate PDF Report' }}
        </button>

        <button class="reports-trigger-button reports-trigger-button--bottom" type="button" @click="isScenarioModalOpen = true">
          Run Analytics Now
        </button>
      </div>

      <div v-if="isScenarioModalOpen" class="reports-modal-backdrop" @click.self="closeScenarioModal">
        <div class="reports-modal">
          <div class="reports-modal-header">
            <h3>Run Analytics Scenario</h3>
            <button type="button" class="reports-modal-close" @click="closeScenarioModal">×</button>
          </div>

          <p class="reports-modal-description">Choose the dataset shape you want to run against.</p>
          <p v-if="analyticsRunStatus" class="reports-modal-status" :class="{ 'is-success': analyticsRunStatusType === 'success', 'is-error': analyticsRunStatusType === 'error' }">
            {{ analyticsRunStatus }}
          </p>

          <div class="reports-scenario-grid">
            <button
              v-for="scenario in analyticsScenarios"
              :key="scenario.key"
              type="button"
              class="reports-scenario-card"
              :class="{ 'is-selected': selectedAnalyticsScenario === scenario.key }"
              :disabled="isTriggeringAnalytics"
              @click="selectedAnalyticsScenario = scenario.key"
            >
              <strong>{{ scenario.title }}</strong>
              <span>{{ scenario.description }}</span>
            </button>
          </div>

          <div class="reports-modal-actions">
            <button type="button" class="reports-secondary-button" @click="closeScenarioModal">Cancel</button>
            <button
              type="button"
              class="reports-primary-button"
              :disabled="isTriggeringAnalytics"
              @click="handleTriggerAnalyticsRun"
            >
              {{ isTriggeringAnalytics ? 'Running...' : 'Run Scenario' }}
            </button>
          </div>
        </div>
      </div>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/ReportsAnalytics.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import adminAnalyticsApi from '@/modules/dashboard/services/adminAnalyticsApi.js';
import {
  ADMIN_ANALYTICS_RANGE_PRESETS,
  buildDualLineChartModel,
  buildRiskDonutStyle,
  formatDateRangeLabel,
  formatMetricDelta,
  formatMetricNumber,
  parseDateOnly,
  resolveAdminAnalyticsDateRange,
} from './adminAnalyticsHelpers.js';

const selectedRangeKey = ref('30d');
const isReportsLoading = ref(true);
const isExporting = ref(false);
const isTriggeringAnalytics = ref(false);
const isScenarioModalOpen = ref(false);
const selectedAnalyticsScenario = ref('clean_data');
const reportsError = ref('');
const analyticsToastMessage = ref('');
const analyticsRunStatus = ref('');
const analyticsRunStatusType = ref('info');
const pdfError = ref('');
const reportSurfaceRef = ref(null);
const reportsAnalytics = ref(createEmptyReport());
const reportsSourceLabel = ref('Loading stored analytics...');

const analyticsScenarios = [
  { key: 'clean_data', title: 'Clean Data', description: 'Reset to a neutral demo state with balanced inputs.' },
  { key: 'high_last_low_this', title: 'High demand last year, low this sem', description: 'Seasonal spike in the prior year, softer current term.' },
  { key: 'high_last_high_this', title: 'High demand last year, high this sem', description: 'Strong demand in both periods for stress testing.' },
  { key: 'low_last_low_this', title: 'Low demand last year, low this sem', description: 'Quiet baseline across both periods.' },
  { key: 'low_last_high_this', title: 'Low demand last year, high this sem', description: 'Recovery pattern with a current-term spike.' },
];

const activeRange = computed(() => resolveAdminAnalyticsDateRange(selectedRangeKey.value));
const activeRangeLabel = computed(() => formatDateRangeLabel(activeRange.value.startDateIso, activeRange.value.endDateIso));

const modelCards = [
  {
    number: 1,
    title: 'Demand Trend Projection',
    subtitle: 'Operational Forecasting',
    description: 'Projects upcoming equipment demand from live reservation activity in the selected period.',
    tone: 'blue',
  },
  {
    number: 2,
    title: 'Readiness Risk Bands',
    subtitle: 'Inventory Monitoring',
    description: 'Highlights equipment pressure using stock levels, overdue linkage, and recent usage frequency.',
    tone: 'green',
  },
  {
    number: 3,
    title: 'Allocation Efficiency',
    subtitle: 'Operational Optimization',
    description: 'Tracks fulfillment, utilization, and pending-request pressure for current admin operations.',
    tone: 'orange',
  },
];

const forecastData = computed(() => reportsAnalytics.value.forecast || {});
const forecastSeries = computed(() => (forecastData.value.actualSeries || []).map((item) => ({
  ...item,
  label: formatShortDate(item.label),
})));
const forecastProjectionSeries = computed(() => (forecastData.value.forecastSeries || []).map((item) => ({
  ...item,
  label: formatShortDate(item.label),
})));
const forecastChart = computed(() => buildDualLineChartModel(forecastSeries.value, forecastProjectionSeries.value, { width: 760, height: 260 }));
const peakDateLabel = computed(() => formatLongDate(forecastData.value.peakDate));
const riskBands = computed(() => reportsAnalytics.value.riskDistribution?.bands || []);
const topRiskFactors = computed(() => reportsAnalytics.value.riskDistribution?.topRiskFactors || []);
const riskDonutStyle = computed(() => buildRiskDonutStyle(riskBands.value));
const safeRateLabel = computed(() => `${formatMetricNumber(reportsAnalytics.value.riskDistribution?.safeRate || 0, 0)}%`);
const optimizationMetrics = computed(() => reportsAnalytics.value.optimizationMetrics || []);
const utilizationItems = computed(() => reportsAnalytics.value.utilizationByCategory || []);
const topEquipment = computed(() => reportsAnalytics.value.topEquipment || []);
const reportGeneratedAt = computed(() => reportsAnalytics.value.summary?.generatedAt || 'N/A');
const summaryItems = computed(() => [
  { label: 'Total Equipment', value: formatMetricNumber(reportsAnalytics.value.summary?.totalEquipment || 0, 0) },
  { label: 'Active Reservations', value: formatMetricNumber(reportsAnalytics.value.summary?.activeReservations || 0, 0) },
  { label: 'Pending Requests', value: formatMetricNumber(reportsAnalytics.value.summary?.pendingRequests || 0, 0) },
  { label: 'Completed This Period', value: formatMetricNumber(reportsAnalytics.value.summary?.completedThisPeriod || 0, 0) },
  { label: 'Generated At', value: reportGeneratedAt.value },
]);

onMounted(() => {
  loadReportsAnalytics();
});

watch(selectedRangeKey, () => {
  loadReportsAnalytics();
});

async function loadReportsAnalytics(options = {}) {
  const preferLiveOnly = options.preferLiveOnly === true;
  isReportsLoading.value = true;
  reportsError.value = '';
  pdfError.value = '';

  try {
    reportsAnalytics.value = createEmptyReport();
    reportsAnalytics.value = await adminAnalyticsApi.getReportsAnalytics(activeRange.value);
    reportsSourceLabel.value = `Using live aggregation for ${activeRangeLabel.value}.`;
  } catch (error) {
    if (preferLiveOnly) {
      reportsAnalytics.value = createEmptyReport();
      reportsSourceLabel.value = 'Analytics data is unavailable right now.';
      return;
    }

    try {
      const latestResultsResponse = await adminAnalyticsApi.getLatestAnalyticsResults();
      const storedAnalytics = normalizeStoredAnalyticsResponse(latestResultsResponse);

      if (storedAnalytics !== null) {
        reportsAnalytics.value = storedAnalytics;
        reportsSourceLabel.value = buildStoredAnalyticsLabel(latestResultsResponse?.run);
        return;
      }

      reportsAnalytics.value = createEmptyReport();
      reportsSourceLabel.value = 'Analytics data is unavailable right now.';
    } catch (fallbackError) {
      reportsAnalytics.value = createEmptyReport();
      reportsError.value = resolveReportsError(fallbackError || error);
      reportsSourceLabel.value = 'Analytics data is unavailable right now.';
    }
  } finally {
    isReportsLoading.value = false;
  }
}

async function handleTriggerAnalyticsRun() {
  if (isTriggeringAnalytics.value) {
    return;
  }

  isTriggeringAnalytics.value = true;
  reportsError.value = '';
  analyticsToastMessage.value = '';
  analyticsRunStatus.value = 'Preparing scenario data...';
  analyticsRunStatusType.value = 'info';

  try {
    analyticsRunStatus.value = 'Running analytics service...';
    await adminAnalyticsApi.triggerAnalyticsRun(selectedAnalyticsScenario.value);
    analyticsRunStatus.value = 'Refreshing dashboard data...';
    await refreshReportsAfterRun();
    analyticsToastMessage.value = 'Analytics run completed successfully.';
    analyticsRunStatus.value = 'Analytics run completed successfully.';
    analyticsRunStatusType.value = 'success';
    window.setTimeout(() => {
      if (analyticsToastMessage.value === 'Analytics run completed successfully.') {
        analyticsToastMessage.value = '';
      }
      analyticsRunStatus.value = '';
      isScenarioModalOpen.value = false;
    }, 3500);
  } catch (error) {
    reportsError.value = resolveReportsError(error);
    analyticsRunStatus.value = resolveReportsError(error);
    analyticsRunStatusType.value = 'error';
  } finally {
    isTriggeringAnalytics.value = false;
  }
}

async function refreshReportsAfterRun() {
  const maxAttempts = 4;
  let attempt = 0;
  let latestReport = null;

  while (attempt < maxAttempts) {
    latestReport = await adminAnalyticsApi.getReportsAnalytics(activeRange.value);
    reportsAnalytics.value = latestReport;

    if ((latestReport?.forecast?.actualSeries || []).length > 0 || attempt === maxAttempts - 1) {
      reportsSourceLabel.value = `Using live aggregation for ${activeRangeLabel.value}.`;
      return;
    }

    attempt += 1;
    await wait(600);
  }

  reportsAnalytics.value = createEmptyReport();
  reportsSourceLabel.value = 'Analytics data is unavailable right now.';
}

function wait(milliseconds) {
  return new Promise((resolve) => {
    window.setTimeout(resolve, milliseconds);
  });
}

function closeScenarioModal() {
  if (isTriggeringAnalytics.value) {
    return;
  }

  analyticsRunStatus.value = '';
  isScenarioModalOpen.value = false;
}

async function handleGeneratePdf() {
  if (!reportSurfaceRef.value || isExporting.value) {
    return;
  }

  isExporting.value = true;
  pdfError.value = '';

  try {
    const [{ default: html2canvas }, { default: jsPDF }] = await Promise.all([
      import('html2canvas'),
      import('jspdf'),
    ]);
    const canvas = await html2canvas(reportSurfaceRef.value, {
      backgroundColor: '#f5faf7',
      scale: 2,
      useCORS: true,
      logging: false,
      windowWidth: reportSurfaceRef.value.scrollWidth,
    });

    const imageData = canvas.toDataURL('image/png');
    const pdf = new jsPDF('p', 'mm', 'a4');
    const pageWidth = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();
    const imageWidth = pageWidth;
    const imageHeight = (canvas.height * imageWidth) / canvas.width;
    let remainingHeight = imageHeight;
    let position = 0;

    pdf.addImage(imageData, 'PNG', 0, position, imageWidth, imageHeight);
    remainingHeight -= pageHeight;

    while (remainingHeight > 0) {
      position = remainingHeight - imageHeight;
      pdf.addPage();
      pdf.addImage(imageData, 'PNG', 0, position, imageWidth, imageHeight);
      remainingHeight -= pageHeight;
    }

    pdf.save(`techreserve-analytics-${activeRange.value.startDateIso}-to-${activeRange.value.endDateIso}.pdf`);
  } catch (error) {
    pdfError.value = error?.message || 'Unable to generate the PDF report right now.';
  } finally {
    isExporting.value = false;
  }
}

function createEmptyReport() {
  return {
    forecast: {
      actualSeries: [],
      forecastSeries: [],
      peakDate: null,
      peakValue: 0,
      growthPercent: 0,
    },
    riskDistribution: {
      bands: [],
      topRiskFactors: [],
      safeRate: 0,
    },
    optimizationMetrics: [],
    utilizationByCategory: [],
    topEquipment: [],
    summary: {
      totalEquipment: 0,
      activeReservations: 0,
      pendingRequests: 0,
      completedThisPeriod: 0,
      generatedAt: 'N/A',
    },
  };
}

function resolveReportsError(error) {
  return error?.response?.data?.errorMessage
    || error?.message
    || 'Unable to load analytics data right now.';
}

function normalizeStoredAnalyticsResponse(response) {
  const resultList = Array.isArray(response?.results) ? response.results : [];
  if (resultList.length === 0) {
    return null;
  }

  const payloadByType = Object.fromEntries(
    resultList.map((result) => [
      String(result?.result_type || result?.resultType || '').toLowerCase(),
      result?.result_payload || result?.resultPayload || {},
    ]),
  );

  const normalized = createEmptyReport();
  const forecastPayload = payloadByType.sarima || payloadByType.forecast || {};
  const readinessPayload = payloadByType.random_forest || payloadByType.readiness || {};
  const allocationPayload = payloadByType.binary_linear_programming || payloadByType.allocation || {};

  normalized.forecast = {
    actualSeries: (forecastPayload.actualSeries || forecastPayload.actual_series || []).map(normalizeSeriesPoint),
    forecastSeries: (forecastPayload.forecastSeries || forecastPayload.forecast_series || []).map(normalizeSeriesPoint),
    peakDate: forecastPayload.peakDate || forecastPayload.peak_date || null,
    peakValue: Number(forecastPayload.peakValue || forecastPayload.peak_value || 0),
    growthPercent: Number(forecastPayload.growthPercent || forecastPayload.growth_percent || 0),
  };

  normalized.riskDistribution = {
    bands: normalizeBands(readinessPayload),
    topRiskFactors: readinessPayload.topRiskFactors || readinessPayload.top_risk_factors || [],
    safeRate: Number(readinessPayload.safeRate || readinessPayload.safe_rate || 0),
  };

  normalized.optimizationMetrics = normalizeOptimizationMetrics(allocationPayload);
  normalized.utilizationByCategory = allocationPayload.utilizationByCategory || allocationPayload.utilization_by_category || [];
  normalized.topEquipment = allocationPayload.topEquipment || allocationPayload.top_equipment || [];
  normalized.summary = {
    totalEquipment: Number(allocationPayload.summary?.totalEquipment || allocationPayload.summary?.total_equipment || 0),
    activeReservations: Number(allocationPayload.summary?.activeReservations || allocationPayload.summary?.active_reservations || 0),
    pendingRequests: Number(allocationPayload.summary?.pendingRequests || allocationPayload.summary?.pending_requests || 0),
    completedThisPeriod: Number(allocationPayload.summary?.completedThisPeriod || allocationPayload.summary?.completed_this_period || 0),
    generatedAt: response?.run?.started_at || response?.run?.startedAt || 'N/A',
  };

  return normalized;
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
  return [
    {
      label: 'Allocation Plan Items',
      note: 'stored results',
      value: Array.isArray(allocationPlan) ? allocationPlan.length : 0,
      icon: 'AP',
      tone: 'tree',
    },
  ];
}

function buildStoredAnalyticsLabel(run) {
  if (!run) {
    return 'Using stored analytics results.';
  }

  const startedAt = run.started_at || run.startedAt || 'unknown time';
  const runType = run.run_type || run.runType || 'analytics';
  const status = run.status || 'unknown';
  return `Using latest stored ${runType} run (${status}) from ${startedAt}.`;
}

function formatShortDate(value) {
  if (!value) return '';
  const date = parseDateOnly(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric' }).format(date);
}

function formatLongDate(value) {
  if (!value) return 'No forecast peak yet';
  const date = parseDateOnly(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(date);
}
</script>
