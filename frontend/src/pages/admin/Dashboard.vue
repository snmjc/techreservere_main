<!-- ===== AI GENERATED: FOAdministratorDashboardPage ===== -->
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
          <p class="admin-dashboard-subtitle">Static operational monitoring for requests, resource usage, readiness, and system activity.</p>
        </div>
        <div class="admin-dashboard-date-chip">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" />
            <path d="M16 2v4" />
            <path d="M8 2v4" />
            <path d="M3 10h18" />
          </svg>
          {{ dashboardDateRange }}
        </div>
      </div>

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
              <p>Mock utilization across major facilities and equipment groups.</p>
            </div>
            <span class="admin-dashboard-panel-badge">Static Data</span>
          </div>

          <div class="admin-dashboard-chart">
            <svg viewBox="0 0 720 260" role="img" aria-label="Resource utilization graph">
              <g class="admin-dashboard-chart-grid">
                <path d="M52 32H684" />
                <path d="M52 82H684" />
                <path d="M52 132H684" />
                <path d="M52 182H684" />
                <path d="M52 232H684" />
              </g>
              <g class="admin-dashboard-chart-labels">
                <text x="34" y="36">100</text>
                <text x="40" y="86">75</text>
                <text x="40" y="136">50</text>
                <text x="40" y="186">25</text>
                <text x="44" y="236">0</text>
              </g>
              <path class="admin-dashboard-chart-area" d="M58 190L120 155L182 166L244 110L306 134L368 88L430 118L492 76L554 104L616 92L684 66L684 232L58 232Z" />
              <polyline class="admin-dashboard-chart-line" points="58,190 120,155 182,166 244,110 306,134 368,88 430,118 492,76 554,104 616,92 684,66" />
              <g class="admin-dashboard-chart-points">
                <circle cx="58" cy="190" r="4" />
                <circle cx="244" cy="110" r="4" />
                <circle cx="368" cy="88" r="4" />
                <circle cx="492" cy="76" r="4" />
                <circle cx="684" cy="66" r="4" />
              </g>
              <g class="admin-dashboard-chart-months">
                <text x="52" y="254">Mon</text>
                <text x="176" y="254">Tue</text>
                <text x="300" y="254">Wed</text>
                <text x="424" y="254">Thu</text>
                <text x="548" y="254">Fri</text>
                <text x="660" y="254">Sat</text>
              </g>
            </svg>
          </div>
        </section>

        <section class="admin-dashboard-panel">
          <div class="admin-dashboard-panel-heading">
            <div>
              <h2>Grouped Data</h2>
              <p>Operational statistics for the current period.</p>
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
            <p>Static readiness and availability by facility type.</p>
          </div>
        </div>

        <div class="admin-dashboard-facility-grid">
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
            <p>{{ facility.status }}</p>
          </article>
        </div>
      </section>

      <div class="admin-dashboard-monitoring-grid">
        <section class="admin-dashboard-panel admin-dashboard-risk-panel">
          <div class="admin-dashboard-panel-heading">
            <div>
              <h2>Readiness Risk Alerts</h2>
              <p>Flag-based monitoring for equipment and facility readiness.</p>
            </div>
          </div>

          <div class="admin-dashboard-risk-alert-list">
            <article
              v-for="alert in readinessRiskAlerts"
              :key="alert.title"
              class="admin-dashboard-risk-alert"
            >
              <span class="admin-dashboard-risk-severity" :class="alert.className">{{ alert.severity }}</span>
              <div>
                <strong>{{ alert.title }}</strong>
                <p>{{ alert.detail }}</p>
              </div>
              <span class="admin-dashboard-risk-count">{{ alert.count }}</span>
            </article>
          </div>
        </section>

        <section class="admin-dashboard-panel">
          <div class="admin-dashboard-panel-heading">
            <div>
              <h2>System Activity Overview</h2>
              <p>Mock activity summary for the current operating day.</p>
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
              <b>{{ activity.value }}</b>
            </article>
          </div>
        </section>
      </div>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/Dashboard.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { apiUrl } from '@/shared/utils/apiBase.js';
import { buildAuthorizationHeaders } from '@/shared/utils/authToken.js';

const router = useRouter();
const authStore = useAuthenticationStore();

const emptyDashboardSummary = {
  totalAccounts: 0,
  totalEquipment: 0,
  totalReservations: 0,
  pendingReservations: 0,
  approvedReservations: 0,
  activeReservations: 0,
  completedReservations: 0,
  activeEquipmentCount: 0,
  activeFacilityUsageCount: 0,
  overdueEquipment: 0,
  equipmentUtilizationRate: 0,
};

const dashboardSummary = ref({ ...emptyDashboardSummary });
const isDashboardSummaryLoading = ref(true);

const dashboardDateRange = computed(() => {
  const formatter = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  const today = new Date();
  const start = new Date(today);
  start.setDate(today.getDate() - 14);
  return `${formatter.format(start)} - ${formatter.format(today)}`;
});

