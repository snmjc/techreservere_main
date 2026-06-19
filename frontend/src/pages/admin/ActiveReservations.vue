<!-- ===== AI GENERATED: AdminActiveReservationsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="admin-ops-page active-reservations-page">
      <header class="admin-ops-header">
        <div class="admin-ops-header-copy">
          <p class="admin-ops-kicker">Deployment Tracking</p>
          <h1>Active Reservations</h1>
          <p>Track reservations currently in use, open deployment details, confirm returns, and flag issues when needed.</p>
        </div>
      </header>

      <div class="admin-ops-filter-card">
        <div class="admin-ops-toolbar active-reservations-toolbar">
          <label class="admin-ops-field">
            <span>Search</span>
            <input
              id="activeResSearchInput"
              v-model="searchQueryText"
              type="text"
              class="active-reservations-search-input"
              placeholder="Requester name or request ID"
            />
          </label>
          <label class="admin-ops-field">
            <span>Showing</span>
            <select
              id="activeResShowingSelect"
              v-model="showingFilterValue"
              class="active-reservations-showing-select"
            >
              <option value="all">All</option>
              <option value="venue">Venue</option>
              <option value="equipment">Equipment</option>
              <option value="both">Both</option>
            </select>
          </label>
          <button class="admin-ops-sort-button active-reservations-sort-button" aria-label="Sort">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"/>
              <polyline points="19 12 12 19 5 12"/>
            </svg>
          </button>
        </div>
      </div>

      <div class="admin-ops-table-card">
        <ReservationActiveTableComponent
          :reservation-list="activeReservationsList"
          :search-query-text="searchQueryText"
          @view-deployment-details="handleViewDeploymentDetails"
          @return-confirmation="handleReturnConfirmation"
          @report-reservation="handleReportReservation"
        />
      </div>

      <div class="admin-ops-page-footer active-reservations-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </div>
    </section>

    <!-- Process Deployment Modal -->
    <ReservationDeploymentModalComponent
      :reservation-record="selectedReservationRecord"
      @close-deployment-modal="handleCloseDeploymentModal"
      @confirm-return-record="handleConfirmReturn"
      @report-reservation-record="handleReportReservation"
    />

    <div v-if="confirmReservationRecord" class="active-reservation-action-overlay" @click.self="closeConfirmModal">
      <section class="active-reservation-action-card">
        <header class="active-reservation-action-header">
          <div>
            <h2>Return Confirmation</h2>
            <p>Review the active reservation and confirm the return with remarks and administrator verification.</p>
          </div>
          <button class="active-reservation-action-close" type="button" aria-label="Close" @click="closeConfirmModal">&times;</button>
        </header>

        <div class="active-reservation-action-body">
          <section class="active-reservation-summary-card">
            <div class="active-reservation-summary-top">
              <div class="active-reservation-summary-requester">
                <span class="active-reservation-summary-avatar">{{ getRequesterInitials(confirmReservationRecord) }}</span>
                <div>
                  <strong>{{ confirmReservationRecord.requesterFullName || 'N/A' }}</strong>
                  <small>ID: {{ confirmReservationRecord.requesterId || confirmReservationRecord.requestIdentifier || 'N/A' }}</small>
                  <span class="active-reservation-summary-role">{{ confirmReservationRecord.requesterRole || 'Borrower' }}</span>
                </div>
              </div>

              <div class="active-reservation-summary-metric">
                <span>Request ID</span>
                <strong>{{ confirmReservationRecord.requestDisplayIdentifier || confirmReservationRecord.requestIdentifier }}</strong>
              </div>

              <div class="active-reservation-summary-metric">
                <span>Request Type</span>
                <strong>{{ confirmReservationRecord.requestType || 'N/A' }}</strong>
              </div>

              <div class="active-reservation-summary-metric">
                <span>Status</span>
                <strong>{{ confirmReservationRecord.deploymentStatus || confirmReservationRecord.requestStatus || 'Active' }}</strong>
              </div>
            </div>

            <div class="active-reservation-summary-bottom">
              <div class="active-reservation-summary-item">
                <span>Facility</span>
                <strong>{{ confirmReservationRecord.facilityName || 'N/A' }}</strong>
              </div>

              <div class="active-reservation-summary-item">
                <span>Schedule</span>
                <strong class="active-reservation-summary-stack">
                  <em>{{ confirmReservationRecord.activityDate || 'N/A' }}</em>
                  <em>{{ confirmReservationRecord.activityEndTime || confirmReservationRecord.requestSchedule || 'N/A' }}</em>
                </strong>
              </div>

              <div class="active-reservation-summary-item">
                <span>Purpose</span>
                <strong>{{ confirmReservationRecord.requestPurpose || 'N/A' }}</strong>
              </div>

              <div class="active-reservation-summary-item">
                <span>Quantity</span>
                <strong>{{ confirmReservationRecord.requestQuantity || 0 }}</strong>
              </div>
            </div>
          </section>

          <label class="active-reservation-action-field active-reservation-action-field--full">
            <span>Remarks</span>
            <textarea
              v-model.trim="confirmForm.remarks"
              maxlength="500"
              rows="4"
              placeholder="Enter any remarks or return notes..."
            />
            <small>{{ confirmForm.remarks.length }} / 500</small>
          </label>

          <div class="active-reservation-action-security">
            <h3>Admin Confirmation</h3>
            <p>Please verify your administrator account before confirming the return.</p>

            <div class="active-reservation-action-grid">
              <label class="active-reservation-action-field">
                <span>Admin Email</span>
                <input v-model.trim="confirmForm.adminEmail" type="email" :placeholder="currentAdminEmail || 'Enter your admin email'" />
              </label>

            </div>
          </div>
        </div>

        <footer class="active-reservation-action-footer">
          <button class="active-reservation-action-button active-reservation-action-button--ghost" type="button" @click="closeConfirmModal">Cancel</button>
          <button class="active-reservation-action-button active-reservation-action-button--confirm" type="button" @click="submitConfirmReturn">
            Return Confirmation
          </button>
        </footer>
      </section>
    </div>

    <div v-if="reportReservationRecord" class="active-reservation-action-overlay" @click.self="closeReportModal">
      <section class="active-reservation-action-card">
        <header class="active-reservation-action-header">
          <div>
            <h2>Report</h2>
            <p>Provide the incident details and administrator verification before reporting this active reservation.</p>
          </div>
          <button class="active-reservation-action-close" type="button" aria-label="Close" @click="closeReportModal">&times;</button>
        </header>

        <div class="active-reservation-action-body">
          <section class="active-reservation-summary-card">
            <div class="active-reservation-summary-top">
              <div class="active-reservation-summary-requester">
                <span class="active-reservation-summary-avatar">{{ getRequesterInitials(reportReservationRecord) }}</span>
                <div>
                  <strong>{{ reportReservationRecord.requesterFullName || 'N/A' }}</strong>
                  <small>ID: {{ reportReservationRecord.requesterId || reportReservationRecord.requestIdentifier || 'N/A' }}</small>
                  <span class="active-reservation-summary-role">{{ reportReservationRecord.requesterRole || 'Borrower' }}</span>
                </div>
              </div>

              <div class="active-reservation-summary-metric">
                <span>Request ID</span>
                <strong>{{ reportReservationRecord.requestDisplayIdentifier || reportReservationRecord.requestIdentifier }}</strong>
              </div>

              <div class="active-reservation-summary-metric">
                <span>Request Type</span>
                <strong>{{ reportReservationRecord.requestType || 'N/A' }}</strong>
              </div>

              <div class="active-reservation-summary-metric">
                <span>Status</span>
                <strong>{{ reportReservationRecord.deploymentStatus || reportReservationRecord.requestStatus || 'Active' }}</strong>
              </div>
            </div>

            <div class="active-reservation-summary-bottom">
              <div class="active-reservation-summary-item">
                <span>Facility</span>
                <strong>{{ reportReservationRecord.facilityName || 'N/A' }}</strong>
              </div>

              <div class="active-reservation-summary-item">
                <span>Schedule</span>
                <strong class="active-reservation-summary-stack">
                  <em>{{ reportReservationRecord.activityDate || 'N/A' }}</em>
                  <em>{{ reportReservationRecord.activityEndTime || reportReservationRecord.requestSchedule || 'N/A' }}</em>
                </strong>
              </div>

              <div class="active-reservation-summary-item">
                <span>Purpose</span>
                <strong>{{ reportReservationRecord.requestPurpose || 'N/A' }}</strong>
              </div>

              <div class="active-reservation-summary-item">
                <span>Quantity</span>
                <strong>{{ reportReservationRecord.requestQuantity || 0 }}</strong>
              </div>
            </div>
          </section>

          <label class="active-reservation-action-field active-reservation-action-field--full">
            <span>Remarks</span>
            <textarea
              v-model.trim="reportForm.remarks"
              maxlength="500"
              rows="4"
              placeholder="Describe the issue or report details..."
            />
            <small>{{ reportForm.remarks.length }} / 500</small>
          </label>

          <div class="active-reservation-action-security">
            <h3>Admin Confirmation</h3>
            <p>Please verify your administrator account before submitting this report.</p>

            <div class="active-reservation-action-grid">
              <label class="active-reservation-action-field">
                <span>Admin Email</span>
                <input v-model.trim="reportForm.adminEmail" type="email" :placeholder="currentAdminEmail || 'Enter your admin email'" />
              </label>

            </div>
          </div>
        </div>

        <footer class="active-reservation-action-footer">
          <button class="active-reservation-action-button active-reservation-action-button--ghost" type="button" @click="closeReportModal">Cancel</button>
          <button class="active-reservation-action-button active-reservation-action-button--report" type="button" @click="submitReportReservation">
            Report
          </button>
        </footer>
      </section>
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/ActiveReservations.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import ReservationActiveTableComponent from '@/modules/reservation/components/ReservationActiveTableComponent.vue';
import ReservationDeploymentModalComponent from '@/modules/reservation/components/ReservationDeploymentModalComponent.vue';
import '@/modules/reservation/components/reservationDeploymentModal.css';
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';

