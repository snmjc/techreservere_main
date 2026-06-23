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
            <div v-if="utilizationItems.length === 0" class="reports-inline-message">No category utilization data is available yet.</div>
            <div v-else class="reports-bar-chart">
              <div v-for="item in utilizationItems" :key="item.label" :title="resolveUtilizationTooltip(item)">
                <span>{{ formatMetricNumber(item.value, 0) }}%</span>
                <i :style="{ height: `${Math.max(12, Number(item.value || 0))}%` }" :title="resolveUtilizationTooltip(item)"></i>
                <small :title="resolveUtilizationTooltip(item)">{{ item.label }}</small>
              </div>
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
import { Chart, registerables } from 'chart.js';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
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

Chart.register(...registerables);

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
const forecastChartRef = ref(null);
const riskChartRef = ref(null);
const reportsAnalytics = ref(createEmptyReport());
const reportsSourceLabel = ref('Loading stored analytics...');
let forecastChartInstance = null;
let riskChartInstance = null;

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
const riskBands = computed(() => reportsAnalytics.value.riskDistribution?.bands || []);
const topRiskFactors = computed(() => reportsAnalytics.value.riskDistribution?.topRiskFactors || []);
const highRiskEquipment = computed(() => reportsAnalytics.value.riskDistribution?.highRiskEquipment || []);
const safeRateLabel = computed(() => `${formatMetricNumber(reportsAnalytics.value.riskDistribution?.safeRate || 0, 0)}%`);
const highRiskTooltip = computed(() => resolveHighRiskTooltip());
const riskNarrative = computed(() => buildRiskNarrative(riskBands.value));
const optimizationMetrics = computed(() => reportsAnalytics.value.optimizationMetrics || []);
const utilizationItems = computed(() => reportsAnalytics.value.utilizationByCategory || []);
const topEquipment = computed(() => reportsAnalytics.value.topEquipment || []);
const optimizationNarrative = computed(() => buildOptimizationNarrative(optimizationMetrics.value, reportsAnalytics.value.summary || {}));
const utilizationNarrative = computed(() => buildUtilizationNarrative(utilizationItems.value));
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

onBeforeUnmount(() => {
  destroyCharts();
});

watch(selectedRangeKey, () => {
  loadReportsAnalytics();
});

watch(
  () => reportsAnalytics.value,
  () => {
    renderCharts();
  },
  { deep: true }
);

