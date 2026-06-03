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
          <h1>Requests Hub</h1>
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
          <button class="admin-wishlist-add-button" type="button" @click="openAddAccountModal">
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
              <option value="not_invited">Not invited</option>
              <option value="verified">Verified</option>
              <option value="approved">Approved</option>
              <option value="unverified">Unverified</option>
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

        <div v-if="isLoading" class="admin-wishlist-empty-state">
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
              <tr v-for="(account, index) in filteredWishlistAccounts" :key="account.accountIdentifier">
                <td>{{ index + 1 }}</td>
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
                  <div v-if="account.supportingDocumentData" class="admin-wishlist-proof-cell">
                    <a
                      :href="account.supportingDocumentData"
                      :download="account.supportingDocumentName || 'signup-proof'"
                    >
                      Download proof
                    </a>
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
                      aria-label="Approve and email"
                      title="Approve and email"
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
                      class="admin-wishlist-icon-button admin-wishlist-icon-button--verify"
                      type="button"
                      aria-label="Verify email and approve"
                      title="Verify email and approve"
                      :disabled="!canVerifyEmail(account)"
                      @click="openApprovalModal(account, 'verify')"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6 9 17l-5-5" />
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
            <p>Review account and invitation details from the database.</p>
          </div>

          <div class="admin-wishlist-view-account-grid">
            <div class="admin-wishlist-view-account-main">
              <p><strong>Last Name:</strong> <span>{{ selectedAccount.lastName }}</span></p>
              <p><strong>First Name:</strong> <span>{{ selectedAccount.firstName }}</span></p>
              <p><strong>ID Number:</strong> <span>{{ selectedAccount.rawIdNumber || selectedAccount.idNumber }}</span></p>
              <p><strong>Username:</strong> <span>{{ selectedAccount.username || 'N/A' }}</span></p>
              <p><strong>Email:</strong> <span>{{ selectedAccount.emailAddress }}</span></p>
              <p><strong>Role:</strong> <span>{{ selectedAccount.role }}</span></p>
              <p><strong>Account Status:</strong> <span>{{ getStatusLabel(selectedAccount.accountStatus) }}</span></p>
              <p><strong>Account Registered:</strong> <span>{{ formatDisplayDateTime(selectedAccount.registeredAt) }}</span></p>
              <p><strong>Account Type:</strong> <span>{{ selectedAccount.accountType }}</span></p>
              <p><strong>Proof File:</strong> <span>{{ selectedAccount.supportingDocumentName || 'N/A' }}</span></p>
            </div>
            <div class="admin-wishlist-view-account-side">
              <p><strong>Invite Sent:</strong> <span>{{ getInviteSentStatus(selectedAccount) }}</span></p>
              <p><strong>Invited By:</strong> <span>{{ selectedAccount.inviteInvitedBy || 'N/A' }}</span></p>
              <p><strong>Invite Sent Date:</strong> <span>{{ formatNullableDateTime(selectedAccount.inviteSentAt) }}</span></p>
              <p><strong>Expiration Date:</strong> <span>{{ formatNullableDateTime(selectedAccount.inviteExpiresAt) }}</span></p>
              <p><strong>Accepted Status:</strong> <span>{{ getAcceptedStatus(selectedAccount) }}</span></p>
              <p><strong>Accepted Date:</strong> <span>{{ formatNullableDateTime(selectedAccount.inviteAcceptedAt) }}</span></p>
            </div>
          </div>

          <div v-if="selectedAccount.supportingDocumentData" class="admin-wishlist-proof-actions">
            <a
              class="admin-wishlist-proof-link"
              :href="selectedAccount.supportingDocumentData"
              :download="selectedAccount.supportingDocumentName || 'signup-proof'"
            >
              Download proof
            </a>
          </div>

          <div class="admin-wishlist-modal-actions">
            <button
              class="admin-wishlist-close-button"
              type="button"
              @click="closeModals"
            >
              Close
            </button>
          </div>
        </section>
      </div>

      <div v-if="approvalAccount" class="admin-wishlist-modal-overlay admin-wishlist-modal-overlay--top" @click.self="!isProcessing && closeApprovalModal()">
        <section class="admin-wishlist-approval-modal">
          <button class="admin-wishlist-modal-close" type="button" aria-label="Close" :disabled="isProcessing" @click="closeApprovalModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>

          <div class="admin-wishlist-modal-heading">
            <h2>{{ getInviteModalTitle(approvalAccount) }}</h2>
            <p>{{ getInviteModalDescription(approvalAccount) }}</p>
          </div>

          <div class="admin-wishlist-approval-profile">
            <span class="admin-wishlist-avatar" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="8" r="4" />
                <path d="M4 21a8 8 0 0 1 16 0" />
              </svg>
            </span>
            <div class="admin-wishlist-invite-summary">
              <em :class="getAccountTypeBadgeClass(approvalAccount.accountType)">
                {{ approvalAccount.accountType }}
              </em>
              <div class="admin-wishlist-invite-details">
                <p>
                  <span>Last Name</span>
                  <strong>{{ approvalAccount.lastName }}</strong>
                </p>
                <p>
                  <span>First Name</span>
                  <strong>{{ approvalAccount.firstName }}</strong>
                </p>
                <p>
                  <span>ID Number</span>
                  <strong>{{ approvalAccount.rawIdNumber || approvalAccount.idNumber }}</strong>
                </p>
                <p>
                  <span>Username</span>
                  <strong>{{ approvalAccount.username || 'N/A' }}</strong>
                </p>
                <p>
                  <span>{{ getApprovalEmailLabel(approvalAccount) }}</span>
                  <strong>{{ approvalAccount.emailAddress }}</strong>
                </p>
                <p>
                  <span>Role</span>
                  <strong>{{ approvalAccount.role }}</strong>
                </p>
                <p v-if="approvalMode === 'resend' || approvalMode === 'verify'">
                  <span>Last Invite Sent</span>
                  <strong>{{ formatNullableDateTime(approvalAccount.inviteSentAt) }}</strong>
                </p>
                <p v-if="approvalMode === 'verify'">
                  <span>Email Verified</span>
                  <strong>{{ formatNullableDateTime(approvalAccount.inviteAcceptedAt) }}</strong>
                </p>
              </div>
            </div>
          </div>

          <div v-if="approvalAccount.supportingDocumentData" class="admin-wishlist-proof-actions">
            <a
              class="admin-wishlist-proof-link"
              :href="approvalAccount.supportingDocumentData"
              :download="approvalAccount.supportingDocumentName || 'signup-proof'"
            >
              Download proof
            </a>
          </div>

          <label class="admin-wishlist-confirm-field">
            <span>Type your admin email <strong>{{ currentAdminEmail || 'from your account' }}</strong> to confirm:</span>
            <input
              v-model.trim="approvalForm.confirmEmail"
              type="email"
              :placeholder="currentAdminEmail || 'admin@fit.edu.ph'"
              autocomplete="off"
              :disabled="isProcessing"
            />
          </label>

          <p v-if="approvalFormError" class="admin-wishlist-add-error">{{ approvalFormError }}</p>

          <div class="admin-wishlist-modal-actions">
            <button
              class="admin-wishlist-cancel-button"
              type="button"
              :disabled="isProcessing"
              @click="closeApprovalModal"
            >
              Cancel
            </button>
            <button
              class="admin-wishlist-send-invite-button"
              type="button"
              :disabled="isProcessing || !isApprovalConfirmationReady"
              @click="verifyAccount"
            >
              {{ isProcessing ? getProcessingLabel() : getInviteSubmitLabel(approvalAccount) }}
            </button>
          </div>
        </section>
      </div>

      <div v-if="denialAccount" class="admin-wishlist-modal-overlay admin-wishlist-modal-overlay--top" @click.self="closeDenialModal">
        <section class="admin-wishlist-denial-modal">
          <button class="admin-wishlist-modal-close" type="button" aria-label="Close" @click="closeDenialModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>

          <div class="admin-wishlist-modal-heading">
            <h2>Deny Account Request</h2>
            <p>Review the request email before confirming denial. This will prevent the account from being invited.</p>
          </div>

          <div class="admin-wishlist-approval-profile admin-wishlist-denial-profile">
            <span class="admin-wishlist-avatar" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="8" r="4" />
                <path d="M4 21a8 8 0 0 1 16 0" />
              </svg>
            </span>
            <div class="admin-wishlist-invite-summary">
              <em :class="getAccountTypeBadgeClass(denialAccount.accountType)">
                {{ denialAccount.accountType }}
              </em>
              <div class="admin-wishlist-invite-details">
                <p>
                  <span>Name</span>
                  <strong>{{ denialAccount.fullName }}</strong>
                </p>
                <p>
                  <span>ID Number</span>
                  <strong>{{ denialAccount.rawIdNumber || denialAccount.idNumber }}</strong>
                </p>
                <p>
                  <span>Email to deny</span>
                  <strong>{{ denialAccount.emailAddress }}</strong>
                </p>
                <p>
                  <span>Role</span>
                  <strong>{{ denialAccount.role }}</strong>
                </p>
              </div>
            </div>
          </div>

          <label class="admin-wishlist-confirm-field">
            <span>Type the exact email <strong>{{ denialAccount.emailAddress }}</strong> to confirm denial:</span>
            <input
              v-model.trim="denialConfirmEmail"
              type="email"
              :placeholder="denialAccount.emailAddress"
              autocomplete="off"
            />
          </label>

          <label class="admin-wishlist-confirm-field">
            <span>Type your admin password to confirm denial:</span>
            <input
              v-model="denialConfirmPassword"
              type="password"
              placeholder="Admin password"
              autocomplete="current-password"
            />
          </label>

          <p v-if="denialFormError" class="admin-wishlist-add-error">{{ denialFormError }}</p>

          <div class="admin-wishlist-modal-actions">
            <button
              class="admin-wishlist-cancel-button"
              type="button"
              :disabled="isProcessing"
              @click="closeDenialModal"
            >
              Cancel
            </button>
            <button
              class="admin-wishlist-deny-button"
              type="button"
              :disabled="isProcessing || !isDenialConfirmationReady"
              @click="denyAccount"
            >
              {{ isProcessing ? 'Denying...' : 'Deny Request' }}
            </button>
          </div>
        </section>
      </div>

      <div v-if="deleteAccountRequest" class="admin-wishlist-modal-overlay admin-wishlist-modal-overlay--top" @click.self="closeDeleteModal">
        <section class="admin-wishlist-denial-modal">
          <button class="admin-wishlist-modal-close" type="button" aria-label="Close" @click="closeDeleteModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>

          <div class="admin-wishlist-modal-heading">
            <h2>Delete Account Request</h2>
            <p>This will permanently remove the request and its invitation record from the database.</p>
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
                  <span>Name</span>
                  <strong>{{ deleteAccountRequest.fullName }}</strong>
                </p>
                <p>
                  <span>ID Number</span>
                  <strong>{{ deleteAccountRequest.rawIdNumber || deleteAccountRequest.idNumber }}</strong>
                </p>
                <p>
                  <span>Email to delete</span>
                  <strong>{{ deleteAccountRequest.emailAddress }}</strong>
                </p>
                <p>
                  <span>Role</span>
                  <strong>{{ deleteAccountRequest.role }}</strong>
                </p>
              </div>
            </div>
          </div>

          <label class="admin-wishlist-confirm-field">
            <span>Type the exact email <strong>{{ deleteAccountRequest.emailAddress }}</strong> to confirm deletion:</span>
            <input
              v-model.trim="deleteConfirmEmail"
              type="email"
              :placeholder="deleteAccountRequest.emailAddress"
              autocomplete="off"
            />
          </label>

          <label class="admin-wishlist-confirm-field">
            <span>Type your admin password to confirm deletion:</span>
            <input
              v-model="deleteConfirmPassword"
              type="password"
              placeholder="Admin password"
              autocomplete="current-password"
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
              {{ isProcessing ? 'Deleting...' : 'Delete Request' }}
            </button>
          </div>
        </section>
      </div>

      <div v-if="showAddAdminModal" class="admin-wishlist-modal-overlay admin-wishlist-modal-overlay--top" @click.self="!isProcessing && closeAddAdminModal()">
        <section class="admin-wishlist-add-admin-modal admin-wishlist-create-modal">
          <button class="admin-wishlist-modal-close" type="button" aria-label="Close" :disabled="isProcessing" @click="closeAddAdminModal">
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
              <input
                v-model.trim="addAdminForm.lastName"
                type="text"
                placeholder="Last Name"
                minlength="2"
                required
                :disabled="isProcessing"
                @input="sanitizeAdminNameField('lastName')"
              />
            </label>
            <label>
              <span>First Name</span>
              <input
                v-model.trim="addAdminForm.firstName"
                type="text"
                placeholder="First Name"
                minlength="2"
                required
                :disabled="isProcessing"
                @input="sanitizeAdminNameField('firstName')"
              />
            </label>
            <label class="admin-wishlist-field-wide">
              <span>Email</span>
              <input v-model.trim="addAdminForm.emailAddress" type="email" placeholder="Email" required :disabled="isProcessing" />
            </label>
            <label>
              <span>ID Number</span>
              <input v-model.trim="addAdminForm.idNumber" type="text" placeholder="ID Number" required :disabled="isProcessing" />
            </label>
            <label class="admin-wishlist-field-wide">
              <span>Default Password</span>
              <input type="text" value="admin123" readonly disabled />
            </label>

            <p v-if="addAdminError" class="admin-wishlist-add-error">{{ addAdminError }}</p>

            <div class="admin-wishlist-modal-actions">
              <button class="admin-wishlist-cancel-button" type="button" :disabled="isProcessing" @click="closeAddAdminModal">
                Cancel
              </button>
              <button class="admin-wishlist-verify-button" type="submit" :disabled="isProcessing">
                {{ isProcessing ? 'Creating...' : 'Create Account' }}
              </button>
            </div>
          </form>
        </section>
      </div>

      <div v-if="showAddUserModal" class="admin-wishlist-modal-overlay admin-wishlist-modal-overlay--top" @click.self="closeAddUserModal">
        <section class="admin-wishlist-add-user-modal admin-wishlist-create-modal">
          <button class="admin-wishlist-modal-close" type="button" aria-label="Close" @click="closeAddUserModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>

          <div class="admin-wishlist-modal-heading admin-wishlist-modal-heading--user">
            <h2>Add User Account</h2>
            <p>Create a user request record for verification.</p>
          </div>

          <div class="admin-wishlist-add-section-label">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="8" r="4" />
              <path d="M4 21a8 8 0 0 1 16 0" />
            </svg>
            Account Information
          </div>

          <form class="admin-wishlist-add-form" @submit.prevent="createUserAccount">
            <label>
              <span>Last Name</span>
              <input v-model.trim="addUserForm.lastName" type="text" placeholder="Vito" required />
            </label>
            <label>
              <span>First Name</span>
              <input v-model.trim="addUserForm.firstName" type="text" placeholder="Justin Timothy" required />
            </label>
            <label class="admin-wishlist-field-wide">
              <span>FIT Email Address</span>
              <input v-model.trim="addUserForm.emailAddress" type="email" placeholder="jtvito@fit.edu.ph" required />
            </label>
            <label>
              <span>ID Number</span>
              <input v-model.trim="addUserForm.idNumber" type="text" placeholder="2023*****" required />
            </label>
            <label class="admin-wishlist-field-wide">
              <span>Role</span>
              <select v-model="addUserForm.role" required>
                <option value="Student">Student</option>
                <option value="Faculty">Faculty</option>
              </select>
            </label>
            <label>
              <span>Password</span>
              <span class="admin-wishlist-password-field">
                <input
                  v-model="addUserForm.password"
                  :type="showAddUserPassword ? 'text' : 'password'"
                  placeholder="Password"
                  required
                />
                <button
                  type="button"
                  class="admin-wishlist-password-toggle"
                  :aria-label="showAddUserPassword ? 'Hide password' : 'Show password'"
                  @click="showAddUserPassword = !showAddUserPassword"
                >
                  <svg v-if="showAddUserPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 3l18 18" />
                    <path d="M10.58 10.58A2 2 0 0 0 12 14a2 2 0 0 0 1.42-.58" />
                    <path d="M9.88 4.24A9.77 9.77 0 0 1 12 4c6 0 10 8 10 8a17.5 17.5 0 0 1-3.1 4.35" />
                    <path d="M6.61 6.61A17.5 17.5 0 0 0 2 12s4 8 10 8a9.77 9.77 0 0 0 5.39-1.61" />
                  </svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M2 12s4-8 10-8 10 8 10 8-4 8-10 8-10-8-10-8Z" />
                    <circle cx="12" cy="12" r="3" />
                  </svg>
                </button>
              </span>
            </label>
            <label>
              <span>Confirm Password</span>
              <span class="admin-wishlist-password-field">
                <input
                  v-model="addUserForm.confirmPassword"
                  :type="showAddUserConfirmPassword ? 'text' : 'password'"
                  placeholder="Confirm Password"
                  required
                />
                <button
                  type="button"
                  class="admin-wishlist-password-toggle"
                  :aria-label="showAddUserConfirmPassword ? 'Hide confirm password' : 'Show confirm password'"
                  @click="showAddUserConfirmPassword = !showAddUserConfirmPassword"
                >
                  <svg v-if="showAddUserConfirmPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 3l18 18" />
                    <path d="M10.58 10.58A2 2 0 0 0 12 14a2 2 0 0 0 1.42-.58" />
                    <path d="M9.88 4.24A9.77 9.77 0 0 1 12 4c6 0 10 8 10 8a17.5 17.5 0 0 1-3.1 4.35" />
                    <path d="M6.61 6.61A17.5 17.5 0 0 0 2 12s4 8 10 8a9.77 9.77 0 0 0 5.39-1.61" />
                  </svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M2 12s4-8 10-8 10 8 10 8-4 8-10 8-10-8-10-8Z" />
                    <circle cx="12" cy="12" r="3" />
                  </svg>
                </button>
              </span>
            </label>

            <p v-if="addUserError" class="admin-wishlist-add-error">{{ addUserError }}</p>

            <div class="admin-wishlist-modal-actions">
              <button class="admin-wishlist-cancel-button" type="button" @click="closeAddUserModal">
                Cancel
              </button>
              <button class="admin-wishlist-send-invite-button" type="submit" :disabled="isProcessing">
                {{ isProcessing ? 'Creating...' : 'Create Account' }}
              </button>
            </div>
          </form>
        </section>
      </div>

      <div v-if="showAddEmployeeModal" class="admin-wishlist-modal-overlay admin-wishlist-modal-overlay--top" @click.self="!isProcessing && closeAddEmployeeModal()">
        <section class="admin-wishlist-add-employee-modal admin-wishlist-create-modal">
          <button class="admin-wishlist-modal-close" type="button" aria-label="Close" :disabled="isProcessing" @click="closeAddEmployeeModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>

          <div class="admin-wishlist-modal-heading admin-wishlist-modal-heading--employee">
            <h2>Add Staff Account</h2>
            <p>Create a staff request record for verification.</p>
          </div>

          <div class="admin-wishlist-add-section-label">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="8" r="4" />
              <path d="M4 21a8 8 0 0 1 16 0" />
            </svg>
            Account Information
          </div>

          <form class="admin-wishlist-add-form" @submit.prevent="createEmployeeAccount">
            <label>
              <span>Last Name</span>
              <input v-model.trim="addEmployeeForm.lastName" type="text" placeholder="Dela Cruz" required @input="sanitizeEmployeeNameField('lastName')" />
            </label>
            <label>
              <span>First Name</span>
              <input v-model.trim="addEmployeeForm.firstName" type="text" placeholder="Juan" required @input="sanitizeEmployeeNameField('firstName')" />
            </label>
            <label>
              <span>Phone</span>
              <input
                v-model.trim="addEmployeeForm.phone"
                type="tel"
                inputmode="numeric"
                maxlength="10"
                placeholder="9123456789"
                required
                @input="sanitizeEmployeePhone"
              />
            </label>
            <label>
              <span>Work ID Number</span>
              <input v-model.trim="addEmployeeForm.idNumber" type="text" placeholder="2023-****" required />
            </label>
            <label class="admin-wishlist-field-wide">
              <span>Role</span>
              <input v-model="addEmployeeForm.role" type="text" readonly />
            </label>

            <p v-if="addEmployeeError" class="admin-wishlist-add-error">{{ addEmployeeError }}</p>

            <div class="admin-wishlist-modal-actions">
              <button class="admin-wishlist-cancel-button" type="button" :disabled="isProcessing" @click="closeAddEmployeeModal">
                Cancel
              </button>
              <button class="admin-wishlist-send-invite-button" type="submit" :disabled="isProcessing || !isEmployeeCreateFormReady">
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
import {
  buildAdminAccountPayload,
  buildEmployeeAccountPayload,
  buildUserAccountPayload,
  filterWishlistAccounts,
  formatCreateAccountError,
  formatDisplayDateTime,
  formatNullableDateTime,
  getAcceptedStatus,
  getAccountTypeBadgeClass,
  getApprovalEmailLabel,
  getInviteSentStatus,
  getStatusClass,
  getStatusLabel,
  getUniqueRequestAccounts,
  getAdminCreateError,
  getEmployeeCreateError,
  getUserCreateError,
  normalizeEmailForConfirmation,
  normalizeWishlistAccount,
  sanitizeNameInput,
  sanitizePhoneInput,
  validateEmployeeAccountForm as validateEmployeeFormValues,
} from './wishlist/adminWishlistHelpers.js';

