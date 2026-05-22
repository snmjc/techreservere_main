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
              <option value="pending">Unverified</option>
              <option value="invited">Invite Sent</option>
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
                <td>{{ account.role }}</td>
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
                      class="admin-wishlist-icon-button admin-wishlist-icon-button--invite"
                      type="button"
                      aria-label="Send invite"
                      title="Send invite"
                      @click="openApprovalModal(account)"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="5" width="18" height="14" rx="2" />
                        <path d="m3 7 9 6 9-6" />
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
            <p>Verify system account and send an invite.</p>
          </div>

          <div class="admin-wishlist-view-account-grid">
            <div class="admin-wishlist-view-account-main">
              <p><strong>ID Number:</strong> <span>{{ selectedAccount.idNumber }}</span></p>
              <p><strong>Last Name:</strong> <span>{{ selectedAccount.lastName }}</span></p>
              <p><strong>First Name:</strong> <span>{{ selectedAccount.firstName }}</span></p>
              <p><strong>{{ getEmailLabel(selectedAccount) }}</strong> <span>{{ selectedAccount.emailAddress }}</span></p>
              <p v-if="selectedAccount.accountType === 'Employee'"><strong>Phone:</strong> <span>{{ selectedAccount.contactNumber }}</span></p>
              <p><strong>Role:</strong> <span>{{ selectedAccount.role }}</span></p>
              <p><strong>Account Status:</strong> <span>{{ getStatusLabel(selectedAccount.accountStatus) }}</span></p>
              <p v-if="selectedAccount.accountType !== 'Employee'"><strong>Account Registered:</strong> <span>{{ formatDisplayDate(selectedAccount.registeredAt) }}</span></p>
            </div>
            <div class="admin-wishlist-view-account-side">
              <p><strong>Account Type:</strong> <span>{{ selectedAccount.accountType }}</span></p>
              <div>
                <p><strong>Invite Sent:</strong> <span>{{ formatNullableDate(selectedAccount.inviteSentAt) }}</span></p>
                <p><strong>Expires:</strong> <span>{{ formatNullableDate(selectedAccount.inviteExpiresAt) }}</span></p>
                <p><strong>Accepted:</strong> <span>{{ formatNullableDate(selectedAccount.inviteAcceptedAt) }}</span></p>
              </div>
            </div>
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

      <div v-if="approvalAccount" class="admin-wishlist-modal-overlay admin-wishlist-modal-overlay--top" @click.self="closeApprovalModal">
        <section class="admin-wishlist-approval-modal">
          <button class="admin-wishlist-modal-close" type="button" aria-label="Close" @click="closeApprovalModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>

          <div class="admin-wishlist-modal-heading">
            <h2>{{ approvalAccount.accountType === 'Employee' ? 'Ready to Invite Employee' : 'Send Invite' }}</h2>
            <p>{{ approvalAccount.accountType === 'Employee' ? 'Review the worker information before sending the invitation.' : 'This will verify the account and send an invitation link to their email for system access.' }}</p>
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
                  <span>Name</span>
                  <strong>{{ approvalAccount.fullName }}</strong>
                </p>
                <p>
                  <span>ID Number</span>
                  <strong>{{ approvalAccount.idNumber }}</strong>
                </p>
                <p>
                  <span>{{ getApprovalEmailLabel(approvalAccount) }}</span>
                  <strong>{{ approvalAccount.emailAddress }}</strong>
                </p>
                <p>
                  <span>Role</span>
                  <strong>{{ approvalAccount.role }}</strong>
                </p>
              </div>
            </div>
          </div>

          <label class="admin-wishlist-confirm-field">
            <span>Type your admin email <strong>{{ currentAdminEmail || 'from your account' }}</strong> to confirm:</span>
            <input
              v-model.trim="approvalForm.confirmEmail"
              type="email"
              :placeholder="currentAdminEmail || 'admin@fit.edu.ph'"
              autocomplete="off"
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
              {{ isProcessing ? 'Sending...' : 'Send Invite' }}
            </button>
          </div>
        </section>
      </div>

      <div v-if="showAddAdminModal" class="admin-wishlist-modal-overlay admin-wishlist-modal-overlay--top" @click.self="closeAddAdminModal">
        <section class="admin-wishlist-add-admin-modal admin-wishlist-create-modal">
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
              <input v-model.trim="addAdminForm.lastName" type="text" placeholder="Last Name" required />
            </label>
            <label>
              <span>First Name</span>
              <input v-model.trim="addAdminForm.firstName" type="text" placeholder="First Name" required />
            </label>
            <label class="admin-wishlist-field-wide">
              <span>Email</span>
              <input v-model.trim="addAdminForm.emailAddress" type="email" placeholder="Email" required />
            </label>
            <label>
              <span>ID Number</span>
              <input v-model.trim="addAdminForm.idNumber" type="text" placeholder="ID Number" required />
            </label>
            <label class="admin-wishlist-field-wide">
              <span>Role</span>
              <input v-model="addAdminForm.role" type="text" placeholder="Role" readonly />
            </label>
            <label>
              <span>Password</span>
              <span class="admin-wishlist-password-field">
                <input
                  v-model="addAdminForm.password"
                  :type="showAddAdminPassword ? 'text' : 'password'"
                  placeholder="Password"
                  required
                />
                <button
                  type="button"
                  class="admin-wishlist-password-toggle"
                  :aria-label="showAddAdminPassword ? 'Hide password' : 'Show password'"
                  @click="showAddAdminPassword = !showAddAdminPassword"
                >
                  <svg v-if="showAddAdminPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
                  v-model="addAdminForm.confirmPassword"
                  :type="showAddAdminConfirmPassword ? 'text' : 'password'"
                  placeholder="Confirm Password"
                  required
                />
                <button
                  type="button"
                  class="admin-wishlist-password-toggle"
                  :aria-label="showAddAdminConfirmPassword ? 'Hide confirm password' : 'Show confirm password'"
                  @click="showAddAdminConfirmPassword = !showAddAdminConfirmPassword"
                >
                  <svg v-if="showAddAdminConfirmPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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

      <div v-if="showAddEmployeeModal" class="admin-wishlist-modal-overlay admin-wishlist-modal-overlay--top" @click.self="closeAddEmployeeModal">
        <section class="admin-wishlist-add-employee-modal admin-wishlist-create-modal">
          <button class="admin-wishlist-modal-close" type="button" aria-label="Close" @click="closeAddEmployeeModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>

          <div class="admin-wishlist-modal-heading admin-wishlist-modal-heading--employee">
            <h2>Add Employee Account</h2>
            <p>Create an employee request record for verification.</p>
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
              <input v-model.trim="addEmployeeForm.lastName" type="text" placeholder="Zefanya" required />
            </label>
            <label>
              <span>First Name</span>
              <input v-model.trim="addEmployeeForm.firstName" type="text" placeholder="Nicole" required />
            </label>
            <label class="admin-wishlist-field-wide">
              <span>Email</span>
              <input v-model.trim="addEmployeeForm.emailAddress" type="email" placeholder="nicolezefanya@gmail.com" required />
            </label>
            <label>
              <span>Phone</span>
              <input v-model.trim="addEmployeeForm.phone" type="tel" placeholder="0912 345 6789" required />
            </label>
            <label>
              <span>ID Number</span>
              <input v-model.trim="addEmployeeForm.idNumber" type="text" placeholder="2023-****" required />
            </label>
            <label class="admin-wishlist-field-wide">
              <span>Role</span>
              <input v-model.trim="addEmployeeForm.role" type="text" placeholder="Maintenance Staff" required />
            </label>
            <label>
              <span>Password</span>
              <span class="admin-wishlist-password-field">
                <input
                  v-model="addEmployeeForm.password"
                  :type="showAddEmployeePassword ? 'text' : 'password'"
                  placeholder="Password"
                  required
                />
                <button
                  type="button"
                  class="admin-wishlist-password-toggle"
                  :aria-label="showAddEmployeePassword ? 'Hide password' : 'Show password'"
                  @click="showAddEmployeePassword = !showAddEmployeePassword"
                >
                  <svg v-if="showAddEmployeePassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
                  v-model="addEmployeeForm.confirmPassword"
                  :type="showAddEmployeeConfirmPassword ? 'text' : 'password'"
                  placeholder="Confirm Password"
                  required
                />
                <button
                  type="button"
                  class="admin-wishlist-password-toggle"
                  :aria-label="showAddEmployeeConfirmPassword ? 'Hide confirm password' : 'Show confirm password'"
                  @click="showAddEmployeeConfirmPassword = !showAddEmployeeConfirmPassword"
                >
                  <svg v-if="showAddEmployeeConfirmPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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

            <p v-if="addEmployeeError" class="admin-wishlist-add-error">{{ addEmployeeError }}</p>

            <div class="admin-wishlist-modal-actions">
              <button class="admin-wishlist-cancel-button" type="button" @click="closeAddEmployeeModal">
                Cancel
              </button>
              <button class="admin-wishlist-send-invite-button" type="submit" :disabled="isProcessing">
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
const userRoleFilter = ref('all');
const editListMode = ref(false);
const isLoading = ref(false);
const isProcessing = ref(false);
const showAddAdminModal = ref(false);
const showAddUserModal = ref(false);
const showAddEmployeeModal = ref(false);
const showAddAdminPassword = ref(false);
const showAddAdminConfirmPassword = ref(false);
const showAddUserPassword = ref(false);
const showAddUserConfirmPassword = ref(false);
const showAddEmployeePassword = ref(false);
const showAddEmployeeConfirmPassword = ref(false);
const selectedAccount = ref(null);
const approvalAccount = ref(null);
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
  role: 'Admin',
  password: '',
  confirmPassword: '',
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
  emailAddress: '',
  phone: '',
  idNumber: '',
  role: 'Maintenance Staff',
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
    contactNumber: '09170000003',
    accountStatus: 'invited',
    registeredAt: '2026-05-14T14:05:00+08:00',
    inviteSentAt: '2026-05-17T09:00:00+08:00',
    inviteExpiresAt: '2026-05-24T09:00:00+08:00',
    inviteAcceptedAt: null,
  },
];

