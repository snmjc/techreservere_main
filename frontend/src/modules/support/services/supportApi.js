import axios from 'axios';
import { apiUrl } from '@/shared/utils/apiBase.js';
import {
  buildAuthorizationHeaders,
  buildJsonAuthorizationHeaders,
  resolveAuthToken,
} from '@/shared/utils/authToken.js';

async function getHeaders(json = false) {
  const authToken = await resolveAuthToken();
  return json ? buildJsonAuthorizationHeaders(authToken) : buildAuthorizationHeaders(authToken);
}

export const supportApi = {
  async listFeedback() {
    const response = await axios.get(apiUrl('/api/v1/support/feedback'), {
      headers: await getHeaders(false),
    });
    return response.data;
  },

  async createFeedback(payload) {
    const response = await axios.post(apiUrl('/api/v1/support/feedback'), payload, {
      headers: await getHeaders(true),
    });
    return response.data;
  },

  async updateFeedbackStatus(feedbackIdentifier, payload) {
    const response = await axios.patch(apiUrl(`/api/v1/support/feedback/${feedbackIdentifier}/status`), payload, {
      headers: await getHeaders(true),
    });
    return response.data;
  },

  async listDamageReports() {
    const response = await axios.get(apiUrl('/api/v1/support/damage-reports'), {
      headers: await getHeaders(false),
    });
    return response.data;
  },

  async createDamageReport(payload) {
    const response = await axios.post(apiUrl('/api/v1/support/damage-reports'), payload, {
      headers: await getHeaders(true),
    });
    return response.data;
  },

  async updateDamageReportStatus(damageReportIdentifier, payload) {
    const response = await axios.patch(apiUrl(`/api/v1/support/damage-reports/${damageReportIdentifier}/status`), payload, {
      headers: await getHeaders(true),
    });
    return response.data;
  },
};
