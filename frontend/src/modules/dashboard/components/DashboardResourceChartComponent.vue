<!-- ===== AI GENERATED: DashboardResourceChartComponent ===== -->
<template>
  <div class="dashboard-resource-chart-card">
    <div class="dashboard-resource-chart-header">
      <h3 class="dashboard-resource-chart-title">Resource Utilization Today</h3>
      <p class="dashboard-resource-chart-subtitle">Real-time occupancy across facilities</p>
    </div>
    <div class="dashboard-resource-chart-placeholder">
      <!-- Placeholder area chart SVG mimicking the design -->
      <svg
        class="dashboard-resource-chart-svg"
        viewBox="0 0 600 200"
        preserveAspectRatio="none"
      >
        <!-- Y Axis labels -->
        <text x="10" y="15" class="dashboard-chart-axis-label">260</text>
        <text x="10" y="55" class="dashboard-chart-axis-label">195</text>
        <text x="10" y="95" class="dashboard-chart-axis-label">130</text>
        <text x="10" y="140" class="dashboard-chart-axis-label">65</text>

        <!-- Grid lines -->
        <line x1="40" y1="10" x2="590" y2="10" class="dashboard-chart-grid-line" />
        <line x1="40" y1="50" x2="590" y2="50" class="dashboard-chart-grid-line" />
        <line x1="40" y1="90" x2="590" y2="90" class="dashboard-chart-grid-line" />
        <line x1="40" y1="130" x2="590" y2="130" class="dashboard-chart-grid-line" />
        <line x1="40" y1="170" x2="590" y2="170" class="dashboard-chart-grid-line" />

        <!-- Area 1 (teal) -->
        <path
          d="M40,130 C100,110 160,80 220,90 C280,100 340,60 400,70 C460,80 520,50 590,40 L590,170 L40,170 Z"
          fill="rgba(20, 184, 166, 0.5)"
        />

        <!-- Area 2 (blue) -->
        <path
          d="M40,150 C100,140 160,120 220,130 C280,140 340,100 400,110 C460,120 520,90 590,80 L590,170 L40,170 Z"
          fill="rgba(59, 130, 246, 0.5)"
        />

        <!-- Area 3 (orange) -->
        <path
          d="M40,165 C100,155 160,145 220,155 C280,160 340,140 400,145 C460,150 520,135 590,130 L590,170 L40,170 Z"
          fill="rgba(249, 115, 22, 0.5)"
        />
      </svg>
      <p class="dashboard-resource-chart-note">Chart will be connected to live data</p>
    </div>
  </div>
</template>

<script setup>
</script>
