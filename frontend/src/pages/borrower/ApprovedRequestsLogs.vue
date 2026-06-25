<template>
  <AdminSidebarLayoutComponent
    :role-label="'BORROWER'"
    :navigation-items="borrowerNavigationItems"
  >
    <section class="logs-shell">
      <section class="logs-hero">
        <p class="logs-hero-eyebrow">Release Workflow</p>
        <h1 class="logs-hero-title">Approved Requests Logs</h1>
        <p class="logs-hero-copy">Review approved requests, confirm schedules, and open the reservation list for the next step in the workflow.</p>
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
          <div class="logs-board-empty">Loading approved requests...</div>
        </template>

        <template v-else-if="filteredLogs.length === 0">
          <div class="logs-board-empty">No approved request logs found.</div>
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
            <span class="logs-status-pill logs-status-pill--approved">{{ log.status }}</span>
          </div>

          <div class="logs-cell logs-cell-action">
            <button
              type="button"
              class="logs-action-button"
              @click="handleViewLog(log)"
            >
              View
            </button>
          </div>
        </article>
      </section>

      <div class="logs-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </div>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
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
const statusFilter = ref('all');
const sortBy = ref('date');
const sortOrder = ref('desc');
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
  (requestStore.approvedRequestsList || []).map((record) => ({
    ...mapRequestRecordToLog(record, 'Approved'),
    status: normalizeApprovedStatus(record.requestStatus),
  }))
);

const statusOptions = computed(() => [...new Set(approvedLogs.value.map((log) => log.status).filter(Boolean))]);

const filteredLogs = computed(() => {
  let logs = filterLogsBySearch(approvedLogs.value, searchQuery.value);

  if (statusFilter.value !== 'all') {
    logs = logs.filter((log) => log.status === statusFilter.value);
  }

  return sortLogs(logs, sortBy.value, sortOrder.value);
});

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

function normalizeApprovedStatus(status) {
  return status || 'Approved';
}
</script>
