<template>
  <AdminSidebarLayoutComponent
    :role-label="'DELA CRUZ, JUAN'"
    :navigation-items="borrowerNavigationItems"
  >
    <section class="borrower-reservation-page">
      <div class="borrower-reservation-topline">
        <button type="button" aria-label="Back" @click="navigateToPreviousPage">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        </button>
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
                    <div><span>Activity Date</span><strong>{{ formatDisplayDate(reservationFormStore.activityDate) }}</strong></div>
                    <div><span>Purpose</span><strong>{{ reservationFormStore.purposeText || 'N/A' }}</strong></div>
                    <div><span>Activity Time</span><strong>{{ reservationFormStore.activityTimeFrom }} - {{ reservationFormStore.activityTimeTo }}</strong></div>
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

async function handleSubmitReservationRequest() {
  try {
    isSubmitting.value = true;
    submissionError.value = '';

    const eventDateTime = new Date(`${reservationFormStore.activityDate}T${reservationFormStore.activityTimeFrom || '00:00'}`);
    const activityTimeRange = `${reservationFormStore.activityTimeFrom || '00:00'}-${reservationFormStore.activityTimeTo || '00:00'}`;

    const reservationData = {
      organizationName: reservationFormStore.departmentName || 'Organization',
      venueIdentifier: reservationFormStore.selectedVenueRecord?.venueIdentifier || null,
      requestedEquipmentList: (reservationFormStore.selectedEquipmentItems || []).map((item) => ({
        name: item.equipmentName,
        quantity: item.selectedQuantity,
      })),
      requestedQuantity: reservationFormStore.participantCount || 0,
      eventDateTime: eventDateTime.toISOString(),
      activityTimeRange,
      purposeDescription: reservationFormStore.purposeText || 'Reservation',
      activityType: reservationFormStore.activityNameTitle || 'Activity',
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
</script>
