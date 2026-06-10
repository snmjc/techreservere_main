<!-- ===== AI GENERATED: BorrowerApprovedRequestsLogsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'BORROWER'"
    :navigation-items="borrowerNavigationItems"
  >
    <!-- Page Header with Go Back Button -->
    <div class="logs-page-header">
      <h2 class="logs-page-heading">Approved Requests Logs</h2>
      <button class="logs-go-back-button" @click="handleGoBack">
        ← Go Back
      </button>
    </div>

    <!-- Search and Sorting Controls -->
    <div class="logs-controls">
      <div class="logs-search-group">
        <label class="logs-search-label" for="logsSearch">Search:</label>
        <input
          id="logsSearch"
          v-model="searchQuery"
          type="text"
          class="logs-search-input"
          placeholder="Reservation ID or Name"
        />
      </div>

      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <div class="logs-sort-group">
          <label class="logs-sort-label" for="logsSort">Sort By:</label>
          <select
            id="logsSort"
            v-model="sortBy"
            class="logs-sort-select"
          >
            <option value="date">Date</option>
            <option value="name">Name</option>
            <option value="facility">Facility</option>
          </select>
        </div>

        <button
          class="logs-sort-toggle"
          @click="toggleSortOrder"
          :title="sortOrder === 'asc' ? 'Sort Descending' : 'Sort Ascending'"
        >
          {{ sortOrder === 'asc' ? '↑' : '↓' }}
        </button>
      </div>
    </div>

    <!-- Logs Table -->
    <div class="logs-table-wrapper">
      <table class="logs-table">
        <thead>
          <tr>
            <th class="logs-th">Reservation ID</th>
            <th class="logs-th">Name</th>
            <th class="logs-th">Role</th>
            <th class="logs-th">Schedule</th>
            <th class="logs-th">Facility</th>
            <th class="logs-th">Type</th>
            <th class="logs-th">Purpose</th>
            <th class="logs-th">Status</th>
            <th class="logs-th">Approved By</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in filteredLogs" :key="log.id" class="logs-tr">
            <td class="logs-td">{{ log.reservationId }}</td>
            <td class="logs-td">{{ log.name }}</td>
            <td class="logs-td">
              <span class="logs-badge" :class="{ 'logs-badge--student': log.role === 'Student', 'logs-badge--faculty': log.role === 'Faculty' }">
                {{ log.role }}
              </span>
            </td>
            <td class="logs-td">{{ log.date }}</td>
            <td class="logs-td">
              <div class="logs-facility">
                <img v-if="log.facilityImage" :src="log.facilityImage" :alt="log.facility" class="logs-facility-image" />
                <span>{{ log.facility }}</span>
              </div>
            </td>
            <td class="logs-td">
              <span class="logs-badge" :class="{ 'logs-badge--venue': log.type === 'Venue', 'logs-badge--equipment': log.type === 'Equipment' }">
                {{ log.type }}
              </span>
            </td>
            <td class="logs-td">{{ log.purpose }}</td>
            <td class="logs-td">
              <span class="logs-status-badge logs-status-badge--approved">
                {{ log.status }}
              </span>
            </td>
            <td class="logs-td">{{ log.approvedBy }}</td>
          </tr>
        </tbody>
      </table>

      <!-- No Results Message -->
      <div v-if="filteredLogs.length === 0" class="logs-no-results">
        No approved request logs found matching your search.
      </div>
    </div>

    <!-- Footer -->
    <div class="logs-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/Logs.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { createTextPlaceholderDataUrl } from '@/shared/utils/mockImage.js';

const router = useRouter();
const searchQuery = ref('');
const sortBy = ref('date');
const sortOrder = ref('asc');

