<!-- ===== AI GENERATED: BorrowerCreateReservationDocumentsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'DELA CRUZ, JUAN'"
    :navigation-items="borrowerNavigationItems"
  >
    <!-- Page Header -->
    <div class="create-reservation-docs-page-header">
      <h2 class="create-reservation-docs-page-heading">Create Reservation</h2>
    </div>

    <!-- Form Subtitle -->
    <p class="create-reservation-docs-form-subtitle">{{ formSubtitle }}</p>

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
          <div v-if="supportingDocumentsList.length > 0" class="create-reservation-docs-file-list">
            <div
              v-for="(supportingDocument, index) in supportingDocumentsList"
              :key="supportingDocument.documentFileName"
              class="create-reservation-docs-file-item"
            >
              <span class="create-reservation-docs-file-bullet"></span>
              <span class="create-reservation-docs-file-name">{{ supportingDocument.documentFileName }}</span>
              <button class="create-reservation-docs-file-remove" @click="removeSupportingDocument(index)" aria-label="Remove file">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
          </div>
          <p v-else class="create-reservation-docs-no-files">No files uploaded yet.</p>
          <input ref="supportingFileInput" type="file" multiple accept=".pdf,.doc,.docx,.jpg,.png" style="display:none" @change="onSupportingFilesSelected" />
          <button class="create-reservation-docs-upload-button" @click="$refs.supportingFileInput.click()">
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
          <div v-if="recommendationDocumentsList.length > 0" class="create-reservation-docs-file-list">
            <div
              v-for="(recommendationDocument, index) in recommendationDocumentsList"
              :key="recommendationDocument.documentFileName"
              class="create-reservation-docs-file-item"
            >
              <span class="create-reservation-docs-file-bullet"></span>
              <span class="create-reservation-docs-file-name">{{ recommendationDocument.documentFileName }}</span>
              <button class="create-reservation-docs-file-remove" @click="removeRecommendationDocument(index)" aria-label="Remove file">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
          </div>
          <p v-else class="create-reservation-docs-no-files">No files uploaded yet.</p>
          <input ref="recommendationFileInput" type="file" multiple accept=".pdf,.doc,.docx,.jpg,.png" style="display:none" @change="onRecommendationFilesSelected" />
          <button class="create-reservation-docs-upload-button" @click="$refs.recommendationFileInput.click()">
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

      <!-- Navigation & Submit Buttons -->
      <div class="create-reservation-docs-form-actions">
        <button class="create-reservation-docs-prev-button" @click="navigateToPreviousPage">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"/>
          </svg>
          Previous Page
        </button>
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
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/borrowerCreateReservationDocumentsPage.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { useReservationFormStore } from '@/modules/reservation/store/reservationFormStore.js';

const router = useRouter();
const reservationFormStore = useReservationFormStore();

const formSubtitle = computed(() => {
  const type = reservationFormStore.reservationType;
  if (type === 'Venue') return 'Venue Reservation Form';
  if (type === 'Equipment') return 'Equipment Reservation Form';
  return 'Venue and Equipment Reservation Form';
});

const documentsFormState = ref({
  securityGuardCount: 'None',
  securityCrewCount: 'None',
  documentType: 'Reservation',
});

const supportingDocumentsList = ref([]);
const recommendationDocumentsList = ref([]);

function onSupportingFilesSelected(event) {
  const files = event.target.files;
  for (const file of files) {
    supportingDocumentsList.value.push({ documentFileName: file.name, file });
  }
  event.target.value = '';
}

function onRecommendationFilesSelected(event) {
  const files = event.target.files;
  for (const file of files) {
    recommendationDocumentsList.value.push({ documentFileName: file.name, file });
  }
  event.target.value = '';
}

function removeSupportingDocument(index) {
  supportingDocumentsList.value.splice(index, 1);
}

function removeRecommendationDocument(index) {
  recommendationDocumentsList.value.splice(index, 1);
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
