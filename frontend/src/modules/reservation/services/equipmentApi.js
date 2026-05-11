import axios from 'axios';

const API_BASE_URL = `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'}/api/v1`;

const equipmentApi = {
  async listEquipment() {
    const authToken = localStorage.getItem('techreserve_auth_token') || localStorage.getItem('authToken') || localStorage.getItem('clerkToken');
    try {
      const response = await axios.get(`${API_BASE_URL}/equipment`, {
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
      const response = await axios.get(`${API_BASE_URL}/equipment/${equipmentIdentifier}`, {
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
      const response = await axios.post(`${API_BASE_URL}/equipment`, equipmentData, {
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
      const response = await axios.put(`${API_BASE_URL}/equipment/${equipmentIdentifier}`, equipmentData, {
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
