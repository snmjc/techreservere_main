<template>
  <AdminSidebarLayoutComponent
    :role-label="isAdmin ? 'SUPPORT' : ''"
    :navigation-items="navigationItems"
  >
    <section class="support-center">
      <header class="support-center__hero">
        <div>
          <p class="support-center__eyebrow">Support Center</p>
          <h1>Feedback and Issue Reporting</h1>
          <p class="support-center__copy">
            Share suggestions, report system issues, and track damage reports in one place.
          </p>
        </div>
      </header>

      <div class="support-center__tabs">
        <button
          v-for="tab in availableTabs"
          :key="tab.value"
          type="button"
          class="support-center__tab"
          :class="{ 'is-active': activeTab === tab.value }"
          @click="activeTab = tab.value"
        >
          {{ tab.label }}
        </button>
      </div>

      <div v-if="pageError" class="support-center__alert support-center__alert--error">{{ pageError }}</div>
      <div v-if="pageSuccess" class="support-center__alert support-center__alert--success">{{ pageSuccess }}</div>

      <div class="support-center__grid">
        <section v-if="activeTab === 'feedback'" class="support-center__panel">
          <div class="support-center__panel-head">
            <div>
              <h2>Feedback Channel</h2>
              <p>Submit product issues, feature requests, and usability feedback.</p>
            </div>
          </div>

          <form class="support-center__form" @submit.prevent="submitFeedback">
            <label class="support-center__field">
              <span>Category</span>
              <select v-model="feedbackForm.category">
                <option v-for="option in feedbackCategoryOptions" :key="option" :value="option">{{ option }}</option>
              </select>
            </label>

            <label class="support-center__field">
              <span>Subject</span>
              <input v-model.trim="feedbackForm.subjectLine" type="text" maxlength="160" placeholder="What should we look into?" />
            </label>

            <label class="support-center__field support-center__field--full">
              <span>Details</span>
              <textarea v-model.trim="feedbackForm.messageBody" rows="5" maxlength="2000" placeholder="Describe the issue, suggestion, or workflow pain point."></textarea>
            </label>

            <button class="support-center__primary-button" type="submit" :disabled="isSubmittingFeedback">
              {{ isSubmittingFeedback ? 'Submitting...' : 'Submit Feedback' }}
            </button>
          </form>

          <div class="support-center__records">
            <article v-for="record in feedbackRecords" :key="record.feedbackIdentifier" class="support-center__record">
              <div class="support-center__record-head">
                <div>
                  <strong>{{ record.subjectLine }}</strong>
                  <p>{{ record.category }} · {{ formatDate(record.createdTimestamp) }}</p>
                </div>
                <span class="support-center__status-pill">{{ record.currentStatus }}</span>
              </div>
              <p class="support-center__record-body">{{ record.messageBody }}</p>
              <div v-if="isAdmin" class="support-center__admin-actions">
                <select :value="record.currentStatus" @change="updateFeedbackStatus(record, $event.target.value)">
                  <option v-for="option in feedbackStatusOptions" :key="option" :value="option">{{ option }}</option>
                </select>
              </div>
              <p v-if="record.adminNotes" class="support-center__admin-note">Admin note: {{ record.adminNotes }}</p>
            </article>
            <p v-if="feedbackRecords.length === 0" class="support-center__empty">No feedback submissions yet.</p>
          </div>
        </section>

        <section v-if="activeTab === 'damage'" class="support-center__panel">
          <div class="support-center__panel-head">
            <div>
              <h2>Damage Reporting</h2>
              <p>Report damaged facilities and equipment with optional image evidence.</p>
            </div>
          </div>

          <form class="support-center__form" @submit.prevent="submitDamageReport">
            <label class="support-center__field">
              <span>Resource Type</span>
              <select v-model="damageForm.resourceType">
                <option v-for="option in damageResourceTypes" :key="option" :value="option">{{ option }}</option>
              </select>
            </label>

            <label class="support-center__field">
              <span>Issue Type</span>
              <select v-model="damageForm.issueType">
                <option v-for="option in damageIssueTypes" :key="option" :value="option">{{ option }}</option>
              </select>
            </label>

            <label class="support-center__field">
              <span>Resource Name</span>
              <input v-model.trim="damageForm.resourceName" type="text" maxlength="160" placeholder="Room 1203, Projector A, Speaker Set..." />
            </label>

            <label class="support-center__field support-center__field--full">
              <span>Description</span>
              <textarea v-model.trim="damageForm.descriptionText" rows="5" maxlength="2000" placeholder="Explain what is damaged, missing, or unsafe."></textarea>
            </label>

            <label class="support-center__field support-center__field--full">
              <span>Optional photo</span>
              <input type="file" accept="image/png,image/jpeg,image/webp" @change="handleDamageImageChange" />
              <small v-if="damageImageName">{{ damageImageName }}</small>
            </label>

            <button class="support-center__primary-button" type="submit" :disabled="isSubmittingDamageReport">
              {{ isSubmittingDamageReport ? 'Submitting...' : 'Submit Damage Report' }}
            </button>
          </form>

          <div class="support-center__records">
            <article v-for="record in damageReports" :key="record.damageReportIdentifier" class="support-center__record">
              <div class="support-center__record-head">
                <div>
                  <strong>{{ record.resourceName }}</strong>
                  <p>{{ record.resourceType }} · {{ record.issueType }} · {{ formatDate(record.createdTimestamp) }}</p>
                </div>
                <span class="support-center__status-pill">{{ record.currentStatus }}</span>
              </div>
              <p class="support-center__record-body">{{ record.descriptionText }}</p>
              <img v-if="record.imageData" :src="record.imageData" alt="Damage report preview" class="support-center__image-preview" />
              <div v-if="isAdmin" class="support-center__admin-actions">
                <select :value="record.currentStatus" @change="updateDamageReportStatus(record, $event.target.value)">
                  <option v-for="option in damageStatusOptions" :key="option" :value="option">{{ option }}</option>
                </select>
              </div>
              <p v-if="record.adminNotes" class="support-center__admin-note">Admin note: {{ record.adminNotes }}</p>
            </article>
            <p v-if="damageReports.length === 0" class="support-center__empty">No damage reports yet.</p>
          </div>
        </section>
      </div>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { supportApi } from '@/modules/support/services/supportApi.js';

