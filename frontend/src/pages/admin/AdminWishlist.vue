<!-- ===== AI GENERATED: AdminWishlistPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="admin-wishlist-page">
      <div class="admin-wishlist-header">
        <div>
          <p class="admin-wishlist-kicker">Account verification</p>
          <h1>Wishlist</h1>
          <p>Review registered accounts before approval and invitation.</p>
        </div>

        <div class="admin-wishlist-header-actions">
          <button class="admin-wishlist-refresh-button" type="button" @click="loadWishlistAccounts">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
              <path d="M3 21v-5h5" />
              <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
              <path d="M16 8h5V3" />
            </svg>
            Refresh
          </button>
          <button class="admin-wishlist-add-button" type="button" @click="openAddAdminModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 5v14" />
              <path d="M5 12h14" />
            </svg>
            Add Account
          </button>
        </div>
      </div>

      <div v-if="toastMessage" class="admin-wishlist-toast">
        {{ toastMessage }}
      </div>

      <div v-if="loadErrorMessage" class="admin-wishlist-error-banner">
        {{ loadErrorMessage }}
      </div>

      <div class="admin-wishlist-tabs">
        <button
          v-for="tab in wishlistTabs"
          :key="tab.value"
          type="button"
          class="admin-wishlist-tab"
          :class="{ 'admin-wishlist-tab--active': activeTab === tab.value }"
          @click="activeTab = tab.value"
        >
          {{ tab.label }} <span>({{ tab.count }})</span>
        </button>
      </div>

      <section class="admin-wishlist-panel">
        <div class="admin-wishlist-toolbar">
          <label class="admin-wishlist-search">
            <span>Search</span>
            <input v-model="searchText" type="search" placeholder="Name, email, or ID number" />
          </label>

          <label class="admin-wishlist-select">
            <span>Sort by</span>
            <select v-model="sortMode">
              <option value="newest">Newest registered</option>
              <option value="name">Name</option>
              <option value="role">Role</option>
              <option value="status">Status</option>
            </select>
          </label>

          <label class="admin-wishlist-select">
            <span>Status</span>
            <select v-model="statusFilter">
              <option value="all">All</option>
              <option value="pending">Unverified</option>
              <option value="invited">Invite Sent</option>
              <option value="rejected">Denied</option>
            </select>
          </label>

          <button
            class="admin-wishlist-edit-button"
            type="button"
            :class="{ 'admin-wishlist-edit-button--active': editListMode }"
            @click="editListMode = !editListMode"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 20h9" />
              <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
            </svg>
            {{ editListMode ? 'Done' : 'Edit List' }}
          </button>
        </div>

        <div v-if="isLoading" class="admin-wishlist-empty-state">
          Loading wishlist accounts...
        </div>

        <div v-else class="admin-wishlist-table-wrap">
          <table class="admin-wishlist-table">
            <thead>
              <tr>
                <th>#</th>
                <th>ID No.</th>
                <th>Name</th>
                <th>Role</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(account, index) in filteredWishlistAccounts" :key="account.accountIdentifier">
                <td>{{ index + 1 }}</td>
                <td>{{ account.idNumber }}</td>
                <td>
                  <strong>{{ account.firstName }} {{ account.lastName }}</strong>
                  <span>{{ account.emailAddress }}</span>
                </td>
                <td>{{ account.roleLabel }}</td>
                <td>
                  <span class="admin-wishlist-status" :class="getStatusClass(account.accountStatus)">
                    {{ getStatusLabel(account.accountStatus) }}
                  </span>
                </td>
                <td>
                  <div class="admin-wishlist-actions">
                    <button
                      class="admin-wishlist-icon-button"
                      type="button"
                      aria-label="View account"
                      title="View account"
                      @click="openViewModal(account)"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                        <circle cx="12" cy="12" r="3" />
                      </svg>
                    </button>
                    <button
                      v-if="editListMode"
                      class="admin-wishlist-icon-button admin-wishlist-icon-button--deny"
                      type="button"
                      aria-label="Deny account"
                      title="Deny account"
                      @click="denyAccount(account)"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredWishlistAccounts.length === 0">
                <td colspan="6" class="admin-wishlist-empty-row">
                  No wishlist accounts match the current filters.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <div v-if="selectedAccount" class="admin-wishlist-modal-overlay" @click.self="closeModals">
        <section class="admin-wishlist-view-modal">
          <button class="admin-wishlist-modal-close" type="button" aria-label="Close" @click="closeModals">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>

          <div class="admin-wishlist-modal-heading">
            <h2>View Account</h2>
            <p>Verify requestor information before sending an invitation.</p>
          </div>

          <div class="admin-wishlist-detail-grid">
            <div>
              <span>ID Number</span>
              <strong>{{ selectedAccount.idNumber }}</strong>
            </div>
            <div>
              <span>Last Name</span>
              <strong>{{ selectedAccount.lastName }}</strong>
            </div>
            <div>
              <span>First Name</span>
              <strong>{{ selectedAccount.firstName }}</strong>
            </div>
            <div>
              <span>FIT Email</span>
              <strong>{{ selectedAccount.emailAddress }}</strong>
            </div>
            <div>
              <span>Role</span>
              <strong>{{ selectedAccount.roleLabel }}</strong>
            </div>
            <div>
              <span>Account Status</span>
              <strong>{{ getStatusLabel(selectedAccount.accountStatus) }}</strong>
            </div>
            <div>
              <span>Account Registered</span>
              <strong>{{ formatDisplayDate(selectedAccount.registeredAt) }}</strong>
            </div>
            <div>
              <span>Invite Sent</span>
              <strong>{{ formatNullableDate(selectedAccount.inviteSentAt) }}</strong>
            </div>
            <div>
              <span>Expires</span>
              <strong>{{ formatNullableDate(selectedAccount.inviteExpiresAt) }}</strong>
            </div>
            <div>
              <span>Accepted</span>
              <strong>{{ formatNullableDate(selectedAccount.inviteAcceptedAt) }}</strong>
            </div>
          </div>

          <div class="admin-wishlist-modal-actions">
            <button
              class="admin-wishlist-deny-button"
              type="button"
              :disabled="isProcessing"
              @click="denyAccount(selectedAccount)"
            >
              Deny
            </button>
            <button
              class="admin-wishlist-verify-button"
              type="button"
              :disabled="isProcessing"
              @click="openApprovalModal"
            >
              Verify
            </button>
          </div>
        </section>
      </div>

      <div v-if="approvalAccount" class="admin-wishlist-modal-overlay admin-wishlist-modal-overlay--top" @click.self="approvalAccount = null">
        <section class="admin-wishlist-approval-modal">
          <button class="admin-wishlist-modal-close" type="button" aria-label="Close" @click="approvalAccount = null">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>

          <div class="admin-wishlist-modal-heading">
            <h2>Send Invite</h2>
            <p>This verifies the account and sends an invitation link that expires in 7 days.</p>
          </div>

          <div class="admin-wishlist-approval-profile">
            <span class="admin-wishlist-avatar">{{ approvalAccount.initials }}</span>
            <div>
              <strong>{{ approvalAccount.firstName }} {{ approvalAccount.lastName }}</strong>
              <span>{{ approvalAccount.idNumber }}</span>
              <span>{{ approvalAccount.emailAddress }}</span>
            </div>
          </div>

          <div class="admin-wishlist-form-grid">
            <label>
              <span>Email</span>
              <input v-model="approvalForm.emailAddress" type="email" placeholder="requestor@fit.edu.ph" />
            </label>
            <label>
              <span>Roles</span>
              <select v-model="approvalForm.role">
                <option value="ROLE_ADMIN">Admin</option>
                <option value="ROLE_BORROWER">User: Student/Faculty</option>
              </select>
            </label>
            <label>
              <span>ID number</span>
              <input v-model="approvalForm.idNumber" type="text" readonly />
            </label>
            <label>
              <span>Last Name</span>
              <input v-model="approvalForm.lastName" type="text" readonly />
            </label>
            <label>
              <span>First Name</span>
              <input v-model="approvalForm.firstName" type="text" readonly />
            </label>
          </div>

          <div class="admin-wishlist-modal-actions">
            <button
              class="admin-wishlist-deny-button"
              type="button"
              :disabled="isProcessing"
              @click="denyAccount(approvalAccount)"
            >
              Deny
            </button>
            <button
              class="admin-wishlist-verify-button"
              type="button"
              :disabled="isProcessing"
              @click="verifyAccount"
            >
              {{ isProcessing ? 'Sending...' : 'Verify' }}
            </button>
          </div>
        </section>
      </div>

      <div v-if="showAddAdminModal" class="admin-wishlist-modal-overlay admin-wishlist-modal-overlay--top" @click.self="closeAddAdminModal">
        <section class="admin-wishlist-add-admin-modal">
          <button class="admin-wishlist-modal-close" type="button" aria-label="Close" @click="closeAddAdminModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>

          <div class="admin-wishlist-modal-heading">
            <h2>Add Admin Account</h2>
            <p>Create an administrator request record for verification.</p>
          </div>

          <div class="admin-wishlist-add-section-label">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="8" r="4" />
              <path d="M4 21a8 8 0 0 1 16 0" />
            </svg>
            Account Information
          </div>

          <form class="admin-wishlist-add-form" @submit.prevent="createAdminAccount">
            <label>
              <span>Last Name</span>
              <input v-model.trim="addAdminForm.lastName" type="text" placeholder="Torres" required />
            </label>
            <label>
              <span>First Name</span>
              <input v-model.trim="addAdminForm.firstName" type="text" placeholder="Joseph Romulus" required />
            </label>
            <label>
              <span>Email</span>
              <input v-model.trim="addAdminForm.emailAddress" type="email" placeholder="jrtorres@fit.edu.ph" required />
            </label>
            <label>
              <span>ID Number</span>
              <input v-model.trim="addAdminForm.idNumber" type="text" placeholder="2023*****" required />
            </label>
            <label>
              <span>Role</span>
              <input v-model="addAdminForm.role" type="text" readonly />
            </label>
            <label>
              <span>Password</span>
              <input v-model="addAdminForm.password" type="password" placeholder="Enter Password" required />
            </label>
            <label>
              <span>Confirm Password</span>
              <input v-model="addAdminForm.confirmPassword" type="password" placeholder="Confirm Password" required />
            </label>

            <p v-if="addAdminError" class="admin-wishlist-add-error">{{ addAdminError }}</p>

            <div class="admin-wishlist-modal-actions">
              <button class="admin-wishlist-cancel-button" type="button" @click="closeAddAdminModal">
                Cancel
              </button>
              <button class="admin-wishlist-verify-button" type="submit" :disabled="isProcessing">
                {{ isProcessing ? 'Creating...' : 'Create Account' }}
              </button>
            </div>
          </form>
        </section>
      </div>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/AdminWishlist.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { adminWishlistApi } from '@/services/adminWishlistApi.js';

