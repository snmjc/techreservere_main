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
      _: Date.now(),
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

  async getAnalyticsConfiguration() {
    const authToken = getStoredAuthToken();
    const response = await axios.get(apiUrl('/api/v1/analytics/configuration'), {
      headers: buildAuthorizationHeaders(authToken),
    });

    return unwrapResponse(response);
  },

  async saveAnalyticsConfiguration(configuration) {
    const authToken = getStoredAuthToken();
    const response = await axios.patch(apiUrl('/api/v1/analytics/configuration'), {
      configuration,
    }, {
      headers: buildAuthorizationHeaders(authToken),
    });

    return unwrapResponse(response);
  },

  async getLatestAnalyticsResults() {
    const authToken = getStoredAuthToken();
    const response = await axios.get(apiUrl('/api/v1/analytics/latest-results'), {
      headers: buildAuthorizationHeaders(authToken),
    });

    return unwrapResponse(response);
  },

  async triggerAnalyticsRun(scenario) {
    const authToken = getStoredAuthToken();
    const response = await axios.post(apiUrl('/api/v1/analytics/trigger-run'), {
      scenario,
    }, {
      headers: buildAuthorizationHeaders(authToken),
    });

    return unwrapResponse(response);
  },
};

export default adminAnalyticsApi;