const authStore = useAuthenticationStore();
const isAdmin = computed(() => authStore.userRole === 'ROLE_ADMIN' || authStore.userRole === 'ROLE_DEVELOPER');
const navigationItems = computed(() => (isAdmin.value ? adminNavigationItems : borrowerNavigationItems));

const activeTab = ref('feedback');
const pageError = ref('');
const pageSuccess = ref('');
const isSubmittingFeedback = ref(false);
const isSubmittingDamageReport = ref(false);
const feedbackRecords = ref([]);
const damageReports = ref([]);
const damageImageName = ref('');

const feedbackCategoryOptions = ['General', 'Bug Report', 'Feature Request', 'Usability', 'Access Issue'];
const feedbackStatusOptions = ['Submitted', 'In Review', 'Resolved', 'Closed'];
const damageResourceTypes = ['Venue', 'Equipment', 'Facility', 'Other'];
const damageIssueTypes = ['Damage', 'Missing Parts', 'Cleanliness', 'Safety Concern', 'Other'];
const damageStatusOptions = ['Submitted', 'In Review', 'Scheduled', 'Resolved', 'Closed'];

const feedbackForm = reactive({
  category: 'General',
  subjectLine: '',
  messageBody: '',
});

const damageForm = reactive({
  resourceType: 'Venue',
  issueType: 'Damage',
  resourceName: '',
  descriptionText: '',
  imageData: '',
});

const availableTabs = computed(() => [
  { label: isAdmin.value ? 'Feedback Queue' : 'Feedback', value: 'feedback' },
  { label: isAdmin.value ? 'Damage Reports Queue' : 'Damage Reports', value: 'damage' },
]);

onMounted(async () => {
  await Promise.all([loadFeedback(), loadDamageReports()]);
});

async function loadFeedback() {
  const response = await supportApi.listFeedback();
  feedbackRecords.value = Array.isArray(response?.data?.feedback) ? response.data.feedback : [];
}

async function loadDamageReports() {
  const response = await supportApi.listDamageReports();
  damageReports.value = Array.isArray(response?.data?.damageReports) ? response.data.damageReports : [];
}

async function submitFeedback() {
  pageError.value = '';
  pageSuccess.value = '';
  isSubmittingFeedback.value = true;

  try {
    await supportApi.createFeedback({ ...feedbackForm });
    feedbackForm.category = 'General';
    feedbackForm.subjectLine = '';
    feedbackForm.messageBody = '';
    pageSuccess.value = 'Feedback submitted successfully.';
    await loadFeedback();
  } catch (error) {
    pageError.value = error?.response?.data?.errorMessage || 'Unable to submit feedback right now.';
  } finally {
    isSubmittingFeedback.value = false;
  }
}

async function submitDamageReport() {
  pageError.value = '';
  pageSuccess.value = '';
  isSubmittingDamageReport.value = true;

  try {
    await supportApi.createDamageReport({ ...damageForm });
    damageForm.resourceType = 'Venue';
    damageForm.issueType = 'Damage';
    damageForm.resourceName = '';
    damageForm.descriptionText = '';
    damageForm.imageData = '';
    damageImageName.value = '';
    pageSuccess.value = 'Damage report submitted successfully.';
    await loadDamageReports();
  } catch (error) {
    pageError.value = error?.response?.data?.errorMessage || 'Unable to submit damage report right now.';
  } finally {
    isSubmittingDamageReport.value = false;
  }
}

