<!-- ===== AI GENERATED: AdminPendingRequestsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="admin-ops-page pending-requests-page">
      <header class="admin-ops-header">
        <div class="admin-ops-header-copy">
          <p class="admin-ops-kicker">Reservation Workflow</p>
          <h1>Pending Requests</h1>
          <p>Review incoming reservation requests, inspect the request details, and approve or reject them from one consistent queue.</p>
        </div>
      </header>

      <div class="admin-ops-filter-card">
        <div class="admin-ops-toolbar pending-requests-toolbar">
          <label class="admin-ops-field">
            <span>Search</span>
            <input
              id="requestSearchInput"
              v-model="searchQueryText"
              type="text"
              class="pending-requests-search-input"
              placeholder="Requester name or request ID"
            />
          </label>
          <label class="admin-ops-field">
            <span>Showing</span>
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
          </label>
          <button
            class="admin-ops-sort-button pending-requests-sort-button"
            :class="{ 'admin-ops-sort-button--ascending': sortDirection === 'asc' }"
            :aria-label="`Sort ${sortDirection === 'asc' ? 'descending' : 'ascending'}`"
            :title="sortDirection === 'asc' ? 'Oldest submitted first' : 'Newest submitted first'"
            type="button"
            @click="toggleSortDirection"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"/>
              <polyline points="19 12 12 19 5 12"/>
            </svg>
            <span>{{ sortDirection === 'asc' ? 'Oldest First' : 'Newest First' }}</span>
          </button>
        </div>
      </div>

      <div class="admin-ops-table-card">
        <RequestPendingTableComponent
          :request-list="paginatedPendingRequests"
          :search-query-text="searchQueryText"
          @view-request-details="handleViewRequestDetails"
          @approve-request-record="handleApproveRequest"
          @reject-request-record="handleRejectRequest"
        />
      </div>

      <div v-if="pendingRequestsTotalPages > 1" class="pending-requests-pagination">
        <button type="button" :disabled="pendingRequestsCurrentPage === 1" @click="pendingRequestsCurrentPage -= 1">Previous</button>
        <span>Page {{ pendingRequestsCurrentPage }} of {{ pendingRequestsTotalPages }}</span>
        <button type="button" :disabled="pendingRequestsCurrentPage === pendingRequestsTotalPages" @click="pendingRequestsCurrentPage += 1">Next</button>
      </div>

      <div class="admin-ops-page-footer pending-requests-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </div>
    </section>

    <div v-if="approveRequestRecord" class="pending-request-action-overlay" @click.self="closeApproveModal">
      <section class="pending-request-action-card">
        <header class="pending-request-action-header">
          <div>
            <h2>Approval Confirmation</h2>
            <p>Please review the request details and provide your confirmation to approve.</p>
          </div>
          <button class="pending-request-action-close" type="button" aria-label="Close" @click="closeApproveModal">&times;</button>
        </header>

        <div class="pending-request-action-body">
          <section class="pending-request-summary-card">
            <div class="pending-request-summary-top">
              <div class="pending-request-summary-requester">
                <span class="pending-request-summary-avatar">{{ getRequesterInitials(approveRequestRecord) }}</span>
                <div>
                  <strong>{{ approveRequestRecord.requesterFullName || 'N/A' }}</strong>
                  <small>ID: {{ approveRequestRecord.requesterId || approveRequestRecord.requestIdentifier || 'N/A' }}</small>
                  <span class="pending-request-summary-role">{{ approveRequestRecord.requesterRole || 'Borrower' }}</span>
                </div>
              </div>

              <div class="pending-request-summary-metric">
                <span>Request ID</span>
                <strong>{{ approveRequestRecord.requestDisplayIdentifier || approveRequestRecord.requestIdentifier }}</strong>
              </div>

              <div class="pending-request-summary-metric">
                <span>Request Type</span>
                <strong>
                  <i class="pending-request-summary-badge" :class="getRequestBadgeClass(approveRequestRecord.requestType)">
                    {{ approveRequestRecord.requestType || 'N/A' }}
                  </i>
                </strong>
              </div>

              <div class="pending-request-summary-metric">
                <span>Requested On</span>
                <strong>{{ formatRequestDate(approveRequestRecord.requestedDate) }}</strong>
              </div>
            </div>

            <div class="pending-request-summary-bottom">
              <div class="pending-request-summary-item">
                <span>Facility / Equipment</span>
                <strong class="pending-request-summary-facility">
                  <img :src="buildFacilityPlaceholder(approveRequestRecord.facilityName)" alt="Facility preview" />
                  <em>{{ approveRequestRecord.facilityName || 'N/A' }}</em>
                </strong>
              </div>

              <div class="pending-request-summary-item">
                <span>Purpose</span>
                <strong>{{ approveRequestRecord.requestPurpose || 'N/A' }}</strong>
              </div>

              <div class="pending-request-summary-item">
                <span>Schedule</span>
                <strong class="pending-request-summary-stack">
                  <em>{{ formatScheduleDateRange(approveRequestRecord.requestScheduleStart, approveRequestRecord.requestScheduleEnd) }}</em>
                  <em>{{ formatScheduleTimeRange(approveRequestRecord.requestScheduleStart, approveRequestRecord.requestScheduleEnd) }}</em>
                </strong>
              </div>

              <div class="pending-request-summary-item">
                <span>Quantity</span>
                <strong>{{ approveRequestRecord.requestQuantity || 0 }}</strong>
              </div>
            </div>
          </section>

          <div class="pending-request-details-remarks">
            <strong>Borrower Remarks:</strong>
            <div class="pending-request-details-remarks-box">
              {{ approveRequestRecord.borrowerRemarks || 'No borrower remarks added.' }}
            </div>
          </div>

          <label class="pending-request-action-field pending-request-action-field--full">
            <span>Remarks (Optional)</span>
            <textarea
              v-model.trim="approveForm.remarks"
              maxlength="500"
              rows="4"
              placeholder="Enter any remarks or notes for this approval..."
            />
            <small>{{ approveForm.remarks.length }} / 500</small>
          </label>

          <div class="pending-request-action-security">
            <h3>Security Check</h3>
            <p>For security reasons, please enter your administrator email to confirm this action.</p>

            <label class="pending-request-action-field pending-request-action-field--full">
              <span>Admin Email</span>
              <input v-model.trim="approveForm.adminEmail" type="email" placeholder="Enter your admin email" />
              <small>This helps us verify your identity before processing the approval.</small>
            </label>
          </div>

          <p v-if="approveModalError" class="pending-request-action-feedback pending-request-action-feedback--error">
            {{ approveModalError }}
          </p>
        </div>

        <footer class="pending-request-action-footer">
          <button class="pending-request-action-button pending-request-action-button--ghost" type="button" :disabled="isApprovingRequest" @click="closeApproveModal">Cancel</button>
          <button class="pending-request-action-button pending-request-action-button--approve" type="button" :disabled="isApprovingRequest" @click="confirmApproveRequest">
            {{ isApprovingRequest ? 'Confirming...' : 'Confirm Approval' }}
          </button>
        </footer>
      </section>
    </div>

    <div v-if="deleteRequestRecord" class="pending-request-action-overlay" @click.self="closeDeleteModal">
      <section class="pending-request-action-card pending-request-action-card--delete">
        <header class="pending-request-action-header">
          <div>
            <h2>Deny Request</h2>
            <p>You are about to deny this request. This action cannot be undone.</p>
          </div>
          <button class="pending-request-action-close" type="button" aria-label="Close" @click="closeDeleteModal">&times;</button>
        </header>

        <div class="pending-request-action-body">
          <section class="pending-request-summary-card">
            <div class="pending-request-summary-top">
              <div class="pending-request-summary-requester">
                <span class="pending-request-summary-avatar">{{ getRequesterInitials(deleteRequestRecord) }}</span>
                <div>
                  <strong>{{ deleteRequestRecord.requesterFullName || 'N/A' }}</strong>
                  <small>ID: {{ deleteRequestRecord.requesterId || deleteRequestRecord.requestIdentifier || 'N/A' }}</small>
                  <span class="pending-request-summary-role">{{ deleteRequestRecord.requesterRole || 'Borrower' }}</span>
                </div>
              </div>

              <div class="pending-request-summary-metric">
                <span>Request ID</span>
                <strong>{{ deleteRequestRecord.requestDisplayIdentifier || deleteRequestRecord.requestIdentifier }}</strong>
              </div>

              <div class="pending-request-summary-metric">
                <span>Request Type</span>
                <strong>
                  <i class="pending-request-summary-badge" :class="getRequestBadgeClass(deleteRequestRecord.requestType)">
                    {{ deleteRequestRecord.requestType || 'N/A' }}
                  </i>
                </strong>
              </div>

              <div class="pending-request-summary-metric">
                <span>Requested On</span>
                <strong>{{ formatRequestDate(deleteRequestRecord.requestedDate) }}</strong>
              </div>
            </div>

            <div class="pending-request-summary-bottom">
              <div class="pending-request-summary-item">
                <span>Facility / Equipment</span>
                <strong class="pending-request-summary-facility">
                  <img :src="buildFacilityPlaceholder(deleteRequestRecord.facilityName)" alt="Facility preview" />
                  <em>{{ deleteRequestRecord.facilityName || 'N/A' }}</em>
                </strong>
              </div>

              <div class="pending-request-summary-item">
                <span>Purpose</span>
                <strong>{{ deleteRequestRecord.requestPurpose || 'N/A' }}</strong>
              </div>

              <div class="pending-request-summary-item">
                <span>Schedule</span>
                <strong class="pending-request-summary-stack">
                  <em>{{ formatScheduleDateRange(deleteRequestRecord.requestScheduleStart, deleteRequestRecord.requestScheduleEnd) }}</em>
                  <em>{{ formatScheduleTimeRange(deleteRequestRecord.requestScheduleStart, deleteRequestRecord.requestScheduleEnd) }}</em>
                </strong>
              </div>

              <div class="pending-request-summary-item">
                <span>Quantity</span>
                <strong>{{ deleteRequestRecord.requestQuantity || 0 }}</strong>
              </div>
            </div>
          </section>

          <div class="pending-request-details-remarks">
            <strong>Borrower Remarks:</strong>
            <div class="pending-request-details-remarks-box">
              {{ deleteRequestRecord.borrowerRemarks || 'No borrower remarks added.' }}
            </div>
          </div>

          <label class="pending-request-action-field pending-request-action-field--full">
            <span>Remarks (Optional)</span>
            <textarea
              v-model.trim="deleteForm.remarks"
              maxlength="500"
              rows="4"
              placeholder="Enter the reason for denying this request..."
            />
            <small>{{ deleteForm.remarks.length }} / 500</small>
          </label>

          <div class="pending-request-action-security">
            <h3>Security Check</h3>
            <p>For security reasons, please verify your identity to confirm this action.</p>

            <div class="pending-request-action-grid">
              <label class="pending-request-action-field">
                <span>Admin Email</span>
                <input v-model.trim="deleteForm.adminEmail" type="email" :placeholder="currentAdminEmail || 'Enter your admin email'" />
              </label>

              <label class="pending-request-action-field">
                <span>Password</span>
                <input v-model="deleteForm.password" type="password" placeholder="Enter your password" />
              </label>
            </div>

            <p class="pending-request-action-help">This helps us verify your identity before processing the denial.</p>
          </div>
        </div>

        <footer class="pending-request-action-footer">
          <button class="pending-request-action-button pending-request-action-button--ghost" type="button" :disabled="isDenyingRequest" @click="closeDeleteModal">Cancel</button>
          <button class="pending-request-action-button pending-request-action-button--delete" type="button" :disabled="isDenyingRequest" @click="confirmDeleteRequest">
            {{ isDenyingRequest ? 'Denying...' : 'Deny Request' }}
          </button>
        </footer>
      </section>
    </div>

    <div v-if="selectedRequestRecord" class="pending-request-details-overlay" @click.self="handleCloseRequestModal">
      <section class="pending-request-details-card">
        <header class="pending-request-details-header">
          <h2>Workflow</h2>
          <button class="pending-request-details-close" type="button" aria-label="Close" @click="handleCloseRequestModal">&times;</button>
        </header>

        <div class="pending-request-details-body">
          <div class="pending-request-details-topline">
            <div class="pending-request-details-meta-list">
              <div class="pending-request-details-inline">
                <strong>Request ID:</strong>
                <span>{{ selectedRequestRecord.requestDisplayIdentifier || selectedRequestRecord.requestIdentifier }}</span>
              </div>
              <div class="pending-request-details-inline">
                <strong>Requester:</strong>
                <span>{{ selectedRequestRecord.requesterFullName || 'N/A' }}</span>
              </div>
              <div class="pending-request-details-inline">
                <strong>Role:</strong>
                <span>{{ selectedRequestRecord.requesterRole || 'N/A' }}</span>
              </div>
              <div class="pending-request-details-inline">
                <strong>Department:</strong>
                <span>{{ selectedRequestRecord.requesterDepartment || 'N/A' }}</span>
              </div>
              <div class="pending-request-details-inline">
                <strong>Requested Date:</strong>
                <span>{{ selectedRequestRecord.requestedDate || 'N/A' }}</span>
              </div>
            </div>

            <div class="pending-request-details-type-card">
              <strong>Request Type:</strong>
              <span>{{ selectedRequestRecord.requestType || 'N/A' }}</span>
            </div>
          </div>

          <div class="pending-request-details-divider"></div>

          <div class="pending-request-details-grid">
            <div class="pending-request-details-panel">
              <strong>Facility</strong>
              <div class="pending-request-details-facility">
                <img :src="buildFacilityPlaceholder(selectedRequestRecord.facilityName)" alt="Facility preview" />
                <span>{{ selectedRequestRecord.facilityName || 'N/A' }}</span>
              </div>
            </div>

            <div class="pending-request-details-panel">
              <strong>Schedule</strong>
              <div class="pending-request-details-stack">
                <span>{{ formatScheduleDateRange(selectedRequestRecord.requestScheduleStart, selectedRequestRecord.requestScheduleEnd) }}</span>
                <span>{{ formatScheduleTimeRange(selectedRequestRecord.requestScheduleStart, selectedRequestRecord.requestScheduleEnd) }}</span>
              </div>
            </div>

            <div class="pending-request-details-panel pending-request-details-panel--wide">
              <strong>Activity Name/Title</strong>
              <span>{{ selectedRequestRecord.activityNameTitle || 'N/A' }}</span>
            </div>

            <div class="pending-request-details-panel pending-request-details-panel--wide">
              <strong>Purpose</strong>
              <span>{{ selectedRequestRecord.requestPurpose || 'N/A' }}</span>
            </div>

            <div class="pending-request-details-panel">
              <strong>No. of Participants</strong>
              <span>{{ selectedRequestRecord.participantCount || 0 }}</span>
            </div>

            <div class="pending-request-details-panel">
              <strong>Status</strong>
              <span class="pending-request-details-status">{{ selectedRequestRecord.requestStatus || 'N/A' }}</span>
            </div>
          </div>

          <div class="pending-request-details-divider"></div>

          <div class="pending-request-details-remarks">
            <strong>Borrower Remarks:</strong>
            <div class="pending-request-details-remarks-box">
              {{ selectedRequestRecord.borrowerRemarks || 'No borrower remarks added.' }}
            </div>
          </div>

          <div class="pending-request-details-remarks">
            <strong>Admin / Status Remarks:</strong>
            <div class="pending-request-details-remarks-box">
              {{ selectedRequestRecord.remarks || 'No remarks added yet.' }}
            </div>
          </div>
        </div>

        <footer class="pending-request-details-footer">
          <button class="pending-request-details-button" type="button" @click="handleCloseRequestModal">Close</button>
        </footer>
      </section>
    </div>
    <DataRequestStatusFloater :items="pendingRequestsStatusItems" />
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, onMounted, computed, reactive, watch } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import DataRequestStatusFloater from '@/shared/components/DataRequestStatusFloater.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/PendingRequests.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import RequestPendingTableComponent from '@/modules/request/components/RequestPendingTableComponent.vue';
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { sortReservationRecords } from '@/modules/request/services/requestReservationMapper.js';

