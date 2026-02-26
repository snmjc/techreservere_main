<!-- ===== AI GENERATED: AdminReportsAnalyticsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <!-- Page Header -->
    <div class="reports-analytics-page-header">
      <h2 class="reports-analytics-page-heading">Reports &amp; Analytics</h2>
      <button class="reports-analytics-export-button">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
          <polyline points="7 10 12 15 17 10"/>
          <line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        Export Data
      </button>
    </div>

    <!-- Sub-heading -->
    <p class="reports-analytics-sub-heading">System Analytics and Insights</p>

    <!-- Grid Layout: 2 charts left, 3 cards right stacked -->
    <div class="reports-analytics-grid">
      <!-- Left Top: Hourly Equipment Demand -->
      <div class="reports-analytics-grid-left-top">
        <AnalyticsHourlyDemandChartComponent />
      </div>

      <!-- Left Bottom: Monthly Equipment Usage Forecast -->
      <div class="reports-analytics-grid-left-bottom">
        <AnalyticsMonthlyForecastChartComponent />
      </div>

      <!-- Right Stack: At-Risk + Efficiency + Summary -->
      <div class="reports-analytics-grid-right-stack">
        <AnalyticsAtRiskTableComponent
          :at-risk-reservations="atRiskReservationsList"
        />
        <AnalyticsEfficiencyMetricsComponent
          :efficiency-metrics="efficiencyMetricsList"
        />
        <AnalyticsSummaryCardComponent
          :analytics-summary-items="analyticsSummaryList"
        />
      </div>
    </div>

    <!-- Footer -->
    <div class="reports-analytics-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/adminReportsAnalyticsPage.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import AnalyticsHourlyDemandChartComponent from '@/modules/analytics/components/AnalyticsHourlyDemandChartComponent.vue';
import AnalyticsMonthlyForecastChartComponent from '@/modules/analytics/components/AnalyticsMonthlyForecastChartComponent.vue';
import AnalyticsAtRiskTableComponent from '@/modules/analytics/components/AnalyticsAtRiskTableComponent.vue';
import AnalyticsEfficiencyMetricsComponent from '@/modules/analytics/components/AnalyticsEfficiencyMetricsComponent.vue';
import AnalyticsSummaryCardComponent from '@/modules/analytics/components/AnalyticsSummaryCardComponent.vue';

/**
 * @constant {Array<Object>} atRiskReservationsList
 * @description Static at-risk reservation records for display.
 */
const atRiskReservationsList = ref([
  {
    reservationCode: 'P-101',
    equipmentName: 'Podium',
    reservationDate: 'Feb 28',
    riskLevel: 'High',
    riskFactor: 'Peak usage',
  },
  {
    reservationCode: 'M-112',
    equipmentName: 'Microphone',
    reservationDate: 'Mar 1',
    riskLevel: 'Medium',
    riskFactor: 'Peak usage',
  },
  {
    reservationCode: 'S-091',
    equipmentName: 'Sound System',
    reservationDate: 'Mar 5',
    riskLevel: 'High',
    riskFactor: 'Peak usage',
  },
  {
    reservationCode: 'P-115',
    equipmentName: 'Philippine Flag',
    reservationDate: 'Mar 7',
    riskLevel: 'High',
    riskFactor: 'Peak usage',
  },
]);

/**
 * @constant {Array<Object>} efficiencyMetricsList
 * @description Static efficiency metric records for display.
 */
const efficiencyMetricsList = ref([
  { metricValue: '22%', metricLabel: 'Conflict\nReduction' },
  { metricValue: '87%', metricLabel: 'Staff\nUtilization' },
  { metricValue: '77%', metricLabel: 'Equipment\nUtilization' },
]);

/**
 * @constant {Array<Object>} analyticsSummaryList
 * @description Static analytics summary records for display.
 */
const analyticsSummaryList = ref([
  { summaryLabel: 'Upcoming Reservations', summaryValue: '10' },
  { summaryLabel: 'Predicted Peak Period', summaryValue: 'Feb 26 - 28' },
  { summaryLabel: 'High-Risk Reservations', summaryValue: '6' },
  { summaryLabel: 'Suggested Adjustments', summaryValue: '4' },
]);
</script>
