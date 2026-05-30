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
              <p><strong>Work ID Number:</strong> <span>{{ viewAccount.idNumber }}</span></p>
              <p><strong>Last Name:</strong> <span>{{ viewAccount.lastName }}</span></p>
              <p><strong>First Name:</strong> <span>{{ viewAccount.firstName }}</span></p>
              <p><strong>{{ getEmailLabel(viewAccount) }}</strong> <span>{{ viewAccount.emailAddress }}</span></p>
              <p v-if="viewAccount.accountType === 'Employee'"><strong>Phone:</strong> <span>{{ viewAccount.contactNumber || 'N/A' }}</span></p>
              <p><strong>Role:</strong> <span>{{ viewAccount.roleLabel }}</span></p>
            </div>

            <div class="manage-accounts-detail-side">
              <p>
                <strong>Account Status:</strong>
                <span class="manage-accounts-status" :class="getStatusClass(viewAccount.accountStatus)">
                  {{ viewAccount.accountStatus }}
                </span>
              </p>
              <p><strong>Account Registered:</strong> <span>{{ formatDateTime(viewAccount.createdTimestamp) }}</span></p>
              <p><strong>Account Type:</strong> <span class="manage-accounts-type-pill" :class="getAccountTypeClass(viewAccount.accountType)">{{ viewAccount.accountType }}</span></p>
              <p><strong>Invite Sent:</strong> <span>{{ formatNullableDateTime(viewAccount.inviteSentAt) }}</span></p>
              <p><strong>Expires:</strong> <span>{{ formatNullableDateTime(viewAccount.inviteExpiresAt) }}</span></p>
              <p><strong>Accepted:</strong> <span>{{ formatNullableDateTime(viewAccount.inviteAcceptedAt) }}</span></p>
            </div>
          </div>

          <div class="manage-accounts-modal-actions">
            <button class="manage-accounts-close-button" type="button" @click="closeModals">Close</button>
          </div>
        </section>
      </div>

      <div v-if="updateAccount" class="manage-accounts-modal-overlay" @click.self="!isProcessing && closeModals()">
        <section class="manage-accounts-view-modal">
          <button class="manage-accounts-modal-close" type="button" aria-label="Close" :disabled="isProcessing" @click="closeModals">
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
              <label><strong>Last Name:</strong><input v-model.trim="updateForm.lastName" required @input="sanitizeUpdateNameField('lastName')" /></label>
              <label><strong>First Name:</strong><input v-model.trim="updateForm.firstName" required @input="sanitizeUpdateNameField('firstName')" /></label>
              <label><strong>Phone Number:</strong><input v-model.trim="updateForm.contactNumber" type="tel" inputmode="numeric" maxlength="10" placeholder="9123456789" required @input="sanitizeUpdatePhone" /></label>
              <label><strong>{{ getUpdateEmailLabel() }}</strong><input v-model.trim="updateForm.emailAddress" type="email" readonly /></label>
              <label>
                <strong>Role:</strong>
                <input v-model="updateForm.roleLabel" readonly />
              </label>
              <label class="manage-accounts-field-wide">
                <strong>Profile Photo:</strong>
                <input type="file" accept=".jpg,image/jpeg" :disabled="isProcessing" @change="handleUpdateProfilePhotoChange" />
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

            <p v-if="modalErrorMessage" class="manage-accounts-modal-error">{{ modalErrorMessage }}</p>

            <div class="manage-accounts-modal-actions manage-accounts-modal-actions--wide">
              <button class="manage-accounts-cancel-button" type="button" :disabled="isProcessing" @click="closeModals">Cancel</button>
              <button class="manage-accounts-save-button" type="submit" :disabled="isProcessing || !isUpdateFormReady">
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
              <strong>{{ accessAccount.fullName }}</strong>
              <span>Work ID: {{ accessAccount.idNumber }}</span>
              <span>Phone: {{ accessAccount.contactNumber || 'N/A' }}</span>
              <span>{{ accessAccount.emailAddress }}</span>
              <span>{{ accessAccount.roleLabel }}</span>
              <em :class="getAccountTypeClass(accessAccount.accountType)">{{ accessAccount.accountType }}</em>
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
            <p>{{ workLogsAccount.fullName }} - {{ workLogsAccount.idNumber }}</p>
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
                @click="toggleWorkLog(log.taskIdentifier)"
              >
                <span>
                  <strong>{{ log.taskName }}</strong>
                  <small>{{ formatNullableDateTime(log.taskDateTime) }}</small>
                </span>
                <em>{{ log.status }}</em>
              </button>

              <div v-if="expandedWorkLogIds.has(log.taskIdentifier)" class="manage-accounts-work-log-details">
                <p><strong>Reservation Details:</strong> <span>{{ formatReservationDetails(log.reservationDetails) }}</span></p>
                <p><strong>Assignments:</strong> <span>{{ formatAssignments(log.assignments) }}</span></p>
                <p><strong>Task Type:</strong> <span>{{ log.taskType || 'N/A' }}</span></p>
                <p><strong>Description:</strong> <span>{{ log.taskDescription || 'N/A' }}</span></p>
                <p><strong>Created:</strong> <span>{{ formatNullableDateTime(log.createdTimestamp) }}</span></p>
                <p><strong>Updated:</strong> <span>{{ formatNullableDateTime(log.updatedTimestamp) }}</span></p>
              </div>
            </article>
          </div>

          <div class="manage-accounts-modal-actions">
            <button class="manage-accounts-close-button" type="button" @click="closeModals">Close</button>
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
import './css/ManageAccounts.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { adminManageAccountsApi } from '@/services/adminManageAccountsApi.js';

