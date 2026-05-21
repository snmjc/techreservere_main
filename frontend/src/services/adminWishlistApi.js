const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';
const AUTH_ACCOUNT_STORAGE_KEY = 'techreserve_auth_account';

function createLocalBackendToken() {
  try {
    const isLocalDev = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    const accountString = localStorage.getItem(AUTH_ACCOUNT_STORAGE_KEY);
    if (!accountString && !isLocalDev) return null;

    const account = accountString ? JSON.parse(accountString) : {};
    const role = String(account?.roleDesignation ?? account?.role ?? '').toUpperCase();
    const accountIdentifier = account?.accountIdentifier || 1;

    if (!isLocalDev && (!accountIdentifier || (role !== 'ROLE_ADMIN' && role !== 'ADMIN'))) {
      return null;
    }

    return btoa(JSON.stringify({
      accountId: accountIdentifier,
      email: account.emailAddress,
      role: 'ROLE_ADMIN',
      exp: Math.floor(Date.now() / 1000) + 86400,
    }));
  } catch (error) {
    console.warn('Unable to create local backend auth token:', error);
    return null;
  }
}

function resolveBearerToken(token) {
  const isLocalDev = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
  const localBackendToken = createLocalBackendToken();

  if (isLocalDev && localBackendToken) {
    return localBackendToken;
  }

  if (token && !String(token).startsWith('mock_token_')) {
    return token;
  }

  return localBackendToken;
}

function buildHeaders(token, includeJson = false) {
  const headers = {};
  const bearerToken = resolveBearerToken(token);
  if (includeJson) headers['Content-Type'] = 'application/json';
  if (bearerToken) headers.Authorization = `Bearer ${bearerToken}`;
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

export const adminWishlistApi = {
  async getWishlistAccounts(token) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/v1/users/wishlist`, {
        method: 'GET',
        headers: buildHeaders(token),
      });
      return parseResponse(response);
    } catch (error) {
      return { success: false, error: error.message };
    }
  },

  async verifyAccount(accountIdentifier, token) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/v1/users/${accountIdentifier}/approve`, {
        method: 'POST',
        headers: buildHeaders(token, true),
      });
      return parseResponse(response);
    } catch (error) {
      return { success: false, error: error.message };
    }
  },

  async denyAccount(accountIdentifier, token) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/v1/users/${accountIdentifier}/reject`, {
        method: 'POST',
        headers: buildHeaders(token, true),
      });
      return parseResponse(response);
    } catch (error) {
      return { success: false, error: error.message };
    }
  },

  async createAdminAccount(accountPayload, token) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/v1/users/wishlist/admin-accounts`, {
        method: 'POST',
        headers: buildHeaders(token, true),
        body: JSON.stringify(accountPayload),
      });
      return parseResponse(response);
    } catch (error) {
      return { success: false, error: error.message };
    }
  },
};
