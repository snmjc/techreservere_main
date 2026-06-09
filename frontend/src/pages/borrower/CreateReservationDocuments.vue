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
        <BorrowerReservationStepper :current-step="4" />

        <section class="borrower-reservation-card">
          <div class="borrower-reservation-panel">
            <h2>Supporting Documents</h2>
            <p>Upload the necessary documents to support your reservation request.</p>

            <div class="reservation-documents-layout">
              <section class="reservation-documents-required">
                <h3>Required Documents</h3>
                <article v-for="documentItem in requiredDocumentItems" :key="documentItem.key" class="reservation-document-item">
                  <div>
                    <strong>{{ documentItem.label }}</strong>
                    <span>{{ getDocumentFileName(documentItem.key) }}</span>
                  </div>
                  <button type="button" @click="triggerFileInput(documentItem.key)">Upload</button>
                  <input :ref="(node) => setFileInputRef(documentItem.key, node)" type="file" hidden @change="handleRequiredDocumentUpload(documentItem.key, $event)" />
                </article>
              </section>

              <section class="reservation-documents-dropzone">
                <h3>Upload Additional Documents (Optional)</h3>
                <div class="reservation-documents-dropzone-box" @click="triggerOptionalInput">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/></svg>
                  <p>Drag and drop files here or click to browse</p>
                  <small>PDF, DOC, DOCX, JPG, PNG. Max 10MB per file.</small>
                </div>
                <input ref="optionalInputRef" type="file" hidden multiple @change="handleOptionalDocumentsUpload" />
                <div v-if="additionalDocuments.length" class="reservation-documents-optional-list">
                  <span v-for="documentFile in additionalDocuments" :key="documentFile.documentFileName">{{ documentFile.documentFileName }}</span>
                </div>
                <div class="borrower-reservation-note">
                  <strong>Note</strong>
                  <p>Please ensure all uploaded files are clear and complete.</p>
                </div>
              </section>
            </div>
          </div>

          <footer class="borrower-reservation-actions">
            <button class="borrower-reservation-button borrower-reservation-button--secondary" type="button" @click="navigateToPreviousPage">
              Previous
            </button>
            <button class="borrower-reservation-button borrower-reservation-button--primary" type="button" @click="navigateToNextPage">
              Next: Review Summary
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
import './css/CreateReservationDocuments.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { useReservationFormStore } from '@/modules/reservation/store/reservationFormStore.js';

const router = useRouter();
const reservationFormStore = useReservationFormStore();
const optionalInputRef = ref(null);
const fileInputRefs = ref({});

const requiredDocumentItems = [
  { key: 'proposal', label: 'Activity Proposal' },
  { key: 'materials', label: 'List of Materials (if any)' },
  { key: 'participants', label: 'List of Participants' },
  { key: 'recommendation', label: 'Director/Professor Recommendation' },
];

const supportingDocuments = computed(() => reservationFormStore.supportingDocumentsList || []);
const recommendationDocuments = computed(() => reservationFormStore.recommendationDocumentsList || []);
const additionalDocuments = computed(() => reservationFormStore.additionalDocumentsList || []);

function setFileInputRef(key, node) {
  if (!node) return;
  fileInputRefs.value[key] = node;
}

function triggerFileInput(key) {
  fileInputRefs.value[key]?.click();
}

function triggerOptionalInput() {
  optionalInputRef.value?.click();
}

function handleRequiredDocumentUpload(key, event) {
  const [file] = event.target.files || [];
  if (!file) return;

  const payload = { documentKey: key, documentFileName: file.name };

  if (key === 'recommendation') {
    reservationFormStore.recommendationDocumentsList = [payload];
  } else {
    const remainingItems = supportingDocuments.value.filter((item) => item.documentKey !== key);
    reservationFormStore.supportingDocumentsList = [...remainingItems, payload];
  }

  event.target.value = '';
}

function handleOptionalDocumentsUpload(event) {
  const files = Array.from(event.target.files || []);
  reservationFormStore.additionalDocumentsList = files.map((file) => ({
    documentFileName: file.name,
  }));
  event.target.value = '';
}

function getDocumentFileName(key) {
  if (key === 'recommendation') {
    return recommendationDocuments.value[0]?.documentFileName || 'No file uploaded yet';
  }

  return supportingDocuments.value.find((item) => item.documentKey === key)?.documentFileName || 'No file uploaded yet';
}

function navigateToPreviousPage() {
  router.push({ name: 'borrowerCreateReservationAdditionalPage' });
}

function navigateToNextPage() {
  router.push({ name: 'borrowerCreateReservationSummaryPage' });
}
</script>
