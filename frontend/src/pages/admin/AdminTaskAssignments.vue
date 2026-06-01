<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="admin-task-assignments-page">
      <header class="admin-task-assignments-header">
        <div>
          <h1>Task Assignments</h1>
          <p>Review reservation tasks assigned to staff accounts.</p>
        </div>
        <div class="admin-task-assignments-toolbar">
          <button class="admin-task-assignments-secondary" type="button" :disabled="isLoading" @click="loadPageData">
            {{ isLoading ? 'Refreshing...' : 'Refresh' }}
          </button>
          <button class="admin-task-assignments-primary" type="button" @click="openCreateModal">
            Create Task
          </button>
        </div>
      </header>

      <p v-if="loadError" class="admin-task-assignments-error">{{ loadError }}</p>

      <div v-if="isLoading" class="admin-task-assignments-state">
        Loading task assignments...
      </div>

      <div v-else-if="tasks.length === 0" class="admin-task-assignments-state">
        No task assignments found.
      </div>

      <div v-else class="admin-task-assignments-list">
        <article
          v-for="task in tasks"
          :key="task.taskIdentifier"
          class="admin-task-assignment-card"
        >
          <div class="admin-task-assignment-main">
            <div>
              <h2>{{ task.taskTitle }}</h2>
              <p>{{ task.taskDescription || 'No task description provided.' }}</p>
            </div>
            <span class="admin-task-assignment-status">{{ task.taskStatus }}</span>
          </div>

          <dl class="admin-task-assignment-meta">
            <div>
              <dt>Task Type</dt>
              <dd>{{ task.taskType || 'N/A' }}</dd>
            </div>
            <div>
              <dt>Reservation</dt>
              <dd>{{ task.reservationLabel || formatReservationLabel(task.reservationIdentifier) }}</dd>
            </div>
            <div>
              <dt>Assigned Staff</dt>
              <dd>{{ formatStaffLabel(task) }}</dd>
            </div>
            <div>
              <dt>Due Date</dt>
              <dd>{{ formatDateTime(task.dueDateTimestamp) }}</dd>
            </div>
          </dl>

          <div class="admin-task-assignment-actions">
            <button type="button" class="admin-task-assignments-secondary" @click="openUpdateModal(task)">Update</button>
            <button type="button" class="admin-task-assignments-danger" @click="openDeleteModal(task)">Delete</button>
          </div>
        </article>
      </div>
    </section>

    <div v-if="showTaskModal" class="admin-task-assignments-modal-overlay" @click.self="closeTaskModal">
      <section class="admin-task-assignments-modal">
        <header class="admin-task-assignments-modal-header">
          <div>
            <h2>{{ taskModalMode === 'create' ? 'Create Task Assignment' : 'Update Task Assignment' }}</h2>
            <p>Save the task details and staff assignment.</p>
          </div>
          <button type="button" aria-label="Close" @click="closeTaskModal">x</button>
        </header>

        <p v-if="modalError" class="admin-task-assignments-error">{{ modalError }}</p>

        <form class="admin-task-assignments-form" @submit.prevent="submitTaskForm">
          <label>
            <span>Task Name</span>
            <input v-model.trim="taskForm.taskTitle" type="text" maxlength="200" autocomplete="off" />
          </label>

          <label>
            <span>Description</span>
            <textarea v-model.trim="taskForm.taskDescription" rows="3"></textarea>
          </label>

          <label>
            <span>Task Type</span>
            <select v-model="taskForm.taskType">
              <option v-for="option in taskTypeOptions" :key="option" :value="option">{{ option }}</option>
            </select>
          </label>

          <label>
            <span>Reservation</span>
            <select v-model="taskForm.reservationIdentifier">
              <option value="">Select reservation</option>
              <option v-for="reservation in reservationOptions" :key="reservation.value" :value="reservation.value">
                {{ reservation.label }}
              </option>
            </select>
          </label>

          <label>
            <span>Assigned Staff</span>
            <select v-model="taskForm.assignedToAccountId">
              <option value="">Select staff</option>
              <option v-for="staff in staffOptions" :key="staff.value" :value="staff.value">
                {{ staff.label }}
              </option>
            </select>
          </label>

          <label>
            <span>Due Date</span>
            <input v-model="taskForm.dueDateTimestamp" type="datetime-local" />
          </label>

          <label>
            <span>Status</span>
            <select v-model="taskForm.taskStatus">
              <option v-for="option in statusOptions" :key="option" :value="option">{{ option }}</option>
            </select>
          </label>

          <section class="admin-task-assignments-override">
            <label class="admin-task-assignments-checkbox">
              <input v-model="taskForm.emergencyOverride" type="checkbox" />
              <span>Emergency override</span>
            </label>
            <div v-if="taskForm.emergencyOverride" class="admin-task-assignments-security-grid">
              <label>
                <span>Admin Email</span>
                <input v-model.trim="taskForm.confirmedAdminEmail" type="email" :placeholder="currentAdminEmail || 'admin@techreserve.edu.ph'" />
              </label>
              <label>
                <span>Admin Password</span>
                <input v-model="taskForm.confirmedAdminPassword" type="password" autocomplete="current-password" />
              </label>
            </div>
          </section>

          <footer class="admin-task-assignments-modal-actions">
            <button type="button" class="admin-task-assignments-secondary" :disabled="isSubmitting" @click="closeTaskModal">Cancel</button>
            <button type="submit" class="admin-task-assignments-primary" :disabled="isSubmitting">
              {{ isSubmitting ? (taskModalMode === 'create' ? 'Creating...' : 'Saving...') : (taskModalMode === 'create' ? 'Create' : 'Save Changes') }}
            </button>
          </footer>
        </form>
      </section>
    </div>

    <div v-if="deleteTask" class="admin-task-assignments-modal-overlay" @click.self="closeDeleteModal">
      <section class="admin-task-assignments-modal admin-task-assignments-modal--narrow">
        <header class="admin-task-assignments-modal-header">
          <div>
            <h2>Delete Task Assignment</h2>
            <p>This action will permanently delete the selected task assignment.</p>
          </div>
          <button type="button" aria-label="Close" @click="closeDeleteModal">x</button>
        </header>

        <div class="admin-task-assignments-delete-summary">
          <p><strong>Task Name</strong><span>{{ deleteTask.taskTitle }}</span></p>
          <p><strong>Assigned Staff</strong><span>{{ formatStaffLabel(deleteTask) }}</span></p>
          <p><strong>Reservation</strong><span>{{ deleteTask.reservationLabel || formatReservationLabel(deleteTask.reservationIdentifier) }}</span></p>
          <p><strong>Status</strong><span>{{ deleteTask.taskStatus }}</span></p>
        </div>

        <p v-if="modalError" class="admin-task-assignments-error">{{ modalError }}</p>

        <div class="admin-task-assignments-form">
          <label>
            <span>Admin Email</span>
            <input v-model.trim="deleteForm.confirmedAdminEmail" type="email" :placeholder="currentAdminEmail || 'admin@techreserve.edu.ph'" />
          </label>
          <label>
            <span>Admin Password</span>
            <input v-model="deleteForm.confirmedAdminPassword" type="password" autocomplete="current-password" />
          </label>
        </div>

        <footer class="admin-task-assignments-modal-actions">
          <button type="button" class="admin-task-assignments-secondary" :disabled="isSubmitting" @click="closeDeleteModal">Cancel</button>
          <button type="button" class="admin-task-assignments-danger" :disabled="!canDelete || isSubmitting" @click="confirmDeleteTask">
            {{ isSubmitting ? 'Deleting...' : 'Delete' }}
          </button>
        </footer>
      </section>
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { apiUrl } from '@/shared/utils/apiBase.js';
import { AUTH_STORAGE_KEYS } from '@/modules/authentication/utils/authStorage.js';

