import { apiUrl } from '@/shared/utils/apiBase.js';
import { buildAuthorizationHeaders, resolveAuthToken } from '@/shared/utils/authToken.js';

function buildHeaders(authToken, includeJson = false) {
  return {
    ...(includeJson ? { 'Content-Type': 'application/json' } : {}),
    ...buildAuthorizationHeaders(authToken),
  };
}

async function requestJson(path, authToken, options = {}) {
  try {
    const liveAuthToken = authToken || await resolveAuthToken();
    const response = await fetch(apiUrl(path), {
      method: options.method || 'GET',
      headers: buildHeaders(liveAuthToken, Boolean(options.body)),
      body: options.body,
    });

    const result = await response.json().catch(() => ({}));

    if (!response.ok) {
      return {
        success: false,
        error: result.errorMessage || result.message || 'Request failed.',
      };
    }

    return {
      success: true,
      data: result.data || result,
    };
  } catch (error) {
    return {
      success: false,
      error: error?.message || 'Request failed.',
    };
  }
}

export const taskWorkflowApi = {
  async fetchTasksByReservation(reservationIdentifier, authToken) {
    return requestJson(`/api/v1/tasks/reservation/${reservationIdentifier}`, authToken);
  },

  async createTask(taskPayload, authToken) {
    return requestJson('/api/v1/tasks', authToken, {
      method: 'POST',
      body: JSON.stringify(taskPayload),
    });
  },

  async updateTask(taskIdentifier, taskPayload, authToken) {
    return requestJson(`/api/v1/tasks/${taskIdentifier}`, authToken, {
      method: 'PUT',
      body: JSON.stringify(taskPayload),
    });
  },

  async fetchAccounts(authToken) {
    return requestJson('/api/v1/accounts', authToken);
  },
};
