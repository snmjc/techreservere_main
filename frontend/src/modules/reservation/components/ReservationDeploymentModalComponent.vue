<!-- ===== AI GENERATED: ReservationDeploymentModalComponent ===== -->
<template>
  <div v-if="reservationRecord" class="reservation-deployment-modal-overlay" @click.self="handleCloseModal">
    <div class="reservation-deployment-modal-container">
      <!-- Close Button -->
      <button class="reservation-deployment-modal-close-button" aria-label="Close" @click="handleCloseModal">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>

      <!-- Modal Heading -->
      <h2 class="reservation-deployment-modal-heading">Process Deployment</h2>

      <!-- DEFAULT VIEW: Deployment Details -->
      <template v-if="!showReturnForm">
        <div class="reservation-deployment-modal-details">
          <!-- Top Row: Request ID + Request Type -->
          <div class="reservation-deployment-modal-row reservation-deployment-modal-row--split">
            <div class="reservation-deployment-modal-field">
              <span class="reservation-deployment-modal-label">Request ID:</span>
              <span class="reservation-deployment-modal-value">{{ reservationRecord.requestIdentifier }}</span>
            </div>
            <div class="reservation-deployment-modal-field reservation-deployment-modal-field--right">
              <span class="reservation-deployment-modal-label">Request Type:</span>
              <span class="reservation-deployment-modal-value">{{ reservationRecord.requestType }}</span>
            </div>
          </div>

          <!-- Requester Info -->
          <div class="reservation-deployment-modal-info-block">
            <div class="reservation-deployment-modal-field">
              <span class="reservation-deployment-modal-label">Requester:</span>
              <span class="reservation-deployment-modal-value">{{ reservationRecord.requesterFullName }}</span>
            </div>
            <div class="reservation-deployment-modal-field">
              <span class="reservation-deployment-modal-label">Role:</span>
              <span class="reservation-deployment-modal-value">{{ reservationRecord.requesterRole }}</span>
            </div>
            <div class="reservation-deployment-modal-field">
              <span class="reservation-deployment-modal-label">Department:</span>
              <span class="reservation-deployment-modal-value">{{ reservationRecord.requesterDepartment }}</span>
            </div>
            <div class="reservation-deployment-modal-field">
              <span class="reservation-deployment-modal-label">Requested Date:</span>
              <span class="reservation-deployment-modal-value">{{ reservationRecord.requestedDate }}</span>
            </div>
          </div>

          <!-- Divider -->
          <div class="reservation-deployment-modal-divider"></div>

          <!-- Activity Info -->
          <div class="reservation-deployment-modal-info-block">
            <div class="reservation-deployment-modal-row">
              <div class="reservation-deployment-modal-field">
                <span class="reservation-deployment-modal-label">Activity Date:</span>
                <span class="reservation-deployment-modal-value">{{ reservationRecord.activityDate }}</span>
              </div>
              <div class="reservation-deployment-modal-field" style="margin-left: 1.5rem;">
                <span class="reservation-deployment-modal-label">Activity Time:</span>
                <span class="reservation-deployment-modal-value">{{ reservationRecord.activityEndTime }}</span>
              </div>
            </div>
            <div class="reservation-deployment-modal-field">
              <span class="reservation-deployment-modal-label">Activity Name/Title:</span>
              <span class="reservation-deployment-modal-value">{{ reservationRecord.activityNameTitle }}</span>
            </div>
            <div class="reservation-deployment-modal-field">
              <span class="reservation-deployment-modal-label">Purpose:</span>
              <span class="reservation-deployment-modal-value">{{ reservationRecord.requestPurpose }}</span>
            </div>
            <div class="reservation-deployment-modal-field">
              <span class="reservation-deployment-modal-label">No. of Participants:</span>
              <span class="reservation-deployment-modal-value">{{ reservationRecord.participantCount }}</span>
            </div>
          </div>

          <!-- Divider -->
          <div class="reservation-deployment-modal-divider"></div>

          <!-- Status -->
          <div class="reservation-deployment-modal-field">
            <span class="reservation-deployment-modal-label">Status:</span>
            <span class="reservation-deployment-modal-value reservation-deployment-modal-value--status">{{ reservationRecord.deploymentStatus }}</span>
          </div>

          <!-- Reservation Summary -->
          <div class="reservation-deployment-modal-section">
            <p class="reservation-deployment-modal-section-label">Reservation Summary:</p>
            <table class="reservation-deployment-modal-summary-table">
              <tbody>
                <tr
                  v-for="summaryItem in reservationRecord.reservationSummary"
                  :key="summaryItem.itemName"
                >
                  <td class="reservation-deployment-modal-summary-cell">{{ summaryItem.itemName }}</td>
                  <td class="reservation-deployment-modal-summary-cell reservation-deployment-modal-summary-cell--count">{{ summaryItem.itemCount }}</td>
                  <td class="reservation-deployment-modal-summary-cell">
                    <span v-if="summaryItem.itemRecorded" class="reservation-deployment-recorded-badge">Recorded</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Divider -->
          <div class="reservation-deployment-modal-divider"></div>

          <!-- Assigned FO Personnel -->
          <div class="reservation-deployment-modal-field">
            <span class="reservation-deployment-modal-label">Assigned FO Personnel:</span>
            <span class="reservation-deployment-modal-value">{{ reservationRecord.assignedPersonnel }}</span>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="reservation-deployment-modal-actions">
          <button class="reservation-deployment-modal-action-button reservation-deployment-modal-action-button--return" @click="handleShowReturnForm">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            Return Confirmation
          </button>
          <button class="reservation-deployment-modal-action-button reservation-deployment-modal-action-button--report" @click="handleReportClick">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            Report
          </button>
        </div>
      </template>

      <!-- RETURN FORM VIEW: Record Equipment Return -->
      <template v-else>
        <div class="reservation-deployment-return-section">
          <h3 class="reservation-deployment-return-heading">Record Equipment Return</h3>
          <table class="reservation-deployment-return-table">
            <thead>
              <tr class="reservation-deployment-return-header-row">
                <th class="reservation-deployment-return-header-cell">Reservation Summary</th>
                <th class="reservation-deployment-return-header-cell">Return Date &amp; Time</th>
                <th class="reservation-deployment-return-header-cell">Condition upon return:</th>
                <th class="reservation-deployment-return-header-cell">Remarks</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="summaryItem in reservationRecord.reservationSummary"
                :key="summaryItem.itemName"
                class="reservation-deployment-return-body-row"
              >
                <td class="reservation-deployment-return-cell">{{ summaryItem.itemName }}</td>
                <td class="reservation-deployment-return-cell">{{ reservationRecord.returnDateTime }}</td>
                <td class="reservation-deployment-return-cell">
                  <span class="reservation-deployment-condition-badge reservation-deployment-condition-badge--good">Good</span>
                </td>
                <td class="reservation-deployment-return-cell">
                  <span class="reservation-deployment-remarks-text">Fine as is...</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Done Button -->
        <div class="reservation-deployment-modal-actions reservation-deployment-modal-actions--right">
          <button class="reservation-deployment-modal-action-button reservation-deployment-modal-action-button--done" @click="handleReturnDoneClick">
            Done
          </button>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

/**
 * @typedef {Object} ReservationDeploymentModalProps
 * @property {Object|null} reservationRecord - The active reservation record to display
 */
const props = defineProps({
  reservationRecord: {
    type: Object,
    required: false,
    default: null,
  },
});

const emit = defineEmits([
  'closeDeploymentModal',
  'confirmReturnRecord',
  'reportReservationRecord',
]);

const showReturnForm = ref(false);

/**
 * @function handleCloseModal
 * @description Emits close event and resets form view.
 * @returns {void}
 */
function handleCloseModal() {
  showReturnForm.value = false;
  emit('closeDeploymentModal');
}

/**
 * @function handleShowReturnForm
 * @description Switches to the record equipment return form view.
 * @returns {void}
 */
function handleShowReturnForm() {
  showReturnForm.value = true;
}

/**
 * @function handleReturnDoneClick
 * @description Emits confirm return event and resets form view.
 * @returns {void}
 */
function handleReturnDoneClick() {
  showReturnForm.value = false;
  emit('confirmReturnRecord', props.reservationRecord);
}

/**
 * @function handleReportClick
 * @description Emits report event.
 * @returns {void}
 */
function handleReportClick() {
  emit('reportReservationRecord', props.reservationRecord);
}
</script>
