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
          <button class="admin-ops-sort-button pending-requests-sort-button" aria-label="Sort">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"/>
              <polyline points="19 12 12 19 5 12"/>
            </svg>
          </button>
        </div>
      </div>

      <div class="admin-ops-table-card">
        <RequestPendingTableComponent
          :request-list="pendingRequestsList"
          :search-query-text="searchQueryText"
          @view-request-details="handleViewRequestDetails"
          @approve-request-record="handleApproveRequest"
          @reject-request-record="handleRejectRequest"
        />
      </div>

      <div class="admin-ops-page-footer pending-requests-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </div>
    </section>

    <div v-if="approveRequestRecord" class="pending-request-action-overlay" @click.self="closeApproveModal">
      <section class="pending-request-action-card">
        <header class="pending-request-action-header">
          <div>
            <h2>Approve Request</h2>
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
                <strong>{{ formatRequestDate(approveRequestRecord.requestSchedule) }}</strong>
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
                  <em>{{ formatRequestDate(approveRequestRecord.requestSchedule) }}</em>
                  <em>{{ formatRequestTime(approveRequestRecord.requestSchedule) }}</em>
                </strong>
              </div>

              <div class="pending-request-summary-item">
                <span>Quantity</span>
                <strong>{{ approveRequestRecord.requestQuantity || 0 }}</strong>
              </div>
            </div>
          </section>

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
        </div>

        <footer class="pending-request-action-footer">
          <button class="pending-request-action-button pending-request-action-button--ghost" type="button" @click="closeApproveModal">Cancel</button>
          <button class="pending-request-action-button pending-request-action-button--approve" type="button" @click="confirmApproveRequest">
            Confirm Approval
          </button>
        </footer>
      </section>
    </div>

    <div v-if="deleteRequestRecord" class="pending-request-action-overlay" @click.self="closeDeleteModal">
      <section class="pending-request-action-card pending-request-action-card--delete">
        <header class="pending-request-action-header">
          <div>
            <h2>Delete Request</h2>
            <p>You are about to delete this request. This action cannot be undone.</p>
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
                <strong>{{ formatRequestDate(deleteRequestRecord.requestSchedule) }}</strong>
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
                  <em>{{ formatRequestDate(deleteRequestRecord.requestSchedule) }}</em>
                  <em>{{ formatRequestTime(deleteRequestRecord.requestSchedule) }}</em>
                </strong>
              </div>

              <div class="pending-request-summary-item">
                <span>Quantity</span>
                <strong>{{ deleteRequestRecord.requestQuantity || 0 }}</strong>
              </div>
            </div>
          </section>

          <label class="pending-request-action-field pending-request-action-field--full">
            <span>Remarks (Required)</span>
            <textarea
              v-model.trim="deleteForm.remarks"
              maxlength="500"
              rows="4"
              placeholder="Enter the reason for deleting this request..."
            />
            <small>{{ deleteForm.remarks.length }} / 500</small>
          </label>

          <div class="pending-request-action-security">
            <h3>Security Check</h3>
            <p>For security reasons, please verify your identity to confirm this action.</p>

            <div class="pending-request-action-grid">
              <label class="pending-request-action-field">
                <span>Admin Email</span>
                <input v-model.trim="deleteForm.adminEmail" type="email" placeholder="Enter your admin email" />
              </label>

              <label class="pending-request-action-field">
                <span>Password</span>
                <input v-model="deleteForm.password" type="password" placeholder="Enter your password" />
              </label>
            </div>

            <p class="pending-request-action-help">This helps us verify your identity before processing the deletion.</p>
          </div>
        </div>

        <footer class="pending-request-action-footer">
          <button class="pending-request-action-button pending-request-action-button--ghost" type="button" @click="closeDeleteModal">Cancel</button>
          <button class="pending-request-action-button pending-request-action-button--delete" type="button" @click="confirmDeleteRequest">
            Delete Request
          </button>
        </footer>
      </section>
    </div>

    <div v-if="selectedRequestRecord" class="pending-request-details-overlay" @click.self="handleCloseRequestModal">
      <section class="pending-request-details-card">
        <header class="pending-request-details-header">
          <h2>Request Details</h2>
          <button class="pending-request-details-close" type="button" aria-label="Close" @click="handleCloseRequestModal">&times;</button>
        </header>

        <div class="pending-request-details-body">
          <div class="pending-request-details-section-label">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="9" />
              <path d="M12 8v8" />
              <path d="M12 16h.01" />
            </svg>
            <span>Request Information</span>
          </div>

          <div class="pending-request-details-grid">
            <div class="pending-request-details-column">
              <div class="pending-request-details-item">
                <dt>Request ID</dt>
                <dd>{{ selectedRequestRecord.requestDisplayIdentifier || selectedRequestRecord.requestIdentifier }}</dd>
              </div>

              <div class="pending-request-details-item">
                <dt>Requester</dt>
                <dd>{{ selectedRequestRecord.requesterFullName || 'N/A' }}</dd>
                <small>ID: {{ selectedRequestRecord.requesterId || selectedRequestRecord.requestIdentifier || 'N/A' }}</small>
              </div>

              <div class="pending-request-details-item">
                <dt>Role</dt>
                <dd>{{ selectedRequestRecord.requesterRole || 'N/A' }}</dd>
              </div>

              <div class="pending-request-details-item">
                <dt>Contact</dt>
                <dd>{{ selectedRequestRecord.contactEmail || selectedRequestRecord.requesterDepartment || 'N/A' }}</dd>
                <small>{{ selectedRequestRecord.contactNumber || 'No contact number provided' }}</small>
              </div>
            </div>

            <div class="pending-request-details-column">
              <div class="pending-request-details-item">
                <dt>Type</dt>
                <dd>{{ selectedRequestRecord.requestType || 'N/A' }}</dd>
              </div>

              <div class="pending-request-details-item">
                <dt>Facility</dt>
                <dd class="pending-request-details-facility">
                  <img :src="buildFacilityPlaceholder(selectedRequestRecord.facilityName)" alt="Facility preview" />
                  <span>{{ selectedRequestRecord.facilityName || 'N/A' }}</span>
                </dd>
              </div>

              <div class="pending-request-details-item">
                <dt>Schedule</dt>
                <dd class="pending-request-details-stack">
                  <span>{{ formatRequestDate(selectedRequestRecord.requestSchedule) }}</span>
                  <span>{{ formatRequestTime(selectedRequestRecord.requestSchedule) }}</span>
                </dd>
              </div>

              <div class="pending-request-details-item">
                <dt>Quantity</dt>
                <dd>{{ selectedRequestRecord.requestQuantity || 0 }}</dd>
              </div>

              <div class="pending-request-details-item">
                <dt>Purpose</dt>
                <dd>{{ selectedRequestRecord.requestPurpose || 'N/A' }}</dd>
              </div>
            </div>
          </div>
        </div>

        <footer class="pending-request-details-footer">
          <button class="pending-request-details-button" type="button" @click="handleCloseRequestModal">Close</button>
        </footer>
      </section>
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/PendingRequests.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import RequestPendingTableComponent from '@/modules/request/components/RequestPendingTableComponent.vue';
import { useRequestStore } from '@/modules/request/store/requestStore.js';

