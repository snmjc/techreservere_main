<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="admin-task-assignments-page">
      <div v-if="taskToastMessage" class="admin-task-assignments-toast">
        {{ taskToastMessage }}
      </div>

      <header class="admin-task-assignments-header">
        <div class="admin-task-assignments-header-copy">
          <p class="admin-task-assignments-kicker">Operations Workspace</p>
          <h1>Task Assignments</h1>
          <p>Monitor reservation workloads, review staff assignments, and keep every deployment on schedule.</p>
        </div>

        <div class="admin-task-assignments-header-actions">
          <button class="admin-task-assignments-secondary" type="button" :disabled="isLoading" @click="loadPageData">
            {{ isLoading ? 'Refreshing...' : 'Refresh' }}
          </button>
          <button class="admin-task-assignments-primary" type="button" @click="openCreateModal">
            + Assign Task
          </button>
        </div>
      </header>

      <section class="admin-task-assignments-summary">
        <article
          v-for="card in summaryCards"
          :key="card.label"
          class="admin-task-summary-card"
          :class="`admin-task-summary-card--${card.tone}`"
        >
          <span class="admin-task-summary-card-icon">{{ card.icon }}</span>
          <div>
            <p>{{ card.label }}</p>
            <strong>{{ card.value }}</strong>
            <small>{{ card.caption }}</small>
          </div>
        </article>
      </section>

      <section class="admin-task-assignments-panel">
        <div class="admin-task-assignments-filters">
          <label class="admin-task-assignments-search">
            <span class="sr-only">Search tasks</span>
            <input
              v-model.trim="searchQuery"
              type="search"
              placeholder="Search reservation, facility, task, or personnel..."
            />
          </label>

          <label>
            <span>Status</span>
            <select v-model="statusFilter">
              <option value="all">All Status</option>
              <option v-for="option in statusOptions" :key="option" :value="option">{{ option }}</option>
              <option value="Overdue">Overdue</option>
            </select>
          </label>

          <label>
            <span>Personnel</span>
            <select v-model="personnelFilter">
              <option value="all">All Personnel</option>
              <option v-for="staff in staffFilterOptions" :key="staff.value" :value="staff.value">{{ staff.label }}</option>
            </select>
          </label>

          <label>
            <span>Sort</span>
            <select v-model="sortFilter">
              <option value="latest">Latest First</option>
              <option value="oldest">Oldest First</option>
              <option value="status">Status</option>
              <option value="reservation">Reservation ID</option>
            </select>
          </label>

          <label>
            <span>From</span>
            <input v-model="dateFilterStart" type="date" />
          </label>

          <label>
            <span>To</span>
            <input v-model="dateFilterEnd" type="date" />
          </label>
        </div>

        <p v-if="loadError" class="admin-task-assignments-error">{{ loadError }}</p>

        <div v-if="isLoading" class="admin-task-assignments-state">
          Loading task assignments...
        </div>

        <div v-else-if="filteredTasks.length === 0" class="admin-task-assignments-state">
          No task assignments match the current filters.
        </div>

        <template v-else>
          <div class="admin-task-assignments-table-wrap">
            <table class="admin-task-assignments-table">
              <thead>
                <tr>
                  <th>Reservation ID</th>
                  <th>Task Details</th>
                  <th>Assigned To</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="task in paginatedTasks" :key="task.taskIdentifier">
                  <td class="admin-task-cell-id">
                    <strong>{{ getReservationCode(task) }}</strong>
                    <small>Task #{{ task.taskIdentifier }}</small>
                  </td>

                  <td class="admin-task-cell-details">
                    <strong>{{ task.reservationLabel || formatReservationLabel(task.reservationIdentifier) }}</strong>
                    <span>{{ task.taskTitle }}</span>
                    <small>{{ task.taskDescription || task.taskType }}</small>
                    <small>{{ formatTaskSchedule(task) }}</small>
                  </td>

                  <td class="admin-task-cell-staff">
                    <strong>{{ formatStaffLabel(task) }}</strong>
                    <small>{{ task.assignedStaffRole || 'Technician' }}</small>
                  </td>

                  <td>
                    <span
                      class="admin-task-status-pill"
                      :class="`admin-task-status-pill--${getStatusTone(task)}`"
                    >
                      {{ getStatusLabel(task) }}
                    </span>
                  </td>

                  <td>
                    <div class="admin-task-actions">
                      <button type="button" class="admin-task-action admin-task-action--view" @click="openViewModal(task)">
                        View
                      </button>
                      <button type="button" class="admin-task-action admin-task-action--edit" @click="openUpdateModal(task)">
                        Update
                      </button>
                      <button
                        type="button"
                        class="admin-task-action admin-task-action--verify"
                        :disabled="!canVerifyTask(task)"
                        @click="openVerifyModal(task)"
                      >
                        Verify
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <footer class="admin-task-assignments-footer">
            <p>Showing {{ pageStart }} to {{ pageEnd }} of {{ filteredTasks.length }} task assignments</p>

            <div class="admin-task-assignments-pagination">
              <button type="button" :disabled="currentPage === 1" @click="currentPage -= 1">
                Prev
              </button>
              <button
                v-for="pageNumber in visiblePageNumbers"
                :key="pageNumber"
                type="button"
                :class="{ 'is-active': pageNumber === currentPage }"
                @click="currentPage = pageNumber"
              >
                {{ pageNumber }}
              </button>
              <button type="button" :disabled="currentPage === totalPages" @click="currentPage += 1">
                Next
              </button>
            </div>
          </footer>

          <div class="admin-task-assignments-legend">
            <span><i class="legend-dot legend-dot--pending" />Pending</span>
            <span><i class="legend-dot legend-dot--progress" />In Progress</span>
            <span><i class="legend-dot legend-dot--done" />Completed</span>
            <span><i class="legend-dot legend-dot--overdue" />Overdue</span>
          </div>
        </template>
      </section>
    </section>

    <div v-if="viewTask" class="admin-task-assignments-modal-overlay" @click.self="closeViewModal">
      <section class="admin-task-assignments-modal admin-task-assignments-modal--narrow">
        <header class="admin-task-assignments-modal-header">
          <div>
            <h2>View Task Assignment</h2>
            <p>Review the task assignment details.</p>
          </div>
          <button type="button" aria-label="Close" @click="closeViewModal">x</button>
        </header>

        <div class="admin-task-assignments-delete-summary">
          <p><strong>Reservation ID</strong><span>{{ getReservationCode(viewTask) }}</span></p>
          <p><strong>Reservation</strong><span>{{ viewTask.reservationLabel || formatReservationLabel(viewTask.reservationIdentifier) }}</span></p>
          <p><strong>Task Name</strong><span>{{ viewTask.taskTitle || 'N/A' }}</span></p>
          <p><strong>Description</strong><span>{{ viewTask.taskDescription || viewTask.taskType || 'N/A' }}</span></p>
          <p><strong>Assigned To</strong><span>{{ formatStaffLabel(viewTask) }}</span></p>
          <p><strong>Status</strong><span>{{ getStatusLabel(viewTask) }}</span></p>
          <p><strong>Schedule</strong><span>{{ formatTaskSchedule(viewTask) }}</span></p>
        </div>

        <footer class="admin-task-assignments-modal-actions">
          <button type="button" class="admin-task-assignments-secondary" @click="closeViewModal">Close</button>
        </footer>
      </section>
    </div>

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
            <p
              v-if="taskSubmissionFeedback.message"
              class="admin-task-assignments-submit-feedback"
              :class="`admin-task-assignments-submit-feedback--${taskSubmissionFeedback.tone}`"
            >
              {{ taskSubmissionFeedback.message }}
            </p>
            <button type="button" class="admin-task-assignments-secondary" :disabled="isSubmitting" @click="closeTaskModal">Cancel</button>
            <button type="submit" class="admin-task-assignments-primary" :disabled="isSubmitting">
              {{ isSubmitting ? (taskModalMode === 'create' ? 'Creating...' : 'Saving...') : (taskModalMode === 'create' ? 'Create' : 'Save Changes') }}
            </button>
          </footer>
        </form>
      </section>
    </div>

    <div v-if="verifyTask" class="admin-task-assignments-modal-overlay" @click.self="closeVerifyModal">
      <section class="admin-task-assignments-modal admin-task-assignments-modal--narrow">
        <header class="admin-task-assignments-modal-header">
          <div>
            <h2>Verify Task Assignment</h2>
            <p>Confirm this task assignment to mark it as completed.</p>
          </div>
          <button type="button" aria-label="Close" @click="closeVerifyModal">x</button>
        </header>

        <div class="admin-task-assignments-delete-summary">
          <p><strong>Task Name</strong><span>{{ verifyTask.taskTitle }}</span></p>
          <p><strong>Assigned Staff</strong><span>{{ formatStaffLabel(verifyTask) }}</span></p>
          <p><strong>Reservation</strong><span>{{ verifyTask.reservationLabel || formatReservationLabel(verifyTask.reservationIdentifier) }}</span></p>
          <p><strong>Status</strong><span>{{ getStatusLabel(verifyTask) }}</span></p>
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
          <button type="button" class="admin-task-assignments-secondary" :disabled="isSubmitting" @click="closeVerifyModal">Cancel</button>
          <button type="button" class="admin-task-assignments-primary" :disabled="!canVerifySubmit || isSubmitting" @click="confirmVerifyTask">
            {{ isSubmitting ? 'Verifying...' : 'Verify' }}
          </button>
        </footer>
      </section>
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { apiUrl } from '@/shared/utils/apiBase.js';
import { buildAuthorizationHeaders } from '@/shared/utils/authToken.js';