async function loadReportsAnalytics(options = {}) {
  const preferLiveOnly = options.preferLiveOnly === true;
  isReportsLoading.value = true;
  reportsError.value = '';
  pdfError.value = '';

  try {
    reportsAnalytics.value = createEmptyReport();
    reportsAnalytics.value = await adminAnalyticsApi.getReportsAnalytics(activeRange.value);
    reportsSourceLabel.value = `Using live aggregation for ${activeRangeLabel.value}.`;
    await renderCharts();
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
    await renderCharts();
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

function destroyCharts() {
  if (forecastChartInstance) {
    forecastChartInstance.destroy();
    forecastChartInstance = null;
  }

  if (riskChartInstance) {
    riskChartInstance.destroy();
    riskChartInstance = null;
  }
}

async function renderCharts() {
  await nextTick();
  renderForecastChart();
  renderRiskChart();
}

function renderForecastChart() {
  const canvas = forecastChartRef.value;
  if (!canvas || forecastSeries.value.length === 0) {
    if (forecastChartInstance) {
      forecastChartInstance.destroy();
      forecastChartInstance = null;
    }
    return;
  }

  if (forecastChartInstance) {
    forecastChartInstance.destroy();
  }

  forecastChartInstance = new Chart(canvas, {
    type: 'line',
    data: {
      labels: forecastDisplaySeries.value.labels.map((label) => formatShortDate(label)),
      datasets: [
        {
          label: 'Actual Demand',
          data: forecastDisplaySeries.value.actualValues,
          borderColor: '#1d4ed8',
          backgroundColor: 'rgba(29, 78, 216, 0.12)',
          tension: 0.35,
          pointRadius: 2,
          pointHoverRadius: 4,
        },
        {
          label: 'Forecasted Demand',
          data: forecastDisplaySeries.value.forecastValues,
          borderColor: '#60a5fa',
          borderDash: [8, 6],
          tension: 0.35,
          pointRadius: 2,
          pointHoverRadius: 4,
        },
        {
          label: 'Midpoint Trend',
          data: forecastMidpointSeries.value,
          borderColor: '#10b981',
          borderDash: [2, 4],
          tension: 0.35,
          pointRadius: 1,
          pointHoverRadius: 3,
          borderWidth: 2,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: true, labels: { usePointStyle: true } },
        tooltip: {
          mode: 'index',
          intersect: false,
          callbacks: {
            label(context) {
              const label = context.dataset?.label || '';
              const value = Number(context.raw || 0);
              return `${label}: ${formatMetricNumber(value, 1)} requests`;
            },
          },
        },
      },
      scales: {
        x: { grid: { color: '#e5e7eb' } },
        y: { beginAtZero: true, grid: { color: '#e5e7eb' } },
      },
    },
  });
}

function renderRiskChart() {
  const canvas = riskChartRef.value;
  if (!canvas || riskBands.value.length === 0) {
    if (riskChartInstance) {
      riskChartInstance.destroy();
      riskChartInstance = null;
    }
    return;
  }

  if (riskChartInstance) {
    riskChartInstance.destroy();
  }

  riskChartInstance = new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels: riskBands.value.map((item) => item.label),
      datasets: [
        {
          data: riskBands.value.map((item) => Number(item.count || 0)),
          backgroundColor: riskBands.value.map((item) => item.color),
          borderWidth: 0,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '65%',
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label(context) {
              const label = context.label || '';
              const value = Number(context.raw || 0);
              if (label === 'High Risk') {
                const names = highRiskEquipment.value.map((item) => item?.name).filter(Boolean);
                return [`${label}: ${value} equipment`, ...names.slice(0, 5).map((name) => `• ${name}`)];
              }
              return `${label}: ${value} equipment`;
            },
          },
        },
      },
    },
    plugins: [
      {
        id: 'centerText',
        afterDraw(chart) {
          const { ctx, chartArea } = chart;
          if (!chartArea) return;
          ctx.save();
          ctx.fillStyle = '#15803d';
          ctx.font = '700 18px sans-serif';
          ctx.textAlign = 'center';
          ctx.textBaseline = 'middle';
          ctx.fillText(safeRateLabel.value, (chartArea.left + chartArea.right) / 2, (chartArea.top + chartArea.bottom) / 2);
          ctx.restore();
        },
      },
    ],
  });
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
    highRiskEquipment: readinessPayload.highRiskEquipment || readinessPayload.high_risk_equipment || [],
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

function buildRiskNarrative(bands = []) {
  const normalizedBands = Array.isArray(bands) ? bands : [];
  const getCount = (label) => Number(normalizedBands.find((band) => band?.label === label)?.count || 0);
  const totalEquipment = normalizedBands.reduce((sum, band) => sum + Number(band?.count || 0), 0);
  const highRisk = getCount('High Risk');
  const mediumRisk = getCount('Medium Risk');
  const lowRisk = getCount('Low Risk');
  const veryLowRisk = getCount('Very Low Risk');
  const safeEquipment = lowRisk + veryLowRisk;
  const attentionCount = highRisk + mediumRisk;
  const safeRate = totalEquipment > 0 ? Math.round((safeEquipment / totalEquipment) * 100) : 0;

  const dominantBand = [
    { label: 'Low Risk', count: lowRisk },
    { label: 'High Risk', count: highRisk },
    { label: 'Medium Risk', count: mediumRisk },
    { label: 'Very Low Risk', count: veryLowRisk },
  ].sort((left, right) => right.count - left.count)[0] || { label: 'Low Risk', count: 0 };

  const summary = totalEquipment > 0
    ? `Most tracked equipment is currently in a ${dominantBand.label} condition: ${formatMetricNumber(dominantBand.count, 0)} out of ${formatMetricNumber(totalEquipment, 0)} equipment items (${safeRate}% safe rate). ${attentionCount > 0 ? `${formatMetricNumber(attentionCount, 0)} items still require attention—${formatMetricNumber(highRisk, 0)} High Risk and ${formatMetricNumber(mediumRisk, 0)} Medium Risk.` : 'No equipment currently requires immediate escalation.'} ${veryLowRisk === 0 ? 'No equipment falls under Very Low Risk, which means all tracked assets still have at least one monitored operational risk factor.' : `${formatMetricNumber(veryLowRisk, 0)} equipment items are already in Very Low Risk status.`}`
    : 'No readiness risk data is available for the selected range.';

  const interpretation = totalEquipment > 0
    ? `Overall inventory readiness is ${attentionCount > 0 ? 'stable, but the High- and Medium-Risk equipment should be reviewed first' : 'stable'}. Prioritize items affected by inactive availability, low stock pressure, frequent use, or overdue release status to prevent disruption to reservations and operations.`
    : 'There is no risk distribution to interpret for this range.';

  return { summary, interpretation };
}