const authStore = useAuthenticationStore();
const activeAccountTab = ref(getDefaultAccountTab());
const searchQueryText = ref('');
const showingFilterValue = ref('all');
const sortMode = ref('created');
const userRoleFilter = ref('all');
const sortOrderAscending = ref(true);
const isLoading = ref(false);
const isProcessing = ref(false);
const toastMessage = ref('');
const loadErrorMessage = ref('');
const modalErrorMessage = ref('');
const accounts = ref([]);
const viewAccount = ref(null);
const updateAccount = ref(null);
const accessAccount = ref(null);
const workLogsAccount = ref(null);
const employeeWorkLogs = ref([]);
const workLogsLoading = ref(false);
const workLogsError = ref('');
const expandedWorkLogIds = ref(new Set());
const accessMode = ref('disable');
const confirmEmailText = ref('');
const confirmPasswordText = ref('');

const updateForm = reactive({
  idNumber: '',
  lastName: '',
  firstName: '',
  emailAddress: '',
  contactNumber: '',
  roleDesignation: 'ROLE_ADMIN',
  roleLabel: 'Admin',
  accountType: 'Admin',
  profilePhotoName: '',
  profilePhotoData: '',
  profilePhotoPreview: '',
});

const normalizedAccounts = computed(() => accounts.value.map(normalizeAccount));
const isEmployeeUpdateModal = computed(() => updateAccount.value?.accountType === 'Employee');
const pageTitle = computed(() => 'Manage Accounts');
const pageDescription = computed(() => 'Manage and oversee system accounts in TechReserve.');
const manageAccountsColumnCount = computed(() => (activeAccountTab.value === 'employee' ? 7 : 6));
const currentAdminEmail = computed(() => {
  const account = authStore.accountData || authStore.clerkAccountData || {};
  return String(account.emailAddress || account.email || '').trim();
});
const isAccessConfirmationReady = computed(() => {
  if (isProcessing.value || !accessAccount.value) return false;
  if (confirmEmailText.value.trim() === '') return false;

  if (accessMode.value === 'delete') {
    return confirmPasswordText.value.trim() !== '';
  }

  return true;
});
const isUpdateFormReady = computed(() => validateUpdateAccountForm() === '');

const accountTabs = computed(() => [
  { label: 'Admin', value: 'admin', count: normalizedAccounts.value.filter((account) => account.accountType === 'Admin').length },
  { label: 'User', value: 'user', count: normalizedAccounts.value.filter((account) => account.accountType === 'User').length },
  { label: 'Employee', value: 'employee', count: normalizedAccounts.value.filter((account) => account.accountType === 'Employee').length },
]);

