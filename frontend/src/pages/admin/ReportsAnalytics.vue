<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="reports-analytics-page">
      <header class="reports-analytics-header">
        <div>
          <p class="reports-analytics-kicker">Analytics Dashboard</p>
          <h1>Reports &amp; Analytics</h1>
        </div>

        <label class="reports-analytics-date-range">
          <span>Date Range:</span>
          <select>
            <option>May 1 - May 16, 2026</option>
            <option>Last 30 days</option>
            <option>This semester</option>
          </select>
        </label>
      </header>

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
            <h2>Demand Forecasting (SARIMA Model)</h2>
            <p>Forecasted equipment demand</p>
          </div>
        </div>

        <div class="reports-forecast-layout">
          <div class="reports-chart-card">
            <div class="reports-chart-legend">
              <span><i class="legend-dot legend-dot--actual"></i>Actual Demand</span>
              <span><i class="legend-dot legend-dot--forecast"></i>Forecasted Demand</span>
            </div>
            <svg class="reports-line-chart" viewBox="0 0 760 260" role="img" aria-label="Demand forecasting line chart">
              <g class="reports-grid-lines">
                <path d="M52 30H730M52 80H730M52 130H730M52 180H730M52 230H730" />
                <path d="M52 30V230M166 30V230M280 30V230M394 30V230M508 30V230M622 30V230M730 30V230" />
              </g>
              <g class="reports-axis-labels">
                <text x="18" y="235">0</text>
                <text x="12" y="184">100</text>
                <text x="12" y="134">200</text>
                <text x="12" y="84">300</text>
                <text x="12" y="34">400</text>
                <text x="48" y="252">May 1</text>
                <text x="155" y="252">May 4</text>
                <text x="270" y="252">May 7</text>
                <text x="382" y="252">May 10</text>
                <text x="496" y="252">May 13</text>
                <text x="610" y="252">May 16</text>
              </g>
              <polyline class="reports-line reports-line--actual" points="55,205 92,180 130,165 166,178 204,154 242,120 280,185 318,160 356,155 394,110 432,138 470,130 508,148" />
              <polyline class="reports-line reports-line--forecast" points="55,230 92,205 130,180 166,160 204,170 242,180 280,165 318,188 356,170 394,150 432,112 470,136 508,128 546,96 584,72 622,118 660,90 698,82 730,76" />
            </svg>
          </div>

          <aside class="reports-insights-card">
            <h3>Forecast Insights</h3>
            <dl>
              <div>
                <dt>Forecasted Peak</dt>
                <dd>May 20, 2026<br><strong>362 requests</strong></dd>
              </div>
              <div>
                <dt>Expected Growth</dt>
                <dd><strong class="positive">+18.6%</strong><br>from previous period</dd>
              </div>
              <div>
                <dt>Model Accuracy (MAPE)</dt>
                <dd><strong>12.45%</strong></dd>
              </div>
            </dl>
          </aside>
        </div>
      </section>

      <div class="reports-two-column">
        <section class="reports-panel">
          <h2>Readiness Risk Detection (Random Forest)</h2>
          <p>Risk level distribution</p>
          <div class="reports-risk-layout">
            <div class="reports-donut" aria-label="Risk level distribution">
              <span>72%</span>
            </div>
            <ul class="reports-risk-list">
              <li v-for="risk in riskLevels" :key="risk.label">
                <i :style="{ background: risk.color }"></i>
                <span>{{ risk.label }}</span>
                <strong>{{ risk.value }}</strong>
              </li>
            </ul>
            <div class="reports-top-risk-card">
              <h3>Top Risk Factors</h3>
              <ol>
                <li>High usage frequency</li>
                <li>Past delay incidents</li>
                <li>Preparation time</li>
                <li>Maintenance history</li>
              </ol>
            </div>
          </div>
        </section>

        <section class="reports-panel">
          <h2>Resource Allocation Optimization (BILP)</h2>
          <p>Optimization performance</p>
          <div class="reports-optimization-list">
            <article v-for="metric in optimizationMetrics" :key="metric.label">
              <span :class="`reports-metric-icon reports-metric-icon--${metric.tone}`">{{ metric.icon }}</span>
              <div>
                <strong>{{ metric.label }}</strong>
                <small>{{ metric.note }}</small>
              </div>
              <em :class="{ negative: metric.value.startsWith('-') }">{{ metric.value }}</em>
            </article>
          </div>
        </section>
      </div>

      <div class="reports-bottom-grid">
        <section class="reports-panel">
          <h2>Equipment Utilization Overview</h2>
          <div class="reports-bar-chart">
            <div v-for="item in utilizationItems" :key="item.label">
              <span>{{ item.value }}%</span>
              <i :style="{ height: `${item.value}%` }"></i>
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
              <tr v-for="item in topEquipment" :key="item.name">
                <td>{{ item.name }}</td>
                <td>{{ item.count }}</td>
                <td>{{ item.rate }}%</td>
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

      <div class="reports-actions">
        <button class="reports-generate-button" type="button">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
            <path d="M14 2v6h6" />
            <path d="M9 15h6" />
            <path d="M9 18h6" />
          </svg>
          Generate PDF Report
        </button>
      </div>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/ReportsAnalytics.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';