const authStore = useAuthenticationStore();
const isLoading = ref(false);
const isSubmitting = ref(false);
const loadError = ref('');
const modalError = ref('');
const tasks = ref([]);
const reservationOptions = ref([]);
const staffOptions = ref([]);
const showTaskModal = ref(false);
const taskModalMode = ref('create');
const editingTask = ref(null);
const deleteTask = ref(null);

const taskTypeOptions = ['Preparation', 'Deployment', 'Maintenance', 'Inspection', 'Return'];
const statusOptions = ['Pending', 'In Progress', 'Completed', 'Cancelled'];

const taskForm = reactive({
  taskTitle: '',
  taskDescription: '',
  taskType: 'Preparation',
  reservationIdentifier: '',
  assignedToAccountId: '',
  dueDateTimestamp: '',
  taskStatus: 'Pending',
  emergencyOverride: false,
  confirmedAdminEmail: '',
  confirmedAdminPassword: '',
});

const deleteForm = reactive({
  confirmedAdminEmail: '',
  confirmedAdminPassword: '',
});

const currentAdminEmail = computed(() => {
  const account = authStore.accountData || authStore.clerkAccountData || {};
  return String(account.emailAddress || account.email || '').trim();
});

const canDelete = computed(() => deleteForm.confirmedAdminEmail.trim() !== '' && deleteForm.confirmedAdminPassword.trim() !== '');