const authStore = useAuthenticationStore();
const isLoading = ref(false);
const isSubmitting = ref(false);
const loadError = ref('');
const modalError = ref('');
const taskToastMessage = ref('');
const taskSubmissionFeedback = reactive({
  message: '',
  tone: 'success',
});
const tasks = ref([]);
const reservationOptions = ref([]);
const staffOptions = ref([]);
const showTaskModal = ref(false);
const taskModalMode = ref('create');
const editingTask = ref(null);
const viewTask = ref(null);
const verifyTask = ref(null);

const searchQuery = ref('');
const statusFilter = ref('all');
const personnelFilter = ref('all');
const sortFilter = ref('latest');
const dateFilterStart = ref('');
const dateFilterEnd = ref('');
const currentPage = ref(1);
const pageSize = 6;

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

const canVerifySubmit = computed(() => deleteForm.confirmedAdminEmail.trim() !== '' && deleteForm.confirmedAdminPassword.trim() !== '');

const summaryCards = computed(() => {
  const totalAssignments = tasks.value.length;
  const inProgress = tasks.value.filter((task) => normalizeStatus(task.taskStatus) === 'in_progress').length;
  const completed = tasks.value.filter((task) => normalizeStatus(task.taskStatus) === 'completed').length;
  const overdue = tasks.value.filter((task) => isTaskOverdue(task)).length;

  return [
    {
      label: 'Total Assignments',
      value: totalAssignments,
      caption: 'All recorded tasks',
      icon: '👥',
      tone: 'emerald',
    },
    {
      label: 'In Progress',
      value: inProgress,
      caption: 'Currently ongoing',
      icon: '🕘',
      tone: 'amber',
    },
    {
      label: 'Completed',
      value: completed,
      caption: 'Finished assignments',
      icon: '☑',
      tone: 'sky',
    },
    {
      label: 'Overdue',
      value: overdue,
      caption: 'Require immediate attention',
      icon: '❗',
      tone: 'rose',
    },
  ];
});

