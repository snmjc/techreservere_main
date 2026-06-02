import { validatePersonName } from './wishlistTextHelpers.js';
import { getStatusLabel } from './wishlistStatusHelpers.js';

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

export function getEmployeeCreateError(form, accounts) {
  const validationError = validateEmployeeAccountForm(form);
  if (validationError) return validationError;

  if (wishlistIdNumberExists(accounts, form.idNumber)) {
    return 'A staff account with this Work ID number already exists.';
  }

  if (wishlistPhoneNumberExists(accounts, form.phone)) {
    return 'A staff account with this phone number already exists.';
  }

  return '';
}

export function buildEmployeeAccountPayload(form) {
  return {
    lastName: form.lastName,
    firstName: form.firstName,
    phone: form.phone,
    idNumber: form.idNumber,
    role: form.role,
  };
}

export function formatCreateAccountError(result, accountType) {
  const conflict = result?.data?.conflict;
  if (!conflict) {
    return result?.error || `Unable to create ${accountType} account.`;
  }

  return `This ${formatMatchedField(conflict.matchedField)} is already used by ${formatConflictAccount(conflict)}. Check ${formatConflictLocation(conflict)}.`;
}

function isInstitutionalAdminEmail(emailAddress) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailAddress)
    && (emailAddress.endsWith('@fit.edu.ph') || emailAddress.endsWith('@techreserve.edu.ph'));
}

function wishlistIdNumberExists(accounts, idNumber) {
  const normalizedIdNumber = String(idNumber || '').trim().toLowerCase();

  return accounts.some((account) => String(account.rawIdNumber || account.idNumber || '').trim().toLowerCase() === normalizedIdNumber);
}

function wishlistPhoneNumberExists(accounts, phone) {
  return accounts.some((account) => String(account.contactNumber || '').replace(/\D/g, '') === phone);
}

function formatMatchedField(matchedField) {
  if (matchedField === 'idNumber') return 'ID number';
  if (matchedField === 'phone') return 'phone number';
  return 'email';
}

function formatConflictAccount(conflict) {
  const fullName = `${conflict.firstName || ''} ${conflict.lastName || ''}`.trim() || 'Existing account';
  return `${fullName} (${conflict.emailAddress}, ${conflict.accountType}, ${getStatusLabel(conflict.status)})`;
}

function formatConflictLocation(conflict) {
  return conflict.isApproved || String(conflict.status).toLowerCase() === 'approved'
    ? 'Manage Accounts'
    : 'Requests Hub or Manage Accounts';
}
