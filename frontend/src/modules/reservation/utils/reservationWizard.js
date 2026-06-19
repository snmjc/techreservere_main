import { ROUTE_NAMES } from '@/router/routeNames.js';

export const RESERVATION_WIZARD_ROUTES = new Set([
  ROUTE_NAMES.borrowerCreateReservation,
  'borrowerCreateReservationVenuePage',
  'borrowerCreateReservationAdditionalPage',
  'borrowerCreateReservationDocumentsPage',
  'borrowerCreateReservationSummaryPage',
]);

export const RESERVATION_WIZARD_STORAGE_KEY = 'techreserve:reservation-wizard';
export const MAX_EQUIPMENT_SELECTION_COUNT = 5;

export function isReservationWizardRoute(routeName) {
  return RESERVATION_WIZARD_ROUTES.has(String(routeName || ''));
}

export function readReservationWizardCache() {
  if (typeof window === 'undefined') {
    return null;
  }

  try {
    const rawValue = window.sessionStorage.getItem(RESERVATION_WIZARD_STORAGE_KEY);
    if (!rawValue) {
      return null;
    }

    const parsedValue = JSON.parse(rawValue);
    return parsedValue && typeof parsedValue === 'object' ? parsedValue : null;
  } catch (error) {
    console.warn('Unable to read the reservation wizard cache.', error);
    return null;
  }
}

export function writeReservationWizardCache(payload) {
  if (typeof window === 'undefined') {
    return;
  }

  window.sessionStorage.setItem(RESERVATION_WIZARD_STORAGE_KEY, JSON.stringify(payload));
}

export function clearReservationWizardCache() {
  if (typeof window === 'undefined') {
    return;
  }

  window.sessionStorage.removeItem(RESERVATION_WIZARD_STORAGE_KEY);
}
