<!-- ===== AI GENERATED: AdminApprovedRequestsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="admin-ops-page approved-requests-page">
      <header class="admin-ops-header">
        <div class="admin-ops-header-copy">
          <p class="admin-ops-kicker">Release Workflow</p>
          <h1>Approved Requests</h1>
          <p>Monitor approved reservations, review workflow details, and move requests forward to deployment or cancellation.</p>
        </div>
      </header>

      <div class="admin-ops-filter-card">
        <div class="admin-ops-toolbar approved-requests-toolbar">
          <label class="admin-ops-field">
            <span>Search</span>
            <input
              id="approvedSearchInput"
              v-model="searchQueryText"
              type="text"
              class="approved-requests-search-input"
              placeholder="Requester name or request ID"
            />
          </label>
          <label class="admin-ops-field">
            <span>Showing</span>
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
          </label>
          <button class="admin-ops-sort-button approved-requests-sort-button" aria-label="Sort">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"/>
              <polyline points="19 12 12 19 5 12"/>
            </svg>
          </button>
        </div>
      </div>

      <div class="admin-ops-table-card">
        <RequestApprovedTableComponent
          :request-list="approvedRequestsList"
          :search-query-text="searchQueryText"
          @view-workflow-details="handleViewWorkflowDetails"
          @edit-workflow-record="handleEditWorkflow"
          @deploy-release-record="handleDeployRelease"
          @cancel-request-record="handleCancelRequest"
        />
      </div>

      <div class="admin-ops-page-footer approved-requests-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </div>
    </section>

    <RequestWorkflowModalComponent
      :request-record="selectedRequestRecord"
      @close-workflow-modal="handleCloseWorkflowModal"
      @deploy-release-record="handleDeployRelease"
      @edit-workflow-record="handleEditWorkflow"
      @cancel-workflow-record="handleCancelRequest"
    />

    <div v-if="editRequestRecord" class="approved-request-action-overlay" @click.self="closeEditModal">
      <section class="approved-request-action-card">
        <header class="approved-request-action-header">
          <div>
            <h2>Edit Workflow</h2>
            <p>Update the workflow details for this approved request.</p>
          </div>
          <button class="approved-request-action-close" type="button" aria-label="Close" @click="closeEditModal">&times;</button>
        </header>

        <div class="approved-request-action-body">
          <section class="approved-request-summary-card">
            <div class="approved-request-summary-top">
              <div class="approved-request-summary-requester">
                <span class="approved-request-summary-avatar">{{ getRequesterInitials(editRequestRecord) }}</span>
                <div>
                  <strong>{{ editRequestRecord.requesterFullName || 'N/A' }}</strong>
                  <small>ID: {{ editRequestRecord.requesterId || editRequestRecord.requestIdentifier || 'N/A' }}</small>
                  <span class="approved-request-summary-role">{{ editRequestRecord.requesterRole || 'Borrower' }}</span>
                </div>
              </div>

              <div class="approved-request-summary-metric">
                <span>Request ID</span>
                <strong>{{ editRequestRecord.requestDisplayIdentifier || editRequestRecord.requestIdentifier }}</strong>
              </div>

              <div class="approved-request-summary-metric">
                <span>Request Type</span>
                <strong>{{ editRequestRecord.requestType || 'N/A' }}</strong>
              </div>

              <div class="approved-request-summary-metric">
                <span>Status</span>
                <strong>{{ editRequestRecord.requestStatus || 'Approved' }}</strong>
              </div>
            </div>
          </section>

          <div class="approved-request-action-grid">
            <label class="approved-request-action-field">
              <span>Activity Name/Title</span>
              <input v-model.trim="editForm.activityNameTitle" type="text" placeholder="Enter activity title" />
            </label>

            <label class="approved-request-action-field">
              <span>Assigned FO Personnel</span>
              <input v-model.trim="editForm.assignedPersonnel" type="text" placeholder="Assign FO personnel" />
            </label>

            <label class="approved-request-action-field">
              <span>Schedule</span>
              <input v-model.trim="editForm.requestSchedule" type="text" placeholder="Enter schedule" />
            </label>

            <label class="approved-request-action-field">
              <span>Participants</span>
              <input v-model.number="editForm.participantCount" type="number" min="0" placeholder="0" />
            </label>
          </div>

          <label class="approved-request-action-field approved-request-action-field--full">
            <span>Purpose</span>
            <textarea
              v-model.trim="editForm.requestPurpose"
              maxlength="500"
              rows="3"
              placeholder="Enter purpose"
            />
          </label>

          <label class="approved-request-action-field approved-request-action-field--full">
            <span>Remarks</span>
            <textarea
              v-model.trim="editForm.remarks"
              maxlength="500"
              rows="4"
              placeholder="Add workflow remarks..."
            />
            <small>{{ editForm.remarks.length }} / 500</small>
          </label>
        </div>

        <footer class="approved-request-action-footer">
          <button class="approved-request-action-button approved-request-action-button--ghost" type="button" @click="closeEditModal">Cancel</button>
          <button class="approved-request-action-button approved-request-action-button--edit" type="button" @click="submitEditWorkflow">
            Save Changes
          </button>
        </footer>
      </section>
    </div>

    <div v-if="deployRequestRecord" class="approved-request-action-overlay" @click.self="closeDeployModal">
      <section class="approved-request-action-card">
        <header class="approved-request-action-header">
          <div>
            <h2>Deploy Request</h2>
            <p>Review the approved request and confirm deployment with remarks and administrator verification.</p>
          </div>
          <button class="approved-request-action-close" type="button" aria-label="Close" @click="closeDeployModal">&times;</button>
        </header>

        <div class="approved-request-action-body">
          <section class="approved-request-summary-card">
            <div class="approved-request-summary-top">
              <div class="approved-request-summary-requester">
                <span class="approved-request-summary-avatar">{{ getRequesterInitials(deployRequestRecord) }}</span>
                <div>
                  <strong>{{ deployRequestRecord.requesterFullName || 'N/A' }}</strong>
                  <small>ID: {{ deployRequestRecord.requesterId || deployRequestRecord.requestIdentifier || 'N/A' }}</small>
                  <span class="approved-request-summary-role">{{ deployRequestRecord.requesterRole || 'Borrower' }}</span>
                </div>
              </div>

              <div class="approved-request-summary-metric">
                <span>Request ID</span>
                <strong>{{ deployRequestRecord.requestDisplayIdentifier || deployRequestRecord.requestIdentifier }}</strong>
              </div>

              <div class="approved-request-summary-metric">
                <span>Request Type</span>
                <strong>{{ deployRequestRecord.requestType || 'N/A' }}</strong>
              </div>

              <div class="approved-request-summary-metric">
                <span>Status</span>
                <strong>{{ deployRequestRecord.requestStatus || 'Approved' }}</strong>
              </div>
            </div>

            <div class="approved-request-summary-bottom">
              <div class="approved-request-summary-item">
                <span>Facility</span>
                <strong>{{ deployRequestRecord.facilityName || 'N/A' }}</strong>
              </div>

              <div class="approved-request-summary-item">
                <span>Schedule</span>
                <strong>{{ deployRequestRecord.requestSchedule || 'N/A' }}</strong>
              </div>

              <div class="approved-request-summary-item">
                <span>Purpose</span>
                <strong>{{ deployRequestRecord.requestPurpose || 'N/A' }}</strong>
              </div>

              <div class="approved-request-summary-item">
                <span>Quantity</span>
                <strong>{{ deployRequestRecord.requestQuantity || 0 }}</strong>
              </div>
            </div>
          </section>

          <label class="approved-request-action-field approved-request-action-field--full">
            <span>Remarks</span>
            <textarea
              v-model.trim="deployForm.remarks"
              maxlength="500"
              rows="4"
              placeholder="Add deployment remarks..."
            />
            <small>{{ deployForm.remarks.length }} / 500</small>
          </label>

          <div class="approved-request-action-security">
            <h3>Admin Confirmation</h3>
            <p>Please verify your administrator account before deploying this request.</p>

            <div class="approved-request-action-grid">
              <label class="approved-request-action-field">
                <span>Admin Email</span>
                <input v-model.trim="deployForm.adminEmail" type="email" :placeholder="currentAdminEmail || 'Enter your admin email'" />
              </label>

            </div>
          </div>
        </div>

        <footer class="approved-request-action-footer">
          <button class="approved-request-action-button approved-request-action-button--ghost" type="button" @click="closeDeployModal">Cancel</button>
          <button class="approved-request-action-button approved-request-action-button--deploy" type="button" @click="submitDeployRequest">
            Deploy/Release
          </button>
        </footer>
      </section>
    </div>

    <div v-if="cancelRequestRecord" class="approved-request-action-overlay" @click.self="closeCancelModal">
      <section class="approved-request-action-card">
        <header class="approved-request-action-header">
          <div>
            <h2>Deny Request</h2>
            <p>Provide the reason for denial and administrator verification before completing this action.</p>
          </div>
          <button class="approved-request-action-close" type="button" aria-label="Close" @click="closeCancelModal">&times;</button>
        </header>

        <div class="approved-request-action-body">
          <section class="approved-request-summary-card">
            <div class="approved-request-summary-top">
              <div class="approved-request-summary-requester">
                <span class="approved-request-summary-avatar">{{ getRequesterInitials(cancelRequestRecord) }}</span>
                <div>
                  <strong>{{ cancelRequestRecord.requesterFullName || 'N/A' }}</strong>
                  <small>ID: {{ cancelRequestRecord.requesterId || cancelRequestRecord.requestIdentifier || 'N/A' }}</small>
                  <span class="approved-request-summary-role">{{ cancelRequestRecord.requesterRole || 'Borrower' }}</span>
                </div>
              </div>

              <div class="approved-request-summary-metric">
                <span>Request ID</span>
                <strong>{{ cancelRequestRecord.requestDisplayIdentifier || cancelRequestRecord.requestIdentifier }}</strong>
              </div>

              <div class="approved-request-summary-metric">
                <span>Request Type</span>
                <strong>{{ cancelRequestRecord.requestType || 'N/A' }}</strong>
              </div>

              <div class="approved-request-summary-metric">
                <span>Status</span>
                <strong>{{ cancelRequestRecord.requestStatus || 'Approved' }}</strong>
              </div>
            </div>

            <div class="approved-request-summary-bottom">
              <div class="approved-request-summary-item">
                <span>Facility</span>
                <strong>{{ cancelRequestRecord.facilityName || 'N/A' }}</strong>
              </div>

              <div class="approved-request-summary-item">
                <span>Schedule</span>
                <strong>{{ cancelRequestRecord.requestSchedule || 'N/A' }}</strong>
              </div>

              <div class="approved-request-summary-item">
                <span>Purpose</span>
                <strong>{{ cancelRequestRecord.requestPurpose || 'N/A' }}</strong>
              </div>

              <div class="approved-request-summary-item">
                <span>Quantity</span>
                <strong>{{ cancelRequestRecord.requestQuantity || 0 }}</strong>
              </div>
            </div>
          </section>

          <label class="approved-request-action-field approved-request-action-field--full">
            <span>Remarks</span>
            <textarea
              v-model.trim="cancelForm.remarks"
              maxlength="500"
              rows="4"
              placeholder="Enter the reason for denying this request..."
            />
            <small>{{ cancelForm.remarks.length }} / 500</small>
          </label>

          <div class="approved-request-action-security">
            <h3>Admin Confirmation</h3>
            <p>Please verify your administrator account before denying this request.</p>

            <div class="approved-request-action-grid">
              <label class="approved-request-action-field">
                <span>Admin Email</span>
                <input v-model.trim="cancelForm.adminEmail" type="email" :placeholder="currentAdminEmail || 'Enter your admin email'" />
              </label>

            </div>
          </div>
        </div>

        <footer class="approved-request-action-footer">
          <button class="approved-request-action-button approved-request-action-button--ghost" type="button" @click="closeCancelModal">Cancel</button>
          <button class="approved-request-action-button approved-request-action-button--cancel" type="button" @click="submitCancelRequest">
            Deny Request
          </button>
        </footer>
      </section>
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/ApprovedRequests.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import RequestApprovedTableComponent from '@/modules/request/components/RequestApprovedTableComponent.vue';
import RequestWorkflowModalComponent from '@/modules/request/components/RequestWorkflowModalComponent.vue';
import '@/modules/request/components/requestWorkflowModal.css';
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';

