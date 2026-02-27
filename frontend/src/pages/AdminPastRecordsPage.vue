<!-- ===== AI GENERATED: AdminPastRecordsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <!-- Page Heading -->
    <h2 class="admin-past-records-page-heading">Past Records</h2>

    <!-- Tabs: All / Completed / Rejected / Cancelled -->
    <div class="admin-past-records-tabs-row">
      <button
        class="admin-past-records-tab-button"
        :class="{ 'admin-past-records-tab-button--active': activeRecordTab === 'all' }"
        @click="activeRecordTab = 'all'"
      >
        All
      </button>
      <button
        class="admin-past-records-tab-button"
        :class="{ 'admin-past-records-tab-button--active': activeRecordTab === 'completed' }"
        @click="activeRecordTab = 'completed'"
      >
        Completed
      </button>
      <button
        class="admin-past-records-tab-button"
        :class="{ 'admin-past-records-tab-button--active': activeRecordTab === 'rejected' }"
        @click="activeRecordTab = 'rejected'"
      >
        Rejected
      </button>
      <button
        class="admin-past-records-tab-button"
        :class="{ 'admin-past-records-tab-button--active': activeRecordTab === 'cancelled' }"
        @click="activeRecordTab = 'cancelled'"
      >
        Cancelled
      </button>
    </div>

    <!-- Toolbar -->
    <div class="admin-past-records-toolbar">
      <div class="admin-past-records-search-group">
        <label class="admin-past-records-search-label" for="adminPastRecordsSearch">Search:</label>
        <input
          id="adminPastRecordsSearch"
          v-model="searchQueryText"
          type="text"
          class="admin-past-records-search-input"
          placeholder="Name"
        />
      </div>
      <div class="admin-past-records-showing-group">
        <label class="admin-past-records-showing-label" for="adminPastRecordsShowing">Showing:</label>
        <select id="adminPastRecordsShowing" v-model="showingFilterValue" class="admin-past-records-showing-select">
          <option value="all">All</option>
        </select>
        <button class="admin-past-records-sort-button" @click="sortOrderAscending = !sortOrderAscending" aria-label="Sort">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19" /><polyline points="19 12 12 19 5 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="admin-past-records-table-wrapper">
      <table class="admin-past-records-table">
        <thead>
          <tr class="admin-past-records-table-header-row">
            <th class="admin-past-records-table-header-cell">ID</th>
            <th class="admin-past-records-table-header-cell">Name</th>
            <th class="admin-past-records-table-header-cell">Role</th>
            <th class="admin-past-records-table-header-cell">Schedule</th>
            <th class="admin-past-records-table-header-cell">Facility</th>
            <th class="admin-past-records-table-header-cell">Quantity</th>
            <th class="admin-past-records-table-header-cell">Type</th>
            <th class="admin-past-records-table-header-cell">Purpose</th>
            <th class="admin-past-records-table-header-cell">Date Processed</th>
            <th class="admin-past-records-table-header-cell">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="record in filteredRecordList"
            :key="record.requestIdentifier"
            class="admin-past-records-table-body-row"
          >
            <td class="admin-past-records-table-cell admin-past-records-table-cell--id">{{ record.requestIdentifier }}</td>
            <td class="admin-past-records-table-cell">{{ record.requesterFullName }}</td>
            <td class="admin-past-records-table-cell">{{ record.requesterRole }}</td>
            <td class="admin-past-records-table-cell">
              <div class="admin-past-records-schedule">
                <span class="admin-past-records-schedule-label">Requested:</span>
                <span>{{ record.requestedDate }}</span>
              </div>
              <div class="admin-past-records-schedule">
                <span class="admin-past-records-schedule-label">{{ record.recordStatus === 'Rejected' ? 'Needed:' : 'Needed:' }}</span>
                <span>{{ record.neededDate }}</span>
              </div>
            </td>
            <td class="admin-past-records-table-cell">
              <div class="admin-past-records-facility">
                <img
                  :src="record.facilityImage"
                  :alt="record.facilityName"
                  class="admin-past-records-facility-img"
                />
                <span>{{ record.facilityName }}</span>
              </div>
            </td>
            <td class="admin-past-records-table-cell">{{ record.requestQuantity }}</td>
            <td class="admin-past-records-table-cell">{{ record.requestType }}</td>
            <td class="admin-past-records-table-cell">{{ record.requestPurpose }}</td>
            <td class="admin-past-records-table-cell">{{ record.dateProcessed }}</td>
            <td class="admin-past-records-table-cell">
              <span class="admin-past-records-status-badge" :class="getStatusBadgeClass(record.recordStatus)">{{ record.recordStatus }}</span>
            </td>
          </tr>
          <tr v-if="filteredRecordList.length === 0">
            <td colspan="10" class="admin-past-records-table-cell admin-past-records-table-empty-row">No past records found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Footer -->
    <div class="admin-past-records-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/adminPastRecordsPage.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { useRequestStore } from '@/modules/request/store/requestStore.js';

const requestStore = useRequestStore();
const activeRecordTab = ref('all');
const searchQueryText = ref('');
const showingFilterValue = ref('all');
const sortOrderAscending = ref(true);

/**
 * @function filteredRecordList
 * @description Filters past records by active tab and search query.
 * @returns {Array<Object>}
 */
const filteredRecordList = computed(() => {
  let recordsFiltered = requestStore.pastRecordsList;

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
 * @function getStatusBadgeClass
 * @description Returns CSS class for status badge.
 * @param {string} recordStatus
 * @returns {string}
 */
function getStatusBadgeClass(recordStatus) {
  const statusLower = recordStatus.toLowerCase();
  if (statusLower === 'completed') return 'admin-past-records-status-badge--completed';
  if (statusLower === 'rejected') return 'admin-past-records-status-badge--rejected';
  if (statusLower === 'cancelled') return 'admin-past-records-status-badge--cancelled';
  return '';
}
</script>
