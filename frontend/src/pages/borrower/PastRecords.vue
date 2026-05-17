<!-- ===== AI GENERATED: BorrowerPastRecordsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="userFullName"
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
          placeholder="Search by name or ID"
        />
      </div>
      <div class="past-records-showing-group">
        <label class="past-records-showing-label" for="pastRecordsOrdering">Order By:</label>
        <select id="pastRecordsOrdering" v-model="orderByValue" class="past-records-showing-select">
          <option value="date">Requested Date</option>
          <option value="name">Name</option>
          <option value="status">Status</option>
        </select>
        <label class="past-records-showing-label" for="pastRecordsShowing">Showing:</label>
        <select id="pastRecordsShowing" v-model="showingFilterValue" class="past-records-showing-select">
          <option value="all">All</option>
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
        </select>
        <button class="past-records-sort-button" @click="toggleSortOrder" :title="sortOrder === 'asc' ? 'Sort Descending' : 'Sort Ascending'" aria-label="Sort">
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
            <th class="past-records-table-header-cell">Requested Date</th>
            <th class="past-records-table-header-cell">Needed Date</th>
            <th class="past-records-table-header-cell">Facility</th>
            <th class="past-records-table-header-cell">Quantity</th>
            <th class="past-records-table-header-cell">Type</th>
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
            <td class="past-records-table-cell">{{ record.requestedDate }}</td>
            <td class="past-records-table-cell">{{ record.neededDate }}</td>
            <td class="past-records-table-cell">{{ record.facilityName }}</td>
            <td class="past-records-table-cell">{{ record.requestQuantity }}</td>
            <td class="past-records-table-cell">
              <span class="past-records-type-badge" :class="getTypeBadgeClass(record.requestType)">{{ record.requestType }}</span>
            </td>
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
import { ref, computed, onMounted } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore';
import '@/shared/components/adminSidebarLayout.css';
import './css/PastRecords.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';

const authStore = useAuthenticationStore();
const activeRecordTab = ref('all');
const searchQueryText = ref('');
const showingFilterValue = ref('all');
const sortOrder = ref('desc');
const orderByValue = ref('date');
const loading = ref(false);

const pastRecordsList = ref([]);

const userFullName = computed(() => authStore.userFullName || 'USER');

const mockPastRecords = [
  {
    requestIdentifier: 'RES-001',
    requesterFullName: 'Juan Dela Cruz',
    requesterRole: 'Student',
    requestedDate: '2024-05-01',
    neededDate: '2024-05-15',
    facilityName: 'Classroom A',
    requestQuantity: 1,
    requestType: 'Venue',
    recordStatus: 'Completed',
  },
  {
    requestIdentifier: 'RES-002',
    requesterFullName: 'Maria Santos',
    requesterRole: 'Faculty',
    requestedDate: '2024-04-28',
    neededDate: '2024-05-10',
    facilityName: 'Multipurpose Room',
    requestQuantity: 2,
    requestType: 'Equipment',
    recordStatus: 'Completed',
  },
  {
    requestIdentifier: 'RES-003',
    requesterFullName: 'Pedro Garcia',
    requesterRole: 'Student',
    requestedDate: '2024-04-20',
    neededDate: '2024-05-05',
    facilityName: 'Projector',
    requestQuantity: 1,
    requestType: 'Equipment',
    recordStatus: 'Rejected',
  },
  {
    requestIdentifier: 'RES-004',
    requesterFullName: 'Ana Reyes',
    requesterRole: 'Faculty',
    requestedDate: '2024-04-15',
    neededDate: '2024-04-25',
    facilityName: 'Classroom B',
    requestQuantity: 1,
    requestType: 'Venue',
    recordStatus: 'Cancelled',
  },
  {
    requestIdentifier: 'RES-005',
    requesterFullName: 'Carlos Mendoza',
    requesterRole: 'Student',
    requestedDate: '2024-04-10',
    neededDate: '2024-04-22',
    facilityName: 'Conference Room',
    requestQuantity: 3,
    requestType: 'Venue and Equipment',
    recordStatus: 'Completed',
  },
  {
    requestIdentifier: 'RES-006',
    requesterFullName: 'Rosa Flores',
    requesterRole: 'Faculty',
    requestedDate: '2024-04-05',
    neededDate: '2024-04-18',
    facilityName: 'LED Screen',
    requestQuantity: 2,
    requestType: 'Equipment',
    recordStatus: 'Rejected',
  },
  {
    requestIdentifier: 'RES-007',
    requesterFullName: 'Miguel Torres',
    requesterRole: 'Student',
    requestedDate: '2024-03-28',
    neededDate: '2024-04-10',
    facilityName: 'Auditorium',
    requestQuantity: 1,
    requestType: 'Venue',
    recordStatus: 'Completed',
  },
  {
    requestIdentifier: 'RES-008',
    requesterFullName: 'Sofia Ramirez',
    requesterRole: 'Faculty',
    requestedDate: '2024-03-20',
    neededDate: '2024-04-02',
    facilityName: 'Microphone Set',
    requestQuantity: 1,
    requestType: 'Equipment',
    recordStatus: 'Cancelled',
  },
];

onMounted(async () => {
  await loadPastRecords();
});

async function loadPastRecords() {
  loading.value = true;
  try {
    pastRecordsList.value = mockPastRecords;
  } catch (error) {
    console.error('Error loading past records:', error);
    pastRecordsList.value = mockPastRecords;
  } finally {
    loading.value = false;
  }
}

function toggleSortOrder() {
  sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
}

/**
 * @function filteredRecordList
 * @description Filters past records by active tab and search query.
 * @returns {Array<Object>}
 */
const filteredRecordList = computed(() => {
  let recordsFiltered = pastRecordsList.value || [];

  // Filter by status tab
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

  // Filter by search query
  const queryLower = searchQueryText.value.toLowerCase().trim();
  if (queryLower) {
    recordsFiltered = recordsFiltered.filter(
      (record) =>
        record.requesterFullName.toLowerCase().includes(queryLower) ||
        record.requestIdentifier.toLowerCase().includes(queryLower)
    );
  }

  // Sort by selected field
  recordsFiltered.sort((a, b) => {
    let compareA, compareB;

    if (orderByValue.value === 'date') {
      compareA = new Date(a.requestedDate);
      compareB = new Date(b.requestedDate);
    } else if (orderByValue.value === 'name') {
      compareA = a.requesterFullName.toLowerCase();
      compareB = b.requesterFullName.toLowerCase();
    } else if (orderByValue.value === 'status') {
      compareA = a.recordStatus.toLowerCase();
      compareB = b.recordStatus.toLowerCase();
    }

    if (typeof compareA === 'string' && typeof compareB === 'string') {
      return sortOrder.value === 'asc' 
        ? compareA.localeCompare(compareB)
        : compareB.localeCompare(compareA);
    } else {
      return sortOrder.value === 'asc' ? compareA - compareB : compareB - compareA;
    }
  });

  // Limit showing records
  if (showingFilterValue.value !== 'all') {
    const limit = parseInt(showingFilterValue.value);
    recordsFiltered = recordsFiltered.slice(0, limit);
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
  if (typeLower.includes('venue') && typeLower.includes('equipment')) return 'past-records-type-badge--both';
  if (typeLower === 'venue') return 'past-records-type-badge--venue';
  if (typeLower === 'equipment') return 'past-records-type-badge--equipment';
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
