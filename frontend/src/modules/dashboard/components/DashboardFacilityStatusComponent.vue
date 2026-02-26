<!-- ===== AI GENERATED: DashboardFacilityStatusComponent ===== -->
<template>
  <div class="dashboard-facility-status-card">
    <div class="dashboard-facility-status-header">
      <h3 class="dashboard-facility-status-title">Facility Status Overview</h3>
      <p class="dashboard-facility-status-subtitle">Current status of all facilities</p>
    </div>
    <div class="dashboard-facility-status-list">
      <div
        v-for="facilityItem in facilityStatusList"
        :key="facilityItem.facilityName"
        class="dashboard-facility-status-item"
      >
        <span class="dashboard-facility-status-name">{{ facilityItem.facilityName }}</span>
        <div class="dashboard-facility-status-bar-track">
          <div
            class="dashboard-facility-status-bar-fill"
            :style="{
              width: calculateFillPercentage(facilityItem.currentCount, facilityItem.totalCount) + '%',
              backgroundColor: facilityItem.barColor || '#3b82f6'
            }"
          ></div>
        </div>
        <span class="dashboard-facility-status-ratio">
          {{ facilityItem.currentCount }}/{{ facilityItem.totalCount }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * @typedef {Object} FacilityStatusItem
 * @property {string} facilityName - Name of the facility
 * @property {number} currentCount - Current occupied/used count
 * @property {number} totalCount - Total capacity
 * @property {string} barColor - Color for the progress bar
 */

/**
 * @typedef {Object} DashboardFacilityStatusProps
 * @property {Array<FacilityStatusItem>} facilityStatusList - Array of facility status items
 */
const props = defineProps({
  facilityStatusList: {
    type: Array,
    required: true,
  },
});

/**
 * @function calculateFillPercentage
 * @description Calculates percentage for the progress bar fill width.
 * @param {number} currentCount - Current value
 * @param {number} totalCount - Maximum value
 * @returns {number}
 */
function calculateFillPercentage(currentCount, totalCount) {
  if (totalCount === 0) return 0;
  return Math.min(Math.round((currentCount / totalCount) * 100), 100);
}
</script>
