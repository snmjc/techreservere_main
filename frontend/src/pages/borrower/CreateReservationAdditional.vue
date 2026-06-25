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
        <BorrowerReservationStepper :current-step="3" />

        <section class="borrower-reservation-card">
          <div class="borrower-reservation-panel">
            <h2>Additional Information</h2>
            <p>Provide additional details and manpower requirements for your reservation.</p>

            <div class="borrower-reservation-grid">
              <div class="borrower-reservation-field">
                <label for="securityGuardCount">Security Guard</label>
                <select id="securityGuardCount" v-model="formState.securityGuardCount">
                  <option value="None">None</option>
                  <option value="1 Guard">1 Guard</option>
                  <option value="2 Guards">2 Guards</option>
                  <option value="3 Guards">3 Guards</option>
                </select>
                <small class="borrower-reservation-help">Select if security guard is required for your event.</small>
              </div>

              <div class="borrower-reservation-note">
                <strong>Note</strong>
                <p>Additional manpower requests may be subject to availability and additional fees.</p>
              </div>

              <div class="borrower-reservation-field">
                <label for="securityCrewCount">Security Crew</label>
                <select id="securityCrewCount" v-model="formState.securityCrewCount">
                  <option value="None">None</option>
                  <option value="1 Crew">1 Crew</option>
                  <option value="2 Crew">2 Crew</option>
                  <option value="3 Crew">3 Crew</option>
                </select>
                <small class="borrower-reservation-help">Select if security crew is required for your event.</small>
              </div>

              <div class="borrower-reservation-field borrower-reservation-field--full">
                <label for="borrowerRemarks">Remarks</label>
                <textarea
                  id="borrowerRemarks"
                  v-model="formState.borrowerRemarks"
                  rows="4"
                  maxlength="500"
                  placeholder="Add any optional notes or special instructions for your reservation."
                ></textarea>
                <small class="borrower-reservation-help">Optional. This will be included in your reservation summary and request details.</small>
              </div>
            </div>
          </div>

          <footer class="borrower-reservation-actions">
            <button class="borrower-reservation-button borrower-reservation-button--secondary" type="button" @click="navigateToPreviousPage">
              Previous
            </button>
            <button class="borrower-reservation-button borrower-reservation-button--primary" type="button" :disabled="isStepLoading" @click="navigateToNextPage">
              {{ isStepLoading ? 'Loading...' : 'Next: Supporting Documents' }}
            </button>
          </footer>
        </section>
      </div>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import BorrowerReservationStepper from '@/modules/reservation/components/BorrowerReservationStepper.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/CreateReservationWizard.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { useReservationFormStore } from '@/modules/reservation/store/reservationFormStore.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';

const router = useRouter();
const reservationFormStore = useReservationFormStore();
const isStepLoading = ref(false);

const formState = reactive({
  securityGuardCount: reservationFormStore.securityGuardCount || 'None',
  securityCrewCount: reservationFormStore.securityCrewCount || 'None',
  borrowerRemarks: reservationFormStore.borrowerRemarks || '',
});

onMounted(() => {
  if (!reservationFormStore.hasReservationDetails() || !reservationFormStore.hasSelectionForCurrentType()) {
    router.replace({ name: ROUTE_NAMES.borrowerCreateReservation });
  }
});

function navigateToPreviousPage() {
  router.push({ name: 'borrowerCreateReservationVenuePage' });
}

function navigateToNextPage() {
  reservationFormStore.securityGuardCount = formState.securityGuardCount;
  reservationFormStore.securityCrewCount = formState.securityCrewCount;
  reservationFormStore.borrowerRemarks = String(formState.borrowerRemarks || '').trim();
  reservationFormStore.persistForm();
  isStepLoading.value = true;
  window.setTimeout(() => {
    router.push({ name: 'borrowerCreateReservationDocumentsPage' });
  }, 250);
}
</script>
