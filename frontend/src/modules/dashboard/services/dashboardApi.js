import axios from 'axios';
import { apiUrl } from '@/shared/utils/apiBase.js';

const dashboardApi = {
  async getDashboardSummary() {
    try {
      const authToken =
        localStorage.getItem('techreserve_auth_token') ||
        localStorage.getItem('authToken') ||
        localStorage.getItem('clerkToken');
      const response = await axios.get(apiUrl('/api/v1/dashboard/summary'), {
        headers: {
          'Authorization': `Bearer ${authToken}`
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
