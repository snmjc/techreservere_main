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

const searchQueryText = ref('');
const showingFilterValue = ref('all');
const selectedReservationRecord = ref(null);

/**
 * @constant {Array<Object>} activeReservationsList
 * @description Static active reservation records for display.
 */
const activeReservationsList = ref([
  {
    requestIdentifier: 59327,
    requesterFullName: 'Michael Garcia',
    requesterRole: 'Faculty',
    requestSchedule: 'March 08, 2026',
    facilityName: 'Room F403',
    requestQuantity: 1,
    requestType: 'Venue',
    requestPurpose: 'Class',
    requesterDepartment: 'College of Computer Studies and Multimedia Arts',
    requestedDate: 'February 07, 2026',
    activityDate: 'March 08, 2026',
    activityEndTime: '15:00-16:50',
    activityNameTitle: 'IT0047 Make Up Class',
    participantCount: 40,
    deploymentStatus: 'Deployed/Released',
    reservationSummary: [
      { itemName: 'Room F403', itemCount: 'N/A', itemRecorded: true },
    ],
    assignedPersonnel: 'Ms. Gina Espino Kenawy',
    returnDateTime: 'March 08, 2026 15:00-16:50',
  },
  {
    requestIdentifier: 60021,
    requesterFullName: 'Maria Lourdes Cruz',
    requesterRole: 'Faculty',
    requestSchedule: 'March 10, 2026',
    facilityName: 'N/A',
    requestQuantity: 40,
    requestType: 'Equipment',
    requestPurpose: 'Seminar',
    requesterDepartment: 'College of Engineering',
    requestedDate: 'February 10, 2026',
    activityDate: 'March 10, 2026',
    activityEndTime: '10:00-12:00',
    activityNameTitle: 'Engineering Seminar',
    participantCount: 40,
    deploymentStatus: 'Deployed/Released',
    reservationSummary: [
      { itemName: 'White Monobloc Chair', itemCount: 40, itemRecorded: false },
      { itemName: 'Microphone', itemCount: 2, itemRecorded: false },
    ],
    assignedPersonnel: 'Mr. Carlos Reyes',
    returnDateTime: 'March 10, 2026 10:00-12:00',
  },
  {
    requestIdentifier: 44031,
    requesterFullName: 'Juan Dela Cruz',
    requesterRole: 'Student',
    requestSchedule: 'March 17, 2026',
    facilityName: 'F401 Hall',
    requestQuantity: 60,
    requestType: 'Both',
    requestPurpose: 'ACM General Assembly',
    requesterDepartment: 'College of Engineering',
    requestedDate: 'February 20, 2026',
    activityDate: 'March 17, 2026',
    activityEndTime: '08:00-12:00',
    activityNameTitle: 'ACM General Assembly',
    participantCount: 60,
    deploymentStatus: 'Deployed/Released',
    reservationSummary: [
      { itemName: 'F401 Multipurpose Hall', itemCount: 'N/A', itemRecorded: true },
      { itemName: 'White Monobloc Chair', itemCount: 60, itemRecorded: false },
      { itemName: 'Table', itemCount: 5, itemRecorded: false },
      { itemName: 'Podium', itemCount: 1, itemRecorded: false },
    ],
    assignedPersonnel: 'Mr. Roberto Santos',
    returnDateTime: 'March 17, 2026 08:00-12:00',
  },
  {
    requestIdentifier: 27001,
    requesterFullName: 'Carlo Santos',
    requesterRole: 'Student',
    requestSchedule: 'March 18, 2026',
    facilityName: 'Utility',
    requestQuantity: 20,
    requestType: 'Equipment',
    requestPurpose: 'Club Meeting',
    requesterDepartment: 'Student Affairs',
    requestedDate: 'February 22, 2026',
    activityDate: 'March 18, 2026',
    activityEndTime: '14:00-16:00',
    activityNameTitle: 'Robotics Club Meeting',
    participantCount: 20,
    deploymentStatus: 'Deployed/Released',
    reservationSummary: [
      { itemName: 'White Monobloc Chair', itemCount: 20, itemRecorded: false },
      { itemName: 'Table', itemCount: 4, itemRecorded: false },
    ],
    assignedPersonnel: 'Ms. Ana Torres',
    returnDateTime: 'March 18, 2026 14:00-16:00',
  },
]);

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
 * @description Opens the deployment modal in return confirmation view.
 * @param {Object} reservationRecord - The reservation record
 * @returns {void}
 */
function handleReturnConfirmation(reservationRecord) {
  selectedReservationRecord.value = reservationRecord;
}

/**
 * @function handleConfirmReturn
 * @description Handles confirmed return from the modal.
 * @param {Object} reservationRecord - The reservation record returned
 * @returns {void}
 */
function handleConfirmReturn(reservationRecord) {
  // TODO: Wire to return confirmation flow
  console.log('Return confirmed:', reservationRecord);
  selectedReservationRecord.value = null;
}

/**
 * @function handleReportReservation
 * @description Handles report action for a reservation.
 * @param {Object} reservationRecord - The reservation record to report
 * @returns {void}
 */
function handleReportReservation(reservationRecord) {
  // TODO: Wire to report flow
  console.log('Report reservation:', reservationRecord);
}
</script>
