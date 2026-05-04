import axios from 'axios';

const API_BASE_URL = `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'}/api/v1`;

const equipmentApi = {
  async listEquipment() {
    try {
      const response = await axios.get(`${API_BASE_URL}/equipment`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('authToken')}`
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error listing equipment:', error);
      throw error;
    }
  },

  async getEquipmentById(equipmentIdentifier) {
    try {
      const response = await axios.get(`${API_BASE_URL}/equipment/${equipmentIdentifier}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('authToken')}`
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error getting equipment:', error);
      throw error;
    }
  }
};

export default equipmentApi;