onMounted(() => {
  loadPageData();
});

async function loadPageData() {
  isLoading.value = true;
  loadError.value = '';
  const [tasksResult, reservationsResult, accountsResult] = await Promise.all([
    requestJson('/api/v1/tasks'),
    requestJson('/api/v1/reservations'),
    requestJson('/api/v1/accounts'),
  ]);

  if (!tasksResult.success) {
    loadError.value = tasksResult.error || 'Unable to load task assignments.';
    tasks.value = [];
  } else {
    tasks.value = tasksResult.data.tasks || [];
  }

  reservationOptions.value = reservationsResult.success
    ? normalizeReservations(reservationsResult.data.reservations || [])
    : [];
  staffOptions.value = accountsResult.success
    ? normalizeStaff(accountsResult.data.accounts || [])
    : [];

  if (!loadError.value && (!reservationsResult.success || !accountsResult.success)) {
    loadError.value = reservationsResult.error || accountsResult.error || 'Unable to load task form options.';
  }

  isLoading.value = false;
}

function openCreateModal() {
  resetTaskForm();
  taskModalMode.value = 'create';
  editingTask.value = null;
  showTaskModal.value = true;
}

function openUpdateModal(task) {
  resetTaskForm();
  taskModalMode.value = 'update';
  editingTask.value = task;
  taskForm.taskTitle = task.taskTitle || '';
  taskForm.taskDescription = task.taskDescription || '';
  taskForm.taskType = task.taskType || 'Preparation';
  taskForm.reservationIdentifier = task.reservationIdentifier ? String(task.reservationIdentifier) : '';
  taskForm.assignedToAccountId = task.assignedToAccountId ? String(task.assignedToAccountId) : '';
  taskForm.dueDateTimestamp = toDateTimeLocal(task.dueDateTimestamp);
  taskForm.taskStatus = task.taskStatus || 'Pending';
  showTaskModal.value = true;
}

function openDeleteModal(task) {
  deleteTask.value = task;
  resetDeleteForm();
  modalError.value = '';
}

function closeTaskModal() {
  showTaskModal.value = false;
  editingTask.value = null;
  resetTaskForm();
  modalError.value = '';
}

function closeDeleteModal() {
  deleteTask.value = null;
  resetDeleteForm();
  modalError.value = '';
}

async function submitTaskForm() {
  if (isSubmitting.value) return;

  const validationError = validateTaskForm();
  if (validationError) {
    modalError.value = validationError;
    return;
  }

  isSubmitting.value = true;
  modalError.value = '';
  const payload = buildTaskPayload();
  const endpoint = taskModalMode.value === 'create'
    ? '/api/v1/tasks'
    : `/api/v1/tasks/${editingTask.value.taskIdentifier}`;
  const method = taskModalMode.value === 'create' ? 'POST' : 'PUT';
  const result = await requestJson(endpoint, { method, body: JSON.stringify(payload) });
  isSubmitting.value = false;

  if (!result.success) {
    modalError.value = result.error || 'Unable to save task assignment.';
    return;
  }

  closeTaskModal();
  await loadPageData();
}

