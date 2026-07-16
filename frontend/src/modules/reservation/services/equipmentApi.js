import axios from 'axios';
import { apiUrl } from '@/shared/utils/apiBase.js';
import {
  buildAuthorizationHeaders,
  buildJsonAuthorizationHeaders,
  resolveAuthToken,
} from '@/shared/utils/authToken.js';

const equipmentApi = {
  async listEquipment(filters = {}) {
    const authToken = await resolveAuthToken();
    try {
      const response = await axios.get(apiUrl('/api/v1/equipment'), {
        headers: buildAuthorizationHeaders(authToken),
        params: filters,
      });
      return response.data;
    } catch (error) {
      console.error('Error listing equipment:', error);
      throw error;
    }
  },

  async getEquipmentById(equipmentIdentifier) {
    const authToken = await resolveAuthToken();
    try {
      const response = await axios.get(apiUrl(`/api/v1/equipment/${equipmentIdentifier}`), {
        headers: buildAuthorizationHeaders(authToken)
      });
      return response.data;
    } catch (error) {
      console.error('Error getting equipment:', error);
      throw error;
    }
  },

  async createEquipment(equipmentData) {
    const authToken = await resolveAuthToken();
    try {
      const response = await axios.post(apiUrl('/api/v1/equipment'), equipmentData, {
        headers: buildJsonAuthorizationHeaders(authToken)
      });
      return response.data;
    } catch (error) {
      console.error('Error creating equipment:', error);
      throw error;
    }
  },

  async updateEquipment(equipmentIdentifier, equipmentData) {
    const authToken = await resolveAuthToken();
    try {
      const response = await axios.put(apiUrl(`/api/v1/equipment/${equipmentIdentifier}`), equipmentData, {
        headers: buildJsonAuthorizationHeaders(authToken)
      });
      return response.data;
    } catch (error) {
      console.error('Error updating equipment:', error);
      throw error;
    }
  },

  async deleteEquipment(equipmentIdentifier, confirmationData) {
    const authToken = await resolveAuthToken();
    try {
      const response = await axios.delete(apiUrl(`/api/v1/equipment/${equipmentIdentifier}`), {
        headers: buildJsonAuthorizationHeaders(authToken),
        data: confirmationData,
      });
      return response.data;
    } catch (error) {
      console.error('Error deleting equipment:', error);
      throw error;
    }
  },

  async exportEquipmentExcel(filters = {}) {
    const authToken = await resolveAuthToken();
    try {
      const response = await axios.get(apiUrl('/api/v1/equipment/export/excel'), {
        headers: buildAuthorizationHeaders(authToken),
        params: filters,
        responseType: 'blob',
      });
      return response;
    } catch (error) {
      if (error?.response?.data instanceof Blob) {
        try {
          const errorPayload = JSON.parse(await error.response.data.text());
          error.response.data = errorPayload;
        } catch {
          // Keep the original blob when the server did not return JSON.
        }
      }
      console.error('Error exporting equipment excel:', error);
      throw error;
    }
  }
};

export default equipmentApi;
