<!-- ===== AI GENERATED: AnalyticsSummaryCardComponent ===== -->
<template>
  <div class="analytics-summary-card">
    <div class="analytics-summary-header">
      <span class="analytics-summary-icon">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/>
          <line x1="16" y1="17" x2="8" y2="17"/>
          <polyline points="10 9 9 9 8 9"/>
        </svg>
      </span>
      <h3 class="analytics-summary-title">Analytics Summary</h3>
    </div>
    <div class="analytics-summary-rows">
      <div
        v-for="summaryRecord in analyticsSummaryItems"
        :key="summaryRecord.summaryLabel"
        class="analytics-summary-row"
      >
        <span class="analytics-summary-row-label">{{ summaryRecord.summaryLabel }}</span>
        <span class="analytics-summary-row-value">{{ summaryRecord.summaryValue }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * @typedef {Object} AnalyticsSummaryCardProps
 * @property {Array<Object>} analyticsSummaryItems - Array of summary records with summaryLabel and summaryValue
 */
defineProps({
  analyticsSummaryItems: {
    type: Array,
    required: true,
  },
});
</script>
