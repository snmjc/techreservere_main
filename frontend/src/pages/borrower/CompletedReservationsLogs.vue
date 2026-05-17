<!-- ===== AI GENERATED: BorrowerCompletedReservationsLogsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'BORROWER'"
    :navigation-items="borrowerNavigationItems"
  >
    <!-- Page Header with Go Back Button -->
    <div class="logs-page-header">
      <h2 class="logs-page-heading">Completed Reservations Logs</h2>
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
            <th class="logs-th">Completed</th>
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
              <span class="logs-status-badge logs-status-badge--completed">
                {{ log.status }}
              </span>
            </td>
            <td class="logs-td">{{ log.completed }}</td>
          </tr>
        </tbody>
      </table>

      <!-- No Results Message -->
      <div v-if="filteredLogs.length === 0" class="logs-no-results">
        No completed reservation logs found matching your search.
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
    reservationId: 'RES-2026-101',
    name: 'Alice Thompson',
    role: 'Faculty',
    date: '2026-04-10 9:00 AM',
    facility: 'Lecture Hall A',
    facilityImage: 'https://via.placeholder.com/50?text=Lecture',
    type: 'Venue',
    purpose: 'Lecture - Introduction to Computer Science',
    status: 'Completed',
    completed: '2026-04-10 11:30 AM'
  },
  {
    id: 2,
    reservationId: 'RES-2026-102',
    name: 'Brian Martinez',
    role: 'Student',
    date: '2026-04-12 2:00 PM',
    facility: 'Conference Room B',
    facilityImage: 'https://via.placeholder.com/50?text=Conference',
    type: 'Venue',
    purpose: 'Team Meeting - Project Kickoff',
    status: 'Completed',
    completed: '2026-04-12 4:30 PM'
  },
  {
    id: 3,
    reservationId: 'RES-2026-103',
    name: 'Catherine Lee',
    role: 'Faculty',
    date: '2026-04-15 10:00 AM',
    facility: 'Seminar Room',
    facilityImage: 'https://via.placeholder.com/50?text=Seminar',
    type: 'Venue',
    purpose: 'Workshop - Professional Development',
    status: 'Completed',
    completed: '2026-04-15 1:00 PM'
  },
  {
    id: 4,
    reservationId: 'RES-2026-104',
    name: 'Daniel Wilson',
    role: 'Student',
    date: '2026-04-18 3:00 PM',
    facility: 'Training Room A',
    facilityImage: 'https://via.placeholder.com/50?text=Training',
    type: 'Venue',
    purpose: 'Training - New Software Implementation',
    status: 'Completed',
    completed: '2026-04-18 5:30 PM'
  },
  {
    id: 5,
    reservationId: 'RES-2026-105',
    name: 'Emma Davis',
    role: 'Faculty',
    date: '2026-04-20 11:00 AM',
    facility: 'Board Room',
    facilityImage: 'https://via.placeholder.com/50?text=Board+Room',
    type: 'Venue',
    purpose: 'Board Meeting - Quarterly Review',
    status: 'Completed',
    completed: '2026-04-20 1:30 PM'
  },
  {
    id: 6,
    reservationId: 'RES-2026-106',
    name: 'Frank Johnson',
    role: 'Student',
    date: '2026-04-22 2:00 PM',
    facility: 'Meeting Room C',
    facilityImage: 'https://via.placeholder.com/50?text=Meeting',
    type: 'Venue',
    purpose: 'Client Presentation - Sales Pitch',
    status: 'Completed',
    completed: '2026-04-22 4:00 PM'
  },
  {
    id: 7,
    reservationId: 'RES-2026-107',
    name: 'Grace Anderson',
    role: 'Faculty',
    date: '2026-04-25 9:30 AM',
    facility: 'Discussion Room',
    facilityImage: 'https://via.placeholder.com/50?text=Discussion',
    type: 'Venue',
    purpose: 'Focus Group - User Research',
    status: 'Completed',
    completed: '2026-04-25 12:00 PM'
  },
  {
    id: 8,
    reservationId: 'RES-2026-108',
    name: 'Henry Brown',
    role: 'Student',
    date: '2026-04-28 1:00 PM',
    facility: 'Outdoor Pavilion',
    facilityImage: 'https://via.placeholder.com/50?text=Pavilion',
    type: 'Venue',
    purpose: 'Team Building - Company Picnic',
    status: 'Completed',
    completed: '2026-04-28 5:00 PM'
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