const APP_FONT_STACK = "'Inter', 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif";
const requestStore = useRequestStore();
const searchQueryText = ref('');
const showingFilterValue = ref('all');
const selectedRequestRecord = ref(null);
const approveRequestRecord = ref(null);
const deleteRequestRecord = ref(null);
const approveForm = reactive({
  remarks: '',
  adminEmail: '',
});
const deleteForm = reactive({
  remarks: '',
  adminEmail: '',
  password: '',
});

const pendingRequestsList = computed(() => requestStore.pendingRequestsList || []);

onMounted(async () => {
  try {
    await requestStore.fetchReservations();
    const list = requestStore.pendingRequestsList || [];
    console.log('Admin Pending Requests - Count:', list.length);
  } catch (error) {
    console.error('Error fetching pending requests:', error);
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

async function confirmApproveRequest() {
  if (!approveRequestRecord.value || !approveForm.adminEmail.trim()) {
    return;
  }

  await requestStore.approvePendingRequest(approveRequestRecord.value);
  closeApproveModal();
  selectedRequestRecord.value = null;
}

async function confirmDeleteRequest() {
  if (!deleteRequestRecord.value || !deleteForm.remarks.trim() || !deleteForm.adminEmail.trim() || !deleteForm.password.trim()) {
    return;
  }

  await requestStore.rejectPendingRequest(deleteRequestRecord.value, deleteForm.remarks.trim());
  closeDeleteModal();
  selectedRequestRecord.value = null;
}

function closeApproveModal() {
  approveRequestRecord.value = null;
  approveForm.remarks = '';
  approveForm.adminEmail = '';
}

function closeDeleteModal() {
  deleteRequestRecord.value = null;
  deleteForm.remarks = '';
  deleteForm.adminEmail = '';
  deleteForm.password = '';
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
</script>