const normalizedAccounts = computed(() => wishlistAccounts.value.map(normalizeWishlistAccount));
const currentAdminEmail = computed(() => {
  const account = authStore.accountData || authStore.clerkAccountData || {};
  return String(account.emailAddress || account.email || '').trim();
});
const isApprovalConfirmationReady = computed(() => (
  Boolean(approvalAccount.value)
  && Boolean(currentAdminEmail.value)
  && approvalForm.confirmEmail.toLowerCase() === currentAdminEmail.value.toLowerCase()
));

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
    .filter((account) => activeTab.value !== 'user' || userRoleFilter.value === 'all' || getUserRoleName(account).toLowerCase() === userRoleFilter.value)
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
        return getSortRoleName(first).localeCompare(getSortRoleName(second)) || `${first.lastName} ${first.firstName}`.localeCompare(`${second.lastName} ${second.firstName}`);
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
  const contactNumber = account.contactNumber || account.contact_number || account.phone || 'N/A';

  return {
    ...account,
    accountIdentifier: account.accountIdentifier || account.account_identifier || idNumber,
    idNumber: formatIdNumber(idNumber),
    firstName,
    lastName,
    fullName: `${firstName} ${lastName}`.trim(),
    emailAddress,
    contactNumber,
    roleDesignation,
    role: resolveRoleName(account, roleDesignation),
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
  const normalizedRole = String(roleDesignation).toUpperCase();
  if (normalizedRole.includes('ADMIN')) return 'Admin';
  if (normalizedRole.includes('STAFF') || normalizedRole.includes('EMPLOYEE')) return 'Employee';
  const department = String(account.department || '').toLowerCase();
  if (department.includes('staff') || department.includes('employee') || department.includes('technical') || department.includes('maintenance')) return 'Employee';
  return 'User';
}

