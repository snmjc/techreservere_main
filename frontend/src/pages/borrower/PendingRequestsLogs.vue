<!-- ===== AI GENERATED: BorrowerPendingRequestsLogsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'BORROWER'"
    :navigation-items="borrowerNavigationItems"
  >
    <section class="logs-shell">
      <section class="logs-hero">
        <p class="logs-hero-eyebrow">Release Workflow</p>
        <h1 class="logs-hero-title">Pending Requests Logs</h1>
        <p class="logs-hero-copy">Monitor reservation requests awaiting review, track schedules, and cancel entries before they move further into the workflow.</p>
      </section>

      <section class="logs-toolbar-card">
        <div class="logs-toolbar-grid">
          <label class="logs-toolbar-field">
            <span class="logs-toolbar-label">Search</span>
            <input
              v-model.trim="searchQuery"
              type="search"
              class="logs-toolbar-input"
              placeholder="Search code, facility, or activity"
            />
          </label>

          <label class="logs-toolbar-field">
            <span class="logs-toolbar-label">Status</span>
            <select v-model="statusFilter" class="logs-toolbar-select">
              <option value="all">All statuses</option>
              <option v-for="statusOption in statusOptions" :key="statusOption" :value="statusOption">{{ statusOption }}</option>
            </select>
          </label>

          <label class="logs-toolbar-field">
            <span class="logs-toolbar-label">Sort By</span>
            <select v-model="sortBy" class="logs-toolbar-select">
              <option value="date">Submitted Date</option>
              <option value="request">Request Code</option>
              <option value="facility">Facility</option>
              <option value="status">Status</option>
            </select>
          </label>

          <div class="logs-toolbar-field">
            <span class="logs-toolbar-label">Order</span>
            <button type="button" class="logs-toolbar-order" @click="toggleSortOrder">
              {{ sortOrder === 'desc' ? 'Descending' : 'Ascending' }}
            </button>
          </div>
        </div>
      </section>

      <section class="logs-board">
        <div class="logs-board-head">
          <span>Request</span>
          <span>Schedule</span>
          <span>Facility / Type</span>
          <span>Status</span>
          <span>Action</span>
        </div>

        <template v-if="isLoading">
          <div class="logs-board-empty">Loading pending requests...</div>
        </template>

        <template v-else-if="filteredLogs.length === 0">
          <div class="logs-board-empty">No pending request logs found.</div>
        </template>

        <article v-for="log in filteredLogs" :key="log.id" class="logs-board-row">
          <div class="logs-cell">
            <strong>{{ log.reservationId }}</strong>
            <span>{{ log.name }}</span>
            <small>{{ log.role }}</small>
          </div>

          <div class="logs-cell">
            <strong>{{ log.date }}</strong>
            <small>Submitted {{ log.submitted }}</small>
          </div>

          <div class="logs-cell">
            <strong>{{ log.facility }}</strong>
            <span>
              <i class="logs-type-pill" :class="log.type === 'Equipment' ? 'logs-type-pill--equipment' : 'logs-type-pill--venue'">
                {{ log.type }}
              </i>
            </span>
            <small>{{ log.purpose }}</small>
          </div>

          <div class="logs-cell">
            <span class="logs-status-pill logs-status-pill--pending">{{ log.status }}</span>
          </div>

          <div class="logs-cell logs-cell-action">
            <button
              type="button"
              class="logs-action-button logs-action-button--danger"
              :disabled="isCancelling"
              @click="openCancelModal(log.requestRecord)"
            >
              Cancel
            </button>
          </div>
        </article>
      </section>

      <div class="logs-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </div>
    </section>

    <BorrowerRequestCancelModal
      :request-record="requestToCancel"
      :is-submitting="isCancelling"
      :confirmation-email="currentBorrowerEmail"
      @close="closeCancelModal"
      @confirm="handleCancelRequest"
    />
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import BorrowerRequestCancelModal from '@/modules/request/components/BorrowerRequestCancelModal.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/Logs.css';
import './css/ViewReservationList.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { filterLogsBySearch, mapRequestRecordToLog, sortLogs } from './borrowerReservationLogUtils.js';

const requestStore = useRequestStore();
const authStore = useAuthenticationStore();
const searchQuery = ref('');
const statusFilter = ref('all');
const sortBy = ref('date');
const sortOrder = ref('desc');
const isLoading = ref(false);
const requestToCancel = ref(null);
const isCancelling = ref(false);
const currentBorrowerEmail = computed(() => {
  const account = authStore.accountData || authStore.clerkAccountData || {};
  return String(account.emailAddress || account.email || '').trim().toLowerCase();
});

onMounted(async () => {
  isLoading.value = true;

  try {
    await requestStore.fetchReservations();
  } catch (error) {
    console.error('Error fetching pending request logs:', error);
  } finally {
    isLoading.value = false;
  }
});

const pendingLogs = computed(() =>
  (requestStore.pendingRequestsList || []).map((record) => ({
    ...mapRequestRecordToLog(record, 'Pending'),
    requestRecord: record,
    status: normalizePendingStatus(record.requestStatus),
  }))
);

const statusOptions = computed(() => [...new Set(pendingLogs.value.map((log) => log.status).filter(Boolean))]);

const filteredLogs = computed(() => {
  let logs = filterLogsBySearch(pendingLogs.value, searchQuery.value);

  if (statusFilter.value !== 'all') {
    logs = logs.filter((log) => log.status === statusFilter.value);
  }

  return sortLogs(logs, sortBy.value === 'request' ? 'name' : sortBy.value === 'status' ? 'facility' : sortBy.value, sortOrder.value);
});

function toggleSortOrder() {
  sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
}

function openCancelModal(requestRecord) {
  requestToCancel.value = requestRecord;
}

function closeCancelModal() {
  requestToCancel.value = null;
}

async function handleCancelRequest({ reason }) {
  if (!requestToCancel.value) {
    return;
  }

  try {
    isCancelling.value = true;
    await requestStore.cancelOwnRequest(requestToCancel.value, reason);
    closeCancelModal();
  } catch (error) {
    console.error('Unable to cancel request.', error);
  } finally {
    isCancelling.value = false;
  }
}

function normalizePendingStatus(status) {
  return status === 'Pending Review' ? 'Pending' : status || 'Pending';
}
</script>
