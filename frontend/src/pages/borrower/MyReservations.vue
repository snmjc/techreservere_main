<!-- ===== AI GENERATED: BorrowerMyReservationsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="userFullName"
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
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/MyReservations.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';

const router = useRouter();
const requestStore = useRequestStore();
const authStore = useAuthenticationStore();

const activeReservationsCount = computed(() => requestStore.activeCount);
const approvedRequestsCount = computed(() => requestStore.approvedCount);
const pendingRequestsCount = computed(() => requestStore.pendingCount);
const completedReservationsCount = computed(() => requestStore.completedCount);

const userFullName = computed(() => authStore.userFullName);

onMounted(async () => {
  try {
    console.log('Auth token in localStorage:', localStorage.getItem('techreserve_auth_token') ? 'exists' : 'none');
    console.log('Account data in localStorage:', localStorage.getItem('techreserve_auth_account'));
    console.log('User is authenticated:', authStore.isAuthenticated);
    console.log('User role:', authStore.userRole);
    console.log('User account data:', authStore.accountData);
    
    await requestStore.fetchReservations();
    const total = (requestStore.pendingRequestsList.value?.length || 0) + 
                  (requestStore.approvedRequestsList.value?.length || 0) + 
                  (requestStore.activeReservationsList.value?.length || 0);
    console.log('My Reservations - Total:', total);
  } catch (error) {
    console.error('Error fetching reservations:', error);
  }
});

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
