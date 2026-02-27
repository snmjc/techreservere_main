<!-- ===== AI GENERATED: BorrowerMyReservationsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'DELA CRUZ, JUAN'"
    :navigation-items="borrowerNavigationItems"
  >
    <!-- Page Heading -->
    <h2 class="my-reservations-page-heading">My Reservations</h2>

    <!-- Create a Reservation -->
    <p class="my-reservations-create-label">Create a Reservation</p>
    <button class="my-reservations-create-card" @click="navigateToCreateReservation">
      <span class="my-reservations-create-card-icon">+</span>
      <span class="my-reservations-create-card-text">Create New<br/>Reservation</span>
    </button>

    <!-- Total Overview -->
    <p class="my-reservations-overview-label">Total Overview</p>
    <div class="my-reservations-stats-grid">
      <button class="my-reservations-stat-card my-reservations-stat-card--active" @click="navigateToSubList('borrowerActiveReservationsPage')">
        <span class="my-reservations-stat-value">{{ activeReservationsCount }}</span>
        <span class="my-reservations-stat-label">Active<br/>Reservations</span>
      </button>
      <button class="my-reservations-stat-card my-reservations-stat-card--approved" @click="navigateToSubList('borrowerApprovedRequestsPage')">
        <span class="my-reservations-stat-value">{{ approvedRequestsCount }}</span>
        <span class="my-reservations-stat-label">Approved<br/>Requests</span>
      </button>
      <button class="my-reservations-stat-card my-reservations-stat-card--pending" @click="navigateToSubList('borrowerPendingRequestsPage')">
        <span class="my-reservations-stat-value">{{ pendingRequestsCount }}</span>
        <span class="my-reservations-stat-label">Pending<br/>Requests</span>
      </button>
      <button class="my-reservations-stat-card my-reservations-stat-card--completed">
        <span class="my-reservations-stat-value">{{ completedReservationsCount }}</span>
        <span class="my-reservations-stat-label">Completed</span>
      </button>
    </div>

    <!-- Footer -->
    <div class="my-reservations-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/borrowerMyReservationsPage.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { useRequestStore } from '@/modules/request/store/requestStore.js';

const router = useRouter();
const requestStore = useRequestStore();

const activeReservationsCount = computed(() => requestStore.activeCount);
const approvedRequestsCount = computed(() => requestStore.approvedCount);
const pendingRequestsCount = computed(() => requestStore.pendingCount);
const completedReservationsCount = computed(() => requestStore.completedCount);

/**
 * @function navigateToCreateReservation
 * @description Navigates to the create reservation form page.
 * @returns {void}
 */
function navigateToCreateReservation() {
  router.push({ name: 'borrowerCreateReservationPage' });
}

/**
 * @function navigateToSubList
 * @description Navigates to a borrower sub-list page by route name.
 * @param {string} routeName - The target route name
 * @returns {void}
 */
function navigateToSubList(routeName) {
  router.push({ name: routeName });
}
</script>