const authStore = useAuthenticationStore();
const requestStore = useRequestStore();
const searchQueryText = ref('');
const showingFilterValue = ref('all');
const selectedRequestRecord = ref(null);
const editRequestRecord = ref(null);
const deployRequestRecord = ref(null);
const cancelRequestRecord = ref(null);

const editForm = reactive({
  activityNameTitle: '',
  assignedPersonnel: '',
  requestSchedule: '',
  participantCount: 0,
  requestPurpose: '',
  remarks: '',
});
const deployForm = reactive({
  remarks: '',
  adminEmail: '',
});
const cancelForm = reactive({
  remarks: '',
  adminEmail: '',
});

const approvedRequestsList = computed(() => requestStore.approvedRequestsList || []);
const currentAdminEmail = computed(() => {
  const account = authStore.accountData || authStore.clerkAccountData || {};
  return String(account.emailAddress || account.email || '').trim().toLowerCase();
});

onMounted(async () => {
  try {
    await requestStore.fetchReservations();
    const list = requestStore.approvedRequestsList || [];
    console.log('Admin Approved Requests - Count:', list.length);
  } catch (error) {
    console.error('Error fetching approved requests:', error);
  }
});

function handleViewWorkflowDetails(requestRecord) {
  selectedRequestRecord.value = requestRecord;
}

