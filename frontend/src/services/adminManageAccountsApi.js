import { apiUrl } from '@/shared/utils/apiBase.js';
import { getStoredAuthToken, normalizeAuthToken } from '@/shared/utils/authToken.js';

function buildHeaders(token, includeJson = false) {
  const headers = {};
  const bearerToken = normalizeAuthToken(token) || getStoredAuthToken();
  if (includeJson) headers['Content-Type'] = 'application/json';
  if (bearerToken) headers.Authorization = `Bearer ${bearerToken}`;
  return headers;
}

async function parseResponse(response) {
  const rawText = await response.text();
  let result = {};
  try {
    result = rawText ? JSON.parse(rawText) : {};
  } catch {
    result = {};
  }
  if (!response.ok) {
    const fallbackError = rawText
      ? `Request failed with HTTP ${response.status}: ${rawText.slice(0, 180)}`
      : `Request failed with HTTP ${response.status}.`;

    return {
      success: false,
      error: result.errorMessage || result.message || fallbackError,
    };
  }
  return {
    success: true,
    data: result.data ?? result,
  };
}

export const adminManageAccountsApi = {
  async getAccounts(token) {
    try {
      const response = await fetch(apiUrl('/api/v1/accounts'), {
        method: 'GET',
        headers: buildHeaders(token),
      });
      return parseResponse(response);
    } catch (error) {
      return { success: false, error: error.message };
    }
  },

  async getAccountById(accountIdentifier, token) {
    try {
      const response = await fetch(apiUrl(`/api/v1/accounts/${accountIdentifier}`), {
        method: 'GET',
        headers: buildHeaders(token),
      });
      return parseResponse(response);
    } catch (error) {
      return { success: false, error: error.message };
    }
  },

  async updateAccount(accountIdentifier, accountPayload, token) {
    try {
      const response = await fetch(apiUrl(`/api/v1/accounts/${accountIdentifier}/admin-details`), {
        method: 'PUT',
        headers: buildHeaders(token, true),
        body: JSON.stringify(accountPayload),
      });
      return parseResponse(response);
    } catch (error) {
      return { success: false, error: error.message };
    }
  },

  async getEmployeeWorkLogs(accountIdentifier, token) {
    try {
      const response = await fetch(apiUrl(`/api/v1/accounts/${accountIdentifier}/work-logs`), {
        method: 'GET',
        headers: buildHeaders(token),
      });
      return parseResponse(response);
    } catch (error) {
      return { success: false, error: error.message };
    }
  },

  async updateAccountAccess(accountIdentifier, isActive, token, payload = {}) {
    try {
      const response = await fetch(apiUrl(`/api/v1/accounts/${accountIdentifier}/access`), {
        method: 'PATCH',
        headers: buildHeaders(token, true),
        body: JSON.stringify({ isActive, ...payload }),
      });
      return parseResponse(response);
    } catch (error) {
      return { success: false, error: error.message };
    }
  },

  async deleteAccount(accountIdentifier, confirmationPayload, token) {
    try {
      const response = await fetch(apiUrl(`/api/v1/accounts/${accountIdentifier}`), {
        method: 'DELETE',
        headers: buildHeaders(token, true),
        body: JSON.stringify(confirmationPayload),
      });
      return parseResponse(response);
    } catch (error) {
      return { success: false, error: error.message };
    }
  },
};
