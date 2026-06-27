<template>
  <AdminSidebarLayoutComponent
    :role-label="authStore.userFullName || 'BORROWER'"
    :navigation-items="borrowerNavigationItems"
  >
    <div class="logs-page-header">
      <h2 class="logs-page-heading">Active Reservations</h2>
      <button class="logs-go-back-button" @click="navigateBackToMyReservations">
        ← Go Back
      </button>
    </div>

    <div class="logs-controls">
      <div class="logs-search-group">
        <label class="logs-search-label" for="activeLogsSearch">Search:</label>
        <input
          id="activeLogsSearch"
          v-model="searchQuery"
          type="text"
          class="logs-search-input"
          placeholder="Reservation ID or Name"
        />
      </div>

      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <div class="logs-sort-group">
          <label class="logs-sort-label" for="activeLogsSort">Sort By:</label>
          <select
            id="activeLogsSort"
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
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in paginatedLogs" :key="log.id" class="logs-tr">
            <td class="logs-td">{{ log.reservationId }}</td>
            <td class="logs-td">{{ log.name }}</td>
            <td class="logs-td">{{ log.role }}</td>
            <td class="logs-td">{{ log.date }}</td>
            <td class="logs-td">
              <div class="logs-facility">
                <span>{{ log.facility }}</span>
              </div>
            </td>
            <td class="logs-td">
              <span class="logs-badge" :class="getTypeBadgeClass(log.type)">
                {{ log.type }}
              </span>
            </td>
            <td class="logs-td">{{ log.purpose }}</td>
            <td class="logs-td">
              <span class="logs-status-badge" :class="getStatusBadgeClass(log.status)">
                {{ log.status }}
              </span>
            </td>
            <td class="logs-td">{{ log.submitted }}</td>
          </tr>
          <tr v-if="loading">
            <td colspan="9" class="logs-td logs-empty-row">Loading active reservations...</td>
          </tr>
          <tr v-else-if="paginatedLogs.length === 0">
            <td colspan="9" class="logs-td logs-empty-row">No active reservations found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="totalPages > 1" class="logs-pagination">
      <button type="button" :disabled="currentPage === 1" @click="currentPage -= 1">Previous</button>
      <span>Page {{ currentPage }} of {{ totalPages }}</span>
      <button type="button" :disabled="currentPage === totalPages" @click="currentPage += 1">Next</button>
    </div>

    <div class="logs-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>
    <DataRequestStatusFloater :items="activeReservationsStatusItems" />
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import DataRequestStatusFloater from '@/shared/components/DataRequestStatusFloater.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/Logs.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';

const router = useRouter();
const requestStore = useRequestStore();
const authStore = useAuthenticationStore();
const loading = ref(false);
const searchQuery = ref('');
const sortBy = ref('date');
const sortOrder = ref('asc');
const currentPage = ref(1);
const pageSize = 8;

onMounted(async () => {
  try {
    loading.value = true;
    await requestStore.fetchReservations();
  } catch (error) {
    console.error('Error fetching active reservations:', error);
  } finally {
    loading.value = false;
  }
});

const activeLogs = computed(() =>
  (requestStore.activeReservationsList || []).map((record) => ({
    id: record.requestIdentifier,
    reservationId: String(record.requestDisplayIdentifier || record.requestIdentifier),
    name: record.requesterFullName || 'User',
    role: record.requesterRole || 'Borrower',
    date: record.requestSchedule || 'N/A',
    facility: record.facilityName || 'N/A',
    type: record.requestType || 'Reservation',
    purpose: record.requestPurpose || 'N/A',
    status: record.requestStatus || 'Active',
    submitted: formatDateTime(record.requestedDate),
    sortDate: getDateSortValue(record.requestScheduleStart || record.activityTime),
  }))
);
const activeReservationsStatusItems = computed(() => [
  {
    key: 'active',
    label: 'Active reservations',
    state: resolveLocalListState(activeLogs.value),
  },
]);

const filteredLogs = computed(() => {
  let logs = [...activeLogs.value];

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    logs = logs.filter((log) =>
      log.reservationId.toLowerCase().includes(query)
      || log.name.toLowerCase().includes(query)
    );
  }

  logs.sort((a, b) => {
    let compareA;
    let compareB;

    if (sortBy.value === 'date') {
      compareA = a.sortDate;
      compareB = b.sortDate;
    } else if (sortBy.value === 'name') {
      compareA = a.name.toLowerCase();
      compareB = b.name.toLowerCase();
    } else {
      compareA = a.facility.toLowerCase();
      compareB = b.facility.toLowerCase();
    }

    if (typeof compareA === 'string' && typeof compareB === 'string') {
      return sortOrder.value === 'asc'
        ? compareA.localeCompare(compareB)
        : compareB.localeCompare(compareA);
    }

    return sortOrder.value === 'asc'
      ? compareA - compareB
      : compareB - compareA;
  });

  return logs;
});
const totalPages = computed(() => Math.max(1, Math.ceil(filteredLogs.value.length / pageSize)));
const paginatedLogs = computed(() => {
  const startIndex = (currentPage.value - 1) * pageSize;
  return filteredLogs.value.slice(startIndex, startIndex + pageSize);
});

watch([searchQuery, sortBy, sortOrder], () => {
  currentPage.value = 1;
});

watch(totalPages, (pageCount) => {
  if (currentPage.value > pageCount) {
    currentPage.value = pageCount;
  }
});

function toggleSortOrder() {
  sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
}

function getTypeBadgeClass(type) {
  const normalizedType = String(type || '').toLowerCase();
  if (normalizedType === 'venue') return 'logs-badge--venue';
  if (normalizedType === 'equipment') return 'logs-badge--equipment';
  return 'logs-badge--venue';
}

function getStatusBadgeClass(status) {
  const normalizedStatus = String(status || '').toLowerCase();
  if (normalizedStatus.includes('approved') || normalizedStatus.includes('prepared')) {
    return 'logs-status-badge--approved';
  }

  return 'logs-status-badge--active';
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

function getDateSortValue(value) {
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? 0 : date.getTime();
}

function navigateBackToMyReservations() {
  router.push({ name: ROUTE_NAMES.borrowerMyReservations });
}

function resolveLocalListState(records) {
  if (loading.value && records.length > 0) return 'cached-loading';
  if (loading.value) return 'loading';
  return records.length > 0 ? 'fresh' : 'idle';
}
</script>
