import axios from 'axios';
import { apiUrl } from '@/shared/utils/apiBase.js';

const venueApi = {
  async listVenues() {
    const authToken = localStorage.getItem('techreserve_auth_token') || localStorage.getItem('authToken') || localStorage.getItem('clerkToken');
    try {
      const response = await axios.get(apiUrl('/api/v1/venues'), {
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
      const response = await axios.get(apiUrl(`/api/v1/venues/${venueIdentifier}`), {
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
      const response = await axios.post(apiUrl('/api/v1/venues'), venueData, {
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
      const response = await axios.put(apiUrl(`/api/v1/venues/${venueIdentifier}`), venueData, {
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
      const response = await axios.delete(apiUrl(`/api/v1/venues/${venueIdentifier}`), {
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

