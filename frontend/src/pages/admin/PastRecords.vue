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
import { ref, computed, onMounted } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/PastRecords.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { useRequestStore } from '@/modules/request/store/requestStore.js';

const requestStore = useRequestStore();
const activeRecordTab = ref('all');
const searchQueryText = ref('');
const showingFilterValue = ref('all');
const sortOrderAscending = ref(true);

// Mock past records data
const mockPastRecords = ref([
  {
    requestIdentifier: 'RES-001',
    requesterFullName: 'Maria Santos',
    requesterRole: 'Faculty',
    requestedDate: '2026-04-15',
    neededDate: '2026-05-10',
    facilityName: '18F Roofdeck',
    facilityImage: 'https://via.placeholder.com/80?text=Roofdeck',
    requestQuantity: 150,
    requestType: 'Venue',
    requestPurpose: 'Graduation Ceremony',
    dateProcessed: '2026-05-10',
    recordStatus: 'Completed',
  },
  {
    requestIdentifier: 'RES-002',
    requesterFullName: 'Juan Dela Cruz',
    requesterRole: 'Staff',
    requestedDate: '2026-04-20',
    neededDate: '2026-05-05',
    facilityName: 'Chairs',
    facilityImage: 'https://via.placeholder.com/80?text=Chairs',
    requestQuantity: 200,
    requestType: 'Equipment',
    requestPurpose: 'Conference Setup',
    dateProcessed: '2026-05-05',
    recordStatus: 'Completed',
  },
  {
    requestIdentifier: 'RES-003',
    requesterFullName: 'Ana Garcia',
    requesterRole: 'Student',
    requestedDate: '2026-04-10',
    neededDate: '2026-04-25',
    facilityName: 'F407',
    facilityImage: 'https://via.placeholder.com/80?text=F407',
    requestQuantity: 50,
    requestType: 'Venue',
    requestPurpose: 'Club Meeting',
    dateProcessed: '2026-04-25',
    recordStatus: 'Rejected',
  },
  {
    requestIdentifier: 'RES-004',
    requesterFullName: 'Pedro Reyes',
    requesterRole: 'Faculty',
    requestedDate: '2026-04-12',
    neededDate: '2026-05-01',
    facilityName: 'Microphone & Podium',
    facilityImage: 'https://via.placeholder.com/80?text=Microphone',
    requestQuantity: 5,
    requestType: 'Equipment',
    requestPurpose: 'Seminar',
    dateProcessed: '2026-05-01',
    recordStatus: 'Completed',
  },
  {
    requestIdentifier: 'RES-005',
    requesterFullName: 'Rosa Mendoza',
    requesterRole: 'Student',
    requestedDate: '2026-04-18',
    neededDate: '2026-05-08',
    facilityName: 'F503 & Tables',
    facilityImage: 'https://via.placeholder.com/80?text=F503',
    requestQuantity: 80,
    requestType: 'Venue and Equipment',
    requestPurpose: 'Workshop',
    dateProcessed: '2026-05-08',
    recordStatus: 'Completed',
  },
  {
    requestIdentifier: 'RES-006',
    requesterFullName: 'Carlos Lopez',
    requesterRole: 'Staff',
    requestedDate: '2026-04-22',
    neededDate: '2026-05-02',
    facilityName: 'LED Video Wall',
    facilityImage: 'https://via.placeholder.com/80?text=LED',
    requestQuantity: 1,
    requestType: 'Equipment',
    requestPurpose: 'Presentation',
    dateProcessed: '2026-05-02',
    recordStatus: 'Cancelled',
  },
  {
    requestIdentifier: 'RES-007',
    requesterFullName: 'Lisa Wong',
    requesterRole: 'Faculty',
    requestedDate: '2026-04-08',
    neededDate: '2026-04-28',
    facilityName: 'F608',
    facilityImage: 'https://via.placeholder.com/80?text=F608',
    requestQuantity: 60,
    requestType: 'Venue',
    requestPurpose: 'Exam',
    dateProcessed: '2026-04-28',
    recordStatus: 'Rejected',
  },
  {
    requestIdentifier: 'RES-008',
    requesterFullName: 'Miguel Torres',
    requesterRole: 'Student',
    requestedDate: '2026-04-25',
    neededDate: '2026-05-09',
    facilityName: 'Stage & Sound System',
    facilityImage: 'https://via.placeholder.com/80?text=Stage',
    requestQuantity: 1,
    requestType: 'Equipment',
    requestPurpose: 'Concert',
    dateProcessed: '2026-05-09',
    recordStatus: 'Completed',
  },
  {
    requestIdentifier: 'RES-009',
    requesterFullName: 'Sofia Gutierrez',
    requesterRole: 'Staff',
    requestedDate: '2026-04-16',
    neededDate: '2026-05-03',
    facilityName: 'F704',
    facilityImage: 'https://via.placeholder.com/80?text=F704',
    requestQuantity: 40,
    requestType: 'Venue',
    requestPurpose: 'Training',
    dateProcessed: '2026-05-03',
    recordStatus: 'Completed',
  },
  {
    requestIdentifier: 'RES-010',
    requesterFullName: 'Antonio Morales',
    requesterRole: 'Faculty',
    requestedDate: '2026-04-19',
    neededDate: '2026-05-06',
    facilityName: 'Chairs & Tables',
    facilityImage: 'https://via.placeholder.com/80?text=Furniture',
    requestQuantity: 120,
    requestType: 'Equipment',
    requestPurpose: 'Banquet',
    dateProcessed: '2026-05-06',
    recordStatus: 'Cancelled',
  },
]);

onMounted(async () => {
  try {
    await requestStore.fetchReservations();
    const list = requestStore.pastRecordsList || [];
    console.log('Admin Past Records - Count:', list.length);
  } catch (error) {
    console.error('Error fetching past records:', error);
  }
});

/**
 * @function filteredRecordList
 * @description Filters past records by active tab, search query, and applies sorting.
 * @returns {Array<Object>}
 */
const filteredRecordList = computed(() => {
  let recordsFiltered = mockPastRecords.value;

  // Filter by tab status
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
        record.requesterFullName?.toLowerCase().includes(queryLower) ||
        record.requestIdentifier?.toString().includes(queryLower) ||
        record.facilityName?.toLowerCase().includes(queryLower)
    );
  }

  // Apply sorting by name
  recordsFiltered.sort((a, b) => {
    const nameA = a.requesterFullName.toLowerCase();
    const nameB = b.requesterFullName.toLowerCase();
    if (sortOrderAscending.value) {
      return nameA.localeCompare(nameB);
    } else {
      return nameB.localeCompare(nameA);
    }
  });

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
