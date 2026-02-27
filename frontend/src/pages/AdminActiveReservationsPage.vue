<!-- ===== AI GENERATED: AdminActiveReservationsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <!-- Page Heading -->
    <h2 class="active-reservations-page-heading">Active Reservations</h2>

    <!-- Toolbar: Search + Showing -->
    <div class="active-reservations-toolbar">
      <div class="active-reservations-search-group">
        <label class="active-reservations-search-label" for="activeResSearchInput">Search:</label>
        <input
          id="activeResSearchInput"
          v-model="searchQueryText"
          type="text"
          class="active-reservations-search-input"
          placeholder="Name"
        />
      </div>
      <div class="active-reservations-showing-group">
        <label class="active-reservations-showing-label" for="activeResShowingSelect">Showing:</label>
        <select
          id="activeResShowingSelect"
          v-model="showingFilterValue"
          class="active-reservations-showing-select"
        >
          <option value="all">All</option>
          <option value="venue">Venue</option>
          <option value="equipment">Equipment</option>
          <option value="both">Both</option>
        </select>
        <button class="active-reservations-sort-button" aria-label="Sort">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <polyline points="19 12 12 19 5 12"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Active Reservations Table -->
    <ReservationActiveTableComponent
      :reservation-list="activeReservationsList"
      :search-query-text="searchQueryText"
      @view-deployment-details="handleViewDeploymentDetails"
      @return-confirmation="handleReturnConfirmation"
      @report-reservation="handleReportReservation"
    />

    <!-- Footer -->
    <div class="active-reservations-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>

    <!-- Process Deployment Modal -->
    <ReservationDeploymentModalComponent
      :reservation-record="selectedReservationRecord"
      @close-deployment-modal="handleCloseDeploymentModal"
      @confirm-return-record="handleConfirmReturn"
      @report-reservation-record="handleReportReservation"
    />
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/adminActiveReservationsPage.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import ReservationActiveTableComponent from '@/modules/reservation/components/ReservationActiveTableComponent.vue';
import ReservationDeploymentModalComponent from '@/modules/reservation/components/ReservationDeploymentModalComponent.vue';
import '@/modules/reservation/components/reservationDeploymentModal.css';
import { useRequestStore } from '@/modules/request/store/requestStore.js';

const requestStore = useRequestStore();
const searchQueryText = ref('');
const showingFilterValue = ref('all');
const selectedReservationRecord = ref(null);

const activeReservationsList = requestStore.activeReservationsList;

/**
 * @function handleViewDeploymentDetails
 * @description Opens the deployment modal with selected record.
 * @param {Object} reservationRecord - The reservation record to view
 * @returns {void}
 */
function handleViewDeploymentDetails(reservationRecord) {
  selectedReservationRecord.value = reservationRecord;
}

/**
 * @function handleCloseDeploymentModal
 * @description Closes the deployment modal.
 * @returns {void}
 */
function handleCloseDeploymentModal() {
  selectedReservationRecord.value = null;
}

/**
 * @function handleReturnConfirmation
 * @description Completes an active reservation → moves to Past Records as "Completed".
 * @param {Object} reservationRecord - The reservation record
 * @returns {void}
 */
function handleReturnConfirmation(reservationRecord) {
  requestStore.completeActiveReservation(reservationRecord);
  selectedReservationRecord.value = null;
}

/**
 * @function handleConfirmReturn
 * @description Completes an active reservation from the modal → moves to Past Records.
 * @param {Object} reservationRecord - The reservation record returned
 * @returns {void}
 */
function handleConfirmReturn(reservationRecord) {
  requestStore.completeActiveReservation(reservationRecord);
  selectedReservationRecord.value = null;
}

/**
 * @function handleReportReservation
 * @description Cancels an active reservation → moves to Past Records as "Cancelled".
 * @param {Object} reservationRecord - The reservation record to cancel
 * @returns {void}
 */
function handleReportReservation(reservationRecord) {
  requestStore.cancelActiveReservation(reservationRecord);
  selectedReservationRecord.value = null;
}
</script>
