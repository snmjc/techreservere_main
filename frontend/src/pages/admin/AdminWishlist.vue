<!-- ===== AI GENERATED: AdminWishlistPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="admin-wishlist-page">
      <div class="admin-wishlist-header">
        <div>
          <p class="admin-wishlist-kicker">Requestor account lifecycle</p>
          <h1>Request List</h1>
          <p>Review requestor accounts, inspect Clerk invite details, and send or resend invitations safely.</p>
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
          <button v-if="activeTab !== 'employee'" class="admin-wishlist-add-button" type="button" @click="openAddAccountModal">
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
          @click="handleTabChange(tab.value)"
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
              <option value="unverified">Unverified</option>
              <option value="verified">Verified</option>
              <option value="approved">Approved</option>
              <option value="expired">Expired</option>
              <option value="rejected">Denied</option>
            </select>
          </label>

          <label v-if="activeTab === 'user'" class="admin-wishlist-select">
            <span>User role</span>
            <select v-model="userRoleFilter">
              <option value="all">All</option>
              <option value="student">Student</option>
              <option value="faculty">Faculty</option>
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

        <div v-if="isActiveWishlistTabLoading && filteredWishlistAccounts.length === 0" class="admin-wishlist-empty-state">
          Loading request accounts...
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
                <th>Proof</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(account, index) in paginatedWishlistAccounts" :key="account.accountIdentifier">
                <td>{{ wishlistPageStart + index }}</td>
                <td>{{ account.idNumber }}</td>
                <td>
                  <strong>{{ account.firstName }} {{ account.lastName }}</strong>
                  <span>{{ account.emailAddress }}</span>
                </td>
                <td>{{ account.role }}</td>
                <td>
                  <span class="admin-wishlist-status" :class="getStatusClass(account.accountStatus)">
                    {{ getStatusLabel(account.accountStatus) }}
                  </span>
                </td>
                <td>
                  <div v-if="hasSupportingDocument(account)" class="admin-wishlist-proof-cell">
                    <button
                      v-if="isPdfProof(account)"
                      class="admin-wishlist-proof-button"
                      type="button"
                      :disabled="previewIsLoading"
                      @click="openProofPreview(account)"
                    >
                      Preview PDF
                    </button>
                    <button
                      class="admin-wishlist-proof-button"
                      type="button"
                      :disabled="previewIsLoading"
                      @click="downloadProof(account)"
                    >
                      Download proof
                    </button>
                  </div>
                  <span v-else class="admin-wishlist-proof-empty">N/A</span>
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
                      class="admin-wishlist-icon-button admin-wishlist-icon-button--invite"
                      type="button"
                      aria-label="Send invite"
                      title="Send invite"
                      :disabled="!canSendInvite(account)"
                      @click="openApprovalModal(account, 'send')"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="5" width="18" height="14" rx="2" />
                        <path d="m3 7 9 6 9-6" />
                      </svg>
                    </button>
                    <button
                      class="admin-wishlist-icon-button admin-wishlist-icon-button--resend"
                      type="button"
                      aria-label="Resend invite"
                      title="Resend invite"
                      :disabled="!canResendInvite(account)"
                      @click="openApprovalModal(account, 'resend')"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
                        <path d="M3 21v-5h5" />
                        <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
                        <path d="M16 8h5V3" />
                      </svg>
                    </button>
                    <button
                      v-if="editListMode"
                      class="admin-wishlist-icon-button admin-wishlist-icon-button--deny"
                      type="button"
                      aria-label="Deny account"
                      title="Deny account"
                      @click="openDenialModal(account)"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                      </svg>
                    </button>
                    <button
                      v-if="editListMode"
                      class="admin-wishlist-icon-button admin-wishlist-icon-button--delete"
                      type="button"
                      aria-label="Delete account request"
                      title="Delete account request"
                      @click="openDeleteModal(account)"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18" />
                        <path d="M8 6V4h8v2" />
                        <path d="M10 11v6" />
                        <path d="M14 11v6" />
                        <path d="M19 6l-1 14H6L5 6" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredWishlistAccounts.length === 0">
                <td colspan="7" class="admin-wishlist-empty-row">
                  No request accounts match the current filters.
                </td>
              </tr>
            </tbody>
          </table>
          <div class="admin-wishlist-pagination">
            <button type="button" :disabled="wishlistCurrentPage === 1" @click="wishlistCurrentPage -= 1">Previous</button>
            <span>Showing {{ wishlistPageStart }}-{{ wishlistPageEnd }} of {{ filteredWishlistAccounts.length }}</span>
            <button type="button" :disabled="wishlistCurrentPage === wishlistTotalPages" @click="wishlistCurrentPage += 1">Next</button>
          </div>
        </div>
      </section>

      <AdminWishlistReviewModals
        v-model:approval-confirm-email="approvalForm.confirmEmail"
        v-model:denial-confirm-email="denialConfirmEmail"
        v-model:denial-confirm-password="denialConfirmPassword"
        :selected-account="selectedAccount"
        :approval-account="approvalAccount"
        :approval-mode="approvalMode"
        :approval-form-error="approvalFormError"
        :denial-account="denialAccount"
        :denial-form-error="denialFormError"
        :preview-account="previewAccount"
        :preview-document-url="previewDocumentUrl"
        :preview-is-loading="previewIsLoading"
        :preview-error-message="previewErrorMessage"
        :current-admin-email="currentAdminEmail"
        :is-processing="isProcessing"
        :is-approval-confirmation-ready="isApprovalConfirmationReady"
        :is-denial-confirmation-ready="isDenialConfirmationReady"
        :get-invite-modal-title="getInviteModalTitle"
        :get-invite-modal-description="getInviteModalDescription"
        :get-invite-submit-label="getInviteSubmitLabel"
        :get-processing-label="getProcessingLabel"
        @close-view="closeModals"
        @close-approval="closeApprovalModal"
        @close-denial="closeDenialModal"
        @close-proof="closeProofPreview"
        @open-proof="openProofPreview"
        @download-proof="downloadProof"
        @submit-invite="submitInviteAction"
        @deny-account="denyAccount"
      />

      <div v-if="deleteAccountRequest" class="admin-wishlist-modal-overlay admin-wishlist-modal-overlay--top" @click.self="!isProcessing && closeDeleteModal()">
        <section class="admin-wishlist-denial-modal">
          <button class="admin-wishlist-modal-close" type="button" aria-label="Close" :disabled="isProcessing" @click="closeDeleteModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>

          <div class="admin-wishlist-modal-heading">
            <h2>Delete Account Request</h2>
            <p>This will permanently remove only the selected request account from the Wishlist database and close this modal when successful.</p>
          </div>

          <div class="admin-wishlist-approval-profile admin-wishlist-denial-profile">
            <span class="admin-wishlist-avatar" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="8" r="4" />
                <path d="M4 21a8 8 0 0 1 16 0" />
              </svg>
            </span>
            <div class="admin-wishlist-invite-summary">
              <em :class="getAccountTypeBadgeClass(deleteAccountRequest.accountType)">
                {{ deleteAccountRequest.accountType }}
              </em>
              <div class="admin-wishlist-invite-details">
                <p>
                  <span>Work ID Number</span>
                  <strong>{{ deleteAccountRequest.rawIdNumber || deleteAccountRequest.idNumber }}</strong>
                </p>
                <p>
                  <span>Name</span>
                  <strong>{{ deleteAccountRequest.fullName }}</strong>
                </p>
                <p>
                  <span>Phone Number</span>
                  <strong>{{ deleteAccountRequest.contactNumber || 'N/A' }}</strong>
                </p>
                <p>
                  <span>Role</span>
                  <strong>{{ deleteAccountRequest.role }}</strong>
                </p>
                <p>
                  <span>Request Email</span>
                  <strong>{{ deleteAccountRequest.emailAddress }}</strong>
                </p>
              </div>
            </div>
          </div>

          <label class="admin-wishlist-confirm-field">
            <span>Type your admin email <strong>{{ currentAdminEmail || 'from your account' }}</strong> to confirm deletion:</span>
            <input
              v-model.trim="deleteConfirmEmail"
              type="email"
              :placeholder="currentAdminEmail || 'admin@techreserve.edu.ph'"
              autocomplete="off"
              :disabled="isProcessing"
            />
          </label>

          <label class="admin-wishlist-confirm-field">
            <span>Type your admin password to confirm deletion:</span>
            <input
              v-model="deleteConfirmPassword"
              type="password"
              placeholder="Admin password"
              autocomplete="current-password"
              :disabled="isProcessing"
            />
          </label>

          <p v-if="deleteFormError" class="admin-wishlist-add-error">{{ deleteFormError }}</p>

          <div class="admin-wishlist-modal-actions">
            <button
              class="admin-wishlist-cancel-button"
              type="button"
              :disabled="isProcessing"
              @click="closeDeleteModal"
            >
              Cancel
            </button>
            <button
              class="admin-wishlist-deny-button"
              type="button"
              :disabled="isProcessing || !isDeleteConfirmationReady"
              @click="deleteWishlistAccount"
            >
              {{ isProcessing ? 'Deleting Request...' : 'Delete Request' }}
            </button>
          </div>
        </section>
      </div>

      <AdminWishlistCreateAccountModals
        ref="createAccountModals"
        :accounts="allNormalizedAccounts"
        @created="handleAccountCreated"
      />
      <DataRequestStatusFloater :items="wishlistStatusItems" />
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useAuth } from '@clerk/vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import AdminWishlistCreateAccountModals from './components/AdminWishlistCreateAccountModals.vue';
import AdminWishlistReviewModals from './components/AdminWishlistReviewModals.vue';
import DataRequestStatusFloater from '@/shared/components/DataRequestStatusFloater.vue';
import { useAdminWishlistActions } from './composables/useAdminWishlistActions.js';
import { useWishlistTabResource } from './services/wishlistTabResource.js';
import '@/shared/components/adminSidebarLayout.css';
import './css/AdminWishlistList.css';
import './css/AdminWishlistModals.css';
import './css/AdminWishlistForms.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { adminWishlistApi } from '@/services/adminWishlistApi.js';
import { getClerkToken } from '@/modules/authentication/utils/clerkAuthUtils.js';
import { getStoredAuthToken, normalizeAuthToken } from '@/shared/utils/authToken.js';
import {
  filterWishlistAccounts,
  formatDisplayDateTime,
  getAccountTypeBadgeClass,
  getStatusClass,
  getStatusLabel,
  isPdfProof,
  getUniqueRequestAccounts,
  normalizeWishlistAccount,
} from './wishlist/adminWishlistHelpers.js';

