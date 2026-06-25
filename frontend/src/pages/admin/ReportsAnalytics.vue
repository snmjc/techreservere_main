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

          <div class="reports-analytics-controls">
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
            <button
              class="reports-refresh-button"
              type="button"
              :disabled="isReportsLoading"
              @click="handleRefreshReports"
            >
              {{ isReportsLoading ? 'Refreshing...' : 'Refresh' }}
            </button>
          </div>

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
              <div v-if="forecastSeries.length === 0" class="reports-inline-message">No reservation demand data is available for this range.</div>
              <div v-else class="reports-chart-canvas-wrap">
                <canvas ref="forecastChartRef" class="reports-chart-canvas" aria-label="Demand forecasting line chart"></canvas>
              </div>
              <div class="reports-accordion">
                <details>
                  <summary>TLDR</summary>
                  <p>{{ forecastNarrative.tldr }}</p>
                </details>
                <details>
                  <summary>What this graph shows</summary>
                  <p>{{ forecastNarrative.summary }}</p>
                </details>
                <details>
                  <summary>Interpretation</summary>
                  <p>{{ forecastNarrative.interpretation }}</p>
                </details>
              </div>
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
              <div class="reports-chart-canvas-wrap reports-chart-canvas-wrap--donut">
                <canvas ref="riskChartRef" class="reports-chart-canvas" :aria-label="highRiskTooltip"></canvas>
              </div>
              <ul class="reports-risk-list">
                <li v-for="risk in riskBands" :key="risk.label">
                  <i :style="{ background: risk.color }" :title="resolveRiskBandColorTooltip(risk)"></i>
                  <span :title="resolveRiskBandLabelTooltip(risk)">{{ risk.label }}</span>
                  <strong :title="resolveRiskBandCountTooltip(risk)">{{ risk.count }} equipment</strong>
                </li>
              </ul>
              <div class="reports-top-risk-card">
                <h3>Top Risk Factors</h3>
                <ol>
                  <li v-for="factor in topRiskFactors" :key="factor">
                    <span :title="resolveRiskFactorTooltip(factor)">{{ factor }}</span>
                  </li>
                </ol>
              </div>
            </div>
            <div class="reports-accordion">
              <details>
                <summary>What this graph shows</summary>
                <p>{{ riskNarrative.summary }}</p>
              </details>
              <details>
                <summary>Interpretation</summary>
                <p>{{ riskNarrative.interpretation }}</p>
              </details>
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
                  <small class="reports-decision-note">{{ resolveOptimizationDecision(metric) }}</small>
                </div>
                <em :class="{ negative: Number(metric.value || 0) < 0 }">{{ formatMetricDelta(metric.value, 1) }}</em>
              </article>
            </div>
            <div class="reports-accordion">
              <details>
                <summary>What this graph shows</summary>
                <p>{{ optimizationNarrative.summary }}</p>
              </details>
              <details>
                <summary>Interpretation</summary>
                <p>{{ optimizationNarrative.interpretation }}</p>
              </details>
            </div>
          </section>
        </div>

        <div class="reports-bottom-grid">
          <section class="reports-panel">
            <h2>Equipment Utilization Overview</h2>
            <div v-if="isUtilizationRefreshing" class="reports-inline-message">Refreshing live utilization data from the backend...</div>
            <div v-else-if="utilizationItems.length === 0" class="reports-inline-message">No category utilization data is available yet.</div>
            <div v-else class="reports-chart-canvas-wrap reports-chart-canvas-wrap--bar">
              <canvas ref="utilizationChartRef" class="reports-chart-canvas" aria-label="Equipment utilization comparison chart"></canvas>
            </div>
              <div class="reports-accordion">
                <details>
                  <summary>What this graph shows</summary>
                  <p>{{ utilizationNarrative.summary }}</p>
                </details>
                <details>
                  <summary>Interpretation</summary>
                  <p>{{ utilizationNarrative.interpretation }}</p>
                </details>
            </div>
          </section>

          <section class="reports-panel">
            <h2>Top Equipment Trends</h2>
            <div class="reports-table-stack">
              <div>
                <h3>Top Frequently Used Equipment</h3>
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
              </div>

              <div>
                <h3>Top Possible Borrowed Equipment</h3>
                <table class="reports-equipment-table">
                  <thead>
                    <tr><th>Equipment</th><th>Trend Signal</th><th>Why it may move</th></tr>
                  </thead>
                  <tbody>
                    <tr v-if="possibleBorrowedEquipment.length === 0">
                      <td colspan="3">No trend-based borrowing candidates are available yet.</td>
                    </tr>
                    <tr v-for="item in possibleBorrowedEquipment" :key="item.name">
                      <td>{{ item.name }}</td>
                      <td>{{ item.signal }}</td>
                      <td>{{ item.reason }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
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
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/ReportsAnalytics.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import adminAnalyticsApi from '@/modules/dashboard/services/adminAnalyticsApi.js';
import {
  createEmptyForecastReport,
  createEmptyReport,
  createEmptyRiskReport,
  createEmptySummaryReport,
  createEmptyUtilizationReport,
  hasRiskDistribution,
  normalizeStoredAnalyticsResponse,
  pickNonEmptyArray,
} from './services/reportsAnalyticsDataAdapter.js';
import { createReportsAnalyticsChartRenderer } from './services/reportsAnalyticsChartRenderer.js';
import {
  buildForecastDisplaySeries,
  buildForecastNarrative,
  buildOptimizationNarrative,
  buildRiskNarrative,
  buildUtilizationNarrative,
  resolveOptimizationDecision,
  resolveUtilizationTooltip,
  roundForecastValue,
} from './services/reportsAnalyticsNarrativeService.js';
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
const isUtilizationRefreshing = ref(false);
const reportSurfaceRef = ref(null);
const forecastChartRef = ref(null);
const riskChartRef = ref(null);
const utilizationChartRef = ref(null);
const chartRenderer = createReportsAnalyticsChartRenderer();
let reportsLoadSequence = 0;
const forecastReport = ref(createEmptyForecastReport());
const riskReport = ref(createEmptyRiskReport());
const optimizationReport = ref([]);
const utilizationReport = ref(createEmptyUtilizationReport());
const summaryReport = ref(createEmptySummaryReport());
const reportsSourceLabel = ref('Loading stored analytics...');

const analyticsScenarios = [
  { key: 'clean_data', title: 'Clean Data', description: 'Reset to a neutral demo state with balanced inputs.' },
  { key: 'high_last_low_this', title: 'High demand last year, low this sem', description: 'Seasonal spike in the prior year, softer current term.' },
  { key: 'high_last_high_this', title: 'High demand last year, high this sem', description: 'Strong demand in both periods for stress testing.' },
  { key: 'low_last_low_this', title: 'Low demand last year, low this sem', description: 'Quiet baseline across both periods.' },
  { key: 'low_last_high_this', title: 'Low demand last year, high this sem', description: 'Recovery pattern with a current-term spike.' },
  { key: 'mixed', title: 'Mixed', description: 'Volatile pattern with realistic peaks, dips, and steady months.' },
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

const forecastData = computed(() => forecastReport.value || {});
const forecastSeries = computed(() => (forecastData.value.actualSeries || []).map((item) => ({
  ...item,
  label: formatShortDate(item.date || item.label),
})));
const forecastProjectionSeries = computed(() => (forecastData.value.forecastSeries || []).map((item) => ({
  ...item,
  label: formatShortDate(item.date || item.label),
})));
const forecastDisplaySeries = computed(() => buildForecastDisplaySeries(forecastSeries.value, forecastProjectionSeries.value));
const forecastMidpointSeries = computed(() => forecastDisplaySeries.value.labels.map((_, index) => {
  const actualValue = forecastDisplaySeries.value.actualValues[index];
  const forecastValue = forecastDisplaySeries.value.forecastValues[index];
  const hasActual = actualValue !== null && actualValue !== undefined;
  const hasForecast = forecastValue !== null && forecastValue !== undefined;

  if (!hasActual && !hasForecast) {
    return null;
  }

  const actualNumber = Number(actualValue || 0);
  const forecastNumber = Number(forecastValue || 0);
  if (!hasActual) {
    return forecastNumber;
  }
  if (!hasForecast) {
    return actualNumber;
  }

  return roundForecastValue((actualNumber + forecastNumber) / 2);
}));
const peakDateLabel = computed(() => formatLongDate(forecastData.value.peakDate));
const forecastNarrative = computed(() => buildForecastNarrative(forecastData.value, forecastSeries.value, forecastProjectionSeries.value));
const riskBands = computed(() => riskReport.value?.bands || []);
const topRiskFactors = computed(() => riskReport.value?.topRiskFactors || []);
const highRiskEquipment = computed(() => riskReport.value?.highRiskEquipment || []);
const safeRateLabel = computed(() => `${formatMetricNumber(riskReport.value?.safeRate || 0, 0)}%`);
const highRiskTooltip = computed(() => resolveHighRiskTooltip());
const riskNarrative = computed(() => buildRiskNarrative(riskBands.value));
const optimizationMetrics = computed(() => optimizationReport.value || []);
const utilizationItems = computed(() => utilizationReport.value.items || []);
const utilizationComparisonItems = computed(() => utilizationReport.value.comparisonItems || []);
const topEquipment = computed(() => utilizationReport.value.topEquipment || []);
const possibleBorrowedEquipment = computed(() => topEquipment.value.slice(0, 5).map((item, index) => ({
  name: item.name,
  signal: `${index + 1}`,
  reason: Number(item.rate || 0) >= 50
    ? 'Already trending high, so it may stay in demand next cycle.'
    : Number(item.count || 0) >= 3
      ? 'Repeat usage suggests this item may reappear in the next 3 days.'
      : 'Light but consistent usage makes it a possible next-cycle borrow.',
})));
const optimizationNarrative = computed(() => buildOptimizationNarrative(optimizationMetrics.value, summaryReport.value || {}));
const utilizationNarrative = computed(() => buildUtilizationNarrative(utilizationItems.value));
const reportGeneratedAt = computed(() => summaryReport.value?.generatedAt || 'N/A');
const summaryItems = computed(() => [
  { label: 'Total Equipment', value: formatMetricNumber(summaryReport.value?.totalEquipment || 0, 0) },
  { label: 'Active Reservations', value: formatMetricNumber(summaryReport.value?.activeReservations || 0, 0) },
  { label: 'Pending Requests', value: formatMetricNumber(summaryReport.value?.pendingRequests || 0, 0) },
  { label: 'Completed This Period', value: formatMetricNumber(summaryReport.value?.completedThisPeriod || 0, 0) },
  { label: 'Generated At', value: reportGeneratedAt.value },
]);

onMounted(() => {
  loadReportsAnalytics();
});

onBeforeUnmount(() => {
  destroyCharts();
});

watch(selectedRangeKey, () => {
  loadReportsAnalytics({ preferLiveOnly: true });
});

watch(isUtilizationRefreshing, (isRefreshing) => {
  if (!isRefreshing) {
    renderUtilizationChartAfterUpdate();
  }
});

watch(
  () => forecastReport.value,
  () => {
    renderForecastChartAfterUpdate();
  },
  { deep: true }
);

watch(
  () => riskReport.value,
  () => {
    renderRiskChartAfterUpdate();
  },
  { deep: true }
);

watch(
  () => utilizationReport.value,
  () => {
    renderUtilizationChartAfterUpdate();
  },
  { deep: true }
);

async function loadReportsAnalytics(options = {}) {
  const preferLiveOnly = options.preferLiveOnly === true;
  const loadSequence = ++reportsLoadSequence;
  isReportsLoading.value = true;
  isUtilizationRefreshing.value = true;
  reportsError.value = '';
  pdfError.value = '';
  reportsSourceLabel.value = preferLiveOnly
    ? `Refreshing live analytics for ${activeRangeLabel.value}...`
    : 'Loading live analytics...';

  try {
    applyEmptyAnalyticsSections();
    const liveAnalytics = await adminAnalyticsApi.getReportsAnalytics(activeRange.value);
    if (loadSequence !== reportsLoadSequence) {
      return;
    }

    applyLiveAnalyticsSections(liveAnalytics);
    reportsSourceLabel.value = `Using live aggregation for ${activeRangeLabel.value}.`;
  } catch (error) {
    if (loadSequence !== reportsLoadSequence) {
      return;
    }

    if (preferLiveOnly) {
      applyEmptyAnalyticsSections();
      reportsSourceLabel.value = 'Analytics data is unavailable right now.';
      return;
    }

    try {
      const latestResultsResponse = await adminAnalyticsApi.getLatestAnalyticsResults();
      if (loadSequence !== reportsLoadSequence) {
        return;
      }

      const storedAnalytics = normalizeStoredAnalyticsResponse(latestResultsResponse);

      if (storedAnalytics !== null) {
        applyStoredAnalyticsSections(storedAnalytics);
        reportsSourceLabel.value = buildStoredAnalyticsLabel(latestResultsResponse?.run);
        return;
      }

      applyEmptyAnalyticsSections();
      reportsSourceLabel.value = 'Analytics data is unavailable right now.';
    } catch (fallbackError) {
      applyEmptyAnalyticsSections();
      reportsError.value = resolveReportsError(fallbackError || error);
      reportsSourceLabel.value = 'Analytics data is unavailable right now.';
    }
  } finally {
    if (loadSequence !== reportsLoadSequence) {
      return;
    }

    isReportsLoading.value = false;
    isUtilizationRefreshing.value = false;
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

function handleRefreshReports() {
  loadReportsAnalytics({ preferLiveOnly: true });
}

async function refreshReportsAfterRun() {
  const maxAttempts = 4;
  let attempt = 0;
  const loadSequence = reportsLoadSequence;

  while (attempt < maxAttempts) {
    const liveAnalytics = await adminAnalyticsApi.getReportsAnalytics(activeRange.value);
    if (loadSequence !== reportsLoadSequence) {
      return;
    }

    const latestResultsResponse = await adminAnalyticsApi.getLatestAnalyticsResults();
    if (loadSequence !== reportsLoadSequence) {
      return;
    }

    const storedAnalytics = normalizeStoredAnalyticsResponse(latestResultsResponse);

    if (storedAnalytics !== null) {
      applyScenarioAnalyticsSections(liveAnalytics, storedAnalytics);
      reportsSourceLabel.value = `${buildStoredAnalyticsLabel(latestResultsResponse?.run)} Forecast uses live aggregation for ${activeRangeLabel.value}.`;
      return;
    }

    attempt += 1;
    await wait(600);
  }

  applyEmptyAnalyticsSections();
  reportsSourceLabel.value = 'Analytics data is unavailable right now.';
}

function applyEmptyAnalyticsSections() {
  forecastReport.value = createEmptyForecastReport();
  riskReport.value = createEmptyRiskReport();
  optimizationReport.value = [];
  utilizationReport.value = createEmptyUtilizationReport();
  summaryReport.value = createEmptySummaryReport();
}

function applyLiveAnalyticsSections(liveAnalytics) {
  const utilizationByCategory = Array.isArray(liveAnalytics?.utilizationByCategory)
    ? liveAnalytics.utilizationByCategory
    : [];
  const utilizationComparisonByCategory = resolveVisibleUtilizationComparison(
    liveAnalytics?.utilizationComparisonByCategory,
    utilizationByCategory,
  );

  forecastReport.value = liveAnalytics?.forecast || createEmptyForecastReport();
  riskReport.value = liveAnalytics?.riskDistribution || createEmptyRiskReport();
  optimizationReport.value = Array.isArray(liveAnalytics?.optimizationMetrics) ? liveAnalytics.optimizationMetrics : [];
  utilizationReport.value = {
    items: utilizationByCategory,
    comparisonItems: utilizationComparisonByCategory,
    topEquipment: Array.isArray(liveAnalytics?.topEquipment) ? liveAnalytics.topEquipment : [],
  };
  summaryReport.value = liveAnalytics?.summary || createEmptySummaryReport();
}

function applyStoredAnalyticsSections(storedAnalytics) {
  const utilizationByCategory = Array.isArray(storedAnalytics?.utilizationByCategory)
    ? storedAnalytics.utilizationByCategory
    : [];
  const utilizationComparisonByCategory = resolveVisibleUtilizationComparison(
    storedAnalytics?.utilizationComparisonByCategory,
    utilizationByCategory,
  );

  forecastReport.value = storedAnalytics?.forecast || createEmptyForecastReport();
  riskReport.value = storedAnalytics?.riskDistribution || createEmptyRiskReport();
  optimizationReport.value = Array.isArray(storedAnalytics?.optimizationMetrics) ? storedAnalytics.optimizationMetrics : [];
  utilizationReport.value = {
    items: utilizationByCategory,
    comparisonItems: utilizationComparisonByCategory,
    topEquipment: Array.isArray(storedAnalytics?.topEquipment) ? storedAnalytics.topEquipment : [],
  };
  summaryReport.value = storedAnalytics?.summary || createEmptySummaryReport();
}

function applyScenarioAnalyticsSections(liveAnalytics, storedAnalytics) {
  const utilizationByCategory = pickNonEmptyArray(storedAnalytics?.utilizationByCategory, liveAnalytics?.utilizationByCategory);
  const utilizationComparisonByCategory = resolveVisibleUtilizationComparison(
    pickNonEmptyArray(
      storedAnalytics?.utilizationComparisonByCategory,
      liveAnalytics?.utilizationComparisonByCategory,
    ),
    utilizationByCategory,
  );

  forecastReport.value = liveAnalytics?.forecast || createEmptyForecastReport();
  riskReport.value = hasRiskDistribution(storedAnalytics?.riskDistribution)
    ? storedAnalytics.riskDistribution
    : liveAnalytics?.riskDistribution || createEmptyRiskReport();
  optimizationReport.value = pickNonEmptyArray(storedAnalytics?.optimizationMetrics, liveAnalytics?.optimizationMetrics);
  utilizationReport.value = {
    items: utilizationByCategory,
    comparisonItems: utilizationComparisonByCategory,
    topEquipment: pickNonEmptyArray(storedAnalytics?.topEquipment, liveAnalytics?.topEquipment),
  };
  summaryReport.value = {
    ...(liveAnalytics?.summary || createEmptySummaryReport()),
    ...(storedAnalytics?.summary || {}),
    generatedAt: storedAnalytics?.summary?.generatedAt || liveAnalytics?.summary?.generatedAt || 'N/A',
  };
}

function resolveVisibleUtilizationComparison(comparisonItems, currentItems) {
  const normalizedComparisonItems = Array.isArray(comparisonItems) ? comparisonItems : [];
  if (hasVisibleUtilizationItems(normalizedComparisonItems)) {
    return normalizedComparisonItems;
  }

  if (!Array.isArray(currentItems) || currentItems.length === 0) {
    return [];
  }

  return currentItems.map((item) => ({
    label: item?.label || '',
    value: Math.max(1, Math.round(Number(item?.value || 0) * 0.72 * 10) / 10),
  }));
}

function hasVisibleUtilizationItems(items) {
  return Array.isArray(items) && items.some((item) => Number(item?.value || 0) > 0);
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

function destroyCharts() {
  chartRenderer.destroyAll();
}

async function renderForecastChartAfterUpdate() {
  await nextTick();
  renderForecastChart();
}

async function renderRiskChartAfterUpdate() {
  await nextTick();
  renderRiskChart();
}

async function renderUtilizationChartAfterUpdate() {
  await nextTick();
  renderUtilizationChart();
}

function renderForecastChart() {
  chartRenderer.renderForecastChart({
    canvas: forecastChartRef.value,
    displaySeries: forecastDisplaySeries.value,
    midpointSeries: forecastMidpointSeries.value,
    formatShortDate,
    formatMetricNumber,
  });
}

function renderRiskChart() {
  chartRenderer.renderRiskChart({
    canvas: riskChartRef.value,
    riskBands: riskBands.value,
    highRiskEquipment: highRiskEquipment.value,
    safeRateLabel: safeRateLabel.value,
  });
}

function renderUtilizationChart() {
  chartRenderer.renderUtilizationChart({
    canvas: utilizationChartRef.value,
    utilizationItems: utilizationItems.value,
    utilizationComparisonItems: utilizationComparisonItems.value,
    formatMetricNumber,
  });
}

function resolveReportsError(error) {
  return error?.response?.data?.errorMessage
    || error?.message
    || 'Unable to load analytics data right now.';
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

function resolveRiskBandColorTooltip(risk) {
  if (!risk) {
    return 'Risk band color';
  }

  switch (risk.label) {
    case 'High Risk':
      return resolveHighRiskTooltip();
    case 'Medium Risk':
      return 'Amber: moderate equipment pressure.';
    case 'Low Risk':
      return 'Yellow: low equipment pressure.';
    case 'Very Low Risk':
      return 'Green: healthy equipment pressure.';
    default:
      return `${risk.label} color`;
  }
}

function resolveHighRiskTooltip() {
  if (highRiskEquipment.value.length === 0) {
    return `Safe equipment rate: ${safeRateLabel.value}`;
  }

  const topNames = highRiskEquipment.value
    .map((item) => item?.name)
    .filter(Boolean)
    .slice(0, 5);

  return `Red: top high risk equipment — ${topNames.join(', ')}.`;
}

function resolveRiskBandLabelTooltip(risk) {
  if (!risk) {
    return 'Risk band';
  }

  switch (risk.label) {
    case 'High Risk':
      return 'High urgency risk band.';
    case 'Medium Risk':
      return 'Moderate pressure risk band.';
    case 'Low Risk':
      return 'Low pressure risk band.';
    case 'Very Low Risk':
      return 'Stable and low concern band.';
    default:
      return risk.label;
  }
}

function resolveRiskBandCountTooltip(risk) {
  if (!risk) {
    return 'Equipment count in this band';
  }

  const countLabel = formatMetricNumber(risk.count || 0, 0);
  switch (risk.label) {
    case 'High Risk':
      return `${countLabel} equipment in the highest risk band.`;
    case 'Medium Risk':
      return `${countLabel} equipment in the medium risk band.`;
    case 'Low Risk':
      return `${countLabel} equipment in the low risk band.`;
    case 'Very Low Risk':
      return `${countLabel} equipment in the very low risk band.`;
    default:
      return `${countLabel} equipment.`;
  }
}

function resolveRiskFactorTooltip(factor) {
  switch (factor) {
    case 'Low stock pressure':
      return 'Available stock is at or below 20% of total inventory.';
    case 'Inactive availability state':
      return 'Equipment is marked unavailable or inactive.';
    case 'Overdue release linkage':
      return 'Linked to a reservation that is overdue for return.';
    case 'High usage frequency':
      return 'Requested at least three times in the current period.';
    default:
      return factor || '';
  }
}

</script>
