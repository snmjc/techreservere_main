<!-- ===== AI GENERATED: BorrowerActiveReservationsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'DELA CRUZ, JUAN'"
    :navigation-items="borrowerNavigationItems"
  >
    <!-- Page Header -->
    <div class="borrower-sublist-page-header">
      <h2 class="borrower-sublist-page-heading">Active Reservations</h2>
      <span class="borrower-sublist-go-back-link" @click="navigateBackToMyReservations">Go Back</span>
    </div>

    <!-- Toolbar -->
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
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--id">{{ record.requestIdentifier }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--name">{{ record.requesterFullName }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--role">{{ record.requesterRole }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--schedule">{{ record.requestSchedule }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--facility">{{ record.facilityName }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--quantity">{{ record.requestQuantity }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--type">
              <span class="borrower-sublist-type-badge" :class="getTypeBadgeClass(record.requestType)">{{ record.requestType }}</span>
            </td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--purpose">{{ record.requestPurpose }}</td>
            <td class="borrower-sublist-table-cell borrower-sublist-table-cell--status">
              <span class="borrower-sublist-status-badge borrower-sublist-status-badge--active">Active</span>
            </td>
          </tr>
          <tr v-if="filteredRecordList.length === 0">
            <td colspan="9" class="borrower-sublist-table-cell borrower-sublist-table-empty-row">No active reservations.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Footer -->
    <div class="borrower-sublist-page-footer">&copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.</div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/borrowerSubListPage.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';

const router = useRouter();
const searchQueryText = ref('');
const showingFilterValue = ref('all');

const activeRecordsList = ref([
  {
    requestIdentifier: 59327,
    requesterFullName: 'Juan Dela Cruz',
    requesterRole: 'Student',
    requestSchedule: 'Reserved: March 08, 2026 15:00-16:50',
    facilityName: 'F309',
    requestQuantity: 'N/A',
    requestType: 'Venue',
    requestPurpose: 'Class',
  },
]);

/**
 * @function filteredRecordList
 * @description Filters list by search query.
 * @returns {Array<Object>}
 */
const filteredRecordList = computed(() => {
  const queryLower = searchQueryText.value.toLowerCase().trim();
  if (!queryLower) return activeRecordsList.value;
  return activeRecordsList.value.filter((record) =>
    record.requesterFullName.toLowerCase().includes(queryLower) ||
    record.requestIdentifier.toString().includes(queryLower)
  );
});

/**
 * @function getTypeBadgeClass
 * @description Returns CSS class for type badge.
 * @param {string} requestType
 * @returns {string}
 */
function getTypeBadgeClass(requestType) {
  const typeLower = requestType.toLowerCase();
  if (typeLower === 'venue') return 'borrower-sublist-type-badge--venue';
  if (typeLower === 'equipment') return 'borrower-sublist-type-badge--equipment';
  if (typeLower === 'both') return 'borrower-sublist-type-badge--both';
  return '';
}

/**
 * @function navigateBackToMyReservations
 * @description Navigates back to My Reservations page.
 * @returns {void}
 */
function navigateBackToMyReservations() {
  router.push({ name: 'borrowerMyReservationsPage' });
}
</script>