const authStore = useAuthenticationStore();

const activeTab = ref('admin');
const searchText = ref('');
const sortMode = ref('newest');
const statusFilter = ref('all');
const userRoleFilter = ref('all');
const editListMode = ref(false);
const isLoading = ref(false);
const isProcessing = ref(false);
const showAddAdminModal = ref(false);
const showAddUserModal = ref(false);
const showAddEmployeeModal = ref(false);
const showAddUserPassword = ref(false);
const showAddUserConfirmPassword = ref(false);
const showAddEmployeePassword = ref(false);
const showAddEmployeeConfirmPassword = ref(false);
const selectedAccount = ref(null);
const approvalAccount = ref(null);
const approvalMode = ref('send');
const denialAccount = ref(null);
const denialConfirmEmail = ref('');
const denialConfirmPassword = ref('');
const denialFormError = ref('');
const deleteAccountRequest = ref(null);
const deleteConfirmEmail = ref('');
const deleteConfirmPassword = ref('');
const deleteFormError = ref('');
const toastMessage = ref('');
const loadErrorMessage = ref('');
const wishlistAccounts = ref([]);
const addAdminError = ref('');
const addUserError = ref('');
const addEmployeeError = ref('');

const approvalForm = reactive({
  emailAddress: '',
  role: 'ROLE_BORROWER',
  idNumber: '',
  lastName: '',
  firstName: '',
  confirmEmail: '',
});
const approvalFormError = ref('');

