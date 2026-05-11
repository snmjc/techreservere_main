import axios from 'axios';

const API_BASE_URL = `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'}/api/v1`;

const venueApi = {
  async listVenues() {
    const authToken = localStorage.getItem('techreserve_auth_token') || localStorage.getItem('authToken') || localStorage.getItem('clerkToken');
    try {
      const response = await axios.get(`${API_BASE_URL}/venues`, {
        headers: {
          'Authorization': `Bearer ${authToken}`
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error listing venues:', error);
      throw error;
    }
  },

  async getVenueById(venueIdentifier) {
    const authToken = localStorage.getItem('techreserve_auth_token') || localStorage.getItem('authToken') || localStorage.getItem('clerkToken');
    try {
      const response = await axios.get(`${API_BASE_URL}/venues/${venueIdentifier}`, {
        headers: {
          'Authorization': `Bearer ${authToken}`
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error getting venue:', error);
      throw error;
    }
  },

  async createVenue(venueData) {
    const authToken = localStorage.getItem('techreserve_auth_token') || localStorage.getItem('authToken') || localStorage.getItem('clerkToken');
    try {
      const response = await axios.post(`${API_BASE_URL}/venues`, venueData, {
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authToken}`
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error creating venue:', error);
      throw error;
    }
  },

  async updateVenue(venueIdentifier, venueData) {
    const authToken = localStorage.getItem('techreserve_auth_token') || localStorage.getItem('authToken') || localStorage.getItem('clerkToken');
    try {
      const response = await axios.put(`${API_BASE_URL}/venues/${venueIdentifier}`, venueData, {
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authToken}`
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error updating venue:', error);
      throw error;
    }
  },

  async deleteVenue(venueIdentifier) {
    const authToken = localStorage.getItem('techreserve_auth_token') || localStorage.getItem('authToken') || localStorage.getItem('clerkToken');
    try {
      const response = await axios.delete(`${API_BASE_URL}/venues/${venueIdentifier}`, {
        headers: {
          'Authorization': `Bearer ${authToken}`
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error deleting venue:', error);
      throw error;
    }
  }
};

export default venueApi;

