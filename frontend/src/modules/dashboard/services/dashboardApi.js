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
      console.warn('Dashboard API endpoint not available, returning mock data');
      return {
        totalReservations: 0,
        pendingReservations: 0,
        approvedReservations: 0,
        rejectedReservations: 0,
        totalEquipment: 0,
        availableEquipment: 0,
        totalVenues: 0,
        availableVenues: 0
      };
    }
  }
};

export default dashboardApi;
