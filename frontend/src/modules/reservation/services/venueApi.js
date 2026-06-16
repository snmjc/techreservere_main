import axios from 'axios';
import { apiUrl } from '@/shared/utils/apiBase.js';
import {
  buildAuthorizationHeaders,
  buildJsonAuthorizationHeaders,
  getStoredAuthToken,
} from '@/shared/utils/authToken.js';

const venueApi = {
  async listVenues(options = {}) {
    const authToken = getStoredAuthToken();
    try {
      const response = await axios.get(apiUrl('/api/v1/venues'), {
        headers: buildAuthorizationHeaders(authToken),
        params: buildVenueListQueryParams(options),
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
      const response = await axios.post(apiUrl('/api/v1/venues'), buildVenueMutationPayload(venueData), {
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
      const response = await axios.put(apiUrl(`/api/v1/venues/${venueIdentifier}`), buildVenueMutationPayload(venueData), {
        headers: buildJsonAuthorizationHeaders(authToken)
      });
      return response.data;
    } catch (error) {
      console.error('Error updating venue:', error);
      throw error;
    }
  },

  async deleteVenue(venueIdentifier, confirmationPayload = {}) {
    const authToken = getStoredAuthToken();
    try {
      const response = await axios.delete(apiUrl(`/api/v1/venues/${venueIdentifier}`), {
        headers: buildJsonAuthorizationHeaders(authToken),
        data: confirmationPayload
      });
      return response.data;
    } catch (error) {
      console.error('Error deleting venue:', error);
      throw error;
    }
  }
};

function buildVenueListQueryParams(options) {
  const params = {};

  if (options.selectedDate) {
    params.selectedDate = options.selectedDate;
  }

  if (options.startTime) {
    params.startTime = options.startTime;
  }

  if (options.endTime) {
    params.endTime = options.endTime;
  }

  return params;
}

function buildVenueMutationPayload(venueData = {}) {
  return {
    venueName: String(venueData.venueName || '').trim(),
    venueLocation: String(venueData.venueLocation || '').trim(),
    floorLevel: String(venueData.floorLevel || '').trim(),
    capacityLimit: Number(venueData.capacityLimit ?? 0),
    availabilityDate: String(venueData.availabilityDate || '').trim(),
    operationalStatus: String(venueData.operationalStatus || '').trim(),
    description: String(venueData.description || '').trim(),
    imageUrl: normalizeOptionalImageUrl(venueData.imageUrl),
  };
}

function normalizeOptionalImageUrl(value) {
  const normalizedValue = String(value || '').trim();
  return normalizedValue === '' ? null : normalizedValue;
}

export default venueApi;

