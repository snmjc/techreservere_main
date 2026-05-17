<!-- ===== AI GENERATED: BorrowerViewReservationListPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'BORROWER'"
    :navigation-items="borrowerNavigationItems"
  >
    <!-- Page Header with Go Back Button -->
    <div class="view-reservation-list-page-header">
      <h2 class="view-reservation-list-page-heading">Active Reservations</h2>
      <button class="view-reservation-list-go-back-button" @click="handleGoBack">
        ← Go Back
      </button>
    </div>

    <!-- Search and Sorting Controls -->
    <div class="view-reservation-list-controls">
      <div class="view-reservation-list-search-group">
        <label class="view-reservation-list-search-label" for="reservationSearch">Search:</label>
        <input
          id="reservationSearch"
          v-model="searchQuery"
          type="text"
          class="view-reservation-list-search-input"
          placeholder="Name, ID, or Facility"
        />
      </div>

      <div class="view-reservation-list-sort-group">
        <label class="view-reservation-list-sort-label" for="reservationSort">Sort By:</label>
        <select
          id="reservationSort"
          v-model="sortBy"
          class="view-reservation-list-sort-select"
        >
          <option value="date">Requested Date</option>
          <option value="name">Name</option>
          <option value="facility">Facility</option>
        </select>
      </div>

      <button
        class="view-reservation-list-sort-toggle"
        @click="toggleSortOrder"
        :title="sortOrder === 'asc' ? 'Sort Descending' : 'Sort Ascending'"
        aria-label="Toggle sort order"
      >
        {{ sortOrder === 'asc' ? '↑' : '↓' }}
      </button>
    </div>

    <!-- Reservations Table -->
    <div class="view-reservation-list-table-wrapper">
      <table class="view-reservation-list-table">
        <thead>
          <tr>
            <th class="view-reservation-list-th">Reservation ID</th>
            <th class="view-reservation-list-th">Name</th>
            <th class="view-reservation-list-th">Role</th>
            <th class="view-reservation-list-th">Schedule</th>
            <th class="view-reservation-list-th">Facility</th>
            <th class="view-reservation-list-th">Quantity</th>
            <th class="view-reservation-list-th">Type</th>
            <th class="view-reservation-list-th">Purpose</th>
            <th class="view-reservation-list-th">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="reservation in filteredAndSortedReservations" :key="reservation.reservationId" class="view-reservation-list-tr">
            <td class="view-reservation-list-td">{{ reservation.reservationId }}</td>
            <td class="view-reservation-list-td">{{ reservation.name }}</td>
            <td class="view-reservation-list-td">
              <span class="view-reservation-list-badge" :class="{ 'view-reservation-list-badge--student': reservation.role === 'Student', 'view-reservation-list-badge--faculty': reservation.role === 'Faculty' }">
                {{ reservation.role }}
              </span>
            </td>
            <td class="view-reservation-list-td">{{ reservation.schedule }}</td>
            <td class="view-reservation-list-td">
              <div class="view-reservation-list-facility">
                <img v-if="reservation.facilityImage" :src="reservation.facilityImage" :alt="reservation.facility" class="view-reservation-list-facility-image" />
                <span>{{ reservation.facility }}</span>
              </div>
            </td>
            <td class="view-reservation-list-td">{{ reservation.quantity }}</td>
            <td class="view-reservation-list-td">
              <span class="view-reservation-list-badge" :class="{ 'view-reservation-list-badge--venue': reservation.type === 'Venue', 'view-reservation-list-badge--equipment': reservation.type === 'Equipment' }">
                {{ reservation.type }}
              </span>
            </td>
            <td class="view-reservation-list-td">{{ reservation.purpose }}</td>
            <td class="view-reservation-list-td">
              <span class="view-reservation-list-status-badge view-reservation-list-status-badge--deployed">
                {{ reservation.status }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- No Results Message -->
      <div v-if="filteredAndSortedReservations.length === 0" class="view-reservation-list-no-results">
        No active reservations found matching your search.
      </div>
    </div>

    <!-- Footer -->
    <div class="view-reservation-list-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/ViewReservationList.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';

const router = useRouter();
const searchQuery = ref('');
const sortBy = ref('date');
const sortOrder = ref('asc');

/**
 * @constant {Array<Object>} mockReservations
 * @description Mock data for active reservations
 */
const mockReservations = ref([
  {
    reservationId: 'RES-2026-001',
    name: 'John Smith',
    role: 'Student',
    schedule: 'May 15, 2026 - 10:00 AM',
    facility: 'Main Auditorium',
    facilityImage: 'https://via.placeholder.com/50?text=Auditorium',
    quantity: 1,
    type: 'Venue',
    purpose: 'Student Presentation - Engineering Capstone Project',
    status: 'Deployed'
  },
  {
    reservationId: 'RES-2026-002',
    name: 'Maria Garcia',
    role: 'Faculty',
    schedule: 'May 16, 2026 - 2:00 PM',
    facility: 'Conference Room A',
    facilityImage: 'https://via.placeholder.com/50?text=Conference',
    quantity: 1,
    type: 'Venue',
    purpose: 'Department Meeting - Academic Planning',
    status: 'Deployed'
  },
  {
    reservationId: 'RES-2026-003',
    name: 'Robert Johnson',
    role: 'Student',
    schedule: 'May 17, 2026 - 3:30 PM',
    facility: 'Projector HD',
    facilityImage: 'https://via.placeholder.com/50?text=Projector',
    quantity: 2,
    type: 'Equipment',
    purpose: 'Class Project - Research Presentation',
    status: 'Deployed'
  },
  {
    reservationId: 'RES-2026-004',
    name: 'Sarah Williams',
    role: 'Faculty',
    schedule: 'May 18, 2026 - 9:00 AM',
    facility: 'Lecture Hall B',
    facilityImage: 'https://via.placeholder.com/50?text=Lecture',
    quantity: 1,
    type: 'Venue',
    purpose: 'Lecture - Advanced Database Systems',
    status: 'Deployed'
  },
  {
    reservationId: 'RES-2026-005',
    name: 'Michael Chen',
    role: 'Student',
    schedule: 'May 19, 2026 - 1:00 PM',
    facility: 'Wireless Microphone System',
    facilityImage: 'https://via.placeholder.com/50?text=Microphone',
    quantity: 1,
    type: 'Equipment',
    purpose: 'Event Coverage - Student Organization Summit',
    status: 'Deployed'
  },
  {
    reservationId: 'RES-2026-006',
    name: 'Jennifer Lee',
    role: 'Faculty',
    schedule: 'May 20, 2026 - 11:00 AM',
    facility: 'Meeting Room C',
    facilityImage: 'https://via.placeholder.com/50?text=Meeting',
    quantity: 1,
    type: 'Venue',
    purpose: 'Advisory Session - Student Mentoring',
    status: 'Deployed'
  },
  {
    reservationId: 'RES-2026-007',
    name: 'David Martinez',
    role: 'Student',
    schedule: 'May 21, 2026 - 4:00 PM',
    facility: 'Interactive Whiteboard System',
    facilityImage: 'https://via.placeholder.com/50?text=Whiteboard',
    quantity: 3,
    type: 'Equipment',
    purpose: 'Study Group - Mathematics Tutoring',
    status: 'Deployed'
  },
  {
    reservationId: 'RES-2026-008',
    name: 'Amanda Brown',
    role: 'Faculty',
    schedule: 'May 22, 2026 - 2:30 PM',
    facility: 'Seminar Room',
    facilityImage: 'https://via.placeholder.com/50?text=Seminar',
    quantity: 1,
    type: 'Venue',
    purpose: 'Seminar - Research Methodology Workshop',
    status: 'Deployed'
  },
  {
    reservationId: 'RES-2026-009',
    name: 'Christopher Lee',
    role: 'Student',
    schedule: 'May 23, 2026 - 10:30 AM',
    facility: '4K Projector System',
    facilityImage: 'https://via.placeholder.com/50?text=4K+Projector',
    quantity: 1,
    type: 'Equipment',
    purpose: 'Video Production - Documentary Screening',
    status: 'Deployed'
  },
  {
    reservationId: 'RES-2026-010',
    name: 'Patricia Anderson',
    role: 'Faculty',
    schedule: 'May 24, 2026 - 1:30 PM',
    facility: 'Board Room',
    facilityImage: 'https://via.placeholder.com/50?text=Board+Room',
    quantity: 1,
    type: 'Venue',
    purpose: 'Faculty Meeting - Curriculum Review',
    status: 'Deployed'
  },
  {
    reservationId: 'RES-2026-011',
    name: 'Kevin Thompson',
    role: 'Student',
    schedule: 'May 25, 2026 - 3:00 PM',
    facility: 'LED Display Screen',
    facilityImage: 'https://via.placeholder.com/50?text=LED+Screen',
    quantity: 2,
    type: 'Equipment',
    purpose: 'Event Setup - Career Fair Display',
    status: 'Deployed'
  },
  {
    reservationId: 'RES-2026-012',
    name: 'Elizabeth Martinez',
    role: 'Faculty',
    schedule: 'May 26, 2026 - 10:00 AM',
    facility: 'Training Room A',
    facilityImage: 'https://via.placeholder.com/50?text=Training',
    quantity: 1,
    type: 'Venue',
    purpose: 'Training Session - Software Development Tools',
    status: 'Deployed'
  },
  {
    reservationId: 'RES-2026-013',
    name: 'James Wilson',
    role: 'Student',
    schedule: 'May 27, 2026 - 2:00 PM',
    facility: 'Portable Sound System',
    facilityImage: 'https://via.placeholder.com/50?text=Sound+System',
    quantity: 1,
    type: 'Equipment',
    purpose: 'Club Event - Music Performance',
    status: 'Deployed'
  },
  {
    reservationId: 'RES-2026-014',
    name: 'Rachel Green',
    role: 'Faculty',
    schedule: 'May 28, 2026 - 11:30 AM',
    facility: 'Discussion Room',
    facilityImage: 'https://via.placeholder.com/50?text=Discussion',
    quantity: 1,
    type: 'Venue',
    purpose: 'Class Discussion - Literature Analysis',
    status: 'Deployed'
  },
  {
    reservationId: 'RES-2026-015',
    name: 'Daniel Rodriguez',
    role: 'Student',
    schedule: 'May 29, 2026 - 4:30 PM',
    facility: 'Document Camera System',
    facilityImage: 'https://via.placeholder.com/50?text=Doc+Camera',
    quantity: 1,
    type: 'Equipment',
    purpose: 'Presentation - Art Exhibition Documentation',
    status: 'Deployed'
  }
]);

/**
 * @computed {Array<Object>} filteredAndSortedReservations
 * @description Filters and sorts reservations based on search query and sort options
 */
const filteredAndSortedReservations = computed(() => {
  let reservations = [...mockReservations.value];

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    reservations = reservations.filter(res =>
      res.name.toLowerCase().includes(query) ||
      res.reservationId.toLowerCase().includes(query) ||
      res.facility.toLowerCase().includes(query)
    );
  }

  // Apply sorting
  reservations.sort((a, b) => {
    let compareA, compareB;

    if (sortBy.value === 'date') {
      compareA = new Date(a.schedule);
      compareB = new Date(b.schedule);
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

  return reservations;
});

/**
 * @function handleGoBack
 * @description Navigates back to the previous page
 * @returns {void}
 */
function handleGoBack() {
  router.back();
}

/**
 * @function toggleSortOrder
 * @description Toggles between ascending and descending sort order
 * @returns {void}
 */
function toggleSortOrder() {
  sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
}
</script>