const filteredAccounts = computed(() => {
  const type = activeAccountTab.value === 'admin' ? 'Admin' : activeAccountTab.value === 'employee' ? 'Employee' : 'User';
  const query = searchQueryText.value.trim().toLowerCase();

  return normalizedAccounts.value
    .filter((account) => account.accountType === type)
    .filter((account) => showingFilterValue.value === 'all' || account.accountStatus.toLowerCase() === showingFilterValue.value)
    .filter((account) => activeAccountTab.value !== 'user' || userRoleFilter.value === 'all' || getUserRoleName(account).toLowerCase() === userRoleFilter.value)
    .filter((account) => {
      if (!query) return true;
      return [account.idNumber, account.fullName, account.emailAddress, account.roleLabel].some((value) => String(value).toLowerCase().includes(query));
    })
    .sort((first, second) => {
      const result = compareAccounts(first, second);
      return sortOrderAscending.value ? result : -result;
    });
});

onMounted(() => {
  loadAccounts();
});

async function loadAccounts({ showLoading = true } = {}) {
  if (showLoading) isLoading.value = true;
  loadErrorMessage.value = '';
  const result = await adminManageAccountsApi.getAccounts(authStore.authToken);
  if (result.success) {
    accounts.value = result.data.accounts || [];
  } else {
    loadErrorMessage.value = result.error || 'Unable to load accounts.';
  }
  if (showLoading) isLoading.value = false;
}

async function handleRefreshAccounts() {
  await loadAccounts();
  if (!loadErrorMessage.value) {
    showToast('Accounts refreshed.');
  }
}

function normalizeAccount(account) {
  const roleDesignation = account.roleDesignation || account.role_designation || 'ROLE_BORROWER';
  const firstName = account.firstName || account.first_name || '';
  const lastName = account.lastName || account.last_name || '';
  const accountType = account.accountType || resolveAccountType(account, roleDesignation);
  const accountStatus = normalizeManageAccountStatus(account.accountStatus || account.status, account.isActive);

  return {
    ...account,
    accountIdentifier: account.accountIdentifier || account.account_identifier,
    rawIdNumber: account.idNumber || account.id_number || account.accountIdentifier || 'N/A',
    idNumber: formatIdNumber(account.idNumber || account.id_number || account.accountIdentifier || 'N/A'),
    firstName,
    lastName,
    fullName: `${firstName} ${lastName}`.trim(),
    emailAddress: account.emailAddress || account.email_address || '',
    contactNumber: account.contactNumber || account.contact_number || '',
    profilePhotoData: account.profilePhotoData || account.profile_photo_data || '',
    roleDesignation,
    roleLabel: account.roleLabel || resolveRoleLabel(account, roleDesignation, accountType),
    accountType,
    accountStatus,
    isActive: account.isActive !== false && accountStatus !== 'Disabled',
    createdTimestamp: account.createdTimestamp || account.created_timestamp,
    lastLoginTimestamp: account.lastLoginTimestamp || account.last_login_timestamp,
    inviteSentAt: account.inviteSentAt || account.invite_sent_at,
    inviteExpiresAt: account.inviteExpiresAt || account.invite_expires_at,
    inviteAcceptedAt: account.inviteAcceptedAt || account.invite_accepted_at,
    actionPermissions: resolveActionPermissions(accountStatus, account.actionPermissions),
  };
}

function normalizeManageAccountStatus(status, isActive) {
  const normalizedStatus = String(status || '').trim().toLowerCase();
  if (isActive === false || normalizedStatus === 'disabled') return 'Disabled';
  return 'Active';
}

function resolveAccountType(account, roleDesignation) {
  const role = String(roleDesignation).toUpperCase();
  if (role.includes('ADMIN')) return 'Admin';
  const department = String(account.department || '').toLowerCase();
  if (role.includes('STAFF') || role.includes('EMPLOYEE') || department.includes('staff') || department.includes('employee') || department.includes('technical') || department.includes('maintenance') || department.includes('support')) return 'Employee';
  return 'User';
}

function resolveRoleLabel(account, roleDesignation, accountType) {
  if (accountType === 'Admin') return 'Admin';
  if (accountType === 'Employee') return account.department || 'Technical Staff';
  const department = String(account.department || '').trim();
  if (/faculty/i.test(department)) return 'Faculty';
  if (/student/i.test(department)) return 'Student';
  return String(roleDesignation).toUpperCase().includes('FACULTY') ? 'Faculty' : 'Student';
}

function handleTabChange(tabName) {
  activeAccountTab.value = tabName;
  searchQueryText.value = '';
  showingFilterValue.value = 'all';
  userRoleFilter.value = 'all';
  closeModals();
}

function handleToggleSortOrder() {
  sortOrderAscending.value = !sortOrderAscending.value;
}

