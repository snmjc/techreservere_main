<template>
  <AdminSidebarLayoutComponent
    :role-label="'DELA CRUZ, JUAN'"
    :navigation-items="borrowerNavigationItems"
  >
    <section class="borrower-reservation-page">
      <div class="borrower-reservation-topline">
        <h1>Create Reservation</h1>
      </div>

      <div class="borrower-reservation-surface">
        <BorrowerReservationStepper :current-step="5" />

        <p v-if="submissionError" class="reservation-summary-error">{{ submissionError }}</p>

        <section class="borrower-reservation-card">
          <div class="borrower-reservation-panel">
            <h2>Review Summary</h2>
            <p>Please review all details below before submitting your reservation request.</p>

            <div class="reservation-summary-layout">
              <div class="reservation-summary-main">
                <article class="reservation-summary-section">
                  <h3>Reservation Details</h3>
                  <div class="reservation-summary-grid">
                    <div><span>Request Date</span><strong>{{ formatDisplayDate(reservationFormStore.requestDate) }}</strong></div>
                    <div><span>Activity Name / Title</span><strong>{{ reservationFormStore.activityNameTitle || 'N/A' }}</strong></div>
                    <div><span>Activity Start Date</span><strong>{{ formatDisplayDate(reservationFormStore.activityDate) }}</strong></div>
                    <div><span>Activity End Date</span><strong>{{ formatDisplayDate(reservationFormStore.activityEndDate || reservationFormStore.activityDate) }}</strong></div>
                    <div><span>Purpose</span><strong>{{ reservationFormStore.purposeText || 'N/A' }}</strong></div>
                    <div><span>Activity Time</span><strong>{{ formatDisplayTime(reservationFormStore.activityTimeFrom) }} - {{ formatDisplayTime(reservationFormStore.activityTimeTo) }}</strong></div>
                    <div><span>No. of Participants</span><strong>{{ reservationFormStore.participantCount || '0' }}</strong></div>
                    <div><span>Reservation Type</span><strong>{{ reservationFormStore.reservationType }}</strong></div>
                  </div>
                </article>

                <article class="reservation-summary-section">
                  <h3>Venue / Equipment</h3>
                  <div class="reservation-summary-grid">
                    <div><span>Venue / Equipment</span><strong>{{ reservationSummaryLabel }}</strong></div>
                    <div><span>Location</span><strong>{{ reservationFormStore.selectedVenueRecord?.floorLabel || 'N/A' }}</strong></div>
                    <div><span>Capacity</span><strong>{{ reservationFormStore.selectedVenueRecord?.capacityLimit || 'N/A' }}</strong></div>
                  </div>
                </article>

                <article class="reservation-summary-section">
                  <h3>Additional Manpower</h3>
                  <div class="reservation-summary-grid">
                    <div><span>Security Guard</span><strong>{{ reservationFormStore.securityGuardCount || 'None' }}</strong></div>
                    <div><span>Security Crew</span><strong>{{ reservationFormStore.securityCrewCount || 'None' }}</strong></div>
                  </div>
                </article>

                <article class="reservation-summary-section">
                  <h3>Supporting Documents</h3>
                  <div class="reservation-summary-documents">
                    <span v-for="documentName in allDocumentNames" :key="documentName">{{ documentName }}</span>
                    <span v-if="!allDocumentNames.length">No documents uploaded yet.</span>
                  </div>
                </article>

                <article class="reservation-summary-section">
                  <h3>Document Type</h3>
                  <div class="reservation-summary-grid">
                    <div><span>Selected Type</span><strong>{{ reservationFormStore.documentType || 'Reservation' }}</strong></div>
                  </div>
                </article>
              </div>

              <aside class="reservation-summary-progress">
                <h3>Reservation Progress</h3>
                <article v-for="progressItem in progressItems" :key="progressItem.title">
                  <i>✓</i>
                  <div>
                    <strong>{{ progressItem.title }}</strong>
                    <p>{{ progressItem.description }}</p>
                  </div>
                </article>
              </aside>
            </div>
          </div>

          <footer class="borrower-reservation-actions">
            <button class="borrower-reservation-button borrower-reservation-button--secondary" type="button" @click="navigateToPreviousPage" :disabled="isSubmitting">
              Previous
            </button>
            <button class="borrower-reservation-button borrower-reservation-button--primary" type="button" @click="handleSubmitReservationRequest" :disabled="isSubmitting">
              {{ isSubmitting ? 'Submitting...' : 'Submit Request' }}
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
import './css/CreateReservationSummary.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { useReservationFormStore } from '@/modules/reservation/store/reservationFormStore.js';
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';

