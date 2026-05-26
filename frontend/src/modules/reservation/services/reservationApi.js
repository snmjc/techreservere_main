import axios from 'axios';
import { apiUrl } from '@/shared/utils/apiBase.js';

const reservationApi = {
  async createReservation(reservationData) {
    const authToken = localStorage.getItem('techreserve_auth_token') || localStorage.getItem('authToken') || localStorage.getItem('clerkToken');
    console.log('Creating reservation at:', apiUrl('/api/v1/reservations'));
    console.log('Reservation data:', reservationData);
    console.log('Auth token exists:', !!authToken);

    // If the user is logged out, avoid calling protected endpoints.
    if (!authToken) {
      throw new Error('You must be signed in to create a reservation.');
    }
    
    try {
      const response = await axios.post(apiUrl('/api/v1/reservations'), reservationData, {
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authToken}`
        }
      });
      return response.data;
    } catch (apiError) {
      console.error('Error creating reservation:', apiError);
      throw apiError;
    }
  },

  async listReservations() {
    const authToken = localStorage.getItem('techreserve_auth_token') || localStorage.getItem('authToken') || localStorage.getItem('clerkToken');
    console.log('Listing reservations with token exists:', !!authToken);
    console.log('Token:', authToken ? authToken.substring(0, 20) + '...' : 'none');

    // If the user is logged out, avoid calling protected endpoints.
    if (!authToken) {
      return { reservations: [] };
    }
    
    try {
      const response = await axios.get(apiUrl('/api/v1/reservations'), {
        headers: {
          'Authorization': `Bearer ${authToken}`
        }
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
      const authToken = localStorage.getItem('techreserve_auth_token') || localStorage.getItem('authToken');
      const response = await axios.get(apiUrl(`/api/v1/reservations/${reservationIdentifier}`), {
        headers: {
          'Authorization': `Bearer ${authToken}`
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error getting reservation:', error);
      throw error;
    }
  },

  async updateReservationStatus(reservationIdentifier, status, rejectionReason = null) {
    try {
      const authToken = localStorage.getItem('techreserve_auth_token') || localStorage.getItem('authToken');
      const response = await axios.put(
        apiUrl(`/api/v1/reservations/${reservationIdentifier}/status`),
        {
          currentStatus: status,
          rejectionReason: rejectionReason
        },
        {
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${authToken}`
          }
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