function compareAccounts(first, second) {
  if (sortMode.value === 'name') {
    return first.fullName.localeCompare(second.fullName);
  }

  if (sortMode.value === 'role') {
    return getSortRoleName(first).localeCompare(getSortRoleName(second)) || first.fullName.localeCompare(second.fullName);
  }

  if (sortMode.value === 'status') {
    return first.accountStatus.localeCompare(second.accountStatus) || first.fullName.localeCompare(second.fullName);
  }

  const firstTime = new Date(first.createdTimestamp || 0).getTime();
  const secondTime = new Date(second.createdTimestamp || 0).getTime();
  return firstTime - secondTime;
}

function openViewModal(account) {
  viewAccount.value = account;
}

async function openWorkLogs(account) {
  if (!canViewWorkLogs(account)) return;
  workLogsAccount.value = account;
  employeeWorkLogs.value = [];
  workLogsError.value = '';
  expandedWorkLogIds.value = new Set();
  workLogsLoading.value = true;

  const result = await adminManageAccountsApi.getEmployeeWorkLogs(account.accountIdentifier, authStore.authToken);
  workLogsLoading.value = false;

  if (!result.success) {
    workLogsError.value = result.error || 'Unable to load work logs.';
    return;
  }

  employeeWorkLogs.value = result.data.workLogs || [];
}

function canViewWorkLogs(account) {
  return account?.accountType === 'Employee';
}

function toggleWorkLog(taskIdentifier) {
  const nextExpanded = new Set(expandedWorkLogIds.value);
  if (nextExpanded.has(taskIdentifier)) {
    nextExpanded.delete(taskIdentifier);
  } else {
    nextExpanded.add(taskIdentifier);
  }
  expandedWorkLogIds.value = nextExpanded;
}

function formatReservationDetails(reservationDetails) {
  if (!reservationDetails) return 'No linked reservation.';

  const parts = [
    reservationDetails.reservationCode || `Reservation #${reservationDetails.reservationIdentifier}`,
    reservationDetails.organizationName,
    reservationDetails.activityType,
    reservationDetails.eventDateTime ? formatNullableDateTime(reservationDetails.eventDateTime) : '',
    reservationDetails.status,
  ].filter(Boolean);

  return parts.join(' | ');
}

function formatAssignments(assignments) {
  if (!assignments) return 'N/A';

  return [
    assignments.assignedTask,
    assignments.assignmentType,
    assignments.assignedToAccountId ? `Account #${assignments.assignedToAccountId}` : '',
  ].filter(Boolean).join(' | ') || 'N/A';
}

function openUpdateModal(account) {
  if (!canUpdateAccount(account)) {
    showToast('Only active accounts can be updated.');
    return;
  }

  updateAccount.value = account;
  updateForm.idNumber = account.rawIdNumber || account.idNumber;
  updateForm.lastName = account.lastName;
  updateForm.firstName = account.firstName;
  updateForm.emailAddress = account.emailAddress;
  updateForm.contactNumber = account.contactNumber || '';
  updateForm.roleDesignation = normalizeUpdateRoleDesignation(account.accountType, account.roleLabel);
  updateForm.roleLabel = account.roleLabel;
  updateForm.accountType = account.accountType;
  updateForm.profilePhotoName = '';
  updateForm.profilePhotoData = '';
  updateForm.profilePhotoPreview = account.profilePhotoData || '';
  if (account.accountType === 'Employee' && !getEmployeeRoleOptions().includes(updateForm.roleLabel)) {
    updateForm.roleLabel = account.roleLabel || 'Technical Staff';
  }
  modalErrorMessage.value = '';
}

function openAccessModal(account, mode) {
  if (mode === 'disable' && !canDisableAccount(account)) {
    showToast('Only active accounts can be disabled.');
    return;
  }

  if (mode === 'activate' && !canActivateAccount(account)) {
    showToast('Only disabled accounts can be reactivated.');
    return;
  }

  accessAccount.value = account;
  accessMode.value = mode;
  confirmEmailText.value = '';
  modalErrorMessage.value = '';
}

function getAccessModalTitle() {
  if (accessMode.value === 'delete') return 'Delete Account';
  return accessMode.value === 'disable' ? 'Deactivate Account' : 'Reactivate Account';
}

function getAccessModalDescription() {
  if (accessMode.value === 'delete') return 'This will permanently delete the account and its invitation records from the database.';
  return accessMode.value === 'disable'
    ? 'This will deactivate the account and prevent access to the system.'
    : 'This will reactivate the account and restore access to the system.';
}

