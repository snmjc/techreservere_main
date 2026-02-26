<!-- ===== AI GENERATED: DashboardStatCardComponent ===== -->
<template>
  <div class="dashboard-stat-card" :style="{ backgroundColor: cardBackgroundColor }">
    <span class="dashboard-stat-card-count">{{ statCount }}</span>
    <span class="dashboard-stat-card-label">{{ statLabel }}</span>
  </div>
</template>

<script setup>
/**
 * @typedef {Object} DashboardStatCardProps
 * @property {number|string} statCount - The numeric value to display
 * @property {string} statLabel - Description label below the count
 * @property {string} cardBackgroundColor - Background color hex
 */
const props = defineProps({
  statCount: {
    type: [Number, String],
    required: true,
  },
  statLabel: {
    type: String,
    required: true,
  },
  cardBackgroundColor: {
    type: String,
    required: false,
    default: '#1a8a4a',
  },
});
</script>
