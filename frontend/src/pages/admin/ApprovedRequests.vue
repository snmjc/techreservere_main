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
          <p>Monitor approved reservations scheduled for future dates, review workflow details, and move requests forward before their reservation day.</p>
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
            <p>Manually update the workflow tasks for this approved request.</p>
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

            <div class="approved-request-summary-bottom">
              <div class="approved-request-summary-item">
                <span>Venue</span>
                <strong>{{ editRequestRecord.facilityName || 'N/A' }}</strong>
              </div>

              <div class="approved-request-summary-item">
                <span>Participants</span>
                <strong>{{ editRequestRecord.participantCount || 0 }}</strong>
              </div>

              <div class="approved-request-summary-item">
                <span>Start</span>
                <strong>{{ formatWorkflowDateTime(editRequestRecord.requestScheduleStart || editRequestRecord.activityTime) }}</strong>
              </div>

              <div class="approved-request-summary-item">
                <span>End</span>
                <strong>{{ formatWorkflowDateTime(editRequestRecord.requestScheduleEnd || editRequestRecord.activityEndTime) }}</strong>
              </div>
            </div>
          </section>

          <section class="approved-request-workflow-summary">
            <div class="approved-request-workflow-summary-card">
              <span>Equipment</span>
              <div class="approved-request-resource-list">
                <span
                  v-for="resource in getEquipmentResources(editRequestRecord)"
                  :key="`${resource.resourceName}-${resource.resourceCount}`"
                  class="approved-request-resource-chip"
                >
                  {{ resource.resourceName }} x{{ resource.resourceCount }}
                </span>
                <em v-if="!getEquipmentResources(editRequestRecord).length">No equipment reserved.</em>
              </div>
            </div>

            <div class="approved-request-workflow-summary-card approved-request-workflow-summary-card--details">
              <span>Workflow Details</span>
              <div class="approved-request-workflow-detail-grid">
                <div class="approved-request-workflow-detail-item">
                  <small>Venue</small>
                  <strong>{{ editRequestRecord.facilityName || 'N/A' }}</strong>
                </div>
                <div class="approved-request-workflow-detail-item">
                  <small>Status</small>
                  <strong>{{ editRequestRecord.requestStatus || 'Approved' }}</strong>
                </div>
                <div class="approved-request-workflow-detail-item">
                  <small>Participants</small>
                  <strong>{{ editRequestRecord.participantCount || 0 }}</strong>
                </div>
                <div class="approved-request-workflow-detail-item">
                  <small>Start Date and Time</small>
                  <strong>{{ formatWorkflowDateTime(editRequestRecord.requestScheduleStart || editRequestRecord.activityTime) }}</strong>
                </div>
                <div class="approved-request-workflow-detail-item">
                  <small>End Date and Time</small>
                  <strong>{{ formatWorkflowDateTime(editRequestRecord.requestScheduleEnd || editRequestRecord.activityEndTime) }}</strong>
                </div>
              </div>
            </div>
          </section>

          <p v-if="workflowEditorError" class="approved-request-action-feedback approved-request-action-feedback--error">
            {{ workflowEditorError }}
          </p>
          <p v-else-if="workflowEditorNotice" class="approved-request-action-feedback approved-request-action-feedback--notice">
            {{ workflowEditorNotice }}
          </p>

          <div v-if="isWorkflowEditorLoading" class="approved-request-workflow-loading">
            Loading workflow tasks...
          </div>

          <template v-else>
            <div
              v-for="(workflowTask, index) in workflowTasks"
              :key="workflowTask.localId"
              class="approved-request-workflow-task-card"
            >
              <div class="approved-request-workflow-task-header">
                <div>
                  <strong>Workflow Task {{ index + 1 }}</strong>
                  <small>{{ workflowTask.taskIdentifier ? `Task #${workflowTask.taskIdentifier}` : 'New task assignment' }}</small>
                </div>
                <button
                  v-if="workflowTask.isNew"
                  class="approved-request-workflow-remove"
                  type="button"
                  @click="removeWorkflowTaskRow(workflowTask.localId)"
                >
                  Remove
                </button>
              </div>

              <div class="approved-request-action-grid">
                <label class="approved-request-action-field">
                  <span>Task Assignments of Staff</span>
                  <input
                    v-model.trim="workflowTask.taskTitle"
                    type="text"
                    maxlength="200"
                    placeholder="Enter task assignment"
                  />
                </label>

                <label class="approved-request-action-field">
                  <span>Staff Assigned</span>
                  <select v-model="workflowTask.assignedToAccountId">
                    <option value="">Select staff</option>
                    <option v-for="staff in workflowStaffOptions" :key="staff.value" :value="staff.value">
                      {{ staff.label }}
                    </option>
                  </select>
                </label>

                <label class="approved-request-action-field">
                  <span>Ingress</span>
                  <input v-model="workflowTask.preparationStartTimestamp" type="datetime-local" />
                </label>

                <label class="approved-request-action-field">
                  <span>Egress</span>
                  <input v-model="workflowTask.preparationEndTimestamp" type="datetime-local" />
                </label>
              </div>

              <label class="approved-request-action-field approved-request-action-field--full">
                <span>Notes</span>
                <textarea
                  v-model.trim="workflowTask.taskDescription"
                  rows="4"
                  maxlength="1000"
                  placeholder="Add workflow notes..."
                />
              </label>
            </div>

            <button class="approved-request-workflow-add" type="button" @click="addWorkflowTaskRow">
              + Add Task Assignment
            </button>
          </template>
        </div>

        <footer class="approved-request-action-footer">
          <button class="approved-request-action-button approved-request-action-button--ghost" type="button" :disabled="isWorkflowSaving" @click="closeEditModal">Cancel</button>
          <button class="approved-request-action-button approved-request-action-button--edit" type="button" :disabled="isWorkflowEditorLoading || isWorkflowSaving || !hasWorkflowChanges" @click="submitEditWorkflow">
            {{ isWorkflowSaving ? 'Saving...' : 'Save Changes' }}
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
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { onBeforeRouteLeave, useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/ApprovedRequests.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import RequestApprovedTableComponent from '@/modules/request/components/RequestApprovedTableComponent.vue';
import RequestWorkflowModalComponent from '@/modules/request/components/RequestWorkflowModalComponent.vue';
import '@/modules/request/components/requestWorkflowModal.css';
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { taskWorkflowApi } from '@/modules/task/services/taskWorkflowApi.js';

const authStore = useAuthenticationStore();
const requestStore = useRequestStore();
const router = useRouter();
const searchQueryText = ref('');
const showingFilterValue = ref('all');
const selectedRequestRecord = ref(null);
const editRequestRecord = ref(null);
const deployRequestRecord = ref(null);
const cancelRequestRecord = ref(null);

const deployForm = reactive({
  remarks: '',
  adminEmail: '',
});
const cancelForm = reactive({
  remarks: '',
  adminEmail: '',
});
const workflowTasks = ref([]);
const workflowStaffOptions = ref([]);
const workflowEditorError = ref('');
const workflowEditorNotice = ref('');
const isWorkflowEditorLoading = ref(false);
const isWorkflowSaving = ref(false);
const workflowInitialSnapshot = ref('[]');
let workflowTaskCounter = 0;

const approvedRequestsList = computed(() => requestStore.approvedRequestsList || []);
const hasWorkflowChanges = computed(() => serializeWorkflowTasks(workflowTasks.value) !== workflowInitialSnapshot.value);
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

async function handleEditWorkflow(requestRecord) {
  selectedRequestRecord.value = null;
  editRequestRecord.value = requestRecord;
  workflowEditorError.value = '';
  workflowEditorNotice.value = '';
  isWorkflowEditorLoading.value = true;

  try {
    await Promise.all([
      loadWorkflowStaffOptions(),
      loadWorkflowTasks(requestRecord),
    ]);
  } finally {
    isWorkflowEditorLoading.value = false;
  }
}

function handleCancelRequest(requestRecord) {
  cancelRequestRecord.value = requestRecord;
}

async function submitEditWorkflow() {
  if (!editRequestRecord.value) {
    return;
  }

  if (isWorkflowSaving.value) {
    return;
  }

  if (!hasWorkflowChanges.value) {
    workflowEditorNotice.value = 'No workflow changes to save.';
    workflowEditorError.value = '';
    return;
  }

  const validationError = validateWorkflowTasks();
  if (validationError) {
    workflowEditorError.value = validationError;
    workflowEditorNotice.value = '';
    return;
  }

  isWorkflowSaving.value = true;
  workflowEditorError.value = '';
  workflowEditorNotice.value = '';

  try {
    for (const workflowTask of workflowTasks.value) {
      const payload = buildWorkflowTaskPayload(workflowTask, editRequestRecord.value.requestIdentifier);
      const result = workflowTask.taskIdentifier
        ? await taskWorkflowApi.updateTask(workflowTask.taskIdentifier, payload, authStore.authToken)
        : await taskWorkflowApi.createTask(payload, authStore.authToken);

      if (!result.success) {
        throw new Error(result.error || 'Unable to save workflow task.');
      }
    }

    await requestStore.fetchReservations();
    syncEditedRequestAssignmentSummary();
    workflowInitialSnapshot.value = serializeWorkflowTasks(workflowTasks.value);
    closeEditModal();
  } catch (error) {
    workflowEditorError.value = error?.message || 'Unable to save workflow changes.';
  } finally {
    isWorkflowSaving.value = false;
  }
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
    router.push({ name: 'adminActiveReservationsPage' });
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
  workflowTasks.value = [];
  workflowEditorError.value = '';
  workflowEditorNotice.value = '';
  workflowInitialSnapshot.value = '[]';
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

function getEquipmentResources(requestRecord) {
  return (requestRecord?.reservedResources || []).filter((resource) => resource.resourceType === 'Equipment');
}

function formatWorkflowDateTime(value) {
  if (!value) {
    return 'N/A';
  }

  const parsedDate = new Date(value);
  if (Number.isNaN(parsedDate.getTime())) {
    return String(value);
  }

  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    timeZone: 'Asia/Manila',
  }).format(parsedDate);
}