const addAdminForm = reactive({
  lastName: '',
  firstName: '',
  emailAddress: '',
  idNumber: '',
});

const addUserForm = reactive({
  lastName: '',
  firstName: '',
  emailAddress: '',
  idNumber: '',
  role: 'Student',
  password: '',
  confirmPassword: '',
});

const addEmployeeForm = reactive({
  lastName: '',
  firstName: '',
  phone: '',
  idNumber: '',
  role: 'Maintenance Staff',
});

const normalizedAccounts = computed(() => getUniqueRequestAccounts(wishlistAccounts.value.map(normalizeWishlistAccount)));
const currentAdminEmail = computed(() => {
  const account = authStore.accountData || authStore.clerkAccountData || {};
  return String(account.emailAddress || account.email || '').trim();
});
const isApprovalConfirmationReady = computed(() => (
  Boolean(approvalAccount.value)
  && Boolean(currentAdminEmail.value)
  && normalizeEmailForConfirmation(approvalForm.confirmEmail) === normalizeEmailForConfirmation(currentAdminEmail.value)
));
const isDenialConfirmationReady = computed(() => (
  Boolean(denialAccount.value)
  && normalizeEmailForConfirmation(denialConfirmEmail.value) === normalizeEmailForConfirmation(denialAccount.value.emailAddress)
  && denialConfirmPassword.value.trim() !== ''
));
const isDeleteConfirmationReady = computed(() => (
  Boolean(deleteAccountRequest.value)
  && normalizeEmailForConfirmation(deleteConfirmEmail.value) === normalizeEmailForConfirmation(deleteAccountRequest.value.emailAddress)
  && deleteConfirmPassword.value.trim() !== ''
));
const isEmployeeCreateFormReady = computed(() => validateEmployeeFormValues(addEmployeeForm) === '');

