import { apiUrl } from '@/shared/utils/apiBase.js';
import { AUTH_STORAGE_KEYS, readStoredJson } from '@/modules/authentication/utils/authStorage.js';

function createLocalBackendToken() {
  try {
    const isLocalDev = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    const account = readStoredJson(AUTH_STORAGE_KEYS.account) || {};
    if (!account.accountIdentifier && !isLocalDev) return null;
    return btoa(JSON.stringify({
      accountId: account?.accountIdentifier || 1,
      email: account?.emailAddress,
      role: 'ROLE_ADMIN',
      exp: Math.floor(Date.now() / 1000) + 86400,
    }));
  } catch (error) {
    console.warn('Unable to create local backend auth token:', error);
    return null;
  }
}

function buildHeaders(token, includeJson = false) {
  const headers = {};
  const localBackendToken = createLocalBackendToken();
  if (includeJson) headers['Content-Type'] = 'application/json';
  if (localBackendToken || token) headers.Authorization = `Bearer ${localBackendToken || token}`;
  return headers;
}

async function parseResponse(response) {
  const result = await response.json().catch(() => ({}));
  if (!response.ok) {
    return {
      success: false,
      error: result.errorMessage || result.message || 'Request failed.',
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
