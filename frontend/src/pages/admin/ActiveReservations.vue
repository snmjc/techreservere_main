<!-- ===== AI GENERATED: AdminActiveReservationsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="admin-ops-page active-reservations-page">
      <header class="admin-ops-header">
        <div class="admin-ops-header-copy">
          <p class="admin-ops-kicker">Deployment Tracking</p>
          <h1>Active Reservations</h1>
          <p>Track reservations happening today or currently ongoing, open deployment details, confirm returns, and flag issues when needed.</p>
        </div>
      </header>

      <div class="admin-ops-filter-card">
        <div class="admin-ops-toolbar active-reservations-toolbar">
          <label class="admin-ops-field">
            <span>Search</span>
            <input
              id="activeResSearchInput"
              v-model="searchQueryText"
              type="text"
              class="active-reservations-search-input"
              placeholder="Requester name or request ID"
            />
          </label>
          <label class="admin-ops-field">
            <span>Showing</span>
            <select
              id="activeResShowingSelect"
              v-model="showingFilterValue"
              class="active-reservations-showing-select"
            >
              <option value="all">All</option>
              <option value="venue">Venue</option>
              <option value="equipment">Equipment</option>
              <option value="both">Both</option>
            </select>
          </label>
          <button
            class="admin-ops-sort-button active-reservations-sort-button"
            :class="{ 'admin-ops-sort-button--ascending': sortDirection === 'asc' }"
            :aria-label="`Sort ${sortDirection === 'asc' ? 'descending' : 'ascending'}`"
            :title="sortDirection === 'asc' ? 'Soonest schedule first' : 'Latest schedule first'"
            type="button"
            @click="toggleSortDirection"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"/>
              <polyline points="19 12 12 19 5 12"/>
            </svg>
            <span>{{ sortDirection === 'asc' ? 'Soonest First' : 'Latest First' }}</span>
          </button>
        </div>
      </div>

      <div class="admin-ops-table-card">
        <ReservationActiveTableComponent
          :reservation-list="paginatedActiveReservations"
          :search-query-text="searchQueryText"
          @view-deployment-details="handleViewDeploymentDetails"
          @return-confirmation="handleReturnConfirmation"
          @report-reservation="handleReportReservation"
        />
      </div>

      <div v-if="activeReservationsTotalPages > 1" class="active-reservations-pagination">
        <button type="button" :disabled="activeReservationsCurrentPage === 1" @click="activeReservationsCurrentPage -= 1">Previous</button>
        <span>Page {{ activeReservationsCurrentPage }} of {{ activeReservationsTotalPages }}</span>
        <button type="button" :disabled="activeReservationsCurrentPage === activeReservationsTotalPages" @click="activeReservationsCurrentPage += 1">Next</button>
      </div>

      <div class="admin-ops-page-footer active-reservations-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </div>
    </section>

    <!-- Process Deployment Modal -->
    <ReservationDeploymentModalComponent
      :reservation-record="selectedReservationRecord"
      @close-deployment-modal="handleCloseDeploymentModal"
      @confirm-return-record="handleConfirmReturn"
      @report-reservation-record="handleReportReservation"
    />

    <div v-if="confirmReservationRecord" class="active-reservation-action-overlay" @click.self="closeConfirmModal">
      <section class="active-reservation-action-card">
        <header class="active-reservation-action-header">
          <div>
            <h2>Workflow Confirmation</h2>
            <p>Review the workflow details below, validate the reservation completion, or reject the workflow when needed.</p>
          </div>
          <button class="active-reservation-action-close" type="button" aria-label="Close" @click="closeConfirmModal">&times;</button>
        </header>

        <div class="active-reservation-action-body">
          <section class="active-reservation-summary-card">
            <div class="active-reservation-summary-top">
              <div class="active-reservation-summary-requester">
                <span class="active-reservation-summary-avatar">{{ getRequesterInitials(confirmReservationRecord) }}</span>
                <div>
                  <strong>{{ confirmReservationRecord.requesterFullName || 'N/A' }}</strong>
                  <small>ID: {{ confirmReservationRecord.requesterId || confirmReservationRecord.requestIdentifier || 'N/A' }}</small>
                  <span class="active-reservation-summary-role">{{ confirmReservationRecord.requesterRole || 'Borrower' }}</span>
                </div>
              </div>

              <div class="active-reservation-summary-metric">
                <span>Request ID</span>
                <strong>{{ confirmReservationRecord.requestDisplayIdentifier || confirmReservationRecord.requestIdentifier }}</strong>
              </div>

              <div class="active-reservation-summary-metric">
                <span>Request Type</span>
                <strong>{{ confirmReservationRecord.requestType || 'N/A' }}</strong>
              </div>

              <div class="active-reservation-summary-metric">
                <span>Status</span>
                <strong>{{ confirmReservationRecord.deploymentStatus || confirmReservationRecord.requestStatus || 'Active' }}</strong>
              </div>
            </div>

            <div class="active-reservation-summary-bottom">
              <div class="active-reservation-summary-item">
                <span>Venue</span>
                <strong>{{ confirmReservationRecord.facilityName || 'N/A' }}</strong>
              </div>

              <div class="active-reservation-summary-item">
                <span>Start Date &amp; Time</span>
                <strong class="active-reservation-summary-stack">
                  <em>{{ formatWorkflowDateTime(confirmReservationRecord.requestScheduleStart || confirmReservationRecord.activityTime) }}</em>
                </strong>
              </div>

              <div class="active-reservation-summary-item">
                <span>End Date &amp; Time</span>
                <strong class="active-reservation-summary-stack">
                  <em>{{ formatWorkflowDateTime(confirmReservationRecord.requestScheduleEnd || confirmReservationRecord.activityEndTime) }}</em>
                </strong>
              </div>

              <div class="active-reservation-summary-item">
                <span>Number of Participants</span>
                <strong>{{ confirmReservationRecord.participantCount || 0 }}</strong>
              </div>
            </div>
          </section>

          <section class="active-reservation-workflow-section">
            <p class="active-reservation-workflow-label">Equipment</p>
            <div v-if="getEquipmentResources(confirmReservationRecord).length" class="active-reservation-resource-list">
              <span
                v-for="resource in getEquipmentResources(confirmReservationRecord)"
                :key="`${resource.resourceName}-${resource.resourceCount}`"
                class="active-reservation-resource-chip"
              >
                {{ resource.resourceName }} x{{ resource.resourceCount }}
              </span>
            </div>
            <p v-else class="active-reservation-workflow-empty">No equipment reserved for this workflow.</p>
          </section>

          <section class="active-reservation-workflow-section">
            <p class="active-reservation-workflow-label">Task Workflow</p>

            <div v-if="isWorkflowConfirmationLoading" class="active-reservation-workflow-empty">
              Loading workflow confirmation details...
            </div>

            <div v-else-if="workflowConfirmationTasks.length" class="active-reservation-workflow-task-list">
              <article
                v-for="workflowTask in workflowConfirmationTasks"
                :key="workflowTask.taskIdentifier || workflowTask.localKey"
                class="active-reservation-workflow-task-card"
              >
                <div class="active-reservation-workflow-task-head">
                  <strong>{{ workflowTask.taskTitle || 'Task Assignment' }}</strong>
                  <span>{{ workflowTask.taskStatus || 'Pending' }}</span>
                </div>

                <div class="active-reservation-workflow-task-grid">
                  <div>
                    <small>Staff Assigned</small>
                    <strong>{{ workflowTask.assignedStaffName || 'Unassigned' }}</strong>
                  </div>
                  <div>
                    <small>Ingress</small>
                    <strong>{{ formatWorkflowDateTime(workflowTask.preparationStartTimestamp) }}</strong>
                  </div>
                  <div>
                    <small>Egress</small>
                    <strong>{{ formatWorkflowDateTime(workflowTask.preparationEndTimestamp || workflowTask.dueDateTimestamp) }}</strong>
                  </div>
                </div>

                <div class="active-reservation-workflow-task-notes">
                  <small>Notes</small>
                  <p>{{ workflowTask.taskDescription || 'No workflow notes added.' }}</p>
                </div>
              </article>
            </div>

            <p v-else class="active-reservation-workflow-empty">
              No workflow task assignments are linked to this reservation yet.
            </p>
          </section>

          <label class="active-reservation-action-field active-reservation-action-field--full">
            <span>Remarks</span>
            <textarea
              v-model.trim="confirmForm.remarks"
              maxlength="500"
              rows="4"
              placeholder="Add validation remarks or explain why the workflow is being rejected..."
            />
            <small>{{ confirmForm.remarks.length }} / 500</small>
          </label>

          <div class="active-reservation-action-security">
            <h3>Admin Confirmation</h3>
            <p>Please verify your administrator account before confirming or rejecting this workflow.</p>

            <div class="active-reservation-action-grid">
              <label class="active-reservation-action-field">
                <span>Admin Email</span>
                <input v-model.trim="confirmForm.adminEmail" type="email" :placeholder="currentAdminEmail || 'Enter your admin email'" />
              </label>

            </div>
          </div>

          <p v-if="workflowConfirmationError" class="active-reservation-action-feedback active-reservation-action-feedback--error">
            {{ workflowConfirmationError }}
          </p>
        </div>

        <footer class="active-reservation-action-footer">
          <button class="active-reservation-action-button active-reservation-action-button--ghost" type="button" :disabled="isWorkflowConfirmationSubmitting" @click="closeConfirmModal">Cancel</button>
          <button class="active-reservation-action-button active-reservation-action-button--report" type="button" :disabled="isWorkflowConfirmationSubmitting" @click="rejectWorkflowConfirmation">
            {{ isWorkflowConfirmationSubmitting ? 'Processing...' : 'Reject Workflow' }}
          </button>
          <button class="active-reservation-action-button active-reservation-action-button--confirm" type="button" :disabled="isWorkflowConfirmationSubmitting" @click="submitConfirmReturn">
            {{ isWorkflowConfirmationSubmitting ? 'Processing...' : 'Confirm Completed' }}
          </button>
        </footer>
      </section>
    </div>

    <div v-if="reportReservationRecord" class="active-reservation-action-overlay" @click.self="closeReportModal">
      <section class="active-reservation-action-card">
        <header class="active-reservation-action-header">
          <div>
            <h2>Overdue</h2>
            <p>Provide the overdue details and administrator verification before flagging this active reservation.</p>
          </div>
          <button class="active-reservation-action-close" type="button" aria-label="Close" @click="closeReportModal">&times;</button>
        </header>

        <div class="active-reservation-action-body">
          <section class="active-reservation-summary-card">
            <div class="active-reservation-summary-top">
              <div class="active-reservation-summary-requester">
                <span class="active-reservation-summary-avatar">{{ getRequesterInitials(reportReservationRecord) }}</span>
                <div>
                  <strong>{{ reportReservationRecord.requesterFullName || 'N/A' }}</strong>
                  <small>ID: {{ reportReservationRecord.requesterId || reportReservationRecord.requestIdentifier || 'N/A' }}</small>
                  <span class="active-reservation-summary-role">{{ reportReservationRecord.requesterRole || 'Borrower' }}</span>
                </div>
              </div>

              <div class="active-reservation-summary-metric">
                <span>Request ID</span>
                <strong>{{ reportReservationRecord.requestDisplayIdentifier || reportReservationRecord.requestIdentifier }}</strong>
              </div>

              <div class="active-reservation-summary-metric">
                <span>Request Type</span>
                <strong>{{ reportReservationRecord.requestType || 'N/A' }}</strong>
              </div>

              <div class="active-reservation-summary-metric">
                <span>Status</span>
                <strong>{{ reportReservationRecord.deploymentStatus || reportReservationRecord.requestStatus || 'Active' }}</strong>
              </div>
            </div>

            <div class="active-reservation-summary-bottom">
              <div class="active-reservation-summary-item">
                <span>Facility</span>
                <strong>{{ reportReservationRecord.facilityName || 'N/A' }}</strong>
              </div>

              <div class="active-reservation-summary-item">
                <span>Schedule</span>
                <strong class="active-reservation-summary-stack">
                  <em>{{ reportReservationRecord.activityDate || 'N/A' }}</em>
                  <em>{{ reportReservationRecord.activityEndTime || reportReservationRecord.requestSchedule || 'N/A' }}</em>
                </strong>
              </div>

              <div class="active-reservation-summary-item">
                <span>Purpose</span>
                <strong>{{ reportReservationRecord.requestPurpose || 'N/A' }}</strong>
              </div>

              <div class="active-reservation-summary-item">
                <span>Quantity</span>
                <strong>{{ reportReservationRecord.requestQuantity || 0 }}</strong>
              </div>
            </div>
          </section>

          <label class="active-reservation-action-field active-reservation-action-field--full">
            <span>Remarks</span>
            <textarea
              v-model.trim="reportForm.remarks"
              maxlength="500"
              rows="4"
              placeholder="Describe why this reservation is overdue..."
            />
            <small>{{ reportForm.remarks.length }} / 500</small>
          </label>

          <div class="active-reservation-action-security">
            <h3>Admin Confirmation</h3>
            <p>Please verify your administrator account before marking this reservation as overdue.</p>

            <div class="active-reservation-action-grid">
              <label class="active-reservation-action-field">
                <span>Admin Email</span>
                <input v-model.trim="reportForm.adminEmail" type="email" :placeholder="currentAdminEmail || 'Enter your admin email'" />
              </label>

            </div>
          </div>
        </div>

        <footer class="active-reservation-action-footer">
          <button class="active-reservation-action-button active-reservation-action-button--ghost" type="button" @click="closeReportModal">Cancel</button>
          <button class="active-reservation-action-button active-reservation-action-button--report" type="button" @click="submitReportReservation">
            Overdue
          </button>
        </footer>
      </section>
    </div>
    <DataRequestStatusFloater :items="activeReservationsStatusItems" />
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, onMounted, computed, reactive, watch } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import DataRequestStatusFloater from '@/shared/components/DataRequestStatusFloater.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/ActiveReservations.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import ReservationActiveTableComponent from '@/modules/reservation/components/ReservationActiveTableComponent.vue';
import ReservationDeploymentModalComponent from '@/modules/reservation/components/ReservationDeploymentModalComponent.vue';
import '@/modules/reservation/components/reservationDeploymentModal.css';
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { taskWorkflowApi } from '@/modules/task/services/taskWorkflowApi.js';
import { sortReservationRecords } from '@/modules/request/services/requestReservationMapper.js';

