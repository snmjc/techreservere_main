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
                <div class="reservation-time-picker">
                  <div class="reservation-time-picker__summary">
                    <div class="reservation-time-picker__summary-card">
                      <span>Start Time</span>
                      <strong>{{ selectedStartTimeLabel }}</strong>
                    </div>
                    <div class="reservation-time-picker__summary-card">
                      <span>End Time</span>
                      <strong>{{ selectedEndTimeLabel }}</strong>
                    </div>
                  </div>

                  <div class="reservation-time-picker__panels">
                    <div class="reservation-time-picker__panel">
                      <div class="reservation-time-picker__panel-head">
                        <h3>Choose Start Time</h3>
                        <p>Select from 7:00 AM to 6:30 PM.</p>
                      </div>
                      <div class="reservation-time-picker__slot-grid">
                        <button
                          v-for="slot in startTimeSlots"
                          :key="`start-${slot.value}`"
                          type="button"
                          class="reservation-time-picker__slot"
                          :class="{ 'is-selected': formState.activityTimeFrom === slot.value }"
                          @click="selectStartTime(slot.value)"
                        >
                          {{ slot.label }}
                        </button>
                      </div>
                    </div>

                    <div class="reservation-time-picker__panel">
                      <div class="reservation-time-picker__panel-head">
                        <h3>Choose End Time</h3>
                        <p>Select a time later than the chosen start time.</p>
                      </div>
                      <div class="reservation-time-picker__slot-grid">
                        <button
                          v-for="slot in endTimeSlots"
                          :key="`end-${slot.value}`"
                          type="button"
                          class="reservation-time-picker__slot"
                          :class="{ 'is-selected': formState.activityTimeTo === slot.value }"
                          :disabled="slot.disabled"
                          @click="selectEndTime(slot.value)"
                        >
                          {{ slot.label }}
                        </button>
                      </div>
                    </div>
                  </div>
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
import { computed, ref } from 'vue';
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

const allHalfHourSlots = buildHalfHourSlots('07:00', '19:00');
const startTimeSlots = allHalfHourSlots.slice(0, -1);

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

const endTimeSlots = computed(() => allHalfHourSlots.map((slot) => ({
  ...slot,
  disabled: !formState.value.activityTimeFrom || slot.value <= formState.value.activityTimeFrom,
})));

const selectedStartTimeLabel = computed(() => formatSelectedSlotLabel(formState.value.activityTimeFrom, 'Choose a start time'));
const selectedEndTimeLabel = computed(() => formatSelectedSlotLabel(formState.value.activityTimeTo, 'Choose an end time'));

function getTodayISODate() {
  const today = new Date();
  return today.toISOString().split('T')[0];
}

function selectStartTime(timeValue) {
  formState.value.activityTimeFrom = timeValue;

  if (formState.value.activityTimeTo && formState.value.activityTimeTo <= timeValue) {
    formState.value.activityTimeTo = '';
  }
}

function selectEndTime(timeValue) {
  if (!formState.value.activityTimeFrom || timeValue <= formState.value.activityTimeFrom) {
    return;
  }

  formState.value.activityTimeTo = timeValue;
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

function buildHalfHourSlots(startTime, endTime) {
  const slots = [];
  let minutes = parseTimeToMinutes(startTime);
  const lastMinutes = parseTimeToMinutes(endTime);

  while (minutes <= lastMinutes) {
    const value = formatMinutesAsValue(minutes);
    slots.push({
      value,
      label: formatMinutesAsLabel(minutes),
    });
    minutes += 30;
  }

  return slots;
}

function parseTimeToMinutes(timeValue) {
  const [hours, minutes] = String(timeValue).split(':').map(Number);
  return (hours * 60) + minutes;
}

function formatMinutesAsValue(totalMinutes) {
  const hours = String(Math.floor(totalMinutes / 60)).padStart(2, '0');
  const minutes = String(totalMinutes % 60).padStart(2, '0');
  return `${hours}:${minutes}`;
}

function formatMinutesAsLabel(totalMinutes) {
  const hours24 = Math.floor(totalMinutes / 60);
  const minutes = totalMinutes % 60;
  const suffix = hours24 >= 12 ? 'PM' : 'AM';
  const hours12 = hours24 % 12 === 0 ? 12 : hours24 % 12;
  return `${hours12}:${String(minutes).padStart(2, '0')} ${suffix}`;
}

function formatSelectedSlotLabel(timeValue, fallbackLabel) {
  if (!timeValue) {
    return fallbackLabel;
  }

  return formatMinutesAsLabel(parseTimeToMinutes(timeValue));
}
</script>
