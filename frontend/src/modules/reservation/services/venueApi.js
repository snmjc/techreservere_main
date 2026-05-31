import axios from 'axios';
import { apiUrl } from '@/shared/utils/apiBase.js';
import {
  buildAuthorizationHeaders,
  buildJsonAuthorizationHeaders,
  getStoredAuthToken,
} from '@/shared/utils/authToken.js';

const venueApi = {
  async listVenues() {
    const authToken = getStoredAuthToken();
    try {
      const response = await axios.get(apiUrl('/api/v1/venues'), {
        headers: buildAuthorizationHeaders(authToken)
      });
      return response.data;
    } catch (error) {
      console.error('Error listing venues:', error);
      throw error;
    }
  },

  async getVenueById(venueIdentifier) {
    const authToken = getStoredAuthToken();
    try {
      const response = await axios.get(apiUrl(`/api/v1/venues/${venueIdentifier}`), {
        headers: buildAuthorizationHeaders(authToken)
      });
      return response.data;
    } catch (error) {
      console.error('Error getting venue:', error);
      throw error;
    }
  },

  async createVenue(venueData) {
    const authToken = getStoredAuthToken();
    try {
      const response = await axios.post(apiUrl('/api/v1/venues'), venueData, {
        headers: buildJsonAuthorizationHeaders(authToken)
      });
      return response.data;
    } catch (error) {
      console.error('Error creating venue:', error);
      throw error;
    }
  },

  async updateVenue(venueIdentifier, venueData) {
    const authToken = getStoredAuthToken();
    try {
      const response = await axios.put(apiUrl(`/api/v1/venues/${venueIdentifier}`), venueData, {
        headers: buildJsonAuthorizationHeaders(authToken)
      });
      return response.data;
    } catch (error) {
      console.error('Error updating venue:', error);
      throw error;
    }
  },

  async deleteVenue(venueIdentifier) {
    const authToken = getStoredAuthToken();
    try {
      const response = await axios.delete(apiUrl(`/api/v1/venues/${venueIdentifier}`), {
        headers: buildAuthorizationHeaders(authToken)
      });
      return response.data;
    } catch (error) {
      console.error('Error deleting venue:', error);
      throw error;
    }
  }
};

export default venueApi;