async function loadWorkflowStaffOptions() {
  if (workflowStaffOptions.value.length > 0) {
    return;
  }

  const result = await taskWorkflowApi.fetchAccounts(authStore.authToken);
  if (!result.success) {
    throw new Error(result.error || 'Unable to load staff assignments.');
  }

  workflowStaffOptions.value = normalizeStaffOptions(result.data.accounts || []);
}

async function loadWorkflowTasks(requestRecord) {
  const reservationIdentifier = Number(requestRecord?.requestIdentifier || 0);
  if (!reservationIdentifier) {
    workflowTasks.value = [createEmptyWorkflowTask()];
    workflowInitialSnapshot.value = serializeWorkflowTasks(workflowTasks.value);
    return;
  }

  const result = await taskWorkflowApi.fetchTasksByReservation(reservationIdentifier, authStore.authToken);
  if (!result.success) {
    throw new Error(result.error || 'Unable to load workflow tasks.');
  }

  const taskList = Array.isArray(result.data.tasks) ? result.data.tasks : [];
  workflowTasks.value = taskList.length > 0
    ? taskList.map(mapWorkflowTaskRecord)
    : [createEmptyWorkflowTask()];
  workflowInitialSnapshot.value = serializeWorkflowTasks(workflowTasks.value);
}