const wishlistTabs = computed(() => {
  const accounts = normalizedAccounts.value;
  return [
    { label: 'Admin', value: 'admin', count: accounts.filter((account) => account.accountType === 'Admin').length },
    { label: 'User', value: 'user', count: accounts.filter((account) => account.accountType === 'User').length },
    { label: 'Employee', value: 'employee', count: accounts.filter((account) => account.accountType === 'Employee').length },
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

onMounted(() => {
  loadWishlistAccounts();
});

function handleTabChange(tabName) {
  activeTab.value = tabName;
  searchText.value = '';
  statusFilter.value = 'all';
  userRoleFilter.value = 'all';
}

async function loadWishlistAccounts() {
  isLoading.value = true;
  loadErrorMessage.value = '';
  const result = await adminWishlistApi.getWishlistAccounts(authStore.authToken);
  if (result.success) {
    wishlistAccounts.value = result.data.users || result.data || [];
  } else {
    wishlistAccounts.value = [];
    loadErrorMessage.value = result.error || 'Unable to load request accounts from the backend.';
    showToast(loadErrorMessage.value);
  }
  isLoading.value = false;
}

function openViewModal(account) {
  selectedAccount.value = account;
}

function openApprovalModal(account = selectedAccount.value, mode = 'send') {
  if (!account) return;
  if (mode === 'resend' && !canResendInvite(account)) {
    showToast('Resend invite is only available after the previous invitation expires.');
    return;
  }
  if (mode === 'verify' && !canVerifyEmail(account)) {
    showToast('Verify email is only available after the user accepts the invitation.');
    return;
  }
  if (mode === 'send' && !canSendInvite(account)) {
    showToast('Send invite is only available for accounts that are not invited.');
    return;
  }
  approvalAccount.value = account;
  approvalMode.value = ['resend', 'verify'].includes(mode) ? mode : 'send';
  approvalForm.emailAddress = account.emailAddress;
  approvalForm.role = account.roleDesignation;
  approvalForm.idNumber = account.rawIdNumber || account.idNumber;
  approvalForm.lastName = account.lastName;
  approvalForm.firstName = account.firstName;
  approvalForm.confirmEmail = '';
  approvalFormError.value = '';
}

function closeModals() {
  selectedAccount.value = null;
  closeApprovalModal();
  closeDenialModal();
  closeDeleteModal();
}

function closeApprovalModal() {
  approvalAccount.value = null;
  approvalMode.value = 'send';
  approvalForm.confirmEmail = '';
  approvalFormError.value = '';
}

function openDenialModal(account) {
  if (!account) return;
  denialAccount.value = account;
  denialConfirmEmail.value = '';
  denialConfirmPassword.value = '';
  denialFormError.value = '';
}

function closeDenialModal() {
  denialAccount.value = null;
  denialConfirmEmail.value = '';
  denialConfirmPassword.value = '';
  denialFormError.value = '';
}

function openDeleteModal(account) {
  if (!account) return;
  deleteAccountRequest.value = account;
  deleteConfirmEmail.value = '';
  deleteConfirmPassword.value = '';
  deleteFormError.value = '';
}

function closeDeleteModal() {
  deleteAccountRequest.value = null;
  deleteConfirmEmail.value = '';
  deleteConfirmPassword.value = '';
  deleteFormError.value = '';
}

function openAddAccountModal() {
  if (activeTab.value === 'employee') {
    openAddEmployeeModal();
    return;
  }
  if (activeTab.value === 'user') {
    openAddUserModal();
    return;
  }
  openAddAdminModal();
}

function openAddAdminModal() {
  resetAddAdminForm();
  showAddAdminModal.value = true;
}

function closeAddAdminModal() {
  showAddAdminModal.value = false;
  addAdminError.value = '';
  resetAddAdminForm();
}

function openAddUserModal() {
  resetAddUserForm();
  showAddUserPassword.value = false;
  showAddUserConfirmPassword.value = false;
  showAddUserModal.value = true;
}

function closeAddUserModal() {
  showAddUserModal.value = false;
  addUserError.value = '';
  showAddUserPassword.value = false;
  showAddUserConfirmPassword.value = false;
}

function openAddEmployeeModal() {
  resetAddEmployeeForm();
  showAddEmployeePassword.value = false;
  showAddEmployeeConfirmPassword.value = false;
  showAddEmployeeModal.value = true;
}

function closeAddEmployeeModal() {
  showAddEmployeeModal.value = false;
  resetAddEmployeeForm();
}

async function createAdminAccount() {
  if (isProcessing.value) return;

  addAdminError.value = '';

  const validationError = getAdminCreateError(addAdminForm, normalizedAccounts.value);
  if (validationError) return setAdminCreateError(validationError);

  isProcessing.value = true;
  const result = await adminWishlistApi.createAdminAccount(buildAdminAccountPayload(addAdminForm), authStore.authToken);
  isProcessing.value = false;

  if (!result.success) {
    addAdminError.value = formatCreateAccountError(result, 'admin');
    return;
  }

  activeTab.value = 'admin';
  showAddAdminModal.value = false;
  resetAddAdminForm();
  await loadWishlistAccounts();
  showToast('Account created!');
}

function setAdminCreateError(message) {
  addAdminError.value = message;
}

function resetAddAdminForm() {
  addAdminForm.lastName = '';
  addAdminForm.firstName = '';
  addAdminForm.emailAddress = '';
  addAdminForm.idNumber = '';
  addAdminError.value = '';
}

function sanitizeAdminNameField(fieldName) {
  addAdminForm[fieldName] = sanitizeNameInput(addAdminForm[fieldName]);
}

async function createUserAccount() {
  addUserError.value = '';

  const validationError = getUserCreateError(addUserForm, normalizedAccounts.value);
  if (validationError) return setUserCreateError(validationError);

  isProcessing.value = true;
  const result = await adminWishlistApi.createUserAccount(buildUserAccountPayload(addUserForm), authStore.authToken);
  isProcessing.value = false;

  if (!result.success) {
    addUserError.value = formatCreateAccountError(result, 'user');
    return;
  }

  activeTab.value = 'user';
  showAddUserModal.value = false;
  await loadWishlistAccounts();
  showToast('Account created!');
  resetAddUserForm();
}

function setUserCreateError(message) {
  addUserError.value = message;
}

function resetAddUserForm() {
  addUserForm.lastName = '';
  addUserForm.firstName = '';
  addUserForm.emailAddress = '';
  addUserForm.idNumber = '';
  addUserForm.role = 'Student';
  addUserForm.password = '';
  addUserForm.confirmPassword = '';
  showAddUserPassword.value = false;
  showAddUserConfirmPassword.value = false;
  addUserError.value = '';
}

async function createEmployeeAccount() {
  if (isProcessing.value) return;

  addEmployeeError.value = '';

  const validationError = getEmployeeCreateError(addEmployeeForm, normalizedAccounts.value);
  if (validationError) return setEmployeeCreateError(validationError);

  isProcessing.value = true;
  const result = await adminWishlistApi.createEmployeeAccount(buildEmployeeAccountPayload(addEmployeeForm), authStore.authToken);
  isProcessing.value = false;

  if (!result.success) {
    addEmployeeError.value = formatCreateAccountError(result, 'employee');
    return;
  }

  activeTab.value = 'employee';
  showAddEmployeeModal.value = false;
  await loadWishlistAccounts();
  showToast('Account created!');
  resetAddEmployeeForm();
}

function setEmployeeCreateError(message) {
  addEmployeeError.value = message;
}

function resetAddEmployeeForm() {
  addEmployeeForm.lastName = '';
  addEmployeeForm.firstName = '';
  addEmployeeForm.phone = '';
  addEmployeeForm.idNumber = '';
  addEmployeeForm.role = 'Maintenance Staff';
  showAddEmployeePassword.value = false;
  showAddEmployeeConfirmPassword.value = false;
  addEmployeeError.value = '';
}

function sanitizeEmployeeNameField(fieldName) {
  addEmployeeForm[fieldName] = sanitizeNameInput(addEmployeeForm[fieldName]);
}

function sanitizeEmployeePhone() {
  addEmployeeForm.phone = sanitizePhoneInput(addEmployeeForm.phone);
}

async function verifyAccount() {
  if (isProcessing.value) return;
  if (!approvalAccount.value) return;
  if (!currentAdminEmail.value) {
    approvalFormError.value = 'Unable to confirm the responsible admin email. Please sign in again.';
    return;
  }
  if (normalizeEmailForConfirmation(approvalForm.confirmEmail) !== normalizeEmailForConfirmation(currentAdminEmail.value)) {
    approvalFormError.value = approvalMode.value === 'verify'
      ? 'Please type your exact admin email to approve access.'
      : 'Please type your exact admin email to send the invite.';
    return;
  }

  isProcessing.value = true;
  const action = approvalMode.value === 'verify'
    ? adminWishlistApi.verifyEmailAndApproveAccount
    : adminWishlistApi.verifyAccount;
  const result = await action(
    approvalAccount.value.accountIdentifier,
    authStore.authToken,
    { confirmedAdminEmail: normalizeEmailForConfirmation(approvalForm.confirmEmail) },
  );
  if (result.success) {
    const successMessage = getInviteSuccessMessage(approvalAccount.value);
    closeModals();
    showToast(successMessage);
    await loadWishlistAccounts();
  } else {
    approvalFormError.value = result.error || 'Unable to send invite.';
    showToast(approvalFormError.value);
  }
  isProcessing.value = false;
}

async function denyAccount() {
  if (!denialAccount.value) return;
  if (normalizeEmailForConfirmation(denialConfirmEmail.value) !== normalizeEmailForConfirmation(denialAccount.value.emailAddress)) {
    denialFormError.value = 'Please type the exact email address to deny this request.';
    return;
  }
  if (denialConfirmPassword.value.trim() === '') {
    denialFormError.value = 'Please type your admin password to deny this request.';
    return;
  }

  isProcessing.value = true;
  const result = await adminWishlistApi.denyAccount(
    denialAccount.value.accountIdentifier,
    authStore.authToken,
    {
      confirmEmail: normalizeEmailForConfirmation(denialConfirmEmail.value),
      confirmedAdminPassword: denialConfirmPassword.value,
    },
  );
  if (result.success) {
    approvalAccount.value = null;
    selectedAccount.value = null;
    closeDenialModal();
    await loadWishlistAccounts();
    showToast('Account request denied.');
  } else {
    denialFormError.value = result.error || 'Unable to deny account.';
    showToast(denialFormError.value);
  }
  isProcessing.value = false;
}

async function deleteWishlistAccount() {
  if (!deleteAccountRequest.value) return;
  if (normalizeEmailForConfirmation(deleteConfirmEmail.value) !== normalizeEmailForConfirmation(deleteAccountRequest.value.emailAddress)) {
    deleteFormError.value = 'Please type the exact email address to delete this request.';
    return;
  }
  if (deleteConfirmPassword.value.trim() === '') {
    deleteFormError.value = 'Please type your admin password to delete this request.';
    return;
  }

  isProcessing.value = true;
  const result = await adminWishlistApi.deleteAccountRequest(
    deleteAccountRequest.value.accountIdentifier,
    authStore.authToken,
    {
      confirmEmail: normalizeEmailForConfirmation(deleteConfirmEmail.value),
      confirmedAdminPassword: deleteConfirmPassword.value,
    },
  );
  if (result.success) {
    approvalAccount.value = null;
    selectedAccount.value = null;
    closeDeleteModal();
    await loadWishlistAccounts();
    showToast('Account request deleted.');
  } else {
    deleteFormError.value = result.error || 'Unable to delete account request.';
    showToast(deleteFormError.value);
  }
  isProcessing.value = false;
}

function canSendInvite(account) {
  return String(account?.accountStatus || '').toLowerCase() === 'not_invited' && !isProcessing.value;
}

function canResendInvite(account) {
  return String(account?.accountStatus || '').toLowerCase() === 'expired'
    && Boolean(account?.inviteSentAt)
    && !isProcessing.value;
}

function canVerifyEmail(account) {
  return String(account?.accountStatus || '').toLowerCase() === 'verified'
    && Boolean(account?.inviteAcceptedAt)
    && !isProcessing.value;
}

function getInviteModalTitle(account) {
  if (approvalMode.value === 'resend') return 'Resend Invite';
  if (approvalMode.value === 'verify') return 'Verify Email';
  return account?.accountType === 'Employee' ? 'Approve Employee' : 'Approve Account';
}

function getInviteModalDescription(account) {
  if (approvalMode.value === 'resend') {
    return 'The previous invitation expired. Confirm the responsible admin before resending a new invitation link.';
  }
  if (approvalMode.value === 'verify') {
    return 'The invitation was accepted. Confirm the responsible admin before approving system access.';
  }

  return account?.accountType === 'Employee'
    ? 'Review the worker information before approving access and sending the Clerk invitation email.'
    : 'This will approve the account, move it to Manage Accounts as Active, and send the Clerk invitation email.';
}

function getInviteSubmitLabel(account) {
  if (approvalMode.value === 'resend') return 'Resend Invite';
  if (approvalMode.value === 'verify') return 'Approve Access';
  return 'Approve & Email';
}

function getInviteSuccessMessage(account) {
  if (approvalMode.value === 'resend') return 'Invitation Sent!';
  if (approvalMode.value === 'verify') return 'Email verified and account approved!';
  return 'Invitation Sent!';
}

function getProcessingLabel() {
  return approvalMode.value === 'verify' || approvalMode.value === 'send' ? 'Approving...' : 'Sending...';
}

function showToast(message) {
  toastMessage.value = message;
  window.setTimeout(() => {
    if (toastMessage.value === message) toastMessage.value = '';
  }, 4200);
}
</script>
