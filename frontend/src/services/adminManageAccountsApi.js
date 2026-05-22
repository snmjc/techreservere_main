const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';
const AUTH_ACCOUNT_STORAGE_KEY = 'techreserve_auth_account';

function createLocalBackendToken() {
  try {
    const isLocalDev = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    const accountString = localStorage.getItem(AUTH_ACCOUNT_STORAGE_KEY);
    if (!accountString && !isLocalDev) return null;

    const account = accountString ? JSON.parse(accountString) : {};
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
      const response = await fetch(`${API_BASE_URL}/api/v1/accounts`, {
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
      const response = await fetch(`${API_BASE_URL}/api/v1/accounts/${accountIdentifier}/admin-details`, {
        method: 'PUT',
        headers: buildHeaders(token, true),
        body: JSON.stringify(accountPayload),
      });
      return parseResponse(response);
    } catch (error) {
      return { success: false, error: error.message };
    }
  },

  async updateAccountAccess(accountIdentifier, isActive, token) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/v1/accounts/${accountIdentifier}/access`, {
        method: 'PATCH',
        headers: buildHeaders(token, true),
        body: JSON.stringify({ isActive }),
      });
      return parseResponse(response);
    } catch (error) {
      return { success: false, error: error.message };
    }
  },

  async deleteAccount(accountIdentifier, confirmEmail, token) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/v1/accounts/${accountIdentifier}`, {
        method: 'DELETE',
        headers: buildHeaders(token, true),
        body: JSON.stringify({ confirmEmail }),
      });
      return parseResponse(response);
    } catch (error) {
      return { success: false, error: error.message };
    }
  },
};
