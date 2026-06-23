<template>
  <AdminSidebarLayoutComponent
    :role-label="authStore.userFullName || 'BORROWER'"
    :navigation-items="borrowerNavigationItems"
  >
    <section class="borrower-request-list-page">
      <div class="borrower-request-list-page__hero">
        <div>
          <p class="borrower-request-list-page__eyebrow">Request Database</p>
          <h1>My Request Listings</h1>
          <p>Review every reservation request you submitted, preview the full details, and withdraw pending ones when needed.</p>
        </div>
        <button type="button" class="borrower-request-list-page__create-button" @click="router.push({ name: ROUTE_NAMES.borrowerCreateReservation })">
          Create Request
        </button>
      </div>

      <div class="borrower-request-list-page__toolbar">
        <label class="borrower-request-list-page__field">
          <span>Search</span>
          <input v-model.trim="searchQuery" type="search" placeholder="Search code, facility, or activity" />
        </label>

        <label class="borrower-request-list-page__field">
          <span>Status</span>
          <select v-model="statusFilter">
            <option value="all">All statuses</option>
            <option v-for="option in statusOptions" :key="option" :value="option">{{ option }}</option>
          </select>
        </label>

        <label class="borrower-request-list-page__field">
          <span>Sort By</span>
          <select v-model="sortBy">
            <option value="requestedDate">Submitted Date</option>
            <option value="requestDisplayIdentifier">Request Code</option>
            <option value="facilityName">Facility</option>
            <option value="requestStatus">Status</option>
          </select>
        </label>

        <button type="button" class="borrower-request-list-page__sort-button" @click="toggleSortOrder">
          {{ sortOrder === 'asc' ? 'Ascending' : 'Descending' }}
        </button>
      </div>

      <div class="borrower-request-list-page__table-card">
        <div v-if="requestStore.isLoadingReservations" class="borrower-request-list-page__state">
          Loading your requests...
        </div>

        <template v-else>
          <table class="borrower-request-list-page__table">
            <thead>
              <tr>
                <th>Request</th>
                <th>Schedule</th>
                <th>Facility / Type</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="requestRecord in paginatedRequests" :key="requestRecord.requestIdentifier">
                <td>
                  <strong>{{ requestRecord.requestDisplayIdentifier }}</strong>
                  <p>{{ requestRecord.activityNameTitle }}</p>
                </td>
                <td>{{ formatSchedule(requestRecord.requestScheduleStart, requestRecord.requestScheduleEnd) }}</td>
                <td>
                  <strong>{{ requestRecord.facilityName }}</strong>
                  <p>{{ requestRecord.requestType }}</p>
                </td>
                <td>
                  <span class="borrower-request-list-page__badge" :class="getStatusBadgeClass(requestRecord.requestStatus)">
                    {{ requestRecord.requestStatus }}
                  </span>
                </td>
                <td>
                  <div class="borrower-request-list-page__actions">
                    <button type="button" class="borrower-request-list-page__action-button" @click="selectedRequest = requestRecord">
                      View
                    </button>
                    <button
                      type="button"
                      class="borrower-request-list-page__action-button borrower-request-list-page__action-button--danger"
                      :disabled="!canCancelRequest(requestRecord)"
                      @click="openCancelModal(requestRecord)"
                    >
                      Cancel
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="!paginatedRequests.length" class="borrower-request-list-page__state">
            Nothing found for the current search or filter.
          </div>
        </template>
      </div>

      <div v-if="totalPages > 1" class="borrower-request-list-page__pagination">
        <button type="button" :disabled="currentPage === 1" @click="currentPage -= 1">Previous</button>
        <span>Page {{ currentPage }} of {{ totalPages }}</span>
        <button type="button" :disabled="currentPage === totalPages" @click="currentPage += 1">Next</button>
      </div>
    </section>

    <BorrowerRequestViewModal :request-record="selectedRequest" @close="selectedRequest = null" />
    <BorrowerRequestCancelModal
      :request-record="requestToCancel"
      :is-submitting="isCancelling"
      @close="closeCancelModal"
      @confirm="handleCancelRequest"
    />
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import BorrowerRequestCancelModal from '@/modules/request/components/BorrowerRequestCancelModal.vue';
import BorrowerRequestViewModal from '@/modules/request/components/BorrowerRequestViewModal.vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';
import { formatDisplayDateTimeRange } from '@/shared/utils/dateTimeDisplay.js';
import '@/shared/components/adminSidebarLayout.css';
import './css/ViewReservationList.css';

const router = useRouter();
const authStore = useAuthenticationStore();
const requestStore = useRequestStore();
const searchQuery = ref('');
const statusFilter = ref('all');
const sortBy = ref('requestedDate');
const sortOrder = ref('desc');
const currentPage = ref(1);
const pageSize = 8;
const selectedRequest = ref(null);
const requestToCancel = ref(null);
const isCancelling = ref(false);

onMounted(async () => {
  await requestStore.fetchReservations();
});

const requestRecords = computed(() => {
  return [
    ...(requestStore.pendingRequestsList || []),
    ...(requestStore.approvedRequestsList || []),
    ...(requestStore.activeReservationsList || []),
    ...(requestStore.pastRecordsList || []),
  ];
});

const statusOptions = computed(() => [...new Set(requestRecords.value.map((requestRecord) => requestRecord.requestStatus))]);

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
        requestRecord.facilityName,
        requestRecord.activityNameTitle,
        requestRecord.requestStatus,
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

function toggleSortOrder() {
  sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
}

function canCancelRequest(requestRecord) {
  return ['Pending', 'Pending Review', 'Submitted'].includes(String(requestRecord.requestStatus || ''));
}

function openCancelModal(requestRecord) {
  if (!canCancelRequest(requestRecord)) {
    return;
  }

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

function formatSchedule(startValue, endValue) {
  return formatDisplayDateTimeRange(startValue, endValue);
}
</script>
