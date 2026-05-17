<!-- ===== AI GENERATED: BorrowerPendingRequestsLogsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'BORROWER'"
    :navigation-items="borrowerNavigationItems"
  >
    <!-- Page Header with Go Back Button -->
    <div class="logs-page-header">
      <h2 class="logs-page-heading">Pending Requests Logs</h2>
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
            <th class="logs-th">Submitted</th>
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
              <span class="logs-status-badge logs-status-badge--pending">
                {{ log.status }}
              </span>
            </td>
            <td class="logs-td">{{ log.submitted }}</td>
          </tr>
        </tbody>
      </table>

      <!-- No Results Message -->
      <div v-if="filteredLogs.length === 0" class="logs-no-results">
        No pending request logs found matching your search.
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

const router = useRouter();
const searchQuery = ref('');
const sortBy = ref('date');
const sortOrder = ref('asc');

const mockLogs = ref([
  {
    id: 1,
    reservationId: 'RES-2026-016',
    name: 'Thomas Anderson',
    role: 'Student',
    date: '2026-05-30 9:00 AM',
    facility: 'Grand Ballroom',
    facilityImage: 'https://via.placeholder.com/50?text=Ballroom',
    type: 'Venue',
    purpose: 'Student Organization - Annual Gala Event',
    status: 'Pending',
    submitted: '2026-05-28 3:45 PM'
  },
  {
    id: 2,
    reservationId: 'RES-2026-017',
    name: 'Jessica White',
    role: 'Faculty',
    date: '2026-06-01 2:00 PM',
    facility: 'Outdoor Pavilion',
    facilityImage: 'https://via.placeholder.com/50?text=Pavilion',
    type: 'Venue',
    purpose: 'Department Picnic - Team Building Event',
    status: 'Pending',
    submitted: '2026-05-29 10:15 AM'
  },
  {
    id: 3,
    reservationId: 'RES-2026-018',
    name: 'Mark Johnson',
    role: 'Student',
    date: '2026-06-03 11:00 AM',
    facility: 'Computer Lab A',
    facilityImage: 'https://via.placeholder.com/50?text=Lab',
    type: 'Venue',
    purpose: 'Workshop - Python Programming Basics',
    status: 'Pending',
    submitted: '2026-05-29 2:30 PM'
  },
  {
    id: 4,
    reservationId: 'RES-2026-019',
    name: 'Laura Davis',
    role: 'Student',
    date: '2026-06-05 3:30 PM',
    facility: 'Theater Auditorium',
    facilityImage: 'https://via.placeholder.com/50?text=Theater',
    type: 'Venue',
    purpose: 'Drama Club - Spring Production Performance',
    status: 'Pending',
    submitted: '2026-05-30 9:00 AM'
  },
  {
    id: 5,
    reservationId: 'RES-2026-020',
    name: 'Steven Taylor',
    role: 'Student',
    date: '2026-06-07 1:00 PM',
    facility: 'Science Lab B',
    facilityImage: 'https://via.placeholder.com/50?text=Science',
    type: 'Venue',
    purpose: 'Research Project - Chemistry Experiment',
    status: 'Pending',
    submitted: '2026-05-30 11:45 AM'
  },
  {
    id: 6,
    reservationId: 'RES-2026-021',
    name: 'Nicole Harris',
    role: 'Student',
    date: '2026-06-09 10:00 AM',
    facility: 'Art Studio',
    facilityImage: 'https://via.placeholder.com/50?text=Art+Studio',
    type: 'Venue',
    purpose: 'Art Exhibition - Student Artwork Display',
    status: 'Pending',
    submitted: '2026-05-31 1:20 PM'
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
