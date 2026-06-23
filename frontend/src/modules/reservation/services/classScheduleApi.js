import axios from 'axios';
import { apiUrl } from '@/shared/utils/apiBase.js';
import {
  buildAuthorizationHeaders,
  buildJsonAuthorizationHeaders,
  resolveAuthToken,
} from '@/shared/utils/authToken.js';

const classScheduleApi = {
  async listScheduleBlocks(filters = {}) {
    const authToken = await resolveAuthToken();
    const params = {};

    if (filters.venueIdentifier) {
      params.venueIdentifier = filters.venueIdentifier;
    }

    if (filters.dateFrom) {
      params.dateFrom = filters.dateFrom;
    }

    if (filters.dateTo) {
      params.dateTo = filters.dateTo;
    }

    const response = await axios.get(apiUrl('/api/v1/reservation-policy/class-schedules'), {
      params,
      headers: buildAuthorizationHeaders(authToken),
    });

    return response.data;
  },

  async createScheduleBlock(payload) {
    const authToken = await resolveAuthToken();
    const response = await axios.post(
      apiUrl('/api/v1/reservation-policy/class-schedules'),
      payload,
      { headers: buildJsonAuthorizationHeaders(authToken) },
    );

    return response.data;
  },

  async updateScheduleBlock(scheduleBlockIdentifier, payload) {
    const authToken = await resolveAuthToken();
    const response = await axios.put(
      apiUrl(`/api/v1/reservation-policy/class-schedules/${scheduleBlockIdentifier}`),
      payload,
      { headers: buildJsonAuthorizationHeaders(authToken) },
    );

    return response.data;
  },

  async deleteScheduleBlock(scheduleBlockIdentifier) {
    const authToken = await resolveAuthToken();
    const response = await axios.delete(
      apiUrl(`/api/v1/reservation-policy/class-schedules/${scheduleBlockIdentifier}`),
      { headers: buildAuthorizationHeaders(authToken) },
    );

    return response.data;
  },
};

export default classScheduleApi;
