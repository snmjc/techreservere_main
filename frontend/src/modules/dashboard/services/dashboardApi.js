import axios from 'axios';

const API_BASE_URL = `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'}/api/v1`;

const dashboardApi = {
  async getDashboardSummary() {
    try {
      const response = await axios.get(`${API_BASE_URL}/dashboard/summary`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('authToken')}`
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error fetching dashboard summary:', error);
      throw error;
    }
  }
};

export default dashboardApi;
