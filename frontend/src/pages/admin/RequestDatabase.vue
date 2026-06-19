<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="admin-request-database-page">
      <header class="admin-request-database-page__hero">
        <div>
          <p class="admin-request-database-page__eyebrow">Request Database</p>
          <h1>All Request Listings</h1>
          <p>Review submitted, approved, active, and archived requests from one page.</p>
        </div>
        <div class="admin-request-database-page__hero-badge">
          <strong>{{ filteredRequests.length }}</strong>
          <span>Visible requests</span>
        </div>
      </header>

      <div class="admin-request-database-page__toolbar">
        <label class="admin-request-database-page__field">
          <span>Search</span>
          <input v-model.trim="searchQuery" type="search" placeholder="Search request, requester, or schedule" />
        </label>

        <label class="admin-request-database-page__field">
          <span>Status</span>
          <select v-model="statusFilter">
            <option value="all">All statuses</option>
            <option v-for="option in statusOptions" :key="option" :value="option">{{ option }}</option>
          </select>
        </label>

        <label class="admin-request-database-page__field">
          <span>Sort By</span>
          <select v-model="sortBy">
            <option value="requestedDate">Submitted Date</option>
            <option value="requestDisplayIdentifier">Request Code</option>
            <option value="requesterFullName">Requester</option>
            <option value="requestStatus">Status</option>
          </select>
        </label>

        <button type="button" class="admin-request-database-page__sort-button" @click="toggleSortOrder">
          {{ sortOrder === 'asc' ? 'Ascending' : 'Descending' }}
        </button>
      </div>

      <section class="admin-request-database-page__table-card">
        <table class="admin-request-database-page__table">
          <thead>
            <tr>
              <th>Request</th>
              <th>Requester</th>
              <th>Schedule</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="requestStore.isLoadingReservations">
              <td colspan="5" class="admin-request-database-page__state">Loading request listings...</td>
            </tr>
            <tr v-else-if="paginatedRequests.length === 0">
              <td colspan="5" class="admin-request-database-page__state">No request rows available.</td>
            </tr>
            <tr v-for="requestRecord in paginatedRequests" v-else :key="requestRecord.requestIdentifier">
              <td>
                <strong>{{ requestRecord.requestDisplayIdentifier }}</strong>
                <p>{{ requestRecord.activityNameTitle }}</p>
              </td>
              <td>
                <strong>{{ requestRecord.requesterFullName }}</strong>
                <p>{{ requestRecord.requesterRole }}</p>
              </td>
              <td>{{ formatDateTime(requestRecord.activityTime) }}</td>
              <td>
                <span class="admin-request-database-page__badge" :class="getStatusBadgeClass(requestRecord.requestStatus)">
                  {{ requestRecord.requestStatus }}
                </span>
              </td>
              <td>
                <div class="admin-request-database-page__actions">
                  <button type="button" class="admin-request-database-page__action-button" @click="selectedRequest = requestRecord">
                    View
                  </button>
                  <button
                    type="button"
                    class="admin-request-database-page__action-button admin-request-database-page__action-button--approve"
                    :disabled="!canReviewRequest(requestRecord) || isSubmittingAction"
                    @click="openApproveModal(requestRecord)"
                  >
                    Approve
                  </button>
                  <button
                    type="button"
                    class="admin-request-database-page__action-button admin-request-database-page__action-button--reject"
                    :disabled="!canReviewRequest(requestRecord) || isSubmittingAction"
                    @click="openRejectModal(requestRecord)"
                  >
                    Reject
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      <div v-if="totalPages > 1" class="admin-request-database-page__pagination">
        <button type="button" :disabled="currentPage === 1" @click="currentPage -= 1">Previous</button>
        <span>Page {{ currentPage }} of {{ totalPages }}</span>
        <button type="button" :disabled="currentPage === totalPages" @click="currentPage += 1">Next</button>
      </div>
    </section>

    <RequestViewModalComponent
      :request-record="selectedRequest"
      :show-revisions-button="false"
      :review-actions-disabled="!canReviewRequest(selectedRequest)"
      @close-request-modal="selectedRequest = null"
      @approve-request-record="openApproveModal"
      @reject-request-record="openRejectModal"
    />

    <div v-if="approveRequestRecord" class="admin-request-database-page__overlay" @click.self="closeApproveModal">
      <section class="admin-request-database-page__modal">
        <header class="admin-request-database-page__modal-header">
          <div>
            <h2>Approve Request</h2>
            <p>Confirm the request using your admin email.</p>
          </div>
          <button type="button" class="admin-request-database-page__modal-close" @click="closeApproveModal">&times;</button>
        </header>

        <div class="admin-request-database-page__modal-body">
          <div class="admin-request-database-page__modal-summary">
            <strong>{{ approveRequestRecord.requestDisplayIdentifier }}</strong>
            <span>{{ approveRequestRecord.requesterFullName }}</span>
            <span>{{ approveRequestRecord.requestStatus }}</span>
          </div>

          <label class="admin-request-database-page__field admin-request-database-page__field--full">
            <span>Admin Email</span>
            <input v-model.trim="approveForm.adminEmail" type="email" :placeholder="currentAdminEmail || 'Enter your admin email'" />
          </label>
        </div>

        <footer class="admin-request-database-page__modal-footer">
          <button type="button" class="admin-request-database-page__modal-button admin-request-database-page__modal-button--ghost" @click="closeApproveModal">
            Cancel
          </button>
          <button
            type="button"
            class="admin-request-database-page__modal-button admin-request-database-page__modal-button--approve"
            :disabled="isSubmittingAction"
            @click="submitApproveRequest"
          >
            Approve
          </button>
        </footer>
      </section>
    </div>

    <div v-if="rejectRequestRecord" class="admin-request-database-page__overlay" @click.self="closeRejectModal">
      <section class="admin-request-database-page__modal">
        <header class="admin-request-database-page__modal-header">
          <div>
            <h2>Reject Request</h2>
            <p>Provide the rejection remarks and confirm with your admin email.</p>
          </div>
          <button type="button" class="admin-request-database-page__modal-close" @click="closeRejectModal">&times;</button>
        </header>

        <div class="admin-request-database-page__modal-body">
          <div class="admin-request-database-page__modal-summary">
            <strong>{{ rejectRequestRecord.requestDisplayIdentifier }}</strong>
            <span>{{ rejectRequestRecord.requesterFullName }}</span>
            <span>{{ rejectRequestRecord.requestStatus }}</span>
          </div>

          <label class="admin-request-database-page__field admin-request-database-page__field--full">
            <span>Remarks</span>
            <textarea v-model.trim="rejectForm.remarks" rows="4" maxlength="500" placeholder="Enter rejection reason" />
          </label>

          <label class="admin-request-database-page__field admin-request-database-page__field--full">
            <span>Admin Email</span>
            <input v-model.trim="rejectForm.adminEmail" type="email" :placeholder="currentAdminEmail || 'Enter your admin email'" />
          </label>
        </div>

        <footer class="admin-request-database-page__modal-footer">
          <button type="button" class="admin-request-database-page__modal-button admin-request-database-page__modal-button--ghost" @click="closeRejectModal">
            Cancel
          </button>
          <button
            type="button"
            class="admin-request-database-page__modal-button admin-request-database-page__modal-button--reject"
            :disabled="isSubmittingAction"
            @click="submitRejectRequest"
          >
            Reject
          </button>
        </footer>
      </section>
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import RequestViewModalComponent from '@/modules/request/components/RequestViewModalComponent.vue';
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import '@/shared/components/adminSidebarLayout.css';
import '@/modules/request/components/requestViewModal.css';
import './css/RequestDatabase.css';