const mockLogs = ref([
  {
    id: 1,
    reservationId: 'RES-2026-002',
    name: 'Maria Garcia',
    role: 'Faculty',
    date: '2026-05-16 2:00 PM',
    facility: 'Conference Room A',
    facilityImage: createTextPlaceholderDataUrl('Conference', { width: 100, height: 50, fontSize: 14 }),
    type: 'Venue',
    purpose: 'Department Meeting - Academic Planning',
    status: 'Approved',
    approvedBy: 'Dr. James Wilson'
  },
  {
    id: 2,
    reservationId: 'RES-2026-004',
    name: 'Sarah Williams',
    role: 'Faculty',
    date: '2026-05-18 9:00 AM',
    facility: 'Lecture Hall B',
    facilityImage: createTextPlaceholderDataUrl('Lecture', { width: 100, height: 50, fontSize: 14 }),
    type: 'Venue',
    purpose: 'Lecture - Advanced Database Systems',
    status: 'Approved',
    approvedBy: 'Prof. Lisa Anderson'
  },
  {
    id: 3,
    reservationId: 'RES-2026-006',
    name: 'Jennifer Lee',
    role: 'Faculty',
    date: '2026-05-20 11:00 AM',
    facility: 'Meeting Room C',
    facilityImage: createTextPlaceholderDataUrl('Meeting', { width: 100, height: 50, fontSize: 14 }),
    type: 'Venue',
    purpose: 'Advisory Session - Student Mentoring',
    status: 'Approved',
    approvedBy: 'Dr. Robert Martinez'
  },
  {
    id: 4,
    reservationId: 'RES-2026-008',
    name: 'Amanda Brown',
    role: 'Faculty',
    date: '2026-05-22 2:30 PM',
    facility: 'Seminar Room',
    facilityImage: createTextPlaceholderDataUrl('Seminar', { width: 100, height: 50, fontSize: 14 }),
    type: 'Venue',
    purpose: 'Seminar - Research Methodology Workshop',
    status: 'Approved',
    approvedBy: 'Prof. Michael Chen'
  },
  {
    id: 5,
    reservationId: 'RES-2026-010',
    name: 'Patricia Anderson',
    role: 'Faculty',
    date: '2026-05-24 1:30 PM',
    facility: 'Board Room',
    facilityImage: createTextPlaceholderDataUrl('Board Room', { width: 100, height: 50, fontSize: 13 }),
    type: 'Venue',
    purpose: 'Faculty Meeting - Curriculum Review',
    status: 'Approved',
    approvedBy: 'Dr. Sarah Johnson'
  },
  {
    id: 6,
    reservationId: 'RES-2026-012',
    name: 'Elizabeth Martinez',
    role: 'Faculty',
    date: '2026-05-26 10:00 AM',
    facility: 'Training Room A',
    facilityImage: createTextPlaceholderDataUrl('Training', { width: 100, height: 50, fontSize: 14 }),
    type: 'Venue',
    purpose: 'Training Session - Software Development Tools',
    status: 'Approved',
    approvedBy: 'Prof. David Lee'
  },
  {
    id: 7,
    reservationId: 'RES-2026-014',
    name: 'Rachel Green',
    role: 'Faculty',
    date: '2026-05-28 11:30 AM',
    facility: 'Discussion Room',
    facilityImage: createTextPlaceholderDataUrl('Discussion', { width: 100, height: 50, fontSize: 13 }),
    type: 'Venue',
    purpose: 'Class Discussion - Literature Analysis',
    status: 'Approved',
    approvedBy: 'Dr. Jennifer Brown'
  }
]);

const filteredLogs = computed(() => {
  let logs = [...mockLogs.value];

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    logs = logs.filter(log =>
      log.reservationId.toLowerCase().includes(query) ||
      log.name.toLowerCase().includes(query)
    );
  }

  // Apply sorting
  logs.sort((a, b) => {
    let compareA, compareB;

    if (sortBy.value === 'date') {
      compareA = new Date(a.date);
      compareB = new Date(b.date);
    } else if (sortBy.value === 'name') {
      compareA = a.name.toLowerCase();
      compareB = b.name.toLowerCase();
    } else if (sortBy.value === 'facility') {
      compareA = a.facility.toLowerCase();
      compareB = b.facility.toLowerCase();
    }

    if (typeof compareA === 'string' && typeof compareB === 'string') {
      return sortOrder.value === 'asc'
        ? compareA.localeCompare(compareB)
        : compareB.localeCompare(compareA);
    } else {
      return sortOrder.value === 'asc'
        ? compareA - compareB
        : compareB - compareA;
    }
  });

  return logs;
});

function handleGoBack() {
  router.back();
}

function toggleSortOrder() {
  sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
}
</script>