function handleCloseWorkflowModal() {
  selectedRequestRecord.value = null;
}

function handleDeployRelease(requestRecord) {
  deployRequestRecord.value = requestRecord;
}

function handleEditWorkflow(requestRecord) {
  editRequestRecord.value = requestRecord;
  editForm.activityNameTitle = requestRecord?.activityNameTitle || '';
  editForm.assignedPersonnel = requestRecord?.assignedPersonnel || '';
  editForm.requestSchedule = requestRecord?.requestSchedule || '';
  editForm.participantCount = Number(requestRecord?.participantCount || 0);
  editForm.requestPurpose = requestRecord?.requestPurpose || '';
  editForm.remarks = requestRecord?.remarks || '';
}

function handleCancelRequest(requestRecord) {
  cancelRequestRecord.value = requestRecord;
}

function submitEditWorkflow() {
  if (!editRequestRecord.value) {
    return;
  }

  Object.assign(editRequestRecord.value, {
    activityNameTitle: editForm.activityNameTitle.trim(),
    assignedPersonnel: editForm.assignedPersonnel.trim(),
    requestSchedule: editForm.requestSchedule.trim(),
    participantCount: Number(editForm.participantCount || 0),
    requestPurpose: editForm.requestPurpose.trim(),
    remarks: editForm.remarks.trim(),
  });

  closeEditModal();
}

