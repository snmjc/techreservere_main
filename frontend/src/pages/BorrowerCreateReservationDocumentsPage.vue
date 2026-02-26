<!-- ===== AI GENERATED: BorrowerCreateReservationDocumentsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'DELA CRUZ, JUAN'"
    :navigation-items="borrowerNavigationItems"
  >
    <!-- Page Header -->
    <div class="create-reservation-docs-page-header">
      <h2 class="create-reservation-docs-page-heading">Create Reservation</h2>
      <span class="create-reservation-docs-prev-link" @click="navigateToPreviousPage">Previous Page</span>
    </div>

    <!-- Form Subtitle -->
    <p class="create-reservation-docs-form-subtitle">Venue and Equipment Reservation Form</p>

    <!-- Form Card -->
    <div class="create-reservation-docs-form-card">

      <!-- Additional Fee Note -->
      <p class="create-reservation-docs-additional-fee-note">Subject to additional fees</p>

      <!-- Additional Manpower -->
      <div class="create-reservation-docs-section">
        <div class="create-reservation-docs-section-label">Additional Manpower</div>
        <div class="create-reservation-docs-section-content">
          <div class="create-reservation-docs-manpower-row">
            <span class="create-reservation-docs-manpower-label">Security Guard:</span>
            <input
              v-model="documentsFormState.securityGuardCount"
              type="text"
              class="create-reservation-docs-manpower-input"
              placeholder="None"
            />
          </div>
          <div class="create-reservation-docs-manpower-row">
            <span class="create-reservation-docs-manpower-label">Security Crew:</span>
            <input
              v-model="documentsFormState.securityCrewCount"
              type="text"
              class="create-reservation-docs-manpower-input"
              placeholder="None"
            />
          </div>
        </div>
      </div>

      <!-- Supporting Documents -->
      <div class="create-reservation-docs-section">
        <div class="create-reservation-docs-section-label">
          Supporting Documents
          <span class="create-reservation-docs-section-sublabel">Activity Proposal</span>
          <span class="create-reservation-docs-section-sublabel">List of Materials (if any)</span>
          <span class="create-reservation-docs-section-sublabel">List of Participants</span>
        </div>
        <div class="create-reservation-docs-section-content">
          <div class="create-reservation-docs-file-list">
            <div
              v-for="supportingDocument in supportingDocumentsList"
              :key="supportingDocument.documentFileName"
              class="create-reservation-docs-file-item"
            >
              <span class="create-reservation-docs-file-bullet"></span>
              <span class="create-reservation-docs-file-name">{{ supportingDocument.documentFileName }}</span>
            </div>
          </div>
          <button class="create-reservation-docs-upload-button" @click="handleUploadSupportingDocument">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><polyline points="17 8 12 3 7 8" /><line x1="12" y1="3" x2="12" y2="15" />
            </svg>
            Upload Files
          </button>
        </div>
      </div>

      <!-- Recommended By -->
      <div class="create-reservation-docs-section">
        <div class="create-reservation-docs-section-label">
          Recommended By
          <span class="create-reservation-docs-section-sublabel">Director/Professor Recommendation</span>
        </div>
        <div class="create-reservation-docs-section-content">
          <div class="create-reservation-docs-file-list">
            <div
              v-for="recommendationDocument in recommendationDocumentsList"
              :key="recommendationDocument.documentFileName"
              class="create-reservation-docs-file-item"
            >
              <span class="create-reservation-docs-file-bullet"></span>
              <span class="create-reservation-docs-file-name">{{ recommendationDocument.documentFileName }}</span>
            </div>
          </div>
          <button class="create-reservation-docs-upload-button" @click="handleUploadRecommendationDocument">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><polyline points="17 8 12 3 7 8" /><line x1="12" y1="3" x2="12" y2="15" />
            </svg>
            Upload Files
          </button>
        </div>
      </div>

      <!-- Document Type -->
      <div class="create-reservation-docs-section">
        <div class="create-reservation-docs-section-label">Document Type</div>
        <div class="create-reservation-docs-section-content">
          <select
            v-model="documentsFormState.documentType"
            class="create-reservation-docs-type-select"
          >
            <option value="Reservation">Reservation</option>
            <option value="Request">Request</option>
          </select>
        </div>
      </div>

      <!-- Submit Button -->
      <div class="create-reservation-docs-submit-row">
        <button class="create-reservation-docs-submit-button" @click="handleSubmitReservationRequest">
          <span class="create-reservation-docs-submit-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12" />
            </svg>
          </span>
          Submit Request
        </button>
      </div>
    </div>

    <!-- Footer -->
    <div class="create-reservation-docs-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/borrowerCreateReservationDocumentsPage.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';

const router = useRouter();

const documentsFormState = ref({
  securityGuardCount: 'None',
  securityCrewCount: 'None',
  documentType: 'Reservation',
});

/**
 * @constant {Array<Object>} supportingDocumentsList
 * @description Static supporting documents for demonstration.
 */
const supportingDocumentsList = ref([
  { documentFileName: 'DelaCruz_F704_APP.pdf' },
  { documentFileName: 'DelaCruz_ListofMaterials.pdf' },
  { documentFileName: 'DelaCruz_ListofParticipants.pdf' },
]);

/**
 * @constant {Array<Object>} recommendationDocumentsList
 * @description Static recommendation documents for demonstration.
 */
const recommendationDocumentsList = ref([
  { documentFileName: 'DelaCruz_ProfRecommendation.pdf' },
]);

/**
 * @function handleUploadSupportingDocument
 * @description Handles uploading supporting documents (placeholder).
 * @returns {void}
 */
function handleUploadSupportingDocument() {
  console.log('Upload supporting document triggered');
}

/**
 * @function handleUploadRecommendationDocument
 * @description Handles uploading recommendation documents (placeholder).
 * @returns {void}
 */
function handleUploadRecommendationDocument() {
  console.log('Upload recommendation document triggered');
}

/**
 * @function handleSubmitReservationRequest
 * @description Handles the reservation request submission (placeholder).
 * @returns {void}
 */
function handleSubmitReservationRequest() {
  console.log('Submit reservation request:', documentsFormState.value);
}

/**
 * @function navigateToPreviousPage
 * @description Navigates back to page 2 (venue selection) of the create reservation flow.
 * @returns {void}
 */
function navigateToPreviousPage() {
  router.push({ name: 'borrowerCreateReservationVenuePage' });
}
</script>
