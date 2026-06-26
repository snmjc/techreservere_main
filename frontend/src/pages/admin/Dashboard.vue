<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="admin-dashboard-page">
      <div class="admin-dashboard-header">
        <div>
          <p class="admin-dashboard-kicker">FO administrator dashboard</p>
          <h1>Admin Dashboard</h1>
          <p class="admin-dashboard-subtitle">Live operational monitoring for requests, resource usage, readiness, and system activity.</p>
        </div>

        <label class="admin-dashboard-date-chip">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" />
            <path d="M16 2v4" />
            <path d="M8 2v4" />
            <path d="M3 10h18" />
          </svg>
          <select v-model="selectedRangeKey" class="admin-dashboard-date-select">
            <option
              v-for="preset in ADMIN_ANALYTICS_RANGE_PRESETS"
              :key="preset.key"
              :value="preset.key"
            >
              {{ preset.label }}
            </option>
          </select>
          <span class="admin-dashboard-date-label">{{ activeRangeLabel }}</span>
        </label>
      </div>

      <p v-if="dashboardError" class="admin-dashboard-inline-message is-error">{{ dashboardError }}</p>

      <section class="admin-dashboard-section">
        <div class="admin-dashboard-section-heading">
          <h2>Total Overview</h2>
        </div>

        <div class="admin-dashboard-stat-cards-row">
          <article
            v-for="card in totalOverviewCards"
            :key="card.label"
            class="admin-dashboard-stat-card"
            :class="card.className"
            role="button"
            tabindex="0"
            @click="navigateToMetricPage(card.routeName)"
            @keydown.enter.prevent="navigateToMetricPage(card.routeName)"
            @keydown.space.prevent="navigateToMetricPage(card.routeName)"
          >
            <span class="admin-dashboard-stat-icon" v-html="card.icon"></span>
            <div class="admin-dashboard-stat-copy">
              <p class="admin-dashboard-stat-card-label">{{ card.label }}</p>
              <strong class="admin-dashboard-stat-card-count">{{ card.value }}</strong>
              <span class="admin-dashboard-stat-card-meta">{{ card.meta }}</span>
            </div>
          </article>
        </div>
      </section>

      <div class="admin-dashboard-main-grid">
        <section class="admin-dashboard-panel admin-dashboard-panel--wide">
          <div class="admin-dashboard-panel-heading">
            <div>
              <h2>Resource Utilization</h2>
              <p>Reservation demand and equipment load across the selected period.</p>
            </div>
            <span class="admin-dashboard-panel-badge">{{ activeRangeLabel }}</span>
          </div>

          <div v-if="isDashboardLoading" class="admin-dashboard-inline-message">Loading dashboard data...</div>
          <div v-else-if="resourceSeries.length === 0" class="admin-dashboard-inline-message">No demand data is available for the selected range.</div>
          <div v-else class="admin-dashboard-chart">
            <canvas
              ref="resourceChartRef"
              class="admin-dashboard-chart-canvas"
              aria-label="Resource utilization graph"
            ></canvas>
          </div>
        </section>

        <section class="admin-dashboard-panel">
          <div class="admin-dashboard-panel-heading">
            <div>
              <h2>Grouped Data</h2>
              <p>Operational statistics derived from the selected period.</p>
            </div>
          </div>

          <div class="admin-dashboard-grouped-stats">
            <article
              v-for="stat in groupedStats"
              :key="stat.label"
              class="admin-dashboard-grouped-stat"
            >
              <span class="admin-dashboard-grouped-icon" v-html="stat.icon"></span>
              <div>
                <strong>{{ stat.value }}</strong>
                <span>{{ stat.label }}</span>
              </div>
            </article>
          </div>
        </section>
      </div>

      <section class="admin-dashboard-panel">
        <div class="admin-dashboard-panel-heading">
          <div>
            <h2>Facility Status Overview</h2>
            <p>Venue usage grouped by inferred facility type from current venue names.</p>
          </div>
        </div>

        <div v-if="facilityStatus.length === 0" class="admin-dashboard-inline-message">No facility records are available yet.</div>
        <div v-else class="admin-dashboard-facility-grid">
          <article
            v-for="facility in facilityStatus"
            :key="facility.name"
            class="admin-dashboard-facility-card"
          >
            <div class="admin-dashboard-facility-card-header">
              <span>{{ facility.name }}</span>
              <strong>{{ facility.occupied }}/{{ facility.total }}</strong>
            </div>
            <div class="admin-dashboard-facility-bar">
              <i :style="{ width: `${facility.percent}%` }"></i>
            </div>
            <p>{{ facility.statusLabel }}</p>
          </article>
        </div>
      </section>

      <div class="admin-dashboard-monitoring-grid">
        <section class="admin-dashboard-panel admin-dashboard-risk-panel">
          <div class="admin-dashboard-panel-heading">
            <div>
              <h2>Readiness Risk Alerts</h2>
              <p>Inventory and release monitoring derived from live operational records.</p>
            </div>
          </div>

          <div class="admin-dashboard-risk-alert-list">
            <article
              v-for="alert in readinessAlerts"
              :key="alert.title"
              class="admin-dashboard-risk-alert"
            >
              <span class="admin-dashboard-risk-severity" :class="alert.className">{{ alert.severity }}</span>
              <div>
                <strong>{{ alert.title }}</strong>
                <p>{{ alert.detail }}</p>
              </div>
              <span class="admin-dashboard-risk-count">{{ formatCount(alert.count) }}</span>
            </article>
          </div>
        </section>

        <section class="admin-dashboard-panel">
          <div class="admin-dashboard-panel-heading">
            <div>
              <h2>System Activity Overview</h2>
              <p>Live activity summary for requests, approvals, and release / return throughput.</p>
            </div>
          </div>

          <div class="admin-dashboard-activity-list">
            <article
              v-for="activity in systemActivityOverview"
              :key="activity.label"
              class="admin-dashboard-activity-item"
            >
              <div>
                <strong>{{ activity.label }}</strong>
                <span>{{ activity.meta }}</span>
              </div>
              <b>{{ formatCount(activity.value) }}</b>
            </article>
          </div>
        </section>
      </div>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/Dashboard.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import adminAnalyticsApi from '@/modules/dashboard/services/adminAnalyticsApi.js';
