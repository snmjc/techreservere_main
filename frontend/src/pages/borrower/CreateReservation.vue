<template>
  <AdminSidebarLayoutComponent
    :role-label="''"
    :navigation-items="borrowerNavigationItems"
  >
    <section class="borrower-reservation-page">
      <div class="borrower-reservation-topline">
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
                <input id="requestDate" v-model="formState.requestDate" type="date" readonly />
                <small class="borrower-reservation-help">This date is set automatically when you create the request.</small>
                <small v-if="validationErrors.requestDate" class="borrower-reservation-help borrower-reservation-help--error">{{ validationErrors.requestDate }}</small>
              </div>

              <div class="borrower-reservation-field">
                <label for="participantCount">No. of Participants <em>*</em></label>
                <input
                  id="participantCount"
                  v-model="formState.participantCount"
                  type="text"
                  inputmode="numeric"
                  maxlength="3"
                  placeholder="Enter estimated participants"
                  @input="sanitizeParticipantCountField"
                />
                <small class="borrower-reservation-help">Enter the estimated number of participants. Maximum: 500.</small>
                <small v-if="validationErrors.participantCount" class="borrower-reservation-help borrower-reservation-help--error">{{ validationErrors.participantCount }}</small>
              </div>

              <div class="borrower-reservation-field">
                <label for="activityDate">Activity Start Date <em>*</em></label>
                <input id="activityDate" v-model="formState.activityDate" type="date" :min="activityStartMinDate" :max="activityMaxDate" @change="handleActivityDateChange" />
                <small class="borrower-reservation-help">{{ bookingWindowHelpText }}</small>
                <small v-if="validationErrors.activityDate" class="borrower-reservation-help borrower-reservation-help--error">{{ validationErrors.activityDate }}</small>
              </div>

              <div class="borrower-reservation-field">
                <label for="activityEndDate">Activity End Date <em>*</em></label>
                <input id="activityEndDate" v-model="formState.activityEndDate" type="date" :min="activityEndMinDate" :max="activityMaxDate" @change="handleActivityEndDateChange" />
                <small v-if="validationErrors.activityEndDate" class="borrower-reservation-help borrower-reservation-help--error">{{ validationErrors.activityEndDate }}</small>
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
                  <div class="reservation-time-picker__trigger-grid">
                    <div ref="startPickerRef" class="reservation-time-picker__trigger-wrap">
                      <button
                        type="button"
                        class="reservation-time-picker__trigger"
                        :class="{ 'is-open': openTimePicker === 'start' }"
                        @click="toggleTimePicker('start')"
                      >
                        <span>Start Time</span>
                        <strong>{{ selectedStartTimeLabel }}</strong>
                      </button>

                      <div v-if="openTimePicker === 'start'" class="reservation-time-picker__popover">
                        <div class="reservation-time-picker__popover-head">
                          <h3>Choose Start Time</h3>
                          <p>Select only :00 or :30.</p>
                        </div>
                        <div class="reservation-time-picker__popover-preview">{{ formatSelectedSlotLabel(startTimeDraft, 'Choose a start time') }}</div>
                        <div class="reservation-time-picker__picker-grid">
                          <div class="reservation-time-picker__picker-column">
                            <span>Available Slots</span>
                            <div class="reservation-time-picker__picker-options reservation-time-picker__picker-options--slots">
                              <button
                                v-for="timeOption in startTimeOptions"
                                :key="`start-slot-${timeOption.value}`"
                                type="button"
                                class="reservation-time-picker__picker-option"
                                :class="{ 'is-selected': startTimeDraft === timeOption.value }"
                                @click="startTimeDraft = timeOption.value"
                              >
                                {{ timeOption.label }}
                              </button>
                            </div>
                          </div>
                        </div>
                        <div class="reservation-time-picker__popover-actions">
                          <button type="button" class="reservation-time-picker__popover-button reservation-time-picker__popover-button--ghost" @click="closeTimePicker">Cancel</button>
                          <button type="button" class="reservation-time-picker__popover-button reservation-time-picker__popover-button--primary" @click="applyTimeSelection('start')">Confirm</button>
                        </div>
                      </div>
                    </div>

                    <div ref="endPickerRef" class="reservation-time-picker__trigger-wrap">
                      <button
                        type="button"
                        class="reservation-time-picker__trigger"
                        :class="{ 'is-open': openTimePicker === 'end' }"
                        @click="toggleTimePicker('end')"
                      >
                        <span>End Time</span>
                        <strong>{{ selectedEndTimeLabel }}</strong>
                      </button>

                      <div v-if="openTimePicker === 'end'" class="reservation-time-picker__popover">
                        <div class="reservation-time-picker__popover-head">
                          <h3>Choose End Time</h3>
                          <p>Select a time later than the chosen start time.</p>
                        </div>
                        <div class="reservation-time-picker__popover-preview">{{ formatSelectedSlotLabel(endTimeDraft, 'Choose an end time') }}</div>
                        <div class="reservation-time-picker__picker-grid">
                          <div class="reservation-time-picker__picker-column">
                            <span>Available Slots</span>
                            <div class="reservation-time-picker__picker-options reservation-time-picker__picker-options--slots">
                              <button
                                v-for="timeOption in endTimeOptions"
                                :key="`end-slot-${timeOption.value}`"
                                type="button"
                                class="reservation-time-picker__picker-option"
                                :class="{ 'is-selected': endTimeDraft === timeOption.value }"
                                @click="endTimeDraft = timeOption.value"
                              >
                                {{ timeOption.label }}
                              </button>
                            </div>
                          </div>
                        </div>
                        <div class="reservation-time-picker__popover-actions">
                          <button type="button" class="reservation-time-picker__popover-button reservation-time-picker__popover-button--ghost" @click="closeTimePicker">Cancel</button>
                          <button type="button" class="reservation-time-picker__popover-button reservation-time-picker__popover-button--primary" @click="applyTimeSelection('end')">Confirm</button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <small class="borrower-reservation-help">Choose a start and end time for the activity.</small>
                <small v-if="validationErrors.activityTime" class="borrower-reservation-help borrower-reservation-help--error">{{ validationErrors.activityTime }}</small>
              </div>

              <div class="borrower-reservation-field">
                <label for="activityNameTitle">Activity Name / Title <em>*</em></label>
                <input id="activityNameTitle" v-model.trim="formState.activityNameTitle" type="text" maxlength="100" placeholder="IT0003 Presentation" />
                <small class="borrower-reservation-help">Enter a short title for your activity or event. Maximum: 100 characters.</small>
                <small v-if="validationErrors.activityNameTitle" class="borrower-reservation-help borrower-reservation-help--error">{{ validationErrors.activityNameTitle }}</small>
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
                <small v-if="validationErrors.purposeText" class="borrower-reservation-help borrower-reservation-help--error">{{ validationErrors.purposeText }}</small>
              </div>

              <div v-if="isOtherPurposeSelected" class="borrower-reservation-field borrower-reservation-field--full">
                <label for="purposeOtherText">Please specify <em>*</em></label>
                <textarea
                  id="purposeOtherText"
                  v-model.trim="formState.purposeOtherText"
                  rows="3"
                  maxlength="200"
                  placeholder="Enter the specific purpose of your reservation..."
                ></textarea>
                <small class="borrower-reservation-help">Provide the exact purpose when you choose `Others: Specify`. Maximum: 200 characters.</small>
                <small v-if="validationErrors.purposeOtherText" class="borrower-reservation-help borrower-reservation-help--error">{{ validationErrors.purposeOtherText }}</small>
              </div>
            </div>
          </div>

          <footer class="borrower-reservation-actions borrower-reservation-actions--end">
            <button class="borrower-reservation-button borrower-reservation-button--ghost" type="button" @click="router.push({ name: ROUTE_NAMES.borrowerMyReservations })">
              Cancel
            </button>
            <button class="borrower-reservation-button borrower-reservation-button--primary" type="button" :disabled="isStepLoading" @click="handleNextPage">
              {{ isStepLoading ? 'Loading...' : 'Next: Select Venue / Equipment' }}
            </button>
          </footer>
        </section>
      </div>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import BorrowerReservationStepper from '@/modules/reservation/components/BorrowerReservationStepper.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/CreateReservationWizard.css';
