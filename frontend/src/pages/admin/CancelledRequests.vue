<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="admin-cancelled-requests-page">
      <header class="admin-cancelled-requests-hero">
        <div>
          <p class="admin-cancelled-requests-kicker">Workflow Archive</p>
          <h1>Cancelled Requests</h1>
          <p>Review reservation requests that were cancelled and already recorded in the database archive.</p>
        </div>
        <div class="admin-cancelled-requests-badge">
          <strong>{{ filteredCancelledRequests.length }}</strong>
          <span>Cancelled records</span>
        </div>
      </header>

      <section class="admin-cancelled-requests-toolbar">
        <label class="admin-cancelled-requests-field admin-cancelled-requests-field--search">
          <span>Search</span>
          <input v-model.trim="searchQuery" type="search" placeholder="Search request, requester, facility, or purpose" />
        </label>

        <label class="admin-cancelled-requests-field">
          <span>Sort By</span>
          <select v-model="sortBy">
            <option value="requestedDate">Requested Date</option>
            <option value="requestDisplayIdentifier">Request Code</option>
            <option value="requesterFullName">Requester</option>
            <option value="facilityName">Facility</option>
          </select>
        </label>

        <button type="button" class="admin-cancelled-requests-sort-button" @click="toggleSortOrder">
          {{ sortOrder === 'asc' ? 'Ascending' : 'Descending' }}
        </button>
      </section>

      <section class="admin-cancelled-requests-table-card">
        <table class="admin-cancelled-requests-table">
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
              <td colspan="5" class="admin-cancelled-requests-state">Loading cancelled requests...</td>
            </tr>
            <tr v-else-if="paginatedCancelledRequests.length === 0">
              <td colspan="5" class="admin-cancelled-requests-state">No cancelled requests found.</td>
            </tr>
            <tr v-for="requestRecord in paginatedCancelledRequests" v-else :key="requestRecord.requestIdentifier">
              <td>
                <strong>{{ requestRecord.requestDisplayIdentifier }}</strong>
                <p>{{ requestRecord.activityTitle || requestRecord.activityNameTitle || 'N/A' }}</p>
              </td>
              <td>
                <strong>{{ requestRecord.requesterFullName }}</strong>
                <p>{{ requestRecord.requesterRole }}</p>
              </td>
              <td>{{ formatSchedule(requestRecord.requestScheduleStart, requestRecord.requestScheduleEnd) }}</td>
              <td>
                <span class="admin-cancelled-requests-status-pill">
                  {{ requestRecord.recordStatus || requestRecord.requestStatus }}
                </span>
              </td>
              <td>
                <button type="button" class="admin-cancelled-requests-view-button" @click="selectedRequest = requestRecord">
                  View
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      <div class="admin-cancelled-requests-pagination">
        <button type="button" :disabled="currentPage === 1" @click="currentPage -= 1">Previous</button>
        <span>Page {{ currentPage }} of {{ totalPages }}</span>
        <button type="button" :disabled="currentPage === totalPages" @click="currentPage += 1">Next</button>
      </div>
    </section>

    <RequestViewModalComponent
      :request-record="selectedRequest"
      :show-action-buttons="false"
      :show-revisions-button="false"
      @close-request-modal="selectedRequest = null"
    />
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import RequestViewModalComponent from '@/modules/request/components/RequestViewModalComponent.vue';
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { formatDisplayDateTimeRange } from '@/shared/utils/dateTimeDisplay.js';
import '@/shared/components/adminSidebarLayout.css';
import '@/modules/request/components/requestViewModal.css';
import './css/CancelledRequests.css';

const requestStore = useRequestStore();
const selectedRequest = ref(null);
const searchQuery = ref('');
const sortBy = ref('requestedDate');
const sortOrder = ref('desc');
const currentPage = ref(1);
const pageSize = 8;

onMounted(async () => {
  try {
    await requestStore.fetchReservations();
  } catch (error) {
    console.error('Error fetching cancelled requests:', error);
  }
});

const cancelledRequestsList = computed(() =>
  (requestStore.pastRecordsList || []).filter((record) => record.recordStatus === 'Cancelled')
);

const filteredCancelledRequests = computed(() => {
  const query = searchQuery.value.toLowerCase();
  const records = [...cancelledRequestsList.value];

  const searchedRecords = query
    ? records.filter((record) =>
      [
        record.requestDisplayIdentifier,
        record.requesterFullName,
        record.requesterRole,
        record.facilityName,
        record.requestPurpose,
        record.requestType,
      ].some((value) => String(value || '').toLowerCase().includes(query)))
    : records;

  searchedRecords.sort((first, second) => {
    const firstValue = normalizeSortValue(first?.[sortBy.value]);
    const secondValue = normalizeSortValue(second?.[sortBy.value]);

    if (firstValue < secondValue) return sortOrder.value === 'asc' ? -1 : 1;
    if (firstValue > secondValue) return sortOrder.value === 'asc' ? 1 : -1;
    return String(first.requestIdentifier).localeCompare(String(second.requestIdentifier));
  });

  return searchedRecords;
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredCancelledRequests.value.length / pageSize)));

const paginatedCancelledRequests = computed(() => {
  const start = (currentPage.value - 1) * pageSize;
  return filteredCancelledRequests.value.slice(start, start + pageSize);
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

function normalizeSortValue(value) {
  const dateValue = Date.parse(String(value || ''));
  if (!Number.isNaN(dateValue)) {
    return dateValue;
  }

  return String(value || '').toLowerCase();
}

function formatSchedule(startValue, endValue) {
  return formatDisplayDateTimeRange(startValue, endValue);
}
</script>