function addWorkflowTaskRow() {
  workflowTasks.value.push(createEmptyWorkflowTask());
  workflowEditorNotice.value = '';
}

function removeWorkflowTaskRow(localId) {
  workflowTasks.value = workflowTasks.value.filter((workflowTask) => workflowTask.localId !== localId);
  if (workflowTasks.value.length === 0) {
    workflowTasks.value = [createEmptyWorkflowTask()];
  }
  workflowEditorNotice.value = '';
}

function validateWorkflowTasks() {
  if (workflowTasks.value.length === 0) {
    return 'Add at least one workflow task before saving.';
  }

  for (const [index, workflowTask] of workflowTasks.value.entries()) {
    const label = `Workflow Task ${index + 1}`;
    if (workflowTask.taskTitle.trim() === '') {
      return `${label}: task assignment is required.`;
    }
    if (!workflowTask.assignedToAccountId) {
      return `${label}: please select the assigned staff.`;
    }
    if (!workflowTask.preparationStartTimestamp) {
      return `${label}: ingress time is required.`;
    }
    if (!workflowTask.preparationEndTimestamp) {
      return `${label}: egress time is required.`;
    }
    if (new Date(workflowTask.preparationEndTimestamp).getTime() < new Date(workflowTask.preparationStartTimestamp).getTime()) {
      return `${label}: egress time must be later than ingress time.`;
    }
  }

  return '';
}

function buildWorkflowTaskPayload(workflowTask, reservationIdentifier) {
  return {
    taskTitle: workflowTask.taskTitle.trim(),
    taskDescription: workflowTask.taskDescription.trim(),
    taskType: workflowTask.taskType || 'Preparation',
    taskStatus: workflowTask.taskStatus || 'Pending',
    reservationIdentifier: Number(reservationIdentifier),
    assignedToAccountId: Number(workflowTask.assignedToAccountId),
    dueDateTimestamp: normalizeDateTimeForApi(workflowTask.preparationEndTimestamp),
    preparationStartTimestamp: normalizeDateTimeForApi(workflowTask.preparationStartTimestamp),
    preparationEndTimestamp: normalizeDateTimeForApi(workflowTask.preparationEndTimestamp),
    emergencyOverride: false,
    confirmedAdminEmail: '',
    confirmedAdminPassword: '',
  };
}

function createEmptyWorkflowTask() {
  workflowTaskCounter += 1;

  return {
    localId: `workflow-task-${workflowTaskCounter}`,
    taskIdentifier: null,
    taskTitle: '',
    taskDescription: '',
    taskType: 'Preparation',
    taskStatus: 'Pending',
    assignedToAccountId: '',
    preparationStartTimestamp: '',
    preparationEndTimestamp: '',
    isNew: true,
  };
}