const authStore = useAuthenticationStore();
const requestStore = useRequestStore();
const searchQueryText = ref('');
const showingFilterValue = ref('all');
const selectedReservationRecord = ref(null);
const confirmReservationRecord = ref(null);
const reportReservationRecord = ref(null);
const confirmForm = reactive({
  remarks: '',
  adminEmail: '',
});
const reportForm = reactive({
  remarks: '',
  adminEmail: '',
});

const activeReservationsList = computed(() => requestStore.activeReservationsList || []);
const currentAdminEmail = computed(() => {
  const account = authStore.accountData || authStore.clerkAccountData || {};
  return String(account.emailAddress || account.email || '').trim().toLowerCase();
});

onMounted(async () => {
  try {
    await requestStore.fetchReservations();
    const list = requestStore.activeReservationsList || [];
    console.log('Admin Active Reservations - Count:', list.length);
  } catch (error) {
    console.error('Error fetching active reservations:', error);
  }
});

/**
 * @function handleViewDeploymentDetails
 * @description Opens the deployment modal with selected record.
 * @param {Object} reservationRecord - The reservation record to view
 * @returns {void}
 */
function handleViewDeploymentDetails(reservationRecord) {
  selectedReservationRecord.value = reservationRecord;
}

/**
 * @function handleCloseDeploymentModal
 * @description Closes the deployment modal.
 * @returns {void}
 */
