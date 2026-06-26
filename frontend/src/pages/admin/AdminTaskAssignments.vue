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
@import './css/AdminTaskAssignments.css';
</style>

