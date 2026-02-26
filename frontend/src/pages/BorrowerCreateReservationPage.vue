<!-- ===== AI GENERATED: BorrowerCreateReservationPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'DELA CRUZ, JUAN'"
    :navigation-items="borrowerNavigationItems"
  >
    <!-- Page Header -->
    <div class="create-reservation-page-header">
      <h2 class="create-reservation-page-heading">Create Reservation</h2>
      <span class="create-reservation-next-link" @click="handleNextPage">Next Page</span>
    </div>

    <!-- Form Card -->
    <div class="create-reservation-form-card">
      <h3 class="create-reservation-form-title">Venue and Equipment Reservation Form</h3>

      <div class="create-reservation-form-row">
        <label class="create-reservation-form-label">Request Date:</label>
        <input
          v-model="reservationFormState.requestDate"
          type="text"
          class="create-reservation-form-input"
          readonly
        />
      </div>

      <div class="create-reservation-form-row">
        <label class="create-reservation-form-label">Activity Date:</label>
        <input
          v-model="reservationFormState.activityDate"
          type="text"
          class="create-reservation-form-input"
          placeholder="March 08, 2026"
        />
      </div>

      <div class="create-reservation-form-row">
        <label class="create-reservation-form-label">Activity Time:</label>
        <input
          v-model="reservationFormState.activityTime"
          type="text"
          class="create-reservation-form-input"
          placeholder="15:00 - 16:50"
        />
      </div>

      <div class="create-reservation-form-row">
        <label class="create-reservation-form-label">Activity Name/Title:</label>
        <input
          v-model="reservationFormState.activityNameTitle"
          type="text"
          class="create-reservation-form-input"
          placeholder="IT0003 Presentation"
        />
      </div>

      <div class="create-reservation-form-row">
        <label class="create-reservation-form-label">Purpose:</label>
        <input
          v-model="reservationFormState.purposeText"
          type="text"
          class="create-reservation-form-input"
          placeholder="Class"
        />
      </div>

      <div class="create-reservation-form-row">
        <label class="create-reservation-form-label">Department:</label>
        <input
          v-model="reservationFormState.departmentName"
          type="text"
          class="create-reservation-form-input"
          placeholder="CCSMMA"
        />
      </div>

      <div class="create-reservation-form-row">
        <label class="create-reservation-form-label">No. of Participants:</label>
        <input
          v-model="reservationFormState.participantCount"
          type="text"
          class="create-reservation-form-input"
          placeholder="15"
        />
      </div>

      <div class="create-reservation-form-row">
        <label class="create-reservation-form-label">Reservation Type:</label>
        <select
          v-model="reservationFormState.reservationType"
          class="create-reservation-form-select"
        >
          <option value="Venue">Venue</option>
          <option value="Equipment">Equipment</option>
          <option value="Both">Both</option>
        </select>
      </div>
    </div>

    <!-- Footer -->
    <div class="create-reservation-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/borrowerCreateReservationPage.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';

const router = useRouter();

/**
 * @function getCurrentDateFormatted
 * @description Returns today's date formatted as "Month DD, YYYY".
 * @returns {string}
 */
function getCurrentDateFormatted() {
  const today = new Date();
  return today.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
}

const reservationFormState = ref({
  requestDate: getCurrentDateFormatted(),
  activityDate: '',
  activityTime: '',
  activityNameTitle: '',
  purposeText: '',
  departmentName: '',
  participantCount: '',
  reservationType: 'Venue',
});

/**
 * @function handleNextPage
 * @description Handles Next Page click (placeholder for future step navigation).
 * @returns {void}
 */
function handleNextPage() {
  router.push({ name: 'borrowerCreateReservationVenuePage' });
}
</script>