const REVIEWABLE_STATUSES = ['Pending', 'Pending Review', 'Submitted'];

const requestStore = useRequestStore();
const authStore = useAuthenticationStore();
const searchQuery = ref('');
const statusFilter = ref('all');
const sortBy = ref('requestedDate');
const sortOrder = ref('desc');
const currentPage = ref(1);
const pageSize = 10;
const selectedRequest = ref(null);
const approveRequestRecord = ref(null);
const rejectRequestRecord = ref(null);
const isSubmittingAction = ref(false);

const approveForm = reactive({
  adminEmail: '',
});
const rejectForm = reactive({
  remarks: '',
  adminEmail: '',
});

const currentAdminEmail = computed(() => {
  const account = authStore.accountData || authStore.clerkAccountData || {};
  return String(account.emailAddress || account.email || '').trim().toLowerCase();
});

const requestRecords = computed(() => {
  return [
    ...(requestStore.pendingRequestsList || []),
    ...(requestStore.approvedRequestsList || []),
    ...(requestStore.activeReservationsList || []),
    ...(requestStore.pastRecordsList || []),
  ];
});

const statusOptions = computed(() => [...new Set(requestRecords.value.map((requestRecord) => requestRecord.requestStatus).filter(Boolean))]);

const filteredRequests = computed(() => {
  const query = searchQuery.value.toLowerCase();

  return requestRecords.value
    .filter((requestRecord) => {
      if (statusFilter.value !== 'all' && requestRecord.requestStatus !== statusFilter.value) {
        return false;
      }

      if (!query) {
        return true;
      }

      return [
        requestRecord.requestDisplayIdentifier,
        requestRecord.requesterFullName,
        requestRecord.requestStatus,
        requestRecord.activityNameTitle,
        requestRecord.requestSchedule,
      ].some((value) => String(value || '').toLowerCase().includes(query));
    })
    .sort((leftRecord, rightRecord) => compareRequestRecords(leftRecord, rightRecord, sortBy.value, sortOrder.value));
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredRequests.value.length / pageSize)));
const paginatedRequests = computed(() => {
  const startIndex = (currentPage.value - 1) * pageSize;
  return filteredRequests.value.slice(startIndex, startIndex + pageSize);
});

watch([searchQuery, statusFilter, sortBy, sortOrder], () => {
  currentPage.value = 1;
});

