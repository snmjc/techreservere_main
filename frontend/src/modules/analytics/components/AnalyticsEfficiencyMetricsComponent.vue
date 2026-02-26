<!-- ===== AI GENERATED: AnalyticsEfficiencyMetricsComponent ===== -->
<template>
  <div class="analytics-efficiency-card">
    <div class="analytics-efficiency-header">
      <span class="analytics-efficiency-icon">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="20" x2="18" y2="10"/>
          <line x1="12" y1="20" x2="12" y2="4"/>
          <line x1="6" y1="20" x2="6" y2="14"/>
        </svg>
      </span>
      <h3 class="analytics-efficiency-title">Efficiency Metrics</h3>
    </div>
    <div class="analytics-efficiency-stats-row">
      <div
        v-for="metricRecord in efficiencyMetrics"
        :key="metricRecord.metricLabel"
        class="analytics-efficiency-stat"
      >
        <span class="analytics-efficiency-stat-value">{{ metricRecord.metricValue }}</span>
        <span class="analytics-efficiency-stat-label">{{ metricRecord.metricLabel }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * @typedef {Object} AnalyticsEfficiencyMetricsProps
 * @property {Array<Object>} efficiencyMetrics - Array of metric records with metricValue and metricLabel
 */
defineProps({
  efficiencyMetrics: {
    type: Array,
    required: true,
  },
});
</script>
