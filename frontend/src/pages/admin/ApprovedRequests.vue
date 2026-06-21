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
          @deploy-release-record="handleDeployRelease"
          @cancel-request-record="handleCancelRequest"
        />
      </div>

      <div class="admin-ops-page-footer approved-requests-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </div>
    </section>

    <!-- Workflow Modal -->
    <RequestWorkflowModalComponent
      :request-record="selectedRequestRecord"
      @close-workflow-modal="handleCloseWorkflowModal"
      @deploy-release-record="handleDeployRelease"
      @edit-workflow-record="handleEditWorkflow"
      @cancel-workflow-record="handleCancelRequest"
    />

    <div v-if="showWorkflowEditor" class="approved-workflow-editor-overlay" @click.self="closeWorkflowEditor">
      <section class="approved-workflow-editor">
        <header class="approved-workflow-editor__header">
          <div>
            <p class="approved-workflow-editor__eyebrow">Workflow Assignment</p>
            <h2>{{ workflowEditorMode === 'update' ? 'Edit Workflow' : 'Create Workflow' }}</h2>
            <p>Assign staff, link the reservation, and set the deployment task details.</p>
          </div>
          <button type="button" class="approved-workflow-editor__close" @click="closeWorkflowEditor">x</button>
        </header>

        <p v-if="workflowEditorError" class="approved-workflow-editor__error">{{ workflowEditorError }}</p>

        <form class="approved-workflow-editor__form" @submit.prevent="submitWorkflowEditor">
          <label>
            <span>Venue</span>
            <select v-model="workflowVenueFilter">
              <option v-for="option in workflowVenueOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
          </label>

          <label>
            <span>Reservation Details</span>
            <select v-model="workflowForm.reservationIdentifier">
              <option value="">Select reservation</option>
              <option v-for="option in filteredWorkflowReservationOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
          </label>

          <label>
            <span>Assigned Staff</span>
            <select v-model="workflowForm.assignedToAccountId">
              <option value="">Select staff</option>
              <option v-for="option in staffOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
          </label>

          <label>
            <span>Task Type</span>
            <select v-model="workflowForm.taskType">
              <option v-for="option in workflowTaskTypeOptions" :key="option" :value="option">{{ option }}</option>
            </select>
          </label>

          <label>
            <span>Task Name</span>
            <input v-model.trim="workflowForm.taskTitle" type="text" maxlength="200" />
          </label>

          <label>
            <span>Status</span>
            <select v-model="workflowForm.taskStatus">
              <option v-for="option in workflowStatusOptions" :key="option" :value="option">{{ option }}</option>
            </select>
          </label>

          <label class="approved-workflow-editor__full">
            <span>Description</span>
            <textarea v-model.trim="workflowForm.taskDescription" rows="3"></textarea>
          </label>

          <label>
            <span>Due Schedule</span>
            <input v-model="workflowForm.dueDateTimestamp" type="datetime-local" />
          </label>

          <label>
            <span>Facility Preview</span>
            <input :value="selectedWorkflowReservation?.facilityName || 'No facility selected'" type="text" readonly />
          </label>

          <footer class="approved-workflow-editor__actions approved-workflow-editor__full">
            <button type="button" class="approved-workflow-editor__button approved-workflow-editor__button--ghost" :disabled="isWorkflowEditorSubmitting" @click="closeWorkflowEditor">
              Cancel
            </button>
            <button type="submit" class="approved-workflow-editor__button approved-workflow-editor__button--primary" :disabled="isWorkflowEditorSubmitting">
              {{ isWorkflowEditorSubmitting ? 'Saving...' : (workflowEditorMode === 'update' ? 'Save Workflow' : 'Create Workflow') }}
            </button>
          </footer>
        </form>
      </section>
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/ApprovedRequests.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import RequestApprovedTableComponent from '@/modules/request/components/RequestApprovedTableComponent.vue';
import RequestWorkflowModalComponent from '@/modules/request/components/RequestWorkflowModalComponent.vue';
import '@/modules/request/components/requestWorkflowModal.css';
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import taskApi from '@/modules/task/services/taskApi.js';
import { apiUrl } from '@/shared/utils/apiBase.js';
import { buildAuthorizationHeaders } from '@/shared/utils/authToken.js';

