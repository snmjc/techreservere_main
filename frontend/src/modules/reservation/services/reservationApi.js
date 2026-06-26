import axios from 'axios';
import { apiUrl } from '@/shared/utils/apiBase.js';
import {
  buildAuthorizationHeaders,
  buildJsonAuthorizationHeaders,
  refreshAuthToken,
  resolveAuthToken,
} from '@/shared/utils/authToken.js';

const reservationApi = {
  async createReservation(reservationData) {
    let authToken = await resolveAuthToken();

    if (!authToken) {
      authToken = await refreshAuthToken();
    }

    // If the user is logged out, avoid calling protected endpoints.
    if (!authToken) {
      throw new Error('You must be signed in to create a reservation.');
    }
    
    try {
      const response = await postReservationRequest(reservationData, authToken);
      return response.data;
    } catch (apiError) {
      if (apiError?.response?.status === 401) {
        const refreshedToken = await refreshAuthToken();
        if (refreshedToken) {
          try {
            const retryResponse = await postReservationRequest(reservationData, refreshedToken);
            return retryResponse.data;
          } catch (retryError) {
            throw normalizeReservationCreateError(retryError);
          }
        }
      }

      throw normalizeReservationCreateError(apiError);
    }
  },

  async listReservations() {
    const authToken = await resolveAuthToken();

    // If the user is logged out, avoid calling protected endpoints.
    if (!authToken) {
      return { reservations: [] };
    }
    
    try {
      const response = await axios.get(apiUrl('/api/v1/reservations'), {
        headers: buildAuthorizationHeaders(authToken)
      });
      return response.data;
    } catch (error) {
      console.error('Error listing reservations:', error);
      console.warn('Reservations API endpoint not available, returning empty list');
      return { reservations: [] };
    }
  },

  async getReservationById(reservationIdentifier) {
    try {
      const authToken = await resolveAuthToken();
      const response = await axios.get(apiUrl(`/api/v1/reservations/${reservationIdentifier}`), {
        headers: buildAuthorizationHeaders(authToken)
      });
      return response.data;
    } catch (error) {
      console.error('Error getting reservation:', error);
      throw error;
    }
  },

  async updateReservationStatus(reservationIdentifier, status, rejectionReason = null, securityConfirmation = null) {
    try {
      const authToken = await resolveAuthToken();
      const response = await axios.put(
        apiUrl(`/api/v1/reservations/${reservationIdentifier}/status`),
        {
          currentStatus: status,
          rejectionReason: rejectionReason,
          confirmedAdminEmail: securityConfirmation?.confirmedAdminEmail || '',
          confirmedAdminPassword: securityConfirmation?.confirmedAdminPassword || '',
        },
        {
          headers: buildJsonAuthorizationHeaders(authToken)
        }
      );
      return response.data;
    } catch (error) {
      console.error('Error updating reservation status:', error);
      throw error;
    }
  }
};

async function postReservationRequest(reservationData, authToken) {
  return axios.post(apiUrl('/api/v1/reservations'), reservationData, {
    headers: buildJsonAuthorizationHeaders(authToken)
  });
}

function normalizeReservationCreateError(apiError) {
  const backendPayload = apiError?.response?.data || {};
  const backendMessage = backendPayload?.errorMessage || backendPayload?.message;
  if (backendMessage) {
    const normalizedError = new Error(backendMessage);
    normalizedError.name = backendPayload?.errorType || 'ReservationCreateFailed';
    normalizedError.code = backendPayload?.errorType || 'ReservationCreateFailed';
    normalizedError.status = apiError?.response?.status || 0;
    normalizedError.failureBucket = backendPayload?.data?.failureBucket || '';
    throw normalizedError;
  }

  if (apiError?.response?.status === 401) {
    const unauthorizedError = new Error('Your session expired. Please sign in again before submitting the reservation.');
    unauthorizedError.name = 'AuthenticationRequired';
    unauthorizedError.code = 'AuthenticationRequired';
    unauthorizedError.status = 401;
    throw unauthorizedError;
  }

  throw apiError;
}

export default reservationApi;