function buildForecastNarrative(forecast = {}, actualSeries = [], forecastSeries = []) {
  const actualValues = Array.isArray(actualSeries) ? actualSeries.map((item) => Number(item?.value || 0)) : [];
  const forecastValues = Array.isArray(forecastSeries) ? forecastSeries.map((item) => Number(item?.value || 0)) : [];
  const actualPeak = actualValues.length > 0 ? Math.max(...actualValues) : 0;
  const actualPeakIndex = actualValues.indexOf(actualPeak);
  const actualTailAverage = actualValues.length >= 3
    ? actualValues.slice(-3).reduce((sum, value) => sum + value, 0) / 3
    : actualValues.reduce((sum, value) => sum + value, 0);
  const forecastPeak = Number(forecast?.peakValue || (forecastValues.length > 0 ? Math.max(...forecastValues) : 0));
  const forecastPeakIndex = forecastValues.indexOf(forecastPeak);
  const forecastTailAverage = forecastValues.length >= 3
    ? forecastValues.slice(-3).reduce((sum, value) => sum + value, 0) / 3
    : forecastValues.reduce((sum, value) => sum + value, 0);
  const actualAverage = actualValues.length > 0 ? actualValues.reduce((sum, value) => sum + value, 0) / actualValues.length : 0;
  const forecastAverage = forecastValues.length > 0 ? forecastValues.reduce((sum, value) => sum + value, 0) / forecastValues.length : 0;
  const forecastLookAheadAverage = forecastValues.slice(-3).reduce((sum, value) => sum + value, 0) / Math.max(1, Math.min(3, forecastValues.length));
  const midpointTail = forecastValues.length >= 1
    ? forecastValues
        .slice(-3)
        .map((value, index, values) => {
          const actualValue = actualValues.slice(-values.length)[index] ?? actualAverage;
          return (Number(actualValue || 0) + Number(value || 0)) / 2;
        })
    : [];
  const midpointTrend = midpointTail.length > 0 ? midpointTail.reduce((sum, value) => sum + value, 0) / midpointTail.length : 0;
  const growthPercent = Number(forecast?.growthPercent || 0);
  const peakDate = formatLongDate(forecast?.peakDate);
  const forecastGap = forecastPeak - actualPeak;
  const averageGap = forecastAverage - actualAverage;
  const peakInForecast = forecastPeakIndex >= 0 && actualPeakIndex >= 0 ? forecastPeakIndex >= actualPeakIndex : false;
  const isSmoothTrend = forecastValues.length >= 4 && Math.max(...forecastValues) - Math.min(...forecastValues) <= Math.max(2, forecastAverage * 0.25);

  const summary = actualValues.length > 0 || forecastValues.length > 0
    ? `This graph compares ${formatMetricNumber(actualValues.length, 0)} actual demand points with ${formatMetricNumber(forecastValues.length, 0)} forecast points over the selected range. The forecast peaks at ${formatMetricNumber(forecastPeak, 1)} requests on ${peakDate}, while actual demand peaks at ${formatMetricNumber(actualPeak, 1)} requests.`
    : 'No demand series is available for this range.';

  const tldr = forecastValues.length > 0
    ? [
        forecastLookAheadAverage >= actualAverage + 2
          ? `If the next 3 days stay above ${formatMetricNumber(actualAverage, 1)} requests, prepare more equipment and staff now.`
          : forecastLookAheadAverage <= actualAverage - 2
            ? `If the next 3 days fall below ${formatMetricNumber(actualAverage, 1)} requests, keep inventory lean and avoid over-preparing.`
            : `If the next 3 days stay near ${formatMetricNumber(actualAverage, 1)} requests, hold current allocation and monitor for changes.`,
        midpointTrend >= forecastLookAheadAverage
          ? 'If the midpoint trend stays above the look-ahead average, keep a cautious buffer because demand may flatten later.'
          : 'If the midpoint trend stays below the look-ahead average, move to a proactive allocation plan because demand may rise later.',
        forecastGap >= 3
          ? 'If the forecast peak is higher than the current peak, expect a sharper surge and stage resources earlier.'
          : forecastGap <= -3
            ? 'If the forecast peak is lower than the current peak, the surge may already be passing and the load should ease.'
            : 'If the forecast peak stays close to the current peak, keep the same operating rhythm and review daily.',
      ].join(' ')
    : 'No forecast trend is available yet for a decision check.';

  const interpretation = forecastValues.length > 0
    ? [
        growthPercent >= 15
          ? 'Demand is materially above the previous period, so reserve extra equipment earlier and watch for capacity pressure.'
          : growthPercent >= 0
            ? 'Demand is slightly above the previous period, so keep a moderate buffer and monitor the next booking cycle.'
            : 'Demand is below the previous period, so release surplus inventory cautiously and avoid overcommitting stock.',
        forecastGap >= 5
          ? 'The forecast peak is much higher than actual demand, which suggests a strong seasonal surge or a concentrated booking window.'
          : forecastGap >= 2
            ? 'The forecast peak is moderately higher than actual demand, which suggests a clear upward shift in request volume.'
            : forecastGap <= -2
              ? 'The forecast peak is below the current peak, so future demand may soften after the current burst.'
              : 'Forecast and actual peaks are close, so the next period should stay near the current operational pattern.',
        averageGap >= 3
          ? 'Average forecast volume is noticeably above actual demand, so this range should be staffed as a higher-demand cycle.'
          : averageGap <= -3
            ? 'Average forecast volume is noticeably below actual demand, so current usage may be tapering off.'
            : 'Average forecast volume is close to actual demand, so the overall pattern is steady.',
        forecastLookAheadAverage >= actualAverage + 2
          ? `The next 3 days average ${formatMetricNumber(forecastLookAheadAverage, 1)} requests, so prepare for a short-term rise in demand.`
          : forecastLookAheadAverage <= actualAverage - 2
            ? `The next 3 days average ${formatMetricNumber(forecastLookAheadAverage, 1)} requests, so demand should ease slightly.`
            : `The next 3 days average ${formatMetricNumber(forecastLookAheadAverage, 1)} requests, so demand should stay near the current baseline.`,
        midpointTrend >= forecastLookAheadAverage
          ? `The midpoint trend sits at ${formatMetricNumber(midpointTrend, 1)} requests, which leans closer to the actual demand line and supports a cautious allocation plan.`
          : `The midpoint trend sits at ${formatMetricNumber(midpointTrend, 1)} requests, which leans toward the forecast line and supports a more proactive allocation plan.`,
        peakInForecast
          ? 'The forecast peak arrives at or after the actual peak, which usually means the pressure is moving later in the period.'
          : 'The forecast peak appears earlier than the actual peak, which may indicate an early spike followed by a taper.',
        isSmoothTrend
          ? `The forecast line is relatively smooth, with a tail average of ${formatMetricNumber(forecastTailAverage, 1)} requests; that makes it better for base staffing plans.`
          : `The forecast line is volatile, with a tail average of ${formatMetricNumber(forecastTailAverage, 1)} requests; that supports a more flexible buffer plan.`,
      ].join(' ')
    : 'There is no forecast trend to interpret for this range.';

  return { tldr, summary, interpretation };
}