const totalOverviewCards = computed(() => [
  {
    label: 'Total Users',
    value: formatMetricValue(dashboardSummary.value.totalAccounts),
    meta: 'Connected user accounts',
    className: 'admin-dashboard-stat-card--users',
    routeName: 'adminManageAccountsPage',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
  },
  {
    label: 'Pending Requests',
    value: formatMetricValue(dashboardSummary.value.pendingReservations),
    meta: 'Awaiting admin review',
    className: 'admin-dashboard-stat-card--pending',
    routeName: 'adminPendingRequestsPage',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
  },
  {
    label: 'Approved Requests',
    value: formatMetricValue(dashboardSummary.value.approvedReservations),
    meta: 'Ready for release',
    className: 'admin-dashboard-stat-card--approved',
    routeName: 'adminApprovedRequestsPage',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>',
  },
  {
    label: 'Active Equipment / Facilities',
    value: isDashboardSummaryLoading.value
      ? '...'
      : `${formatCount(dashboardSummary.value.activeEquipmentCount)} / ${formatCount(dashboardSummary.value.activeFacilityUsageCount)}`,
    meta: 'Deployed items / booked facilities',
    className: 'admin-dashboard-stat-card--deployed',
    routeName: 'adminActiveReservationsPage',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="5" width="16" height="11" rx="2"/><path d="M8 21h8"/><path d="M12 16v5"/></svg>',
  },
  {
    label: 'Overdue Equipment',
    value: formatMetricValue(dashboardSummary.value.overdueEquipment),
    meta: 'Past expected return',
    className: 'admin-dashboard-stat-card--overdue',
    routeName: 'adminPastRecordsPage',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
  },
]);

const groupedStats = computed(() => [
  {
    label: 'Overall Equipment Utilization',
    value: isDashboardSummaryLoading.value ? '...' : `${dashboardSummary.value.equipmentUtilizationRate}%`,
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 16v-5"/><path d="M12 16V7"/><path d="M17 16v-3"/></svg>',
  },
  {
    label: 'Active Users',
    value: formatMetricValue(dashboardSummary.value.totalAccounts),
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
  },
  {
    label: 'Active Facility Usage',
    value: '64.3%',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/><path d="M9 9h1"/><path d="M9 13h1"/><path d="M9 17h1"/></svg>',
  },
  {
    label: 'Average Session Time',
    value: '1.2 hrs',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l4 2"/></svg>',
  },
]);

const facilityStatus = [
  { name: 'Classrooms', occupied: 17, total: 30, percent: 57, status: 'Moderate usage' },
  { name: 'Multipurpose Room', occupied: 1, total: 1, percent: 100, status: 'Currently occupied' },
  { name: 'Laboratories', occupied: 8, total: 12, percent: 67, status: 'Moderate usage' },
  { name: 'Audio Visual Room', occupied: 0, total: 1, percent: 0, status: 'Currently available' },
];

const readinessRiskAlerts = [
  {
    severity: 'High',
    count: '7',
    className: 'is-high',
    title: 'Equipment below readiness threshold',
    detail: 'Projectors and portable speakers need inspection before the next release window.',
  },
  {
    severity: 'Medium',
    count: '12',
    className: 'is-medium',
    title: 'Limited reserve stock',
    detail: 'Demand is close to available backup units for high-use multimedia equipment.',
  },
  {
    severity: 'Low',
    count: '28',
    className: 'is-low',
    title: 'Routine facility checks queued',
    detail: 'Facility rooms remain usable while scheduled readiness checks are pending.',
  },
];

const systemActivityOverview = [
  { label: 'New requests today', value: '24', meta: '12 facility requests, 12 equipment requests' },
  { label: 'Approvals processed', value: '18', meta: 'Average turnaround time is 1.2 hours' },
  { label: 'Equipment releases / returns', value: '11', meta: '7 released, 4 returned' },
  { label: 'Readiness alerts generated', value: '3', meta: 'Based on overdue, stock, and inspection flags' },
];

onMounted(() => {
  loadDashboardSummary();
});

async function loadDashboardSummary() {
  isDashboardSummaryLoading.value = true;
  try {
    const response = await fetch(apiUrl('/api/v1/dashboard/summary'), {
      method: 'GET',
      headers: buildDashboardHeaders(),
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) return;

    dashboardSummary.value = {
      ...emptyDashboardSummary,
      ...(result.data || result),
    };
  } catch (error) {
    dashboardSummary.value = { ...emptyDashboardSummary };
  } finally {
    isDashboardSummaryLoading.value = false;
  }
}

function buildDashboardHeaders() {
  return buildAuthorizationHeaders(authStore.authToken);
}

function navigateToMetricPage(routeName) {
  if (!routeName) return;
  router.push({ name: routeName });
}

function formatCount(value) {
  const numberValue = Number(value);
  if (!Number.isFinite(numberValue)) return String(value ?? 0);
  return new Intl.NumberFormat('en-US').format(numberValue);
}

function formatMetricValue(value) {
  return isDashboardSummaryLoading.value ? '...' : formatCount(value);
}
</script>