import { createDashboardChartRenderer } from './services/dashboardChartRenderer.js';
import {
  ADMIN_ANALYTICS_RANGE_PRESETS,
  formatDateRangeLabel,
  formatLeadTimeHours,
  formatMetricNumber,
  resolveAdminAnalyticsDateRange,
} from './adminAnalyticsHelpers.js';

const router = useRouter();

const selectedRangeKey = ref('14d');
const isDashboardLoading = ref(true);
const dashboardError = ref('');
const dashboardOverview = ref(createEmptyOverview());
const resourceChartRef = ref(null);
const chartRenderer = createDashboardChartRenderer();

const activeRange = computed(() => resolveAdminAnalyticsDateRange(selectedRangeKey.value));
const activeRangeLabel = computed(() => formatDateRangeLabel(activeRange.value.startDateIso, activeRange.value.endDateIso));
const resourceSeries = computed(() => dashboardOverview.value.resourceUtilization || []);

const totalOverviewCards = computed(() => {
  const summary = dashboardOverview.value.summary || {};
  return [
    {
      label: 'Total Users',
      value: metricValue(summary.totalAccounts),
      meta: 'Connected user accounts',
      className: 'admin-dashboard-stat-card--users',
      routeName: 'adminManageAccountsPage',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    },
    {
      label: 'Pending Requests',
      value: metricValue(summary.pendingReservations),
      meta: 'Awaiting admin review',
      className: 'admin-dashboard-stat-card--pending',
      routeName: 'adminPendingRequestsPage',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    },
    {
      label: 'Approved Requests',
      value: metricValue(summary.approvedReservations),
      meta: 'Ready for release',
      className: 'admin-dashboard-stat-card--approved',
      routeName: 'adminApprovedRequestsPage',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>',
    },
    {
      label: 'Under Maintenance',
      value: metricValue(summary.maintenanceEquipmentCount),
      meta: 'Inventory items temporarily unavailable for borrowers',
      className: 'admin-dashboard-stat-card--overdue',
      routeName: 'adminManageEquipmentPage',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 1 1.4 0l1.6 1.6a1 1 0 0 1 0 1.4l-7.8 7.8-3.2.8.8-3.2 7.2-7.2Z"/><path d="m13 8 3 3"/><path d="M5 19h14"/></svg>',
    },
    {
      label: 'Active Equipment / Facilities',
      value: isDashboardLoading.value
        ? '...'
        : `${formatCount(summary.activeEquipmentCount)} / ${formatCount(summary.activeFacilityCount)}`,
      meta: 'Deployed items / booked facilities',
      className: 'admin-dashboard-stat-card--deployed',
      routeName: 'adminActiveReservationsPage',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="5" width="16" height="11" rx="2"/><path d="M8 21h8"/><path d="M12 16v5"/></svg>',
    },
    {
      label: 'Overdue Equipment',
      value: metricValue(summary.overdueEquipmentCount),
      meta: 'Released reservations not yet returned past scheduled event time',
      className: 'admin-dashboard-stat-card--overdue',
      routeName: 'adminPastRecordsPage',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
    },
  ];
});