const authStore = useAuthenticationStore();
const { getToken } = useAuth();

const activeTab = ref('user');
const searchText = ref('');
const sortMode = ref('newest');
const statusFilter = ref('all');
const userRoleFilter = ref('all');
const wishlistPagesByTab = reactive({
  admin: 1,
  user: 1,
  employee: 1,
});
const wishlistPageSize = 10;
const createAccountModals = ref(null);
const editListMode = ref(false);
const toastMessage = ref('');
const previewAccount = ref(null);
const previewDocumentUrl = ref('');
const previewIsLoading = ref(false);
const previewErrorMessage = ref('');

const currentAdminEmail = computed(() => {
  const account = authStore.accountData || authStore.clerkAccountData || {};
  return String(account.emailAddress || account.email || '').trim();
});
const wishlistCacheScope = computed(() => {
  const account = authStore.accountData || authStore.clerkAccountData || {};
  const tokenScope = normalizeAuthToken(authStore.authToken) || getStoredAuthToken() || '';
  return String(
    account.accountIdentifier
      || account.emailAddress
      || account.email
      || currentAdminEmail.value
      || tokenScope.slice(-32)
      || 'anonymous',
  ).trim();
});
const wishlistTabResources = {
  admin: createWishlistTabResource('admin', 'Admin Requests'),
  user: createWishlistTabResource('user', 'User Requests'),
  employee: createWishlistTabResource('employee', 'Employee Requests'),
};
const activeWishlistResource = computed(() => wishlistTabResources[activeTab.value] || wishlistTabResources.user);
const wishlistAccountsByTab = computed(() => ({
  admin: normalizeTabAccounts(wishlistTabResources.admin.data.value),
  user: normalizeTabAccounts(wishlistTabResources.user.data.value),
  employee: normalizeTabAccounts(wishlistTabResources.employee.data.value),
}));
const normalizedAccounts = computed(() => wishlistAccountsByTab.value[activeTab.value] || []);
const allNormalizedAccounts = computed(() => [
  ...wishlistAccountsByTab.value.admin,
  ...wishlistAccountsByTab.value.user,
  ...wishlistAccountsByTab.value.employee,
]);
const loadErrorMessage = computed(() => activeWishlistResource.value.error.value);

