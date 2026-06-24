<!-- ===== AI GENERATED: BorrowerActiveReservationsLogsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'BORROWER'"
    :navigation-items="borrowerNavigationItems"
  >
    <!-- Page Header with Go Back Button -->
    <div class="logs-page-header">
      <h2 class="logs-page-heading">Active Reservations Logs</h2>
      <button class="logs-go-back-button" @click="handleGoBack">
        ← Go Back
      </button>
    </div>

    <!-- Search and Filter Controls -->
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
    </div>

    <!-- Logs Timeline -->
    <div class="logs-timeline">
      <div v-for="log in filteredLogs" :key="log.id" class="logs-entry">
        <div class="logs-entry-header">
          <span class="logs-entry-id">{{ log.reservationId }}</span>
          <span class="logs-entry-name">{{ log.name }}</span>
          <span class="logs-entry-date">{{ log.date }}</span>
        </div>
        <div class="logs-entry-details">
          <p><strong>Facility:</strong> {{ log.facility }}</p>
          <p><strong>Purpose:</strong> {{ log.purpose }}</p>
          <p><strong>Status:</strong> <span class="logs-status-badge logs-status-badge--active">{{ log.status }}</span></p>
          <p><strong>Activity:</strong> {{ log.activity }}</p>
          <button
            type="button"
            class="logs-action-button"
            @click="handleViewLog(log)"
          >
            View
          </button>
        </div>
      </div>

      <!-- No Results Message -->
      <div v-if="isLoading" class="logs-no-results">
        Loading active reservation logs...
      </div>
      <div v-else-if="filteredLogs.length === 0" class="logs-no-results">
        No active reservation logs found matching your search.
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
import { filterLogsBySearch, mapRequestRecordToLog } from './borrowerReservationLogUtils.js';

const router = useRouter();
const requestStore = useRequestStore();
const searchQuery = ref('');
const isLoading = ref(false);

onMounted(async () => {
  isLoading.value = true;

  try {
    await requestStore.fetchReservations();
  } catch (error) {
    console.error('Error fetching active reservation logs:', error);
  } finally {
    isLoading.value = false;
  }
});

const activeLogs = computed(() =>
  (requestStore.activeReservationsList || []).map((record) => mapRequestRecordToLog(record, 'Active'))
);

const filteredLogs = computed(() => {
  return filterLogsBySearch(activeLogs.value, searchQuery.value);
});

function handleGoBack() {
  router.back();
}

function handleViewLog(log) {
  router.push({
    name: ROUTE_NAMES.borrowerViewReservationList,
    query: {
      request: log?.reservationId || '',
      status: 'active',
    },
  });
}
</script>