const router = useRouter();
const reservationFormStore = useReservationFormStore();
const requestStore = useRequestStore();
const isSubmitting = ref(false);
const submissionError = ref('');

const allDocumentNames = computed(() => [
  ...(reservationFormStore.supportingDocumentsList || []).map((item) => item.documentFileName),
  ...(reservationFormStore.recommendationDocumentsList || []).map((item) => item.documentFileName),
  ...(reservationFormStore.additionalDocumentsList || []).map((item) => item.documentFileName),
]);

const reservationSummaryLabel = computed(() => {
  const venueName = reservationFormStore.selectedVenueRecord?.venueName;
  const equipmentNames = (reservationFormStore.selectedEquipmentItems || [])
    .map((item) => `${item.equipmentName} (${item.selectedQuantity})`)
    .join(', ');

  if (reservationFormStore.reservationType === 'Both') {
    return [venueName, equipmentNames].filter(Boolean).join(' + ');
  }

  return reservationFormStore.reservationType === 'Venue' ? (venueName || 'N/A') : (equipmentNames || 'N/A');
});

const progressItems = [
  { title: 'Reservation Details', description: 'Request date, time, purpose, participants, and type.' },
  { title: 'Select Venue / Equipment', description: 'Selected your preferred venue or equipment.' },
  { title: 'Additional Information', description: 'Added required manpower and other related details.' },
  { title: 'Supporting Documents', description: 'Uploaded necessary documents for your request.' },
  { title: 'Review Summary', description: 'Review all information before submitting your request.' },
];

function formatDisplayDate(value) {
  if (!value) return 'N/A';
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return value;
  return new Intl.DateTimeFormat('en-PH', { month: 'long', day: 'numeric', year: 'numeric' }).format(parsed);
}

function formatDisplayTime(value) {
  if (!value) return 'N/A';
  const [hours, minutes] = String(value).split(':').map(Number);
  if (Number.isNaN(hours) || Number.isNaN(minutes)) return value;
  const suffix = hours >= 12 ? 'PM' : 'AM';
  const hours12 = hours % 12 === 0 ? 12 : hours % 12;
  return `${hours12}:${String(minutes).padStart(2, '0')} ${suffix}`;
}

async function handleSubmitReservationRequest() {
  try {
    isSubmitting.value = true;
    submissionError.value = '';

    const validationError = validateReservationSubmission();
    if (validationError) {
      submissionError.value = validationError;
      return;
    }

    const eventDateTime = new Date(`${reservationFormStore.activityDate}T${reservationFormStore.activityTimeFrom || '00:00'}`);
    const endDateTime = new Date(`${reservationFormStore.activityEndDate || reservationFormStore.activityDate}T${reservationFormStore.activityTimeTo || '00:00'}`);

    const reservationData = {
      organizationName: reservationFormStore.activityNameTitle.trim(),
      venueIdentifier: reservationFormStore.selectedVenueRecord?.venueIdentifier || null,
      requestedEquipmentList: (reservationFormStore.selectedEquipmentItems || []).map((item) => ({
        equipmentIdentifier: item.equipmentIdentifier,
        name: item.equipmentName,
        quantity: item.selectedQuantity,
      })),
      requestedQuantity: Number(reservationFormStore.participantCount),
      eventDateTime: eventDateTime.toISOString(),
      endDateTime: endDateTime.toISOString(),
      purposeDescription: reservationFormStore.purposeText,
      activityType: reservationFormStore.activityNameTitle.trim(),
      supportingDocuments: allDocumentNames.value,
    };

    const result = await requestStore.addNewReservation(reservationData);
    if (result) {
      reservationFormStore.resetForm();
      router.push({ name: ROUTE_NAMES.borrowerMyReservations });
    }
  } catch (error) {
    submissionError.value = error.message || 'Failed to submit reservation. Please try again.';
  } finally {
    isSubmitting.value = false;
  }
}