const staffFilterOptions = computed(() => tasks.value
  .map((task) => ({
    value: String(task.assignedToAccountId || ''),
    label: formatStaffLabel(task),
  }))
  .filter((staff) => staff.value && staff.label !== 'Unassigned')
  .filter((staff, index, list) => list.findIndex((entry) => entry.value === staff.value) === index)
  .sort((first, second) => first.label.localeCompare(second.label)));

const filteredTasks = computed(() => {
  const filteredList = tasks.value.filter((task) => {
    const query = searchQuery.value.trim().toLowerCase();
    const staffId = String(task.assignedToAccountId || '');
    const searchableText = [
      task.taskTitle,
      task.taskDescription,
      task.taskType,
      task.reservationLabel,
      getReservationCode(task),
      formatStaffLabel(task),
      task.assignedStaffRole,
    ].filter(Boolean).join(' ').toLowerCase();

    if (query && !searchableText.includes(query)) return false;

    if (statusFilter.value !== 'all') {
      if (statusFilter.value === 'Overdue') {
        if (!isTaskOverdue(task)) return false;
      } else if (normalizeStatus(task.taskStatus) !== normalizeStatus(statusFilter.value)) {
        return false;
      }
    }

    if (personnelFilter.value !== 'all' && personnelFilter.value !== staffId) return false;

    const taskDate = getComparableTaskDate(task);
    if (dateFilterStart.value && (!taskDate || taskDate < startOfDay(dateFilterStart.value))) return false;
    if (dateFilterEnd.value && (!taskDate || taskDate > endOfDay(dateFilterEnd.value))) return false;

    return true;
  });

  return filteredList.slice().sort((firstTask, secondTask) => compareTasks(firstTask, secondTask));
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredTasks.value.length / pageSize)));

