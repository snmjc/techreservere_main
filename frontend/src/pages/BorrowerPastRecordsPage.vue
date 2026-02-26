<!-- ===== AI GENERATED: BorrowerPastRecordsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'DELA CRUZ, JUAN'"
    :navigation-items="borrowerNavigationItems"
  >
    <!-- Page Heading -->
    <h2 class="past-records-page-heading">Past Records</h2>

    <!-- Tabs: All / Completed / Rejected / Cancelled -->
    <div class="past-records-tabs-row">
      <button
        class="past-records-tab-button"
        :class="{ 'past-records-tab-button--active': activeRecordTab === 'all' }"
        @click="activeRecordTab = 'all'"
      >
        All
      </button>
      <button
        class="past-records-tab-button"
        :class="{ 'past-records-tab-button--active': activeRecordTab === 'completed' }"
        @click="activeRecordTab = 'completed'"
      >
        Completed
      </button>
      <button
        class="past-records-tab-button"
        :class="{ 'past-records-tab-button--active': activeRecordTab === 'rejected' }"
        @click="activeRecordTab = 'rejected'"
      >
        Rejected
      </button>
      <button
        class="past-records-tab-button"
        :class="{ 'past-records-tab-button--active': activeRecordTab === 'cancelled' }"
        @click="activeRecordTab = 'cancelled'"
      >
        Cancelled
      </button>
    </div>

    <!-- Toolbar -->
    <div class="past-records-toolbar">
      <div class="past-records-search-group">
        <label class="past-records-search-label" for="pastRecordsSearch">Search:</label>
        <input
          id="pastRecordsSearch"
          v-model="searchQueryText"
          type="text"
          class="past-records-search-input"
          placeholder="Name"
        />
      </div>
      <div class="past-records-showing-group">
        <label class="past-records-showing-label" for="pastRecordsShowing">Showing:</label>
        <select id="pastRecordsShowing" v-model="showingFilterValue" class="past-records-showing-select">
          <option value="all">All</option>
        </select>
        <button class="past-records-sort-button" aria-label="Sort">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19" /><polyline points="19 12 12 19 5 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="past-records-table-wrapper">
      <table class="past-records-table">
        <thead>
          <tr class="past-records-table-header-row">
            <th class="past-records-table-header-cell">ID</th>
            <th class="past-records-table-header-cell">Name</th>
            <th class="past-records-table-header-cell">Role</th>
            <th class="past-records-table-header-cell">Schedule</th>
            <th class="past-records-table-header-cell">Facility</th>
            <th class="past-records-table-header-cell">Quantity</th>
            <th class="past-records-table-header-cell">Type</th>
            <th class="past-records-table-header-cell">Purpose</th>
            <th class="past-records-table-header-cell">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="record in filteredRecordList"
            :key="record.requestIdentifier + record.recordStatus"
            class="past-records-table-body-row"
          >
            <td class="past-records-table-cell past-records-table-cell--id">{{ record.requestIdentifier }}</td>
            <td class="past-records-table-cell">{{ record.requesterFullName }}</td>
            <td class="past-records-table-cell">{{ record.requesterRole }}</td>
            <td class="past-records-table-cell">{{ record.requestSchedule }}</td>
            <td class="past-records-table-cell">{{ record.facilityName }}</td>
            <td class="past-records-table-cell">{{ record.requestQuantity }}</td>
            <td class="past-records-table-cell">
              <span class="past-records-type-badge" :class="getTypeBadgeClass(record.requestType)">{{ record.requestType }}</span>
            </td>
            <td class="past-records-table-cell">{{ record.requestPurpose }}</td>
            <td class="past-records-table-cell">
              <span class="past-records-status-badge" :class="getStatusBadgeClass(record.recordStatus)">{{ record.recordStatus }}</span>
            </td>
          </tr>
          <tr v-if="filteredRecordList.length === 0">
            <td colspan="9" class="past-records-table-cell past-records-table-empty-row">No past records found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Footer -->
    <div class="past-records-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/borrowerPastRecordsPage.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';

const activeRecordTab = ref('all');
const searchQueryText = ref('');
const showingFilterValue = ref('all');

/**
 * @constant {Array<Object>} pastRecordsList
 * @description Static past records data for demonstration.
 */
const pastRecordsList = ref([
  {
    requestIdentifier: 59327,
    requesterFullName: 'Juan Dela Cruz',
    requesterRole: 'Student',
    requestSchedule: 'March 08, 2026 15:00-16:50',
    facilityName: 'F704',
    requestQuantity: 'N/A',
    requestType: 'Venue',
    requestPurpose: 'Class',
    recordStatus: 'Completed',
  },
  {
    requestIdentifier: 59310,
    requesterFullName: 'Juan Dela Cruz',
    requesterRole: 'Student',
    requestSchedule: 'Feb 20, 2026 09:00-11:00',
    facilityName: 'F503',
    requestQuantity: 'N/A',
    requestType: 'Venue',
    requestPurpose: 'Meeting',
    recordStatus: 'Rejected',
  },
  {
    requestIdentifier: 59295,
    requesterFullName: 'Juan Dela Cruz',
    requesterRole: 'Student',
    requestSchedule: 'Feb 15, 2026 13:00-14:30',
    facilityName: '8F Exec. Lounge 1',
    requestQuantity: '5',
    requestType: 'Equipment',
    requestPurpose: 'Event',
    recordStatus: 'Cancelled',
  },
]);

/**
 * @function filteredRecordList
 * @description Filters past records by active tab and search query.
 * @returns {Array<Object>}
 */
const filteredRecordList = computed(() => {
  let recordsFiltered = pastRecordsList.value;

  if (activeRecordTab.value !== 'all') {
    const tabStatusMap = {
      completed: 'Completed',
      rejected: 'Rejected',
      cancelled: 'Cancelled',
    };
    recordsFiltered = recordsFiltered.filter(
      (record) => record.recordStatus === tabStatusMap[activeRecordTab.value]
    );
  }

  const queryLower = searchQueryText.value.toLowerCase().trim();
  if (queryLower) {
    recordsFiltered = recordsFiltered.filter(
      (record) =>
        record.requesterFullName.toLowerCase().includes(queryLower) ||
        record.requestIdentifier.toString().includes(queryLower)
    );
  }

  return recordsFiltered;
});

/**
 * @function getTypeBadgeClass
 * @description Returns CSS class for type badge.
 * @param {string} requestType
 * @returns {string}
 */
function getTypeBadgeClass(requestType) {
  const typeLower = requestType.toLowerCase();
  if (typeLower === 'venue') return 'past-records-type-badge--venue';
  if (typeLower === 'equipment') return 'past-records-type-badge--equipment';
  if (typeLower === 'both') return 'past-records-type-badge--both';
  return '';
}

/**
 * @function getStatusBadgeClass
 * @description Returns CSS class for status badge.
 * @param {string} recordStatus
 * @returns {string}
 */
function getStatusBadgeClass(recordStatus) {
  const statusLower = recordStatus.toLowerCase();
  if (statusLower === 'completed') return 'past-records-status-badge--completed';
  if (statusLower === 'rejected') return 'past-records-status-badge--rejected';
  if (statusLower === 'cancelled') return 'past-records-status-badge--cancelled';
  return '';
}
</script>
