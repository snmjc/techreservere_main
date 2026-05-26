import axios from 'axios';
import { apiUrl } from '@/shared/utils/apiBase.js';

const equipmentApi = {
  async listEquipment() {
    const authToken = localStorage.getItem('techreserve_auth_token') || localStorage.getItem('authToken') || localStorage.getItem('clerkToken');
    try {
      const response = await axios.get(apiUrl('/api/v1/equipment'), {
        headers: {
          'Authorization': `Bearer ${authToken}`
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error listing equipment:', error);
      throw error;
    }
  },

  async getEquipmentById(equipmentIdentifier) {
    const authToken = localStorage.getItem('techreserve_auth_token') || localStorage.getItem('authToken') || localStorage.getItem('clerkToken');
    try {
      const response = await axios.get(apiUrl(`/api/v1/equipment/${equipmentIdentifier}`), {
        headers: {
          'Authorization': `Bearer ${authToken}`
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error getting equipment:', error);
      throw error;
    }
  },

  async createEquipment(equipmentData) {
    const authToken = localStorage.getItem('techreserve_auth_token') || localStorage.getItem('authToken') || localStorage.getItem('clerkToken');
    try {
      const response = await axios.post(apiUrl('/api/v1/equipment'), equipmentData, {
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authToken}`
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error creating equipment:', error);
      throw error;
    }
  },

  async updateEquipment(equipmentIdentifier, equipmentData) {
    const authToken = localStorage.getItem('techreserve_auth_token') || localStorage.getItem('authToken') || localStorage.getItem('clerkToken');
    try {
      const response = await axios.put(apiUrl(`/api/v1/equipment/${equipmentIdentifier}`), equipmentData, {
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authToken}`
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error updating equipment:', error);
      throw error;
    }
  }
};

export default equipmentApi;