function buildForecastDisplaySeries(actualSeries = [], forecastSeries = []) {
  const actualMap = new Map();
  const forecastMap = new Map();

  actualSeries.forEach((item) => {
    const key = item?.date || item?.label || '';
    if (key) {
      actualMap.set(key, Number(item?.value || 0));
    }
  });

  forecastSeries.forEach((item) => {
    const key = item?.date || item?.label || '';
    if (key) {
      forecastMap.set(key, Number(item?.value || 0));
    }
  });

  const dateComparator = (left, right) => parseDateOnly(left).getTime() - parseDateOnly(right).getTime();
  const actualKeys = [...actualMap.keys()].sort(dateComparator);
  const forecastKeys = [...forecastMap.keys()].sort(dateComparator);
  const lastActualKey = actualKeys[actualKeys.length - 1];
  const lastActualDate = lastActualKey ? parseDateOnly(lastActualKey) : null;
  const normalizedForecastKeys = forecastKeys.filter((key) => {
    if (!lastActualDate) {
      return true;
    }
    const date = parseDateOnly(key);
    return Number.isNaN(date.getTime()) || date > lastActualDate;
  });

  if (normalizedForecastKeys.length === 0 && lastActualDate && actualKeys.length > 0) {
    const tailValues = actualKeys.slice(-3).map((key) => actualMap.get(key) || 0);
    const tailAverage = tailValues.length > 0 ? tailValues.reduce((sum, value) => sum + value, 0) / tailValues.length : 0;
    for (let offset = 1; offset <= 3; offset += 1) {
      const futureDate = new Date(lastActualDate);
      futureDate.setDate(futureDate.getDate() + offset);
      const key = formatDateKeyLocal(futureDate);
      normalizedForecastKeys.push(key);
      if (!forecastMap.has(key)) {
        forecastMap.set(key, roundForecastValue(tailAverage));
      }
    }
  }

  const labels = [...new Set([...actualKeys, ...normalizedForecastKeys])].sort(dateComparator);
  const actualValues = labels.map((label) => {
    const isFutureForecastLabel = lastActualDate && parseDateOnly(label).getTime() > lastActualDate.getTime();
    return isFutureForecastLabel ? null : Number(actualMap.get(label) ?? 0);
  });
  const forecastValues = labels.map((label) => Number(forecastMap.get(label) ?? 0));

  return { labels, actualValues, forecastValues };
}