async function submitDeployRequest() {
  if (!deployRequestRecord.value) {
    return;
  }

  const emailError = validateAdminEmailConfirmation(deployForm.adminEmail, 'deploy');
  if (emailError) {
    window.alert(emailError);
    return;
  }

  try {
    if (deployForm.remarks.trim()) {
      deployRequestRecord.value.remarks = deployForm.remarks.trim();
    }

    await requestStore.deployApprovedRequest(deployRequestRecord.value, {
      confirmedAdminEmail: normalizeEmailForConfirmation(deployForm.adminEmail),
    });
    closeDeployModal();
    selectedRequestRecord.value = null;
  } catch (error) {
    window.alert(error?.message || 'Unable to deploy this request.');
  }
}

async function submitCancelRequest() {
  if (!cancelRequestRecord.value) {
    return;
  }

  if (cancelForm.remarks.trim() === '') {
    window.alert('Please add remarks before denying this request.');
    return;
  }

  const emailError = validateAdminEmailConfirmation(cancelForm.adminEmail, 'cancel');
  if (emailError) {
    window.alert(emailError);
    return;
  }

  try {
    await requestStore.cancelApprovedRequest(cancelRequestRecord.value, cancelForm.remarks.trim(), {
      confirmedAdminEmail: normalizeEmailForConfirmation(cancelForm.adminEmail),
    });
    closeCancelModal();
    selectedRequestRecord.value = null;
  } catch (error) {
    window.alert(error?.message || 'Unable to cancel this request.');
  }
}

function closeEditModal() {
  editRequestRecord.value = null;
  editForm.activityNameTitle = '';
  editForm.assignedPersonnel = '';
  editForm.requestSchedule = '';
  editForm.participantCount = 0;
  editForm.requestPurpose = '';
  editForm.remarks = '';
}

function closeDeployModal() {
  deployRequestRecord.value = null;
  deployForm.remarks = '';
  deployForm.adminEmail = '';
}

function closeCancelModal() {
  cancelRequestRecord.value = null;
  cancelForm.remarks = '';
  cancelForm.adminEmail = '';
}

function validateAdminEmailConfirmation(emailValue, actionName) {
  const normalizedEmail = normalizeEmailForConfirmation(emailValue);
  if (normalizedEmail === '') {
    return `Please type your exact admin email before ${actionName === 'deploy' ? 'deploying' : 'denying'} this request.`;
  }

  if (currentAdminEmail.value === '') {
    return 'Unable to verify the admin in charge. Please sign in again.';
  }

  if (normalizedEmail !== currentAdminEmail.value) {
    return `Please type your exact admin email before ${actionName === 'deploy' ? 'deploying' : 'denying'} this request.`;
  }

  return '';
}

function normalizeEmailForConfirmation(emailValue) {
  return String(emailValue || '').trim().toLowerCase();
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
</script>
