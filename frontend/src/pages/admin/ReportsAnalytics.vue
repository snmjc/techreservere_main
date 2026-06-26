<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="reports-analytics-page">
      <div ref="reportSurfaceRef" class="reports-export-surface" :class="{ 'is-exporting-pdf': isPreparingPdf }">
        <div class="reports-pdf-page">
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
              {{ isReportsLoading ? 'Refreshing...' : 'Refresh' }}
            </button>
            <button
              class="reports-models-button"
              type="button"
              @click="openModelSheet"
            >
              PKL Models
            </button>
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
        </div>

        <div class="reports-pdf-page">
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
        </div>

        <div class="reports-pdf-page">
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

      <div v-if="isModelSheetOpen" class="reports-sheet-backdrop" @click.self="closeModelSheet">
        <aside class="reports-model-sheet" aria-label="PKL model manager">
          <div class="reports-model-sheet-header">
            <div>
              <p>Model Artifacts</p>
              <h3>PKL Sets</h3>
            </div>
            <button type="button" class="reports-modal-close" @click="closeModelSheet">×</button>
          </div>

          <p class="reports-model-sheet-copy">
            Create, rename, mix, and switch the PKL artifacts used by each analytics model.
          </p>

          <div class="reports-model-sheet-actions">
            <button
              type="button"
              class="reports-primary-button"
              :disabled="isRefreshingDailyAnalytics"
              @click="handleRefreshDailyAnalytics"
            >
              {{ isRefreshingDailyAnalytics ? 'Refreshing...' : 'Refresh Today Analytics' }}
            </button>
            <button
              type="button"
              class="reports-secondary-button"
              :disabled="isCreatingModelSet"
              @click="handleCreateTestModelSet"
            >
              {{ isCreatingModelSet ? 'Creating PKL...' : 'Create Test PKL' }}
            </button>
            <button
              type="button"
              class="reports-secondary-button"
              :disabled="isModelArtifactsLoading"
              @click="loadModelArtifacts"
            >
              {{ isModelArtifactsLoading ? 'Loading...' : 'Refresh List' }}
            </button>
          </div>

          <p v-if="modelArtifactMessage" class="reports-inline-message" :class="{ 'is-success': modelArtifactMessageType === 'success', 'is-error': modelArtifactMessageType === 'error' }">
            {{ modelArtifactMessage }}
          </p>

          <div v-if="isModelArtifactsLoading" class="reports-inline-message">Loading PKL sets...</div>
          <div v-else-if="modelArtifactSets.length === 0" class="reports-inline-message">No PKL sets are available yet.</div>
          <div v-else class="reports-model-set-list">
            <article
              v-for="modelSet in modelArtifactSets"
              :key="modelSet.setName"
              class="reports-model-set-card"
              :class="{ 'is-active': modelSet.active }"
            >
              <div class="reports-model-set-card-header">
                <div>
                  <strong>{{ modelSet.setName }}</strong>
                  <span>{{ modelSet.active ? 'Active for analytics' : 'Available PKL set' }}</span>
                </div>
                <em>{{ modelSet.complete ? 'Complete' : 'Partial' }}</em>
              </div>

              <dl>
                <div>
                  <dt>Trained</dt>
                  <dd>{{ formatModelArtifactDate(modelSet.trainedAt) }}</dd>
                </div>
                <div>
                  <dt>Artifacts</dt>
                  <dd>{{ countExistingArtifacts(modelSet) }}/3</dd>
                </div>
              </dl>

              <ul>
                <li v-for="artifact in modelSet.artifacts" :key="artifact.artifact">
                  <div>
                    <span>{{ formatArtifactLabel(artifact.artifact) }}</span>
                    <small>{{ artifact.active ? 'active' : (artifact.exists ? 'ready' : 'missing') }}</small>
                  </div>
                  <button
                    type="button"
                    class="reports-mini-button"
                    :disabled="artifact.active || !artifact.exists || isSwappingModelArtifact === `${modelSet.setName}:${artifact.artifact}`"
                    @click="handleActivateModelArtifact(modelSet.setName, artifact.artifact)"
                  >
                    {{ artifact.active ? 'Using' : 'Use' }}
                  </button>
                </li>
              </ul>

              <div class="reports-model-rename-row">
                <input
                  v-model.trim="modelSetRenameDrafts[modelSet.setName]"
                  type="text"
                  :placeholder="modelSet.setName === 'default' ? 'Default cannot be renamed' : 'Rename PKL set'"
                  :disabled="modelSet.setName === 'default' || isRenamingModelSet === modelSet.setName"
                />
                <button
                  type="button"
                  class="reports-secondary-button"
                  :disabled="modelSet.setName === 'default' || isRenamingModelSet === modelSet.setName || !modelSetRenameDrafts[modelSet.setName]"
                  @click="handleRenameModelSet(modelSet.setName)"
                >
                  {{ isRenamingModelSet === modelSet.setName ? 'Renaming...' : 'Rename' }}
                </button>
              </div>

              <div class="reports-model-set-actions">
                <button
                  type="button"
                  class="reports-secondary-button"
                  :disabled="modelSet.active || !modelSet.complete || isSwappingModelSet"
                  @click="handleActivateModelSet(modelSet.setName)"
                >
                  {{ modelSet.active ? 'Using' : 'Use This PKL' }}
                </button>
                <button
                  type="button"
                  class="reports-danger-button"
                  :disabled="isDeletingModelSet === modelSet.setName"
                  @click="handleDeleteModelSet(modelSet.setName)"
                >
                  {{ isDeletingModelSet === modelSet.setName ? 'Deleting...' : 'Delete' }}
                </button>
              </div>
            </article>
          </div>
        </aside>
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
const isModelSheetOpen = ref(false);
const isModelArtifactsLoading = ref(false);
const isCreatingModelSet = ref(false);
const isRefreshingDailyAnalytics = ref(false);
const isSwappingModelSet = ref(false);
const isSwappingModelArtifact = ref('');
const isRenamingModelSet = ref('');
const isDeletingModelSet = ref('');
const selectedAnalyticsScenario = ref('clean_data');
const reportsError = ref('');
const analyticsToastMessage = ref('');
const analyticsRunStatus = ref('');
const analyticsRunStatusType = ref('info');
const modelArtifactMessage = ref('');
const modelArtifactMessageType = ref('info');
const pdfError = ref('');
const isPreparingPdf = ref(false);
const isUtilizationRefreshing = ref(false);
const reportSurfaceRef = ref(null);
const forecastChartRef = ref(null);
const riskChartRef = ref(null);
const utilizationChartRef = ref(null);
const chartRenderer = createReportsAnalyticsChartRenderer();
const forecastReport = ref(createEmptyForecastReport());
const riskReport = ref(createEmptyRiskReport());
const optimizationReport = ref([]);
const utilizationReport = ref(createEmptyUtilizationReport());
const summaryReport = ref(createEmptySummaryReport());
const reportsSourceLabel = ref('Loading stored analytics...');
const modelArtifacts = ref({
  activeSet: 'default',
  activeArtifacts: {},
  sets: [],
});
const modelSetRenameDrafts = ref({});

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
const modelArtifactSets = computed(() => Array.isArray(modelArtifacts.value?.sets) ? modelArtifacts.value.sets : []);