import './css/CreateReservation.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { useReservationFormStore } from '@/modules/reservation/store/reservationFormStore.js';
import { bookingPolicyApi } from '@/modules/reservation/services/bookingPolicyApi.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';

const router = useRouter();
const reservationFormStore = useReservationFormStore();
const todayIsoDate = getTodayISODate();
const bookingWindow = ref({
  activeBookingStartDate: todayIsoDate,
  activeBookingEndDate: getCurrentYearEndISODate(),
  extendedBookingEndDate: getCurrentYearEndISODate(),
  termLabel: '',
});

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

const BUSINESS_START_MINUTES = 7 * 60;
const BUSINESS_END_MINUTES = 21 * 60;
const startPickerRef = ref(null);
const endPickerRef = ref(null);
const openTimePicker = ref('');
const isStepLoading = ref(false);
const validationErrors = reactive({
  requestDate: '',
  participantCount: '',
  activityDate: '',
  activityEndDate: '',
  activityTime: '',
  activityNameTitle: '',
  purposeText: '',
  purposeOtherText: '',
});

const formState = ref({
  requestDate: reservationFormStore.requestDate || todayIsoDate,
  activityDate: reservationFormStore.activityDate || '',
  activityEndDate: reservationFormStore.activityEndDate || reservationFormStore.activityDate || '',
  activityTimeFrom: reservationFormStore.activityTimeFrom || '',
  activityTimeTo: reservationFormStore.activityTimeTo || '',
  activityNameTitle: reservationFormStore.activityNameTitle || '',
  purposeText: reservationFormStore.purposeText || '',
  purposeOtherText: reservationFormStore.purposeOtherText || '',
  participantCount: reservationFormStore.participantCount || '',
  reservationType: reservationFormStore.reservationType || 'Venue',
});

