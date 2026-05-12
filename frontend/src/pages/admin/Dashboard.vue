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
      <div class="admin-dashboard-stat-card admin-dashboard-stat-card--pending">
        <div class="admin-dashboard-stat-card-count">{{ requestStore.pendingCount }}</div>
        <div class="admin-dashboard-stat-card-label">Pending Requests</div>
      </div>
      <div class="admin-dashboard-stat-card admin-dashboard-stat-card--approved">
        <div class="admin-dashboard-stat-card-count">{{ requestStore.approvedCount }}</div>
        <div class="admin-dashboard-stat-card-label">Approved Requests</div>
      </div>
      <div class="admin-dashboard-stat-card admin-dashboard-stat-card--deployed">
        <div class="admin-dashboard-stat-card-count">{{ requestStore.activeCount }}</div>
        <div class="admin-dashboard-stat-card-label">Equipment Currently Deployed</div>
      </div>
    </div>

    <!-- Middle Row: Overdue + Quick Stats -->
    <div class="admin-dashboard-middle-row">
      <div class="admin-dashboard-stat-card admin-dashboard-stat-card--overdue">
        <div class="admin-dashboard-stat-card-count">{{ requestStore.overdueCount }}</div>
        <div class="admin-dashboard-stat-card-label">Overdue Equipment</div>
      </div>
      <div class="admin-dashboard-quick-stats">
        <div class="admin-dashboard-quick-stat-item">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
            <path d="M12 6v6l4 2.4"/>
          </svg>
          <div class="admin-dashboard-quick-stat-label">Overall Equipment Utilization</div>
          <div class="admin-dashboard-quick-stat-value">76.8%</div>
        </div>
        <div class="admin-dashboard-quick-stat-item">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
          <div class="admin-dashboard-quick-stat-label">Active Users</div>
          <div class="admin-dashboard-quick-stat-value">482</div>
        </div>
        <div class="admin-dashboard-quick-stat-item">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
          </svg>
          <div class="admin-dashboard-quick-stat-label">Average Session Time</div>
          <div class="admin-dashboard-quick-stat-value">1.2 hrs</div>
        </div>
      </div>
    </div>

    <!-- Bottom Row: Chart + Facility Status -->
    <div class="admin-dashboard-bottom-row">
      <div class="admin-dashboard-resource-chart">
        <h3 class="admin-dashboard-resource-chart-title">Resource Utilization Today</h3>
        <p class="admin-dashboard-resource-chart-subtitle">Real-time occupancy across facilities</p>
        <div class="admin-dashboard-chart-placeholder">
          <svg viewBox="0 0 300 150" class="admin-dashboard-chart-svg">
            <defs>
              <linearGradient id="gradient1" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" style="stop-color:#06b6d4;stop-opacity:0.8" />
                <stop offset="100%" style="stop-color:#06b6d4;stop-opacity:0.1" />
              </linearGradient>
              <linearGradient id="gradient2" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" style="stop-color:#fbbf24;stop-opacity:0.8" />
                <stop offset="100%" style="stop-color:#fbbf24;stop-opacity:0.1" />
              </linearGradient>
            </defs>
            <path d="M 0 100 Q 50 70, 100 80 T 200 60 T 300 90 L 300 150 L 0 150 Z" fill="url(#gradient1)" />
            <path d="M 0 120 Q 50 90, 100 100 T 200 80 T 300 110 L 300 150 L 0 150 Z" fill="url(#gradient2)" />
            <polyline points="0,100 50,70 100,80 150,65 200,60 250,75 300,90" stroke="#06b6d4" stroke-width="2" fill="none" />
            <polyline points="0,120 50,90 100,100 150,85 200,80 250,95 300,110" stroke="#fbbf24" stroke-width="2" fill="none" />
          </svg>
        </div>
      </div>
      <div class="admin-dashboard-facility-status">
        <h3 class="admin-dashboard-facility-status-title">Facility Status Overview</h3>
        <p class="admin-dashboard-facility-status-subtitle">Current status of all facilities</p>
        <div class="admin-dashboard-facility-list">
          <div class="admin-dashboard-facility-item">
            <span class="admin-dashboard-facility-name">Classrooms</span>
            <div class="admin-dashboard-facility-bar">
              <div class="admin-dashboard-facility-bar-fill" style="width: 57%;"></div>
            </div>
            <span class="admin-dashboard-facility-count">17/30</span>
          </div>
          <div class="admin-dashboard-facility-item">
            <span class="admin-dashboard-facility-name">Multipurpose Rooms</span>
            <div class="admin-dashboard-facility-bar">
              <div class="admin-dashboard-facility-bar-fill" style="width: 33%;"></div>
            </div>
            <span class="admin-dashboard-facility-count">1/3</span>
          </div>
          <div class="admin-dashboard-facility-item">
            <span class="admin-dashboard-facility-name">Chairs</span>
            <div class="admin-dashboard-facility-bar">
              <div class="admin-dashboard-facility-bar-fill" style="width: 85%;"></div>
            </div>
            <span class="admin-dashboard-facility-count">85/100</span>
          </div>
        </div>
      </div>
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
