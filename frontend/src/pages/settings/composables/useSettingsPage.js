import { computed, onMounted, reactive, ref } from 'vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { apiUrl } from '@/shared/utils/apiBase.js';
import { AUTH_STORAGE_KEYS } from '@/modules/authentication/utils/authStorage.js';

export function useSettingsPage() {
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
  const accountProfile = reactive(createEmptyAccountProfile());
  const accountForm = reactive(createEmptyAccountForm());
  const passwordForm = reactive(createEmptyPasswordForm());
  const preferenceItems = reactive([
    { label: 'Reservation Updates', description: 'Receive updates about your reservations', enabled: true },
    { label: 'Upcoming Reminders', description: 'Reminder for upcoming reservations', enabled: true },
    { label: 'System Notifications', description: 'System updates and maintenance notices', enabled: true },
  ]);

  const settingsTabs = [
    { label: 'Account Settings', value: 'account' },
    { label: 'Security', value: 'security' },
    { label: 'Preferences', value: 'preferences' },
  ];

  const userRole = computed(() => (authStore.userRole === 'ROLE_ADMIN' ? 'ADMINISTRATOR' : 'BORROWER'));
  const navigationItems = computed(() => (authStore.userRole === 'ROLE_ADMIN' ? adminNavigationItems : borrowerNavigationItems));
  const fullName = computed(() => [accountProfile.firstName, accountProfile.lastName].filter(Boolean).join(' '));
  const accountInitials = computed(() => `${accountProfile.firstName?.charAt(0) || ''}${accountProfile.lastName?.charAt(0) || ''}`.toUpperCase() || 'U');
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

    resetCurrentTabForm();
    activeTab.value = tabValue;
    clearMessages();

    if (tabValue === 'account' && !accountForm.firstName && !accountForm.lastName && !accountForm.contactNumber) {
      fillAccountFormFromProfile();
    }
  }

  async function loadAccountSettings() {
    isLoadingProfile.value = true;
    loadError.value = '';

    try {
      const payload = await requestJson('/api/v1/accounts/me/settings', { headers: buildAuthHeaders() });
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

    const validationError = validateAccountForm(accountForm);
    if (validationError) {
      accountError.value = validationError;
      return;
    }

    isSavingAccount.value = true;
    try {
      const payload = await requestJson('/api/v1/accounts/me/settings', {
        method: 'PUT',
        headers: buildAuthHeaders(),
        body: JSON.stringify(buildAccountSettingsBody(accountForm)),
      });

      applyAccountProfile(payload.data?.account || {});
      syncAuthAccount(authStore, payload.data?.account || {});
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

    const validationError = validatePasswordForm(passwordForm, passwordRequirements.value);
    if (validationError) {
      passwordError.value = validationError;
      return;
    }

    isUpdatingPassword.value = true;
    try {
      await requestJson('/api/v1/accounts/me/password', {
        method: 'PUT',
        headers: buildAuthHeaders(),
        body: JSON.stringify(passwordForm),
      });

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

    const validationError = validateProfilePhoto(file);
    if (validationError) {
      accountError.value = validationError;
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

  function sanitizePhoneInput() {
    let digitsOnly = accountForm.contactNumber.replace(/\D/g, '');
    if (digitsOnly.startsWith('09')) {
      digitsOnly = digitsOnly.slice(1);
    }
    accountForm.contactNumber = digitsOnly.slice(0, 10);
  }

  function fillAccountFormFromProfile() {
    accountForm.firstName = accountProfile.firstName || '';
    accountForm.lastName = accountProfile.lastName || '';
    accountForm.contactNumber = accountProfile.contactNumber || '';
    accountForm.profilePhotoData = null;
    selectedPhotoName.value = '';
    clearFileInput(profilePhotoInput);
  }

  function resetAccountForm() {
    Object.assign(accountForm, createEmptyAccountForm());
    selectedPhotoName.value = '';
    accountError.value = '';
    clearFileInput(profilePhotoInput);
  }

  function resetPasswordForm() {
    Object.assign(passwordForm, createEmptyPasswordForm());
    passwordError.value = '';
  }

  function buildAuthHeaders() {
    const headers = { 'Content-Type': 'application/json' };
    if (authStore.authToken) {
      headers.Authorization = `Bearer ${authStore.authToken}`;
    }
    return headers;
  }

  function applyAccountProfile(account) {
    Object.assign(accountProfile, {
      accountIdentifier: account.accountIdentifier ?? null,
      idNumber: account.idNumber || '',
      lastName: account.lastName || '',
      firstName: account.firstName || '',
      emailAddress: account.emailAddress || '',
      roleDesignation: account.roleDesignation || '',
      roleLabel: account.roleLabel || '',
      accountType: account.accountType || '',
      contactNumber: account.contactNumber || '',
      profilePhotoData: account.profilePhotoData || null,
    });
  }

  function resetCurrentTabForm() {
    if (activeTab.value === 'account') resetAccountForm();
    if (activeTab.value === 'security') resetPasswordForm();
  }

  function clearMessages() {
    accountError.value = '';
    accountSuccess.value = '';
    passwordError.value = '';
    passwordSuccess.value = '';
  }

  return {
    activeTab,
    isLoadingProfile,
    isSavingAccount,
    isUpdatingPassword,
    loadError,
    accountError,
    accountSuccess,
    passwordError,
    passwordSuccess,
    selectedPhotoName,
    profilePhotoInput,
    accountProfile,
    accountForm,
    passwordForm,
    settingsTabs,
    preferenceItems,
    userRole,
    navigationItems,
    fullName,
    accountInitials,
    passwordRequirements,
    selectTab,
    saveAccountSettings,
    updatePassword,
    handlePhotoChange,
    sanitizePhoneInput,
  };
}

async function requestJson(path, options) {
  const response = await fetch(apiUrl(path), options);
  const payload = await response.json().catch(() => ({}));

  if (!response.ok || payload.success === false) {
    throw new Error(payload.errorMessage || 'Unable to complete the request.');
  }

  return payload;
}

function buildAccountSettingsBody(accountForm) {
  const body = {
    firstName: accountForm.firstName,
    lastName: accountForm.lastName,
    contactNumber: accountForm.contactNumber,
  };

  if (accountForm.profilePhotoData) {
    body.profilePhotoData = accountForm.profilePhotoData;
  }

  return body;
}

function validateAccountForm(accountForm) {
  const firstName = accountForm.firstName.trim();
  const lastName = accountForm.lastName.trim();
  const phone = accountForm.contactNumber.trim();

  if (!firstName || !lastName || !phone) return 'First name, last name, and phone number are required.';
  if (!isValidName(firstName) || !isValidName(lastName)) return 'Names must contain letters and spaces only, with at least 2 characters each.';
  if (!/^9\d{9}$/.test(phone)) return 'Phone number must be 10 digits and begin with 9. Example: 9123456789.';
  return '';
}

function validatePasswordForm(passwordForm, passwordRequirements) {
  if (!passwordForm.currentPassword || !passwordForm.newPassword || !passwordForm.confirmPassword) {
    return 'Current password, new password, and confirmation are required.';
  }

  if (passwordForm.currentPassword === passwordForm.newPassword) {
    return 'New password must be different from the current password.';
  }

  if (!Object.values(passwordRequirements).every(Boolean)) {
    return 'Password must meet all listed requirements.';
  }

  if (passwordForm.newPassword !== passwordForm.confirmPassword) {
    return 'New password and confirmation password do not match.';
  }

  return '';
}

function validateProfilePhoto(file) {
  const fileName = file.name.toLowerCase();
  if (!fileName.endsWith('.jpg') || file.type !== 'image/jpeg') return 'Profile photo must be a .jpg image only.';
  if (file.size > 2 * 1024 * 1024) return 'Profile photo must be 2 MB or smaller.';
  return '';
}

function syncAuthAccount(authStore, account) {
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

function createEmptyAccountProfile() {
  return {
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
  };
}

function createEmptyAccountForm() {
  return {
    firstName: '',
    lastName: '',
    contactNumber: '',
    profilePhotoData: null,
  };
}

function createEmptyPasswordForm() {
  return {
    currentPassword: '',
    newPassword: '',
    confirmPassword: '',
  };
}

function clearFileInput(profilePhotoInput) {
  if (profilePhotoInput.value) {
    profilePhotoInput.value.value = '';
  }
}

function isValidName(value) {
  return /^[A-Za-z]+(?: [A-Za-z]+)*$/.test(value) && value.trim().length >= 2;
}
