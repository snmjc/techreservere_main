<!-- ===== AI GENERATED: AnalyticsAtRiskTableComponent ===== -->
<template>
  <div class="analytics-at-risk-card">
    <div class="analytics-at-risk-header">
      <span class="analytics-at-risk-icon">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
      </span>
      <h3 class="analytics-at-risk-title">Top At-Risk Reservations</h3>
    </div>
    <table class="analytics-at-risk-table">
      <thead>
        <tr>
          <th class="analytics-at-risk-header-cell">ID</th>
          <th class="analytics-at-risk-header-cell">Equipment</th>
          <th class="analytics-at-risk-header-cell">Date</th>
          <th class="analytics-at-risk-header-cell">Risk Level</th>
          <th class="analytics-at-risk-header-cell">Factor</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="riskRecord in atRiskReservations"
          :key="riskRecord.reservationCode"
          class="analytics-at-risk-body-row"
        >
          <td class="analytics-at-risk-cell">{{ riskRecord.reservationCode }}</td>
          <td class="analytics-at-risk-cell">{{ riskRecord.equipmentName }}</td>
          <td class="analytics-at-risk-cell">{{ riskRecord.reservationDate }}</td>
          <td class="analytics-at-risk-cell">{{ riskRecord.riskLevel }}</td>
          <td class="analytics-at-risk-cell">{{ riskRecord.riskFactor }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
/**
 * @typedef {Object} AnalyticsAtRiskTableProps
 * @property {Array<Object>} atRiskReservations - Array of at-risk reservation records
 */
defineProps({
  atRiskReservations: {
    type: Array,
    required: true,
  },
});
</script>