const selectedStartTimeLabel = computed(() => formatSelectedSlotLabel(formState.value.activityTimeFrom, 'Choose a start time'));
const selectedEndTimeLabel = computed(() => formatSelectedSlotLabel(formState.value.activityTimeTo, 'Choose an end time'));
const isOtherPurposeSelected = computed(() => formState.value.purposeText === 'Others: Specify');
const activityStartMinDate = computed(() => maxIsoDate(
  maxIsoDate(todayIsoDate, formState.value.requestDate || todayIsoDate),
  bookingWindow.value.activeBookingStartDate || todayIsoDate,
));
const activityEndMinDate = computed(() => maxIsoDate(activityStartMinDate.value, formState.value.activityDate || activityStartMinDate.value));
const activityMaxDate = computed(() => bookingWindow.value.activeBookingEndDate || getCurrentYearEndISODate());
const bookingWindowHelpText = computed(() => {
  const termLabel = String(bookingWindow.value.termLabel || '').trim();
  const formattedStart = formatPolicyDate(bookingWindow.value.activeBookingStartDate);
  const formattedEnd = formatPolicyDate(bookingWindow.value.activeBookingEndDate);
  return termLabel
    ? `Booking window for ${termLabel}: ${formattedStart} to ${formattedEnd}.`
    : `Booking window: ${formattedStart} to ${formattedEnd}.`;
});
const allTimeSlotOptions = computed(() => buildTimeSlotOptions(BUSINESS_START_MINUTES, BUSINESS_END_MINUTES));
const startTimeOptions = computed(() => allTimeSlotOptions.value);
const endTimeOptions = computed(() => {
  if (
    !formState.value.activityDate
    || !formState.value.activityEndDate
    || formState.value.activityDate !== formState.value.activityEndDate
  ) {
    return allTimeSlotOptions.value;
  }

  const startMinutes = parseTimeToMinutes(formState.value.activityTimeFrom);
  if (!Number.isInteger(startMinutes)) {
    return allTimeSlotOptions.value;
  }

  return allTimeSlotOptions.value.filter((timeOption) => {
    const optionMinutes = parseTimeToMinutes(timeOption.value);
    return !Number.isInteger(optionMinutes) || optionMinutes > startMinutes;
  });
});
const startTimeDraft = ref(normalizeTimeValue(formState.value.activityTimeFrom, formatMinutesAsValue(BUSINESS_START_MINUTES)));
const endTimeDraft = ref(normalizeTimeValue(formState.value.activityTimeTo, formatMinutesAsValue(BUSINESS_START_MINUTES + 30)));