watch(totalPages, (nextTotalPages) => {
  if (currentPage.value > nextTotalPages) {
    currentPage.value = nextTotalPages;
  }
});

onMounted(async () => {
  try {
    await requestStore.fetchReservations();
  } catch (error) {
    console.error('Error fetching request database:', error);
  }
});

function toggleSortOrder() {
  sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
}

function canReviewRequest(requestRecord) {
  return REVIEWABLE_STATUSES.includes(String(requestRecord?.requestStatus || ''));
}

function openApproveModal(requestRecord) {
  if (!canReviewRequest(requestRecord) || !requestRecord) {
    return;
  }

  approveRequestRecord.value = requestRecord;
  approveForm.adminEmail = currentAdminEmail.value;
}

function openRejectModal(requestRecord) {
  if (!canReviewRequest(requestRecord) || !requestRecord) {
    return;
  }

  rejectRequestRecord.value = requestRecord;
  rejectForm.remarks = '';
  rejectForm.adminEmail = currentAdminEmail.value;
}

async function submitApproveRequest() {
  if (!approveRequestRecord.value) {
    return;
  }

  const emailError = validateAdminEmailConfirmation(approveForm.adminEmail, 'approve');
  if (emailError) {
    window.alert(emailError);
    return;
  }

  try {
    isSubmittingAction.value = true;
    await requestStore.approvePendingRequest(approveRequestRecord.value, {
      confirmedAdminEmail: normalizeEmailForConfirmation(approveForm.adminEmail),
    });
    closeApproveModal();
    selectedRequest.value = null;
  } catch (error) {
    window.alert(error?.message || 'Unable to approve this request.');
  } finally {
    isSubmittingAction.value = false;
  }
}

async function submitRejectRequest() {
  if (!rejectRequestRecord.value) {
    return;
  }

  if (rejectForm.remarks.trim() === '') {
    window.alert('Please enter remarks before rejecting this request.');
    return;
  }

  const emailError = validateAdminEmailConfirmation(rejectForm.adminEmail, 'reject');
  if (emailError) {
    window.alert(emailError);
    return;
  }

  try {
    isSubmittingAction.value = true;
    await requestStore.rejectPendingRequest(rejectRequestRecord.value, rejectForm.remarks.trim(), {
      confirmedAdminEmail: normalizeEmailForConfirmation(rejectForm.adminEmail),
    });
    closeRejectModal();
    selectedRequest.value = null;
  } catch (error) {
    window.alert(error?.message || 'Unable to reject this request.');
  } finally {
    isSubmittingAction.value = false;
  }
}

function closeApproveModal() {
  approveRequestRecord.value = null;
  approveForm.adminEmail = '';
}

function closeRejectModal() {
  rejectRequestRecord.value = null;
  rejectForm.remarks = '';
  rejectForm.adminEmail = '';
}

function validateAdminEmailConfirmation(emailValue, actionName) {
  const normalizedEmail = normalizeEmailForConfirmation(emailValue);
  if (normalizedEmail === '') {
    return `Please type your exact admin email before ${actionName === 'approve' ? 'approving' : 'rejecting'} this request.`;
  }

  if (currentAdminEmail.value === '') {
    return 'Unable to verify the admin in charge. Please sign in again.';
  }

  if (normalizedEmail !== currentAdminEmail.value) {
    return `Please type your exact admin email before ${actionName === 'approve' ? 'approving' : 'rejecting'} this request.`;
  }

  return '';
}

function normalizeEmailForConfirmation(emailValue) {
  return String(emailValue || '').trim().toLowerCase();
}

function getStatusBadgeClass(status) {
  const normalizedStatus = String(status || '').toLowerCase();
  if (normalizedStatus.includes('approved') || normalizedStatus.includes('active') || normalizedStatus.includes('deploy')) return 'is-approved';
  if (normalizedStatus.includes('cancel')) return 'is-cancelled';
  if (normalizedStatus.includes('reject')) return 'is-rejected';
  if (normalizedStatus.includes('complete')) return 'is-completed';
  return 'is-pending';
}

function compareRequestRecords(leftRecord, rightRecord, sortKey, direction) {
  const leftValue = resolveSortValue(leftRecord, sortKey);
  const rightValue = resolveSortValue(rightRecord, sortKey);

  if (typeof leftValue === 'number' && typeof rightValue === 'number') {
    return direction === 'asc' ? leftValue - rightValue : rightValue - leftValue;
  }

  return direction === 'asc'
    ? String(leftValue).localeCompare(String(rightValue))
    : String(rightValue).localeCompare(String(leftValue));
}

function resolveSortValue(requestRecord, sortKey) {
  if (sortKey === 'requestedDate') {
    const parsedDate = new Date(requestRecord.requestedDate);
    return Number.isNaN(parsedDate.getTime()) ? 0 : parsedDate.getTime();
  }

  return String(requestRecord[sortKey] || '').toLowerCase();
}

function formatDateTime(value) {
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return value || 'N/A';
  }

  return new Intl.DateTimeFormat('en-PH', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  }).format(parsed);
}
</script>
