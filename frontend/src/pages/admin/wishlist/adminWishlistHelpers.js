export function normalizeWishlistAccount(account) {
  const roleDesignation = account.roleDesignation || account.role_designation || 'ROLE_BORROWER';
  const firstName = account.firstName || account.first_name || '';
  const lastName = account.lastName || account.last_name || '';
  const emailAddress = account.emailAddress || account.email_address || '';
  const accountType = account.accountType || resolveAccountType(account, roleDesignation);
  const inviteSentAt = account.inviteSentAt || account.invite_sent_at || null;
  const inviteExpiresAt = account.inviteExpiresAt || account.invite_expires_at || null;
  const inviteAcceptedAt = account.inviteAcceptedAt || account.invite_accepted_at || null;
  const inviteStatus = account.inviteStatus || account.invite_status || null;
  const inviteInvitedBy = account.inviteInvitedBy || account.invite_invited_by || account.sentBy || account.sent_by || null;
  const accountStatus = resolveRequestStatus(account.accountStatus || account.status, inviteAcceptedAt, inviteExpiresAt);
  const idNumber = account.idNumber || account.studentIdNumber || account.accountIdentifier || account.account_identifier || 'N/A';
  const contactNumber = account.contactNumber || account.contact_number || account.phone || 'N/A';

  return {
    ...account,
    accountIdentifier: account.accountIdentifier || account.account_identifier || idNumber,
    rawIdNumber: String(idNumber),
    idNumber: formatIdNumber(idNumber),
    firstName,
    lastName,
    fullName: `${firstName} ${lastName}`.trim(),
    emailAddress,
    contactNumber,
    supportingDocumentName: account.supportingDocumentName || account.signup_supporting_document_name || null,
    supportingDocumentMimeType: account.supportingDocumentMimeType || account.signup_supporting_document_mime_type || null,
    supportingDocumentData: account.supportingDocumentData || account.signup_supporting_document_data || null,
    roleDesignation,
    role: resolveRoleName(account, roleDesignation),
    roleLabel: account.roleLabel || resolveRoleLabel(account, roleDesignation),
    accountType,
    accountStatus,
    registeredAt: account.registeredAt || account.createdTimestamp || account.created_timestamp || new Date().toISOString(),
    inviteStatus,
    inviteInvitedBy,
    inviteSentAt,
    inviteExpiresAt,
    inviteAcceptedAt,
    initials: `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase() || 'TR',
  };
}

export function getUniqueRequestAccounts(accounts) {
  const accountsByEmail = new Map();
  accounts.forEach((account) => {
    const emailKey = normalizeEmailForConfirmation(account.emailAddress);
    if (!emailKey) return;

    const existingAccount = accountsByEmail.get(emailKey);
    if (!existingAccount || new Date(account.registeredAt).getTime() > new Date(existingAccount.registeredAt).getTime()) {
      accountsByEmail.set(emailKey, account);
    }
  });

  return Array.from(accountsByEmail.values());
}

export function isPdfProof(account) {
  const mimeType = String(account?.supportingDocumentMimeType || '').toLowerCase();
  const fileName = String(account?.supportingDocumentName || '').toLowerCase();
  return mimeType === 'application/pdf' || fileName.endsWith('.pdf');
}

export function resolveRequestStatus(status, inviteAcceptedAt, inviteExpiresAt) {
  const normalized = String(status || 'pending').toLowerCase();
  if (inviteAcceptedAt || normalized === 'accepted' || normalized === 'approved' || normalized === 'verified') return 'verified';
  if (normalized === 'rejected' || normalized === 'denied') return 'rejected';
  if (inviteExpiresAt && new Date(inviteExpiresAt).getTime() < Date.now() && normalized !== 'rejected' && normalized !== 'denied') {
    return 'expired';
  }
  if (inviteExpiresAt && new Date(inviteExpiresAt).getTime() >= Date.now() && normalized !== 'rejected' && normalized !== 'denied') {
    return 'unverified';
  }
  if (normalized === 'invited' || normalized === 'unverified') return 'unverified';
  if (normalized === 'pending' || normalized === 'not_invited') return 'not_invited';
  return normalized;
}

export function resolveAccountType(account, roleDesignation) {
  const normalizedRole = String(roleDesignation).toUpperCase();
  if (normalizedRole.includes('ADMIN')) return 'Admin';
  if (normalizedRole.includes('STAFF') || normalizedRole.includes('EMPLOYEE')) return 'Employee';
  const department = String(account.department || '').toLowerCase();
  if (department.includes('staff') || department.includes('employee') || department.includes('technical') || department.includes('maintenance')) return 'Employee';
  return 'User';
}

export function resolveRoleLabel(account, roleDesignation) {
  const accountType = account.accountType || resolveAccountType(account, roleDesignation);
  if (String(roleDesignation).toUpperCase().includes('ADMIN')) return 'Admin';
  if (accountType === 'Employee') return resolveRoleName({ ...account, accountType }, roleDesignation);
  const department = String(account.department || '').toLowerCase();
  if (department.includes('faculty') || department.includes('employee')) return 'User: Faculty';
  return 'User: Student';
}

export function resolveRoleName(account, roleDesignation) {
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

export function getSortRoleName(account) {
  return account.accountType === 'User' ? getUserRoleName(account) : account.role;
}

export function getUserRoleName(account) {
  const roleText = `${account?.role || ''} ${account?.roleLabel || ''} ${account?.roleDesignation || ''}`.toLowerCase();
  return roleText.includes('faculty') ? 'Faculty' : 'Student';
}

export function getApprovalEmailLabel(account) {
  if (account?.accountType === 'Employee') return 'Employee company email';
  return account?.accountType === 'User' ? 'FIT email address' : 'Email address';
}

export function formatEmployeeRoleName(value) {
  const normalized = String(value || '').trim();
  if (!normalized || /^user:/i.test(normalized)) return '';
  if (/^role_/i.test(normalized)) return '';
  return normalized
    .replace(/[_-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

export function getEmailLabel(account) {
  return account?.accountType === 'Employee' ? 'Email Address:' : 'FIT Email Address:';
}

export function getAccountTypeBadgeClass(accountType) {
  return {
    'admin-wishlist-account-type-badge--employee': accountType === 'Employee',
    'admin-wishlist-account-type-badge--admin': accountType === 'Admin',
    'admin-wishlist-account-type-badge--user': accountType === 'User',
  };
}

export function formatIdNumber(idNumber) {
  const value = String(idNumber);
  if (value.includes('*') || value === 'N/A') return value;
  if (value.length <= 4) return value;
  return `${value.slice(0, 4)}*****`;
}

export function normalizeEmailForConfirmation(value) {
  return String(value || '')
    .replace(/[\u200B-\u200D\uFEFF]/g, '')
    .replace(/\s+/g, '')
    .trim()
    .toLowerCase();
}

export function getStatusLabel(status) {
  const normalized = String(status || '').toLowerCase();
  if (normalized === 'approved' || normalized === 'verified' || normalized === 'accepted') return 'Verified';
  if (normalized === 'not_invited' || normalized === 'pending') return 'Not invited';
  if (normalized === 'invited' || normalized === 'unverified') return 'Unverified';
  if (normalized === 'expired') return 'Expired';
  if (normalized === 'rejected' || normalized === 'denied') return 'Denied';
  return 'Unverified';
}

export function getStatusClass(status) {
  const normalized = String(status || '').toLowerCase();
  if (normalized === 'rejected' || normalized === 'denied') return 'admin-wishlist-status--denied';
  if (normalized === 'invited' || normalized === 'unverified') return 'admin-wishlist-status--invited';
  if (normalized === 'accepted' || normalized === 'approved' || normalized === 'verified') return 'admin-wishlist-status--accepted';
  if (normalized === 'expired') return 'admin-wishlist-status--expired';
  return 'admin-wishlist-status--pending';
}

export function formatDisplayDate(value) {
  if (!value) return 'N/A';
  return new Intl.DateTimeFormat('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date(value));
}

export function formatDisplayDateTime(value) {
  if (!value) return 'N/A';
  const parsedDate = new Date(value);
  if (Number.isNaN(parsedDate.getTime())) return 'N/A';

  return new Intl.DateTimeFormat('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  }).format(parsedDate);
}

export function formatNullableDateTime(value) {
  if (!value) return 'N/A';
  return formatDisplayDateTime(value);
}

export function getInviteSentStatus(account) {
  if (!account?.inviteSentAt) return 'Not sent';
  const inviteStatus = String(account.inviteStatus || '').trim();
  return inviteStatus ? toTitleCase(inviteStatus) : 'Sent';
}

export function getAcceptedStatus(account) {
  if (account?.inviteAcceptedAt) return 'Accepted';
  if (account?.inviteExpiresAt && new Date(account.inviteExpiresAt).getTime() < Date.now()) return 'Expired';
  if (account?.inviteSentAt) return 'Pending acceptance';
  return 'Not accepted';
}

export function toTitleCase(value) {
  return String(value || '')
    .replace(/[_-]+/g, ' ')
    .replace(/\w\S*/g, (part) => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase());
}

export function sanitizeNameInput(value) {
  return String(value || '').replace(/[^A-Za-z ]+/g, '').replace(/\s{2,}/g, ' ');
}

export function sanitizePhoneInput(value) {
  return String(value || '').replace(/\D/g, '').slice(0, 10);
}

export function validatePersonName(value) {
  const normalized = String(value || '').trim();
  const letterCount = (normalized.match(/[A-Za-z]/g) || []).length;
  return letterCount >= 2 && /^[A-Za-z ]+$/.test(normalized);
}

export function isInstitutionalAdminEmail(emailAddress) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailAddress)
    && (emailAddress.endsWith('@fit.edu.ph') || emailAddress.endsWith('@techreserve.edu.ph'));
}

export function validateAdminAccountForm(form) {
  const lastName = form.lastName.trim();
  const firstName = form.firstName.trim();
  const emailAddress = form.emailAddress.trim().toLowerCase();

  if (!validatePersonName(lastName)) {
    return 'Last name must have at least 2 letters and cannot contain numbers or symbols.';
  }

  if (!validatePersonName(firstName)) {
    return 'First name must have at least 2 letters and cannot contain numbers or symbols.';
  }

  if (!isInstitutionalAdminEmail(emailAddress)) {
    return 'Admin account must use a valid institutional email address.';
  }

  return '';
}

export function validateEmployeeAccountForm(form) {
  const lastName = form.lastName.trim();
  const firstName = form.firstName.trim();
  const phone = form.phone.trim();
  const idNumber = form.idNumber.trim();

  if (!validatePersonName(lastName)) {
    return 'Last name must have at least 2 letters and cannot contain numbers or symbols.';
  }

  if (!validatePersonName(firstName)) {
    return 'First name must have at least 2 letters and cannot contain numbers or symbols.';
  }

  if (idNumber === '') {
    return 'Work ID number is required.';
  }

  if (!/^9\d{9}$/.test(phone)) {
    return 'Phone number must be exactly 10 digits and begin with 9.';
  }

  return '';
}

export function formatCreateAccountError(result, accountType) {
  const conflict = result?.data?.conflict;
  if (!conflict) {
    return result?.error || `Unable to create ${accountType} account.`;
  }

  const fullName = `${conflict.firstName || ''} ${conflict.lastName || ''}`.trim() || 'Existing account';
  const status = getStatusLabel(conflict.status);
  const location = conflict.isApproved || String(conflict.status).toLowerCase() === 'approved'
    ? 'Manage Accounts'
    : 'Requests Hub or Manage Accounts';
  const matchedField = conflict.matchedField === 'idNumber'
    ? 'ID number'
    : conflict.matchedField === 'phone'
      ? 'phone number'
      : 'email';

  return `This ${matchedField} is already used by ${fullName} (${conflict.emailAddress}, ${conflict.accountType}, ${status}). Check ${location}.`;
}
