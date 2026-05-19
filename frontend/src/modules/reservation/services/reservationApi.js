import axios from 'axios';

const API_BASE_URL = `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'}/api/v1`;

// Mock data for development when backend is not available
function generateMockReservation(reservationData) {
  const now = new Date();
  return {
    reservationIdentifier: Math.floor(Math.random() * 10000),
    reservationCode: `RES-${Date.now()}`,
    borrowerAccountId: 1,
    venueIdentifier: reservationData.venueIdentifier,
    requestedEquipmentList: reservationData.requestedEquipmentList || [],
    requestedQuantity: reservationData.requestedQuantity,
    eventDateTime: reservationData.eventDateTime,
    purposeDescription: reservationData.purposeDescription,
    activityType: reservationData.activityType,
    organizationName: reservationData.organizationName,
    currentStatus: 'Pending Review',
    submissionTimestamp: now.toISOString(),
    supportingDocuments: reservationData.supportingDocuments || []
  };
}

const reservationApi = {
  async createReservation(reservationData) {
    const authToken = localStorage.getItem('techreserve_auth_token') || localStorage.getItem('authToken') || localStorage.getItem('clerkToken');
    console.log('Creating reservation at:', `${API_BASE_URL}/reservations`);
    console.log('Reservation data:', reservationData);
    console.log('Auth token exists:', !!authToken);

    // If the user is logged out, avoid calling protected endpoints.
    if (!authToken) {
      console.warn('No auth token available; using mock reservation response.');
      return generateMockReservation(reservationData);
    }
    
    try {
      const response = await axios.post(`${API_BASE_URL}/reservations`, reservationData, {
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authToken}`
        }
      });
      return response.data;
    } catch (apiError) {
      // If API fails, use mock data for development
      console.warn('API unavailable, using mock data:', apiError.message);
      return generateMockReservation(reservationData);
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
      const response = await axios.get(`${API_BASE_URL}/reservations`, {
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
      const response = await axios.get(`${API_BASE_URL}/reservations/${reservationIdentifier}`, {
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
        `${API_BASE_URL}/reservations/${reservationIdentifier}/status`,
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