const authStore = useAuthenticationStore();
const requestStore = useRequestStore();
const searchQueryText = ref('');
const showingFilterValue = ref('all');
const sortDirection = ref('asc');
const activeReservationsCurrentPage = ref(1);
const activeReservationsPageSize = 8;
const selectedReservationRecord = ref(null);
const confirmReservationRecord = ref(null);
const reportReservationRecord = ref(null);
const confirmForm = reactive({
  remarks: '',
  adminEmail: '',
});
const reportForm = reactive({
  remarks: '',
  adminEmail: '',
});
const workflowConfirmationTasks = ref([]);
const isWorkflowConfirmationLoading = ref(false);
const isWorkflowConfirmationSubmitting = ref(false);
const workflowConfirmationError = ref('');

const activeReservationsList = computed(() => requestStore.activeReservationsList || []);
const activeReservationsStatusItems = computed(() => [
  {
    key: 'active-reservations',
    label: 'Active Reservations',
    state: resolveReservationListState(activeReservationsList.value),
  },
]);
const filteredActiveReservations = computed(() => {
  const queryLower = searchQueryText.value.toLowerCase().trim();

  const filteredRecords = activeReservationsList.value.filter((reservationRecord) => {
    const requestType = String(reservationRecord?.requestType || '').toLowerCase();
    const matchesShowing = showingFilterValue.value === 'all'
      || requestType === showingFilterValue.value;
    const matchesQuery = queryLower === ''
      || String(reservationRecord?.requesterFullName || '').toLowerCase().includes(queryLower)
      || String(reservationRecord?.requestIdentifier || '').toLowerCase().includes(queryLower)
      || String(reservationRecord?.requestDisplayIdentifier || '').toLowerCase().includes(queryLower);

    return matchesShowing && matchesQuery;
  });

  return sortReservationRecords(filteredRecords, 'active', sortDirection.value);
});
const activeReservationsTotalPages = computed(() => Math.max(1, Math.ceil(filteredActiveReservations.value.length / activeReservationsPageSize)));
const paginatedActiveReservations = computed(() => {
  const startIndex = (activeReservationsCurrentPage.value - 1) * activeReservationsPageSize;
  return filteredActiveReservations.value.slice(startIndex, startIndex + activeReservationsPageSize);
});
const currentAdminEmail = computed(() => {
  const account = authStore.accountData || authStore.clerkAccountData || {};
  return String(account.emailAddress || account.email || '').trim().toLowerCase();
});

