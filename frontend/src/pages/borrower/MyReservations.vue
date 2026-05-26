<!-- ===== AI GENERATED: BorrowerMyReservationsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="userFullName"
    :navigation-items="borrowerNavigationItems"
  >
    <section class="my-reservations-page">
      <div class="my-reservations-hero">
        <div>
          <p class="my-reservations-kicker">Borrower workspace</p>
          <h1>My Reservations</h1>
          <p class="my-reservations-subtitle">
            Create requests, track approvals, and review reservation activity in one place.
          </p>
        </div>
        <button class="my-reservations-primary-action" type="button" @click="navigateToCreateReservation">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14" />
            <path d="M5 12h14" />
          </svg>
          New Reservation
        </button>
      </div>

      <div class="my-reservations-overview">
        <button
          v-for="stat in reservationStats"
          :key="stat.label"
          type="button"
          class="my-reservations-stat-card"
          :class="stat.className"
          @click="stat.action"
        >
          <span class="my-reservations-stat-icon" v-html="stat.icon"></span>
          <span class="my-reservations-stat-copy">
            <strong>{{ stat.value }}</strong>
            <span>{{ stat.label }}</span>
          </span>
        </button>
      </div>

      <div class="my-reservations-content-grid">
        <section class="my-reservations-panel my-reservations-panel--wide">
          <div class="my-reservations-panel-heading">
            <div>
              <h2>Reservation Flow</h2>
              <p>Follow each request from submission to completion.</p>
            </div>
          </div>
          <div class="my-reservations-timeline">
            <div class="my-reservations-timeline-step is-complete">
              <span>1</span>
              <div>
                <strong>Submit</strong>
                <p>Send reservation details and required documents.</p>
              </div>
            </div>
            <div class="my-reservations-timeline-step is-current">
              <span>2</span>
              <div>
                <strong>Review</strong>
                <p>Administrator checks availability and request details.</p>
              </div>
            </div>
            <div class="my-reservations-timeline-step">
              <span>3</span>
              <div>
                <strong>Use</strong>
                <p>Claim approved equipment or venue on schedule.</p>
              </div>
            </div>
            <div class="my-reservations-timeline-step">
              <span>4</span>
              <div>
                <strong>Return</strong>
                <p>Complete the reservation and close the record.</p>
              </div>
            </div>
          </div>
        </section>

        <section class="my-reservations-panel">
          <div class="my-reservations-panel-heading">
            <div>
              <h2>Quick Actions</h2>
              <p>Common tasks for borrowers.</p>
            </div>
          </div>
          <div class="my-reservations-action-list">
            <button type="button" @click="navigateToCreateReservation">
              <span class="my-reservations-action-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
                  <path d="M14 2v6h6" />
                  <path d="M12 11v6" />
                  <path d="M9 14h6" />
                </svg>
              </span>
              Create new request
            </button>
            <button type="button" @click="navigateToViewReservationList">
              <span class="my-reservations-action-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="4" width="18" height="17" rx="2" />
                  <path d="M8 2v4" />
                  <path d="M16 2v4" />
                  <path d="M3 10h18" />
                  <path d="M8 15h5" />
                  <path d="M8 18h8" />
                </svg>
              </span>
              View active reservations
            </button>
            <button type="button" @click="navigateToPastRecords">
              <span class="my-reservations-action-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M3 12a9 9 0 1 0 3-6.7" />
                  <path d="M3 3v6h6" />
                  <path d="M12 7v5l4 2" />
                </svg>
              </span>
              Check past records
            </button>
          </div>
        </section>
      </div>

      <p class="my-reservations-page-footer">2026 TechReserve Reservation Management</p>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted } from 'vue';
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

const reservationStats = computed(() => [
  {
    label: 'Active Reservations',
    value: activeReservationsCount.value,
    className: 'is-active',
    action: navigateToViewReservationList,
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>',
  },
  {
    label: 'Approved Requests',
    value: approvedRequestsCount.value,
    className: 'is-approved',
    action: navigateToApprovedRequestsLogs,
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>',
  },
  {
    label: 'Pending Requests',
    value: pendingRequestsCount.value,
    className: 'is-pending',
    action: navigateToPendingRequestsLogs,
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
  },
  {
    label: 'Completed',
    value: completedReservationsCount.value,
    className: 'is-completed',
    action: navigateToCompletedReservationsLogs,
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>',
  },
]);

onMounted(async () => {
  try {
    if (!authStore.isAuthenticated) {
      router.push({ name: 'clerkLoginPage' });
      return;
    }

    await requestStore.fetchReservations();
  } catch (error) {
    console.error('Error fetching reservations:', error);
  }
});

function navigateToCreateReservation() {
  router.push({ name: 'borrowerCreateReservationPage' });
}

function navigateToViewReservationList() {
  router.push({ name: 'borrowerViewReservationListPage' });
}

function navigateToApprovedRequestsLogs() {
  router.push({ name: 'borrowerApprovedRequestsLogsPage' });
}

function navigateToPendingRequestsLogs() {
  router.push({ name: 'borrowerPendingRequestsLogsPage' });
}

function navigateToCompletedReservationsLogs() {
  router.push({ name: 'borrowerCompletedReservationsLogsPage' });
}

function navigateToPastRecords() {
  router.push({ name: 'borrowerPastRecordsPage' });
}
</script>
