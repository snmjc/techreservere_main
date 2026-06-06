import axios from 'axios';
import { apiUrl } from '@/shared/utils/apiBase.js';
import {
  buildAuthorizationHeaders,
  buildJsonAuthorizationHeaders,
  getStoredAuthToken,
} from '@/shared/utils/authToken.js';

const equipmentApi = {
  async listEquipment() {
    const authToken = getStoredAuthToken();
    try {
      const response = await axios.get(apiUrl('/api/v1/equipment'), {
        headers: buildAuthorizationHeaders(authToken)
      });
      return response.data;
    } catch (error) {
      console.error('Error listing equipment:', error);
      throw error;
    }
  },

  async getEquipmentById(equipmentIdentifier) {
    const authToken = getStoredAuthToken();
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
    const authToken = getStoredAuthToken();
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
    const authToken = getStoredAuthToken();
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
    const authToken = getStoredAuthToken();
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
  }
};

export default equipmentApi;
