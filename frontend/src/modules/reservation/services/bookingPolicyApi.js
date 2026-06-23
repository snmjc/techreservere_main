import { apiUrl } from '@/shared/utils/apiBase.js';
import { buildAuthorizationHeaders, resolveAuthToken } from '@/shared/utils/authToken.js';

async function requestBookingPolicy(path, options = {}) {
  const authToken = await resolveAuthToken();
  const response = await fetch(apiUrl(path), {
    method: options.method || 'GET',
    headers: {
      ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      ...buildAuthorizationHeaders(authToken),
    },
    body: options.body,
  });

  const result = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(result.errorMessage || result.message || 'Unable to load booking policy.');
  }

  return result.data || result;
}

export const bookingPolicyApi = {
  async getBookingWindow() {
    return requestBookingPolicy('/api/v1/reservation-policy/booking-window');
  },
};
