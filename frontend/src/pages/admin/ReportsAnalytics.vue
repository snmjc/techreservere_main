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
              <select v-model="selectedRangeKey" :disabled="isReportsLoading">
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
            <button
              class="reports-models-button"
              type="button"
              :disabled="isReportsLoading"
              @click="openModelSheet"
            >
              Analytics Models
            </button>
          </div>

        </header>

        <p v-if="reportsError" class="reports-inline-message is-error">{{ reportsError }}</p>
        <p v-else-if="isReportsLoading" class="reports-inline-message">Refreshing report sections...</p>
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
            <em>{{ model.modelLabel }}</em>
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
              <h2>Demand Forecasting (SARIMA)</h2>
              <p>Forecasted equipment demand based on recent reservation volume.</p>
            </div>
          </div>
          <p v-if="isForecastSectionLoading" class="reports-section-loading">Loading demand forecast...</p>
          <p v-if="forecastSectionError" class="reports-inline-message is-error">{{ forecastSectionError }}</p>

          <div class="reports-forecast-layout">
            <div class="reports-chart-card">
              <div v-if="!isForecastSectionLoading && forecastSeries.length === 0" class="reports-inline-message">No reservation demand data is available for this range.</div>
              <div v-else-if="isForecastSectionLoading && forecastSeries.length === 0" class="reports-section-placeholder"></div>
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

          <div class="reports-accordion reports-preference-accordion">
            <details
              :open="isForecastValidationOpen"
              @toggle="handlePreferenceAccordionToggle('forecastValidation', $event)"
            >
              <summary>
                <span>SARIMA Accuracy Validation</span>
                <small>{{ isForecastValidationOpen ? 'Hide metrics' : 'Show metrics' }}</small>
              </summary>
              <div class="reports-validation-grid">
                <article
                  v-for="metric in forecastValidationCards"
                  :key="metric.label"
                  class="reports-validation-card"
                  :class="{ 'is-good': metric.good, 'is-bad': metric.bad }"
                >
                  <span>{{ metric.label }}</span>
                  <strong>{{ metric.value }}</strong>
                  <small>{{ metric.note }}</small>
                </article>
              </div>
            </details>
          </div>
        </section>

        <div class="reports-two-column">
          <section class="reports-panel">
            <h2>Readiness Risk Detection (Random Forest)</h2>
            <p>Risk level distribution across tracked equipment inventory.</p>
            <p v-if="isRiskSectionLoading" class="reports-section-loading">Loading readiness risk detection...</p>
            <p v-if="riskSectionError" class="reports-inline-message is-error">{{ riskSectionError }}</p>
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
            <div class="reports-accordion reports-preference-accordion">
              <details
                :open="isRiskValidationOpen"
                @toggle="handlePreferenceAccordionToggle('riskValidation', $event)"
              >
                <summary>
                  <span>Random Forest Validation Diagnostics</span>
                  <small>{{ isRiskValidationOpen ? 'Hide diagnostics' : 'Show diagnostics' }}</small>
                </summary>
                <div class="reports-rf-metrics-grid">
                  <article v-for="metric in randomForestMetricCards" :key="metric.label">
                    <span>{{ metric.label }}</span>
                    <strong>{{ metric.value }}</strong>
                    <small>{{ metric.note }}</small>
                  </article>
                </div>
                <div class="reports-confusion-matrix-wrap" v-if="confusionMatrixRows.length > 0">
                  <h3>Confusion Matrix</h3>
                  <table class="reports-confusion-matrix">
                    <thead>
                      <tr>
                        <th>Actual \\ Predicted</th>
                        <th v-for="label in confusionMatrixLabels" :key="label">{{ label }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="row in confusionMatrixRows" :key="row.label">
                        <th>{{ row.label }}</th>
                        <td v-for="cell in row.values" :key="`${row.label}-${cell.label}`">{{ cell.value }}</td>
                      </tr>
                    </tbody>
                  </table>
                  <p class="reports-matrix-explainer">{{ confusionMatrixExplanation }}</p>
                </div>
                <div class="reports-risk-probability-grid">
                  <article v-for="metric in riskProbabilityCards" :key="metric.label">
                    <span>{{ metric.label }}</span>
                    <strong>{{ metric.value }}</strong>
                    <small>{{ metric.note }}</small>
                  </article>
                </div>
              </details>
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
            <h2>Resource Allocation Optimization (B-ILP)</h2>
            <p>Efficiency indicators derived from request throughput and inventory usage.</p>
            <p v-if="isOptimizationSectionLoading" class="reports-section-loading">Loading allocation optimization...</p>
            <p v-if="optimizationSectionError" class="reports-inline-message is-error">{{ optimizationSectionError }}</p>
            <div class="reports-optimization-list">
              <article v-for="metric in optimizationMetrics" :key="metric.label">
                <span
                  class="reports-metric-icon"
                  :class="`reports-metric-icon--${resolveMetricDirection(metric.value)}`"
                  :aria-label="resolveMetricDirectionLabel(metric.value)"
                >
                  {{ resolveMetricDirectionIcon(metric.value) }}
                </span>
                <div>
                  <strong>{{ metric.label }}</strong>
                  <small>{{ metric.note }}</small>
                  <small class="reports-decision-note">{{ resolveOptimizationDecision(metric) }}</small>
                </div>
                <em :class="`reports-metric-value reports-metric-value--${resolveMetricDirection(metric.value)}`">{{ formatMetricDelta(metric.value, 1) }}</em>
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
            <h2>Equipment Utilization Overview (Random Forest)</h2>
            <p v-if="isUtilizationSectionLoading" class="reports-section-loading">Loading equipment utilization...</p>
            <p v-if="utilizationSectionError" class="reports-inline-message is-error">{{ utilizationSectionError }}</p>
            <div v-if="!isUtilizationSectionLoading && utilizationItems.length === 0" class="reports-inline-message">No category utilization data is available yet.</div>
            <div v-else-if="isUtilizationSectionLoading && utilizationItems.length === 0" class="reports-section-placeholder"></div>
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
            <p v-if="isEquipmentTrendsSectionLoading" class="reports-section-loading">Loading equipment trends...</p>
            <p v-if="equipmentTrendsSectionError" class="reports-inline-message is-error">{{ equipmentTrendsSectionError }}</p>
            <div class="reports-table-stack">
              <div>
                <h3>Top Frequently Used Equipment</h3>
                <table class="reports-equipment-table">
                  <thead>
                    <tr><th>Equipment</th><th>Times Used</th></tr>
                  </thead>
                  <tbody>
                    <tr v-if="isEquipmentTrendsSectionLoading && topFrequentlyUsedEquipment.length === 0">
                      <td colspan="2">Loading frequent equipment trends...</td>
                    </tr>
                    <tr v-else-if="topFrequentlyUsedEquipment.length === 0">
                      <td colspan="2">No equipment requests were recorded in the selected range.</td>
                    </tr>
                    <tr v-for="item in paginatedTopFrequentlyUsedEquipment" :key="item.name">
                      <td>{{ item.name }}</td>
                      <td>{{ formatMetricNumber(item.count, 0) }}</td>
                    </tr>
                  </tbody>
                </table>
                <div class="reports-equipment-pagination">
                  <button type="button" :disabled="topEquipmentCurrentPage === 1" @click="topEquipmentCurrentPage -= 1">Previous</button>
                  <span>Page {{ topEquipmentCurrentPage }} of {{ topEquipmentTotalPages }}</span>
                  <button type="button" :disabled="topEquipmentCurrentPage === topEquipmentTotalPages" @click="topEquipmentCurrentPage += 1">Next</button>
                </div>
              </div>

              <div>
                <h3>Equipment Preparation Decisions</h3>
                <table class="reports-equipment-table">
                  <thead>
                    <tr><th>Equipment</th><th>Predicted Demand</th><th>Decision</th><th>Recommended Action</th><th class="reports-equipment-table__action-heading">Details</th></tr>
                  </thead>
                  <tbody>
                    <tr v-if="isEquipmentTrendsSectionLoading && possibleBorrowedEquipment.length === 0">
                      <td colspan="5">Loading equipment preparation decisions...</td>
                    </tr>
                    <tr v-else-if="possibleBorrowedEquipment.length === 0">
                      <td colspan="5">No equipment needs preparation based on forecasted demand.</td>
                    </tr>
                    <tr v-for="item in paginatedPossibleBorrowedEquipment" :key="`${item.name}-${item.count}`">
                      <td>{{ item.name }}</td>
                      <td>{{ formatMetricNumber(item.predictedDemand, 1) }}</td>
                      <td>
                        <span class="reports-decision-pill" :class="`reports-decision-pill--${item.tone}`">{{ item.decision }}</span>
                      </td>
                      <td>{{ item.action }}</td>
                      <td class="reports-equipment-table__action-cell">
                        <button
                          type="button"
                          class="reports-row-icon-button"
                          :aria-label="`View demand basis for ${item.name}`"
                          title="View demand basis"
                          @click="openPreparationDecisionModal(item)"
                        >
                          <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                            <circle cx="12" cy="12" r="3" />
                          </svg>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
                <div class="reports-equipment-pagination">
                  <button type="button" :disabled="possibleBorrowedCurrentPage === 1" @click="possibleBorrowedCurrentPage -= 1">Previous</button>
                  <span>Page {{ possibleBorrowedCurrentPage }} of {{ possibleBorrowedTotalPages }}</span>
                  <button type="button" :disabled="possibleBorrowedCurrentPage === possibleBorrowedTotalPages" @click="possibleBorrowedCurrentPage += 1">Next</button>
                </div>
              </div>
            </div>
          </section>

          <section class="reports-panel reports-summary-panel">
            <h2>System Summary</h2>
            <p v-if="isSummarySectionLoading" class="reports-section-loading">Loading system summary...</p>
            <p v-if="summarySectionError" class="reports-inline-message is-error">{{ summarySectionError }}</p>
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

        <button
          class="reports-trigger-button reports-trigger-button--bottom"
          type="button"
          :disabled="isReportsLoading"
          @click="isScenarioModalOpen = true"
        >
          Run Scenario Test Analytics
        </button>
      </div>

      <div v-if="isModelSheetOpen" class="reports-sheet-backdrop" @click.self="closeModelSheet">
        <aside class="reports-model-sheet" aria-label="Analytics model manager">
          <div class="reports-model-sheet-header">
            <div>
              <p>Model Artifacts</p>
              <h3>Analytics Sets</h3>
            </div>
            <button type="button" class="reports-modal-close" @click="closeModelSheet">×</button>
          </div>

          <p class="reports-model-sheet-copy">
            Create, rename, mix, and switch the Analytics artifacts used by each analytics model.
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
              {{ isCreatingModelSet ? 'Creating Analytics...' : 'Create Test Analytics' }}
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

          <div v-if="isModelArtifactsLoading" class="reports-inline-message">Loading Analytics sets...</div>
          <div v-else-if="modelArtifactSets.length === 0" class="reports-inline-message">No Analytics sets are available yet.</div>
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
                  <span>{{ modelSet.active ? 'Active for analytics' : 'Available Analytics set' }}</span>
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
                  :placeholder="modelSet.setName === 'default' ? 'Default cannot be renamed' : 'Rename Analytics set'"
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
                  {{ modelSet.active ? 'Using' : 'Use This Analytics' }}
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
            <h3>Run Scenario Test Analytics</h3>
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
              :disabled="isTriggeringAnalytics || isReportsLoading"
              @click="handleTriggerAnalyticsRun"
            >
              {{ isTriggeringAnalytics ? 'Running...' : 'Run Scenario Test Analytics' }}
            </button>
          </div>
        </div>
      </div>
    </section>
    <EquipmentPreparationDecisionModal
      :decision-item="selectedPreparationDecision"
      @close="closePreparationDecisionModal"
    />
    <DataRequestStatusFloater :items="reportsAnalyticsStatusItems" />
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import DataRequestStatusFloater from '@/shared/components/DataRequestStatusFloater.vue';
import EquipmentPreparationDecisionModal from './components/EquipmentPreparationDecisionModal.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/ReportsAnalytics.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import adminAnalyticsApi from '@/modules/dashboard/services/adminAnalyticsApi.js';
import {
  createEmptyForecastReport,
  createEmptyEquipmentTrendPagination,
  createEmptyRiskReport,
  createEmptySummaryReport,
  createEmptyUtilizationReport,
  normalizeAllocationSectionResponse,
  normalizeForecastSectionResponse,
  normalizeReadinessSectionResponse,
  normalizeStoredAnalyticsResponse,
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

const REPORTS_ACCORDION_PREFERENCE_KEY_PREFIX = 'techreserve_reports_analytics_accordions';
const REPORTS_ANALYTICS_SECTION_CACHE_PREFIX = 'techreserve_reports_analytics_section_v1';
const REPORTS_ANALYTICS_SECTION_CACHE_TTL_MS = 5 * 60 * 1000;
const REPORTS_ANALYTICS_SECTION_KEYS = Object.freeze([
  'forecast',
  'readiness',
  'optimization',
  'utilization',
  'equipmentTrends',
  'summary',
]);
const DEFAULT_EQUIPMENT_TREND_PAGINATION = Object.freeze(createEmptyEquipmentTrendPagination());
const DEFAULT_REPORTS_ACCORDION_PREFERENCES = Object.freeze({
  forecastValidation: false,
  riskValidation: false,
});

const authStore = useAuthenticationStore();
const selectedRangeKey = ref('30d');
const isReportsLoading = ref(true);
const sectionLoadingState = ref({
  forecast: false,
  readiness: false,
  optimization: false,
  utilization: false,
  equipmentTrends: false,
  summary: false,
});
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
const equipmentTrendPageSize = 5;
const topEquipmentCurrentPage = ref(1);
const possibleBorrowedCurrentPage = ref(1);
const selectedPreparationDecision = ref(null);
const reportsError = ref('');
const sectionErrors = ref({
  forecast: '',
  readiness: '',
  optimization: '',
  utilization: '',
  equipmentTrends: '',
  summary: '',
});
const sectionCacheExpiresAt = ref({
  forecast: null,
  readiness: null,
  optimization: null,
  utilization: null,
  equipmentTrends: null,
  summary: null,
});
const analyticsToastMessage = ref('');
const analyticsRunStatus = ref('');
const analyticsRunStatusType = ref('info');
const modelArtifactMessage = ref('');
const modelArtifactMessageType = ref('info');
const pdfError = ref('');
const reportSurfaceRef = ref(null);
const forecastChartRef = ref(null);
const riskChartRef = ref(null);
const utilizationChartRef = ref(null);
const chartRenderer = createReportsAnalyticsChartRenderer();
let reportsLoadSequence = 0;
let equipmentTrendsLoadSequence = 0;
let isResettingEquipmentTrendPages = false;
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
const reportsAccordionPreferenceStorageKey = computed(() => (
  `${REPORTS_ACCORDION_PREFERENCE_KEY_PREFIX}:${resolveReportsPreferenceUserKey(authStore.activeAccount)}`
));
const reportsAccordionPreferences = ref(loadReportsAccordionPreferences());

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
    modelLabel: 'SARIMA',
    description: 'Projects upcoming equipment demand from live reservation activity in the selected period.',
    tone: 'blue',
  },
  {
    number: 2,
    title: 'Readiness Risk Bands',
    subtitle: 'Inventory Monitoring',
    modelLabel: 'Random Forest',
    description: 'Highlights equipment pressure using stock levels, overdue linkage, and recent usage frequency.',
    tone: 'green',
  },
  {
    number: 3,
    title: 'Allocation Efficiency',
    subtitle: 'Operational Optimization',
    modelLabel: 'B-ILP',
    description: 'Tracks fulfillment, utilization, and pending-request pressure for current admin operations.',
    tone: 'orange',
  },
  {
    number: 4,
    title: 'Utilization Overview',
    subtitle: 'Equipment Usage Modeling',
    modelLabel: 'Random Forest',
    description: 'Models category-level equipment utilization from reservation usage and inventory pressure.',
    tone: 'green',
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
const forecastHistorySeries = computed(() => (forecastData.value.historySeries || []).map((item) => ({
  ...item,
  label: formatShortDate(item.date || item.label),
})));
const forecastDisplaySeries = computed(() => buildForecastDisplaySeries(
  forecastSeries.value,
  forecastProjectionSeries.value,
  forecastHistorySeries.value,
));
const forecastMidpointSeries = computed(() => forecastDisplaySeries.value.labels.map((_, index) => {
  const actualValue = forecastDisplaySeries.value.actualValues[index];
  const forecastValue = forecastDisplaySeries.value.forecastValues[index];
  const hasActual = actualValue !== null && actualValue !== undefined;
  const hasForecast = forecastValue !== null && forecastValue !== undefined;

  if (!hasActual || !hasForecast) {
    return null;
  }

  const actualNumber = Number(actualValue || 0);
  const forecastNumber = Number(forecastValue || 0);
  return roundForecastValue((actualNumber + forecastNumber) / 2);
}));
const peakDateLabel = computed(() => formatLongDate(forecastData.value.peakDate));
const forecastNarrative = computed(() => buildForecastNarrative(forecastData.value, forecastSeries.value, forecastProjectionSeries.value));
const forecastAccuracy = computed(() => forecastData.value?.accuracyMetrics || {});
const forecastAccuracyDateLabel = computed(() => {
  const startDate = formatShortDate(forecastAccuracy.value.evaluationStartDate);
  const endDate = formatShortDate(forecastAccuracy.value.evaluationEndDate);
  if (!startDate || !endDate) {
    return 'No validation date';
  }
  return startDate === endDate ? startDate : `${startDate} to ${endDate}`;
});
const forecastAccuracyEvaluationNote = computed(() => {
  const evaluatedPeriods = Number(forecastAccuracy.value.evaluatedPeriods || 0);
  const zeroDemandExcluded = Number(forecastAccuracy.value.zeroDemandExcluded || 0);
  if (evaluatedPeriods <= 0) {
    return `No non-zero actual demand in ${forecastAccuracyDateLabel.value}; ${formatMetricNumber(zeroDemandExcluded, 0)} zero-demand dates excluded.`;
  }
  return `${formatMetricNumber(evaluatedPeriods, 0)} demand dates evaluated in ${forecastAccuracyDateLabel.value}; ${formatMetricNumber(zeroDemandExcluded, 0)} zero-demand dates excluded.`;
});
const forecastValidationCards = computed(() => {
  const mape = forecastAccuracy.value.sarimaMape;
  const status = forecastAccuracy.value.accuracyStatus || 'insufficient_data';
  const improvement = Number(forecastAccuracy.value.forecastImprovementPercent);
  return [
    {
      label: 'SARIMA MAPE',
      value: formatOptionalPercent(mape, 2, false, 'Not computable'),
      note: `${status === 'good' ? 'Good: average forecast error is within the 20% target.' : status === 'needs_review' ? 'Needs review: average forecast error is above the 20% target.' : 'Not computable because MAPE needs non-zero actual demand.'} ${forecastAccuracyEvaluationNote.value}`,
      good: status === 'good',
      bad: status === 'needs_review',
    },
    {
      label: 'Naive MAPE',
      value: formatOptionalPercent(forecastAccuracy.value.naiveMape, 2, false, 'Not computable'),
      note: `Simple benchmark for ${forecastAccuracyDateLabel.value}: predicts each day using the previous actual demand. SARIMA should beat this to justify the model.`,
      good: false,
      bad: false,
    },
    {
      label: 'Seasonal Naive MAPE',
      value: formatOptionalPercent(forecastAccuracy.value.seasonalNaiveMape, 2, false, 'Not computable'),
      note: `Weekly benchmark for ${forecastAccuracyDateLabel.value}: predicts each day from the same weekday last week. Best comparison when demand has weekly patterns.`,
      good: false,
      bad: false,
    },
    {
      label: 'SARIMA Improvement',
      value: formatOptionalPercent(forecastAccuracy.value.forecastImprovementPercent, 2, true, 'Not computable'),
      note: `${Number.isFinite(improvement) && improvement > 0 ? 'SARIMA is outperforming the benchmark.' : Number.isFinite(improvement) && improvement < 0 ? 'SARIMA is worse than the benchmark for this range.' : 'No improvement score because one of the MAPE values is not computable.'} Compared with ${formatBenchmarkMethod(forecastAccuracy.value.benchmarkMethod)} over ${forecastAccuracyDateLabel.value}.`,
      good: Number.isFinite(improvement) && improvement > 0,
      bad: Number.isFinite(improvement) && improvement < 0,
    },
  ];
});
const riskBands = computed(() => riskReport.value?.bands || []);
const topRiskFactors = computed(() => riskReport.value?.topRiskFactors || []);
const highRiskEquipment = computed(() => riskReport.value?.highRiskEquipment || []);
const safeRateLabel = computed(() => `${formatMetricNumber(riskReport.value?.safeRate || 0, 0)}%`);
const highRiskTooltip = computed(() => resolveHighRiskTooltip());
const riskNarrative = computed(() => buildRiskNarrative(riskBands.value));
const randomForestMetrics = computed(() => riskReport.value?.modelMetrics || {});
const randomForestMetricNote = computed(() => (
  Object.keys(randomForestMetrics.value).length > 0
    ? 'From the active Random Forest validation split.'
    : 'Train or refresh Analytics Models to generate validation metrics.'
));
const randomForestMetricCards = computed(() => {
  if (Object.keys(randomForestMetrics.value).length === 0) {
    return [
      { label: 'Accuracy', value: 'No trained metrics', note: randomForestMetricNote.value },
      { label: 'Precision', value: 'No trained metrics', note: randomForestMetricNote.value },
      { label: 'Recall', value: 'No trained metrics', note: randomForestMetricNote.value },
      { label: 'F1 Score', value: 'No trained metrics', note: randomForestMetricNote.value },
    ];
  }

  return [
    {
      label: 'Accuracy',
      value: formatOptionalPercent(metricRatioToPercent(randomForestMetrics.value.accuracy), 1, false, 'No trained metrics'),
      note: 'Overall correctness: share of validation items where the predicted risk band matched the actual band.',
    },
    {
      label: 'Precision',
      value: formatOptionalPercent(metricRatioToPercent(randomForestMetrics.value.precision), 1, false, 'No trained metrics'),
      note: 'Trust in flagged bands: higher means fewer items are incorrectly placed into a risk band.',
    },
    {
      label: 'Recall',
      value: formatOptionalPercent(metricRatioToPercent(randomForestMetrics.value.recall), 1, false, 'No trained metrics'),
      note: 'Coverage of real risk: higher means the model catches more equipment that truly belongs in each band.',
    },
    {
      label: 'F1 Score',
      value: formatOptionalPercent(metricRatioToPercent(randomForestMetrics.value.f1), 1, false, 'No trained metrics'),
      note: 'Balanced score between precision and recall; useful when both false alarms and missed risks matter.',
    },
  ];
});
const confusionMatrixLabels = computed(() => Array.isArray(randomForestMetrics.value.labels) ? randomForestMetrics.value.labels : []);
const confusionMatrixRows = computed(() => {
  const matrix = Array.isArray(randomForestMetrics.value.confusionMatrix) ? randomForestMetrics.value.confusionMatrix : [];
  return matrix.map((values, rowIndex) => ({
    label: confusionMatrixLabels.value[rowIndex] || `Class ${rowIndex + 1}`,
    values: (Array.isArray(values) ? values : []).map((value, cellIndex) => ({
      label: confusionMatrixLabels.value[cellIndex] || `Class ${cellIndex + 1}`,
      value: formatMetricNumber(value || 0, 0),
    })),
  }));
});
const confusionMatrixExplanation = computed(() => {
  const matrix = Array.isArray(randomForestMetrics.value.confusionMatrix) ? randomForestMetrics.value.confusionMatrix : [];
  if (matrix.length === 0) {
    return 'Train or refresh Analytics Models to generate the confusion matrix.';
  }

  let correctCount = 0;
  let totalCount = 0;
  matrix.forEach((row, rowIndex) => {
    (Array.isArray(row) ? row : []).forEach((value, columnIndex) => {
      const count = Number(value || 0);
      totalCount += count;
      if (rowIndex === columnIndex) {
        correctCount += count;
      }
    });
  });
  const mistakeCount = Math.max(0, totalCount - correctCount);
  return `${formatMetricNumber(correctCount, 0)} of ${formatMetricNumber(totalCount, 0)} validation items landed on the diagonal, meaning the actual and predicted risk bands matched. ${formatMetricNumber(mistakeCount, 0)} items were off-diagonal and should be reviewed as model mistakes.`;
});
const riskProbabilityCards = computed(() => {
  const summary = riskReport.value?.riskProbabilitySummary || {};
  if (Object.keys(summary).length === 0) {
    return [
      { label: 'Average At-Risk Probability', value: 'Not calculated', note: 'Refresh analytics to calculate risk probability.' },
      { label: 'Highest At-Risk Probability', value: 'Not calculated', note: 'Refresh analytics to calculate risk probability.' },
      { label: 'High Probability Items', value: '0', note: 'Refresh analytics to calculate risk probability.' },
    ];
  }
  return [
    {
      label: 'Average At-Risk Probability',
      value: formatOptionalPercent(summary.averageRiskProbability, 1, false, 'Not calculated'),
      note: 'Mean probability that equipment belongs to Medium Risk or High Risk. Lower is healthier overall.',
    },
    {
      label: 'Highest At-Risk Probability',
      value: formatOptionalPercent(summary.maxRiskProbability, 1, false, 'Not calculated'),
      note: 'Worst single equipment probability. Use this to identify the item most likely to need attention.',
    },
    {
      label: 'High Probability Items',
      value: formatMetricNumber(summary.highProbabilityCount || 0, 0),
      note: 'Count of equipment at or above 65% at-risk probability. These are priority review candidates.',
    },
  ];
});
const optimizationMetrics = computed(() => optimizationReport.value || []);
const isForecastValidationOpen = computed(() => reportsAccordionPreferences.value.forecastValidation === true);
const isRiskValidationOpen = computed(() => reportsAccordionPreferences.value.riskValidation === true);
const isForecastSectionLoading = computed(() => sectionLoadingState.value.forecast);
const isRiskSectionLoading = computed(() => sectionLoadingState.value.readiness);
const isOptimizationSectionLoading = computed(() => sectionLoadingState.value.optimization);
const isUtilizationSectionLoading = computed(() => sectionLoadingState.value.utilization);
const isEquipmentTrendsSectionLoading = computed(() => sectionLoadingState.value.equipmentTrends);
const isSummarySectionLoading = computed(() => sectionLoadingState.value.summary);
const forecastSectionError = computed(() => sectionErrors.value.forecast);
const riskSectionError = computed(() => sectionErrors.value.readiness);
const optimizationSectionError = computed(() => sectionErrors.value.optimization);
const utilizationSectionError = computed(() => sectionErrors.value.utilization);
const equipmentTrendsSectionError = computed(() => sectionErrors.value.equipmentTrends);
const summarySectionError = computed(() => sectionErrors.value.summary);
const utilizationItems = computed(() => utilizationReport.value.items || []);
const utilizationComparisonItems = computed(() => utilizationReport.value.comparisonItems || []);
const equipmentTrendPagination = computed(() => utilizationReport.value.equipmentTrendPagination || DEFAULT_EQUIPMENT_TREND_PAGINATION);
const topEquipmentPagination = computed(() => equipmentTrendPagination.value.topEquipment || DEFAULT_EQUIPMENT_TREND_PAGINATION.topEquipment);
const preparationDecisionPagination = computed(() => (
  equipmentTrendPagination.value.preparationDecisions || DEFAULT_EQUIPMENT_TREND_PAGINATION.preparationDecisions
));
const topEquipment = computed(() => normalizeEquipmentTrendItems(utilizationReport.value.topEquipment || []));
const topFrequentlyUsedEquipment = computed(() => topEquipment.value);
const topEquipmentTotalPages = computed(() => Math.max(1, Number(topEquipmentPagination.value.totalPages || 1)));
const paginatedTopFrequentlyUsedEquipment = computed(() => topFrequentlyUsedEquipment.value);
const possibleBorrowedEquipment = computed(() => {
  const candidates = normalizeEquipmentTrendItems(utilizationReport.value.possibleBorrowedEquipment || []);
  return candidates.filter((item) => item?.name).map(buildPreparationDecisionItem);
});
const possibleBorrowedTotalPages = computed(() => Math.max(1, Number(preparationDecisionPagination.value.totalPages || 1)));
const paginatedPossibleBorrowedEquipment = computed(() => possibleBorrowedEquipment.value);
const optimizationNarrative = computed(() => buildOptimizationNarrative(optimizationMetrics.value, summaryReport.value || {}));
const utilizationNarrative = computed(() => buildUtilizationNarrative(utilizationItems.value));
const reportGeneratedAt = computed(() => summaryReport.value?.generatedAt || 'Not generated');
const summaryItems = computed(() => [
  { label: 'Total Equipment', value: formatMetricNumber(summaryReport.value?.totalEquipment || 0, 0) },
  { label: 'Active Reservations', value: formatMetricNumber(summaryReport.value?.activeReservations || 0, 0) },
  { label: 'Pending Requests', value: formatMetricNumber(summaryReport.value?.pendingRequests || 0, 0) },
  { label: 'Completed This Period', value: formatMetricNumber(summaryReport.value?.completedThisPeriod || 0, 0) },
  { label: 'Generated At', value: reportGeneratedAt.value },
]);
const summaryStatusRecord = computed(() => (
  summaryReport.value?.generatedAt && summaryReport.value.generatedAt !== 'Not generated'
    ? summaryReport.value
    : null
));
const modelArtifactSets = computed(() => Array.isArray(modelArtifacts.value?.sets) ? modelArtifacts.value.sets : []);
const reportsAnalyticsStatusItems = computed(() => [
  {
    key: 'forecast',
    label: 'Demand forecast',
    state: resolveReportSectionState(isForecastSectionLoading.value, forecastSectionError.value, forecastSeries.value),
    expiresAt: sectionCacheExpiresAt.value.forecast,
  },
  {
    key: 'readiness',
    label: 'Risk readiness',
    state: resolveReportSectionState(isRiskSectionLoading.value, riskSectionError.value, riskBands.value),
    expiresAt: sectionCacheExpiresAt.value.readiness,
  },
  {
    key: 'optimization',
    label: 'B-ILP optimization',
    state: resolveReportSectionState(isOptimizationSectionLoading.value, optimizationSectionError.value, optimizationMetrics.value),
    expiresAt: sectionCacheExpiresAt.value.optimization,
  },
  {
    key: 'utilization',
    label: 'Equipment utilization',
    state: resolveReportSectionState(isUtilizationSectionLoading.value, utilizationSectionError.value, utilizationItems.value),
    expiresAt: sectionCacheExpiresAt.value.utilization,
  },
  {
    key: 'equipment-trends',
    label: 'Equipment trends',
    state: resolveReportSectionState(isEquipmentTrendsSectionLoading.value, equipmentTrendsSectionError.value, topFrequentlyUsedEquipment.value),
    expiresAt: sectionCacheExpiresAt.value.equipmentTrends,
  },
  {
    key: 'system-summary',
    label: 'System summary',
    state: resolveReportSectionState(isSummarySectionLoading.value, summarySectionError.value, summaryStatusRecord.value),
    expiresAt: sectionCacheExpiresAt.value.summary,
  },
  {
    key: 'models',
    label: 'Analytics models',
    state: resolveReportSectionState(isModelArtifactsLoading.value, modelArtifactMessageType.value === 'error' ? modelArtifactMessage.value : '', modelArtifactSets.value),
  },
]);

watch(reportsAccordionPreferenceStorageKey, () => {
  reportsAccordionPreferences.value = loadReportsAccordionPreferences();
});

onMounted(() => {
  loadReportsAnalytics();
});

onBeforeUnmount(() => {
  destroyCharts();
});

watch(selectedRangeKey, () => {
  resetEquipmentTrendPages();
  loadReportsAnalytics();
});

watch(isUtilizationSectionLoading, (isRefreshing) => {
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

watch(topEquipmentTotalPages, (pageCount) => {
  if (topEquipmentCurrentPage.value > pageCount) {
    topEquipmentCurrentPage.value = pageCount;
  }
});

watch(possibleBorrowedTotalPages, (pageCount) => {
  if (possibleBorrowedCurrentPage.value > pageCount) {
    possibleBorrowedCurrentPage.value = pageCount;
  }
});

watch([topEquipmentCurrentPage, possibleBorrowedCurrentPage], () => {
  if (isResettingEquipmentTrendPages || isReportsLoading.value) {
    return;
  }

  loadEquipmentTrendsPage();
});

async function loadReportsAnalytics(options = {}) {
  const loadSequence = ++reportsLoadSequence;
  isReportsLoading.value = true;
  setAllSectionLoading(true);
  clearSectionErrors();
  reportsError.value = '';
  pdfError.value = '';
  reportsSourceLabel.value = `Loading FastAPI analytics for ${activeRangeLabel.value}...`;
  const requestedRange = activeRange.value;
  const forceRefresh = options?.forceRefresh === true;

  const sectionRequests = [
    loadAnalyticsSection(loadSequence, 'forecast', requestedRange, { forceRefresh }),
    loadAnalyticsSection(loadSequence, 'readiness', requestedRange, { forceRefresh }),
    loadAnalyticsSection(loadSequence, 'optimization', requestedRange, { forceRefresh }),
    loadAnalyticsSection(loadSequence, 'utilization', requestedRange, { forceRefresh }),
    loadAnalyticsSection(loadSequence, 'equipment-trends', requestedRange, { forceRefresh }),
    loadAnalyticsSection(loadSequence, 'summary', requestedRange, { forceRefresh }),
  ];

  await Promise.allSettled(sectionRequests);

  if (loadSequence !== reportsLoadSequence) {
    return;
  }

  isReportsLoading.value = false;
  if (Object.values(sectionErrors.value).some(Boolean)) {
    reportsSourceLabel.value = 'Some analytics sections could not refresh; showing only cached or completed data where available.';
  } else {
    reportsSourceLabel.value = `Using FastAPI analytics for ${activeRangeLabel.value}.`;
  }
}

async function loadEquipmentTrendsPage(options = {}) {
  sectionLoadingState.value = {
    ...sectionLoadingState.value,
    equipmentTrends: true,
  };
  sectionErrors.value = {
    ...sectionErrors.value,
    equipmentTrends: '',
  };

  await loadAnalyticsSection(reportsLoadSequence, 'equipment-trends', activeRange.value, options);
}

async function loadAnalyticsSection(loadSequence, section, range, options = {}) {
  const sectionKey = normalizeReportsSectionKey(section);
  const requestOptions = resolveReportsSectionRequestOptions(sectionKey);
  const equipmentTrendsRequestSequence = sectionKey === 'equipmentTrends'
    ? ++equipmentTrendsLoadSequence
    : 0;

  if (options?.forceRefresh === true) {
    clearReportsSectionCache(sectionKey, range, requestOptions);
    sectionCacheExpiresAt.value = {
      ...sectionCacheExpiresAt.value,
      [sectionKey]: null,
    };
  } else if (!applyCachedAnalyticsSection(sectionKey, range, requestOptions)) {
    clearAnalyticsSection(sectionKey);
  }

  try {
    const analyticsResponse = await adminAnalyticsApi.getAnalyticsRangeSectionResults(section, range, requestOptions);
    if (loadSequence !== reportsLoadSequence) {
      return;
    }
    if (sectionKey === 'equipmentTrends' && equipmentTrendsRequestSequence !== equipmentTrendsLoadSequence) {
      return;
    }

    applyAnalyticsSection(sectionKey, analyticsResponse);
    const expiresAt = writeReportsSectionCache(sectionKey, range, analyticsResponse, requestOptions);
    sectionCacheExpiresAt.value = {
      ...sectionCacheExpiresAt.value,
      [sectionKey]: expiresAt,
    };
  } catch (error) {
    if (loadSequence !== reportsLoadSequence) {
      return;
    }
    if (sectionKey === 'equipmentTrends' && equipmentTrendsRequestSequence !== equipmentTrendsLoadSequence) {
      return;
    }

    sectionErrors.value = {
      ...sectionErrors.value,
      [sectionKey]: resolveReportsError(error),
    };
  } finally {
    if (
      loadSequence === reportsLoadSequence
      && (sectionKey !== 'equipmentTrends' || equipmentTrendsRequestSequence === equipmentTrendsLoadSequence)
    ) {
      sectionLoadingState.value = {
        ...sectionLoadingState.value,
        [sectionKey]: false,
      };
    }
  }
}

function applyAnalyticsSection(section, analyticsResponse) {
  if (section === 'forecast') {
    forecastReport.value = normalizeForecastSectionResponse(analyticsResponse);
    return;
  }

  if (section === 'readiness') {
    riskReport.value = normalizeReadinessSectionResponse(analyticsResponse);
    return;
  }

  if (['optimization', 'utilization', 'equipmentTrends', 'summary'].includes(section)) {
    const allocationAnalytics = normalizeAllocationSectionResponse(analyticsResponse);
    if (section === 'optimization') {
      optimizationReport.value = allocationAnalytics.optimizationMetrics;
      return;
    }

    if (section === 'utilization') {
      utilizationReport.value = {
        ...utilizationReport.value,
        items: allocationAnalytics.utilizationByCategory,
        comparisonItems: allocationAnalytics.utilizationComparisonByCategory,
      };
      return;
    }

    if (section === 'equipmentTrends') {
      utilizationReport.value = {
        ...utilizationReport.value,
        topEquipment: allocationAnalytics.topEquipment,
        possibleBorrowedEquipment: allocationAnalytics.possibleBorrowedEquipment,
        equipmentTrendPagination: allocationAnalytics.equipmentTrendPagination,
      };
      syncEquipmentTrendPageRefs(allocationAnalytics.equipmentTrendPagination);
      return;
    }

    if (section === 'summary') {
      summaryReport.value = allocationAnalytics.summary;
      return;
    }
  }
}

function clearAnalyticsSection(section) {
  if (section === 'forecast') {
    forecastReport.value = createEmptyForecastReport();
    return;
  }

  if (section === 'readiness') {
    riskReport.value = createEmptyRiskReport();
    return;
  }

  if (section === 'optimization') {
    optimizationReport.value = [];
    return;
  }

  if (section === 'utilization') {
    utilizationReport.value = {
      ...utilizationReport.value,
      items: [],
      comparisonItems: [],
    };
    return;
  }

  if (section === 'equipmentTrends') {
    utilizationReport.value = {
      ...utilizationReport.value,
      topEquipment: [],
      possibleBorrowedEquipment: [],
      equipmentTrendPagination: createEmptyEquipmentTrendPagination(),
    };
    return;
  }

  if (section === 'summary') {
    summaryReport.value = createEmptySummaryReport();
  }
}

function setAllSectionLoading(isLoading) {
  sectionLoadingState.value = {
    forecast: isLoading,
    readiness: isLoading,
    optimization: isLoading,
    utilization: isLoading,
    equipmentTrends: isLoading,
    summary: isLoading,
  };
}

function normalizeReportsSectionKey(section) {
  const normalized = String(section || '').trim().toLowerCase().replace(/-/g, '_');
  if (normalized === 'equipment_trends') return 'equipmentTrends';
  if (normalized === 'readiness' || normalized === 'risk' || normalized === 'random_forest') return 'readiness';
  if (normalized === 'optimization' || normalized === 'bilp' || normalized === 'binary_linear_programming') return 'optimization';
  if (normalized === 'utilization') return 'utilization';
  if (normalized === 'summary') return 'summary';
  return 'forecast';
}

function resolveReportsSectionRequestOptions(sectionKey) {
  if (sectionKey !== 'equipmentTrends') {
    return {};
  }

  return {
    topEquipmentPage: topEquipmentCurrentPage.value,
    preparationDecisionPage: possibleBorrowedCurrentPage.value,
    equipmentTrendPageSize,
  };
}

function syncEquipmentTrendPageRefs(pagination) {
  const topPage = Number(pagination?.topEquipment?.page || topEquipmentCurrentPage.value);
  const preparationPage = Number(pagination?.preparationDecisions?.page || possibleBorrowedCurrentPage.value);
  isResettingEquipmentTrendPages = true;

  if (Number.isFinite(topPage) && topPage > 0) {
    topEquipmentCurrentPage.value = topPage;
  }

  if (Number.isFinite(preparationPage) && preparationPage > 0) {
    possibleBorrowedCurrentPage.value = preparationPage;
  }

  nextTick(() => {
    isResettingEquipmentTrendPages = false;
  });
}

function resetEquipmentTrendPages() {
  isResettingEquipmentTrendPages = true;
  topEquipmentCurrentPage.value = 1;
  possibleBorrowedCurrentPage.value = 1;
  nextTick(() => {
    isResettingEquipmentTrendPages = false;
  });
}

function applyCachedAnalyticsSection(sectionKey, range, cacheOptions = {}) {
  const cachedSection = readReportsSectionCache(sectionKey, range, cacheOptions);
  if (!cachedSection) {
    sectionCacheExpiresAt.value = {
      ...sectionCacheExpiresAt.value,
      [sectionKey]: null,
    };
    return false;
  }

  try {
    applyAnalyticsSection(sectionKey, cachedSection.payload);
    sectionCacheExpiresAt.value = {
      ...sectionCacheExpiresAt.value,
      [sectionKey]: cachedSection.expiresAt,
    };
    return true;
  } catch (error) {
    clearReportsSectionCache(sectionKey, range, cacheOptions);
    sectionCacheExpiresAt.value = {
      ...sectionCacheExpiresAt.value,
      [sectionKey]: null,
    };
    return false;
  }
}

function readReportsSectionCache(sectionKey, range, cacheOptions = {}) {
  try {
    const storedValue = sessionStorage.getItem(resolveReportsSectionCacheKey(sectionKey, range, cacheOptions));
    if (!storedValue) {
      return null;
    }

    const parsedValue = JSON.parse(storedValue);
    const expiresAt = Number(parsedValue?.expiresAt || 0);
    if (!parsedValue?.payload || !Number.isFinite(expiresAt) || expiresAt <= Date.now()) {
      clearReportsSectionCache(sectionKey, range, cacheOptions);
      return null;
    }

    return {
      payload: parsedValue.payload,
      expiresAt,
    };
  } catch (error) {
    clearReportsSectionCache(sectionKey, range, cacheOptions);
    return null;
  }
}

function writeReportsSectionCache(sectionKey, range, payload, cacheOptions = {}) {
  const expiresAt = Date.now() + REPORTS_ANALYTICS_SECTION_CACHE_TTL_MS;
  try {
    sessionStorage.setItem(resolveReportsSectionCacheKey(sectionKey, range, cacheOptions), JSON.stringify({
      expiresAt,
      payload,
    }));
  } catch (error) {
    // Session storage can be unavailable in restricted browser contexts.
  }

  return expiresAt;
}

function clearReportsSectionCache(sectionKey, range, cacheOptions = {}) {
  try {
    sessionStorage.removeItem(resolveReportsSectionCacheKey(sectionKey, range, cacheOptions));
  } catch (error) {
    // Session storage can be unavailable in restricted browser contexts.
  }
}

function clearReportsSectionCacheForRange(range) {
  const cachePrefix = resolveReportsSectionCachePrefix(range);
  try {
    Object.keys(sessionStorage)
      .filter((cacheKey) => cacheKey.startsWith(cachePrefix))
      .forEach((cacheKey) => sessionStorage.removeItem(cacheKey));
  } catch (error) {
    REPORTS_ANALYTICS_SECTION_KEYS.forEach((sectionKey) => {
      clearReportsSectionCache(sectionKey, range);
    });
  }
  clearSectionCacheExpiresAt();
}

function clearSectionCacheExpiresAt() {
  sectionCacheExpiresAt.value = Object.fromEntries(
    REPORTS_ANALYTICS_SECTION_KEYS.map((sectionKey) => [sectionKey, null]),
  );
}

function resolveReportsSectionCachePrefix(range) {
  const userKey = resolveReportsPreferenceUserKey(authStore.activeAccount);
  return [
    REPORTS_ANALYTICS_SECTION_CACHE_PREFIX,
    userKey,
    range?.startDateIso || '',
    range?.endDateIso || '',
    range?.days || '',
  ].join(':');
}

function resolveReportsSectionCacheKey(sectionKey, range, cacheOptions = {}) {
  const optionKey = sectionKey === 'equipmentTrends'
    ? [
      `top-${cacheOptions.topEquipmentPage || 1}`,
      `prep-${cacheOptions.preparationDecisionPage || 1}`,
      `size-${cacheOptions.equipmentTrendPageSize || equipmentTrendPageSize}`,
    ].join(':')
    : 'default';

  return [
    resolveReportsSectionCachePrefix(range),
    sectionKey,
    optionKey,
  ].join(':');
}

function resolveReportSectionState(isLoading, errorMessage, records) {
  const hasData = Array.isArray(records)
    ? records.length > 0
    : Boolean(records && Object.keys(records).length > 0);

  if (errorMessage && !hasData) return 'error';
  if (isLoading && hasData) return 'cached-loading';
  if (isLoading) return 'loading';
  if (errorMessage) return 'cached';
  return hasData ? 'fresh' : 'idle';
}

function clearSectionErrors() {
  sectionErrors.value = {
    forecast: '',
    readiness: '',
    optimization: '',
    utilization: '',
    equipmentTrends: '',
    summary: '',
  };
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
    const analyticsResponse = await adminAnalyticsApi.triggerAnalyticsRun(
      selectedAnalyticsScenario.value,
      activeRange.value,
    );
    const storedAnalytics = normalizeStoredAnalyticsResponse(analyticsResponse);
    clearReportsSectionCacheForRange(activeRange.value);
    applyStoredAnalyticsSections(storedAnalytics);
    reportsSourceLabel.value = `Using FastAPI analytics for ${activeRangeLabel.value}.`;
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
  clearReportsSectionCacheForRange(activeRange.value);
  loadReportsAnalytics({ forceRefresh: true });
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
  modelArtifactMessage.value = 'Training a new Analytics set...';
  modelArtifactMessageType.value = 'info';

  try {
    const setName = `test-${new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19).toLowerCase()}`;
    const response = await adminAnalyticsApi.trainAnalyticsModels({ setName, activate: true });
    const trainingRun = response?.trainingRun || response || {};
    modelArtifactMessage.value = `Created and activated ${trainingRun.setName || setName}.`;
    modelArtifactMessageType.value = 'success';
    analyticsToastMessage.value = 'Analytics test set created and activated.';
    await loadModelArtifacts();
    await loadReportsAnalytics({ forceRefresh: true });
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
  modelArtifactMessage.value = 'Refreshing today analytics with the active Analytics set...';
  modelArtifactMessageType.value = 'info';
  reportsError.value = '';

  try {
    const analyticsResponse = await adminAnalyticsApi.refreshDailyAnalytics(activeRange.value);
    const storedAnalytics = normalizeStoredAnalyticsResponse(analyticsResponse);
    clearReportsSectionCacheForRange(activeRange.value);
    applyStoredAnalyticsSections(storedAnalytics);
    reportsSourceLabel.value = `Using refreshed FastAPI analytics for ${activeRangeLabel.value}.`;
    modelArtifactMessage.value = 'Today analytics refreshed.';
    modelArtifactMessageType.value = 'success';
    analyticsToastMessage.value = 'Today analytics refreshed with the active Analytics set.';
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
    analyticsToastMessage.value = `Active Analytics set switched to ${setName}.`;
    await loadModelArtifacts();
    await loadReportsAnalytics({ forceRefresh: true });
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
    analyticsToastMessage.value = `${formatArtifactLabel(artifact)} Analytics switched to ${setName}.`;
    await loadModelArtifacts();
    await loadReportsAnalytics({ forceRefresh: true });
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
    await loadReportsAnalytics({ forceRefresh: true });
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

  const shouldDelete = window.confirm(`Delete Analytics set "${setName}"?`);
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
    await loadReportsAnalytics({ forceRefresh: true });
  } catch (error) {
    modelArtifactMessage.value = resolveReportsError(error);
    modelArtifactMessageType.value = 'error';
  } finally {
    isDeletingModelSet.value = '';
  }
}

function applyStoredAnalyticsSections(storedAnalytics) {
  forecastReport.value = storedAnalytics.forecast;
  riskReport.value = storedAnalytics.riskDistribution;
  optimizationReport.value = storedAnalytics.optimizationMetrics;
  utilizationReport.value = {
    items: storedAnalytics.utilizationByCategory,
    comparisonItems: storedAnalytics.utilizationComparisonByCategory,
    topEquipment: storedAnalytics.topEquipment,
    possibleBorrowedEquipment: storedAnalytics.possibleBorrowedEquipment,
    equipmentTrendPagination: storedAnalytics.equipmentTrendPagination,
  };
  summaryReport.value = storedAnalytics.summary;
}

function normalizeEquipmentTrendItems(items) {
  if (!Array.isArray(items)) {
    return [];
  }

  return items
    .map((item) => ({
      name: item?.name || item?.equipment || item?.equipmentName || item?.equipment_name || '',
      count: Number(item?.count ?? item?.usageCount ?? item?.usage_count ?? item?.timesUsed ?? item?.times_used ?? 0),
      currentUsage: Number(item?.currentUsage ?? item?.current_usage ?? item?.count ?? 0),
      previousYearCount: Number(item?.previousYearCount ?? item?.previous_year_count ?? item?.historicalCount ?? item?.historical_count ?? 0),
      predictedDemand: Number(item?.predictedDemand ?? item?.predicted_demand ?? item?.forecastDemand ?? item?.forecast_demand ?? item?.count ?? 0),
      predictionGap: Number(item?.predictionGap ?? item?.prediction_gap ?? 0),
      totalQuantity: Number(item?.totalQuantity ?? item?.total_quantity ?? 0),
      score: Number(item?.score ?? item?.forecastScore ?? item?.forecast_score ?? 0),
      reason: item?.reason || item?.note || item?.why || '',
      decision: item?.decision || '',
      action: item?.action || '',
    }))
    .filter((item) => item.name);
}

function buildPreparationDecisionItem(item) {
  const currentCount = Number(item?.currentUsage ?? item?.count ?? 0);
  const previousYearCount = Number(item?.previousYearCount || extractSameDateUsageCount(item.reason) || 0);
  const predictedDemand = Number.isFinite(Number(item?.predictedDemand))
    ? Number(item.predictedDemand)
    : currentCount;
  const predictionGap = Number.isFinite(Number(item?.predictionGap))
    ? Number(item.predictionGap)
    : Math.max(0, predictedDemand - currentCount);
  const forecastPressure = predictedDemand > currentCount;
  const decision = item.decision || (forecastPressure ? 'Prepare for forecast' : 'Keep prepared');
  const action = item.action || (forecastPressure
    ? `Reserve a buffer for about ${formatMetricNumber(Math.max(1, Math.round(predictionGap)), 0)} forecasted uses.`
    : 'Monitor availability and avoid lending all units early.');
  const signal = `Predicted: ${formatMetricNumber(predictedDemand, 1)} | Current: ${formatMetricNumber(currentCount, 0)} | Past same dates: ${formatMetricNumber(previousYearCount, 0)}`;

  return {
    ...item,
    currentUsage: currentCount,
    previousYearCount,
    predictedDemand,
    predictionGap,
    totalQuantity: Number(item?.totalQuantity || 0),
    score: Number(item?.score || predictedDemand),
    signal,
    decision,
    action,
    reason: item.reason || (forecastPressure ? 'Forecasted demand is higher than current usage.' : 'Current demand is already active in this range.'),
    tone: forecastPressure ? 'urgent' : 'steady',
  };
}

function openPreparationDecisionModal(item) {
  selectedPreparationDecision.value = item;
}

function closePreparationDecisionModal() {
  selectedPreparationDecision.value = null;
}

function extractSameDateUsageCount(value) {
  const match = String(value || '').match(/Used\s+(\d+)\s+times/i);
  return match ? Number(match[1]) : 0;
}

function countExistingArtifacts(modelSet) {
  return (modelSet?.artifacts || []).filter((artifact) => artifact?.exists).length;
}

function formatArtifactLabel(artifactName) {
  switch (artifactName) {
    case 'demand_forecast.pkl':
      return 'Demand Forecasting - SARIMA';
    case 'readiness_random_forest.pkl':
      return 'Readiness Risk Detection - Random Forest';
    case 'allocation_optimizer.pkl':
      return 'Allocation Optimization - B-ILP';
    default:
      return artifactName || 'Analytics';
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

function handlePreferenceAccordionToggle(preferenceKey, event) {
  if (!Object.hasOwn(DEFAULT_REPORTS_ACCORDION_PREFERENCES, preferenceKey)) {
    return;
  }

  saveReportsAccordionPreference(preferenceKey, event?.target?.open === true);
}

function resolveReportsPreferenceUserKey(account) {
  const identifier = account?.accountIdentifier
    || account?.account_identifier
    || account?.clerkUserId
    || account?.clerk_user_id
    || account?.emailAddress
    || account?.email
    || 'guest';

  return String(identifier).trim().toLowerCase().replace(/[^a-z0-9_-]+/g, '-');
}

function loadReportsAccordionPreferences() {
  try {
    const storedValue = localStorage.getItem(reportsAccordionPreferenceStorageKey.value);
    const parsedValue = storedValue ? JSON.parse(storedValue) : {};
    return {
      ...DEFAULT_REPORTS_ACCORDION_PREFERENCES,
      ...(parsedValue && typeof parsedValue === 'object' ? parsedValue : {}),
    };
  } catch (error) {
    return { ...DEFAULT_REPORTS_ACCORDION_PREFERENCES };
  }
}

function saveReportsAccordionPreference(preferenceKey, isOpen) {
  const nextPreferences = {
    ...reportsAccordionPreferences.value,
    [preferenceKey]: isOpen,
  };
  reportsAccordionPreferences.value = nextPreferences;

  try {
    localStorage.setItem(reportsAccordionPreferenceStorageKey.value, JSON.stringify(nextPreferences));
  } catch (error) {
    // Local storage may be unavailable in private or restricted browser contexts.
  }
}

function resolveReportsError(error) {
  return error?.response?.data?.errorMessage
    || error?.message
    || 'Unable to load analytics data right now.';
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

function formatOptionalPercent(value, digits = 1, signed = false, emptyLabel = 'Not available') {
  if (value === null || value === undefined || value === '') {
    return emptyLabel;
  }
  const number = Number(value);
  if (!Number.isFinite(number)) {
    return emptyLabel;
  }
  const prefix = signed && number > 0 ? '+' : '';
  return `${prefix}${formatMetricNumber(number, digits)}%`;
}

function metricRatioToPercent(value) {
  if (value === null || value === undefined || value === '') {
    return null;
  }
  const number = Number(value);
  if (!Number.isFinite(number)) {
    return null;
  }
  return number <= 1 ? number * 100 : number;
}

function formatBenchmarkMethod(value) {
  switch (value) {
    case 'seasonal_naive':
      return 'seasonal naive';
    case 'naive':
      return 'naive';
    default:
      return 'the benchmark';
  }
}

function resolveMetricDirection(value) {
  const number = Number(value || 0);
  if (number > 0) return 'up';
  if (number < 0) return 'down';
  return 'flat';
}

function resolveMetricDirectionIcon(value) {
  switch (resolveMetricDirection(value)) {
    case 'up':
      return '↑';
    case 'down':
      return '↓';
    default:
      return '→';
  }
}

function resolveMetricDirectionLabel(value) {
  switch (resolveMetricDirection(value)) {
    case 'up':
      return 'Metric is trending up';
    case 'down':
      return 'Metric is trending down';
    default:
      return 'Metric is unchanged';
  }
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
