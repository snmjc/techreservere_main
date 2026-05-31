export function normalizeAccount(account) {
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

export function normalizeManageAccountStatus(status, isActive) {
  const normalizedStatus = String(status || '').trim().toLowerCase();
  if (isActive === false || normalizedStatus === 'disabled') return 'Disabled';
  return 'Active';
}

export function resolveAccountType(account, roleDesignation) {
  const role = String(roleDesignation).toUpperCase();
  if (role.includes('ADMIN')) return 'Admin';
  const department = String(account.department || '').toLowerCase();
  if (role.includes('STAFF') || role.includes('EMPLOYEE') || department.includes('staff') || department.includes('employee') || department.includes('technical') || department.includes('maintenance') || department.includes('support')) return 'Employee';
  return 'User';
}

export function resolveRoleLabel(account, roleDesignation, accountType) {
  if (accountType === 'Admin') return 'Admin';
  if (accountType === 'Employee') return account.department || 'Technical Staff';
  const department = String(account.department || '').trim();
  if (/faculty/i.test(department)) return 'Faculty';
  if (/student/i.test(department)) return 'Student';
  return String(roleDesignation).toUpperCase().includes('FACULTY') ? 'Faculty' : 'Student';
}

export function compareManageAccounts(first, second, sortMode) {
  if (sortMode === 'name') {
    return first.fullName.localeCompare(second.fullName);
  }

  if (sortMode === 'role') {
    return getSortRoleName(first).localeCompare(getSortRoleName(second)) || first.fullName.localeCompare(second.fullName);
  }

  if (sortMode === 'status') {
    return first.accountStatus.localeCompare(second.accountStatus) || first.fullName.localeCompare(second.fullName);
  }

  const firstTime = new Date(first.createdTimestamp || 0).getTime();
  const secondTime = new Date(second.createdTimestamp || 0).getTime();
  return firstTime - secondTime;
}

export function formatReservationDetails(reservationDetails) {
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

export function formatAssignments(assignments) {
  if (!assignments) return 'N/A';

  return [
    assignments.assignedTask,
    assignments.assignmentType,
    assignments.assignedToAccountId ? `Account #${assignments.assignedToAccountId}` : '',
  ].filter(Boolean).join(' | ') || 'N/A';
}

export function sanitizeAccountNameInput(value) {
  return String(value || '').replace(/[^A-Za-z ]+/g, '').replace(/\s{2,}/g, ' ');
}

export function sanitizeAccountPhoneInput(value) {
  return String(value || '').replace(/\D/g, '').slice(0, 10);
}

export function validateManageAccountUpdateForm(updateForm) {
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

export function isValidAccountName(value) {
  return /^[A-Za-z]+(?: [A-Za-z]+)*$/.test(String(value || '').trim()) && String(value || '').trim().length >= 2;
}

export function normalizeEmailForConfirmation(value) {
  return String(value || '')
    .replace(/[\u200B-\u200D\uFEFF]/g, '')
    .replace(/\s+/g, '')
    .trim()
    .toLowerCase();
}

export function formatIdNumber(value) {
  const text = String(value || 'N/A');
  if (text.includes('*') || text === 'N/A') return text;
  if (text.length <= 4) return text;
  return `${text.slice(0, 4)}*****`;
}

export function formatDateTime(value) {
  if (!value) return 'N/A';
  return new Intl.DateTimeFormat('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(value));
}

export function formatNullableDateTime(value) {
  return value ? formatDateTime(value) : 'N/A';
}

export function getStatusClass(status) {
  const normalized = String(status || '').toLowerCase();
  if (normalized === 'disabled') return 'manage-accounts-status--disabled';
  return 'manage-accounts-status--active';
}

export function getEmailLabel(account) {
  return account?.accountType === 'Employee' ? 'Email Address:' : 'FIT Email Address:';
}

export function getRoleOptions(account) {
  if (!account) return ['Admin'];
  if (account.accountType === 'Admin') return ['Admin'];
  if (account.accountType === 'Employee') return getEmployeeRoleOptions();
  return ['Student', 'Faculty'];
}

export function getEmployeeRoleOptions() {
  return ['Support Staff', 'Technical Staff', 'Maintenance Staff'];
}

export function getUpdateAccountTypeForPayload(updateForm, isEmployeeUpdateModal) {
  if (isEmployeeUpdateModal) {
    return updateForm.accountType === 'Admin' ? 'Admin' : 'Employee';
  }
  return updateForm.accountType;
}

export function getUpdateRoleLabelForPayload(accountType, updateForm) {
  if (accountType === 'Admin') return 'Admin';
  return updateForm.roleLabel;
}

export function normalizeUpdateRoleDesignation(accountType, roleLabel) {
  if (accountType === 'Admin' || roleLabel === 'Admin') return 'ROLE_ADMIN';
  if (accountType === 'Employee') return 'ROLE_STAFF';
  return roleLabel === 'Faculty' ? 'ROLE_FACULTY' : 'ROLE_BORROWER';
}

export function getSortRoleName(account) {
  return account.accountType === 'User' ? getUserRoleName(account) : account.roleLabel;
}

export function getUserRoleName(account) {
  const roleText = `${account?.roleLabel || ''} ${account?.roleDesignation || ''}`.toLowerCase();
  return roleText.includes('faculty') ? 'Faculty' : 'Student';
}

export function getAccountTypeClass(accountType) {
  return {
    'manage-accounts-type-pill--admin': accountType === 'Admin',
    'manage-accounts-type-pill--user': accountType === 'User',
    'manage-accounts-type-pill--employee': accountType === 'Employee',
  };
}

export function resolveActionPermissions(accountStatus, serverPermissions = null) {
  return {
    view: serverPermissions?.view ?? true,
    update: accountStatus === 'Active',
    disable: accountStatus === 'Active',
    activate: accountStatus === 'Disabled',
  };
}

export function canUpdateAccount(account) {
  return Boolean(account?.actionPermissions?.update);
}

export function canDisableAccount(account) {
  return Boolean(account?.actionPermissions?.disable);
}

export function canActivateAccount(account) {
  return Boolean(account?.actionPermissions?.activate);
}

export function getDefaultAccountTab() {
  return 'admin';
}
