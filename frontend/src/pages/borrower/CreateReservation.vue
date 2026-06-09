<template>
  <AdminSidebarLayoutComponent
    :role-label="'DELA CRUZ, JUAN'"
    :navigation-items="borrowerNavigationItems"
  >
    <section class="borrower-reservation-page">
      <div class="borrower-reservation-topline">
        <button type="button" aria-label="Back" @click="router.push({ name: ROUTE_NAMES.borrowerMyReservations })">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <h1>Create Reservation</h1>
      </div>

      <div class="borrower-reservation-surface">
        <BorrowerReservationStepper :current-step="1" />

        <section class="borrower-reservation-card">
          <div class="borrower-reservation-panel">
            <h2>Reservation Details</h2>
            <p>Provide the basic information about your reservation request.</p>

            <div class="borrower-reservation-grid">
              <div class="borrower-reservation-field">
                <label for="requestDate">Request Date <em>*</em></label>
                <input id="requestDate" v-model="formState.requestDate" type="date" />
              </div>

              <div class="borrower-reservation-field">
                <label for="participantCount">No. of Participants <em>*</em></label>
                <input id="participantCount" v-model="formState.participantCount" type="number" min="1" placeholder="Enter estimated participants" />
                <small class="borrower-reservation-help">Enter the estimated number of participants.</small>
              </div>

              <div class="borrower-reservation-field">
                <label for="activityDate">Activity Date <em>*</em></label>
                <input id="activityDate" v-model="formState.activityDate" type="date" :min="formState.requestDate || todayIsoDate" />
              </div>

              <div class="borrower-reservation-field">
                <label for="reservationType">Reservation Type <em>*</em></label>
                <select id="reservationType" v-model="formState.reservationType">
                  <option value="Venue">Venue</option>
                  <option value="Equipment">Equipment</option>
                  <option value="Both">Venue and Equipment</option>
                </select>
                <small class="borrower-reservation-help">Choose whether you are reserving a venue or equipment.</small>
              </div>

              <div class="borrower-reservation-field borrower-reservation-field--full">
                <label>Activity Time <em>*</em></label>
                <div class="reservation-details-time-row">
                  <input v-model="formState.activityTimeFrom" type="time" />
                  <span>-</span>
                  <input v-model="formState.activityTimeTo" type="time" />
                </div>
              </div>

              <div class="borrower-reservation-field">
                <label for="activityNameTitle">Activity Name / Title <em>*</em></label>
                <input id="activityNameTitle" v-model.trim="formState.activityNameTitle" type="text" placeholder="IT0003 Presentation" />
                <small class="borrower-reservation-help">Enter a short title for your activity or event.</small>
              </div>

              <div class="borrower-reservation-note">
                <strong>Please ensure accuracy</strong>
                <p>The information you provide here will be used to process your reservation request.</p>
              </div>

              <div class="borrower-reservation-field">
                <label for="purposeText">Purpose <em>*</em></label>
                <select id="purposeText" v-model="formState.purposeText">
                  <option value="">Select purpose</option>
                  <option v-for="option in purposeOptions" :key="option" :value="option">{{ option }}</option>
                </select>
                <small class="borrower-reservation-help">Select the main purpose of your reservation.</small>
              </div>
            </div>
          </div>

          <footer class="borrower-reservation-actions borrower-reservation-actions--end">
            <button class="borrower-reservation-button borrower-reservation-button--ghost" type="button" @click="router.push({ name: ROUTE_NAMES.borrowerMyReservations })">
              Cancel
            </button>
            <button class="borrower-reservation-button borrower-reservation-button--primary" type="button" @click="handleNextPage">
              Next: Select Venue / Equipment
            </button>
          </footer>
        </section>
      </div>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import BorrowerReservationStepper from '@/modules/reservation/components/BorrowerReservationStepper.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/CreateReservationWizard.css';
import './css/CreateReservation.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { useReservationFormStore } from '@/modules/reservation/store/reservationFormStore.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';

const router = useRouter();
const reservationFormStore = useReservationFormStore();
const todayIsoDate = getTodayISODate();

const purposeOptions = [
  'Academic Class',
  'Thesis Defense',
  'Seminar',
  'Student Organization Event - Academic',
  'Student Organization Event - Non-Academic',
  'Department Activity',
  'Faculty Meeting',
  'Examination / Assessment',
  'Project/Capstone Presentation',
  'Orientation Program',
  'Performance/Production',
  'General Assembly',
  'Others: Specify',
];

const formState = ref({
  requestDate: reservationFormStore.requestDate || todayIsoDate,
  activityDate: reservationFormStore.activityDate || '',
  activityTimeFrom: reservationFormStore.activityTimeFrom || '',
  activityTimeTo: reservationFormStore.activityTimeTo || '',
  activityNameTitle: reservationFormStore.activityNameTitle || '',
  purposeText: reservationFormStore.purposeText || '',
  participantCount: reservationFormStore.participantCount || '',
  reservationType: reservationFormStore.reservationType || 'Venue',
});

function getTodayISODate() {
  const today = new Date();
  return today.toISOString().split('T')[0];
}

function handleNextPage() {
  if (
    !formState.value.requestDate ||
    !formState.value.activityDate ||
    !formState.value.activityTimeFrom ||
    !formState.value.activityTimeTo ||
    !formState.value.activityNameTitle.trim() ||
    !formState.value.purposeText ||
    !String(formState.value.participantCount).trim()
  ) {
    alert('Please complete all required reservation details first.');
    return;
  }

  if (formState.value.activityTimeFrom >= formState.value.activityTimeTo) {
    alert('End time must be after start time.');
    return;
  }

  reservationFormStore.requestDate = formState.value.requestDate;
  reservationFormStore.activityDate = formState.value.activityDate;
  reservationFormStore.activityTimeFrom = formState.value.activityTimeFrom;
  reservationFormStore.activityTimeTo = formState.value.activityTimeTo;
  reservationFormStore.activityNameTitle = formState.value.activityNameTitle.trim();
  reservationFormStore.purposeText = formState.value.purposeText;
  reservationFormStore.participantCount = String(formState.value.participantCount);
  reservationFormStore.reservationType = formState.value.reservationType;

  router.push({ name: 'borrowerCreateReservationVenuePage' });
}
</script>