const wishlistTabs = computed(() => {
  return [
    { label: 'Admin', value: 'admin', count: wishlistAccountsByTab.value.admin.length },
    { label: 'User', value: 'user', count: wishlistAccountsByTab.value.user.length },
    { label: 'Employee', value: 'employee', count: wishlistAccountsByTab.value.employee.length },
  ];
});

const filteredWishlistAccounts = computed(() => {
  return filterWishlistAccounts(normalizedAccounts.value, {
    activeTab: activeTab.value,
    searchText: searchText.value,
    sortMode: sortMode.value,
    statusFilter: statusFilter.value,
    userRoleFilter: userRoleFilter.value,
  });
});
const isActiveWishlistTabLoading = computed(() => activeWishlistResource.value.isLoading.value);
const wishlistTotalPages = computed(() => Math.max(1, Math.ceil(filteredWishlistAccounts.value.length / wishlistPageSize)));
const wishlistCurrentPage = computed({
  get: () => wishlistPagesByTab[activeTab.value] || 1,
  set: (pageNumber) => {
    wishlistPagesByTab[activeTab.value] = pageNumber;
  },
});
const paginatedWishlistAccounts = computed(() => {
  const startIndex = (wishlistCurrentPage.value - 1) * wishlistPageSize;
  return filteredWishlistAccounts.value.slice(startIndex, startIndex + wishlistPageSize);
});
const wishlistPageStart = computed(() => (
  filteredWishlistAccounts.value.length === 0 ? 0 : ((wishlistCurrentPage.value - 1) * wishlistPageSize) + 1
));
const wishlistPageEnd = computed(() => Math.min(wishlistCurrentPage.value * wishlistPageSize, filteredWishlistAccounts.value.length));
const wishlistStatusItems = computed(() => [
  wishlistTabResources.admin.statusItem.value,
  wishlistTabResources.user.statusItem.value,
  wishlistTabResources.employee.statusItem.value,
]);

