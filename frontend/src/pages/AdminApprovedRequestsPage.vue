<!-- ===== AI GENERATED: AdminApprovedRequestsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <!-- Page Heading -->
    <h2 class="approved-requests-page-heading">Approved Requests</h2>

    <!-- Toolbar: Search + Showing -->
    <div class="approved-requests-toolbar">
      <div class="approved-requests-search-group">
        <label class="approved-requests-search-label" for="approvedSearchInput">Search:</label>
        <input
          id="approvedSearchInput"
          v-model="searchQueryText"
          type="text"
          class="approved-requests-search-input"
          placeholder="Name"
        />
      </div>
      <div class="approved-requests-showing-group">
        <label class="approved-requests-showing-label" for="approvedShowingSelect">Showing:</label>
        <select
          id="approvedShowingSelect"
          v-model="showingFilterValue"
          class="approved-requests-showing-select"
        >
          <option value="all">All</option>
          <option value="venue">Venue</option>
          <option value="equipment">Equipment</option>
          <option value="both">Both</option>
        </select>
        <button class="approved-requests-sort-button" aria-label="Sort">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <polyline points="19 12 12 19 5 12"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Approved Requests Table -->
    <RequestApprovedTableComponent
      :request-list="approvedRequestsList"
      :search-query-text="searchQueryText"
      @view-workflow-details="handleViewWorkflowDetails"
      @deploy-release-record="handleDeployRelease"
      @cancel-request-record="handleCancelRequest"
    />

    <!-- Footer -->
    <div class="approved-requests-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>

    <!-- Workflow Modal -->
    <RequestWorkflowModalComponent
      :request-record="selectedRequestRecord"
      @close-workflow-modal="handleCloseWorkflowModal"
      @deploy-release-record="handleDeployRelease"
      @edit-workflow-record="handleEditWorkflow"
      @cancel-workflow-record="handleCancelRequest"
    />
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/adminApprovedRequestsPage.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import RequestApprovedTableComponent from '@/modules/request/components/RequestApprovedTableComponent.vue';
import RequestWorkflowModalComponent from '@/modules/request/components/RequestWorkflowModalComponent.vue';
import '@/modules/request/components/requestWorkflowModal.css';
import { useRequestStore } from '@/modules/request/store/requestStore.js';

const requestStore = useRequestStore();
const searchQueryText = ref('');
const showingFilterValue = ref('all');
const selectedRequestRecord = ref(null);

const approvedRequestsList = requestStore.approvedRequestsList;

/**
 * @function handleViewWorkflowDetails
 * @description Opens the workflow modal with selected record.
 * @param {Object} requestRecord - The request record to view
 * @returns {void}
 */
function handleViewWorkflowDetails(requestRecord) {
  selectedRequestRecord.value = requestRecord;
}

/**
 * @function handleCloseWorkflowModal
 * @description Closes the workflow modal.
 * @returns {void}
 */
function handleCloseWorkflowModal() {
  selectedRequestRecord.value = null;
}

/**
 * @function handleDeployRelease
 * @description Deploys/releases an approved request → moves to Active Reservations.
 * @param {Object} requestRecord - The request record to deploy
 * @returns {void}
 */
function handleDeployRelease(requestRecord) {
  requestStore.deployApprovedRequest(requestRecord);
  selectedRequestRecord.value = null;
}

/**
 * @function handleEditWorkflow
 * @description Handles edit workflow action.
 * @param {Object} requestRecord - The request record to edit
 * @returns {void}
 */
function handleEditWorkflow(requestRecord) {
  console.log('Edit workflow:', requestRecord);
}

/**
 * @function handleCancelRequest
 * @description Cancels an approved request → moves to Past Records.
 * @param {Object} requestRecord - The request record to cancel
 * @returns {void}
 */
function handleCancelRequest(requestRecord) {
  requestStore.cancelApprovedRequest(requestRecord);
  selectedRequestRecord.value = null;
}
</script>