onMounted(async () => {
  formState.value.requestDate = todayIsoDate;
  await loadBookingWindow();
  formState.value.activityTimeFrom = normalizeTimeValue(formState.value.activityTimeFrom, '');
  formState.value.activityTimeTo = normalizeTimeValue(formState.value.activityTimeTo, '');
  document.addEventListener('mousedown', handleGlobalPointerDown);
});

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleGlobalPointerDown);
});

function getTodayISODate() {
  const today = new Date();
  return [
    today.getFullYear(),
    String(today.getMonth() + 1).padStart(2, '0'),
    String(today.getDate()).padStart(2, '0'),
  ].join('-');
}

function getCurrentYearEndISODate() {
  const today = new Date();
  return `${today.getFullYear()}-12-31`;
}

async function loadBookingWindow() {
  try {
    const policyWindow = await bookingPolicyApi.getBookingWindow();
    bookingWindow.value = {
      activeBookingStartDate: policyWindow?.activeBookingStartDate || todayIsoDate,
      activeBookingEndDate: policyWindow?.activeBookingEndDate || getCurrentYearEndISODate(),
      extendedBookingEndDate: policyWindow?.extendedBookingEndDate || policyWindow?.activeBookingEndDate || getCurrentYearEndISODate(),
      termLabel: policyWindow?.termLabel || '',
    };
  } catch {
    bookingWindow.value = {
      activeBookingStartDate: todayIsoDate,
      activeBookingEndDate: getCurrentYearEndISODate(),
      extendedBookingEndDate: getCurrentYearEndISODate(),
      termLabel: '',
    };
  }

  if (formState.value.activityDate && formState.value.activityDate < activityStartMinDate.value) {
    formState.value.activityDate = activityStartMinDate.value;
  }

  if (formState.value.activityEndDate && formState.value.activityEndDate < activityEndMinDate.value) {
    formState.value.activityEndDate = activityEndMinDate.value;
  }
}

function toggleTimePicker(type) {
  if (openTimePicker.value === type) {
    closeTimePicker();
    return;
  }

  if (type === 'start') {
    startTimeDraft.value = normalizeTimeValue(
      formState.value.activityTimeFrom,
      formatMinutesAsValue(BUSINESS_START_MINUTES),
    );
  } else {
    endTimeDraft.value = normalizeTimeValue(
      formState.value.activityTimeTo,
      resolveDefaultEndTimeValue(),
    );
  }

  openTimePicker.value = type;
}

function closeTimePicker() {
  openTimePicker.value = '';
}

function applyTimeSelection(type) {
  const fallbackValue = type === 'start'
    ? formatMinutesAsValue(BUSINESS_START_MINUTES)
    : resolveDefaultEndTimeValue();
  const timeValue = normalizeTimeValue(type === 'start' ? startTimeDraft.value : endTimeDraft.value, fallbackValue);

  if (type === 'start') {
    formState.value.activityTimeFrom = timeValue;
    if (
      formState.value.activityDate === formState.value.activityEndDate &&
      formState.value.activityTimeTo &&
      formState.value.activityTimeTo <= timeValue
    ) {
      formState.value.activityTimeTo = '';
      endTimeDraft.value = resolveDefaultEndTimeValue();
    }
  } else {
    formState.value.activityTimeTo = timeValue;
  }

  closeTimePicker();
}

function handleGlobalPointerDown(event) {
  const target = event.target;
  if (openTimePicker.value === 'start' && startPickerRef.value?.contains(target)) {
    return;
  }

  if (openTimePicker.value === 'end' && endPickerRef.value?.contains(target)) {
    return;
  }

  closeTimePicker();
}

function handleActivityDateChange() {
  if (formState.value.activityDate && formState.value.activityDate < activityStartMinDate.value) {
    formState.value.activityDate = activityStartMinDate.value;
  }

  if (formState.value.activityDate && formState.value.activityDate > activityMaxDate.value) {
    formState.value.activityDate = activityMaxDate.value;
  }

  if (!formState.value.activityEndDate || formState.value.activityEndDate < formState.value.activityDate) {
    formState.value.activityEndDate = formState.value.activityDate;
  }

  if (
    formState.value.activityDate === formState.value.activityEndDate &&
    formState.value.activityTimeFrom &&
    formState.value.activityTimeTo &&
    formState.value.activityTimeTo <= formState.value.activityTimeFrom
  ) {
    formState.value.activityTimeTo = '';
    endTimeDraft.value = resolveDefaultEndTimeValue();
  }
}

