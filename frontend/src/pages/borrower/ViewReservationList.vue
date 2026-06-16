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
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/ViewReservationList.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { useRequestStore } from '@/modules/request/store/requestStore.js';

const router = useRouter();
const requestStore = useRequestStore();
const searchQuery = ref('');
const sortBy = ref('date');
const sortOrder = ref('asc');

onMounted(async () => {
  try {
    await requestStore.fetchReservations();
  } catch (error) {
    console.error('Error fetching active reservations:', error);
  }
});

const activeReservations = computed(() =>
  (requestStore.activeReservationsList || []).map((record) => ({
    reservationId: String(record.requestDisplayIdentifier || record.requestIdentifier || 'N/A'),
    name: record.requesterFullName || 'You',
    role: record.requesterRole || 'Borrower',
    schedule: record.requestSchedule || 'N/A',
    facility: record.facilityName || 'N/A',
    quantity: record.requestQuantity || 0,
    type: record.requestType || 'Reservation',
    purpose: record.requestPurpose || 'N/A',
    status: record.requestStatus || 'Active',
    sortDate: getDateSortValue(record.requestSchedule),
  }))
);

const filteredAndSortedReservations = computed(() => {
  const query = searchQuery.value.toLowerCase().trim();
  let reservations = activeReservations.value;

  if (query) {
    reservations = reservations.filter((reservation) =>
      [reservation.name, reservation.reservationId, reservation.facility]
        .some((value) => String(value || '').toLowerCase().includes(query))
    );
  }

  return [...reservations].sort((first, second) => {
    const firstValue = resolveSortValue(first);
    const secondValue = resolveSortValue(second);

    if (typeof firstValue === 'string' && typeof secondValue === 'string') {
      return sortOrder.value === 'asc'
        ? firstValue.localeCompare(secondValue)
        : secondValue.localeCompare(firstValue);
    }

    return sortOrder.value === 'asc' ? firstValue - secondValue : secondValue - firstValue;
  });
});

function handleGoBack() {
  router.back();
}

function toggleSortOrder() {
  sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
}

function resolveSortValue(reservation) {
  if (sortBy.value === 'name') return reservation.name.toLowerCase();
  if (sortBy.value === 'facility') return reservation.facility.toLowerCase();
  return reservation.sortDate;
}

function getDateSortValue(value) {
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? 0 : date.getTime();
}
</script>