function getAccessModalActionLabel() {
  if (isProcessing.value) {
    if (accessMode.value === 'delete') return 'Deleting...';
    return accessMode.value === 'disable' ? 'Deactivating...' : 'Reactivating...';
  }
  if (accessMode.value === 'delete') return 'Delete Account';
  return accessMode.value === 'disable' ? 'Deactivate Account' : 'Reactivate Account';
}

function getAccessConfirmationLabel() {
  if (accessMode.value === 'activate' || accessMode.value === 'disable' || accessMode.value === 'delete') {
    const actionName = accessMode.value === 'activate'
      ? 'reactivation'
      : accessMode.value === 'disable'
        ? 'deactivation'
        : 'deletion';
    return `Type your admin email ${currentAdminEmail.value || 'from your account'} to confirm ${actionName}:`;
  }

  return `Type ${accessAccount.value?.emailAddress || 'the account email'} to confirm:`;
}

function getAccessConfirmationPlaceholder() {
  if (accessMode.value === 'activate' || accessMode.value === 'disable' || accessMode.value === 'delete') {
    return currentAdminEmail.value || 'admin@techreserve.edu.ph';
  }

  return accessAccount.value?.emailAddress || '';
}

function closeModals() {
  viewAccount.value = null;
  updateAccount.value = null;
  accessAccount.value = null;
  workLogsAccount.value = null;
  employeeWorkLogs.value = [];
  workLogsLoading.value = false;
  workLogsError.value = '';
  expandedWorkLogIds.value = new Set();
  confirmEmailText.value = '';
  confirmPasswordText.value = '';
  resetUpdateForm();
  modalErrorMessage.value = '';
}

function resetUpdateForm() {
  updateForm.idNumber = '';
  updateForm.lastName = '';
  updateForm.firstName = '';
  updateForm.emailAddress = '';
  updateForm.contactNumber = '';
  updateForm.roleDesignation = 'ROLE_ADMIN';
  updateForm.roleLabel = 'Admin';
  updateForm.accountType = 'Admin';
  updateForm.profilePhotoName = '';
  updateForm.profilePhotoData = '';
  updateForm.profilePhotoPreview = '';
}

function sanitizeUpdateNameField(fieldName) {
  updateForm[fieldName] = String(updateForm[fieldName] || '').replace(/[^A-Za-z ]+/g, '').replace(/\s{2,}/g, ' ');
}

function sanitizeUpdatePhone() {
  updateForm.contactNumber = String(updateForm.contactNumber || '').replace(/\D/g, '').slice(0, 10);
}

function validateUpdateAccountForm() {
  const lastName = updateForm.lastName.trim();
  const firstName = updateForm.firstName.trim();
  const phone = updateForm.contactNumber.trim();

  if (!isValidAccountName(lastName)) {
    return 'Last name is required, must be at least 2 characters, and may contain letters and spaces only.';
  }

  if (!isValidAccountName(firstName)) {
    return 'First name is required, must be at least 2 characters, and may contain letters and spaces only.';
  }

  if (!/^9\d{9}$/.test(phone)) {
    return 'Phone number must be exactly 10 digits and begin with 9.';
  }

  if (updateForm.profilePhotoName && !updateForm.profilePhotoName.toLowerCase().endsWith('.jpg')) {
    return 'Profile photo must be a .jpg image only.';
  }

  return '';
}

function isValidAccountName(value) {
  return /^[A-Za-z]+(?: [A-Za-z]+)*$/.test(String(value || '').trim()) && String(value || '').trim().length >= 2;
}

function handleUpdateProfilePhotoChange(event) {
  const file = event.target.files?.[0] || null;
  updateForm.profilePhotoName = '';
  updateForm.profilePhotoData = '';

  if (!file) {
    updateForm.profilePhotoPreview = updateAccount.value?.profilePhotoData || '';
    modalErrorMessage.value = '';
    return;
  }

  if (!file.name.toLowerCase().endsWith('.jpg') || file.type !== 'image/jpeg') {
    modalErrorMessage.value = 'Profile photo must be a .jpg image only.';
    event.target.value = '';
    updateForm.profilePhotoPreview = updateAccount.value?.profilePhotoData || '';
    return;
  }

  const reader = new FileReader();
  reader.onload = () => {
    updateForm.profilePhotoName = file.name;
    updateForm.profilePhotoData = String(reader.result || '');
    updateForm.profilePhotoPreview = updateForm.profilePhotoData;
    modalErrorMessage.value = '';
  };
  reader.onerror = () => {
    modalErrorMessage.value = 'Unable to read profile photo.';
    event.target.value = '';
  };
  reader.readAsDataURL(file);
}

