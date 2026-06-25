import {
  isAllowedAdminEmail,
  isAllowedRequestHubUserEmail,
  normalizeIdNumber,
  normalizeStaffPhoneNumber,
  validatePersonName,
  validateRequiredIdNumber,
} from './wishlistTextHelpers.js';
import { getStatusLabel } from './wishlistStatusHelpers.js';

export function validateAdminAccountForm(form) {
  const idNumber = normalizeIdNumber(form.idNumber);
  const lastName = form.lastName.trim();
  const firstName = form.firstName.trim();
  const emailAddress = form.emailAddress.trim().toLowerCase();

  if (!validateRequiredIdNumber(idNumber)) {
    return 'ID number must be exactly 9 digits.';
  }

  if (!validatePersonName(lastName)) {
    return 'Last name must have at least 2 letters and cannot contain numbers or symbols.';
  }

  if (!validatePersonName(firstName)) {
    return 'First name must have at least 2 letters and cannot contain numbers or symbols.';
  }

  if (!isAllowedAdminEmail(emailAddress)) {
    return 'Admin account must use a valid @feutech.edu.ph email address.';
  }

  return '';
}

export function getAdminCreateError(form, accounts) {
  const validationError = validateAdminAccountForm(form);
  if (validationError) return validationError;

  if (wishlistEmailExists(accounts, form.emailAddress)) {
    return 'An account with this email already exists in Requests Hub.';
  }

  if (wishlistIdNumberExists(accounts, form.idNumber)) {
    return 'An account with this ID number already exists in Requests Hub.';
  }

  return '';
}

export function buildAdminAccountPayload(form) {
  return {
    idNumber: normalizeIdNumber(form.idNumber),
    lastName: form.lastName,
    firstName: form.firstName,
    emailAddress: form.emailAddress,
    roleDesignation: form.roleDesignation,
    confirmedAdminEmail: form.confirmedAdminEmail,
  };
}

export function getUserCreateError(form, accounts) {
  const validationError = validateUserAccountForm(form);
  if (validationError) return validationError;

  if (form.password !== form.confirmPassword) {
    return 'Password and confirm password must match.';
  }

  if (wishlistEmailExists(accounts, form.emailAddress)) {
    return 'An account with this email already exists in Requests Hub.';
  }

  return '';
}

export function validateUserAccountForm(form) {
  const lastName = String(form.lastName || '').trim();
  const firstName = String(form.firstName || '').trim();
  const emailAddress = String(form.emailAddress || '').trim().toLowerCase();
  const idNumber = normalizeIdNumber(form.idNumber);
  const role = String(form.role || '').trim();
  const password = String(form.password || '');

  if (lastName === '' || firstName === '' || emailAddress === '' || idNumber === '' || role === '' || password === '') {
    return 'Last name, first name, email, ID number, role, and password are required.';
  }

  if (!validateRequiredIdNumber(idNumber)) {
    return 'ID number must be exactly 9 digits.';
  }

  if (!filterEmailAddress(emailAddress)) {
    return 'Please provide a valid email address.';
  }

  if (!isAllowedRequestHubUserEmail(emailAddress)) {
    return 'User email must use @fit.edu.ph or @feutech.edu.ph only.';
  }

  return '';
}

export function buildUserAccountPayload(form) {
  return {
    lastName: form.lastName,
    firstName: form.firstName,
    emailAddress: form.emailAddress,
    idNumber: form.idNumber,
    roleDesignation: form.roleDesignation || 'ROLE_BORROWER',
    role: form.role,
    passwordText: form.password,
  };
}

export function validateEmployeeAccountForm(form) {
  const lastName = form.lastName.trim();
  const firstName = form.firstName.trim();
  const rawPhone = form.phone.trim();
  const phone = normalizeStaffPhoneNumber(rawPhone);
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

  if (!validateRequiredIdNumber(idNumber)) {
    return 'Work ID number must be exactly 9 digits.';
  }

  if (!/^(09\d{9}|9\d{9})$/.test(rawPhone) || !/^9\d{9}$/.test(phone)) {
    return 'Phone number must be 11 digits starting with 09, or 10 digits starting with 9.';
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
    phone: normalizeStaffPhoneNumber(form.phone),
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

function wishlistIdNumberExists(accounts, idNumber) {
  const normalizedIdNumber = normalizeIdNumber(idNumber).toLowerCase();

  return accounts.some((account) => String(account.rawIdNumber || account.idNumber || '').trim().toLowerCase() === normalizedIdNumber);
}

function wishlistEmailExists(accounts, emailAddress) {
  const normalizedEmailAddress = String(emailAddress || '').trim().toLowerCase();

  return accounts.some((account) => String(account.emailAddress || '').trim().toLowerCase() === normalizedEmailAddress);
}

function wishlistPhoneNumberExists(accounts, phone) {
  const normalizedPhone = normalizeStaffPhoneNumber(phone);
  return accounts.some((account) => normalizeStaffPhoneNumber(account.contactNumber || '') === normalizedPhone);
}

function filterEmailAddress(emailAddress) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailAddress);
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
