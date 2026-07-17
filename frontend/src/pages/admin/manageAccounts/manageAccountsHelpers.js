import {
  formatDisplayDateTime as formatSharedDateTime,
  formatNullableDateTime as formatSharedNullableDateTime,
} from '@/shared/utils/dateTimeDisplay.js';

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
    username: account.username || '',
    contactNumber: account.contactNumber || account.contact_number || '',
    profilePhotoData: account.profilePhotoData || account.profile_photo_data || '',
    supportingDocumentName: account.supportingDocumentName || account.signup_supporting_document_name || null,
    supportingDocumentMimeType: account.supportingDocumentMimeType || account.signup_supporting_document_mime_type || null,
    supportingDocumentData: account.supportingDocumentData || account.signup_supporting_document_data || null,
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
    inviteStatus: account.inviteStatus || account.invite_status || '',
    invitationStatus: account.invitationStatus || account.invitation_status || '',
    inviteInvitedBy: account.inviteInvitedBy || account.invite_invited_by || account.sentBy || account.sent_by || '',
    invitedAt: account.invitedAt || account.invited_at || '',
    approvedAt: account.approvedAt || account.approved_at || '',
    actionPermissions: resolveActionPermissions(accountStatus, account.actionPermissions),
  };
}

export function normalizeManageAccountStatus(status, isActive) {
  const normalizedStatus = String(status || '').trim().toLowerCase();
  if (isActive === false || normalizedStatus === 'disabled' || normalizedStatus === 'inactive' || normalizedStatus === 'suspended') return 'Disabled';
  if (normalizedStatus === 'pending') return 'Pending';
  if (normalizedStatus === 'verified' || normalizedStatus === 'invited') return 'Verified';
  return 'Active';
}

