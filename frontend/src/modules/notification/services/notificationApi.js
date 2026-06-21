import axios from 'axios';
import { apiUrl } from '@/shared/utils/apiBase.js';
import {
  buildAuthorizationHeaders,
  getStoredAuthToken,
} from '@/shared/utils/authToken.js';

function normalizeNotificationPayload(responseData) {
  if (Array.isArray(responseData?.data?.notifications)) {
    return responseData.data.notifications;
  }

  if (Array.isArray(responseData?.notifications)) {
    return responseData.notifications;
  }

  if (Array.isArray(responseData?.data)) {
    return responseData.data;
  }

  return [];
}

export const notificationApi = {
  async listNotifications() {
    const authToken = getStoredAuthToken();
    if (!authToken) {
      return [];
    }

    const response = await axios.get(apiUrl('/api/v1/notifications'), {
      headers: buildAuthorizationHeaders(authToken),
    });

    return normalizeNotificationPayload(response.data);
  },

  async markAsRead(notificationIdentifier) {
    const authToken = getStoredAuthToken();
    if (!authToken) {
      return;
    }

    await axios.put(
      apiUrl(`/api/v1/notifications/${notificationIdentifier}/read`),
      {},
      { headers: buildAuthorizationHeaders(authToken) },
    );
  },
};