async function saveAccountChanges() {
  if (!updateAccount.value) return;
  if (isProcessing.value) return;

  const validationError = validateUpdateAccountForm();
  if (validationError) {
    modalErrorMessage.value = validationError;
    return;
  }

  isProcessing.value = true;
  const result = await adminManageAccountsApi.updateAccount(updateAccount.value.accountIdentifier, {
    lastName: updateForm.lastName,
    firstName: updateForm.firstName,
    contactNumber: updateForm.contactNumber,
    profilePhotoName: updateForm.profilePhotoName,
    profilePhotoData: updateForm.profilePhotoData,
  }, authStore.authToken);
  isProcessing.value = false;

  if (!result.success) {
    modalErrorMessage.value = result.error || 'Unable to save changes.';
    return;
  }

  upsertAccount(result.data.account);
  closeModals();
  showToast('Changes saved!');
}

async function confirmAccessChange() {
  if (!accessAccount.value) return;
  if (isProcessing.value) return;

  if (accessMode.value === 'activate' || accessMode.value === 'disable' || accessMode.value === 'delete') {
    if (!currentAdminEmail.value) {
      modalErrorMessage.value = 'Unable to verify the admin in-charge. Please sign in again.';
      return;
    }

    if (normalizeEmailForConfirmation(confirmEmailText.value) !== normalizeEmailForConfirmation(currentAdminEmail.value)) {
      modalErrorMessage.value = accessMode.value === 'activate'
        ? 'Please type your exact admin email to reactivate this account.'
        : accessMode.value === 'disable'
          ? 'Please type your exact admin email to deactivate this account.'
          : 'Please type your exact admin email to delete this account.';
      return;
    }
  } else if (normalizeEmailForConfirmation(confirmEmailText.value) !== normalizeEmailForConfirmation(accessAccount.value.emailAddress)) {
    modalErrorMessage.value = 'Please type the exact account email address to confirm.';
    return;
  }

  if (accessMode.value === 'delete') {
    if (confirmPasswordText.value.trim() === '') {
      modalErrorMessage.value = 'Please type your admin password to delete this account.';
      return;
    }

    isProcessing.value = true;
    const result = await adminManageAccountsApi.deleteAccount(
      accessAccount.value.accountIdentifier,
      {
        confirmedAdminEmail: normalizeEmailForConfirmation(confirmEmailText.value),
        confirmedAdminPassword: confirmPasswordText.value,
      },
      authStore.authToken,
    );
    isProcessing.value = false;

    if (!result.success) {
      modalErrorMessage.value = result.error || 'Unable to delete account.';
      return;
    }

    removeAccount(accessAccount.value.accountIdentifier);
    closeModals();
    showToast('Account deleted!');
    return;
  }

  const shouldActivate = accessMode.value === 'activate';
  isProcessing.value = true;
  const result = await adminManageAccountsApi.updateAccountAccess(
    accessAccount.value.accountIdentifier,
    shouldActivate,
    authStore.authToken,
    { confirmedAdminEmail: normalizeEmailForConfirmation(confirmEmailText.value) },
  );
  isProcessing.value = false;

  if (!result.success) {
    modalErrorMessage.value = result.error || 'Unable to update account access.';
    return;
  }

  upsertAccount(result.data.account);
  closeModals();
  showToast(shouldActivate ? 'Account reactivated!' : 'Account deactivated!');
  await loadAccounts({ showLoading: false });
}

function normalizeEmailForConfirmation(value) {
  return String(value || '')
    .replace(/[\u200B-\u200D\uFEFF]/g, '')
    .replace(/\s+/g, '')
    .trim()
    .toLowerCase();
}

function removeAccount(accountIdentifier) {
  accounts.value = accounts.value.filter((account) => String(account.accountIdentifier) !== String(accountIdentifier));
}

function upsertAccount(updatedAccount) {
  if (!updatedAccount) return;
  const index = accounts.value.findIndex((account) => String(account.accountIdentifier) === String(updatedAccount.accountIdentifier));
  if (index >= 0) {
    accounts.value.splice(index, 1, updatedAccount);
  } else {
    accounts.value.unshift(updatedAccount);
  }
}

