export {
  formatDisplayDate,
  formatDisplayDateTime,
  formatNullableDateTime,
} from '@/shared/utils/dateTimeDisplay.js';

export function normalizeEmailForConfirmation(value) {
  return String(value || '')
    .replace(/[\u200B-\u200D\uFEFF]/g, '')
    .replace(/\s+/g, '')
    .trim()
    .toLowerCase();
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

export function normalizeIdNumber(value) {
  return String(value || '').replace(/\s+/g, '').trim();
}

export function validateRequiredIdNumber(value) {
  return normalizeIdNumber(value) !== '';
}

export function isAllowedAdminEmail(value) {
  const normalized = String(value || '').trim().toLowerCase();
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalized)) {
    return false;
  }

  return normalized.endsWith('@feutech.edu.ph');
}

export function isAllowedRequestHubUserEmail(value) {
  const normalized = String(value || '').trim().toLowerCase();
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalized)) {
    return false;
  }

  return normalized.endsWith('@fit.edu.ph') || normalized.endsWith('@feutech.edu.ph');
}
