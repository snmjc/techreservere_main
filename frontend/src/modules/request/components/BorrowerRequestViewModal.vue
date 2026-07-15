<template>
  <div v-if="requestRecord" class="borrower-request-modal-overlay" @click.self="emit('close')">
    <div class="borrower-request-modal">
      <button type="button" class="borrower-request-modal__close" aria-label="Close" @click="emit('close')">x</button>
      <div class="borrower-request-modal__header">
        <div>
          <p class="borrower-request-modal__eyebrow">Request Preview</p>
          <h2>{{ requestRecord.requestDisplayIdentifier }}</h2>
        </div>
        <span class="borrower-request-modal__status" :class="statusClass">{{ requestRecord.requestStatus }}</span>
      </div>

      <div class="borrower-request-modal__grid">
        <section>
          <h3>Request Details</h3>
          <dl>
            <div><dt>Type</dt><dd>{{ requestRecord.requestType }}</dd></div>
            <div><dt>Date of Request</dt><dd>{{ formatRequestDate(requestRecord.requestedDate) }}</dd></div>
            <div><dt>Start Date and Time</dt><dd>{{ formatDateTime(requestRecord.requestScheduleStart || requestRecord.activityTime) }}</dd></div>
            <div><dt>End Date and Time</dt><dd>{{ formatDateTime(requestRecord.requestScheduleEnd || requestRecord.activityEndTime) }}</dd></div>
            <div><dt>Activity</dt><dd>{{ requestRecord.activityNameTitle }}</dd></div>
            <div><dt>Purpose</dt><dd>{{ requestRecord.requestPurpose }}</dd></div>
            <div><dt>Participants</dt><dd>{{ requestRecord.participantCount }}</dd></div>
          </dl>
        </section>

        <section>
          <h3>Reservation Items</h3>
          <dl>
            <div><dt>Venue / Facility</dt><dd>{{ requestRecord.facilityName }}</dd></div>
            <div><dt>Requester</dt><dd>{{ requestRecord.requesterFullName }}</dd></div>
            <div><dt>Department</dt><dd>{{ requestRecord.requesterDepartment }}</dd></div>
          </dl>

          <div class="borrower-request-modal__summary">
            <h4>Equipment Summary</h4>
            <p v-if="!requestRecord.reservationSummary?.length">No equipment selected.</p>
            <ul v-else>
              <li v-for="summaryItem in requestRecord.reservationSummary" :key="summaryItem.itemName">
                <span>{{ summaryItem.itemName }}</span>
                <strong>{{ summaryItem.itemCount }}</strong>
              </li>
            </ul>
          </div>
        </section>
      </div>

      <section class="borrower-request-modal__footer-block">
        <h3>Uploaded Documents</h3>
        <p v-if="!requestRecord.uploadedDocuments?.length">No uploaded documents.</p>
        <ul v-else class="borrower-request-modal__documents">
          <li v-for="documentFile in requestRecord.uploadedDocuments" :key="documentFile.fileName">{{ documentFile.fileName }}</li>
        </ul>
      </section>

      <section class="borrower-request-modal__footer-block">
        <h3>Borrower Remarks</h3>
        <p>{{ borrowerRemarks }}</p>
      </section>

      <section v-if="statusNotes" class="borrower-request-modal__footer-block">
        <h3>Status Notes</h3>
        <p>{{ statusNotes }}</p>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  requestRecord: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close']);

const statusClass = computed(() => {
  const status = String(props.requestRecord?.requestStatus || '').toLowerCase();
  if (status.includes('approved')) return 'is-approved';
  if (status.includes('cancel')) return 'is-cancelled';
  if (status.includes('reject')) return 'is-rejected';
  if (status.includes('complete')) return 'is-completed';
  return 'is-pending';
});

const statusNotes = computed(() => {
  return String(props.requestRecord?.remarks || props.requestRecord?.cancellationReason || '').trim();
});

const borrowerRemarks = computed(() => {
  return String(props.requestRecord?.borrowerRemarks || '').trim() || 'No remarks added.';
});

function formatDateTime(value) {
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return value || 'N/A';
  }

  return new Intl.DateTimeFormat('en-PH', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  }).format(parsed);
}

function formatRequestDate(value) {
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return value || 'N/A';
  }

  return new Intl.DateTimeFormat('en-PH', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(parsed);
}
</script>
