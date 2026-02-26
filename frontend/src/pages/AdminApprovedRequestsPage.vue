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

const searchQueryText = ref('');
const showingFilterValue = ref('all');
const selectedRequestRecord = ref(null);

/**
 * @constant {Array<Object>} approvedRequestsList
 * @description Static approved request records for display.
 */
const approvedRequestsList = ref([
  {
    requestIdentifier: 59327,
    requesterFullName: 'Michael Garcia',
    requesterRole: 'Faculty',
    requestSchedule: 'March 08, 2026 8:00 AM',
    requestQuantity: 1,
    requestType: 'Venue',
    requestPurpose: 'Class',
    requesterDepartment: 'College of Computer Studies and Multimedia Arts',
    requestedDate: 'February 07, 2026',
    activityTime: 'March 08, 2026',
    activityEndTime: '15:00-16:50',
    activityNameTitle: 'IT0047 Make Up Class',
    participantCount: 40,
    requestStatus: 'Approved',
    reservationSummary: [
      { itemName: 'Room F403', itemCount: 'N/A' },
    ],
    assignedPersonnel: 'Ms. Gina Espino Kenawy',
  },
  {
    requestIdentifier: 60021,
    requesterFullName: 'Maria Lourdes Cruz',
    requesterRole: 'Faculty',
    requestSchedule: 'March 10, 2026 10:00 AM',
    requestQuantity: 40,
    requestType: 'Equipment',
    requestPurpose: 'Seminar',
    requesterDepartment: 'College of Engineering',
    requestedDate: 'February 10, 2026',
    activityTime: 'March 10, 2026',
    activityEndTime: '10:00-12:00',
    activityNameTitle: 'Engineering Seminar',
    participantCount: 40,
    requestStatus: 'Approved',
    reservationSummary: [
      { itemName: 'White Monobloc Chair', itemCount: 40 },
      { itemName: 'Microphone', itemCount: 2 },
    ],
    assignedPersonnel: 'Mr. Carlos Reyes',
  },
  {
    requestIdentifier: 70145,
    requesterFullName: 'Taylor Ocampo',
    requesterRole: 'Faculty',
    requestSchedule: 'March 12, 2026 1:00 PM',
    requestQuantity: 1,
    requestType: 'Venue',
    requestPurpose: 'Workshop',
    requesterDepartment: 'IT Department',
    requestedDate: 'February 12, 2026',
    activityTime: 'March 12, 2026',
    activityEndTime: '13:00-16:00',
    activityNameTitle: 'IT Workshop',
    participantCount: 35,
    requestStatus: 'Approved',
    reservationSummary: [
      { itemName: 'F404 Multimedia Room', itemCount: 'N/A' },
    ],
    assignedPersonnel: 'Ms. Ana Torres',
  },
  {
    requestIdentifier: 44031,
    requesterFullName: 'Juan Dela Cruz',
    requesterRole: 'Student',
    requestSchedule: 'March 17, 2026 8:00 AM',
    requestQuantity: 60,
    requestType: 'Both',
    requestPurpose: 'ACM General Assembly',
    requesterDepartment: 'College of Engineering',
    requestedDate: 'February 20, 2026',
    activityTime: 'March 17, 2026',
    activityEndTime: '08:00-12:00',
    activityNameTitle: 'ACM General Assembly',
    participantCount: 60,
    requestStatus: 'Approved',
    reservationSummary: [
      { itemName: 'F401 Multipurpose Hall', itemCount: 'N/A' },
      { itemName: 'White Monobloc Chair', itemCount: 60 },
      { itemName: 'Table', itemCount: 5 },
      { itemName: 'Podium', itemCount: 1 },
    ],
    assignedPersonnel: 'Mr. Roberto Santos',
  },
  {
    requestIdentifier: 80512,
    requesterFullName: 'Alex Mendoza',
    requesterRole: 'Faculty',
    requestSchedule: 'March 20, 2026 9:00 AM',
    requestQuantity: 75,
    requestType: 'Equipment',
    requestPurpose: 'Department Event',
    requesterDepartment: 'College of Science',
    requestedDate: 'February 22, 2026',
    activityTime: 'March 20, 2026',
    activityEndTime: '09:00-17:00',
    activityNameTitle: 'Science Week Opening',
    participantCount: 200,
    requestStatus: 'Approved',
    reservationSummary: [
      { itemName: 'White Monobloc Chair', itemCount: 75 },
      { itemName: 'Table', itemCount: 15 },
      { itemName: 'Extension Cord', itemCount: 10 },
    ],
    assignedPersonnel: 'Ms. Gina Espino Kenawy',
  },
]);

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
 * @description Handles deploy/release action for a request.
 * @param {Object} requestRecord - The request record to deploy
 * @returns {void}
 */
function handleDeployRelease(requestRecord) {
  // TODO: Wire to deploy/release flow
  console.log('Deploy/Release:', requestRecord);
  selectedRequestRecord.value = null;
}

/**
 * @function handleEditWorkflow
 * @description Handles edit workflow action.
 * @param {Object} requestRecord - The request record to edit
 * @returns {void}
 */
function handleEditWorkflow(requestRecord) {
  // TODO: Wire to edit workflow flow
  console.log('Edit workflow:', requestRecord);
}

/**
 * @function handleCancelRequest
 * @description Handles cancel action for a request.
 * @param {Object} requestRecord - The request record to cancel
 * @returns {void}
 */
function handleCancelRequest(requestRecord) {
  // TODO: Wire to cancel flow
  console.log('Cancel request:', requestRecord);
  selectedRequestRecord.value = null;
}
</script>