const {
  isProcessing,
  selectedAccount,
  approvalAccount,
  approvalMode,
  approvalForm,
  approvalFormError,
  denialAccount,
  denialConfirmEmail,
  denialConfirmPassword,
  denialFormError,
  deleteAccountRequest,
  deleteConfirmEmail,
  deleteConfirmPassword,
  deleteFormError,
  isApprovalConfirmationReady,
  isDenialConfirmationReady,
  isDeleteConfirmationReady,
  openViewModal,
  openApprovalModal,
  closeModals,
  closeApprovalModal,
  openDenialModal,
  closeDenialModal,
  openDeleteModal,
  closeDeleteModal,
  submitInviteAction,
  denyAccount,
  deleteWishlistAccount,
  canSendInvite,
  canResendInvite,
  getInviteModalTitle,
  getInviteModalDescription,
  getInviteSubmitLabel,
  getProcessingLabel,
} = useAdminWishlistActions({
  authStore,
  currentAdminEmail,
  loadWishlistAccounts,
  showToast,
});

onMounted(() => {
  loadWishlistAccounts();
});

watch([searchText, sortMode, statusFilter, userRoleFilter], () => {
  wishlistCurrentPage.value = 1;
});

watch(wishlistTotalPages, (pageCount) => {
  if (wishlistCurrentPage.value > pageCount) {
    wishlistCurrentPage.value = pageCount;
  }
});

function handleTabChange(tabName) {
  activeTab.value = tabName;
  searchText.value = '';
  statusFilter.value = 'all';
  userRoleFilter.value = 'all';

  if (activeWishlistResource.value.state.value === 'idle') {
    loadActiveWishlistTab();
  }
}

async function loadWishlistAccounts() {
  const results = await Promise.allSettled(Object.values(wishlistTabResources).map((resource) => resource.load()));
  const failedCount = results.filter((result) => result.status === 'rejected').length
    + Object.values(wishlistTabResources).filter((resource) => resource.state.value === 'error').length;
  if (failedCount > 0) {
    showToast('Some wishlist tabs failed to load. Check Data Status for details.');
  }
}

async function loadActiveWishlistTab() {
  await activeWishlistResource.value.load();
}

function createWishlistTabResource(tabKey, label) {
  return useWishlistTabResource({
    tabKey,
    label,
    cacheScope: wishlistCacheScope,
    fetchAccounts: fetchWishlistAccountsByTab,
  });
}

