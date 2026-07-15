<!-- ===== AI GENERATED: RequestViewModalComponent ===== -->
<template>
  <div v-if="requestRecord" class="request-view-modal-overlay" @click.self="handleCloseModal">
    <div class="request-view-modal-container">
      <button class="request-view-modal-close-button" aria-label="Close" @click="handleCloseModal">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>

      <h2 class="request-view-modal-heading">Request Preview</h2>

      <div class="request-view-modal-details">
        <div class="request-view-modal-topline">
          <div class="request-view-modal-topline-copy">
            <span class="request-view-modal-kicker">Reservation ID</span>
            <strong>{{ requestRecord.reservationCode || requestRecord.requestDisplayIdentifier }}</strong>
            <small>{{ requestRecord.requestStatus }}</small>
          </div>

          <span class="request-view-modal-status-badge">{{ requestRecord.requestStatus }}</span>
        </div>

        <div class="request-view-modal-divider"></div>

        <div class="request-view-modal-grid">
          <div class="request-view-modal-panel">
            <span class="request-view-modal-label">Date of Request</span>
            <strong class="request-view-modal-emphasis">{{ formatRequestDate(requestRecord.requestedDate) }}</strong>
          </div>

          <div class="request-view-modal-panel">
            <span class="request-view-modal-label">Start Date and Time</span>
            <strong class="request-view-modal-emphasis">{{ formatDateTime(requestRecord.requestScheduleStart || requestRecord.activityTime) }}</strong>
          </div>

          <div class="request-view-modal-panel">
            <span class="request-view-modal-label">End Date and Time</span>
            <strong class="request-view-modal-emphasis">{{ formatDateTime(requestRecord.requestScheduleEnd || requestRecord.activityEndTime) }}</strong>
          </div>

          <div class="request-view-modal-panel">
            <span class="request-view-modal-label">Activity Title</span>
            <strong class="request-view-modal-emphasis">{{ requestRecord.activityTitle || requestRecord.activityNameTitle }}</strong>
          </div>

          <div class="request-view-modal-panel">
            <span class="request-view-modal-label">Type of Activity</span>
            <strong class="request-view-modal-emphasis">{{ requestRecord.typeOfActivity || 'N/A' }}</strong>
          </div>

          <div class="request-view-modal-panel">
            <span class="request-view-modal-label">Number of Participants</span>
            <strong class="request-view-modal-emphasis">{{ requestRecord.participantCount }}</strong>
          </div>

          <div class="request-view-modal-panel">
            <span class="request-view-modal-label">Request Type</span>
            <strong class="request-view-modal-emphasis">{{ requestRecord.requestType }}</strong>
          </div>

          <div class="request-view-modal-panel request-view-modal-panel--wide">
            <span class="request-view-modal-label">Purpose</span>
            <p class="request-view-modal-copy">{{ requestRecord.requestPurpose }}</p>
          </div>
        </div>

        <div class="request-view-modal-divider"></div>

        <div class="request-view-modal-section">
          <p class="request-view-modal-section-label">Facilities and/or Equipment Reserved</p>

          <div v-if="requestRecord.reservedResources?.length" class="request-view-modal-resource-grid">
            <article
              v-for="resource in requestRecord.reservedResources"
              :key="`${resource.resourceType}-${resource.resourceName}`"
              class="request-view-modal-resource-card"
            >
              <span class="request-view-modal-resource-type">{{ resource.resourceType }}</span>
              <strong>{{ resource.resourceName }}</strong>
              <small>Qty: {{ resource.resourceCount }}</small>
            </article>
          </div>

          <div v-else class="request-view-modal-empty-state">
            No reserved facility or equipment listed for this request.
          </div>
        </div>

        <div class="request-view-modal-divider"></div>

        <div class="request-view-modal-section">
          <p class="request-view-modal-section-label">Status Notes</p>
          <div class="request-view-modal-notes-box">
            {{ statusNotes || 'No remarks added yet.' }}
          </div>
        </div>

        <div class="request-view-modal-divider"></div>

        <div class="request-view-modal-section">
          <p class="request-view-modal-section-label">Supporting Documents</p>

          <div v-if="requestRecord.uploadedDocuments?.length" class="request-view-modal-doc-preview-grid">
            <article
              v-for="documentFile in requestRecord.uploadedDocuments"
              :key="documentFile.fileName"
              class="request-view-modal-doc-card"
            >
              <span class="request-view-modal-doc-badge">{{ documentFile.previewLabel || 'Document' }}</span>
              <strong class="request-view-modal-doc-name">{{ documentFile.fileName }}</strong>
              <small>Preview metadata available</small>
            </article>
          </div>

          <div v-else class="request-view-modal-empty-state">
            No supporting documents uploaded.
          </div>
        </div>
      </div>

      <div v-if="showActionButtons" class="request-view-modal-actions">
        <button
          class="request-view-modal-action-button request-view-modal-action-button--approve"
          :class="{ 'request-view-modal-action-button--disabled': reviewActionsDisabled }"
          :disabled="reviewActionsDisabled"
          @click="handleApproveClick"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          Approve Request
        </button>
        <button
          v-if="showRevisionsButton"
          class="request-view-modal-action-button request-view-modal-action-button--revisions"
          @click="handleRequestRevisionsClick"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="1 4 1 10 7 10"/>
            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
          </svg>
          Request Revisions
        </button>
        <button
          class="request-view-modal-action-button request-view-modal-action-button--reject"
          :class="{ 'request-view-modal-action-button--disabled': reviewActionsDisabled }"
          :disabled="reviewActionsDisabled"
          @click="handleRejectClick"
        >
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
import { formatDisplayDate, formatDisplayDateTime } from '@/shared/utils/dateTimeDisplay.js';
import { computed } from 'vue';

const props = defineProps({
  requestRecord: {
    type: Object,
    required: false,
    default: null,
  },
  showActionButtons: {
    type: Boolean,
    required: false,
    default: true,
  },
  showRevisionsButton: {
    type: Boolean,
    required: false,
    default: true,
  },
  reviewActionsDisabled: {
    type: Boolean,
    required: false,
    default: false,
  },
});

const emit = defineEmits([
  'closeRequestModal',
  'approveRequestRecord',
  'requestRevisionsRecord',
  'rejectRequestRecord',
]);

const statusNotes = computed(() => {
  return String(props.requestRecord?.remarks || props.requestRecord?.cancellationReason || '').trim();
});

function handleCloseModal() {
  emit('closeRequestModal');
}

function handleApproveClick() {
  emit('approveRequestRecord', props.requestRecord);
}

function handleRequestRevisionsClick() {
  emit('requestRevisionsRecord', props.requestRecord);
}

function handleRejectClick() {
  emit('rejectRequestRecord', props.requestRecord);
}

function formatDateTime(value) {
  return formatDisplayDateTime(value);
}

function formatRequestDate(value) {
  return formatDisplayDate(value);
}
</script>
