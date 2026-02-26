<!-- ===== AI GENERATED: RequestPendingTableComponent ===== -->
<template>
  <div class="request-pending-table-wrapper">
    <table class="request-pending-table">
      <thead>
        <tr class="request-pending-table-header-row">
          <th class="request-pending-table-header-cell">ID</th>
          <th class="request-pending-table-header-cell">Name</th>
          <th class="request-pending-table-header-cell">Role</th>
          <th class="request-pending-table-header-cell">Schedule</th>
          <th class="request-pending-table-header-cell">Quantity</th>
          <th class="request-pending-table-header-cell">Type</th>
          <th class="request-pending-table-header-cell">Purpose</th>
          <th class="request-pending-table-header-cell">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="requestRecord in filteredRequestList"
          :key="requestRecord.requestIdentifier"
          class="request-pending-table-body-row"
        >
          <td class="request-pending-table-cell request-pending-table-cell--id">
            {{ requestRecord.requestIdentifier }}
          </td>
          <td class="request-pending-table-cell request-pending-table-cell--name">
            {{ requestRecord.requesterFullName }}
          </td>
          <td class="request-pending-table-cell request-pending-table-cell--role">
            {{ requestRecord.requesterRole }}
          </td>
          <td class="request-pending-table-cell request-pending-table-cell--schedule">
            {{ requestRecord.requestSchedule }}
          </td>
          <td class="request-pending-table-cell request-pending-table-cell--quantity">
            {{ requestRecord.requestQuantity }}
          </td>
          <td class="request-pending-table-cell request-pending-table-cell--type">
            <span
              class="request-pending-type-badge"
              :class="getTypeBadgeClass(requestRecord.requestType)"
            >
              {{ requestRecord.requestType }}
            </span>
          </td>
          <td class="request-pending-table-cell request-pending-table-cell--purpose">
            {{ requestRecord.requestPurpose }}
          </td>
          <td class="request-pending-table-cell request-pending-table-cell--actions">
            <div class="request-pending-actions-group">
              <button
                class="request-pending-action-icon request-pending-action-icon--view"
                aria-label="View Request"
                @click="handleViewRequestClick(requestRecord)"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
              <button
                class="request-pending-action-icon request-pending-action-icon--approve"
                aria-label="Approve Request"
                @click="handleApproveRequestClick(requestRecord)"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>
              </button>
              <button
                class="request-pending-action-icon request-pending-action-icon--reject"
                aria-label="Reject Request"
                @click="handleRejectRequestClick(requestRecord)"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"/>
                  <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
          </td>
        </tr>
        <tr v-if="filteredRequestList.length === 0">
          <td colspan="8" class="request-pending-table-cell request-pending-table-empty-row">
            No pending requests found.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { computed } from 'vue';

/**
 * @typedef {Object} RequestPendingTableProps
 * @property {Array<Object>} requestList - Array of pending request records
 * @property {string} searchQueryText - Current search query for filtering
 */
const props = defineProps({
  requestList: {
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
  'viewRequestDetails',
  'approveRequestRecord',
  'rejectRequestRecord',
]);

/**
 * @function filteredRequestList
 * @description Filters request list based on search query matching name or ID.
 * @returns {Array<Object>}
 */
const filteredRequestList = computed(() => {
  const queryLower = props.searchQueryText.toLowerCase().trim();
  if (!queryLower) {
    return props.requestList;
  }
  return props.requestList.filter((requestRecord) => {
    return (
      requestRecord.requesterFullName.toLowerCase().includes(queryLower) ||
      requestRecord.requestIdentifier.toString().includes(queryLower)
    );
  });
});

/**
 * @function getTypeBadgeClass
 * @description Returns CSS class for the request type badge.
 * @param {string} requestType - The request type string
 * @returns {string}
 */
function getTypeBadgeClass(requestType) {
  const typeLower = requestType.toLowerCase();
  if (typeLower === 'venue') return 'request-pending-type-badge--venue';
  if (typeLower === 'equipment') return 'request-pending-type-badge--equipment';
  if (typeLower === 'both') return 'request-pending-type-badge--both';
  return '';
}

/**
 * @function handleViewRequestClick
 * @description Emits event to parent when view icon is clicked.
 * @param {Object} requestRecord - The request record to view
 * @returns {void}
 */
function handleViewRequestClick(requestRecord) {
  emit('viewRequestDetails', requestRecord);
}

/**
 * @function handleApproveRequestClick
 * @description Emits event to parent when approve icon is clicked.
 * @param {Object} requestRecord - The request record to approve
 * @returns {void}
 */
function handleApproveRequestClick(requestRecord) {
  emit('approveRequestRecord', requestRecord);
}

/**
 * @function handleRejectRequestClick
 * @description Emits event to parent when reject icon is clicked.
 * @param {Object} requestRecord - The request record to reject
 * @returns {void}
 */
function handleRejectRequestClick(requestRecord) {
  emit('rejectRequestRecord', requestRecord);
}
</script>