const authStore = useAuthenticationStore();
const requestStore = useRequestStore();
const searchQueryText = ref('');
const showingFilterValue = ref('all');
const selectedRequestRecord = ref(null);
const staffOptions = ref([]);
const showWorkflowEditor = ref(false);
const workflowEditorMode = ref('create');
const workflowEditorTaskIdentifier = ref(null);
const workflowEditorError = ref('');
const isWorkflowEditorSubmitting = ref(false);
const workflowVenueFilter = ref('all');
const workflowTaskTypeOptions = ['Preparation', 'Deployment', 'Maintenance', 'Inspection', 'Return'];
const workflowStatusOptions = ['Pending', 'In Progress', 'Completed', 'Cancelled'];
const workflowForm = reactive({
  reservationIdentifier: '',
  assignedToAccountId: '',
  taskType: 'Preparation',
  taskTitle: '',
  taskDescription: '',
  dueDateTimestamp: '',
  taskStatus: 'Pending',
});

const approvedRequestsList = computed(() => requestStore.approvedRequestsList || []);
const workflowVenueOptions = computed(() => {
  const venueMap = new Map();
  approvedRequestsList.value.forEach((record) => {
    const key = String(record.facilityName || '').trim();
    if (key !== '' && !venueMap.has(key)) {
      venueMap.set(key, { value: key, label: key });
    }
  });

  return [{ value: 'all', label: 'All venues' }, ...venueMap.values()];
});
const filteredWorkflowReservationOptions = computed(() => approvedRequestsList.value
  .filter((record) => workflowVenueFilter.value === 'all' || record.facilityName === workflowVenueFilter.value)
  .map((record) => ({
    value: String(record.requestIdentifier),
    label: [
      record.requestDisplayIdentifier || record.requestIdentifier,
      record.requesterFullName,
      record.facilityName,
      formatScheduleLabel(record.requestSchedule),
    ].filter(Boolean).join(' - '),
  })));
const selectedWorkflowReservation = computed(() => approvedRequestsList.value
  .find((record) => String(record.requestIdentifier) === String(workflowForm.reservationIdentifier)));

onMounted(async () => {
  try {
    await Promise.all([requestStore.fetchReservations(), loadStaffOptions()]);
    const list = requestStore.approvedRequestsList || [];
    console.log('Admin Approved Requests - Count:', list.length);
  } catch (error) {
    console.error('Error fetching approved requests:', error);
  }
});

watch(() => workflowForm.reservationIdentifier, (reservationIdentifier) => {
  const linkedReservation = approvedRequestsList.value.find((record) => String(record.requestIdentifier) === String(reservationIdentifier));
  if (!linkedReservation) {
    return;
  }

  if (workflowForm.taskTitle.trim() === '') {
    workflowForm.taskTitle = buildDefaultWorkflowTitle(linkedReservation, workflowForm.taskType);
  }

  if (workflowVenueFilter.value === 'all' && linkedReservation.facilityName) {
    workflowVenueFilter.value = linkedReservation.facilityName;
  }
});