const paginatedTasks = computed(() => {
  const start = (currentPage.value - 1) * pageSize;
  return filteredTasks.value.slice(start, start + pageSize);
});

const pageStart = computed(() => filteredTasks.value.length === 0 ? 0 : ((currentPage.value - 1) * pageSize) + 1);
const pageEnd = computed(() => Math.min(currentPage.value * pageSize, filteredTasks.value.length));

const visiblePageNumbers = computed(() => {
  const pages = [];
  for (let pageNumber = 1; pageNumber <= totalPages.value; pageNumber += 1) {
    pages.push(pageNumber);
  }
  return pages;
});

watch([searchQuery, statusFilter, personnelFilter, sortFilter, dateFilterStart, dateFilterEnd], () => {
  currentPage.value = 1;
});

watch(totalPages, (pageCount) => {
  if (currentPage.value > pageCount) {
    currentPage.value = pageCount;
  }
});

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

function openViewModal(task) {
  viewTask.value = task;
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

function openVerifyModal(task) {
  if (!canVerifyTask(task)) {
    return;
  }

  verifyTask.value = task;
  resetDeleteForm();
  modalError.value = '';
}

function closeViewModal() {
  viewTask.value = null;
}

function closeTaskModal() {
  showTaskModal.value = false;
  editingTask.value = null;
  resetTaskForm();
  modalError.value = '';
}

function closeVerifyModal() {
  verifyTask.value = null;
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
  resetTaskSubmissionFeedback();
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

  await loadPageData();
  const feedback = buildTaskSubmissionFeedback(result.data?.warning);
  taskSubmissionFeedback.message = feedback.message;
  taskSubmissionFeedback.tone = feedback.tone;
  showTaskToast(feedback.message);
  window.clearTimeout(submitTaskForm.closeTimeoutId);
  submitTaskForm.closeTimeoutId = window.setTimeout(() => {
    if (taskSubmissionFeedback.message === feedback.message) {
      closeTaskModal();
    }
  }, feedback.tone === 'warning' ? 2600 : 1800);
}

async function confirmVerifyTask() {
  if (!verifyTask.value || isSubmitting.value || !canVerifySubmit.value) return;

  isSubmitting.value = true;
  modalError.value = '';

  if (normalizeEmailForConfirmation(deleteForm.confirmedAdminEmail) !== normalizeEmailForConfirmation(currentAdminEmail.value)) {
    isSubmitting.value = false;
    modalError.value = 'Please enter the exact admin email before verifying this task.';
    return;
  }

  const result = await requestJson(`/api/v1/tasks/${verifyTask.value.taskIdentifier}/status`, {
    method: 'PUT',
    body: JSON.stringify({
      taskStatus: 'Completed',
    }),
  });
  isSubmitting.value = false;

  if (!result.success) {
    modalError.value = result.error || 'Unable to verify task assignment.';
    return;
  }

  closeVerifyModal();
  await loadPageData();
  showTaskToast('Task assignment verified successfully.');
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
  return {
    ...(includeJson ? { 'Content-Type': 'application/json' } : {}),
    ...buildAuthorizationHeaders(authStore.authToken),
  };
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
        resolveStaffIdNumber(account),
        account.roleLabel || account.department,
      ].filter(Boolean).join(' - '),
    }))
    .filter((staff) => staff.value);
}

function resolveStaffIdNumber(account) {
  return account.staffEmployeeIdNumber
    || account.staff_employee_id_number
    || account.rawIdNumber
    || account.idNumber
    || account.id_number
    || '';
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
  return reservationIdentifier ? `Reservation #${reservationIdentifier}` : 'No linked reservation';
}

function getReservationCode(task) {
  const label = String(task.reservationLabel || '').trim();
  if (label.includes(' - ')) {
    return label.split(' - ')[0];
  }
  if (label) return label;
  return task.reservationIdentifier ? `RES-${String(task.reservationIdentifier).padStart(4, '0')}` : `TASK-${String(task.taskIdentifier).padStart(4, '0')}`;
}

function formatTaskSchedule(task) {
  const rawValue = task.dueDateTimestamp || task.createdTimestamp;
  if (!rawValue) return 'No schedule set';
  return `${formatScheduleDate(rawValue)} - ${formatScheduleTime(rawValue)}`;
}

function normalizeStatus(status) {
  return String(status || '').trim().toLowerCase().replace(/\s+/g, '_');
}