const groupedStats = computed(() => {
  const grouped = dashboardOverview.value.groupedStats || {};
  return [
    {
      label: 'Overall Equipment Utilization',
      value: `${formatMetricNumber(grouped.equipmentUtilizationRate, 1)}%`,
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 16v-5"/><path d="M12 16V7"/><path d="M17 16v-3"/></svg>',
    },
    {
      label: 'Active Users',
      value: metricValue(grouped.activeUsers),
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    },
    {
      label: 'Active Facility Usage',
      value: `${formatMetricNumber(grouped.facilityUtilizationRate, 1)}%`,
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/><path d="M9 9h1"/><path d="M9 13h1"/><path d="M9 17h1"/></svg>',
    },
    {
      label: 'Under Maintenance',
      value: metricValue(dashboardOverview.value.summary?.maintenanceEquipmentCount),
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 1 1.4 0l1.6 1.6a1 1 0 0 1 0 1.4l-7.8 7.8-3.2.8.8-3.2 7.2-7.2Z"/><path d="m13 8 3 3"/><path d="M5 19h14"/></svg>',
    },
    {
      label: 'Average Lead Time',
      value: formatLeadTimeHours(grouped.averageLeadTimeHours),
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l4 2"/></svg>',
    },
  ];
});

const facilityStatus = computed(() => dashboardOverview.value.facilityStatus || []);
const readinessAlerts = computed(() => dashboardOverview.value.readinessAlerts || []);
const systemActivityOverview = computed(() => dashboardOverview.value.systemActivity || []);

onMounted(() => {
  loadDashboardOverview();
});

onBeforeUnmount(() => {
  destroyCharts();
});

watch(selectedRangeKey, () => {
  loadDashboardOverview();
});

watch(
  resourceSeries,
  () => {
    renderResourceChartAfterUpdate();
  },
  { deep: true }
);

async function loadDashboardOverview() {
  isDashboardLoading.value = true;
  dashboardError.value = '';

  try {
    dashboardOverview.value = createEmptyOverview();
    dashboardOverview.value = await adminAnalyticsApi.getDashboardOverview(activeRange.value);
  } catch (error) {
    dashboardOverview.value = createEmptyOverview();
    dashboardError.value = resolveDashboardError(error);
  } finally {
    isDashboardLoading.value = false;
    await renderResourceChartAfterUpdate();
  }
}

function destroyCharts() {
  chartRenderer.destroyAll();
}

async function renderResourceChartAfterUpdate() {
  await nextTick();
  renderResourceChart();
}

function renderResourceChart() {
  chartRenderer.renderResourceUtilizationChart({
    canvas: resourceChartRef.value,
    resourceSeries: resourceSeries.value,
    formatMetricNumber,
  });
}

function navigateToMetricPage(routeName) {
  if (!routeName) return;
  router.push({ name: routeName });
}

function createEmptyOverview() {
  return {
    summary: {
      totalAccounts: 0,
      pendingReservations: 0,
      approvedReservations: 0,
      activeEquipmentCount: 0,
      maintenanceEquipmentCount: 0,
      activeFacilityCount: 0,
      overdueEquipmentCount: 0,
    },
    resourceUtilization: [],
    groupedStats: {
      equipmentUtilizationRate: 0,
      activeUsers: 0,
      facilityUtilizationRate: 0,
      averageLeadTimeHours: 0,
    },
    facilityStatus: [],
    readinessAlerts: [],
    systemActivity: [],
  };
}

function metricValue(value) {
  return isDashboardLoading.value ? '...' : formatCount(value);
}

function formatCount(value) {
  return formatMetricNumber(value, 0);
}

function resolveDashboardError(error) {
  return error?.response?.data?.errorMessage
    || error?.message
    || 'Unable to load dashboard analytics right now.';
}
</script>
