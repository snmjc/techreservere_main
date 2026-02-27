<!-- ===== AI GENERATED: BorrowerCreateReservationPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'DELA CRUZ, JUAN'"
    :navigation-items="borrowerNavigationItems"
  >
    <!-- Page Header -->
    <div class="create-reservation-page-header">
      <h2 class="create-reservation-page-heading">Create Reservation</h2>
    </div>

    <!-- Form Card -->
    <div class="create-reservation-form-card">
      <h3 class="create-reservation-form-title">Venue and Equipment Reservation Form</h3>

      <div class="create-reservation-form-row">
        <label class="create-reservation-form-label">Request Date:</label>
        <input
          v-model="reservationFormState.requestDate"
          type="date"
          class="create-reservation-form-input create-reservation-form-date"
        />
      </div>

      <div class="create-reservation-form-row">
        <label class="create-reservation-form-label">Activity Date:</label>
        <input
          v-model="reservationFormState.activityDate"
          type="date"
          class="create-reservation-form-input create-reservation-form-date"
          :min="reservationFormState.requestDate"
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

      <!-- Next Page Button -->
      <div class="create-reservation-form-actions">
        <button class="create-reservation-next-button" @click="handleNextPage">
          Next Page
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Footer -->
    <div class="create-reservation-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/borrowerCreateReservationPage.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { useReservationFormStore } from '@/modules/reservation/store/reservationFormStore.js';

const router = useRouter();
const reservationFormStore = useReservationFormStore();

/**
 * @function getTodayISODate
 * @description Returns today's date in ISO format (YYYY-MM-DD) for date input.
 * @returns {string}
 */
function getTodayISODate() {
  const today = new Date();
  return today.toISOString().split('T')[0];
}

const reservationFormState = ref({
  requestDate: reservationFormStore.requestDate || getTodayISODate(),
  activityDate: reservationFormStore.activityDate || '',
  activityTime: reservationFormStore.activityTime || '',
  activityNameTitle: reservationFormStore.activityNameTitle || '',
  purposeText: reservationFormStore.purposeText || '',
  departmentName: reservationFormStore.departmentName || '',
  participantCount: reservationFormStore.participantCount || '',
  reservationType: reservationFormStore.reservationType || 'Venue',
});

/**
 * @function handleNextPage
 * @description Saves form state to the shared store and navigates to the next step.
 * @returns {void}
 */
function handleNextPage() {
  reservationFormStore.requestDate = reservationFormState.value.requestDate;
  reservationFormStore.activityDate = reservationFormState.value.activityDate;
  reservationFormStore.activityTime = reservationFormState.value.activityTime;
  reservationFormStore.activityNameTitle = reservationFormState.value.activityNameTitle;
  reservationFormStore.purposeText = reservationFormState.value.purposeText;
  reservationFormStore.departmentName = reservationFormState.value.departmentName;
  reservationFormStore.participantCount = reservationFormState.value.participantCount;
  reservationFormStore.reservationType = reservationFormState.value.reservationType;
  router.push({ name: 'borrowerCreateReservationVenuePage' });
}
</script>