function isTaskOverdue(task) {
  const dueDate = task.dueDateTimestamp ? new Date(task.dueDateTimestamp) : null;
  if (!dueDate || Number.isNaN(dueDate.getTime())) return false;
  return dueDate.getTime() < Date.now() && !['completed', 'cancelled'].includes(normalizeStatus(task.taskStatus));
}

function getStatusLabel(task) {
  return isTaskOverdue(task) ? 'Overdue' : (task.taskStatus || 'Pending');
}

function getStatusTone(task) {
  if (isTaskOverdue(task)) return 'overdue';

  const status = normalizeStatus(task.taskStatus);
  if (status === 'completed') return 'completed';
  if (status === 'in_progress') return 'progress';
  if (status === 'cancelled') return 'neutral';
  return 'pending';
}

function canVerifyTask(task) {
  if (isTaskOverdue(task)) return false;
  const status = normalizeStatus(task.taskStatus);
  return status === 'pending' || status === 'in_progress';
}

function compareTasks(firstTask, secondTask) {
  if (sortFilter.value === 'reservation') {
    return getReservationCode(firstTask).localeCompare(getReservationCode(secondTask));
  }

  if (sortFilter.value === 'status') {
    return getStatusLabel(firstTask).localeCompare(getStatusLabel(secondTask));
  }

  const firstDate = getComparableTaskDate(firstTask)?.getTime() || 0;
  const secondDate = getComparableTaskDate(secondTask)?.getTime() || 0;
  return sortFilter.value === 'oldest'
    ? firstDate - secondDate
    : secondDate - firstDate;
}

function getComparableTaskDate(task) {
  const rawValue = task.dueDateTimestamp || task.createdTimestamp;
  if (!rawValue) return null;
  const parsedDate = new Date(rawValue);
  if (Number.isNaN(parsedDate.getTime())) return null;
  return parsedDate;
}

function startOfDay(dateValue) {
  return new Date(`${dateValue}T00:00:00`);
}