function handleActivityEndDateChange() {
  if (formState.value.activityEndDate && formState.value.activityEndDate < activityEndMinDate.value) {
    formState.value.activityEndDate = activityEndMinDate.value;
  }

  if (formState.value.activityEndDate && formState.value.activityEndDate > activityMaxDate.value) {
    formState.value.activityEndDate = activityMaxDate.value;
  }

  if (
    formState.value.activityDate === formState.value.activityEndDate &&
    formState.value.activityTimeFrom &&
    formState.value.activityTimeTo &&
    formState.value.activityTimeTo <= formState.value.activityTimeFrom
  ) {
    formState.value.activityTimeTo = '';
    endTimeDraft.value = resolveDefaultEndTimeValue();
  }
}

function sanitizeParticipantCountField() {
  formState.value.participantCount = String(formState.value.participantCount || '').replace(/\D/g, '').slice(0, 3);
}

function handleNextPage() {
  formState.value.activityTimeFrom = normalizeTimeValue(formState.value.activityTimeFrom, '');
  formState.value.activityTimeTo = normalizeTimeValue(formState.value.activityTimeTo, '');

  if (!validateReservationDetails()) {
    return;
  }

  reservationFormStore.requestDate = formState.value.requestDate;
  reservationFormStore.activityDate = formState.value.activityDate;
  reservationFormStore.activityEndDate = formState.value.activityEndDate;
  reservationFormStore.activityTimeFrom = formState.value.activityTimeFrom;
  reservationFormStore.activityTimeTo = formState.value.activityTimeTo;
  reservationFormStore.activityNameTitle = formState.value.activityNameTitle.trim();
  reservationFormStore.purposeText = formState.value.purposeText;
  reservationFormStore.purposeOtherText = isOtherPurposeSelected.value
    ? formState.value.purposeOtherText.trim()
    : '';
  reservationFormStore.participantCount = String(formState.value.participantCount);
  reservationFormStore.reservationType = formState.value.reservationType;
  reservationFormStore.persistForm();
  isStepLoading.value = true;
  window.setTimeout(() => {
    router.push({ name: 'borrowerCreateReservationVenuePage' });
  }, 250);
}

function validateReservationDetails() {
  clearValidationErrors();

  if (formState.value.requestDate !== todayIsoDate) {
    validationErrors.requestDate = 'Request date is set automatically to today.';
  }

  const participantCount = Number(formState.value.participantCount);
  if (!String(formState.value.participantCount).trim()) {
    validationErrors.participantCount = 'Participant count is required.';
  } else if (!Number.isInteger(participantCount) || participantCount < 1 || participantCount > 500) {
    validationErrors.participantCount = 'Participant count must be a whole number from 1 to 500.';
  }

  if (!isWithinAllowedReservationDate(formState.value.activityDate)) {
    validationErrors.activityDate = `Activity start date must be within the active booking window of ${formatPolicyDate(bookingWindow.value.activeBookingStartDate)} to ${formatPolicyDate(bookingWindow.value.activeBookingEndDate)}.`;
  } else if (formState.value.activityDate < activityStartMinDate.value) {
    validationErrors.activityDate = 'Activity start date cannot be earlier than the request date.';
  }

  if (!isWithinAllowedReservationDate(formState.value.activityEndDate)) {
    validationErrors.activityEndDate = `Activity end date must be within the active booking window of ${formatPolicyDate(bookingWindow.value.activeBookingStartDate)} to ${formatPolicyDate(bookingWindow.value.activeBookingEndDate)}.`;
  } else if (formState.value.activityEndDate < activityEndMinDate.value) {
    validationErrors.activityEndDate = 'Activity end date cannot be earlier than the activity start date.';
  }

  if (!formState.value.activityTimeFrom || !formState.value.activityTimeTo) {
    validationErrors.activityTime = 'Start time and end time are required.';
  } else {
    const startDateTime = new Date(`${formState.value.activityDate}T${formState.value.activityTimeFrom}`);
    const endDateTime = new Date(`${formState.value.activityEndDate}T${formState.value.activityTimeTo}`);
    if (Number.isNaN(startDateTime.getTime()) || Number.isNaN(endDateTime.getTime()) || endDateTime <= startDateTime) {
      validationErrors.activityTime = 'End time must be later than the start time.';
    } else if (!isAllowedTimeSlot(formState.value.activityTimeFrom) || !isAllowedTimeSlot(formState.value.activityTimeTo)) {
      validationErrors.activityTime = 'Activity time must be between 7:00 AM and 9:00 PM using :00 or :30 increments.';
    }
  }

  if (!formState.value.activityNameTitle.trim()) {
    validationErrors.activityNameTitle = 'Activity name or title is required.';
  } else if (formState.value.activityNameTitle.trim().length > 100) {
    validationErrors.activityNameTitle = 'Activity name or title must be 100 characters or fewer.';
  }

  if (!formState.value.purposeText) {
    validationErrors.purposeText = 'Purpose is required.';
  } else if (isOtherPurposeSelected.value && !formState.value.purposeOtherText.trim()) {
    validationErrors.purposeOtherText = 'Please specify the purpose of your reservation.';
  } else if (isOtherPurposeSelected.value && formState.value.purposeOtherText.trim().length > 200) {
    validationErrors.purposeOtherText = 'Purpose must be 200 characters or fewer.';
  }

  return Object.values(validationErrors).every((value) => value === '');
}