onMounted(async () => {
  try {
    await requestStore.fetchReservations();
    const list = requestStore.activeReservationsList || [];
    console.log('Admin Active Reservations - Count:', list.length);
  } catch (error) {
    console.error('Error fetching active reservations:', error);
  }
});

watch([searchQueryText, showingFilterValue], () => {
  activeReservationsCurrentPage.value = 1;
});

watch(activeReservationsTotalPages, (pageCount) => {
  if (activeReservationsCurrentPage.value > pageCount) {
    activeReservationsCurrentPage.value = pageCount;
  }
});

/**
 * @function handleViewDeploymentDetails
 * @description Opens the deployment modal with selected record.
 * @param {Object} reservationRecord - The reservation record to view
 * @returns {void}
 */
function handleViewDeploymentDetails(reservationRecord) {
  selectedReservationRecord.value = reservationRecord;
}

/**
 * @function handleCloseDeploymentModal
 * @description Closes the deployment modal.
 * @returns {void}
 */
function handleCloseDeploymentModal() {
  selectedReservationRecord.value = null;
}

/**
 * @function handleReturnConfirmation
 * @description Completes an active reservation → moves to Past Records as "Completed".
 * @param {Object} reservationRecord - The reservation record
 * @returns {void}
 */
async function handleReturnConfirmation(reservationRecord) {
  await openWorkflowConfirmationModal(reservationRecord);
}