export function resolveAccountType(account, roleDesignation) {
  const role = String(roleDesignation).toUpperCase();
  if (role.includes('ADMIN')) return 'Admin';
  const department = String(account.department || '').toLowerCase();
  if (role.includes('STAFF') || role.includes('EMPLOYEE') || department.includes('staff') || department.includes('technical') || department.includes('maintenance') || department.includes('support')) return 'Employee';
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

export function getReservationLabel(reservationDetails) {
  if (!reservationDetails) return 'No linked reservation';
  return reservationDetails.reservationCode || `Reservation #${reservationDetails.reservationIdentifier || 'N/A'}`;
}

export function isPdfProof(account) {
  const mimeType = String(account?.supportingDocumentMimeType || '').toLowerCase();
  const fileName = String(account?.supportingDocumentName || '').toLowerCase();
  return mimeType === 'application/pdf' || fileName.endsWith('.pdf');
}

export function formatEquipmentList(equipmentList) {
  if (!Array.isArray(equipmentList) || equipmentList.length === 0) return 'N/A';

  return equipmentList
    .map((equipment) => {
      if (typeof equipment === 'string') return equipment;
      return equipment?.name || equipment?.itemName || equipment?.equipmentName || String(equipment);
    })
    .filter(Boolean)
    .join(', ') || 'N/A';
}

export function formatAssignedEmployee(assignments) {
  if (!assignments) return 'N/A';

  const employeeName = assignments.assignedStaffName || '';
  const employeeId = assignments.assignedStaffIdNumber ? `ID ${assignments.assignedStaffIdNumber}` : '';
  const employeeRole = assignments.assignedStaffRole || '';
  const fallbackAccount = assignments.assignedToAccountId ? `Account #${assignments.assignedToAccountId}` : '';

  return [employeeName || fallbackAccount, employeeId, employeeRole].filter(Boolean).join(' | ') || 'N/A';
}

export function getWorkLogStatusClass(status) {
  const normalized = String(status || '').toLowerCase();
  if (normalized.includes('complete') || normalized.includes('done')) return 'manage-accounts-work-log-status--complete';
  if (normalized.includes('progress') || normalized.includes('active')) return 'manage-accounts-work-log-status--active';
  if (normalized.includes('cancel') || normalized.includes('reject')) return 'manage-accounts-work-log-status--danger';
  return 'manage-accounts-work-log-status--pending';
}

export function sanitizeAccountNameInput(value) {
  return String(value || '').replace(/[^A-Za-z ]+/g, '').replace(/\s{2,}/g, ' ');
}

export function sanitizeAccountPhoneInput(value) {
  return String(value || '').replace(/\D/g, '').slice(0, 10);
}

export function validateProfilePhotoFile(file) {
  if (!file) return '';

  const fileName = String(file.name || '').toLowerCase();
  const mimeType = String(file.type || '').toLowerCase();

  if (!fileName.endsWith('.jpg') || mimeType !== 'image/jpeg') {
    return 'Profile photo must be a .jpg image only.';
  }

  return '';
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
  return formatSharedDateTime(value);
}

export function formatNullableDateTime(value) {
  return formatSharedNullableDateTime(value);
}

export function formatDisplayValue(value, fallback = 'N/A') {
  const normalizedValue = typeof value === 'string' ? value.trim() : value;
  if (normalizedValue === null || normalizedValue === undefined || normalizedValue === '') {
    return fallback;
  }

  return String(normalizedValue);
}

export function getInviteSentStatusLabel(account) {
  const invitationState = getInvitationState(account);

  if (invitationState.accepted) return 'Accepted';
  if (invitationState.expired) return 'Expired';
  if (invitationState.sent) return 'Sent';

  return 'Not Sent';
}

export function getAcceptedStatusLabel(account) {
  const invitationState = getInvitationState(account);

  if (invitationState.accepted) return 'Accepted';
  if (invitationState.expired) return 'Expired';
  if (invitationState.sent) return 'Waiting for Acceptance';

  return 'Not Yet Accepted';
}

export function getSystemEntryDateDisplay(account) {
  const acceptedAt = account?.inviteAcceptedAt || account?.approvedAt || null;
  if (acceptedAt) {
    return formatNullableDateTime(acceptedAt);
  }

  const addedAt = account?.createdTimestamp || null;
  if (addedAt) {
    return formatNullableDateTime(addedAt);
  }

  return 'No date available';
}

export function getStatusClass(status) {
  const normalized = String(status || '').toLowerCase();
  if (normalized === 'disabled') return 'manage-accounts-status--disabled';
  if (normalized === 'pending') return 'manage-accounts-status--pending';
  if (normalized === 'verified') return 'manage-accounts-status--pending';
  return 'manage-accounts-status--active';
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
  return 'ROLE_BORROWER';
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
    update: accountStatus === 'Active' || accountStatus === 'Verified',
    disable: accountStatus === 'Active' || accountStatus === 'Verified',
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

function getInvitationState(account) {
  const invitationStatus = String(account?.invitationStatus || '').trim().toLowerCase();
  const inviteStatus = String(account?.inviteStatus || '').trim().toLowerCase();
  const accountStatus = String(account?.accountStatus || account?.status || '').trim().toLowerCase();
  const hasSentAt = Boolean(account?.inviteSentAt || account?.invitedAt);
  const hasAcceptedAt = Boolean(account?.inviteAcceptedAt);

  const accepted = hasAcceptedAt
    || ['accepted', 'approved', 'active'].includes(invitationStatus)
    || ['accepted', 'approved', 'active'].includes(inviteStatus)
    || (accountStatus === 'active' && hasSentAt);

  const expired = !accepted && isInvitationExpired(account?.inviteExpiresAt);

  const sent = !accepted && !expired && (
    hasSentAt
    || ['sent', 'invited', 'pending'].includes(invitationStatus)
    || ['sent', 'invited', 'pending'].includes(inviteStatus)
    || accountStatus === 'verified'
    || accountStatus === 'invited'
  );

  return {
    accepted,
    expired,
    sent,
  };
}

function isInvitationExpired(value) {
  if (!value) return false;

  const expirationDate = new Date(value);
  if (Number.isNaN(expirationDate.getTime())) {
    return false;
  }

  return expirationDate.getTime() < Date.now();
}
