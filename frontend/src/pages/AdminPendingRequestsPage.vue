<!-- ===== AI GENERATED: AdminPendingRequestsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <!-- Page Heading -->
    <h2 class="pending-requests-page-heading">Pending Requests</h2>

    <!-- Toolbar: Search + Showing -->
    <div class="pending-requests-toolbar">
      <div class="pending-requests-search-group">
        <label class="pending-requests-search-label" for="requestSearchInput">Search:</label>
        <input
          id="requestSearchInput"
          v-model="searchQueryText"
          type="text"
          class="pending-requests-search-input"
          placeholder="Name"
        />
      </div>
      <div class="pending-requests-showing-group">
        <label class="pending-requests-showing-label" for="requestShowingSelect">Showing:</label>
        <select
          id="requestShowingSelect"
          v-model="showingFilterValue"
          class="pending-requests-showing-select"
        >
          <option value="all">All</option>
          <option value="venue">Venue</option>
          <option value="equipment">Equipment</option>
          <option value="both">Both</option>
        </select>
        <button class="pending-requests-sort-button" aria-label="Sort">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <polyline points="19 12 12 19 5 12"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Pending Requests Table -->
    <RequestPendingTableComponent
      :request-list="pendingRequestsList"
      :search-query-text="searchQueryText"
      @view-request-details="handleViewRequestDetails"
      @approve-request-record="handleApproveRequest"
      @reject-request-record="handleRejectRequest"
    />

    <!-- Footer -->
    <div class="pending-requests-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>

    <!-- View Request Modal -->
    <RequestViewModalComponent
      :request-record="selectedRequestRecord"
      @close-request-modal="handleCloseRequestModal"
      @approve-request-record="handleApproveRequest"
      @request-revisions-record="handleRequestRevisions"
      @reject-request-record="handleRejectRequest"
    />
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/adminPendingRequestsPage.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import RequestPendingTableComponent from '@/modules/request/components/RequestPendingTableComponent.vue';
import RequestViewModalComponent from '@/modules/request/components/RequestViewModalComponent.vue';
import '@/modules/request/components/requestViewModal.css';
import { useRequestStore } from '@/modules/request/store/requestStore.js';

const requestStore = useRequestStore();
const searchQueryText = ref('');
const showingFilterValue = ref('all');
const selectedRequestRecord = ref(null);

const pendingRequestsList = requestStore.pendingRequestsList;

/**
 * @function handleViewRequestDetails
 * @description Opens the view request modal with selected record.
 * @param {Object} requestRecord - The request record to view
 * @returns {void}
 */
function handleViewRequestDetails(requestRecord) {
  selectedRequestRecord.value = requestRecord;
}

/**
 * @function handleCloseRequestModal
 * @description Closes the request view modal.
 * @returns {void}
 */
function handleCloseRequestModal() {
  selectedRequestRecord.value = null;
}

/**
 * @function handleApproveRequest
 * @description Approves a pending request → moves to Approved Requests.
 * @param {Object} requestRecord - The request record to approve
 * @returns {void}
 */
function handleApproveRequest(requestRecord) {
  requestStore.approvePendingRequest(requestRecord);
  selectedRequestRecord.value = null;
}

/**
 * @function handleRequestRevisions
 * @description Handles request revisions action.
 * @param {Object} requestRecord - The request record needing revisions
 * @returns {void}
 */
function handleRequestRevisions(requestRecord) {
  console.log('Request revisions:', requestRecord);
}

/**
 * @function handleRejectRequest
 * @description Rejects a pending request → moves to Past Records.
 * @param {Object} requestRecord - The request record to reject
 * @returns {void}
 */
function handleRejectRequest(requestRecord) {
  requestStore.rejectPendingRequest(requestRecord);
  selectedRequestRecord.value = null;
}
</script>
