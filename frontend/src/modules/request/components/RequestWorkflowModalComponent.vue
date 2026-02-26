<!-- ===== AI GENERATED: RequestWorkflowModalComponent ===== -->
<template>
  <div v-if="requestRecord" class="request-workflow-modal-overlay" @click.self="handleCloseModal">
    <div class="request-workflow-modal-container">
      <!-- Close Button -->
      <button class="request-workflow-modal-close-button" aria-label="Close" @click="handleCloseModal">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>

      <!-- Modal Heading -->
      <h2 class="request-workflow-modal-heading">Workflow</h2>

      <!-- Request Details -->
      <div class="request-workflow-modal-details">
        <!-- Top Row: Request ID + Request Type -->
        <div class="request-workflow-modal-row request-workflow-modal-row--split">
          <div class="request-workflow-modal-field">
            <span class="request-workflow-modal-label">Request ID:</span>
            <span class="request-workflow-modal-value">{{ requestRecord.requestIdentifier }}</span>
          </div>
          <div class="request-workflow-modal-field request-workflow-modal-field--right">
            <span class="request-workflow-modal-label">Request Type:</span>
            <span class="request-workflow-modal-value">{{ requestRecord.requestType }}</span>
          </div>
        </div>

        <!-- Requester Info Block -->
        <div class="request-workflow-modal-info-block">
          <div class="request-workflow-modal-field">
            <span class="request-workflow-modal-label">Requester:</span>
            <span class="request-workflow-modal-value">{{ requestRecord.requesterFullName }}</span>
          </div>
          <div class="request-workflow-modal-field">
            <span class="request-workflow-modal-label">Role:</span>
            <span class="request-workflow-modal-value">{{ requestRecord.requesterRole }}</span>
          </div>
          <div class="request-workflow-modal-field">
            <span class="request-workflow-modal-label">Department:</span>
            <span class="request-workflow-modal-value">{{ requestRecord.requesterDepartment }}</span>
          </div>
          <div class="request-workflow-modal-field">
            <span class="request-workflow-modal-label">Requested Date:</span>
            <span class="request-workflow-modal-value">{{ requestRecord.requestedDate }}</span>
          </div>
        </div>

        <!-- Divider -->
        <div class="request-workflow-modal-divider"></div>

        <!-- Activity Info -->
        <div class="request-workflow-modal-info-block">
          <div class="request-workflow-modal-row">
            <div class="request-workflow-modal-field">
              <span class="request-workflow-modal-label">Activity Time:</span>
              <span class="request-workflow-modal-value">{{ requestRecord.activityTime }}</span>
            </div>
            <div class="request-workflow-modal-field" style="margin-left: 1.5rem;">
              <span class="request-workflow-modal-label">Activity Time:</span>
              <span class="request-workflow-modal-value">{{ requestRecord.activityEndTime }}</span>
            </div>
          </div>
          <div class="request-workflow-modal-field">
            <span class="request-workflow-modal-label">Activity Name/Title:</span>
            <span class="request-workflow-modal-value">{{ requestRecord.activityNameTitle }}</span>
          </div>
          <div class="request-workflow-modal-field">
            <span class="request-workflow-modal-label">Purpose:</span>
            <span class="request-workflow-modal-value">{{ requestRecord.requestPurpose }}</span>
          </div>
          <div class="request-workflow-modal-field">
            <span class="request-workflow-modal-label">No. of Participants:</span>
            <span class="request-workflow-modal-value">{{ requestRecord.participantCount }}</span>
          </div>
        </div>

        <!-- Divider -->
        <div class="request-workflow-modal-divider"></div>

        <!-- Status -->
        <div class="request-workflow-modal-field">
          <span class="request-workflow-modal-label">Status:</span>
          <span class="request-workflow-modal-value request-workflow-modal-value--status">{{ requestRecord.requestStatus }}</span>
        </div>

        <!-- Reservation Summary -->
        <div class="request-workflow-modal-section">
          <p class="request-workflow-modal-section-label">Reservation Summary:</p>
          <table class="request-workflow-modal-summary-table">
            <tbody>
              <tr
                v-for="summaryItem in requestRecord.reservationSummary"
                :key="summaryItem.itemName"
              >
                <td class="request-workflow-modal-summary-cell">{{ summaryItem.itemName }}</td>
                <td class="request-workflow-modal-summary-cell request-workflow-modal-summary-cell--count">{{ summaryItem.itemCount }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Divider -->
        <div class="request-workflow-modal-divider"></div>

        <!-- Assigned FO Personnel -->
        <div class="request-workflow-modal-field">
          <span class="request-workflow-modal-label">Assigned FO Personnel:</span>
          <span class="request-workflow-modal-value">{{ requestRecord.assignedPersonnel }}</span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="request-workflow-modal-actions">
        <button class="request-workflow-modal-action-button request-workflow-modal-action-button--deploy" @click="handleDeployReleaseClick">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          Deploy/Release
        </button>
        <button class="request-workflow-modal-action-button request-workflow-modal-action-button--edit" @click="handleEditWorkflowClick">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
          Edit Workflow
        </button>
        <button class="request-workflow-modal-action-button request-workflow-modal-action-button--cancel" @click="handleCancelClick">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
          </svg>
          Cancel
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * @typedef {Object} RequestWorkflowModalProps
 * @property {Object|null} requestRecord - The approved request record to display
 */
const props = defineProps({
  requestRecord: {
    type: Object,
    required: false,
    default: null,
  },
});

const emit = defineEmits([
  'closeWorkflowModal',
  'deployReleaseRecord',
  'editWorkflowRecord',
  'cancelWorkflowRecord',
]);

/**
 * @function handleCloseModal
 * @description Emits close event to parent.
 * @returns {void}
 */
function handleCloseModal() {
  emit('closeWorkflowModal');
}

/**
 * @function handleDeployReleaseClick
 * @description Emits deploy/release event with current request record.
 * @returns {void}
 */
function handleDeployReleaseClick() {
  emit('deployReleaseRecord', props.requestRecord);
}

/**
 * @function handleEditWorkflowClick
 * @description Emits edit workflow event with current request record.
 * @returns {void}
 */
function handleEditWorkflowClick() {
  emit('editWorkflowRecord', props.requestRecord);
}

/**
 * @function handleCancelClick
 * @description Emits cancel event with current request record.
 * @returns {void}
 */
function handleCancelClick() {
  emit('cancelWorkflowRecord', props.requestRecord);
}
</script>