function endOfDay(dateValue) {
  return new Date(`${dateValue}T23:59:59.999`);
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

function formatScheduleDate(value) {
  if (!value) return 'No schedule set';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('en-US', {
    month: 'long',
    day: '2-digit',
    year: 'numeric',
  }).format(date);
}

function formatScheduleTime(value) {
  if (!value) return 'No time';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return 'No time';
  return new Intl.DateTimeFormat('en-US', {
    hour: 'numeric',
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

function showTaskToast(message) {
  taskToastMessage.value = message;
  window.clearTimeout(showTaskToast.timeoutId);
  showTaskToast.timeoutId = window.setTimeout(() => {
    if (taskToastMessage.value === message) {
      taskToastMessage.value = '';
    }
  }, 3200);
}

showTaskToast.timeoutId = null;
submitTaskForm.closeTimeoutId = null;

function buildTaskSubmissionFeedback(warning) {
  const normalizedWarning = typeof warning === 'string' ? warning.trim() : '';
  if (normalizedWarning !== '') {
    return {
      message: normalizedWarning,
      tone: 'warning',
    };
  }

  return {
    message: 'Task assignment saved and SMS sent to assigned staff.',
    tone: 'success',
  };
}

function resetTaskSubmissionFeedback() {
  taskSubmissionFeedback.message = '';
  taskSubmissionFeedback.tone = 'success';
}

function resetTaskForm() {
  window.clearTimeout(submitTaskForm.closeTimeoutId);
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
  resetTaskSubmissionFeedback();
}

function resetDeleteForm() {
  deleteForm.confirmedAdminEmail = '';
  deleteForm.confirmedAdminPassword = '';
}
</script>

<style scoped>
.admin-task-assignments-page {
  width: 100%;
  max-width: 1320px;
  margin: 0;
  padding: 0.4rem 0 2rem;
  color: #14261f;
}

.admin-task-assignments-toast {
  margin-bottom: 0.9rem;
  padding: 0.82rem 0.95rem;
  color: #14532d;
  background: #dcfce7;
  border: 1px solid #86efac;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 800;
}

.admin-task-assignments-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.15rem;
  padding: 1.4rem 1.5rem;
  background:
    radial-gradient(circle at top right, rgba(21, 153, 87, 0.1), transparent 30%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.95), rgba(244, 248, 246, 0.98));
  border: 1px solid #dfe7e1;
  border-radius: 24px;
  box-shadow: 0 20px 38px rgba(15, 23, 42, 0.08);
}

.admin-task-assignments-header-copy {
  max-width: 760px;
}

.admin-task-assignments-kicker {
  margin: 0 0 0.35rem;
  color: #15803d;
  font-size: 0.75rem;
  font-weight: 900;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.admin-task-assignments-header h1,
.admin-task-assignments-modal-header h2 {
  margin: 0;
  color: #143222;
  font-size: clamp(1.85rem, 2.2vw, 2.45rem);
  font-weight: 900;
  line-height: 1;
}

.admin-task-assignments-header-copy > p:last-child,
.admin-task-assignments-modal-header p,
.admin-task-assignments-state {
  margin: 0.5rem 0 0;
  color: #587062;
  font-size: 0.95rem;
}

.admin-task-assignments-header-actions,
.admin-task-assignments-modal-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.admin-task-assignments-modal-actions {
  align-items: center;
}

.admin-task-assignments-primary,
.admin-task-assignments-secondary,
.admin-task-assignments-danger,
.admin-task-action,
.admin-task-assignments-pagination button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 42px;
  padding: 0 1rem;
  border-radius: 10px;
  font-size: 0.86rem;
  font-weight: 850;
  cursor: pointer;
  transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, border-color 0.18s ease;
}

.admin-task-assignments-primary {
  color: #ffffff;
  background: linear-gradient(135deg, #159957, #0f8f46);
  border: 1px solid #0f8f46;
  box-shadow: 0 14px 26px rgba(21, 153, 87, 0.22);
}

.admin-task-assignments-secondary {
  color: #1f3a2c;
  background: #ffffff;
  border: 1px solid #d6e3da;
}

.admin-task-assignments-danger {
  color: #ffffff;
  background: #dc2626;
  border: 1px solid #b91c1c;
}

button:disabled {
  cursor: not-allowed;
  opacity: 0.65;
  transform: none;
}

.admin-task-assignments-summary {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.admin-task-summary-card {
  display: flex;
  align-items: flex-start;
  gap: 0.9rem;
  padding: 1rem 1.1rem;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(245, 248, 246, 0.95));
  border: 1px solid #dfe9e2;
  border-radius: 18px;
  box-shadow: 0 18px 32px rgba(15, 23, 42, 0.07);
}

.admin-task-summary-card-icon {
  display: grid;
  place-items: center;
  width: 46px;
  height: 46px;
  border-radius: 14px;
  font-size: 1.25rem;
}

.admin-task-summary-card p,
.admin-task-summary-card strong,
.admin-task-summary-card small {
  display: block;
}

.admin-task-summary-card p {
  margin: 0 0 0.22rem;
  color: #567061;
  font-size: 0.78rem;
  font-weight: 800;
}

.admin-task-summary-card strong {
  color: #10281d;
  font-size: 1.8rem;
  line-height: 1;
}

.admin-task-summary-card small {
  margin-top: 0.3rem;
  color: #6b7f74;
  font-size: 0.75rem;
  font-weight: 700;
}

.admin-task-summary-card--emerald .admin-task-summary-card-icon {
  background: #dcfce7;
}

.admin-task-summary-card--amber .admin-task-summary-card-icon {
  background: #fef3c7;
}

.admin-task-summary-card--sky .admin-task-summary-card-icon {
  background: #dbeafe;
}

.admin-task-summary-card--rose .admin-task-summary-card-icon {
  background: #fee2e2;
}

.admin-task-assignments-panel {
  width: 100%;
  padding: 1.1rem;
  background:
    radial-gradient(circle at top right, rgba(21, 153, 87, 0.08), transparent 28%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 248, 0.98));
  border: 1px solid #dfe7e1;
  border-radius: 22px;
  box-shadow: 0 24px 40px rgba(15, 23, 42, 0.08);
}

.admin-task-assignments-filters {
  display: grid;
  grid-template-columns: minmax(260px, 1.5fr) repeat(5, minmax(130px, 0.7fr));
  gap: 0.8rem;
  margin-bottom: 1rem;
  align-items: end;
}

.admin-task-assignments-filters label,
.admin-task-assignments-form label,
.admin-task-assignments-security-grid label {
  display: grid;
  gap: 0.38rem;
}

.admin-task-assignments-filters span,
.admin-task-assignments-form span,
.admin-task-assignments-delete-summary strong {
  color: #566c60;
  font-size: 0.75rem;
  font-weight: 850;
}

.admin-task-assignments-filters input,
.admin-task-assignments-filters select,
.admin-task-assignments-form input,
.admin-task-assignments-form textarea,
.admin-task-assignments-form select,
.admin-task-assignments-security-grid input {
  width: 100%;
  min-height: 42px;
  padding: 0.68rem 0.78rem;
  color: #12271d;
  background: #ffffff;
  border: 1px solid #d6e2da;
  border-radius: 12px;
}

.admin-task-assignments-form input,
.admin-task-assignments-form select,
.admin-task-assignments-security-grid input {
  min-height: 40px;
  padding: 0.58rem 0.78rem;
}

.admin-task-assignments-search input {
  padding-left: 0.92rem;
}

.admin-task-assignments-error {
  margin: 0 0 1rem;
  padding: 0.82rem 0.95rem;
  color: #9f1239;
  background: #ffe4e6;
  border: 1px solid #fecdd3;
  border-radius: 12px;
  font-size: 0.86rem;
  font-weight: 800;
}

.admin-task-assignments-submit-feedback {
  flex: 1 1 100%;
  margin: 0;
  padding: 0.78rem 0.92rem;
  border-radius: 12px;
  font-size: 0.84rem;
  font-weight: 800;
}

.admin-task-assignments-submit-feedback--success {
  color: #14532d;
  background: #dcfce7;
  border: 1px solid #86efac;
}

.admin-task-assignments-submit-feedback--warning {
  color: #92400e;
  background: #fffbeb;
  border: 1px solid #fcd34d;
}

.admin-task-assignments-state {
  padding: 1rem;
  text-align: center;
  background: #f7faf8;
  border: 1px dashed #cfddd4;
  border-radius: 16px;
}

.admin-task-assignments-table-wrap {
  width: 100%;
  overflow-x: auto;
}

.admin-task-assignments-table {
  width: 100%;
  min-width: 860px;
  border-collapse: separate;
  border-spacing: 0;
}

.admin-task-assignments-table thead th {
  padding: 0.95rem 0.8rem;
  color: #294638;
  background: #f2f7f4;
  border-bottom: 1px solid #dbe6df;
  font-size: 0.74rem;
  font-weight: 900;
  letter-spacing: 0.04em;
  text-align: left;
  text-transform: uppercase;
}

.admin-task-assignments-table tbody td {
  padding: 0.95rem 0.8rem;
  color: #12271d;
  background: rgba(255, 255, 255, 0.94);
  border-bottom: 1px solid #e6ede8;
  vertical-align: top;
}

.admin-task-assignments-table tbody tr:hover td {
  background: #fcfefd;
}

.admin-task-cell-id strong,
.admin-task-cell-details strong,
.admin-task-cell-staff strong,
.admin-task-cell-schedule strong,
.admin-task-progress-copy strong {
  display: block;
  color: #123224;
  font-size: 0.86rem;
  font-weight: 850;
}

.admin-task-cell-id small,
.admin-task-cell-details span,
.admin-task-cell-details small,
.admin-task-cell-staff small,
.admin-task-cell-schedule small {
  display: block;
  margin-top: 0.24rem;
  color: #688072;
  font-size: 0.75rem;
}

.admin-task-cell-details span {
  color: #25513d;
  font-weight: 800;
}

.admin-task-status-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 102px;
  min-height: 32px;
  padding: 0 0.7rem;
  border-radius: 999px;
  font-size: 0.76rem;
  font-weight: 900;
}

.admin-task-status-pill--pending {
  color: #92400e;
  background: #fef3c7;
}

.admin-task-status-pill--progress {
  color: #1d4ed8;
  background: #dbeafe;
}

.admin-task-status-pill--completed {
  color: #047857;
  background: #d1fae5;
}

.admin-task-status-pill--overdue {
  color: #b91c1c;
  background: #fee2e2;
}

.admin-task-status-pill--neutral {
  color: #4b5563;
  background: #e5e7eb;
}

.admin-task-actions {
  display: flex;
  gap: 0.55rem;
}

.admin-task-action {
  min-height: 34px;
  padding: 0 0.8rem;
  border: 1px solid transparent;
  border-radius: 999px;
  font-size: 0.74rem;
}

.admin-task-action--edit {
  color: #0f766e;
  background: #ccfbf1;
  border-color: #99f6e4;
}

.admin-task-action--view {
  color: #1d4ed8;
  background: #dbeafe;
  border-color: #93c5fd;
}

.admin-task-action--verify {
  color: #166534;
  background: #dcfce7;
  border-color: #86efac;
}

.admin-task-assignments-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding-top: 0.9rem;
}

