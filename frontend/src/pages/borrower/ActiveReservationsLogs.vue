<!-- ===== AI GENERATED: BorrowerActiveReservationsLogsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'BORROWER'"
    :navigation-items="borrowerNavigationItems"
  >
    <!-- Page Header with Go Back Button -->
    <div class="logs-page-header">
      <h2 class="logs-page-heading">Active Reservations Logs</h2>
      <button class="logs-go-back-button" @click="handleGoBack">
        ← Go Back
      </button>
    </div>

    <!-- Search and Filter Controls -->
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
    </div>

    <!-- Logs Timeline -->
    <div class="logs-timeline">
      <div v-for="log in filteredLogs" :key="log.id" class="logs-entry">
        <div class="logs-entry-header">
          <span class="logs-entry-id">{{ log.reservationId }}</span>
          <span class="logs-entry-name">{{ log.name }}</span>
          <span class="logs-entry-date">{{ log.date }}</span>
        </div>
        <div class="logs-entry-details">
          <p><strong>Facility:</strong> {{ log.facility }}</p>
          <p><strong>Purpose:</strong> {{ log.purpose }}</p>
          <p><strong>Status:</strong> <span class="logs-status-badge logs-status-badge--active">{{ log.status }}</span></p>
          <p><strong>Activity:</strong> {{ log.activity }}</p>
        </div>
      </div>

      <!-- No Results Message -->
      <div v-if="filteredLogs.length === 0" class="logs-no-results">
        No active reservation logs found matching your search.
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

const mockLogs = ref([
  {
    id: 1,
    reservationId: 'RES-2026-001',
    name: 'John Smith',
    date: '2026-05-15 10:00 AM',
    facility: 'Main Auditorium',
    purpose: 'Student Presentation - Engineering Capstone Project',
    status: 'Active',
    activity: 'Reservation approved and deployed for use'
  },
  {
    id: 2,
    reservationId: 'RES-2026-003',
    name: 'Robert Johnson',
    date: '2026-05-17 3:30 PM',
    facility: 'Projector HD',
    purpose: 'Class Project - Research Presentation',
    status: 'Active',
    activity: 'Equipment checked out and ready for deployment'
  },
  {
    id: 3,
    reservationId: 'RES-2026-005',
    name: 'Michael Chen',
    date: '2026-05-19 1:00 PM',
    facility: 'Wireless Microphone System',
    purpose: 'Event Coverage - Student Organization Summit',
    status: 'Active',
    activity: 'Equipment deployed to event location'
  },
  {
    id: 4,
    reservationId: 'RES-2026-007',
    name: 'David Martinez',
    date: '2026-05-21 4:00 PM',
    facility: 'Interactive Whiteboard System',
    purpose: 'Study Group - Mathematics Tutoring',
    status: 'Active',
    activity: 'Equipment setup completed and operational'
  },
  {
    id: 5,
    reservationId: 'RES-2026-009',
    name: 'Christopher Lee',
    date: '2026-05-23 10:30 AM',
    facility: '4K Projector System',
    purpose: 'Video Production - Documentary Screening',
    status: 'Active',
    activity: 'Projector configured and tested for screening'
  },
  {
    id: 6,
    reservationId: 'RES-2026-011',
    name: 'Kevin Thompson',
    date: '2026-05-25 3:00 PM',
    facility: 'LED Display Screen',
    purpose: 'Event Setup - Career Fair Display',
    status: 'Active',
    activity: 'Display screens installed and operational'
  },
  {
    id: 7,
    reservationId: 'RES-2026-013',
    name: 'James Wilson',
    date: '2026-05-27 2:00 PM',
    facility: 'Portable Sound System',
    purpose: 'Club Event - Music Performance',
    status: 'Active',
    activity: 'Sound system deployed to event venue'
  },
  {
    id: 8,
    reservationId: 'RES-2026-015',
    name: 'Daniel Rodriguez',
    date: '2026-05-29 4:30 PM',
    facility: 'Document Camera System',
    purpose: 'Presentation - Art Exhibition Documentation',
    status: 'Active',
    activity: 'Camera system ready for documentation'
  }
]);

const filteredLogs = computed(() => {
  if (!searchQuery.value) {
    return mockLogs.value;
  }

  const query = searchQuery.value.toLowerCase();
  return mockLogs.value.filter(log =>
    log.reservationId.toLowerCase().includes(query) ||
    log.name.toLowerCase().includes(query)
  );
});

function handleGoBack() {
  router.back();
}
</script>
