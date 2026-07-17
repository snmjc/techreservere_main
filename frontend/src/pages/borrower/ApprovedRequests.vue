<!-- ===== AI GENERATED: BorrowerApprovedRequestsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="''"
    :navigation-items="borrowerNavigationItems"
  >
    <!-- Page Header -->
    <div class="borrower-sublist-page-header">
      <h2 class="borrower-sublist-page-heading">Approved Upcoming Requests</h2>
      <span class="borrower-sublist-go-back-link" @click="navigateBackToMyReservations">Go Back</span>
    </div>

    <!-- Toolbar -->
    <div class="borrower-sublist-toolbar">
      <div class="borrower-sublist-search-group">
        <label class="borrower-sublist-search-label" for="borrowerApprovedSearch">Search:</label>
        <input
          id="borrowerApprovedSearch"
          v-model="searchQueryText"
          type="text"
          class="borrower-sublist-search-input"
          placeholder="Name"
        />
      </div>
      <div class="borrower-sublist-showing-group">
        <label class="borrower-sublist-showing-label" for="borrowerApprovedShowing">Showing:</label>
        <select id="borrowerApprovedShowing" v-model="showingFilterValue" class="borrower-sublist-showing-select">
          <option value="all">All</option>
        </select>
        <button class="borrower-sublist-sort-button" aria-label="Sort">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="borrower-sublist-table-wrapper">
      <table class="borrower-sublist-table">
        <thead>
          <tr class="borrower-sublist-table-header-row">
            <th class="borrower-sublist-table-header-cell">ID</th>
            <th class="borrower-sublist-table-header-cell">Name</th>
            <th class="borrower-sublist-table-header-cell">Role</th>
            <th class="borrower-sublist-table-header-cell">Date Requested</th>
            <th class="borrower-sublist-table-header-cell">Schedule</th>
            <th class="borrower-sublist-table-header-cell">Facility</th>
            <th class="borrower-sublist-table-header-cell">Quantity</th>
            <th class="borrower-sublist-table-header-cell">Type</th>
            <th class="borrower-sublist-table-header-cell">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="record in paginatedRecordList"
            :key="record.requestIdentifier + record.requesterFullName"
            class="borrower-sublist-table-body-row"
          >
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--id">{{ record.requestIdentifier }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--name">{{ record.requesterFullName }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--role">{{ record.requesterRole }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--requested-date">{{ formatRequestedDate(record.requestedDate) }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--schedule">{{ record.requestSchedule }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--facility">{{ record.facilityName }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--quantity">{{ record.requestQuantity }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--type">
              <span class="borrower-sublist-type-badge" :class="getTypeBadgeClass(record.requestType)">{{ record.requestType }}</span>
            </td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--status">
              <span class="borrower-sublist-status-badge borrower-sublist-status-badge--approved">Approved</span>
            </td>
          </tr>
          <tr v-if="paginatedRecordList.length === 0">
            <td colspan="9" class="borrower-sublist-table-cell borrower-sublist-table-empty-row">No approved requests.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="borrower-sublist-pagination">
      <button type="button" :disabled="currentPage === 1" @click="currentPage -= 1">Previous</button>
      <span>Page {{ currentPage }} of {{ totalPages }}</span>
      <button type="button" :disabled="currentPage === totalPages" @click="currentPage += 1">Next</button>
    </div>

    <!-- Footer -->
    <div class="borrower-sublist-page-footer">&copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.</div>
    <DataRequestStatusFloater :items="approvedRequestsStatusItems" />
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import DataRequestStatusFloater from '@/shared/components/DataRequestStatusFloater.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/SubList.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';

const router = useRouter();
const requestStore = useRequestStore();
const authStore = useAuthenticationStore();
const loading = ref(false);
const searchQueryText = ref('');
const showingFilterValue = ref('all');
const currentPage = ref(1);
const pageSize = 8;

const approvedRecordsList = computed(() => requestStore.approvedRequestsList || []);
const approvedRequestsStatusItems = computed(() => [
  {
    key: 'approved',
    label: 'Approved requests',
    state: resolveReservationListState(approvedRecordsList.value),
  },
]);

onMounted(async () => {
  try {
    await requestStore.fetchReservations();
    const list = requestStore.approvedRequestsList || [];
    console.log('Borrower Approved Requests - Count:', list.length);
  } catch (error) {
    console.error('Error fetching approved requests:', error);
  }
});

const filteredRecordList = computed(() => {
  const queryLower = searchQueryText.value.toLowerCase().trim();
  const list = approvedRecordsList.value || [];
  if (!queryLower) return list;
  return list.filter((record) =>
    record.requesterFullName?.toLowerCase().includes(queryLower) ||
    record.requestIdentifier?.toString().includes(queryLower)
  );
});
const totalPages = computed(() => Math.max(1, Math.ceil(filteredRecordList.value.length / pageSize)));
const paginatedRecordList = computed(() => {
  const startIndex = (currentPage.value - 1) * pageSize;
  return filteredRecordList.value.slice(startIndex, startIndex + pageSize);
});

const hasNoRecords = computed(() => {
  const list = filteredRecordList.value || [];
  return list.length === 0;
});

watch([searchQueryText, showingFilterValue], () => {
  currentPage.value = 1;
});

watch(totalPages, (pageCount) => {
  if (currentPage.value > pageCount) {
    currentPage.value = pageCount;
  }
});

function getTypeBadgeClass(requestType) {
  const typeLower = requestType.toLowerCase();
  if (typeLower === 'venue') return 'borrower-sublist-type-badge--venue';
  if (typeLower === 'equipment') return 'borrower-sublist-type-badge--equipment';
  if (typeLower === 'both') return 'borrower-sublist-type-badge--both';
  return '';
}

function formatRequestedDate(value) {
  const parsedDate = new Date(value);
  if (Number.isNaN(parsedDate.getTime())) {
    return value || 'N/A';
  }

  return new Intl.DateTimeFormat('en-PH', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(parsedDate);
}

function navigateBackToMyReservations() {
  router.push({ name: ROUTE_NAMES.borrowerMyReservations });
}

function resolveReservationListState(records) {
  if (requestStore.isLoadingReservations && records.length > 0) return 'cached-loading';
  if (requestStore.isLoadingReservations) return 'loading';
  return records.length > 0 ? 'fresh' : 'idle';
}
</script>
