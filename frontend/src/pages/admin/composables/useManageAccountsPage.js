import { computed, onMounted, reactive, ref } from 'vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { adminManageAccountsApi } from '@/services/adminManageAccountsApi.js';
import {
  canActivateAccount,
  canDisableAccount,
  canUpdateAccount,
  compareManageAccounts,
  formatAssignedEmployee,
  formatDateTime,
  formatDisplayValue,
  formatEquipmentList,
  formatNullableDateTime,
  getAcceptedStatusLabel,
  getAccountTypeClass,
  getDefaultAccountTab,
  getEmployeeRoleOptions,
  getInviteSentStatusLabel,
  getReservationLabel,
  getStatusClass,
  getUserRoleName,
  getWorkLogStatusClass,
  normalizeAccount,
  normalizeEmailForConfirmation,
  normalizeUpdateRoleDesignation,
  sanitizeAccountNameInput,
  sanitizeAccountPhoneInput,
  validateProfilePhotoFile,
  validateManageAccountUpdateForm,
} from '../manageAccounts/manageAccountsHelpers.js';

export function useManageAccountsPage() {
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
  const viewAccountLoading = ref(false);
  const viewAccountError = ref('');
  const updateAccount = ref(null);
  const updateAccountLoading = ref(false);
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
    profilePhotoMimeType: '',
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
  const isUpdateFormReady = computed(() => !updateAccountLoading.value && validateUpdateAccountForm() === '');

  const accountTabs = computed(() => [
    { label: 'Admin', value: 'admin', count: countAccountsByType('Admin') },
    { label: 'User', value: 'user', count: countAccountsByType('User') },
    { label: 'Employee', value: 'employee', count: countAccountsByType('Employee') },
  ]);

  const filteredAccounts = computed(() => {
    const type = getActiveAccountType();
    const query = searchQueryText.value.trim().toLowerCase();

    return normalizedAccounts.value
      .filter((account) => account.accountType === type)
      .filter(matchesStatusFilter)
      .filter(matchesUserRoleFilter)
      .filter((account) => matchesSearchQuery(account, query))
      .sort(sortFilteredAccounts);
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
    return compareManageAccounts(first, second, sortMode.value);
  }

  async function openViewModal(account) {
    if (!account) return;
    viewAccount.value = account;
    viewAccountLoading.value = true;
    viewAccountError.value = '';

    const result = await adminManageAccountsApi.getAccountById(account.accountIdentifier, authStore.authToken);
    viewAccountLoading.value = false;

    if (!result.success) {
      viewAccountError.value = result.error || 'Unable to load the latest account details.';
      return;
    }

    const latestAccount = result.data.account || result.data;
    if (!latestAccount) {
      viewAccountError.value = 'Account details are unavailable.';
      return;
    }

    viewAccount.value = normalizeAccount(latestAccount);
    upsertAccount(latestAccount);
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

  async function openUpdateModal(account) {
    if (!canUpdateAccount(account)) {
      showToast('Only active accounts can be updated.');
      return;
    }

    updateAccount.value = account;
    hydrateUpdateForm(account);
    modalErrorMessage.value = '';
    updateAccountLoading.value = true;

    const result = await adminManageAccountsApi.getAccountById(account.accountIdentifier, authStore.authToken);
    updateAccountLoading.value = false;

    if (!result.success) {
      modalErrorMessage.value = result.error || 'Unable to load the latest account details.';
      return;
    }

    const latestAccount = result.data.account || result.data;
    if (!latestAccount) {
      modalErrorMessage.value = 'Account details are unavailable.';
      return;
    }

    const normalizedAccount = normalizeAccount(latestAccount);
    updateAccount.value = normalizedAccount;
    hydrateUpdateForm(normalizedAccount);
    upsertAccount(latestAccount);
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
    viewAccountLoading.value = false;
    viewAccountError.value = '';
    updateAccount.value = null;
    updateAccountLoading.value = false;
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
    updateForm.profilePhotoMimeType = '';
    updateForm.profilePhotoData = '';
    updateForm.profilePhotoPreview = '';
  }

  function hydrateUpdateForm(account) {
    updateForm.idNumber = account.rawIdNumber || account.idNumber;
    updateForm.lastName = account.lastName;
    updateForm.firstName = account.firstName;
    updateForm.emailAddress = account.emailAddress;
    updateForm.contactNumber = account.contactNumber || '';
    updateForm.roleDesignation = normalizeUpdateRoleDesignation(account.accountType, account.roleLabel);
    updateForm.roleLabel = account.roleLabel;
    updateForm.accountType = account.accountType;
    updateForm.profilePhotoName = '';
    updateForm.profilePhotoMimeType = '';
    updateForm.profilePhotoData = '';
    updateForm.profilePhotoPreview = account.profilePhotoData || '';
    if (account.accountType === 'Employee' && !getEmployeeRoleOptions().includes(updateForm.roleLabel)) {
      updateForm.roleLabel = account.roleLabel || 'Technical Staff';
    }
  }

  function sanitizeUpdateNameField(fieldName) {
    updateForm[fieldName] = sanitizeAccountNameInput(updateForm[fieldName]);
  }

  function sanitizeUpdatePhone() {
    updateForm.contactNumber = sanitizeAccountPhoneInput(updateForm.contactNumber);
  }

  function validateUpdateAccountForm() {
    return validateManageAccountUpdateForm(updateForm);
  }

  function handleUpdateProfilePhotoChange(event) {
    const file = event.target.files?.[0] || null;
    updateForm.profilePhotoName = '';
    updateForm.profilePhotoMimeType = '';
    updateForm.profilePhotoData = '';

    if (!file) {
      updateForm.profilePhotoPreview = updateAccount.value?.profilePhotoData || '';
      modalErrorMessage.value = '';
      return;
    }

    const profilePhotoError = validateProfilePhotoFile(file);
    if (profilePhotoError) {
      modalErrorMessage.value = profilePhotoError;
      event.target.value = '';
      updateForm.profilePhotoPreview = updateAccount.value?.profilePhotoData || '';
      return;
    }

    const reader = new FileReader();
    reader.onload = () => {
      updateForm.profilePhotoName = file.name;
      updateForm.profilePhotoMimeType = file.type;
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
    if (isProcessing.value || updateAccountLoading.value) return;

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

    const confirmationError = validateAccessConfirmation();
    if (confirmationError) {
      modalErrorMessage.value = confirmationError;
      return;
    }

    if (accessMode.value === 'delete') {
      await deleteConfirmedAccount();
      return;
    }

    await updateConfirmedAccountAccess();
  }

  function validateAccessConfirmation() {
    if (accessMode.value === 'activate' || accessMode.value === 'disable' || accessMode.value === 'delete') {
      if (!currentAdminEmail.value) {
        return 'Unable to verify the admin in-charge. Please sign in again.';
      }

      if (normalizeEmailForConfirmation(confirmEmailText.value) !== normalizeEmailForConfirmation(currentAdminEmail.value)) {
        return getAdminEmailConfirmationError();
      }
    } else if (normalizeEmailForConfirmation(confirmEmailText.value) !== normalizeEmailForConfirmation(accessAccount.value.emailAddress)) {
      return 'Please type the exact account email address to confirm.';
    }

    if (accessMode.value === 'delete' && confirmPasswordText.value.trim() === '') {
      return 'Please type your admin password to delete this account.';
    }

    return '';
  }

  function getAdminEmailConfirmationError() {
    if (accessMode.value === 'activate') return 'Please type your exact admin email to reactivate this account.';
    if (accessMode.value === 'disable') return 'Please type your exact admin email to deactivate this account.';
    return 'Please type your exact admin email to delete this account.';
  }

  async function deleteConfirmedAccount() {
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
  }

  async function updateConfirmedAccountAccess() {
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

  function getUpdateEmailLabel() {
    return updateForm.accountType === 'Employee' || isEmployeeUpdateModal.value ? 'Email:' : 'FIT Email Address:';
  }

  function showToast(message) {
    toastMessage.value = message;
    window.setTimeout(() => {
      if (toastMessage.value === message) toastMessage.value = '';
    }, 2800);
  }

  function countAccountsByType(accountType) {
    return normalizedAccounts.value.filter((account) => account.accountType === accountType).length;
  }

  function getActiveAccountType() {
    if (activeAccountTab.value === 'admin') return 'Admin';
    if (activeAccountTab.value === 'employee') return 'Employee';
    return 'User';
  }

  function matchesStatusFilter(account) {
    return showingFilterValue.value === 'all' || account.accountStatus.toLowerCase() === showingFilterValue.value;
  }

  function matchesUserRoleFilter(account) {
    return activeAccountTab.value !== 'user'
      || userRoleFilter.value === 'all'
      || getUserRoleName(account).toLowerCase() === userRoleFilter.value;
  }

  function matchesSearchQuery(account, query) {
    if (!query) return true;
    return [account.idNumber, account.fullName, account.emailAddress, account.username, account.roleLabel]
      .some((value) => String(value).toLowerCase().includes(query));
  }

  function sortFilteredAccounts(first, second) {
    const result = compareAccounts(first, second);
    return sortOrderAscending.value ? result : -result;
  }

  return {
    activeAccountTab,
    searchQueryText,
    showingFilterValue,
    sortMode,
    userRoleFilter,
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
    formatDateTime,
    formatDisplayValue,
    formatEquipmentList,
    formatNullableDateTime,
    getAcceptedStatusLabel,
    getReservationLabel,
    getInviteSentStatusLabel,
    getWorkLogStatusClass,
    getAccountTypeClass,
    getStatusClass,
  };
}