function roundForecastValue(value) {
  return Math.round(Number(value || 0) * 100) / 100;
}

function formatDateKeyLocal(date) {
  if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
    return '';
  }

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function buildOptimizationNarrative(metrics = [], summary = {}) {
  const normalizedMetrics = Array.isArray(metrics) ? metrics : [];
  const utilizationMetric = normalizedMetrics.find((metric) => String(metric?.label || '').toLowerCase().includes('utilization'));
  const conflictMetric = normalizedMetrics.find((metric) => String(metric?.label || '').toLowerCase().includes('conflict'));
  const constraintMetric = normalizedMetrics.find((metric) => String(metric?.label || '').toLowerCase().includes('constraint'));
  const unassignedMetric = normalizedMetrics.find((metric) => String(metric?.label || '').toLowerCase().includes('unassigned'));

  const utilizationValue = Number(utilizationMetric?.value || 0);
  const conflictValue = Number(conflictMetric?.value || 0);
  const constraintValue = Number(constraintMetric?.value || 0);
  const unassignedValue = Number(unassignedMetric?.value || 0);
  const completed = Number(summary?.completedThisPeriod || 0);
  const activeReservations = Number(summary?.activeReservations || 0);

  const summaryText = normalizedMetrics.length > 0
    ? `This panel compares ${formatMetricNumber(activeReservations, 0)} active reservations and ${formatMetricNumber(completed, 0)} completed requests with efficiency indicators for conflict reduction, utilization, constraint satisfaction, and unassigned work.`
    : 'No allocation efficiency data is available for this range.';

  const interpretation = normalizedMetrics.length > 0
    ? [
        conflictValue >= 10
          ? 'Conflict reduction is improving strongly, so overlapping reservations are being handled well.'
          : conflictValue >= 0
            ? 'Conflict reduction is flat to slightly positive, so the current schedule is holding but still needs monitoring.'
            : 'Conflict reduction is slipping, so overlapping bookings or timing clashes need attention.',
        utilizationValue >= 10
          ? 'Equipment utilization is trending up, which means demand is being absorbed efficiently.'
          : utilizationValue >= 0
            ? 'Equipment utilization is steady, which supports the current allocation plan.'
            : 'Equipment utilization is falling, so some inventory may be underused or waiting idle.',
        constraintValue >= 95
          ? 'Constraint satisfaction is very high, so most requests are being resolved within the current rules.'
          : constraintValue >= 80
            ? 'Constraint satisfaction is acceptable, but the remaining unresolved items should be checked for bottlenecks.'
            : 'Constraint satisfaction is weak, which suggests the current allocation rules are blocking too many requests.',
        unassignedValue <= 5
          ? 'Unassigned requests are low, so the system is matching demand well.'
          : unassignedValue <= 20
            ? 'Unassigned requests are moderate, so a small redistribution may help.'
            : 'Unassigned requests are high, so the allocation plan should be revisited sooner rather than later.',
      ].join(' ')
    : 'There is no efficiency trend to interpret for this range.';

  return { summary: summaryText, interpretation };
}