const authStore = useAuthenticationStore();

const activeTab = ref('admin');
const searchText = ref('');
const sortMode = ref('newest');
const statusFilter = ref('all');
const editListMode = ref(false);
const isLoading = ref(false);
const isProcessing = ref(false);
const showAddAdminModal = ref(false);
const selectedAccount = ref(null);
const approvalAccount = ref(null);
const toastMessage = ref('');
const loadErrorMessage = ref('');
const wishlistAccounts = ref([]);
const addAdminError = ref('');

const approvalForm = reactive({
  emailAddress: '',
  role: 'ROLE_BORROWER',
  idNumber: '',
  lastName: '',
  firstName: '',
});

const addAdminForm = reactive({
  lastName: '',
  firstName: '',
  emailAddress: '',
  idNumber: '',
  role: 'Admin',
  password: '',
  confirmPassword: '',
});

const fallbackWishlistAccounts = [
  {
    accountIdentifier: 20240001,
    idNumber: '2024*****',
    firstName: 'Karin',
    lastName: 'Dela Fuente',
    emailAddress: 'kdelafuente@fit.edu.ph',
    roleDesignation: 'ROLE_ADMIN',
    roleLabel: 'Admin',
    accountType: 'Admin',
    accountStatus: 'pending',
    registeredAt: '2026-05-15T08:30:00+08:00',
    inviteSentAt: null,
    inviteExpiresAt: null,
    inviteAcceptedAt: null,
  },
  {
    accountIdentifier: 20230002,
    idNumber: '2023*****',
    firstName: 'Anabela',
    lastName: 'Valdes',
    emailAddress: 'avaldes@fit.edu.ph',
    roleDesignation: 'ROLE_BORROWER',
    roleLabel: 'User: Student',
    accountType: 'User',
    accountStatus: 'pending',
    registeredAt: '2026-05-16T10:15:00+08:00',
    inviteSentAt: null,
    inviteExpiresAt: null,
    inviteAcceptedAt: null,
  },
  {
    accountIdentifier: 20220003,
    idNumber: '2022*****',
    firstName: 'Miguel',
    lastName: 'Santos',
    emailAddress: 'msantos@fit.edu.ph',
    roleDesignation: 'ROLE_BORROWER',
    roleLabel: 'User: Faculty',
    accountType: 'Employee',
    accountStatus: 'invited',
    registeredAt: '2026-05-14T14:05:00+08:00',
    inviteSentAt: '2026-05-17T09:00:00+08:00',
    inviteExpiresAt: '2026-05-24T09:00:00+08:00',
    inviteAcceptedAt: null,
  },
];

