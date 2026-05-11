<!-- ===== AI GENERATED: AdminDashboardPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <!-- Greeting -->
    <h2 class="admin-dashboard-greeting">
      Hello, <span class="admin-dashboard-greeting-name">Admin</span>!
    </h2>

    <!-- Total Overview Label -->
    <p class="admin-dashboard-overview-label">Total Overview</p>

    <!-- Stat Cards Row -->
    <div class="admin-dashboard-stat-cards-row">
      <DashboardStatCardComponent
        :stat-count="requestStore.pendingCount"
        stat-label="Pending Requests"
        card-background-color="#c49a1a"
      />
      <DashboardStatCardComponent
        :stat-count="requestStore.approvedCount"
        stat-label="Approved Requests"
        card-background-color="#15803d"
      />
      <DashboardStatCardComponent
        :stat-count="requestStore.activeCount"
        stat-label="Equipment Currently Deployed"
        card-background-color="#d97706"
      />
    </div>

    <!-- Middle Row: Overdue + Quick Stats -->
    <div class="admin-dashboard-middle-row">
      <DashboardStatCardComponent
        :stat-count="requestStore.overdueCount"
        stat-label="Overdue Equipment"
        card-background-color="#dc2626"
      />
      <DashboardQuickStatsComponent
        equipment-utilization="0%"
        :active-users-count="0"
        average-session-time="0 hrs"
      />
    </div>

    <!-- Bottom Row: Chart + Facility Status -->
    <div class="admin-dashboard-bottom-row">
      <DashboardResourceChartComponent />
      <DashboardFacilityStatusComponent
        :facility-status-list="facilityStatusList"
      />
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/Dashboard.css';

import DashboardStatCardComponent from '@/modules/dashboard/components/DashboardStatCardComponent.vue';
import DashboardQuickStatsComponent from '@/modules/dashboard/components/DashboardQuickStatsComponent.vue';
import DashboardFacilityStatusComponent from '@/modules/dashboard/components/DashboardFacilityStatusComponent.vue';
import DashboardResourceChartComponent from '@/modules/dashboard/components/DashboardResourceChartComponent.vue';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { useRequestStore } from '@/modules/request/store/requestStore.js';

const requestStore = useRequestStore();

onMounted(async () => {
  try {
    await requestStore.fetchReservations();
  } catch (error) {
    console.error('Error fetching dashboard data:', error);
  }
});

/**
 * @constant {Array<Object>} facilityStatusList
 * @description Static facility status data for display.
 */
const facilityStatusList = ref([]);
</script>