function buildUtilizationNarrative(items = []) {
  const normalizedItems = Array.isArray(items) ? items : [];
  const sortedItems = [...normalizedItems].sort((left, right) => Number(right?.value || 0) - Number(left?.value || 0));
  const topItems = sortedItems.slice(0, 5);
  const highest = topItems[0];
  const lowest = topItems[topItems.length - 1];
  const highestValue = Number(highest?.value || 0);
  const lowestValue = Number(lowest?.value || 0);
  const utilizationSpread = highestValue - lowestValue;
  const topAverage = topItems.length > 0
    ? topItems.reduce((sum, item) => sum + Number(item?.value || 0), 0) / topItems.length
    : 0;

  const summary = topItems.length > 0
    ? `This chart ranks the top ${formatMetricNumber(topItems.length, 0)} categories by utilization, from ${highest?.label || 'the highest category'} at ${formatMetricNumber(highest?.value || 0, 0)}% down to ${lowest?.label || 'the lowest visible category'} at ${formatMetricNumber(lowest?.value || 0, 0)}%.`
    : 'No category utilization data is available yet.';

  const interpretation = topItems.length > 0
    ? [
        highestValue >= 75
          ? 'The leading category is very active, so it should keep the highest stock buffer.'
          : highestValue >= 40
            ? 'The leading category is moderately active, so it needs regular replenishment but not emergency allocation.'
            : 'The leading category is still low, so the current stock level is adequate unless demand changes quickly.',
        utilizationSpread >= 30
          ? 'The spread between top and bottom categories is wide, which points to uneven demand and a need for targeted reallocation.'
          : utilizationSpread >= 10
            ? 'The spread between categories is moderate, so the inventory mix is somewhat balanced but still needs tuning.'
            : 'The categories are clustered closely together, so a balanced allocation strategy should work well.',
        topAverage >= 50
          ? `The top categories average ${formatMetricNumber(topAverage, 0)}%, so the most-used equipment group should be protected first.`
          : `The top categories average only ${formatMetricNumber(topAverage, 0)}%, so you can keep a lighter buffer and watch for changes.`,
        lowestValue <= 20
          ? 'The lowest categories are quiet enough to consider consolidation or temporary redistribution.'
          : 'The lower categories still see meaningful usage, so avoid cutting them too aggressively.',
      ].join(' ')
    : 'There is no category utilization trend to interpret for this range.';

  return { summary, interpretation };
}

function resolveUtilizationTooltip(item) {
  if (!item) {
    return 'Equipment utilization';
  }

  const value = formatMetricNumber(item.value || 0, 0);
  return `${item.label || 'Category'} utilization: ${value}%`;
}

function resolveOptimizationDecision(metric) {
  const label = String(metric?.label || '').toLowerCase();

  if (label.includes('conflict')) {
    return 'Decision: reduce clashes by reserving fewer overlapping items at the same time.';
  }

  if (label.includes('utilization')) {
    return 'Decision: keep this allocation if demand is steady; add inventory if the rate keeps rising.';
  }

  if (label.includes('constraint')) {
    return 'Decision: continue the current plan and monitor unresolved requests for bottlenecks.';
  }

  if (label.includes('unassigned')) {
    return 'Decision: reassign idle inventory or relax scheduling limits if this stays above target.';
  }

  return 'Decision: review this metric alongside the forecast and risk bands before reallocating.';
}
</script>
