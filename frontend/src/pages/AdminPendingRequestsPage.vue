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

const searchQueryText = ref('');
const showingFilterValue = ref('all');
const selectedRequestRecord = ref(null);

/**
 * @constant {Array<Object>} pendingRequestsList
 * @description Static pending request records for display.
 */
const pendingRequestsList = ref([
  {
    requestIdentifier: 44031,
    requesterFullName: 'Juan Dela Cruz',
    requesterRole: 'Student',
    requestSchedule: 'March 17, 2026 8:00 AM',
    requestQuantity: 60,
    requestType: 'Equipment',
    requestPurpose: 'ACM General Assembly',
    requesterDepartment: 'College of Engineering',
    requestedDate: 'February 20, 2026',
    activityTime: 'March 17, 2026',
    activityNameTitle: 'ACM General Assembly',
    participantCount: 60,
    requestStatus: 'Pending for Approval',
    reservationSummary: [
      { itemName: 'White Monobloc Chair', itemCount: 60 },
      { itemName: 'Table', itemCount: 5 },
      { itemName: 'Podium', itemCount: 1 },
    ],
    uploadedDocuments: [
      { fileName: 'ACM_APPandAPF.pdf' },
      { fileName: 'ACM_FloorPlan.pdf' },
    ],
  },
  {
    requestIdentifier: 70306,
    requesterFullName: 'Juan Dela Cruz',
    requesterRole: 'Student',
    requestSchedule: 'March 20, 2026 1:00 PM',
    requestQuantity: 1,
    requestType: 'Venue',
    requestPurpose: 'Workshop Meeting',
    requesterDepartment: 'College of Engineering',
    requestedDate: 'February 22, 2026',
    activityTime: 'March 20, 2026',
    activityNameTitle: 'Workshop Meeting',
    participantCount: 30,
    requestStatus: 'Pending for Approval',
    reservationSummary: [
      { itemName: 'F401 Multipurpose Hall', itemCount: 1 },
    ],
    uploadedDocuments: [
      { fileName: 'Workshop_APF.pdf' },
    ],
  },
  {
    requestIdentifier: 10174,
    requesterFullName: 'Ali Santos',
    requesterRole: 'Faculty',
    requestSchedule: 'March 22, 2026 10:00 AM',
    requestQuantity: 30,
    requestType: 'Both',
    requestPurpose: 'Department Meeting',
    requesterDepartment: 'Computer Science Dept',
    requestedDate: 'February 24, 2026',
    activityTime: 'March 22, 2026',
    activityNameTitle: 'CS Department Meeting',
    participantCount: 30,
    requestStatus: 'Pending for Approval',
    reservationSummary: [
      { itemName: 'F502 Auditorium', itemCount: 1 },
      { itemName: 'White Monobloc Chair', itemCount: 30 },
      { itemName: 'Microphone', itemCount: 2 },
    ],
    uploadedDocuments: [
      { fileName: 'CS_DeptMeeting_APF.pdf' },
    ],
  },
  {
    requestIdentifier: 10174,
    requesterFullName: 'Andrea Reyes',
    requesterRole: 'Faculty',
    requestSchedule: 'March 25, 2026 9:00 AM',
    requestQuantity: 50,
    requestType: 'Equipment',
    requestPurpose: 'Science Fair Setup',
    requesterDepartment: 'College of Science',
    requestedDate: 'February 25, 2026',
    activityTime: 'March 25, 2026',
    activityNameTitle: 'Science Fair 2026',
    participantCount: 200,
    requestStatus: 'Pending for Approval',
    reservationSummary: [
      { itemName: 'Table', itemCount: 20 },
      { itemName: 'Extension Cord', itemCount: 15 },
      { itemName: 'White Monobloc Chair', itemCount: 50 },
    ],
    uploadedDocuments: [
      { fileName: 'ScienceFair_APF.pdf' },
      { fileName: 'ScienceFair_Layout.pdf' },
    ],
  },
  {
    requestIdentifier: 97101,
    requesterFullName: 'Angelica Ramos',
    requesterRole: 'Faculty',
    requestSchedule: 'March 28, 2026 2:00 PM',
    requestQuantity: 1,
    requestType: 'Venue',
    requestPurpose: 'Faculty Orientation',
    requesterDepartment: 'HR Department',
    requestedDate: 'February 26, 2026',
    activityTime: 'March 28, 2026',
    activityNameTitle: 'New Faculty Orientation',
    participantCount: 25,
    requestStatus: 'Pending for Approval',
    reservationSummary: [
      { itemName: 'FS03 Audio Visual Room', itemCount: 1 },
    ],
    uploadedDocuments: [
      { fileName: 'Orientation_APF.pdf' },
    ],
  },
  {
    requestIdentifier: 19083,
    requesterFullName: 'Cristina Archamo',
    requesterRole: 'Student',
    requestSchedule: 'April 1, 2026 8:00 AM',
    requestQuantity: 100,
    requestType: 'Both',
    requestPurpose: 'University Week',
    requesterDepartment: 'Student Council',
    requestedDate: 'February 26, 2026',
    activityTime: 'April 1, 2026',
    activityNameTitle: 'FEU Tech University Week',
    participantCount: 500,
    requestStatus: 'Pending for Approval',
    reservationSummary: [
      { itemName: 'F401 Multipurpose Hall', itemCount: 1 },
      { itemName: 'Stage', itemCount: 1 },
      { itemName: 'White Monobloc Chair', itemCount: 100 },
      { itemName: 'Microphone', itemCount: 4 },
    ],
    uploadedDocuments: [
      { fileName: 'UniWeek_APF.pdf' },
      { fileName: 'UniWeek_Program.pdf' },
      { fileName: 'UniWeek_Layout.pdf' },
    ],
  },
  {
    requestIdentifier: 30950,
    requesterFullName: 'Alyssa Padilla',
    requesterRole: 'Student',
    requestSchedule: 'April 5, 2026 10:00 AM',
    requestQuantity: 40,
    requestType: 'Equipment',
    requestPurpose: 'Org Workshop',
    requesterDepartment: 'College of Engineering',
    requestedDate: 'February 26, 2026',
    activityTime: 'April 5, 2026',
    activityNameTitle: 'Engineering Org Workshop',
    participantCount: 40,
    requestStatus: 'Pending for Approval',
    reservationSummary: [
      { itemName: 'White Monobloc Chair', itemCount: 40 },
      { itemName: 'Table', itemCount: 8 },
    ],
    uploadedDocuments: [
      { fileName: 'OrgWorkshop_APF.pdf' },
    ],
  },
  {
    requestIdentifier: 50400,
    requesterFullName: 'Taylor Ocampo',
    requesterRole: 'Faculty',
    requestSchedule: 'April 8, 2026 1:00 PM',
    requestQuantity: 1,
    requestType: 'Venue',
    requestPurpose: 'Lab Orientation',
    requesterDepartment: 'IT Department',
    requestedDate: 'February 26, 2026',
    activityTime: 'April 8, 2026',
    activityNameTitle: 'IT Lab Orientation',
    participantCount: 35,
    requestStatus: 'Pending for Approval',
    reservationSummary: [
      { itemName: 'F404 Multimedia Room', itemCount: 1 },
    ],
    uploadedDocuments: [
      { fileName: 'LabOrientation_APF.pdf' },
    ],
  },
  {
    requestIdentifier: 84900,
    requesterFullName: 'Market Boo',
    requesterRole: 'Student',
    requestSchedule: 'April 10, 2026 9:00 AM',
    requestQuantity: 75,
    requestType: 'Both',
    requestPurpose: 'Cultural Night',
    requesterDepartment: 'Cultural Committee',
    requestedDate: 'February 26, 2026',
    activityTime: 'April 10, 2026',
    activityNameTitle: 'FEU Tech Cultural Night',
    participantCount: 300,
    requestStatus: 'Pending for Approval',
    reservationSummary: [
      { itemName: 'F502 Auditorium', itemCount: 1 },
      { itemName: 'White Monobloc Chair', itemCount: 75 },
      { itemName: 'Microphone', itemCount: 3 },
      { itemName: 'Stage', itemCount: 1 },
    ],
    uploadedDocuments: [
      { fileName: 'CulturalNight_APF.pdf' },
      { fileName: 'CulturalNight_Perf.pdf' },
    ],
  },
]);

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
 * @description Handles approve action for a request.
 * @param {Object} requestRecord - The request record to approve
 * @returns {void}
 */
function handleApproveRequest(requestRecord) {
  // TODO: Wire to approve flow
  console.log('Approve request:', requestRecord);
  selectedRequestRecord.value = null;
}

/**
 * @function handleRequestRevisions
 * @description Handles request revisions action.
 * @param {Object} requestRecord - The request record needing revisions
 * @returns {void}
 */
function handleRequestRevisions(requestRecord) {
  // TODO: Wire to revisions flow
  console.log('Request revisions:', requestRecord);
}

/**
 * @function handleRejectRequest
 * @description Handles reject action for a request.
 * @param {Object} requestRecord - The request record to reject
 * @returns {void}
 */
function handleRejectRequest(requestRecord) {
  // TODO: Wire to reject flow
  console.log('Reject request:', requestRecord);
  selectedRequestRecord.value = null;
}
</script>