/**
 * @function handleConfirmReturn
 * @description Completes an active reservation from the modal → moves to Past Records.
 * @param {Object} reservationRecord - The reservation record returned
 * @returns {void}
 */
async function handleConfirmReturn(reservationRecord) {
  await openWorkflowConfirmationModal(reservationRecord);
}

/**
 * @function handleReportReservation
 * @description Marks an active reservation as overdue and archives it in Past Records as "Cancelled".
 * @param {Object} reservationRecord - The reservation record to cancel
 * @returns {void}
 */
function handleReportReservation(reservationRecord) {
  reportReservationRecord.value = reservationRecord;
}

function toggleSortDirection() {
  sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
}

async function submitConfirmReturn() {
  if (!confirmReservationRecord.value) {
    return;
  }

  const emailError = validateAdminEmailConfirmation(confirmForm.adminEmail, 'confirm');
  if (emailError) {
    workflowConfirmationError.value = emailError;
    return;
  }

  isWorkflowConfirmationSubmitting.value = true;
  workflowConfirmationError.value = '';

  try {
    await requestStore.completeActiveReservation(
      confirmReservationRecord.value,
      confirmForm.remarks.trim(),
      {
        confirmedAdminEmail: normalizeEmailForConfirmation(confirmForm.adminEmail),
      },
    );
    closeConfirmModal();
    selectedReservationRecord.value = null;
  } catch (error) {
    workflowConfirmationError.value = error?.message || 'Unable to complete this reservation.';
  } finally {
    isWorkflowConfirmationSubmitting.value = false;
  }
}

