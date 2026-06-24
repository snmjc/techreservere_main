<!-- ===== AI GENERATED: BorrowerApprovedRequestsLogsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'BORROWER'"
    :navigation-items="borrowerNavigationItems"
  >
    <!-- Page Header with Go Back Button -->
    <div class="logs-page-header">
      <h2 class="logs-page-heading">Approved Requests Logs</h2>
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
            <th class="logs-th">Approved By</th>
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
                <img v-if="log.facilityImage" :src="log.facilityImage" :alt="log.facility" class="logs-facility-image" />
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
              <span class="logs-status-badge logs-status-badge--approved">
                {{ log.status }}
              </span>
            </td>
            <td class="logs-td">{{ log.approvedBy }}</td>
            <td class="logs-td">
              <button
                type="button"
                class="logs-action-button"
                @click="handleViewLog(log)"
              >
                View
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- No Results Message -->
      <div v-if="isLoading" class="logs-no-results">
        Loading approved request logs...
      </div>
      <div v-else-if="filteredLogs.length === 0" class="logs-no-results">
        No approved request logs found matching your search.
      </div>
    </div>

    <!-- Footer -->
    <div class="logs-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/Logs.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import { filterLogsBySearch, mapRequestRecordToLog, sortLogs } from './borrowerReservationLogUtils.js';

const router = useRouter();
const requestStore = useRequestStore();
const searchQuery = ref('');
const sortBy = ref('date');
const sortOrder = ref('asc');
const isLoading = ref(false);

onMounted(async () => {
  isLoading.value = true;

  try {
    await requestStore.fetchReservations();
  } catch (error) {
    console.error('Error fetching approved request logs:', error);
  } finally {
    isLoading.value = false;
  }
});

const approvedLogs = computed(() =>
  (requestStore.approvedRequestsList || []).map((record) => mapRequestRecordToLog(record, 'Approved'))
);

const filteredLogs = computed(() => {
  const logs = filterLogsBySearch(approvedLogs.value, searchQuery.value);
  return sortLogs(logs, sortBy.value, sortOrder.value);
});

function handleGoBack() {
  router.back();
}

function toggleSortOrder() {
  sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
}

function handleViewLog(log) {
  router.push({
    name: ROUTE_NAMES.borrowerViewReservationList,
    query: {
      request: log?.reservationId || '',
      status: 'approved',
    },
  });
}
</script>
