<!-- ===== AI GENERATED: ReservationActiveTableComponent ===== -->
<template>
  <div class="reservation-active-table-wrapper">
    <table class="reservation-active-table">
      <thead>
        <tr class="reservation-active-table-header-row">
          <th class="reservation-active-table-header-cell">ID</th>
          <th class="reservation-active-table-header-cell">Name</th>
          <th class="reservation-active-table-header-cell">Role</th>
          <th class="reservation-active-table-header-cell">Schedule</th>
          <th class="reservation-active-table-header-cell">Facility</th>
          <th class="reservation-active-table-header-cell">Quantity</th>
          <th class="reservation-active-table-header-cell">Type</th>
          <th class="reservation-active-table-header-cell">Purpose</th>
          <th class="reservation-active-table-header-cell">Action</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="reservationRecord in filteredReservationList"
          :key="reservationRecord.requestIdentifier + reservationRecord.requesterFullName"
          class="reservation-active-table-body-row"
        >
          <td class="reservation-active-table-cell reservation-active-table-cell--id">
            {{ reservationRecord.requestIdentifier }}
          </td>
          <td class="reservation-active-table-cell reservation-active-table-cell--name">
            {{ reservationRecord.requesterFullName }}
          </td>
          <td class="reservation-active-table-cell reservation-active-table-cell--role">
            {{ reservationRecord.requesterRole }}
          </td>
          <td class="reservation-active-table-cell reservation-active-table-cell--schedule">
            {{ reservationRecord.requestSchedule }}
          </td>
          <td class="reservation-active-table-cell reservation-active-table-cell--facility">
            {{ reservationRecord.facilityName }}
          </td>
          <td class="reservation-active-table-cell reservation-active-table-cell--quantity">
            {{ reservationRecord.requestQuantity }}
          </td>
          <td class="reservation-active-table-cell reservation-active-table-cell--type">
            <span
              class="reservation-active-type-badge"
              :class="getTypeBadgeClass(reservationRecord.requestType)"
            >
              {{ reservationRecord.requestType }}
            </span>
          </td>
          <td class="reservation-active-table-cell reservation-active-table-cell--purpose">
            {{ reservationRecord.requestPurpose }}
          </td>
          <td class="reservation-active-table-cell reservation-active-table-cell--actions">
            <div class="reservation-active-actions-group">
              <button
                class="reservation-active-action-icon reservation-active-action-icon--view"
                aria-label="View Deployment"
                @click="handleViewDeploymentClick(reservationRecord)"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
              <button
                class="reservation-active-action-icon reservation-active-action-icon--return"
                aria-label="Return Confirmation"
                @click="handleReturnConfirmationClick(reservationRecord)"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>
              </button>
              <button
                class="reservation-active-action-icon reservation-active-action-icon--report"
                aria-label="Report"
                @click="handleReportClick(reservationRecord)"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/>
                  <line x1="12" y1="8" x2="12" y2="12"/>
                  <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
              </button>
            </div>
          </td>
        </tr>
        <tr v-if="filteredReservationList.length === 0">
          <td colspan="9" class="reservation-active-table-cell reservation-active-table-empty-row">
            No active reservations found.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { computed } from 'vue';

/**
 * @typedef {Object} ReservationActiveTableProps
 * @property {Array<Object>} reservationList - Array of active reservation records
 * @property {string} searchQueryText - Current search query for filtering
 */
const props = defineProps({
  reservationList: {
    type: Array,
    required: true,
  },
  searchQueryText: {
    type: String,
    required: false,
    default: '',
  },
});

const emit = defineEmits([
  'viewDeploymentDetails',
  'returnConfirmation',
  'reportReservation',
]);

/**
 * @function filteredReservationList
 * @description Filters reservation list based on search query matching name or ID.
 * @returns {Array<Object>}
 */
const filteredReservationList = computed(() => {
  const queryLower = props.searchQueryText.toLowerCase().trim();
  if (!queryLower) {
    return props.reservationList;
  }
  return props.reservationList.filter((reservationRecord) => {
    return (
      reservationRecord.requesterFullName.toLowerCase().includes(queryLower) ||
      reservationRecord.requestIdentifier.toString().includes(queryLower)
    );
  });
});

/**
 * @function getTypeBadgeClass
 * @description Returns CSS class for the reservation type badge.
 * @param {string} requestType - The request type string
 * @returns {string}
 */
function getTypeBadgeClass(requestType) {
  const typeLower = requestType.toLowerCase();
  if (typeLower === 'venue') return 'reservation-active-type-badge--venue';
  if (typeLower === 'equipment') return 'reservation-active-type-badge--equipment';
  if (typeLower === 'both') return 'reservation-active-type-badge--both';
  return '';
}

/**
 * @function handleViewDeploymentClick
 * @description Emits view deployment event.
 * @param {Object} reservationRecord - The reservation record to view
 * @returns {void}
 */
function handleViewDeploymentClick(reservationRecord) {
  emit('viewDeploymentDetails', reservationRecord);
}

/**
 * @function handleReturnConfirmationClick
 * @description Emits return confirmation event.
 * @param {Object} reservationRecord - The reservation record
 * @returns {void}
 */
function handleReturnConfirmationClick(reservationRecord) {
  emit('returnConfirmation', reservationRecord);
}

/**
 * @function handleReportClick
 * @description Emits report event.
 * @param {Object} reservationRecord - The reservation record to report
 * @returns {void}
 */
function handleReportClick(reservationRecord) {
  emit('reportReservation', reservationRecord);
}
</script>