onMounted(() => {
  loadReportsAnalytics();
});

onBeforeUnmount(() => {
  destroyCharts();
});

watch(selectedRangeKey, () => {
  loadReportsAnalytics();
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
  isReportsLoading.value = true;
  isUtilizationRefreshing.value = true;
  reportsError.value = '';
  pdfError.value = '';

  try {
    applyEmptyAnalyticsSections();
    applyLiveAnalyticsSections(await adminAnalyticsApi.getReportsAnalytics(activeRange.value));
    reportsSourceLabel.value = `Using live aggregation for ${activeRangeLabel.value}.`;
  } catch (error) {
    if (preferLiveOnly) {
      applyEmptyAnalyticsSections();
      reportsSourceLabel.value = 'Analytics data is unavailable right now.';
      return;
    }

    try {
      const latestResultsResponse = await adminAnalyticsApi.getLatestAnalyticsResults();
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

async function refreshReportsAfterRun() {
  const maxAttempts = 4;
  let attempt = 0;

  while (attempt < maxAttempts) {
    const liveAnalytics = await adminAnalyticsApi.getReportsAnalytics(activeRange.value);
    const latestResultsResponse = await adminAnalyticsApi.getLatestAnalyticsResults();
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
  forecastReport.value = liveAnalytics?.forecast || createEmptyForecastReport();
  riskReport.value = liveAnalytics?.riskDistribution || createEmptyRiskReport();
  optimizationReport.value = Array.isArray(liveAnalytics?.optimizationMetrics) ? liveAnalytics.optimizationMetrics : [];
  utilizationReport.value = {
    items: Array.isArray(liveAnalytics?.utilizationByCategory) ? liveAnalytics.utilizationByCategory : [],
    comparisonItems: Array.isArray(liveAnalytics?.utilizationComparisonByCategory)
      ? liveAnalytics.utilizationComparisonByCategory
      : [],
    topEquipment: Array.isArray(liveAnalytics?.topEquipment) ? liveAnalytics.topEquipment : [],
  };
  summaryReport.value = liveAnalytics?.summary || createEmptySummaryReport();
}

async function openModelSheet() {
  isModelSheetOpen.value = true;
  await loadModelArtifacts();
}

function closeModelSheet() {
  if (
    isCreatingModelSet.value
    || isRefreshingDailyAnalytics.value
    || isSwappingModelSet.value
    || isSwappingModelArtifact.value
    || isRenamingModelSet.value
    || isDeletingModelSet.value
  ) {
    return;
  }

  isModelSheetOpen.value = false;
}

async function loadModelArtifacts() {
  isModelArtifactsLoading.value = true;
  modelArtifactMessage.value = '';

  try {
    const response = await adminAnalyticsApi.listAnalyticsModelArtifacts();
    modelArtifacts.value = response?.modelArtifacts || response || { activeSet: 'default', sets: [] };
    modelSetRenameDrafts.value = Object.fromEntries(
      modelArtifactSets.value.map((modelSet) => [
        modelSet.setName,
        modelSetRenameDrafts.value[modelSet.setName] || '',
      ]),
    );
  } catch (error) {
    modelArtifactMessage.value = resolveReportsError(error);
    modelArtifactMessageType.value = 'error';
  } finally {
    isModelArtifactsLoading.value = false;
  }
}

async function handleCreateTestModelSet() {
  if (isCreatingModelSet.value) {
    return;
  }

  isCreatingModelSet.value = true;
  modelArtifactMessage.value = 'Training a new PKL set...';
  modelArtifactMessageType.value = 'info';

  try {
    const setName = `test-${new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19).toLowerCase()}`;
    const response = await adminAnalyticsApi.trainAnalyticsModels({ setName, activate: true });
    const trainingRun = response?.trainingRun || response || {};
    modelArtifactMessage.value = `Created and activated ${trainingRun.setName || setName}.`;
    modelArtifactMessageType.value = 'success';
    analyticsToastMessage.value = 'PKL test set created and activated.';
    await loadModelArtifacts();
    await loadReportsAnalytics();
  } catch (error) {
    modelArtifactMessage.value = resolveReportsError(error);
    modelArtifactMessageType.value = 'error';
  } finally {
    isCreatingModelSet.value = false;
  }
}

async function handleRefreshDailyAnalytics() {
  if (isRefreshingDailyAnalytics.value) {
    return;
  }

  isRefreshingDailyAnalytics.value = true;
  modelArtifactMessage.value = 'Refreshing today analytics with the active PKL set...';
  modelArtifactMessageType.value = 'info';
  reportsError.value = '';

  try {
    const analyticsResponse = await adminAnalyticsApi.refreshDailyAnalytics(activeRange.value);
    applyStoredAnalyticsSections(normalizeStoredAnalyticsResponse(analyticsResponse));
    reportsSourceLabel.value = `Using refreshed FastAPI analytics for ${activeRangeLabel.value}.`;
    modelArtifactMessage.value = 'Today analytics refreshed.';
    modelArtifactMessageType.value = 'success';
    analyticsToastMessage.value = 'Today analytics refreshed with the active PKL set.';
    await loadModelArtifacts();
  } catch (error) {
    const message = resolveReportsError(error);
    reportsError.value = message;
    modelArtifactMessage.value = message;
    modelArtifactMessageType.value = 'error';
  } finally {
    isRefreshingDailyAnalytics.value = false;
  }
}

async function handleActivateModelSet(setName) {
  if (!setName || isSwappingModelSet.value) {
    return;
  }

  isSwappingModelSet.value = true;
  modelArtifactMessage.value = `Switching analytics to ${setName}...`;
  modelArtifactMessageType.value = 'info';

  try {
    await adminAnalyticsApi.activateAnalyticsModelSet(setName);
    modelArtifactMessage.value = `Analytics is now using ${setName}.`;
    modelArtifactMessageType.value = 'success';
    analyticsToastMessage.value = `Active PKL set switched to ${setName}.`;
    await loadModelArtifacts();
    await loadReportsAnalytics();
  } catch (error) {
    modelArtifactMessage.value = resolveReportsError(error);
    modelArtifactMessageType.value = 'error';
  } finally {
    isSwappingModelSet.value = false;
  }
}

async function handleActivateModelArtifact(setName, artifact) {
  if (!setName || !artifact || isSwappingModelArtifact.value) {
    return;
  }

  const operationKey = `${setName}:${artifact}`;
  isSwappingModelArtifact.value = operationKey;
  modelArtifactMessage.value = `Using ${formatArtifactLabel(artifact)} from ${setName}...`;
  modelArtifactMessageType.value = 'info';

  try {
    await adminAnalyticsApi.activateAnalyticsModelArtifact(setName, artifact);
    modelArtifactMessage.value = `${formatArtifactLabel(artifact)} now uses ${setName}.`;
    modelArtifactMessageType.value = 'success';
    analyticsToastMessage.value = `${formatArtifactLabel(artifact)} PKL switched to ${setName}.`;
    await loadModelArtifacts();
    await loadReportsAnalytics();
  } catch (error) {
    modelArtifactMessage.value = resolveReportsError(error);
    modelArtifactMessageType.value = 'error';
  } finally {
    isSwappingModelArtifact.value = '';
  }
}

async function handleRenameModelSet(setName) {
  const newName = modelSetRenameDrafts.value[setName];
  if (!setName || !newName || isRenamingModelSet.value) {
    return;
  }

  isRenamingModelSet.value = setName;
  modelArtifactMessage.value = `Renaming ${setName}...`;
  modelArtifactMessageType.value = 'info';

  try {
    const response = await adminAnalyticsApi.renameAnalyticsModelSet(setName, newName);
    const renamed = response?.renamedModelSet || response || {};
    const resolvedName = renamed.renamedTo || newName;
    modelArtifactMessage.value = `Renamed ${setName} to ${resolvedName}.`;
    modelArtifactMessageType.value = 'success';
    modelSetRenameDrafts.value[setName] = '';
    await loadModelArtifacts();
    await loadReportsAnalytics();
  } catch (error) {
    modelArtifactMessage.value = resolveReportsError(error);
    modelArtifactMessageType.value = 'error';
  } finally {
    isRenamingModelSet.value = '';
  }
}

async function handleDeleteModelSet(setName) {
  if (!setName || isDeletingModelSet.value) {
    return;
  }

  const shouldDelete = window.confirm(`Delete PKL set "${setName}"?`);
  if (!shouldDelete) {
    return;
  }

  isDeletingModelSet.value = setName;
  modelArtifactMessage.value = `Deleting ${setName}...`;
  modelArtifactMessageType.value = 'info';

  try {
    await adminAnalyticsApi.deleteAnalyticsModelSet(setName);
    modelArtifactMessage.value = `Deleted ${setName}.`;
    modelArtifactMessageType.value = 'success';
    await loadModelArtifacts();
    await loadReportsAnalytics();
  } catch (error) {
    modelArtifactMessage.value = resolveReportsError(error);
    modelArtifactMessageType.value = 'error';
  } finally {
    isDeletingModelSet.value = '';
  }
}

function applyStoredAnalyticsSections(storedAnalytics) {
  forecastReport.value = storedAnalytics?.forecast || createEmptyForecastReport();
  riskReport.value = storedAnalytics?.riskDistribution || createEmptyRiskReport();
  optimizationReport.value = Array.isArray(storedAnalytics?.optimizationMetrics) ? storedAnalytics.optimizationMetrics : [];
  utilizationReport.value = {
    items: Array.isArray(storedAnalytics?.utilizationByCategory) ? storedAnalytics.utilizationByCategory : [],
    comparisonItems: Array.isArray(storedAnalytics?.utilizationComparisonByCategory)
      ? storedAnalytics.utilizationComparisonByCategory
      : [],
    topEquipment: Array.isArray(storedAnalytics?.topEquipment) ? storedAnalytics.topEquipment : [],
  };
  summaryReport.value = storedAnalytics?.summary || createEmptySummaryReport();
}

function applyScenarioAnalyticsSections(liveAnalytics, storedAnalytics) {
  forecastReport.value = liveAnalytics?.forecast || createEmptyForecastReport();
  riskReport.value = hasRiskDistribution(storedAnalytics?.riskDistribution)
    ? storedAnalytics.riskDistribution
    : liveAnalytics?.riskDistribution || createEmptyRiskReport();
  optimizationReport.value = pickNonEmptyArray(storedAnalytics?.optimizationMetrics, liveAnalytics?.optimizationMetrics);
  utilizationReport.value = {
    items: pickNonEmptyArray(storedAnalytics?.utilizationByCategory, liveAnalytics?.utilizationByCategory),
    comparisonItems: pickNonEmptyArray(
      storedAnalytics?.utilizationComparisonByCategory,
      liveAnalytics?.utilizationComparisonByCategory,
    ),
    topEquipment: pickNonEmptyArray(storedAnalytics?.topEquipment, liveAnalytics?.topEquipment),
  };
  summaryReport.value = {
    ...(liveAnalytics?.summary || createEmptySummaryReport()),
    ...(storedAnalytics?.summary || {}),
    generatedAt: storedAnalytics?.summary?.generatedAt || liveAnalytics?.summary?.generatedAt || 'N/A',
  };
}

function wait(milliseconds) {
  return new Promise((resolve) => {
    window.setTimeout(resolve, milliseconds);
  });
}

function countExistingArtifacts(modelSet) {
  return (modelSet?.artifacts || []).filter((artifact) => artifact?.exists).length;
}

function formatArtifactLabel(artifactName) {
  switch (artifactName) {
    case 'demand_forecast.pkl':
      return 'Forecast PKL';
    case 'readiness_random_forest.pkl':
      return 'Readiness PKL';
    case 'allocation_optimizer.pkl':
      return 'Allocation PKL';
    default:
      return artifactName || 'PKL';
  }
}

function formatModelArtifactDate(value) {
  if (!value) {
    return 'Not trained yet';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
}

function closeScenarioModal() {
  if (isTriggeringAnalytics.value) {
    return;
  }

  analyticsRunStatus.value = '';
  isScenarioModalOpen.value = false;
}

function handleRefreshReports() {
  loadReportsAnalytics();
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
    const pdf = new jsPDF('p', 'mm', 'a4');
    const pageWidth = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();
    const margin = 10;
    const contentWidth = pageWidth - (margin * 2);
    const contentHeight = pageHeight - (margin * 2);

    isPreparingPdf.value = true;
    await nextTick();
    await wait(120);

    const exportPages = Array.from(reportSurfaceRef.value.querySelectorAll('.reports-pdf-page'));
    if (exportPages.length === 0) {
      throw new Error('Unable to prepare the analytics pages for export.');
    }

    for (const [index, exportPage] of exportPages.entries()) {
      const canvas = await html2canvas(exportPage, {
        backgroundColor: '#f5faf7',
        scale: 2,
        useCORS: true,
        logging: false,
        windowWidth: exportPage.scrollWidth,
        windowHeight: exportPage.scrollHeight,
      });
      const imageData = canvas.toDataURL('image/png');
      const widthScale = contentWidth / canvas.width;
      const heightScale = contentHeight / canvas.height;
      const renderScale = Math.min(widthScale, heightScale);
      const imageWidth = canvas.width * renderScale;
      const imageHeight = canvas.height * renderScale;
      const horizontalOffset = margin + ((contentWidth - imageWidth) / 2);
      const verticalOffset = margin + ((contentHeight - imageHeight) / 2);

      if (index > 0) {
        pdf.addPage();
      }

      pdf.addImage(imageData, 'PNG', horizontalOffset, verticalOffset, imageWidth, imageHeight);
    }

    pdf.save(`techreserve-analytics-${activeRange.value.startDateIso}-to-${activeRange.value.endDateIso}.pdf`);
  } catch (error) {
    pdfError.value = error?.message || 'Unable to generate the PDF report right now.';
  } finally {
    isPreparingPdf.value = false;
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
