<template>
  <AdminSidebarLayoutComponent
    :role-label="userRole"
    :navigation-items="navigationItems"
  >
    <div class="settings-page">
      <div class="settings-wrapper">
        <div class="settings-header">
          <h1>Settings</h1>
          <div class="settings-tabs" role="tablist" aria-label="Settings sections">
            <button
              v-for="tab in settingsTabs"
              :key="tab.value"
              type="button"
              :class="['settings-tab', { active: activeTab === tab.value }]"
              @click="selectTab(tab.value)"
            >
              {{ tab.label }}
            </button>
          </div>
        </div>

        <p v-if="loadError" class="settings-alert error">{{ loadError }}</p>
        <p v-else-if="isLoadingProfile" class="settings-alert">Loading account settings...</p>

        <section v-if="activeTab === 'account'" class="settings-card">
          <div class="card-header">
            <div>
              <h2>Account Settings</h2>
              <p>Update your profile information saved in the User Accounts database.</p>
            </div>
          </div>

          <div class="card-content account-grid">
            <div class="profile-summary">
              <div class="profile-photo">
                <img v-if="accountProfile.profilePhotoData" :src="accountProfile.profilePhotoData" alt="Profile photo" />
                <span v-else>{{ accountInitials }}</span>
              </div>
              <div>
                <p class="profile-name">{{ fullName || 'Account User' }}</p>
                <p class="profile-meta">{{ accountProfile.emailAddress || 'No email address' }}</p>
                <p class="profile-meta">{{ accountProfile.roleLabel || accountProfile.accountType || 'No role' }}</p>
              </div>
            </div>

            <div class="readonly-grid">
              <div class="info-item">
                <label>ID Number</label>
                <p>{{ accountProfile.idNumber || 'Not set' }}</p>
              </div>
              <div class="info-item">
                <label>Email Address</label>
                <p>{{ accountProfile.emailAddress || 'Not set' }}</p>
              </div>
              <div class="info-item">
                <label>Role</label>
                <p>{{ accountProfile.roleLabel || accountProfile.roleDesignation || 'Not set' }}</p>
              </div>
              <div class="info-item">
                <label>Phone Number</label>
                <p>{{ accountProfile.contactNumber || 'Not set' }}</p>
              </div>
            </div>

            <form class="settings-form" @submit.prevent="saveAccountSettings">
              <div class="form-row">
                <label for="firstName">First Name</label>
                <input
                  id="firstName"
                  v-model.trim="accountForm.firstName"
                  type="text"
                  autocomplete="given-name"
                  :disabled="isSavingAccount"
                />
              </div>

              <div class="form-row">
                <label for="lastName">Last Name</label>
                <input
                  id="lastName"
                  v-model.trim="accountForm.lastName"
                  type="text"
                  autocomplete="family-name"
                  :disabled="isSavingAccount"
                />
              </div>

              <div class="form-row">
                <label for="phoneNumber">Phone Number (10 digits, starts with 9)</label>
                <input
                  id="phoneNumber"
                  v-model.trim="accountForm.contactNumber"
                  type="tel"
                  inputmode="numeric"
                  maxlength="10"
                  placeholder="9XXXXXXXXX"
                  :disabled="isSavingAccount"
                  @input="sanitizePhoneInput"
                />
              </div>

              <div class="form-row">
                <label for="profilePhoto">Profile Photo (.jpg only)</label>
                <input
                  id="profilePhoto"
                  ref="profilePhotoInput"
                  type="file"
                  accept=".jpg,image/jpeg"
                  :disabled="isSavingAccount"
                  @change="handlePhotoChange"
                />
                <p v-if="selectedPhotoName" class="field-hint">{{ selectedPhotoName }}</p>
              </div>

              <p v-if="accountError" class="settings-alert error">{{ accountError }}</p>
              <p v-if="accountSuccess" class="settings-alert success">{{ accountSuccess }}</p>

              <button class="btn btn-primary" type="submit" :disabled="isSavingAccount">
                {{ isSavingAccount ? 'Saving...' : 'Save Changes' }}
              </button>
            </form>
          </div>
        </section>

        <section v-if="activeTab === 'security'" class="settings-card">
          <div class="card-header">
            <div>
              <h2>Security</h2>
              <p>Update the local password used for TechReserve sign-in.</p>
            </div>
          </div>

          <form class="settings-form" @submit.prevent="updatePassword">
            <div class="form-row">
              <label for="currentPassword">Current Password</label>
              <input
                id="currentPassword"
                v-model="passwordForm.currentPassword"
                type="password"
                autocomplete="current-password"
                :disabled="isUpdatingPassword"
              />
            </div>

            <div class="form-row">
              <label for="newPassword">New Password</label>
              <input
                id="newPassword"
                v-model="passwordForm.newPassword"
                type="password"
                autocomplete="new-password"
                :disabled="isUpdatingPassword"
              />
              <div class="password-requirements">
                <p :class="['requirement', { met: passwordRequirements.length }]">At least 8 characters</p>
                <p :class="['requirement', { met: passwordRequirements.upper }]">One uppercase letter</p>
                <p :class="['requirement', { met: passwordRequirements.lower }]">One lowercase letter</p>
                <p :class="['requirement', { met: passwordRequirements.number }]">One number</p>
                <p :class="['requirement', { met: passwordRequirements.special }]">One special character</p>
              </div>
            </div>

            <div class="form-row">
              <label for="confirmPassword">Confirm New Password</label>
              <input
                id="confirmPassword"
                v-model="passwordForm.confirmPassword"
                type="password"
                autocomplete="new-password"
                :disabled="isUpdatingPassword"
              />
            </div>

            <p v-if="passwordError" class="settings-alert error">{{ passwordError }}</p>
            <p v-if="passwordSuccess" class="settings-alert success">{{ passwordSuccess }}</p>

            <button class="btn btn-primary" type="submit" :disabled="isUpdatingPassword">
              {{ isUpdatingPassword ? 'Updating...' : 'Update Password' }}
            </button>
          </form>
        </section>

        <section v-if="activeTab === 'preferences'" class="settings-card">
          <div class="card-header">
            <div>
              <h2>Preferences</h2>
              <p>Notification preferences are retained for the current session.</p>
            </div>
          </div>

          <div class="preferences-subsection">
            <div class="preference-item" v-for="item in preferenceItems" :key="item.label">
              <div class="preference-text">
                <h4>{{ item.label }}</h4>
                <p>{{ item.description }}</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" v-model="item.enabled" />
                <span class="slider"></span>
              </label>
            </div>
          </div>
        </section>
      </div>
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { apiUrl } from '@/shared/utils/apiBase.js';
import { AUTH_STORAGE_KEYS } from '@/modules/authentication/utils/authStorage.js';

