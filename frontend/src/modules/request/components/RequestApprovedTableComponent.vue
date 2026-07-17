<!-- ===== AI GENERATED: RequestApprovedTableComponent ===== -->
<template>
  <div class="request-approved-table-wrapper">
    <table class="request-approved-table">
      <thead>
        <tr class="request-approved-table-header-row">
          <th class="request-approved-table-header-cell">ID</th>
          <th class="request-approved-table-header-cell">Name</th>
          <th class="request-approved-table-header-cell">Role</th>
          <th class="request-approved-table-header-cell">Date Requested</th>
          <th class="request-approved-table-header-cell">Schedule</th>
          <th class="request-approved-table-header-cell">Quantity</th>
          <th class="request-approved-table-header-cell">Type</th>
          <th class="request-approved-table-header-cell">Action</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="requestRecord in requestList"
          :key="requestRecord.requestIdentifier + requestRecord.requesterFullName"
          class="request-approved-table-body-row"
        >
          <td class="request-approved-table-cell request-approved-table-cell--id">
            {{ requestRecord.requestIdentifier }}
          </td>
          <td class="request-approved-table-cell request-approved-table-cell--name">
            {{ requestRecord.requesterFullName }}
          </td>
          <td class="request-approved-table-cell request-approved-table-cell--role">
            {{ requestRecord.requesterRole }}
          </td>
          <td class="request-approved-table-cell request-approved-table-cell--requested-date">
            {{ formatRequestedDate(requestRecord.requestedDate) }}
          </td>
          <td class="request-approved-table-cell request-approved-table-cell--schedule">
            {{ requestRecord.requestSchedule }}
          </td>
          <td class="request-approved-table-cell request-approved-table-cell--quantity">
            {{ requestRecord.requestQuantity }}
          </td>
          <td class="request-approved-table-cell request-approved-table-cell--type">
            <span
              class="request-approved-type-badge"
              :class="getTypeBadgeClass(requestRecord.requestType)"
            >
              {{ requestRecord.requestType }}
            </span>
          </td>
          <td class="request-approved-table-cell request-approved-table-cell--actions">
            <div class="request-approved-actions-group">
              <button
                class="request-approved-action-icon request-approved-action-icon--view"
                aria-label="View Workflow"
                @click="handleViewWorkflowClick(requestRecord)"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
              <button
                class="request-approved-action-icon request-approved-action-icon--edit"
                aria-label="Edit Workflow"
                @click="handleEditWorkflowClick(requestRecord)"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
              </button>
              <button
                class="request-approved-action-icon request-approved-action-icon--deploy"
                aria-label="Deploy/Release"
                @click="handleDeployReleaseClick(requestRecord)"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>
              </button>
              <button
                class="request-approved-action-icon request-approved-action-icon--cancel"
                aria-label="Cancel"
                @click="handleCancelRequestClick(requestRecord)"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"/>
                  <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
          </td>
        </tr>
        <tr v-if="requestList.length === 0">
          <td colspan="8" class="request-approved-table-cell request-approved-table-empty-row">
            No approved requests found.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
/**
 * @typedef {Object} RequestApprovedTableProps
 * @property {Array<Object>} requestList - Array of approved request records
 * @property {string} searchQueryText - Current search query for filtering
 */
defineProps({
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
  'viewWorkflowDetails',
  'editWorkflowRecord',
  'deployReleaseRecord',
  'cancelRequestRecord',
]);

/**
 * @function getTypeBadgeClass
 * @description Returns CSS class for the request type badge.
 * @param {string} requestType - The request type string
 * @returns {string}
 */
function getTypeBadgeClass(requestType) {
  const typeLower = requestType?.toLowerCase() || '';
  if (typeLower === 'venue') return 'request-approved-type-badge--venue';
  if (typeLower === 'equipment') return 'request-approved-type-badge--equipment';
  if (typeLower === 'both') return 'request-approved-type-badge--both';
  return '';
}

/**
 * @function handleViewWorkflowClick
 * @description Emits event to parent when view icon is clicked.
 * @param {Object} requestRecord - The request record to view
 * @returns {void}
 */
function handleViewWorkflowClick(requestRecord) {
  emit('viewWorkflowDetails', requestRecord);
}

/**
 * @function handleDeployReleaseClick
 * @description Emits event to parent when deploy icon is clicked.
 * @param {Object} requestRecord - The request record to deploy
 * @returns {void}
 */
function handleDeployReleaseClick(requestRecord) {
  emit('deployReleaseRecord', requestRecord);
}

function handleEditWorkflowClick(requestRecord) {
  emit('editWorkflowRecord', requestRecord);
}

/**
 * @function handleCancelRequestClick
 * @description Emits event to parent when cancel icon is clicked.
 * @param {Object} requestRecord - The request record to cancel
 * @returns {void}
 */
function handleCancelRequestClick(requestRecord) {
  emit('cancelRequestRecord', requestRecord);
}

function formatRequestedDate(value) {
  const parsedDate = new Date(value);
  if (Number.isNaN(parsedDate.getTime())) {
    return value || 'N/A';
  }

  return new Intl.DateTimeFormat('en-PH', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(parsedDate);
}
</script>