async function updateFeedbackStatus(record, status) {
  pageError.value = '';
  try {
    await supportApi.updateFeedbackStatus(record.feedbackIdentifier, {
      status,
      adminNotes: record.adminNotes || '',
    });
    await loadFeedback();
  } catch (error) {
    pageError.value = error?.response?.data?.errorMessage || 'Unable to update feedback status.';
  }
}

async function updateDamageReportStatus(record, status) {
  pageError.value = '';
  try {
    await supportApi.updateDamageReportStatus(record.damageReportIdentifier, {
      status,
      adminNotes: record.adminNotes || '',
    });
    await loadDamageReports();
  } catch (error) {
    pageError.value = error?.response?.data?.errorMessage || 'Unable to update damage report status.';
  }
}

async function handleDamageImageChange(event) {
  const selectedFile = event.target.files?.[0];
  if (!selectedFile) {
    damageForm.imageData = '';
    damageImageName.value = '';
    return;
  }

  damageImageName.value = selectedFile.name;
  damageForm.imageData = await readFileAsDataUrl(selectedFile);
}

function readFileAsDataUrl(file) {
  return new Promise((resolve, reject) => {
    const fileReader = new FileReader();
    fileReader.onload = () => resolve(String(fileReader.result || ''));
    fileReader.onerror = () => reject(new Error('Failed to read file.'));
    fileReader.readAsDataURL(file);
  });
}

function formatDate(value) {
  if (!value) return 'Unknown date';
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  }).format(new Date(value));
}
</script>

<style scoped>
.support-center {
  width: min(1180px, 100%);
  margin: 0 auto;
  display: grid;
  gap: 1rem;
}

.support-center__hero,
.support-center__panel {
  background: #fff;
  border: 1px solid #dfe9e3;
  border-radius: 16px;
  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.05);
}

.support-center__hero {
  padding: 1.4rem 1.5rem;
}

.support-center__eyebrow {
  margin: 0 0 0.35rem;
  color: #15803d;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.support-center__hero h1,
.support-center__panel h2 {
  margin: 0;
  color: #173122;
}

.support-center__copy,
.support-center__panel-head p,
.support-center__record-head p,
.support-center__empty {
  margin: 0.3rem 0 0;
  color: #5f7267;
}

.support-center__tabs {
  display: flex;
  gap: 0.75rem;
}

.support-center__tab,
.support-center__primary-button {
  min-height: 42px;
  border-radius: 12px;
  font: inherit;
  font-weight: 800;
  cursor: pointer;
}

.support-center__tab {
  padding: 0 1rem;
  color: #355043;
  background: #eef4f1;
  border: 1px solid #d7e3db;
}

.support-center__tab.is-active,
.support-center__primary-button {
  color: #fff;
  background: linear-gradient(135deg, #15803d, #116530);
  border: 1px solid #116530;
}

.support-center__grid {
  display: grid;
  gap: 1rem;
}

.support-center__panel {
  padding: 1.2rem 1.25rem;
  display: grid;
  gap: 1rem;
}

.support-center__form {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem 1rem;
}

.support-center__field {
  display: grid;
  gap: 0.4rem;
}

.support-center__field--full {
  grid-column: 1 / -1;
}

.support-center__field span {
  color: #355043;
  font-size: 0.8rem;
  font-weight: 800;
}

.support-center__field input,
.support-center__field select,
.support-center__field textarea {
  width: 100%;
  min-height: 42px;
  padding: 0.75rem 0.85rem;
  border: 1px solid #d3e2d8;
  border-radius: 12px;
  box-sizing: border-box;
  font: inherit;
}

.support-center__records {
  display: grid;
  gap: 0.85rem;
}

.support-center__record {
  padding: 1rem;
  border: 1px solid #dce7e0;
  border-radius: 14px;
  background: #fbfdfc;
}

.support-center__record-head {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
}

.support-center__record-body,
.support-center__admin-note {
  margin: 0.7rem 0 0;
  color: #294034;
  white-space: pre-wrap;
}

.support-center__status-pill {
  align-self: flex-start;
  padding: 0.35rem 0.7rem;
  color: #116530;
  background: #ebf7ee;
  border-radius: 999px;
  font-size: 0.76rem;
  font-weight: 800;
}

.support-center__admin-actions {
  margin-top: 0.8rem;
}

.support-center__admin-actions select {
  min-height: 40px;
  padding: 0 0.7rem;
  border: 1px solid #d3e2d8;
  border-radius: 10px;
  font: inherit;
}

.support-center__alert {
  padding: 0.85rem 1rem;
  border-radius: 12px;
  font-weight: 700;
}

.support-center__alert--error {
  color: #9f1239;
  background: #fff1f2;
}

.support-center__alert--success {
  color: #166534;
  background: #ecfdf3;
}

.support-center__image-preview {
  width: min(220px, 100%);
  margin-top: 0.8rem;
  border-radius: 12px;
  border: 1px solid #dce7e0;
}

@media (max-width: 760px) {
  .support-center__form {
    grid-template-columns: 1fr;
  }

  .support-center__tabs,
  .support-center__record-head {
    flex-direction: column;
  }
}
</style>