async function rejectWorkflowConfirmation() {
  if (!confirmReservationRecord.value) {
    return;
  }

  if (confirmForm.remarks.trim() === '') {
    workflowConfirmationError.value = 'Please add remarks before rejecting this workflow.';
    return;
  }

  const emailError = validateAdminEmailConfirmation(confirmForm.adminEmail, 'reject');
  if (emailError) {
    workflowConfirmationError.value = emailError;
    return;
  }

  isWorkflowConfirmationSubmitting.value = true;
  workflowConfirmationError.value = '';

  try {
    await requestStore.cancelActiveReservation(
      confirmReservationRecord.value,
      confirmForm.remarks.trim(),
      {
        confirmedAdminEmail: normalizeEmailForConfirmation(confirmForm.adminEmail),
      },
    );
    closeConfirmModal();
    selectedReservationRecord.value = null;
  } catch (error) {
    workflowConfirmationError.value = error?.message || 'Unable to reject this workflow.';
  } finally {
    isWorkflowConfirmationSubmitting.value = false;
  }
}

async function submitReportReservation() {
  if (!reportReservationRecord.value) {
    return;
  }

  if (reportForm.remarks.trim() === '') {
    window.alert('Please add remarks before marking this reservation as overdue.');
    return;
  }

  const emailError = validateAdminEmailConfirmation(reportForm.adminEmail, 'report');
  if (emailError) {
    window.alert(emailError);
    return;
  }

  try {
    await requestStore.cancelActiveReservation(
      reportReservationRecord.value,
      reportForm.remarks.trim(),
      {
        confirmedAdminEmail: normalizeEmailForConfirmation(reportForm.adminEmail),
      },
    );
    closeReportModal();
    selectedReservationRecord.value = null;
  } catch (error) {
    window.alert(error?.message || 'Unable to mark this reservation as overdue.');
  }
}

