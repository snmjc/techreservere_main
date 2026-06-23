<!-- ===== AI GENERATED: BorrowerActiveReservationsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="userFullName || 'BORROWER'"
    :navigation-items="borrowerNavigationItems"
  >
    <div class="borrower-sublist-page-header">
      <h2 class="borrower-sublist-page-heading">Active Reservations</h2>
      <span class="borrower-sublist-go-back-link" @click="navigateBackToMyReservations">Go Back</span>
    </div>

    <div class="borrower-sublist-toolbar">
      <div class="borrower-sublist-search-group">
        <label class="borrower-sublist-search-label" for="borrowerActiveSearch">Search:</label>
        <input
          id="borrowerActiveSearch"
          v-model="searchQueryText"
          type="text"
          class="borrower-sublist-search-input"
          placeholder="Name"
        />
      </div>
      <div class="borrower-sublist-showing-group">
        <label class="borrower-sublist-showing-label" for="borrowerActiveShowing">Showing:</label>
        <select id="borrowerActiveShowing" v-model="showingFilterValue" class="borrower-sublist-showing-select">
          <option value="all">All</option>
        </select>
        <button class="borrower-sublist-sort-button" type="button" aria-label="Sort active reservations">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/>
          </svg>
        </button>
      </div>
    </div>

    <div class="borrower-sublist-table-wrapper">
      <table class="borrower-sublist-table">
        <thead>
          <tr class="borrower-sublist-table-header-row">
            <th class="borrower-sublist-table-header-cell">ID</th>
            <th class="borrower-sublist-table-header-cell">Name</th>
            <th class="borrower-sublist-table-header-cell">Role</th>
            <th class="borrower-sublist-table-header-cell">Schedule</th>
            <th class="borrower-sublist-table-header-cell">Facility</th>
            <th class="borrower-sublist-table-header-cell">Quantity</th>
            <th class="borrower-sublist-table-header-cell">Type</th>
            <th class="borrower-sublist-table-header-cell">Purpose</th>
            <th class="borrower-sublist-table-header-cell">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="record in filteredRecordList"
            :key="record.requestIdentifier + record.requesterFullName"
            class="borrower-sublist-table-body-row"
          >
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--id">{{ record.requestDisplayIdentifier || record.requestIdentifier }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--name">{{ record.requesterFullName }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--role">{{ record.requesterRole }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--schedule">{{ record.requestSchedule }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--facility">{{ record.facilityName }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--quantity">{{ formatQuantity(record.requestQuantity) }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--type">
              <span class="borrower-sublist-type-badge" :class="getTypeBadgeClass(record.requestType)">{{ record.requestType }}</span>
            </td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--purpose">{{ record.requestPurpose }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--status">
              <span class="borrower-sublist-status-badge borrower-sublist-status-badge--active">
                {{ record.requestStatus || 'Active' }}
              </span>
            </td>
          </tr>
          <tr v-if="filteredRecordList.length === 0">
            <td colspan="9" class="borrower-sublist-table-cell borrower-sublist-table-empty-row">
              {{ loading ? 'Loading active reservations...' : 'No active reservations.' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="borrower-sublist-page-footer">&copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.</div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
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

const activeRecordsList = computed(() => requestStore.activeReservationsList || []);
const userFullName = computed(() => authStore.userFullName);

onMounted(async () => {
  try {
    loading.value = true;
    await requestStore.fetchReservations();
    const list = requestStore.activeReservationsList || [];
    console.log('Borrower Active Reservations - Count:', list.length);
  } catch (error) {
    console.error('Error fetching active reservations:', error);
  } finally {
    loading.value = false;
  }
});

const filteredRecordList = computed(() => {
  const queryLower = searchQueryText.value.toLowerCase().trim();
  const list = activeRecordsList.value || [];
  if (!queryLower) return list;
  return list.filter((record) =>
    record.requesterFullName?.toLowerCase().includes(queryLower) ||
    record.requestIdentifier?.toString().includes(queryLower)
  );
});

function getTypeBadgeClass(requestType) {
  const typeLower = String(requestType || '').toLowerCase();
  if (typeLower === 'venue') return 'borrower-sublist-type-badge--venue';
  if (typeLower === 'equipment') return 'borrower-sublist-type-badge--equipment';
  return 'borrower-sublist-type-badge--both';
}

function getStatusBadgeClass(status) {
  const normalizedStatus = String(status || '').trim().toLowerCase();
  if (normalizedStatus === 'deployed' || normalizedStatus === 'active') {
    return 'borrower-sublist-status-badge--active';
  }

  if (normalizedStatus === 'prepared' || normalizedStatus === 'approved') {
    return 'borrower-sublist-status-badge--approved';
  }

  return 'borrower-sublist-status-badge--active';
}

function formatQuantity(value) {
  const count = Number(value);
  return Number.isFinite(count) && count > 0 ? count : 'N/A';
}

function navigateBackToMyReservations() {
  router.push({ name: ROUTE_NAMES.borrowerMyReservations });
}
</script>