function clearValidationErrors() {
  Object.keys(validationErrors).forEach((key) => {
    validationErrors[key] = '';
  });
}

function isWithinAllowedReservationDate(value) {
  return typeof value === 'string'
    && value >= activityStartMinDate.value
    && value <= activityMaxDate.value;
}

function maxIsoDate(leftValue, rightValue) {
  return leftValue > rightValue ? leftValue : rightValue;
}
function parseTimeToMinutes(timeValue) {
  const [hours, minutes] = String(timeValue || '').trim().split(':').map(Number);
  if (!Number.isInteger(hours) || !Number.isInteger(minutes)) {
    return Number.NaN;
  }

  return (hours * 60) + minutes;
}

function isAllowedTimeSlot(timeValue) {
  const [hours, minutes] = String(timeValue || '').split(':').map(Number);
  if (!Number.isInteger(hours) || !Number.isInteger(minutes)) {
    return false;
  }

  if (![0, 30].includes(minutes)) {
    return false;
  }

  const totalMinutes = (hours * 60) + minutes;
  return totalMinutes >= BUSINESS_START_MINUTES && totalMinutes <= BUSINESS_END_MINUTES;
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

  const totalMinutes = parseTimeToMinutes(timeValue);
  if (!Number.isInteger(totalMinutes)) {
    return fallbackLabel;
  }

  return formatMinutesAsLabel(totalMinutes);
}

function buildTimeSlotOptions(startMinutes, endMinutes) {
  const timeSlotOptions = [];

  for (let minutes = startMinutes; minutes <= endMinutes; minutes += 30) {
    timeSlotOptions.push({
      value: formatMinutesAsValue(minutes),
      label: formatMinutesAsLabel(minutes),
    });
  }

  return timeSlotOptions;
}

function normalizeTimeValue(timeValue, fallbackValue = '') {
  const normalizedValue = String(timeValue || '').trim();
  if (normalizedValue === '') {
    return fallbackValue;
  }

  return isAllowedTimeSlot(normalizedValue) ? normalizedValue : fallbackValue;
}

function resolveDefaultEndTimeValue() {
  const normalizedStartTime = normalizeTimeValue(formState.value.activityTimeFrom, formatMinutesAsValue(BUSINESS_START_MINUTES));
  const startMinutes = parseTimeToMinutes(normalizedStartTime);
  const nextSlotMinutes = Number.isInteger(startMinutes)
    ? Math.min(startMinutes + 30, BUSINESS_END_MINUTES)
    : BUSINESS_START_MINUTES + 30;

  if (nextSlotMinutes <= startMinutes) {
    return formatMinutesAsValue(BUSINESS_END_MINUTES);
  }

  return formatMinutesAsValue(nextSlotMinutes);
}

function formatPolicyDate(value) {
  if (!value) {
    return 'N/A';
  }

  const parsedDate = new Date(`${value}T00:00:00`);
  if (Number.isNaN(parsedDate.getTime())) {
    return String(value);
  }

  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    timeZone: 'Asia/Manila',
  }).format(parsedDate);
}
</script>