function closeConfirmModal() {
  confirmReservationRecord.value = null;
  confirmForm.remarks = '';
  confirmForm.adminEmail = '';
  workflowConfirmationTasks.value = [];
  workflowConfirmationError.value = '';
  isWorkflowConfirmationLoading.value = false;
  isWorkflowConfirmationSubmitting.value = false;
}

function closeReportModal() {
  reportReservationRecord.value = null;
  reportForm.remarks = '';
  reportForm.adminEmail = '';
}

function validateAdminEmailConfirmation(emailValue, actionName) {
  const normalizedEmail = normalizeEmailForConfirmation(emailValue);
  const actionCopy = actionName === 'confirm'
    ? 'confirming completion'
    : actionName === 'reject'
      ? 'rejecting this workflow'
      : 'marking this reservation as overdue';

  if (normalizedEmail === '') {
    return `Please type your exact admin email before ${actionCopy}.`;
  }

  if (currentAdminEmail.value === '') {
    return 'Unable to verify the admin in charge. Please sign in again.';
  }

  if (normalizedEmail !== currentAdminEmail.value) {
    return `Please type your exact admin email before ${actionCopy}.`;
  }

  return '';
}

function normalizeEmailForConfirmation(emailValue) {
  return String(emailValue || '').trim().toLowerCase();
}

function getRequesterInitials(reservationRecord) {
  const name = String(reservationRecord?.requesterFullName || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('');

  return name || 'TR';
}

async function openWorkflowConfirmationModal(reservationRecord) {
  confirmReservationRecord.value = reservationRecord;
  workflowConfirmationError.value = '';
  isWorkflowConfirmationLoading.value = true;

  try {
    const reservationIdentifier = Number(reservationRecord?.requestIdentifier || reservationRecord?.reservationIdentifier || 0);
    if (!reservationIdentifier) {
      workflowConfirmationTasks.value = [];
      return;
    }

    const result = await taskWorkflowApi.fetchTasksByReservation(reservationIdentifier, authStore.authToken);
    if (!result.success) {
      throw new Error(result.error || 'Unable to load workflow confirmation details.');
    }

    workflowConfirmationTasks.value = Array.isArray(result.data.tasks)
      ? result.data.tasks.map((taskRecord, index) => ({
        ...taskRecord,
        localKey: `workflow-confirmation-${taskRecord.taskIdentifier || index}`,
      }))
      : [];
  } catch (error) {
    workflowConfirmationTasks.value = [];
    workflowConfirmationError.value = error?.message || 'Unable to load workflow confirmation details.';
  } finally {
    isWorkflowConfirmationLoading.value = false;
  }
}

function formatWorkflowDateTime(value) {
  if (!value) {
    return 'N/A';
  }

  const parsedDate = new Date(value);
  if (Number.isNaN(parsedDate.getTime())) {
    return String(value);
  }

  return new Intl.DateTimeFormat('en-PH', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  }).format(parsedDate);
}

function getEquipmentResources(reservationRecord) {
  return (reservationRecord?.reservedResources || []).filter((resource) => resource.resourceType === 'Equipment');
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