function resolveRoleLabel(account, roleDesignation) {
  const accountType = account.accountType || resolveAccountType(account, roleDesignation);
  if (String(roleDesignation).toUpperCase().includes('ADMIN')) return 'Admin';
  if (accountType === 'Employee') return resolveRoleName({ ...account, accountType }, roleDesignation);
  const department = String(account.department || '').toLowerCase();
  if (department.includes('faculty') || department.includes('employee')) return 'User: Faculty';
  return 'User: Student';
}

function resolveRoleName(account, roleDesignation) {
  if (String(roleDesignation).toUpperCase().includes('ADMIN')) return 'Administrator';
  const accountType = account.accountType || resolveAccountType(account, roleDesignation);
  const rawRole = account.role || account.roleName || account.roleLabel || '';
  const department = String(account.department || '');
  if (accountType === 'Employee') {
    const employeeRole = formatEmployeeRoleName(rawRole);
    if (employeeRole) return employeeRole;
    if (/faculty/i.test(rawRole) || /faculty/i.test(department)) return 'Faculty';
    const departmentRole = formatEmployeeRoleName(department);
    if (departmentRole) return departmentRole;
    return 'Technical Staff';
  }
  if (/faculty/i.test(rawRole) || /faculty/i.test(department)) return 'Faculty';
  return 'Student';
}

