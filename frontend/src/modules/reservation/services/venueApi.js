import axios from 'axios';

const API_BASE_URL = `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'}/api/v1`;

const venueApi = {
  async listVenues() {
    try {
      const response = await axios.get(`${API_BASE_URL}/venues`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('authToken')}`
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error listing venues:', error);
      throw error;
    }
  },

  async getVenueById(venueIdentifier) {
    try {
      const response = await axios.get(`${API_BASE_URL}/venues/${venueIdentifier}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('authToken')}`
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error getting venue:', error);
      throw error;
    }
  }
};

export default venueApi;