const APP_FONT_STACK = "'Inter', 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif";
const authStore = useAuthenticationStore();
const requestStore = useRequestStore();
const router = useRouter();
const searchQueryText = ref('');
const showingFilterValue = ref('all');
const sortDirection = ref('desc');
const pendingRequestsCurrentPage = ref(1);
const pendingRequestsPageSize = 8;
const selectedRequestRecord = ref(null);
const approveRequestRecord = ref(null);
const deleteRequestRecord = ref(null);
const approveForm = reactive({
  remarks: '',
  adminEmail: '',
});
const approveModalError = ref('');
const isApprovingRequest = ref(false);
const isDenyingRequest = ref(false);
const deleteForm = reactive({
  remarks: '',
  adminEmail: '',
  password: '',
});

const pendingRequestsList = computed(() => requestStore.pendingRequestsList || []);
const pendingRequestsStatusItems = computed(() => [
  {
    key: 'pending-reservations',
    label: 'Pending Reservations',
    state: resolveReservationListState(pendingRequestsList.value),
  },
]);
const filteredPendingRequests = computed(() => {
  const queryLower = searchQueryText.value.toLowerCase().trim();

  const filteredRecords = pendingRequestsList.value.filter((requestRecord) => {
    const requestType = String(requestRecord?.requestType || '').toLowerCase();
    const matchesShowing = showingFilterValue.value === 'all'
      || requestType === showingFilterValue.value;
    const matchesQuery = queryLower === ''
      || String(requestRecord?.requesterFullName || '').toLowerCase().includes(queryLower)
      || String(requestRecord?.requestIdentifier || '').toLowerCase().includes(queryLower)
      || String(requestRecord?.requestDisplayIdentifier || '').toLowerCase().includes(queryLower);

    return matchesShowing && matchesQuery;
  });

  return sortReservationRecords(filteredRecords, 'pending', sortDirection.value);
});
const pendingRequestsTotalPages = computed(() => Math.max(1, Math.ceil(filteredPendingRequests.value.length / pendingRequestsPageSize)));
const paginatedPendingRequests = computed(() => {
  const startIndex = (pendingRequestsCurrentPage.value - 1) * pendingRequestsPageSize;
  return filteredPendingRequests.value.slice(startIndex, startIndex + pendingRequestsPageSize);
});
const currentAdminEmail = computed(() => {
  const account = authStore.accountData || authStore.clerkAccountData || {};
  return String(account.emailAddress || account.email || '').trim().toLowerCase();
});