.admin-task-assignments-footer p {
  margin: 0;
  color: #607668;
  font-size: 0.8rem;
  font-weight: 700;
}

.admin-task-assignments-pagination {
  display: flex;
  gap: 0.5rem;
}

.admin-task-assignments-pagination button {
  min-width: 40px;
  min-height: 36px;
  padding: 0 0.75rem;
  color: #305040;
  background: #ffffff;
  border: 1px solid #d3dfd7;
  border-radius: 10px;
}

.admin-task-assignments-pagination .is-active {
  color: #ffffff;
  background: #15803d;
  border-color: #15803d;
}

.admin-task-assignments-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-top: 0.95rem;
  color: #607668;
  font-size: 0.76rem;
  font-weight: 800;
}

.admin-task-assignments-legend span {
  display: inline-flex;
  align-items: center;
  gap: 0.38rem;
}

.legend-dot {
  display: inline-block;
  width: 9px;
  height: 9px;
  border-radius: 999px;
}

.legend-dot--pending {
  background: #f59e0b;
}

.legend-dot--progress {
  background: #3b82f6;
}

.legend-dot--done {
  background: #16a34a;
}

.legend-dot--overdue {
  background: #ef4444;
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
  border: 1px solid #d8e3dd;
  border-radius: 18px;
  box-shadow: 0 26px 45px rgba(15, 23, 42, 0.18);
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

.admin-task-assignments-form textarea,
.admin-task-assignments-override,
.admin-task-assignments-modal-actions,
.admin-task-assignments-delete-summary {
  grid-column: 1 / -1;
}

.admin-task-assignments-form textarea {
  min-height: 88px;
  max-height: 120px;
  padding: 0.72rem 0.78rem;
  line-height: 1.45;
  resize: vertical;
}

.admin-task-assignments-checkbox {
  display: flex !important;
  align-items: center;
  gap: 0.5rem !important;
}

.admin-task-assignments-checkbox input {
  width: 18px;
  min-height: 18px;
}

.admin-task-assignments-override {
  padding: 0.85rem;
  background: #f7faf8;
  border: 1px solid #dbe4df;
  border-radius: 12px;
}

.admin-task-assignments-delete-summary {
  margin-bottom: 1rem;
}

.admin-task-assignments-delete-summary p {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  margin: 0;
  padding: 0.7rem 0.8rem;
  background: #f8fbf9;
  border: 1px solid #e2ebe5;
  border-radius: 10px;
}

.admin-task-assignments-delete-summary span {
  color: #13271d;
  font-weight: 800;
  text-align: right;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

@media (hover: hover) {
  .admin-task-assignments-primary:hover,
  .admin-task-assignments-secondary:hover,
  .admin-task-assignments-danger:hover,
  .admin-task-action:hover,
  .admin-task-assignments-pagination button:hover {
    transform: translateY(-1px);
  }
}

@media (max-width: 1100px) {
  .admin-task-assignments-summary {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .admin-task-assignments-filters {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 760px) {
  .admin-task-assignments-page {
    max-width: 100%;
    padding-top: 0.2rem;
  }

  .admin-task-assignments-header,
  .admin-task-assignments-header-actions,
  .admin-task-assignments-footer,
  .admin-task-assignments-modal-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .admin-task-assignments-summary,
  .admin-task-assignments-filters,
  .admin-task-assignments-form {
    grid-template-columns: 1fr;
  }

  .admin-task-assignments-panel {
    padding: 0.9rem;
  }

  .admin-task-actions {
    flex-direction: column;
  }

  .admin-task-action,
  .admin-task-assignments-header-actions > button,
  .admin-task-assignments-modal-actions > button {
    width: 100%;
  }

  .admin-task-assignments-table {
    min-width: 720px;
  }
}

@media (max-width: 520px) {
  .admin-task-assignments-header,
  .admin-task-summary-card,
  .admin-task-assignments-panel,
  .admin-task-assignments-modal {
    border-radius: 16px;
  }

  .admin-task-assignments-header,
  .admin-task-assignments-panel,
  .admin-task-assignments-modal {
    padding: 0.9rem;
  }

  .admin-task-assignments-header h1,
  .admin-task-assignments-modal-header h2 {
    font-size: 1.55rem;
  }

  .admin-task-assignments-table {
    min-width: 640px;
  }

  .admin-task-assignments-pagination {
    width: 100%;
    justify-content: space-between;
    flex-wrap: wrap;
  }
}
</style>
