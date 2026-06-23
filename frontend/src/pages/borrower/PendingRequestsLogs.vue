<!-- ===== AI GENERATED: BorrowerPendingRequestsLogsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'BORROWER'"
    :navigation-items="borrowerNavigationItems"
  >
    <!-- Page Header with Go Back Button -->
    <div class="logs-page-header">
      <h2 class="logs-page-heading">Pending Requests Logs</h2>
      <button class="logs-go-back-button" @click="handleGoBack">
        ← Go Back
      </button>
    </div>

    <!-- Search and Sorting Controls -->
    <div class="logs-controls">
      <div class="logs-search-group">
        <label class="logs-search-label" for="logsSearch">Search:</label>
        <input
          id="logsSearch"
          v-model="searchQuery"
          type="text"
          class="logs-search-input"
          placeholder="Reservation ID or Name"
        />
      </div>

      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <div class="logs-sort-group">
          <label class="logs-sort-label" for="logsSort">Sort By:</label>
          <select
            id="logsSort"
            v-model="sortBy"
            class="logs-sort-select"
          >
            <option value="date">Date</option>
            <option value="name">Name</option>
            <option value="facility">Facility</option>
          </select>
        </div>

        <button
          class="logs-sort-toggle"
          @click="toggleSortOrder"
          :title="sortOrder === 'asc' ? 'Sort Descending' : 'Sort Ascending'"
        >
          {{ sortOrder === 'asc' ? '↑' : '↓' }}
        </button>
      </div>
    </div>

    <!-- Logs Table -->
    <div class="logs-table-wrapper">
      <table class="logs-table">
        <thead>
          <tr>
            <th class="logs-th">Reservation ID</th>
            <th class="logs-th">Name</th>
            <th class="logs-th">Role</th>
            <th class="logs-th">Schedule</th>
            <th class="logs-th">Facility</th>
            <th class="logs-th">Type</th>
            <th class="logs-th">Purpose</th>
            <th class="logs-th">Status</th>
            <th class="logs-th">Submitted</th>
            <th class="logs-th">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in filteredLogs" :key="log.id" class="logs-tr">
            <td class="logs-td">{{ log.reservationId }}</td>
            <td class="logs-td">{{ log.name }}</td>
            <td class="logs-td">
              <span class="logs-badge" :class="{ 'logs-badge--student': log.role === 'Student', 'logs-badge--faculty': log.role === 'Faculty' }">
                {{ log.role }}
              </span>
            </td>
            <td class="logs-td">{{ log.date }}</td>
            <td class="logs-td">
              <div class="logs-facility">
                <span>{{ log.facility }}</span>
              </div>
            </td>
            <td class="logs-td">
              <span class="logs-badge" :class="{ 'logs-badge--venue': log.type === 'Venue', 'logs-badge--equipment': log.type === 'Equipment' }">
                {{ log.type }}
              </span>
            </td>
            <td class="logs-td">{{ log.purpose }}</td>
            <td class="logs-td">
              <span class="logs-status-badge logs-status-badge--pending">
                {{ log.status }}
              </span>
            </td>
            <td class="logs-td">{{ log.submitted }}</td>
            <td class="logs-td">
              <button
                type="button"
                class="logs-action-button logs-action-button--danger"
                :disabled="isCancelling"
                @click="openCancelModal(log.requestRecord)"
              >
                Cancel
              </button>
            </td>
          </tr>
          <tr v-if="isLoading">
            <td colspan="10" class="logs-td logs-empty-row">Loading pending requests...</td>
          </tr>
          <tr v-else-if="filteredLogs.length === 0">
            <td colspan="10" class="logs-td logs-empty-row">No pending request logs found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Footer -->
    <div class="logs-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>

    <BorrowerRequestCancelModal
      :request-record="requestToCancel"
      :is-submitting="isCancelling"
      @close="closeCancelModal"
      @confirm="handleCancelRequest"
    />
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import BorrowerRequestCancelModal from '@/modules/request/components/BorrowerRequestCancelModal.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/Logs.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';

const router = useRouter();
const requestStore = useRequestStore();
const authStore = useAuthenticationStore();
const searchQuery = ref('');
const sortBy = ref('date');
const sortOrder = ref('asc');
const isLoading = ref(false);
const requestToCancel = ref(null);
const isCancelling = ref(false);

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
    id: record.requestIdentifier,
    requestRecord: record,
    reservationId: String(record.requestDisplayIdentifier || record.requestIdentifier),
    name: record.requesterFullName || 'User',
    role: authStore.userRole || record.requesterRole || 'Borrower',
    date: formatSchedule(record.requestScheduleStart, record.requestScheduleEnd),
    facility: record.facilityName || 'N/A',
    type: record.requestType || 'Reservation',
    purpose: record.requestPurpose || 'N/A',
    status: normalizePendingStatus(record.requestStatus),
    submitted: formatDateTime(record.requestedDate),
    sortDate: getDateSortValue(record.requestScheduleStart || record.activityTime),
  }))
);

const filteredLogs = computed(() => {
  let logs = [...pendingLogs.value];

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    logs = logs.filter(log =>
      log.reservationId.toLowerCase().includes(query) ||
      log.name.toLowerCase().includes(query)
    );
  }

  // Apply sorting
  logs.sort((a, b) => {
    let compareA, compareB;

    if (sortBy.value === 'date') {
      compareA = a.sortDate;
      compareB = b.sortDate;
    } else if (sortBy.value === 'name') {
      compareA = a.name.toLowerCase();
      compareB = b.name.toLowerCase();
    } else if (sortBy.value === 'facility') {
      compareA = a.facility.toLowerCase();
      compareB = b.facility.toLowerCase();
    }

    if (typeof compareA === 'string' && typeof compareB === 'string') {
      return sortOrder.value === 'asc'
        ? compareA.localeCompare(compareB)
        : compareB.localeCompare(compareA);
    } else {
      return sortOrder.value === 'asc'
        ? compareA - compareB
        : compareB - compareA;
    }
  });

  return logs;
});

function handleGoBack() {
  router.back();
}

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

function formatDateTime(value) {
  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return value || 'N/A';
  }

  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: 'numeric',
    minute: '2-digit',
  }).format(date);
}

function formatSchedule(startValue, endValue) {
  const startDate = formatDateTime(startValue);
  const endDate = formatDateTime(endValue);

  if (startDate !== 'N/A' && endDate !== 'N/A') {
    return `${startDate} - ${endDate}`;
  }

  return startDate !== 'N/A' ? startDate : endDate;
}

function getDateSortValue(value) {
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? 0 : date.getTime();
}
</script>