const authStore = useAuthenticationStore();

const activeTab = ref('account');
const isLoadingProfile = ref(false);
const isSavingAccount = ref(false);
const isUpdatingPassword = ref(false);
const loadError = ref('');
const accountError = ref('');
const accountSuccess = ref('');
const passwordError = ref('');
const passwordSuccess = ref('');
const selectedPhotoName = ref('');
const profilePhotoInput = ref(null);

const accountProfile = reactive({
  accountIdentifier: null,
  idNumber: '',
  lastName: '',
  firstName: '',
  emailAddress: '',
  roleDesignation: '',
  roleLabel: '',
  accountType: '',
  contactNumber: '',
  profilePhotoData: null,
});

const accountForm = reactive({
  firstName: '',
  lastName: '',
  contactNumber: '',
  profilePhotoData: null,
});

const passwordForm = reactive({
  currentPassword: '',
  newPassword: '',
  confirmPassword: '',
});

const settingsTabs = [
  { label: 'Account Settings', value: 'account' },
  { label: 'Security', value: 'security' },
  { label: 'Preferences', value: 'preferences' },
];

const preferenceItems = reactive([
  { label: 'Reservation Updates', description: 'Receive updates about your reservations', enabled: true },
  { label: 'Upcoming Reminders', description: 'Reminder for upcoming reservations', enabled: true },
  { label: 'System Notifications', description: 'System updates and maintenance notices', enabled: true },
]);

const userRole = computed(() => (authStore.userRole === 'ROLE_ADMIN' ? 'ADMINISTRATOR' : 'BORROWER'));
const navigationItems = computed(() => (authStore.userRole === 'ROLE_ADMIN' ? adminNavigationItems : borrowerNavigationItems));
const fullName = computed(() => [accountProfile.firstName, accountProfile.lastName].filter(Boolean).join(' '));
const accountInitials = computed(() => {
  const firstInitial = accountProfile.firstName?.charAt(0) || '';
  const lastInitial = accountProfile.lastName?.charAt(0) || '';
  return `${firstInitial}${lastInitial}`.toUpperCase() || 'U';
});

