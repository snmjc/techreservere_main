import axios from 'axios';
import { apiUrl } from '@/shared/utils/apiBase.js';
import { buildAuthorizationHeaders, getStoredAuthToken } from '@/shared/utils/authToken.js';

function unwrapResponse(response) {
  return response?.data?.data || response?.data || {};
}

async function getWithRange(path, range) {
  const authToken = getStoredAuthToken();
  const response = await axios.get(apiUrl(path), {
    headers: buildAuthorizationHeaders(authToken),
    params: {
      startDate: range?.startDateIso,
      endDate: range?.endDateIso,
    },
  });

  return unwrapResponse(response);
}

const adminAnalyticsApi = {
  async getDashboardOverview(range) {
    return getWithRange('/api/v1/dashboard/overview', range);
  },

  async getReportsAnalytics(range) {
    return getWithRange('/api/v1/reports-analytics', range);
  },
};

export default adminAnalyticsApi;
