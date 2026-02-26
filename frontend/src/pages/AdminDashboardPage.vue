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
        :stat-count="9"
        stat-label="Pending Requests"
        card-background-color="#c49a1a"
      />
      <DashboardStatCardComponent
        :stat-count="15"
        stat-label="Approved Requests"
        card-background-color="#15803d"
      />
      <DashboardStatCardComponent
        :stat-count="4"
        stat-label="Equipment Currently Deployed"
        card-background-color="#d97706"
      />
    </div>

    <!-- Middle Row: Overdue + Quick Stats -->
    <div class="admin-dashboard-middle-row">
      <DashboardStatCardComponent
        :stat-count="2"
        stat-label="Overdue Equipment"
        card-background-color="#dc2626"
      />
      <DashboardQuickStatsComponent
        equipment-utilization="76.8%"
        :active-users-count="482"
        average-session-time="1.2 hrs"
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
import { ref } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/adminDashboardPage.css';

import DashboardStatCardComponent from '@/modules/dashboard/components/DashboardStatCardComponent.vue';
import DashboardQuickStatsComponent from '@/modules/dashboard/components/DashboardQuickStatsComponent.vue';
import DashboardFacilityStatusComponent from '@/modules/dashboard/components/DashboardFacilityStatusComponent.vue';
import DashboardResourceChartComponent from '@/modules/dashboard/components/DashboardResourceChartComponent.vue';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';

/**
 * @constant {Array<Object>} facilityStatusList
 * @description Static facility status data for display.
 */
const facilityStatusList = ref([
  { facilityName: 'Classrooms', currentCount: 17, totalCount: 30, barColor: '#60a5fa' },
  { facilityName: 'Multipurpose Rooms', currentCount: 1, totalCount: 3, barColor: '#60a5fa' },
  { facilityName: 'Chairs', currentCount: 85, totalCount: 100, barColor: '#60a5fa' },
]);
</script>