function mapWorkflowTaskRecord(taskRecord) {
  workflowTaskCounter += 1;

  return {
    localId: `workflow-task-${workflowTaskCounter}`,
    taskIdentifier: taskRecord.taskIdentifier || null,
    taskTitle: taskRecord.taskTitle || '',
    taskDescription: taskRecord.taskDescription || '',
    taskType: taskRecord.taskType || 'Preparation',
    taskStatus: taskRecord.taskStatus || 'Pending',
    assignedToAccountId: taskRecord.assignedToAccountId ? String(taskRecord.assignedToAccountId) : '',
    preparationStartTimestamp: toDateTimeLocal(taskRecord.preparationStartTimestamp),
    preparationEndTimestamp: toDateTimeLocal(taskRecord.preparationEndTimestamp || taskRecord.dueDateTimestamp),
    isNew: false,
  };
}

function normalizeDateTimeForApi(value) {
  if (!value) {
    return null;
  }

  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? null : date.toISOString();
}

function toDateTimeLocal(value) {
  if (!value) {
    return '';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return '';
  }

  const offsetDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
  return offsetDate.toISOString().slice(0, 16);
}

function normalizeStaffOptions(accounts) {
  return accounts
    .filter((account) => resolveAccountType(account) === 'Employee')
    .map((account) => ({
      value: String(account.accountIdentifier || account.account_identifier),
      label: [
        `${account.firstName || account.first_name || ''} ${account.lastName || account.last_name || ''}`.trim(),
        account.idNumber || account.id_number,
        account.roleLabel || account.department,
      ].filter(Boolean).join(' - '),
    }))
    .filter((staff) => staff.value);
}

function resolveAccountType(account) {
  const role = String(account.roleDesignation || account.role_designation || '').toUpperCase();
  const department = String(account.department || '').toLowerCase();
  if (role.includes('STAFF') || department.includes('staff') || department.includes('maintenance') || department.includes('support')) {
    return 'Employee';
  }

  return account.accountType || account.account_type || '';
}

function serializeWorkflowTasks(taskList) {
  return JSON.stringify(taskList.map((workflowTask) => ({
    taskIdentifier: workflowTask.taskIdentifier || null,
    taskTitle: workflowTask.taskTitle || '',
    taskDescription: workflowTask.taskDescription || '',
    taskType: workflowTask.taskType || 'Preparation',
    taskStatus: workflowTask.taskStatus || 'Pending',
    assignedToAccountId: workflowTask.assignedToAccountId || '',
    preparationStartTimestamp: workflowTask.preparationStartTimestamp || '',
    preparationEndTimestamp: workflowTask.preparationEndTimestamp || '',
    isNew: Boolean(workflowTask.isNew),
  })));
}

function syncEditedRequestAssignmentSummary() {
  if (!editRequestRecord.value) {
    return;
  }

  const assignedPersonnel = workflowTasks.value
    .map((workflowTask) => workflowStaffOptions.value.find((staff) => staff.value === String(workflowTask.assignedToAccountId)))
    .filter(Boolean)
    .map((staff) => staff.label.split(' - ')[0])
    .filter((name, index, list) => list.indexOf(name) === index)
    .join(', ');

  editRequestRecord.value.assignedPersonnel = assignedPersonnel || 'Pending Assignment';
}

function resetWorkflowDraftsOnTabChange() {
  if (!editRequestRecord.value || isWorkflowSaving.value || document.visibilityState !== 'hidden') {
    return;
  }

  restoreWorkflowDrafts('Unsaved workflow inputs were cleared after switching tabs.');
}

function resetWorkflowDraftsOnWindowBlur() {
  if (!editRequestRecord.value || isWorkflowSaving.value) {
    return;
  }

  restoreWorkflowDrafts('Unsaved workflow inputs were cleared after switching tabs.');
}

function restoreWorkflowDrafts(noticeMessage) {
  if (serializeWorkflowTasks(workflowTasks.value) === workflowInitialSnapshot.value) {
    return;
  }

  workflowTasks.value = JSON.parse(workflowInitialSnapshot.value).map((workflowTask) => ({
    ...workflowTask,
    localId: createWorkflowLocalId(),
  }));
  workflowEditorError.value = '';
  workflowEditorNotice.value = noticeMessage;
}

function createWorkflowLocalId() {
  workflowTaskCounter += 1;
  return `workflow-task-${workflowTaskCounter}`;
}

document.addEventListener('visibilitychange', resetWorkflowDraftsOnTabChange);
window.addEventListener('blur', resetWorkflowDraftsOnWindowBlur);

onBeforeRouteLeave(() => {
  if (editRequestRecord.value && !isWorkflowSaving.value) {
    restoreWorkflowDrafts('Unsaved workflow inputs were cleared after leaving the page.');
  }
});

onBeforeUnmount(() => {
  document.removeEventListener('visibilitychange', resetWorkflowDraftsOnTabChange);
  window.removeEventListener('blur', resetWorkflowDraftsOnWindowBlur);
});
</script>