async function fetchWishlistAccountsByTab(tabKey) {
  const authToken = await ensureWishlistToken();
  const result = await adminWishlistApi.getWishlistAccountsByType(authToken, tabKey);
  if (!result.success) {
    throw new Error(result.error || 'Unable to load request accounts from the backend.');
  }

  const users = result.data?.users ?? result.data;
  if (!Array.isArray(users)) {
    throw new Error('Wishlist API returned an invalid account list.');
  }

  return users;
}

function normalizeTabAccounts(accounts) {
  return getUniqueRequestAccounts(accounts.map(normalizeWishlistAccount));
}

async function ensureWishlistToken() {
  const currentToken = normalizeAuthToken(authStore.authToken) || getStoredAuthToken();
  if (currentToken) {
    return currentToken;
  }

  const clerkToken = await getClerkToken(getToken).catch(() => null);
  const normalizedClerkToken = normalizeAuthToken(clerkToken);
  if (!normalizedClerkToken) {
    return null;
  }

  const existingAccount = authStore.clerkAccountData || authStore.accountData;
  if (existingAccount) {
    authStore.setClerkAuth(normalizedClerkToken, existingAccount, { rememberSession: true });
  }

  return normalizedClerkToken;
}

function openAddAccountModal() {
  createAccountModals.value?.openForTab(activeTab.value);
}

async function openProofPreview(account) {
  if (!hasSupportingDocument(account)) {
    previewAccount.value = null;
    revokePreviewDocumentUrl();
    previewErrorMessage.value = '';
    showToast('No validation PDF was submitted for this request.');
    return;
  }

  previewAccount.value = account;
  revokePreviewDocumentUrl();
  previewErrorMessage.value = '';

  if (!isPdfProof(account)) {
    previewErrorMessage.value = 'This file is not available for in-app PDF preview. Please use the download button instead.';
    return;
  }

  previewIsLoading.value = true;
  const result = await adminWishlistApi.getSupportingDocumentBlob(account.accountIdentifier, authStore.authToken);
  previewIsLoading.value = false;

  if (!result.success) {
    previewErrorMessage.value = result.error || 'Unable to load the supporting document preview.';
    return;
  }

  previewDocumentUrl.value = URL.createObjectURL(result.data.blob);
}

function closeProofPreview() {
  previewAccount.value = null;
  revokePreviewDocumentUrl();
  previewErrorMessage.value = '';
}

async function downloadProof(account) {
  if (!hasSupportingDocument(account) || previewIsLoading.value) {
    return;
  }

  previewIsLoading.value = true;
  const result = await adminWishlistApi.getSupportingDocumentBlob(account.accountIdentifier, authStore.authToken);
  previewIsLoading.value = false;

  if (!result.success) {
    showToast(result.error || 'Unable to download the supporting document.');
    return;
  }

  const objectUrl = URL.createObjectURL(result.data.blob);
  const link = document.createElement('a');
  link.href = objectUrl;
  link.download = account.supportingDocumentName || 'signup-proof';
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.setTimeout(() => URL.revokeObjectURL(objectUrl), 0);
}

function hasSupportingDocument(account) {
  return Boolean(account?.supportingDocumentName && account?.supportingDocumentPath);
}

function revokePreviewDocumentUrl() {
  if (previewDocumentUrl.value) {
    URL.revokeObjectURL(previewDocumentUrl.value);
    previewDocumentUrl.value = '';
  }
}

async function handleAccountCreated(payload) {
  const accountType = typeof payload === 'string' ? payload : payload?.type;
  const defaultPassword = typeof payload === 'object' ? payload?.data?.defaultPassword : '';

  activeTab.value = accountType || 'admin';
  await loadWishlistAccounts();
  if (accountType === 'admin') {
    if (defaultPassword) {
      showToast(`Admin request created. Default password: ${defaultPassword}. Send the Clerk email invitation to continue verification.`);
      return;
    }

    showToast('Admin request created. Send the Clerk email invitation to continue verification.');
    return;
  }

  showToast('Account created!');
}

function showToast(message) {
  toastMessage.value = message;
  window.setTimeout(() => {
    if (toastMessage.value === message) toastMessage.value = '';
  }, 4200);
}
</script>