onMounted(async () => {
  try {
    await requestStore.fetchReservations();
    const list = requestStore.pendingRequestsList || [];
    console.log('Admin Pending Requests - Count:', list.length);
  } catch (error) {
    console.error('Error fetching pending requests:', error);
  }
});

watch([searchQueryText, showingFilterValue], () => {
  pendingRequestsCurrentPage.value = 1;
});

watch(pendingRequestsTotalPages, (pageCount) => {
  if (pendingRequestsCurrentPage.value > pageCount) {
    pendingRequestsCurrentPage.value = pageCount;
  }
});

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
  approveRequestRecord.value = requestRecord;
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
  deleteRequestRecord.value = requestRecord;
}

function toggleSortDirection() {
  sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
}

async function confirmApproveRequest() {
  if (!approveRequestRecord.value || isApprovingRequest.value) {
    return;
  }

  const emailError = validateAdminEmailConfirmation(approveForm.adminEmail, 'approve');
  if (emailError) {
    approveModalError.value = emailError;
    return;
  }

  isApprovingRequest.value = true;
  approveModalError.value = '';

  try {
    await requestStore.approvePendingRequest(approveRequestRecord.value, {
      confirmedAdminEmail: normalizeEmailForConfirmation(approveForm.adminEmail),
    });
    closeApproveModal();
    selectedRequestRecord.value = null;
    router.push({ name: 'adminApprovedRequestsPage' });
  } catch (error) {
    approveModalError.value = error?.message || 'Unable to approve this request.';
  } finally {
    isApprovingRequest.value = false;
  }
}

