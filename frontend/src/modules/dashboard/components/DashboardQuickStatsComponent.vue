<!-- ===== AI GENERATED: DashboardQuickStatsComponent ===== -->
<template>
  <div class="dashboard-quick-stats-panel">
    <div class="dashboard-quick-stats-row">
      <div class="dashboard-quick-stats-item">
        <span class="dashboard-quick-stats-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3"/>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83"/>
          </svg>
        </span>
        <span class="dashboard-quick-stats-label">Overall Equipment Utilization</span>
        <span class="dashboard-quick-stats-value">{{ equipmentUtilization }}</span>
      </div>
      <div class="dashboard-quick-stats-item">
        <span class="dashboard-quick-stats-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
        </span>
        <span class="dashboard-quick-stats-label">Active Users</span>
        <span class="dashboard-quick-stats-value">{{ activeUsersCount }}</span>
      </div>
      <div class="dashboard-quick-stats-item">
        <span class="dashboard-quick-stats-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
          </svg>
        </span>
        <span class="dashboard-quick-stats-label">Average Session Time</span>
        <span class="dashboard-quick-stats-value">{{ averageSessionTime }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * @typedef {Object} DashboardQuickStatsProps
 * @property {string} equipmentUtilization - Equipment utilization percentage string
 * @property {number|string} activeUsersCount - Active users count
 * @property {string} averageSessionTime - Average session time string
 */
const props = defineProps({
  equipmentUtilization: {
    type: String,
    required: false,
    default: '0%',
  },
  activeUsersCount: {
    type: [Number, String],
    required: false,
    default: 0,
  },
  averageSessionTime: {
    type: String,
    required: false,
    default: '0 hrs',
  },
});
</script>
