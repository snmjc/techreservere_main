import axios from 'axios';
import { apiUrl } from '@/shared/utils/apiBase.js';
import {
  buildAuthorizationHeaders,
  buildJsonAuthorizationHeaders,
  getStoredAuthToken,
} from '@/shared/utils/authToken.js';

const reservationApi = {
  async createReservation(reservationData) {
    const authToken = getStoredAuthToken();

    // If the user is logged out, avoid calling protected endpoints.
    if (!authToken) {
      throw new Error('You must be signed in to create a reservation.');
    }
    
    try {
      const response = await axios.post(apiUrl('/api/v1/reservations'), reservationData, {
        headers: buildJsonAuthorizationHeaders(authToken)
      });
      return response.data;
    } catch (apiError) {
      console.error('Error creating reservation:', apiError);
      const backendMessage = apiError?.response?.data?.errorMessage || apiError?.response?.data?.message;
      if (backendMessage) {
        throw new Error(backendMessage);
      }
      throw apiError;
    }
  },

  async listReservations() {
    const authToken = getStoredAuthToken();

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
      const authToken = getStoredAuthToken();
      const response = await axios.get(apiUrl(`/api/v1/reservations/${reservationIdentifier}`), {
        headers: buildAuthorizationHeaders(authToken)
      });
      return response.data;
    } catch (error) {
      console.error('Error getting reservation:', error);
      throw error;
    }
  },

  async updateReservationStatus(reservationIdentifier, status, rejectionReason = null) {
    try {
      const authToken = getStoredAuthToken();
      const response = await axios.put(
        apiUrl(`/api/v1/reservations/${reservationIdentifier}/status`),
        {
          currentStatus: status,
          rejectionReason: rejectionReason
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

export default reservationApi;