function getSortRoleName(account) {
  return account.accountType === 'User' ? getUserRoleName(account) : account.role;
}

function getUserRoleName(account) {
  const roleText = `${account?.role || ''} ${account?.roleLabel || ''} ${account?.roleDesignation || ''}`.toLowerCase();
  return roleText.includes('faculty') ? 'Faculty' : 'Student';
}

function getApprovalEmailLabel(account) {
  if (account?.accountType === 'Employee') return 'Employee company email';
  return account?.accountType === 'User' ? 'FIT email address' : 'Email address';
}

function formatEmployeeRoleName(value) {
  const normalized = String(value || '').trim();
  if (!normalized || /^user:/i.test(normalized)) return '';
  if (/^role_/i.test(normalized)) return '';
  return normalized
    .replace(/[_-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function getEmailLabel(account) {
  return account?.accountType === 'Employee' ? 'Email Address:' : 'FIT Email Address:';
}

function getAccountTypeBadgeClass(accountType) {
  return {
    'admin-wishlist-account-type-badge--employee': accountType === 'Employee',
    'admin-wishlist-account-type-badge--admin': accountType === 'Admin',
    'admin-wishlist-account-type-badge--user': accountType === 'User',
  };
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

function openApprovalModal(account = selectedAccount.value) {
  if (!account) return;
  approvalAccount.value = account;
  approvalForm.emailAddress = account.emailAddress;
  approvalForm.role = account.roleDesignation;
  approvalForm.idNumber = account.idNumber;
  approvalForm.lastName = account.lastName;
  approvalForm.firstName = account.firstName;
  approvalForm.confirmEmail = '';
  approvalFormError.value = '';
}

function closeModals() {
  selectedAccount.value = null;
  closeApprovalModal();
}

function closeApprovalModal() {
  approvalAccount.value = null;
  approvalForm.confirmEmail = '';
  approvalFormError.value = '';
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
  showAddAdminPassword.value = false;
  showAddAdminConfirmPassword.value = false;
  showAddAdminModal.value = true;
}

function closeAddAdminModal() {
  showAddAdminModal.value = false;
  addAdminError.value = '';
  showAddAdminPassword.value = false;
  showAddAdminConfirmPassword.value = false;
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
  addEmployeeError.value = '';
  showAddEmployeePassword.value = false;
  showAddEmployeeConfirmPassword.value = false;
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
    addAdminError.value = formatCreateAccountError(result, 'admin');
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
  showAddAdminPassword.value = false;
  showAddAdminConfirmPassword.value = false;
  addAdminError.value = '';
}

async function createUserAccount() {
  addUserError.value = '';

  if (addUserForm.password !== addUserForm.confirmPassword) {
    addUserError.value = 'Password and confirm password must match.';
    return;
  }

  const emailExists = normalizedAccounts.value.some(
    (account) => account.emailAddress.toLowerCase() === addUserForm.emailAddress.toLowerCase()
  );
  if (emailExists) {
    addUserError.value = 'An account with this email already exists in the wishlist.';
    return;
  }

  const accountPayload = {
    lastName: addUserForm.lastName,
    firstName: addUserForm.firstName,
    emailAddress: addUserForm.emailAddress,
    idNumber: addUserForm.idNumber,
    role: addUserForm.role,
    passwordText: addUserForm.password,
  };

  isProcessing.value = true;
  const result = await adminWishlistApi.createUserAccount(accountPayload, authStore.authToken);
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
  addEmployeeError.value = '';

  if (addEmployeeForm.password !== addEmployeeForm.confirmPassword) {
    addEmployeeError.value = 'Password and confirm password must match.';
    return;
  }

  const emailExists = normalizedAccounts.value.some(
    (account) => account.emailAddress.toLowerCase() === addEmployeeForm.emailAddress.toLowerCase()
  );
  if (emailExists) {
    addEmployeeError.value = 'An account with this email already exists in the wishlist.';
    return;
  }

  const accountPayload = {
    lastName: addEmployeeForm.lastName,
    firstName: addEmployeeForm.firstName,
    emailAddress: addEmployeeForm.emailAddress,
    phone: addEmployeeForm.phone,
    idNumber: addEmployeeForm.idNumber,
    role: addEmployeeForm.role,
    passwordText: addEmployeeForm.password,
  };

  isProcessing.value = true;
  const result = await adminWishlistApi.createEmployeeAccount(accountPayload, authStore.authToken);
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

function resetAddEmployeeForm() {
  addEmployeeForm.lastName = '';
  addEmployeeForm.firstName = '';
  addEmployeeForm.emailAddress = '';
  addEmployeeForm.phone = '';
  addEmployeeForm.idNumber = '';
  addEmployeeForm.role = 'Maintenance Staff';
  addEmployeeForm.password = '';
  addEmployeeForm.confirmPassword = '';
  showAddEmployeePassword.value = false;
  showAddEmployeeConfirmPassword.value = false;
  addEmployeeError.value = '';
}

function formatCreateAccountError(result, accountType) {
  const conflict = result?.data?.conflict;
  if (!conflict) {
    return result?.error || `Unable to create ${accountType} account.`;
  }

  const fullName = `${conflict.firstName || ''} ${conflict.lastName || ''}`.trim() || 'Existing account';
  const status = getStatusLabel(conflict.status);
  const location = conflict.isApproved || String(conflict.status).toLowerCase() === 'approved'
    ? 'Manage Accounts'
    : 'Wishlist or Manage Accounts';
  const matchedField = conflict.matchedField === 'idNumber' ? 'ID number' : 'email';

  return `This ${matchedField} is already used by ${fullName} (${conflict.emailAddress}, ${conflict.accountType}, ${status}). Check ${location}.`;
}

async function verifyAccount() {
  if (!approvalAccount.value) return;
  if (!currentAdminEmail.value) {
    approvalFormError.value = 'Unable to confirm the responsible admin email. Please sign in again.';
    return;
  }
  if (approvalForm.confirmEmail.toLowerCase() !== currentAdminEmail.value.toLowerCase()) {
    approvalFormError.value = 'Please type your exact admin email to send the invite.';
    return;
  }

  isProcessing.value = true;
  const result = await adminWishlistApi.verifyAccount(
    approvalAccount.value.accountIdentifier,
    authStore.authToken,
    { confirmedAdminEmail: approvalForm.confirmEmail },
  );
  if (result.success) {
    closeModals();
    await loadWishlistAccounts();
    showToast('Invitation Sent!');
  } else {
    approvalFormError.value = result.error || 'Unable to send invite.';
    showToast(approvalFormError.value);
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