watch(() => workflowForm.taskType, (taskType) => {
  if (!selectedWorkflowReservation.value) {
    return;
  }

  if (workflowEditorMode.value === 'create' || workflowForm.taskTitle.trim() === '') {
    workflowForm.taskTitle = buildDefaultWorkflowTitle(selectedWorkflowReservation.value, taskType);
  }
});

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
  workflowEditorError.value = '';
  workflowEditorMode.value = Number(requestRecord?.workflowTaskIdentifier || 0) > 0 ? 'update' : 'create';
  workflowEditorTaskIdentifier.value = Number(requestRecord?.workflowTaskIdentifier || 0) || null;
  workflowVenueFilter.value = requestRecord?.facilityName || 'all';
  workflowForm.reservationIdentifier = String(requestRecord?.requestIdentifier || '');
  workflowForm.assignedToAccountId = resolveAssignedStaffAccountId(requestRecord);
  workflowForm.taskType = requestRecord?.workflowTaskType || 'Preparation';
  workflowForm.taskTitle = requestRecord?.workflowTaskTitle || buildDefaultWorkflowTitle(requestRecord, workflowForm.taskType);
  workflowForm.taskDescription = Array.isArray(requestRecord?.workflowTasks) && requestRecord.workflowTasks[0]?.taskDescription
    ? requestRecord.workflowTasks[0].taskDescription
    : '';
  workflowForm.dueDateTimestamp = toDateTimeLocal(requestRecord?.workflowDueDateTimestamp);
  workflowForm.taskStatus = requestRecord?.workflowStatus || 'Pending';
  showWorkflowEditor.value = true;
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

async function loadStaffOptions() {
  const result = await requestJson('/api/v1/accounts');
  if (!result.success) {
    return;
  }

  staffOptions.value = (result.data.accounts || [])
    .filter((account) => String(account.roleDesignation || account.role_designation || '').toUpperCase().includes('STAFF'))
    .map((account) => ({
      value: String(account.accountIdentifier || account.account_identifier),
      label: [
        `${account.firstName || account.first_name || ''} ${account.lastName || account.last_name || ''}`.trim(),
        account.idNumber || account.id_number,
        account.department,
      ].filter(Boolean).join(' - '),
    }));
}

function closeWorkflowEditor() {
  showWorkflowEditor.value = false;
  workflowEditorTaskIdentifier.value = null;
  workflowEditorError.value = '';
  workflowVenueFilter.value = 'all';
  workflowForm.reservationIdentifier = '';
  workflowForm.assignedToAccountId = '';
  workflowForm.taskType = 'Preparation';
  workflowForm.taskTitle = '';
  workflowForm.taskDescription = '';
  workflowForm.dueDateTimestamp = '';
  workflowForm.taskStatus = 'Pending';
}

async function submitWorkflowEditor() {
  if (isWorkflowEditorSubmitting.value) {
    return;
  }

  if (!workflowForm.reservationIdentifier) {
    workflowEditorError.value = 'Please select reservation details for this workflow.';
    return;
  }

  if (!workflowForm.assignedToAccountId) {
    workflowEditorError.value = 'Please assign a staff member.';
    return;
  }

  if (workflowForm.taskTitle.trim() === '') {
    workflowEditorError.value = 'Task name is required.';
    return;
  }

  const payload = {
    taskTitle: workflowForm.taskTitle.trim(),
    taskDescription: workflowForm.taskDescription.trim(),
    taskType: workflowForm.taskType,
    reservationIdentifier: Number(workflowForm.reservationIdentifier),
    assignedToAccountId: Number(workflowForm.assignedToAccountId),
    dueDateTimestamp: workflowForm.dueDateTimestamp || null,
    taskStatus: workflowForm.taskStatus,
    emergencyOverride: false,
    confirmedAdminEmail: '',
    confirmedAdminPassword: '',
  };

  try {
    isWorkflowEditorSubmitting.value = true;
    workflowEditorError.value = '';

    if (workflowEditorMode.value === 'update' && workflowEditorTaskIdentifier.value) {
      await taskApi.updateTask(workflowEditorTaskIdentifier.value, payload);
    } else {
      await taskApi.createTask(payload);
    }

    await requestStore.fetchReservations();
    const refreshedRecord = approvedRequestsList.value.find((record) => record.requestIdentifier === Number(workflowForm.reservationIdentifier));
    selectedRequestRecord.value = refreshedRecord || null;
    closeWorkflowEditor();
  } catch (error) {
    workflowEditorError.value = error?.response?.data?.errorMessage || error?.message || 'Unable to save workflow.';
  } finally {
    isWorkflowEditorSubmitting.value = false;
  }
}

