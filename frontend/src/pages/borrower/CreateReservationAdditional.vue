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
            </div>
          </div>

          <footer class="borrower-reservation-actions">
            <button class="borrower-reservation-button borrower-reservation-button--secondary" type="button" @click="navigateToPreviousPage">
              Previous
            </button>
            <button class="borrower-reservation-button borrower-reservation-button--primary" type="button" @click="navigateToNextPage">
              Next: Supporting Documents
            </button>
          </footer>
        </section>
      </div>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { reactive } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import BorrowerReservationStepper from '@/modules/reservation/components/BorrowerReservationStepper.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/CreateReservationWizard.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { useReservationFormStore } from '@/modules/reservation/store/reservationFormStore.js';

const router = useRouter();
const reservationFormStore = useReservationFormStore();

const formState = reactive({
  securityGuardCount: reservationFormStore.securityGuardCount || 'None',
  securityCrewCount: reservationFormStore.securityCrewCount || 'None',
});

function navigateToPreviousPage() {
  router.push({ name: 'borrowerCreateReservationVenuePage' });
}

function navigateToNextPage() {
  reservationFormStore.securityGuardCount = formState.securityGuardCount;
  reservationFormStore.securityCrewCount = formState.securityCrewCount;
  router.push({ name: 'borrowerCreateReservationDocumentsPage' });
}
</script>