const passwordRequirements = computed(() => ({
  length: passwordForm.newPassword.length >= 8,
  upper: /[A-Z]/.test(passwordForm.newPassword),
  lower: /[a-z]/.test(passwordForm.newPassword),
  number: /\d/.test(passwordForm.newPassword),
  special: /[^A-Za-z\d]/.test(passwordForm.newPassword),
}));

onMounted(loadAccountSettings);

function selectTab(tabValue) {
  if (tabValue === activeTab.value) return;

  if (activeTab.value === 'account') {
    resetAccountForm();
  }

  if (activeTab.value === 'security') {
    resetPasswordForm();
  }

  activeTab.value = tabValue;
  accountError.value = '';
  accountSuccess.value = '';
  passwordError.value = '';
  passwordSuccess.value = '';

  if (tabValue === 'account' && !accountForm.firstName && !accountForm.lastName && !accountForm.contactNumber) {
    fillAccountFormFromProfile();
  }
}

async function loadAccountSettings() {
  isLoadingProfile.value = true;
  loadError.value = '';

  try {
    const response = await fetch(apiUrl('/api/v1/accounts/me/settings'), {
      headers: buildAuthHeaders(),
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok || payload.success === false) {
      throw new Error(payload.errorMessage || 'Unable to load account settings.');
    }

    applyAccountProfile(payload.data?.account || {});
    fillAccountFormFromProfile();
  } catch (error) {
    loadError.value = error.message || 'Unable to load account settings.';
  } finally {
    isLoadingProfile.value = false;
  }
}

async function saveAccountSettings() {
  if (isSavingAccount.value) return;

  accountError.value = '';
  accountSuccess.value = '';

  const validationError = validateAccountForm();
  if (validationError) {
    accountError.value = validationError;
    return;
  }

  isSavingAccount.value = true;
  try {
    const body = {
      firstName: accountForm.firstName,
      lastName: accountForm.lastName,
      contactNumber: accountForm.contactNumber,
    };

    if (accountForm.profilePhotoData) {
      body.profilePhotoData = accountForm.profilePhotoData;
    }

    const response = await fetch(apiUrl('/api/v1/accounts/me/settings'), {
      method: 'PUT',
      headers: buildAuthHeaders(),
      body: JSON.stringify(body),
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok || payload.success === false) {
      throw new Error(payload.errorMessage || 'Unable to save account settings.');
    }

    applyAccountProfile(payload.data?.account || {});
    syncAuthAccount(payload.data?.account || {});
    resetAccountForm();
    accountSuccess.value = 'Account settings updated.';
  } catch (error) {
    accountError.value = error.message || 'Unable to save account settings.';
  } finally {
    isSavingAccount.value = false;
  }
}

async function updatePassword() {
  if (isUpdatingPassword.value) return;

  passwordError.value = '';
  passwordSuccess.value = '';

  const validationError = validatePasswordForm();
  if (validationError) {
    passwordError.value = validationError;
    return;
  }

  isUpdatingPassword.value = true;
  try {
    const response = await fetch(apiUrl('/api/v1/accounts/me/password'), {
      method: 'PUT',
      headers: buildAuthHeaders(),
      body: JSON.stringify(passwordForm),
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok || payload.success === false) {
      throw new Error(payload.errorMessage || 'Unable to update password.');
    }

    resetPasswordForm();
    passwordSuccess.value = 'Password updated.';
  } catch (error) {
    passwordError.value = error.message || 'Unable to update password.';
  } finally {
    isUpdatingPassword.value = false;
  }
}

function handlePhotoChange(event) {
  accountError.value = '';
  selectedPhotoName.value = '';
  accountForm.profilePhotoData = null;

  const file = event.target.files?.[0];
  if (!file) return;

  const fileName = file.name.toLowerCase();
  if (!fileName.endsWith('.jpg') || file.type !== 'image/jpeg') {
    accountError.value = 'Profile photo must be a .jpg image only.';
    event.target.value = '';
    return;
  }

  if (file.size > 2 * 1024 * 1024) {
    accountError.value = 'Profile photo must be 2 MB or smaller.';
    event.target.value = '';
    return;
  }

  const reader = new FileReader();
  reader.onload = () => {
    accountForm.profilePhotoData = String(reader.result || '');
    selectedPhotoName.value = file.name;
  };
  reader.onerror = () => {
    accountError.value = 'Unable to read the selected profile photo.';
    event.target.value = '';
  };
  reader.readAsDataURL(file);
}

function validateAccountForm() {
  const firstName = accountForm.firstName.trim();
  const lastName = accountForm.lastName.trim();
  const phone = accountForm.contactNumber.trim();

  if (!firstName || !lastName || !phone) {
    return 'First name, last name, and phone number are required.';
  }

  if (!isValidName(firstName) || !isValidName(lastName)) {
    return 'Names must contain letters and spaces only, with at least 2 characters each.';
  }

  if (!/^9\d{9}$/.test(phone)) {
    return 'Phone number must be 10 digits and begin with 9. Example: 9123456789.';
  }

  return '';
}

function sanitizePhoneInput() {
  let digitsOnly = accountForm.contactNumber.replace(/\D/g, '');
  if (digitsOnly.startsWith('09')) {
    digitsOnly = digitsOnly.slice(1);
  }
  accountForm.contactNumber = digitsOnly.slice(0, 10);
}

function validatePasswordForm() {
  if (!passwordForm.currentPassword || !passwordForm.newPassword || !passwordForm.confirmPassword) {
    return 'Current password, new password, and confirmation are required.';
  }

  if (passwordForm.currentPassword === passwordForm.newPassword) {
    return 'New password must be different from the current password.';
  }

  if (!Object.values(passwordRequirements.value).every(Boolean)) {
    return 'Password must meet all listed requirements.';
  }

  if (passwordForm.newPassword !== passwordForm.confirmPassword) {
    return 'New password and confirmation password do not match.';
  }

  return '';
}

function isValidName(value) {
  return /^[A-Za-z]+(?: [A-Za-z]+)*$/.test(value) && value.trim().length >= 2;
}

function applyAccountProfile(account) {
  accountProfile.accountIdentifier = account.accountIdentifier ?? null;
  accountProfile.idNumber = account.idNumber || '';
  accountProfile.lastName = account.lastName || '';
  accountProfile.firstName = account.firstName || '';
  accountProfile.emailAddress = account.emailAddress || '';
  accountProfile.roleDesignation = account.roleDesignation || '';
  accountProfile.roleLabel = account.roleLabel || '';
  accountProfile.accountType = account.accountType || '';
  accountProfile.contactNumber = account.contactNumber || '';
  accountProfile.profilePhotoData = account.profilePhotoData || null;
}

function fillAccountFormFromProfile() {
  accountForm.firstName = accountProfile.firstName || '';
  accountForm.lastName = accountProfile.lastName || '';
  accountForm.contactNumber = accountProfile.contactNumber || '';
  accountForm.profilePhotoData = null;
  selectedPhotoName.value = '';
  if (profilePhotoInput.value) {
    profilePhotoInput.value.value = '';
  }
}

function resetAccountForm() {
  accountForm.firstName = '';
  accountForm.lastName = '';
  accountForm.contactNumber = '';
  accountForm.profilePhotoData = null;
  selectedPhotoName.value = '';
  accountError.value = '';
  if (profilePhotoInput.value) {
    profilePhotoInput.value.value = '';
  }
}

function resetPasswordForm() {
  passwordForm.currentPassword = '';
  passwordForm.newPassword = '';
  passwordForm.confirmPassword = '';
  passwordError.value = '';
}

function buildAuthHeaders() {
  const headers = {
    'Content-Type': 'application/json',
  };

  if (authStore.authToken) {
    headers.Authorization = `Bearer ${authStore.authToken}`;
  }

  return headers;
}

function syncAuthAccount(account) {
  if (!account || !account.accountIdentifier) return;

  const nextAccount = {
    ...(authStore.accountData || {}),
    ...account,
  };

  authStore.accountData = nextAccount;
  localStorage.setItem(AUTH_STORAGE_KEYS.account, JSON.stringify(nextAccount));

  if (authStore.clerkAccountData) {
    authStore.clerkAccountData = {
      ...authStore.clerkAccountData,
      ...account,
    };
    localStorage.setItem(AUTH_STORAGE_KEYS.clerkAccount, JSON.stringify(authStore.clerkAccountData));
  }
}
</script>

<style scoped>
@import './css/SettingsPage.css';
</style>