async function confirmDeleteTask() {
  if (!deleteTask.value || isSubmitting.value || !canDelete.value) return;

  isSubmitting.value = true;
  modalError.value = '';
  const result = await requestJson(`/api/v1/tasks/${deleteTask.value.taskIdentifier}`, {
    method: 'DELETE',
    body: JSON.stringify({
      confirmedAdminEmail: normalizeEmailForConfirmation(deleteForm.confirmedAdminEmail),
      confirmedAdminPassword: deleteForm.confirmedAdminPassword,
    }),
  });
  isSubmitting.value = false;

  if (!result.success) {
    modalError.value = result.error || 'Unable to delete task assignment.';
    return;
  }

  closeDeleteModal();
  await loadPageData();
}

function validateTaskForm() {
  if (taskForm.taskTitle.trim() === '') return 'Task name is required.';
  if (taskForm.taskTitle.trim().length > 200) return 'Task name must not exceed 200 characters.';
  if (taskForm.taskType.trim() === '') return 'Task type is required.';
  if (!statusOptions.includes(taskForm.taskStatus)) return 'Please select a valid status.';

  const missingFields = [];
  if (!taskForm.reservationIdentifier) missingFields.push('reservation');
  if (!taskForm.assignedToAccountId) missingFields.push('assigned staff');
  if (!taskForm.dueDateTimestamp) missingFields.push('due date');

  if (missingFields.length > 0 && !taskForm.emergencyOverride) {
    return `Emergency override is required when saving without ${missingFields.join(', ')}.`;
  }

  if (taskForm.emergencyOverride) {
    if (taskForm.confirmedAdminEmail.trim() === '') return 'Admin email is required for emergency override.';
    if (taskForm.confirmedAdminPassword.trim() === '') return 'Admin password is required for emergency override.';
  }

  return '';
}

function buildTaskPayload() {
  return {
    taskTitle: taskForm.taskTitle.trim(),
    taskDescription: taskForm.taskDescription.trim(),
    taskType: taskForm.taskType,
    reservationIdentifier: taskForm.reservationIdentifier ? Number(taskForm.reservationIdentifier) : null,
    assignedToAccountId: taskForm.assignedToAccountId ? Number(taskForm.assignedToAccountId) : null,
    dueDateTimestamp: taskForm.dueDateTimestamp || null,
    taskStatus: taskForm.taskStatus,
    emergencyOverride: taskForm.emergencyOverride,
    confirmedAdminEmail: normalizeEmailForConfirmation(taskForm.confirmedAdminEmail),
    confirmedAdminPassword: taskForm.confirmedAdminPassword,
  };
}

