<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="manage-accounts-page">
      <div v-if="toastMessage" class="manage-accounts-toast">
        {{ toastMessage }}
      </div>

      <header class="manage-accounts-header">
        <div>
          <h1>{{ pageTitle }}</h1>
          <p>{{ pageDescription }}</p>
        </div>
        <div class="manage-accounts-header-actions">
          <button
            v-if="activeAccountTab === 'employee'"
            class="manage-accounts-refresh-button"
            type="button"
            @click="openAddEmployeeRequestModal"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 5v14" />
              <path d="M5 12h14" />
            </svg>
            Add Account
          </button>
          <button
            class="manage-accounts-refresh-button"
            type="button"
            :disabled="isLoading"
            @click="handleRefreshAccounts"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
              <path d="M3 21v-5h5" />
              <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
              <path d="M16 8h5V3" />
            </svg>
            {{ isLoading ? 'Refreshing...' : 'Refresh' }}
          </button>
        </div>
      </header>

      <div v-if="loadErrorMessage" class="manage-accounts-error">
        {{ loadErrorMessage }}
      </div>

      <nav class="manage-accounts-tabs" aria-label="Account type tabs">
        <button
          v-for="tab in accountTabs"
          :key="tab.value"
          type="button"
          :class="{ 'manage-accounts-tab--active': activeAccountTab === tab.value }"
          @click="handleTabChange(tab.value)"
        >
          {{ tab.label }} <span>({{ tab.count }})</span>
        </button>
      </nav>

      <div class="manage-accounts-toolbar">
        <label>
          <span>Search:</span>
          <input v-model="searchQueryText" type="search" placeholder="Name" />
        </label>

        <label>
          <span>Sort by:</span>
          <select v-model="sortMode">
            <option value="created">Chronological</option>
            <option value="name">Name</option>
            <option value="role">Role</option>
            <option value="status">Account status</option>
          </select>
        </label>

        <label>
          <span>Status:</span>
          <select v-model="showingFilterValue">
            <option value="all">All</option>
            <option value="active">Active</option>
            <option value="pending">Pending</option>
            <option value="disabled">Disabled</option>
          </select>
        </label>

        <label v-if="activeAccountTab === 'user'">
          <span>User role:</span>
          <select v-model="userRoleFilter">
            <option value="all">All</option>
            <option value="student">Student</option>
            <option value="faculty">Faculty</option>
          </select>
        </label>

        <button class="manage-accounts-sort-button" type="button" aria-label="Toggle sort order" @click="handleToggleSortOrder">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19" />
            <polyline points="19 12 12 19 5 12" />
          </svg>
        </button>
      </div>

      <div class="manage-accounts-table-wrap">
        <table class="manage-accounts-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Work ID No.</th>
              <th>Name</th>
              <th v-if="activeAccountTab === 'employee'">Phone Number</th>
              <th>Role</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="isLoading">
              <td :colspan="manageAccountsColumnCount" class="manage-accounts-empty">Loading accounts...</td>
            </tr>
            <tr v-for="(account, index) in filteredAccounts" v-else :key="account.accountIdentifier">
              <td>{{ index + 1 }}</td>
              <td>{{ account.idNumber }}</td>
              <td>{{ account.fullName }}</td>
              <td v-if="activeAccountTab === 'employee'">{{ account.contactNumber || 'N/A' }}</td>
              <td>{{ account.roleLabel }}</td>
              <td>
                <span class="manage-accounts-status" :class="getStatusClass(account.accountStatus)">
                  {{ account.accountStatus }}
                </span>
              </td>
              <td>
                <div class="manage-accounts-actions">
                  <button type="button" class="manage-accounts-icon-button" aria-label="View account" title="View account" @click="openViewModal(account)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                      <circle cx="12" cy="12" r="3" />
                    </svg>
                  </button>
                  <button
                    type="button"
                    class="manage-accounts-icon-button manage-accounts-icon-button--edit"
                    aria-label="Update account"
                    :title="canUpdateAccount(account) ? 'Update account' : 'Update not allowed for this status'"
                    :disabled="!canUpdateAccount(account)"
                    @click="openUpdateModal(account)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M12 20h9" />
                      <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" />
                    </svg>
                  </button>
                  <button
                    type="button"
                    class="manage-accounts-icon-button manage-accounts-icon-button--activate"
                    aria-label="Reactivate account"
                    :title="canActivateAccount(account) ? 'Reactivate account' : 'Reactivate not allowed for this status'"
                    :disabled="!canActivateAccount(account)"
                    @click="openAccessModal(account, 'activate')"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="8" r="4" />
                      <path d="M4 21a8 8 0 0 1 16 0" />
                      <path d="M19 4v4" />
                      <path d="M21 6h-4" />
                    </svg>
                  </button>
                  <button
                    type="button"
                    class="manage-accounts-icon-button manage-accounts-icon-button--disable"
                    aria-label="Deactivate account"
                    :title="canDisableAccount(account) ? 'Deactivate account' : 'Deactivation not allowed for this status'"
                    :disabled="!canDisableAccount(account)"
                    @click="openAccessModal(account, 'disable')"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="10" cy="8" r="4" />
                      <path d="M2 21a8 8 0 0 1 12.4-6.7" />
                      <path d="m17 17 4 4" />
                      <path d="m21 17-4 4" />
                    </svg>
                  </button>
                  <button
                    type="button"
                    class="manage-accounts-icon-button manage-accounts-icon-button--delete"
                    aria-label="Delete account"
                    title="Delete account"
                    @click="openAccessModal(account, 'delete')"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M3 6h18" />
                      <path d="M8 6V4h8v2" />
                      <path d="M10 11v6" />
                      <path d="M14 11v6" />
                      <path d="M19 6l-1 14H6L5 6" />
                    </svg>
                  </button>
                  <button
                    v-if="canViewWorkLogs(account)"
                    type="button"
                    class="manage-accounts-icon-button manage-accounts-icon-button--logs"
                    aria-label="View work logs"
                    title="View work logs"
                    @click="openWorkLogs(account)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <rect x="5" y="3" width="14" height="18" rx="2" />
                      <path d="M9 7h6" />
                      <path d="M9 11h6" />
                      <path d="M9 15h4" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!isLoading && filteredAccounts.length === 0">
              <td :colspan="manageAccountsColumnCount" class="manage-accounts-empty">No accounts found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="manage-accounts-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </div>

      <div v-if="viewAccount" class="manage-accounts-modal-overlay" @click.self="closeModals">
        <section class="manage-accounts-view-modal">
          <button class="manage-accounts-modal-close" type="button" aria-label="Close" @click="closeModals">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>

          <div class="manage-accounts-modal-heading">
            <h2>View Account</h2>
            <p>View system account details.</p>
          </div>

          <div class="manage-accounts-detail-layout">
            <span class="manage-accounts-avatar" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="8" r="4" />
                <path d="M4 21a8 8 0 0 1 16 0" />
              </svg>
            </span>

            <div class="manage-accounts-detail-main">
              <p><strong>ID Number:</strong> <span>{{ formatDisplayValue(viewAccount.rawIdNumber || viewAccount.idNumber) }}</span></p>
              <p><strong>Last Name:</strong> <span>{{ formatDisplayValue(viewAccount.lastName) }}</span></p>
              <p><strong>First Name:</strong> <span>{{ formatDisplayValue(viewAccount.firstName) }}</span></p>
              <p><strong>Email:</strong> <span>{{ formatDisplayValue(viewAccount.emailAddress) }}</span></p>
              <p><strong>Role:</strong> <span>{{ formatDisplayValue(viewAccount.roleLabel) }}</span></p>
            </div>

            <div class="manage-accounts-detail-side">
              <p v-if="viewAccountLoading" class="manage-accounts-work-logs-state">Refreshing account details...</p>
              <p v-if="viewAccountError" class="manage-accounts-modal-error">{{ viewAccountError }}</p>
              <p>
                <strong>Account Status:</strong>
                <span class="manage-accounts-status" :class="getStatusClass(viewAccount.accountStatus)">
                  {{ formatDisplayValue(viewAccount.accountStatus) }}
                </span>
              </p>
              <p><strong>Account Registered:</strong> <span>{{ formatDateTime(viewAccount.createdTimestamp) }}</span></p>
              <p><strong>Account Type:</strong> <span class="manage-accounts-type-pill" :class="getAccountTypeClass(viewAccount.accountType)">{{ formatDisplayValue(viewAccount.accountType) }}</span></p>
              <p><strong>Invite Sent Status:</strong> <span>{{ getInviteSentStatusLabel(viewAccount) }}</span></p>
              <p><strong>Expiration Date:</strong> <span>{{ formatNullableDateTime(viewAccount.inviteExpiresAt) }}</span></p>
              <p><strong>Accepted Status:</strong> <span>{{ getAcceptedStatusLabel(viewAccount) }}</span></p>
            </div>
          </div>

          <div class="manage-accounts-modal-actions">
            <button class="manage-accounts-close-button" type="button" @click="closeModals">Close</button>
          </div>
        </section>
      </div>

      <div v-if="updateAccount" class="manage-accounts-modal-overlay" @click.self="!isProcessing && !updateAccountLoading && closeModals()">
        <section class="manage-accounts-view-modal">
          <button class="manage-accounts-modal-close" type="button" aria-label="Close" :disabled="isProcessing || updateAccountLoading" @click="closeModals">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>

          <div class="manage-accounts-modal-heading">
            <h2>Update Account</h2>
            <p>Update {{ updateAccount.accountType.toLowerCase() }} account details.</p>
          </div>

          <form class="manage-accounts-detail-layout manage-accounts-detail-layout--edit" @submit.prevent="saveAccountChanges">
            <span class="manage-accounts-avatar manage-accounts-avatar--muted" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="8" r="4" />
                <path d="M4 21a8 8 0 0 1 16 0" />
              </svg>
            </span>

            <div class="manage-accounts-edit-fields">
              <label><strong>Work ID Number:</strong><input v-model.trim="updateForm.idNumber" readonly /></label>
              <label><strong>Last Name:</strong><input v-model.trim="updateForm.lastName" required :disabled="isProcessing || updateAccountLoading" @input="sanitizeUpdateNameField('lastName')" /></label>
              <label><strong>First Name:</strong><input v-model.trim="updateForm.firstName" required :disabled="isProcessing || updateAccountLoading" @input="sanitizeUpdateNameField('firstName')" /></label>
              <label><strong>Phone Number:</strong><input v-model.trim="updateForm.contactNumber" type="tel" inputmode="numeric" maxlength="10" placeholder="9123456789" required :disabled="isProcessing || updateAccountLoading" @input="sanitizeUpdatePhone" /></label>
              <label><strong>{{ getUpdateEmailLabel() }}</strong><input v-model.trim="updateForm.emailAddress" type="email" readonly /></label>
              <label>
                <strong>Role:</strong>
                <input v-model="updateForm.roleLabel" readonly />
              </label>
              <label class="manage-accounts-field-wide">
                <strong>Profile Photo:</strong>
                <input type="file" accept=".jpg,image/jpeg" :disabled="isProcessing || updateAccountLoading" @change="handleUpdateProfilePhotoChange" />
              </label>
            </div>

            <div class="manage-accounts-detail-side manage-accounts-detail-side--muted">
              <img v-if="updateForm.profilePhotoPreview" class="manage-accounts-profile-preview" :src="updateForm.profilePhotoPreview" alt="" />
              <p><strong>Account Status:</strong> <span class="manage-accounts-status" :class="getStatusClass(updateAccount.accountStatus)">{{ updateAccount.accountStatus }}</span></p>
              <p><strong>Account Registered:</strong> <span>{{ formatDateTime(updateAccount.createdTimestamp) }}</span></p>
              <p><strong>Account Type:</strong> <span class="manage-accounts-type-pill" :class="getAccountTypeClass(updateForm.accountType)">{{ updateForm.accountType }}</span></p>
              <p><strong>Invite Sent:</strong> <span>{{ formatNullableDateTime(updateAccount.inviteSentAt) }}</span></p>
              <p><strong>Expires:</strong> <span>{{ formatNullableDateTime(updateAccount.inviteExpiresAt) }}</span></p>
              <p><strong>Accepted:</strong> <span>{{ formatNullableDateTime(updateAccount.inviteAcceptedAt) }}</span></p>
            </div>

            <p v-if="updateAccountLoading" class="manage-accounts-work-logs-state">Refreshing account details...</p>
            <p v-if="modalErrorMessage" class="manage-accounts-modal-error">{{ modalErrorMessage }}</p>

            <div class="manage-accounts-modal-actions manage-accounts-modal-actions--wide">
              <button class="manage-accounts-cancel-button" type="button" :disabled="isProcessing || updateAccountLoading" @click="closeModals">Cancel</button>
              <button class="manage-accounts-save-button" type="submit" :disabled="isProcessing || updateAccountLoading || !isUpdateFormReady">
                {{ isProcessing ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
          </form>
        </section>
      </div>

      <div v-if="accessAccount" class="manage-accounts-modal-overlay" @click.self="!isProcessing && closeModals()">
        <section class="manage-accounts-access-modal">
          <button class="manage-accounts-modal-close" type="button" aria-label="Close" :disabled="isProcessing" @click="closeModals">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>

          <div class="manage-accounts-modal-heading">
            <h2>{{ getAccessModalTitle() }}</h2>
            <p>{{ getAccessModalDescription() }}</p>
          </div>

          <div class="manage-accounts-confirm-profile">
            <span class="manage-accounts-avatar" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="8" r="4" />
                <path d="M4 21a8 8 0 0 1 16 0" />
              </svg>
            </span>
            <div>
              <strong>{{ formatDisplayValue(accessAccount.fullName) }}</strong>
              <span><strong>Work ID Number:</strong> {{ formatDisplayValue(accessAccount.rawIdNumber || accessAccount.idNumber) }}</span>
              <span><strong>Name:</strong> {{ formatDisplayValue(accessAccount.fullName) }}</span>
              <span v-if="accessMode === 'delete'"><strong>Phone Number:</strong> {{ formatDisplayValue(accessAccount.contactNumber) }}</span>
              <span v-else><strong>Email:</strong> {{ formatDisplayValue(accessAccount.emailAddress) }}</span>
              <span><strong>Role:</strong> {{ formatDisplayValue(accessAccount.roleLabel) }}</span>
            </div>
          </div>

          <label class="manage-accounts-confirm-field">
            <span>{{ getAccessConfirmationLabel() }}</span>
            <input v-model.trim="confirmEmailText" type="email" :placeholder="getAccessConfirmationPlaceholder()" />
          </label>

          <label v-if="accessMode === 'delete'" class="manage-accounts-confirm-field">
            <span>Type your admin password to confirm deletion:</span>
            <input v-model="confirmPasswordText" type="password" placeholder="Admin password" autocomplete="current-password" />
          </label>

          <p v-if="modalErrorMessage" class="manage-accounts-modal-error">{{ modalErrorMessage }}</p>

          <div class="manage-accounts-modal-actions">
            <button class="manage-accounts-cancel-button" type="button" :disabled="isProcessing" @click="closeModals">Cancel</button>
            <button
              class="manage-accounts-save-button"
              :class="{ 'manage-accounts-save-button--danger': accessMode === 'disable' || accessMode === 'delete' }"
              type="button"
              :disabled="isProcessing || !isAccessConfirmationReady"
              @click="confirmAccessChange"
            >
              {{ getAccessModalActionLabel() }}
            </button>
          </div>
        </section>
      </div>

      <div v-if="workLogsAccount" class="manage-accounts-modal-overlay" @click.self="closeModals">
        <section class="manage-accounts-work-logs-modal">
          <button class="manage-accounts-modal-close" type="button" aria-label="Close" @click="closeModals">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>

          <div class="manage-accounts-modal-heading">
            <h2>Work Logs</h2>
            <p>{{ workLogsAccount.fullName }} - {{ workLogsAccount.rawIdNumber || workLogsAccount.idNumber }}</p>
          </div>

          <p v-if="workLogsLoading" class="manage-accounts-work-logs-state">Loading work logs...</p>
          <p v-else-if="workLogsError" class="manage-accounts-modal-error">{{ workLogsError }}</p>
          <p v-else-if="employeeWorkLogs.length === 0" class="manage-accounts-work-logs-state">No work logs found for this employee.</p>

          <div v-else class="manage-accounts-work-logs-list">
            <article
              v-for="log in employeeWorkLogs"
              :key="log.taskIdentifier"
              class="manage-accounts-work-log"
            >
              <button
                class="manage-accounts-work-log-summary"
                type="button"
                :aria-expanded="expandedWorkLogIds.has(log.taskIdentifier)"
                @click="toggleWorkLog(log.taskIdentifier)"
              >
                <span class="manage-accounts-work-log-main">
                  <strong>{{ log.taskName || 'Untitled task' }}</strong>
                  <small>{{ formatNullableDateTime(log.taskDateTime) }}</small>
                  <span>{{ getReservationLabel(log.reservationDetails) }}</span>
                </span>
                <span class="manage-accounts-work-log-meta">
                  <em :class="getWorkLogStatusClass(log.status)">{{ log.status || 'No status' }}</em>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m6 9 6 6 6-6" />
                  </svg>
                </span>
              </button>

              <div v-if="expandedWorkLogIds.has(log.taskIdentifier)" class="manage-accounts-work-log-details">
                <section class="manage-accounts-work-log-section">
                  <h3>Reservation Details</h3>
                  <dl>
                    <div>
                      <dt>Reservation</dt>
                      <dd>{{ getReservationLabel(log.reservationDetails) }}</dd>
                    </div>
                    <div>
                      <dt>Organization</dt>
                      <dd>{{ log.reservationDetails?.organizationName || 'N/A' }}</dd>
                    </div>
                    <div>
                      <dt>Event Date and Time</dt>
                      <dd>{{ formatNullableDateTime(log.reservationDetails?.eventDateTime) }}</dd>
                    </div>
                    <div>
                      <dt>Activity</dt>
                      <dd>{{ log.reservationDetails?.activityType || 'N/A' }}</dd>
                    </div>
                    <div>
                      <dt>Purpose</dt>
                      <dd>{{ log.reservationDetails?.purposeDescription || 'N/A' }}</dd>
                    </div>
                    <div>
                      <dt>Reservation Status</dt>
                      <dd>{{ log.reservationDetails?.status || 'N/A' }}</dd>
                    </div>
                    <div>
                      <dt>Requested Items</dt>
                      <dd>{{ formatEquipmentList(log.reservationDetails?.requestedEquipmentList) }}</dd>
                    </div>
                  </dl>
                </section>

                <section class="manage-accounts-work-log-section">
                  <h3>Assignment Details</h3>
                  <dl>
                    <div>
                      <dt>Assigned Employee</dt>
                      <dd>{{ formatAssignedEmployee(log.assignments) }}</dd>
                    </div>
                    <div>
                      <dt>Task Name</dt>
                      <dd>{{ log.assignments?.assignedTask || log.taskName || 'N/A' }}</dd>
                    </div>
                    <div>
                      <dt>Task Type</dt>
                      <dd>{{ log.taskType || log.assignments?.assignmentType || 'N/A' }}</dd>
                    </div>
                    <div>
                      <dt>Task Date and Time</dt>
                      <dd>{{ formatNullableDateTime(log.taskDateTime) }}</dd>
                    </div>
                    <div>
                      <dt>Status</dt>
                      <dd>{{ log.status || 'N/A' }}</dd>
                    </div>
                    <div>
                      <dt>Description</dt>
                      <dd>{{ log.taskDescription || log.fullTaskInformation?.description || 'N/A' }}</dd>
                    </div>
                    <div>
                      <dt>Created</dt>
                      <dd>{{ formatNullableDateTime(log.createdTimestamp) }}</dd>
                    </div>
                    <div>
                      <dt>Updated</dt>
                      <dd>{{ formatNullableDateTime(log.updatedTimestamp) }}</dd>
                    </div>
                  </dl>
                </section>
              </div>
            </article>
          </div>

          <div class="manage-accounts-modal-actions">
            <button class="manage-accounts-close-button" type="button" @click="closeModals">Close</button>
          </div>
        </section>
      </div>

      <AdminWishlistCreateAccountModals
        ref="createAccountModals"
        :accounts="normalizedAccounts"
        @created="handleEmployeeRequestCreated"
      />
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import AdminWishlistCreateAccountModals from './components/AdminWishlistCreateAccountModals.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/ManageAccounts.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { useManageAccountsPage } from './composables/useManageAccountsPage.js';

const createAccountModals = ref(null);

const {
  activeAccountTab,
  searchQueryText,
  showingFilterValue,
  sortMode,
  userRoleFilter,
  normalizedAccounts,
  isLoading,
  isProcessing,
  toastMessage,
  loadErrorMessage,
  modalErrorMessage,
  viewAccount,
  viewAccountLoading,
  viewAccountError,
  updateAccount,
  updateAccountLoading,
  accessAccount,
  workLogsAccount,
  employeeWorkLogs,
  workLogsLoading,
  workLogsError,
  expandedWorkLogIds,
  accessMode,
  confirmEmailText,
  confirmPasswordText,
  updateForm,
  pageTitle,
  pageDescription,
  manageAccountsColumnCount,
  isAccessConfirmationReady,
  isUpdateFormReady,
  accountTabs,
  filteredAccounts,
  handleRefreshAccounts,
  handleTabChange,
  handleToggleSortOrder,
  openViewModal,
  openWorkLogs,
  canViewWorkLogs,
  toggleWorkLog,
  openUpdateModal,
  openAccessModal,
  getAccessModalTitle,
  getAccessModalDescription,
  getAccessModalActionLabel,
  getAccessConfirmationLabel,
  getAccessConfirmationPlaceholder,
  closeModals,
  sanitizeUpdateNameField,
  sanitizeUpdatePhone,
  handleUpdateProfilePhotoChange,
  saveAccountChanges,
  confirmAccessChange,
  getUpdateEmailLabel,
  canActivateAccount,
  canDisableAccount,
  canUpdateAccount,
  formatAssignedEmployee,
  formatDisplayValue,
  formatEquipmentList,
  formatDateTime,
  formatNullableDateTime,
  getAcceptedStatusLabel,
  getInviteSentStatusLabel,
  getReservationLabel,
  getWorkLogStatusClass,
  getAccountTypeClass,
  getStatusClass,
} = useManageAccountsPage();

function openAddEmployeeRequestModal() {
  createAccountModals.value?.openForTab('employee');
}

async function handleEmployeeRequestCreated(payload) {
  const accountType = typeof payload === 'string' ? payload : payload?.type;
  const defaultPassword = typeof payload === 'object' ? payload?.data?.defaultPassword : '';
  if (accountType !== 'employee') {
    return;
  }

  await handleRefreshAccounts();
  toastMessage.value = defaultPassword
    ? `Staff account created. Default password: ${defaultPassword}`
    : 'Staff account created.';
  window.setTimeout(() => {
    if (
      toastMessage.value === 'Staff account created.'
      || toastMessage.value === `Staff account created. Default password: ${defaultPassword}`
    ) {
      toastMessage.value = '';
    }
  }, 2800);
}
</script>
