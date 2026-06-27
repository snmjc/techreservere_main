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

  async getAnalyticsRangeResults(range) {
    const authToken = getStoredAuthToken();
    const response = await axios.get(apiUrl('/api/v1/analytics/range-results'), {
      headers: buildAuthorizationHeaders(authToken),
      params: {
        historyDays: range?.days || 30,
        startDate: range?.startDateIso,
        endDate: range?.endDateIso,
        _: Date.now(),
      },
    });

    return unwrapResponse(response);
  },

  async getAnalyticsRangeSectionResults(section, range) {
    const authToken = getStoredAuthToken();
    try {
      const response = await axios.get(apiUrl(`/api/v1/analytics/range-results/${encodeURIComponent(section)}`), {
        headers: buildAuthorizationHeaders(authToken),
        params: {
          historyDays: range?.days || 30,
          startDate: range?.startDateIso,
          endDate: range?.endDateIso,
          _: Date.now(),
        },
      });

      return unwrapResponse(response);
    } catch (error) {
      if (![404, 405].includes(Number(error?.response?.status))) {
        throw error;
      }

      return this.getAnalyticsRangeResults(range);
    }
  },

  async triggerAnalyticsRun(scenario, range) {
    const authToken = getStoredAuthToken();
    const response = await axios.post(apiUrl('/api/v1/analytics/trigger-run'), {
      scenario,
      historyDays: range?.days || 30,
      startDate: range?.startDateIso,
      endDate: range?.endDateIso,
    }, {
      headers: buildAuthorizationHeaders(authToken),
    });

    return unwrapResponse(response);
  },

  async refreshDailyAnalytics(range) {
    return this.triggerAnalyticsRun('manual', range);
  },

  async listAnalyticsModelArtifacts() {
    const authToken = getStoredAuthToken();
    const response = await axios.get(apiUrl('/api/v1/analytics/model-artifacts'), {
      headers: buildAuthorizationHeaders(authToken),
      params: {
        _: Date.now(),
      },
    });

    return unwrapResponse(response);
  },

  async trainAnalyticsModels({ setName, activate = true } = {}) {
    const authToken = getStoredAuthToken();
    const response = await axios.post(apiUrl('/api/v1/analytics/train-models'), {
      setName,
      activate,
    }, {
      headers: buildAuthorizationHeaders(authToken),
    });

    return unwrapResponse(response);
  },

  async activateAnalyticsModelSet(setName) {
    const authToken = getStoredAuthToken();
    const response = await axios.post(apiUrl('/api/v1/analytics/model-artifacts/activate'), {
      setName,
    }, {
      headers: buildAuthorizationHeaders(authToken),
    });

    return unwrapResponse(response);
  },

  async activateAnalyticsModelArtifact(setName, artifact) {
    const authToken = getStoredAuthToken();
    const response = await axios.post(apiUrl('/api/v1/analytics/model-artifacts/activate-artifact'), {
      setName,
      artifact,
    }, {
      headers: buildAuthorizationHeaders(authToken),
    });

    return unwrapResponse(response);
  },

  async renameAnalyticsModelSet(setName, newName) {
    const authToken = getStoredAuthToken();
    const response = await axios.patch(apiUrl(`/api/v1/analytics/model-artifacts/${encodeURIComponent(setName)}`), {
      newName,
    }, {
      headers: buildAuthorizationHeaders(authToken),
    });

    return unwrapResponse(response);
  },

  async deleteAnalyticsModelSet(setName) {
    const authToken = getStoredAuthToken();
    const response = await axios.delete(apiUrl(`/api/v1/analytics/model-artifacts/${encodeURIComponent(setName)}`), {
      headers: buildAuthorizationHeaders(authToken),
    });

    return unwrapResponse(response);
  },
};

export default adminAnalyticsApi;
