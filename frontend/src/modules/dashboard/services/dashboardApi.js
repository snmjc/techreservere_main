import axios from 'axios';
import { apiUrl } from '@/shared/utils/apiBase.js';
import { buildAuthorizationHeaders, getStoredAuthToken } from '@/shared/utils/authToken.js';

const dashboardApi = {
  async getDashboardSummary() {
    const authToken = getStoredAuthToken();
    const response = await axios.get(apiUrl('/api/v1/dashboard/summary'), {
      headers: buildAuthorizationHeaders(authToken)
    });

    return response.data;
  }
};

export default dashboardApi;