const modelCards = [
  {
    number: 1,
    title: 'SARIMA (Seasonal ARIMA)',
    subtitle: 'Demand Forecasting',
    description: 'Forecasts equipment demand patterns using time series modeling.',
    tone: 'blue',
  },
  {
    number: 2,
    title: 'Random Forest Classifier',
    subtitle: 'Readiness Risk Detection',
    description: 'Identifies equipment readiness risks based on historical and operational data.',
    tone: 'green',
  },
  {
    number: 3,
    title: 'Binary Integer Linear Programming (BILP)',
    subtitle: 'Resource Allocation Optimization',
    description: 'Optimizes allocation of equipment and venues while satisfying constraints.',
    tone: 'orange',
  },
];

const riskLevels = [
  { label: 'High Risk', value: '18 equipment', color: '#ef4444' },
  { label: 'Medium Risk', value: '25 equipment', color: '#f59e0b' },
  { label: 'Low Risk', value: '28 equipment', color: '#facc15' },
  { label: 'Very Low Risk', value: '92 equipment', color: '#16a34a' },
];

const optimizationMetrics = [
  { label: 'Conflict Reduction', note: 'vs. previous period', value: '+36%', icon: 'CR', tone: 'tree' },
  { label: 'Equipment Utilization', note: 'vs. previous period', value: '+24%', icon: 'EU', tone: 'box' },
  { label: 'Constraint Satisfaction', note: 'requirements met', value: '98.5%', icon: 'CS', tone: 'check' },
  { label: 'Unassigned Requests', note: 'vs. previous period', value: '-12%', icon: 'UR', tone: 'alert' },
];

const utilizationItems = [
  { label: 'Audio', value: 68 },
  { label: 'Visual', value: 74 },
  { label: 'Computing', value: 82 },
  { label: 'Furniture', value: 61 },
  { label: 'Others', value: 70 },
];

const topEquipment = [
  { name: 'Multimedia Projector', count: 128, rate: 85 },
  { name: 'Laptop (Dell)', count: 112, rate: 78 },
  { name: 'Wireless Microphone', count: 97, rate: 72 },
  { name: 'Portable Speaker', count: 85, rate: 68 },
  { name: 'DSLR Camera', count: 74, rate: 66 },
];

const summaryItems = [
  { label: 'Total Equipment', value: '186' },
  { label: 'Active Reservations', value: '42' },
  { label: 'Pending Requests', value: '9' },
  { label: 'Completed This Period', value: '117' },
  { label: 'Generated At', value: 'May 16, 2026 10:30 AM' },
];
</script>