const normalizedAccounts = computed(() => wishlistAccounts.value.map(normalizeWishlistAccount));

const wishlistTabs = computed(() => {
  const accounts = normalizedAccounts.value;
  return [
    { label: 'Admin', value: 'admin', count: accounts.filter((account) => account.accountType === 'Admin').length },
    { label: 'User', value: 'user', count: accounts.filter((account) => account.accountType === 'User').length },
    { label: 'Employee', value: 'employee', count: accounts.filter((account) => account.accountType === 'Employee').length },
  ];
});

const filteredWishlistAccounts = computed(() => {
  const query = searchText.value.trim().toLowerCase();
  const currentType = activeTab.value === 'admin' ? 'Admin' : activeTab.value === 'employee' ? 'Employee' : 'User';

  return normalizedAccounts.value
    .filter((account) => account.accountType === currentType)
    .filter((account) => statusFilter.value === 'all' || account.accountStatus === statusFilter.value)
    .filter((account) => {
      if (!query) return true;
      return [
        account.idNumber,
        account.firstName,
        account.lastName,
        account.emailAddress,
        account.roleLabel,
      ].some((value) => String(value).toLowerCase().includes(query));
    })
    .sort((first, second) => {
      if (sortMode.value === 'name') {
        return `${first.lastName} ${first.firstName}`.localeCompare(`${second.lastName} ${second.firstName}`);
      }
      if (sortMode.value === 'role') {
        return first.roleLabel.localeCompare(second.roleLabel);
      }
      if (sortMode.value === 'status') {
        return getStatusLabel(first.accountStatus).localeCompare(getStatusLabel(second.accountStatus));
      }
      return new Date(second.registeredAt).getTime() - new Date(first.registeredAt).getTime();
    });
});