function handleCloseDeploymentModal() {
  selectedReservationRecord.value = null;
}

/**
 * @function handleReturnConfirmation
 * @description Completes an active reservation → moves to Past Records as "Completed".
 * @param {Object} reservationRecord - The reservation record
 * @returns {void}
 */
function handleReturnConfirmation(reservationRecord) {
  confirmReservationRecord.value = reservationRecord;
}

/**
 * @function handleConfirmReturn
 * @description Completes an active reservation from the modal → moves to Past Records.
 * @param {Object} reservationRecord - The reservation record returned
 * @returns {void}
 */
function handleConfirmReturn(reservationRecord) {
  confirmReservationRecord.value = reservationRecord;
}

/**
 * @function handleReportReservation
 * @description Cancels an active reservation → moves to Past Records as "Cancelled".
 * @param {Object} reservationRecord - The reservation record to cancel
 * @returns {void}
 */
function handleReportReservation(reservationRecord) {
  reportReservationRecord.value = reservationRecord;
}

async function submitConfirmReturn() {
  if (!confirmReservationRecord.value) {
    return;
  }

  const emailError = validateAdminEmailConfirmation(confirmForm.adminEmail, 'confirm');
  if (emailError) {
    window.alert(emailError);
    return;
  }

  try {
    await requestStore.completeActiveReservation(
      confirmReservationRecord.value,
      confirmForm.remarks.trim(),
      {
        confirmedAdminEmail: normalizeEmailForConfirmation(confirmForm.adminEmail),
      },
    );
    closeConfirmModal();
    selectedReservationRecord.value = null;
  } catch (error) {
    window.alert(error?.message || 'Unable to confirm this return.');
  }
}

async function submitReportReservation() {
  if (!reportReservationRecord.value) {
    return;
  }

  if (reportForm.remarks.trim() === '') {
    window.alert('Please add remarks before submitting this report.');
    return;
  }

  const emailError = validateAdminEmailConfirmation(reportForm.adminEmail, 'report');
  if (emailError) {
    window.alert(emailError);
    return;
  }

  try {
    await requestStore.cancelActiveReservation(
      reportReservationRecord.value,
      reportForm.remarks.trim(),
      {
        confirmedAdminEmail: normalizeEmailForConfirmation(reportForm.adminEmail),
      },
    );
    closeReportModal();
    selectedReservationRecord.value = null;
  } catch (error) {
    window.alert(error?.message || 'Unable to submit this report.');
  }
}

function closeConfirmModal() {
  confirmReservationRecord.value = null;
  confirmForm.remarks = '';
  confirmForm.adminEmail = '';
}

function closeReportModal() {
  reportReservationRecord.value = null;
  reportForm.remarks = '';
  reportForm.adminEmail = '';
}

function validateAdminEmailConfirmation(emailValue, actionName) {
  const normalizedEmail = normalizeEmailForConfirmation(emailValue);
  if (normalizedEmail === '') {
    return `Please type your exact admin email before ${actionName === 'confirm' ? 'confirming this return' : 'submitting this report'}.`;
  }

  if (currentAdminEmail.value === '') {
    return 'Unable to verify the admin in charge. Please sign in again.';
  }

  if (normalizedEmail !== currentAdminEmail.value) {
    return `Please type your exact admin email before ${actionName === 'confirm' ? 'confirming this return' : 'submitting this report'}.`;
  }

  return '';
}

function normalizeEmailForConfirmation(emailValue) {
  return String(emailValue || '').trim().toLowerCase();
}

function getRequesterInitials(reservationRecord) {
  const name = String(reservationRecord?.requesterFullName || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('');

  return name || 'TR';
}
</script>