function navigateToPreviousPage() {
  router.push({ name: 'borrowerCreateReservationDocumentsPage' });
}

function validateReservationSubmission() {
  const participantCount = Number(reservationFormStore.participantCount);
  const startDateTime = new Date(`${reservationFormStore.activityDate}T${reservationFormStore.activityTimeFrom || ''}`);
  const endDateTime = new Date(`${reservationFormStore.activityEndDate || reservationFormStore.activityDate}T${reservationFormStore.activityTimeTo || ''}`);
  const todayIsoDate = getTodayIsoDate();
  const yearEndIsoDate = `${new Date().getFullYear()}-12-31`;
  const selectedEquipmentItems = reservationFormStore.selectedEquipmentItems || [];

  if (!reservationFormStore.requestDate || !reservationFormStore.activityDate || !reservationFormStore.activityEndDate) {
    return 'Reservation dates are required.';
  }

  if (
    reservationFormStore.requestDate < todayIsoDate
    || reservationFormStore.activityDate < todayIsoDate
    || reservationFormStore.activityEndDate < todayIsoDate
    || reservationFormStore.requestDate > yearEndIsoDate
    || reservationFormStore.activityDate > yearEndIsoDate
    || reservationFormStore.activityEndDate > yearEndIsoDate
  ) {
    return 'Reservation dates must stay between today and December 31 of the current year.';
  }

  if (!reservationFormStore.activityTimeFrom || !reservationFormStore.activityTimeTo) {
    return 'Activity start and end time are required.';
  }

  if (Number.isNaN(startDateTime.getTime()) || Number.isNaN(endDateTime.getTime()) || endDateTime <= startDateTime) {
    return 'End date and time must be later than the start date and time.';
  }

  if (!Number.isInteger(participantCount) || participantCount < 1 || participantCount > 500) {
    return 'Participant count must be a whole number from 1 to 500.';
  }

  if (!reservationFormStore.activityNameTitle.trim()) {
    return 'Activity name or title is required.';
  }

  if (!reservationFormStore.purposeText) {
    return 'Purpose is required.';
  }

  if (reservationFormStore.reservationType !== 'Equipment' && !reservationFormStore.selectedVenueRecord?.venueIdentifier) {
    return 'Please select a venue before submitting.';
  }

  if (reservationFormStore.reservationType !== 'Venue' && !selectedEquipmentItems.length) {
    return 'Please select at least one equipment item before submitting.';
  }

  const invalidEquipmentItem = selectedEquipmentItems.find((item) => {
    const selectedQuantity = Number(item.selectedQuantity);
    const availableQuantity = Number(item.availableQuantity ?? 0);

    return !Number.isInteger(selectedQuantity) || selectedQuantity < 1 || selectedQuantity > availableQuantity;
  });

  if (invalidEquipmentItem) {
    return `Equipment quantity for ${invalidEquipmentItem.equipmentName} must be between 1 and ${invalidEquipmentItem.availableQuantity}.`;
  }

  return '';
}

function getTodayIsoDate() {
  const today = new Date();
  return [
    today.getFullYear(),
    String(today.getMonth() + 1).padStart(2, '0'),
    String(today.getDate()).padStart(2, '0'),
  ].join('-');
}
</script>