async function requestJson(path) {
  try {
    const response = await fetch(apiUrl(path), {
      headers: buildAuthorizationHeaders(authStore.authToken),
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      return { success: false, error: payload.errorMessage || payload.message || 'Request failed.' };
    }

    return { success: true, data: payload.data || payload };
  } catch (error) {
    return { success: false, error: error?.message || 'Request failed.' };
  }
}

function resolveAssignedStaffAccountId(requestRecord) {
  const linkedTask = Array.isArray(requestRecord?.workflowTasks) ? requestRecord.workflowTasks[0] : null;
  return linkedTask?.assignedToAccountId ? String(linkedTask.assignedToAccountId) : '';
}

function buildDefaultWorkflowTitle(requestRecord, taskType) {
  return [taskType, requestRecord?.facilityName || requestRecord?.activityNameTitle || 'Reservation'].filter(Boolean).join(' - ');
}

function toDateTimeLocal(value) {
  if (!value) {
    return '';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return '';
  }

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  return `${year}-${month}-${day}T${hours}:${minutes}`;
}

function formatScheduleLabel(value) {
  if (!value) {
    return '';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return String(value);
  }

  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  }).format(date);
}
</script>

<style scoped>
.approved-workflow-editor-overlay {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: rgba(15, 23, 42, 0.48);
  z-index: 1000;
}

.approved-workflow-editor {
  width: min(760px, 100%);
  max-height: 90vh;
  overflow: auto;
  background: #ffffff;
  border-radius: 18px;
  border: 1px solid #dbe4de;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
}

.approved-workflow-editor__header,
.approved-workflow-editor__actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.approved-workflow-editor__header {
  padding: 1.2rem 1.3rem 1rem;
  border-bottom: 1px solid #edf1ee;
}

.approved-workflow-editor__header h2,
.approved-workflow-editor__header p {
  margin: 0;
}

.approved-workflow-editor__eyebrow {
  margin-bottom: 0.2rem !important;
  color: #6b7280;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.approved-workflow-editor__close {
  border: none;
  background: transparent;
  color: #6b7280;
  font-size: 1.2rem;
  cursor: pointer;
}

.approved-workflow-editor__error {
  margin: 0;
  padding: 0.95rem 1.3rem 0;
  color: #b91c1c;
  font-size: 0.9rem;
  font-weight: 600;
}

.approved-workflow-editor__form {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem 1rem;
  padding: 1rem 1.3rem 1.3rem;
}

.approved-workflow-editor__form label {
  display: grid;
  gap: 0.35rem;
}

.approved-workflow-editor__form span {
  color: #374151;
  font-size: 0.84rem;
  font-weight: 600;
}

.approved-workflow-editor__form input,
.approved-workflow-editor__form select,
.approved-workflow-editor__form textarea {
  width: 100%;
  border: 1px solid #d7dfd9;
  border-radius: 10px;
  background: #ffffff;
  color: #111827;
  font: inherit;
  padding: 0.8rem 0.9rem;
}

.approved-workflow-editor__full {
  grid-column: 1 / -1;
}

.approved-workflow-editor__actions {
  justify-content: flex-end;
  padding-top: 0.5rem;
}

.approved-workflow-editor__button {
  border: none;
  border-radius: 999px;
  padding: 0.8rem 1.2rem;
  font: inherit;
  font-weight: 700;
  cursor: pointer;
}

.approved-workflow-editor__button--ghost {
  background: #eef2ef;
  color: #1f2937;
}

.approved-workflow-editor__button--primary {
  background: #166534;
  color: #ffffff;
}

@media (max-width: 720px) {
  .approved-workflow-editor__form {
    grid-template-columns: 1fr;
  }
}
</style>