onMounted(() => {
  loadWishlistAccounts();
});

async function loadWishlistAccounts() {
  isLoading.value = true;
  loadErrorMessage.value = '';
  const result = await adminWishlistApi.getWishlistAccounts(authStore.authToken);
  if (result.success) {
    wishlistAccounts.value = result.data.users || result.data || [];
  } else {
    wishlistAccounts.value = [];
    loadErrorMessage.value = result.error || 'Unable to load wishlist accounts from the backend.';
    showToast(loadErrorMessage.value);
  }
  isLoading.value = false;
}

function normalizeWishlistAccount(account) {
  const roleDesignation = account.roleDesignation || account.role_designation || 'ROLE_BORROWER';
  const firstName = account.firstName || account.first_name || '';
  const lastName = account.lastName || account.last_name || '';
  const emailAddress = account.emailAddress || account.email_address || '';
  const accountType = account.accountType || resolveAccountType(account, roleDesignation);
  const accountStatus = String(account.accountStatus || account.status || 'pending').toLowerCase();
  const idNumber = account.idNumber || account.studentIdNumber || account.accountIdentifier || account.account_identifier || 'N/A';

  return {
    ...account,
    accountIdentifier: account.accountIdentifier || account.account_identifier || idNumber,
    idNumber: formatIdNumber(idNumber),
    firstName,
    lastName,
    emailAddress,
    roleDesignation,
    roleLabel: account.roleLabel || resolveRoleLabel(account, roleDesignation),
    accountType,
    accountStatus,
    registeredAt: account.registeredAt || account.createdTimestamp || account.created_timestamp || new Date().toISOString(),
    inviteSentAt: account.inviteSentAt || account.invite_sent_at || null,
    inviteExpiresAt: account.inviteExpiresAt || account.invite_expires_at || null,
    inviteAcceptedAt: account.inviteAcceptedAt || account.invite_accepted_at || null,
    initials: `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase() || 'TR',
  };
}

function resolveAccountType(account, roleDesignation) {
  if (String(roleDesignation).toUpperCase().includes('ADMIN')) return 'Admin';
  const department = String(account.department || '').toLowerCase();
  if (department.includes('faculty') || department.includes('employee')) return 'Employee';
  return 'User';
}

function resolveRoleLabel(account, roleDesignation) {
  if (String(roleDesignation).toUpperCase().includes('ADMIN')) return 'Admin';
  const department = String(account.department || '').toLowerCase();
  if (department.includes('faculty') || department.includes('employee')) return 'User: Faculty';
  return 'User: Student';
}

function formatIdNumber(idNumber) {
  const value = String(idNumber);
  if (value.includes('*') || value === 'N/A') return value;
  if (value.length <= 4) return value;
  return `${value.slice(0, 4)}*****`;
}

function openViewModal(account) {
  selectedAccount.value = account;
}

function openApprovalModal() {
  approvalAccount.value = selectedAccount.value;
  approvalForm.emailAddress = selectedAccount.value.emailAddress;
  approvalForm.role = selectedAccount.value.roleDesignation;
  approvalForm.idNumber = selectedAccount.value.idNumber;
  approvalForm.lastName = selectedAccount.value.lastName;
  approvalForm.firstName = selectedAccount.value.firstName;
}

function closeModals() {
  selectedAccount.value = null;
  approvalAccount.value = null;
}

function openAddAdminModal() {
  resetAddAdminForm();
  showAddAdminModal.value = true;
}

function closeAddAdminModal() {
  showAddAdminModal.value = false;
  addAdminError.value = '';
}

async function createAdminAccount() {
  addAdminError.value = '';

  if (addAdminForm.password !== addAdminForm.confirmPassword) {
    addAdminError.value = 'Password and confirm password must match.';
    return;
  }

  const emailExists = normalizedAccounts.value.some(
    (account) => account.emailAddress.toLowerCase() === addAdminForm.emailAddress.toLowerCase()
  );
  if (emailExists) {
    addAdminError.value = 'An account with this email already exists in the wishlist.';
    return;
  }

  const accountPayload = {
    lastName: addAdminForm.lastName,
    firstName: addAdminForm.firstName,
    emailAddress: addAdminForm.emailAddress,
    idNumber: addAdminForm.idNumber,
    passwordText: addAdminForm.password,
  };

  isProcessing.value = true;
  const result = await adminWishlistApi.createAdminAccount(accountPayload, authStore.authToken);
  isProcessing.value = false;

  if (!result.success) {
    addAdminError.value = result.error || 'Unable to create admin account.';
    return;
  }

  activeTab.value = 'admin';
  showAddAdminModal.value = false;
  await loadWishlistAccounts();
  showToast('Account created!');
  resetAddAdminForm();
}

function resetAddAdminForm() {
  addAdminForm.lastName = '';
  addAdminForm.firstName = '';
  addAdminForm.emailAddress = '';
  addAdminForm.idNumber = '';
  addAdminForm.role = 'Admin';
  addAdminForm.password = '';
  addAdminForm.confirmPassword = '';
  addAdminError.value = '';
}

async function verifyAccount() {
  if (!approvalAccount.value) return;
  isProcessing.value = true;
  const result = await adminWishlistApi.verifyAccount(approvalAccount.value.accountIdentifier, authStore.authToken);
  if (result.success) {
    closeModals();
    await loadWishlistAccounts();
    showToast('Invitation sent. Verified accounts are removed from the wishlist.');
  } else {
    showToast(result.error || 'Unable to verify account.');
  }
  isProcessing.value = false;
}

async function denyAccount(account) {
  if (!account) return;
  isProcessing.value = true;
  const result = await adminWishlistApi.denyAccount(account.accountIdentifier, authStore.authToken);
  if (result.success) {
    approvalAccount.value = null;
    selectedAccount.value = null;
    await loadWishlistAccounts();
    showToast('Account request denied.');
  } else {
    showToast(result.error || 'Unable to deny account.');
  }
  isProcessing.value = false;
}

function removeAccountFromWishlist(accountIdentifier) {
  wishlistAccounts.value = wishlistAccounts.value.filter((account) => {
    const currentIdentifier = account.accountIdentifier || account.account_identifier;
    return String(currentIdentifier) !== String(accountIdentifier);
  });
}

function updateAccountStatus(accountIdentifier, status) {
  wishlistAccounts.value = wishlistAccounts.value.map((account) => {
    const currentIdentifier = account.accountIdentifier || account.account_identifier;
    if (String(currentIdentifier) !== String(accountIdentifier)) return account;
    return { ...account, accountStatus: status, status };
  });
}

function getStatusLabel(status) {
  const normalized = String(status || '').toLowerCase();
  if (normalized === 'approved' || normalized === 'verified') return 'Verified';
  if (normalized === 'rejected' || normalized === 'denied') return 'Denied';
  if (normalized === 'invited') return 'Invite Sent';
  return 'Unverified';
}

function getStatusClass(status) {
  const normalized = String(status || '').toLowerCase();
  if (normalized === 'rejected' || normalized === 'denied') return 'admin-wishlist-status--denied';
  if (normalized === 'invited') return 'admin-wishlist-status--invited';
  return 'admin-wishlist-status--pending';
}

function formatDisplayDate(value) {
  if (!value) return 'N/A';
  return new Intl.DateTimeFormat('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date(value));
}

function formatNullableDate(value) {
  if (!value) return 'N/A';
  return formatDisplayDate(value);
}

function showToast(message) {
  toastMessage.value = message;
  window.setTimeout(() => {
    if (toastMessage.value === message) toastMessage.value = '';
  }, 2800);
}
</script>