async function requestJson(path, options = {}) {
  try {
    const response = await fetch(apiUrl(path), {
      method: options.method || 'GET',
      headers: buildHeaders(Boolean(options.body)),
      body: options.body,
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
      return { success: false, error: result.errorMessage || result.message || 'Request failed.' };
    }
    return { success: true, data: result.data || result };
  } catch (error) {
    return { success: false, error: error?.message || 'Request failed.' };
  }
}

function buildHeaders(includeJson = false) {
  const headers = {};
  const localBackendToken = createLocalBackendToken();
  if (includeJson) headers['Content-Type'] = 'application/json';
  if (localBackendToken || authStore.authToken) headers.Authorization = `Bearer ${localBackendToken || authStore.authToken}`;
  return headers;
}

function createLocalBackendToken() {
  try {
    const isLocalDev = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    const accountString = localStorage.getItem(AUTH_STORAGE_KEYS.account);
    if (!accountString && !isLocalDev) return null;
    const account = accountString ? JSON.parse(accountString) : {};
    return btoa(JSON.stringify({
      accountId: account?.accountIdentifier || 1,
      email: account?.emailAddress,
      role: 'ROLE_ADMIN',
      exp: Math.floor(Date.now() / 1000) + 86400,
    }));
  } catch (error) {
    return null;
  }
}

function normalizeReservations(reservations) {
  return reservations.map((reservation) => ({
    value: String(reservation.reservationIdentifier || reservation.reservation_identifier),
    label: [
      reservation.reservationCode || reservation.reservation_code || `#${reservation.reservationIdentifier || reservation.reservation_identifier}`,
      reservation.organizationName || reservation.organization_name,
      reservation.eventDateTime ? formatDateTime(reservation.eventDateTime) : '',
    ].filter(Boolean).join(' - '),
  })).filter((reservation) => reservation.value);
}

function normalizeStaff(accounts) {
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
  if (role.includes('STAFF') || department.includes('staff') || department.includes('maintenance') || department.includes('support')) return 'Employee';
  return account.accountType || account.account_type || '';
}

function formatStaffLabel(task) {
  if (task.assignedStaffName) {
    return [task.assignedStaffName, task.assignedStaffIdNumber].filter(Boolean).join(' - ');
  }
  return task.assignedToAccountId ? `Account #${task.assignedToAccountId}` : 'Unassigned';
}

function formatReservationLabel(reservationIdentifier) {
  return reservationIdentifier ? `#${reservationIdentifier}` : 'No linked reservation';
}

function formatDateTime(value) {
  if (!value) return 'N/A';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
}

function toDateTimeLocal(value) {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  const offsetDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
  return offsetDate.toISOString().slice(0, 16);
}

function normalizeEmailForConfirmation(value) {
  return String(value || '').replace(/[\u200B-\u200D\uFEFF]/g, '').replace(/\s+/g, '').trim().toLowerCase();
}

function resetTaskForm() {
  taskForm.taskTitle = '';
  taskForm.taskDescription = '';
  taskForm.taskType = 'Preparation';
  taskForm.reservationIdentifier = '';
  taskForm.assignedToAccountId = '';
  taskForm.dueDateTimestamp = '';
  taskForm.taskStatus = 'Pending';
  taskForm.emergencyOverride = false;
  taskForm.confirmedAdminEmail = '';
  taskForm.confirmedAdminPassword = '';
}

function resetDeleteForm() {
  deleteForm.confirmedAdminEmail = '';
  deleteForm.confirmedAdminPassword = '';
}
</script>

<style scoped>
.admin-task-assignments-page {
  width: min(1120px, calc(100vw - 2rem));
  margin: 0 auto;
  padding: 6rem 0 2.5rem;
}

.admin-task-assignments-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.admin-task-assignments-header h1,
.admin-task-assignments-modal-header h2 {
  margin: 0;
  color: #111827;
  font-weight: 900;
}

.admin-task-assignments-header h1 {
  font-size: 1.55rem;
}

.admin-task-assignments-header p,
.admin-task-assignments-modal-header p,
.admin-task-assignments-state {
  margin: 0.35rem 0 0;
  color: #52645c;
  font-size: 0.92rem;
}

.admin-task-assignments-toolbar,
.admin-task-assignment-actions,
.admin-task-assignments-modal-actions {
  display: flex;
  gap: 0.7rem;
  justify-content: flex-end;
}

.admin-task-assignments-primary,
.admin-task-assignments-secondary,
.admin-task-assignments-danger {
  min-height: 38px;
  padding: 0 1rem;
  border-radius: 6px;
  font-weight: 850;
  cursor: pointer;
}

.admin-task-assignments-primary {
  color: #ffffff;
  background: #0f6b3f;
  border: 1px solid #0b5c35;
}

.admin-task-assignments-secondary {
  color: #1f2937;
  background: #ffffff;
  border: 1px solid #cbd5d1;
}

.admin-task-assignments-danger {
  color: #ffffff;
  background: #dc2626;
  border: 1px solid #b91c1c;
}

button:disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

.admin-task-assignments-error {
  padding: 0.8rem 1rem;
  color: #9f1239;
  background: #ffe4e6;
  border: 1px solid #fecdd3;
  border-radius: 6px;
  font-weight: 750;
}

.admin-task-assignments-list {
  display: grid;
  gap: 0.9rem;
}

.admin-task-assignment-card {
  padding: 1rem;
  background: #ffffff;
  border: 1px solid #d8e3dd;
  border-left: 5px solid #0f6b3f;
  border-radius: 8px;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
}

.admin-task-assignment-main {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.admin-task-assignment-main h2 {
  margin: 0;
  color: #111827;
  font-size: 1rem;
  font-weight: 900;
}

.admin-task-assignment-main p {
  margin: 0.35rem 0 0;
  color: #4b5563;
  font-size: 0.85rem;
}

.admin-task-assignment-status {
  flex: 0 0 auto;
  padding: 0.25rem 0.55rem;
  color: #075985;
  background: #e0f2fe;
  border-radius: 5px;
  font-size: 0.75rem;
  font-weight: 850;
}

.admin-task-assignment-meta {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.75rem;
  margin: 1rem 0;
}

.admin-task-assignment-meta div,
.admin-task-assignments-delete-summary p {
  padding: 0.65rem;
  background: #f7faf8;
  border: 1px solid #e4ece8;
  border-radius: 6px;
}

.admin-task-assignment-meta dt,
.admin-task-assignments-form span,
.admin-task-assignments-delete-summary strong {
  color: #52645c;
  font-size: 0.72rem;
  font-weight: 850;
}

.admin-task-assignment-meta dd {
  margin: 0.25rem 0 0;
  color: #111827;
  font-size: 0.85rem;
  font-weight: 800;
}

.admin-task-assignments-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 60;
  display: grid;
  place-items: center;
  padding: 1rem;
  background: rgba(15, 23, 42, 0.48);
}

.admin-task-assignments-modal {
  width: min(720px, 100%);
  max-height: calc(100vh - 2rem);
  overflow: auto;
  padding: 1.25rem;
  background: #ffffff;
  border-radius: 8px;
  border: 1px solid #d8e3dd;
}

.admin-task-assignments-modal--narrow {
  width: min(560px, 100%);
}

.admin-task-assignments-modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.admin-task-assignments-modal-header button {
  border: 0;
  background: transparent;
  font-size: 1.25rem;
  cursor: pointer;
}

.admin-task-assignments-form,
.admin-task-assignments-security-grid,
.admin-task-assignments-delete-summary {
  display: grid;
  gap: 0.8rem;
}

.admin-task-assignments-form {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.admin-task-assignments-form label,
.admin-task-assignments-security-grid label {
  display: grid;
  gap: 0.35rem;
}

.admin-task-assignments-form textarea,
.admin-task-assignments-form input,
.admin-task-assignments-form select,
.admin-task-assignments-security-grid input {
  width: 100%;
  min-height: 40px;
  padding: 0.55rem 0.65rem;
  color: #111827;
  background: #ffffff;
  border: 1px solid #cfdad5;
  border-radius: 6px;
}

.admin-task-assignments-form textarea,
.admin-task-assignments-override,
.admin-task-assignments-modal-actions,
.admin-task-assignments-delete-summary {
  grid-column: 1 / -1;
}

.admin-task-assignments-checkbox {
  display: flex !important;
  grid-template-columns: auto 1fr;
  align-items: center;
  gap: 0.5rem !important;
}

.admin-task-assignments-checkbox input {
  width: 18px;
  min-height: 18px;
}

.admin-task-assignments-override {
  padding: 0.8rem;
  background: #f8fafc;
  border: 1px solid #dbe4df;
  border-radius: 6px;
}

.admin-task-assignments-delete-summary {
  margin-bottom: 1rem;
}

.admin-task-assignments-delete-summary p {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  margin: 0;
}

.admin-task-assignments-delete-summary span {
  color: #111827;
  font-weight: 800;
  text-align: right;
}

@media (max-width: 760px) {
  .admin-task-assignments-header,
  .admin-task-assignment-main,
  .admin-task-assignments-toolbar,
  .admin-task-assignment-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .admin-task-assignment-meta,
  .admin-task-assignments-form {
    grid-template-columns: 1fr;
  }
}
</style>