function formatIdNumber(value) {
  const text = String(value || 'N/A');
  if (text.includes('*') || text === 'N/A') return text;
  if (text.length <= 4) return text;
  return `${text.slice(0, 4)}*****`;
}

function formatDateTime(value) {
  if (!value) return 'N/A';
  return new Intl.DateTimeFormat('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(value));
}

function formatNullableDateTime(value) {
  return value ? formatDateTime(value) : 'N/A';
}

function getStatusClass(status) {
  const normalized = String(status || '').toLowerCase();
  if (normalized === 'disabled') return 'manage-accounts-status--disabled';
  return 'manage-accounts-status--active';
}

function getEmailLabel(account) {
  return account?.accountType === 'Employee' ? 'Email Address:' : 'FIT Email Address:';
}

function getRoleOptions(account) {
  if (!account) return ['Admin'];
  if (account.accountType === 'Admin') return ['Admin'];
  if (account.accountType === 'Employee') return ['Support Staff', 'Technical Staff', 'Maintenance Staff'];
  return ['Student', 'Faculty'];
}

function getUpdateEmailLabel() {
  return updateForm.accountType === 'Employee' || isEmployeeUpdateModal.value ? 'Email:' : 'FIT Email Address:';
}

function getUpdateRoleOptions() {
  if (isEmployeeUpdateModal.value) {
    return updateForm.accountType === 'Admin' ? ['Admin'] : getEmployeeRoleOptions();
  }
  return getRoleOptions(updateAccount.value);
}

function getEmployeeRoleOptions() {
  return ['Support Staff', 'Technical Staff', 'Maintenance Staff'];
}

function handleUpdateAccountTypeChange() {
  if (updateForm.accountType === 'Admin') {
    updateForm.roleLabel = 'Admin';
    updateForm.roleDesignation = 'ROLE_ADMIN';
    return;
  }

  updateForm.roleDesignation = 'ROLE_STAFF';
  if (!getEmployeeRoleOptions().includes(updateForm.roleLabel)) {
    updateForm.roleLabel = 'Technical Staff';
  }
}

function getUpdateAccountTypeForPayload() {
  if (isEmployeeUpdateModal.value) {
    return updateForm.accountType === 'Admin' ? 'Admin' : 'Employee';
  }
  return updateForm.accountType;
}

function getUpdateRoleLabelForPayload(accountType) {
  if (accountType === 'Admin') return 'Admin';
  return updateForm.roleLabel;
}

function normalizeUpdateRoleDesignation(accountType, roleLabel) {
  if (accountType === 'Admin' || roleLabel === 'Admin') return 'ROLE_ADMIN';
  if (accountType === 'Employee') return 'ROLE_STAFF';
  return roleLabel === 'Faculty' ? 'ROLE_FACULTY' : 'ROLE_BORROWER';
}

function getSortRoleName(account) {
  return account.accountType === 'User' ? getUserRoleName(account) : account.roleLabel;
}

function getUserRoleName(account) {
  const roleText = `${account?.roleLabel || ''} ${account?.roleDesignation || ''}`.toLowerCase();
  return roleText.includes('faculty') ? 'Faculty' : 'Student';
}

function getAccountTypeClass(accountType) {
  return {
    'manage-accounts-type-pill--admin': accountType === 'Admin',
    'manage-accounts-type-pill--user': accountType === 'User',
    'manage-accounts-type-pill--employee': accountType === 'Employee',
  };
}

function resolveActionPermissions(accountStatus, serverPermissions = null) {
  return {
    view: serverPermissions?.view ?? true,
    update: accountStatus === 'Active',
    disable: accountStatus === 'Active',
    activate: accountStatus === 'Disabled',
  };
}

function canUpdateAccount(account) {
  return Boolean(account?.actionPermissions?.update);
}

function canDisableAccount(account) {
  return Boolean(account?.actionPermissions?.disable);
}

function canActivateAccount(account) {
  return Boolean(account?.actionPermissions?.activate);
}

function getDefaultAccountTab() {
  return 'admin';
}

function showToast(message) {
  toastMessage.value = message;
  window.setTimeout(() => {
    if (toastMessage.value === message) toastMessage.value = '';
  }, 2800);
}

</script>