async function confirmDeleteRequest() {
  if (!deleteRequestRecord.value || isDenyingRequest.value) {
    return;
  }

  const emailError = validateAdminEmailConfirmation(deleteForm.adminEmail, 'deny');
  if (emailError) {
    window.alert(emailError);
    return;
  }

  if (deleteForm.password.trim() === '') {
    window.alert('Please type your admin password before denying this request.');
    return;
  }

  try {
    isDenyingRequest.value = true;
    await requestStore.rejectPendingRequest(deleteRequestRecord.value, deleteForm.remarks.trim(), {
      confirmedAdminEmail: normalizeEmailForConfirmation(deleteForm.adminEmail),
      confirmedAdminPassword: deleteForm.password,
    });
    closeDeleteModal();
    selectedRequestRecord.value = null;
  } catch (error) {
    window.alert(error?.message || 'Unable to deny this request.');
  } finally {
    isDenyingRequest.value = false;
  }
}

function closeApproveModal() {
  approveRequestRecord.value = null;
  approveForm.remarks = '';
  approveForm.adminEmail = '';
  approveModalError.value = '';
  isApprovingRequest.value = false;
}

function closeDeleteModal() {
  deleteRequestRecord.value = null;
  deleteForm.remarks = '';
  deleteForm.adminEmail = '';
  deleteForm.password = '';
  isDenyingRequest.value = false;
}

