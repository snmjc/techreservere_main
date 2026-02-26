<!-- ===== AI GENERATED: RequestViewModalComponent ===== -->
<template>
  <div v-if="requestRecord" class="request-view-modal-overlay" @click.self="handleCloseModal">
    <div class="request-view-modal-container">
      <!-- Close Button -->
      <button class="request-view-modal-close-button" aria-label="Close" @click="handleCloseModal">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>

      <!-- Modal Heading -->
      <h2 class="request-view-modal-heading">View Request</h2>

      <!-- Request Details -->
      <div class="request-view-modal-details">
        <!-- Top Row: Request ID + Request Type -->
        <div class="request-view-modal-row request-view-modal-row--split">
          <div class="request-view-modal-field">
            <span class="request-view-modal-label">Request ID:</span>
            <span class="request-view-modal-value">{{ requestRecord.requestIdentifier }}</span>
          </div>
          <div class="request-view-modal-field request-view-modal-field--right">
            <span class="request-view-modal-label">Request Type:</span>
            <span class="request-view-modal-value">{{ requestRecord.requestType }}</span>
          </div>
        </div>

        <!-- Requester Info -->
        <div class="request-view-modal-row">
          <div class="request-view-modal-field">
            <span class="request-view-modal-label">Requester:</span>
            <span class="request-view-modal-value">{{ requestRecord.requesterFullName }}</span>
          </div>
        </div>
        <div class="request-view-modal-row">
          <div class="request-view-modal-field">
            <span class="request-view-modal-label">Department:</span>
            <span class="request-view-modal-value">{{ requestRecord.requesterDepartment }}</span>
          </div>
        </div>
        <div class="request-view-modal-row">
          <div class="request-view-modal-field">
            <span class="request-view-modal-label">Requested Date:</span>
            <span class="request-view-modal-value">{{ requestRecord.requestedDate }}</span>
          </div>
        </div>

        <!-- Divider -->
        <div class="request-view-modal-divider"></div>

        <!-- Activity Info -->
        <div class="request-view-modal-row">
          <div class="request-view-modal-field">
            <span class="request-view-modal-label">Activity Time:</span>
            <span class="request-view-modal-value">{{ requestRecord.activityTime }}</span>
          </div>
        </div>
        <div class="request-view-modal-row">
          <div class="request-view-modal-field">
            <span class="request-view-modal-label">Activity Name/Title:</span>
            <span class="request-view-modal-value">{{ requestRecord.activityNameTitle }}</span>
          </div>
        </div>
        <div class="request-view-modal-row">
          <div class="request-view-modal-field">
            <span class="request-view-modal-label">Purpose:</span>
            <span class="request-view-modal-value">{{ requestRecord.requestPurpose }}</span>
          </div>
        </div>
        <div class="request-view-modal-row">
          <div class="request-view-modal-field">
            <span class="request-view-modal-label">No. of Participants:</span>
            <span class="request-view-modal-value">{{ requestRecord.participantCount }}</span>
          </div>
        </div>

        <!-- Divider -->
        <div class="request-view-modal-divider"></div>

        <!-- Status -->
        <div class="request-view-modal-row">
          <div class="request-view-modal-field">
            <span class="request-view-modal-label">Status:</span>
            <span class="request-view-modal-value request-view-modal-value--status">{{ requestRecord.requestStatus }}</span>
          </div>
        </div>

        <!-- Reservation Summary + Uploaded Docs -->
        <div class="request-view-modal-row request-view-modal-row--split-bottom">
          <!-- Reservation Summary Table -->
          <div class="request-view-modal-reservation-summary">
            <p class="request-view-modal-section-label">Reservation Summary:</p>
            <table class="request-view-modal-summary-table">
              <tbody>
                <tr
                  v-for="summaryItem in requestRecord.reservationSummary"
                  :key="summaryItem.itemName"
                >
                  <td class="request-view-modal-summary-cell">{{ summaryItem.itemName }}</td>
                  <td class="request-view-modal-summary-cell request-view-modal-summary-cell--count">{{ summaryItem.itemCount }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Uploaded Documents -->
          <div class="request-view-modal-uploaded-docs">
            <p class="request-view-modal-section-label">View Uploaded Documents</p>
            <ul class="request-view-modal-doc-list">
              <li
                v-for="documentFile in requestRecord.uploadedDocuments"
                :key="documentFile.fileName"
                class="request-view-modal-doc-item"
              >
                <span class="request-view-modal-doc-dot"></span>
                <span class="request-view-modal-doc-name">{{ documentFile.fileName }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="request-view-modal-actions">
        <button class="request-view-modal-action-button request-view-modal-action-button--approve" @click="handleApproveClick">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          Approve Request
        </button>
        <button class="request-view-modal-action-button request-view-modal-action-button--revisions" @click="handleRequestRevisionsClick">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="1 4 1 10 7 10"/>
            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
          </svg>
          Request Revisions
        </button>
        <button class="request-view-modal-action-button request-view-modal-action-button--reject" @click="handleRejectClick">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
          </svg>
          Reject Request
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * @typedef {Object} RequestViewModalProps
 * @property {Object|null} requestRecord - The request record to display
 */
const props = defineProps({
  requestRecord: {
    type: Object,
    required: false,
    default: null,
  },
});

const emit = defineEmits([
  'closeRequestModal',
  'approveRequestRecord',
  'requestRevisionsRecord',
  'rejectRequestRecord',
]);

/**
 * @function handleCloseModal
 * @description Emits close event to parent.
 * @returns {void}
 */
function handleCloseModal() {
  emit('closeRequestModal');
}

/**
 * @function handleApproveClick
 * @description Emits approve event with current request record.
 * @returns {void}
 */
function handleApproveClick() {
  emit('approveRequestRecord', props.requestRecord);
}

/**
 * @function handleRequestRevisionsClick
 * @description Emits request revisions event with current request record.
 * @returns {void}
 */
function handleRequestRevisionsClick() {
  emit('requestRevisionsRecord', props.requestRecord);
}

/**
 * @function handleRejectClick
 * @description Emits reject event with current request record.
 * @returns {void}
 */
function handleRejectClick() {
  emit('rejectRequestRecord', props.requestRecord);
}
</script>