function formatRequestDate(value) {
  const parsedDate = new Date(value);
  if (Number.isNaN(parsedDate.getTime())) {
    return value || 'N/A';
  }

  return new Intl.DateTimeFormat('en-PH', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  }).format(parsedDate);
}

function formatRequestTime(value) {
  const parsedDate = new Date(value);
  if (Number.isNaN(parsedDate.getTime())) {
    return 'Time not available';
  }

  return new Intl.DateTimeFormat('en-PH', {
    hour: 'numeric',
    minute: '2-digit',
  }).format(parsedDate);
}

function formatScheduleDateRange(startValue, endValue) {
  const startDate = parseValidDate(startValue);
  const endDate = parseValidDate(endValue);

  if (!startDate && !endDate) {
    return 'Date not available';
  }

  if (!startDate || !endDate) {
    return formatRequestDate(startValue || endValue);
  }

  if (startDate.toDateString() === endDate.toDateString()) {
    return formatRequestDate(startValue);
  }

  return `${formatRequestDate(startValue)} - ${formatRequestDate(endValue)}`;
}

function formatScheduleTimeRange(startValue, endValue) {
  const startDate = parseValidDate(startValue);
  const endDate = parseValidDate(endValue);

  if (!startDate && !endDate) {
    return 'Time not available';
  }

  if (!startDate || !endDate) {
    return formatRequestTime(startValue || endValue);
  }

  return `${formatRequestTime(startValue)} - ${formatRequestTime(endValue)}`;
}

function parseValidDate(value) {
  const parsedDate = new Date(value);
  return Number.isNaN(parsedDate.getTime()) ? null : parsedDate;
}

function validateAdminEmailConfirmation(emailValue, actionName) {
  const normalizedEmail = normalizeEmailForConfirmation(emailValue);
  if (normalizedEmail === '') {
    return `Please type your exact admin email before ${actionName === 'approve' ? 'approving' : 'denying'} this request.`;
  }

  if (currentAdminEmail.value === '') {
    return 'Unable to verify the admin in charge. Please sign in again.';
  }

  if (normalizedEmail !== currentAdminEmail.value) {
    return `Please type your exact admin email before ${actionName === 'approve' ? 'approving' : 'denying'} this request.`;
  }

  return '';
}

function normalizeEmailForConfirmation(emailValue) {
  return String(emailValue || '').trim().toLowerCase();
}

function buildFacilityPlaceholder(facilityName) {
  const label = String(facilityName || 'Facility').trim().slice(0, 28);
  const svg = `
    <svg xmlns="http://www.w3.org/2000/svg" width="88" height="56" viewBox="0 0 88 56">
      <defs>
        <linearGradient id="g" x1="0" x2="1" y1="0" y2="1">
          <stop offset="0%" stop-color="#b9c8d3"/>
          <stop offset="100%" stop-color="#e8eef2"/>
        </linearGradient>
      </defs>
      <rect width="88" height="56" rx="8" fill="url(#g)"/>
      <rect x="8" y="30" width="72" height="18" rx="4" fill="#d9e2e8"/>
      <rect x="14" y="14" width="26" height="12" rx="3" fill="#f8fbfd"/>
      <rect x="46" y="14" width="28" height="12" rx="3" fill="#f8fbfd"/>
      <text x="44" y="51" text-anchor="middle" font-size="7" font-family="${APP_FONT_STACK}" fill="#536772">${label}</text>
    </svg>
  `;

  return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;
}

function getRequesterInitials(requestRecord) {
  const name = String(requestRecord?.requesterFullName || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('');

  return name || 'TR';
}

function getRequestBadgeClass(requestType) {
  const typeLower = String(requestType || '').toLowerCase();
  if (typeLower === 'venue') return 'pending-request-summary-badge--venue';
  if (typeLower === 'equipment') return 'pending-request-summary-badge--equipment';
  return 'pending-request-summary-badge--both';
}

function resolveReservationListState(records) {
  if (requestStore.isLoadingReservations && records.length > 0) {
    return 'cached-loading';
  }

  if (requestStore.isLoadingReservations) {
    return 'loading';
  }

  return records.length > 0 ? 'fresh' : 'idle';
}
</script>
