import { apiUrl } from '@/shared/utils/apiBase.js';
import { AUTH_STORAGE_KEYS } from '@/modules/authentication/utils/authStorage.js';

function createLocalBackendToken() {
  try {
    const isLocalDev = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    const accountString = localStorage.getItem(AUTH_STORAGE_KEYS.account);
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
      status: response.status,
      errorType: result.errorType || result.type || '',
      error: result.errorMessage || result.message || 'Request failed.',
      data: result.data ?? null,
    };
  }
  return {
    success: true,
    data: result.data ?? result,
  };
}

async function sendWishlistRequest(path, { method = 'GET', token, payload } = {}) {
  try {
    const response = await fetch(apiUrl(path), {
      method,
      headers: buildHeaders(token, payload !== undefined),
      ...(payload !== undefined ? { body: JSON.stringify(payload) } : {}),
    });

    return parseResponse(response);
  } catch (error) {
    return { success: false, error: error.message };
  }
}

export const adminWishlistApi = {
  async getWishlistAccounts(token) {
    return sendWishlistRequest('/api/v1/users/wishlist', { token });
  },

  async verifyAccount(accountIdentifier, token, payload = {}) {
    return sendWishlistRequest(`/api/v1/users/${accountIdentifier}/approve`, { method: 'POST', token, payload });
  },

  async verifyEmailAndApproveAccount(accountIdentifier, token, payload = {}) {
    return sendWishlistRequest(`/api/v1/users/${accountIdentifier}/verify-email`, { method: 'POST', token, payload });
  },

  async denyAccount(accountIdentifier, token, payload = {}) {
    return sendWishlistRequest(`/api/v1/users/${accountIdentifier}/reject`, { method: 'POST', token, payload });
  },

  async deleteAccountRequest(accountIdentifier, token, payload = {}) {
    return sendWishlistRequest(`/api/v1/users/${accountIdentifier}/delete-request`, { method: 'DELETE', token, payload });
  },

  async createAdminAccount(accountPayload, token) {
    return sendWishlistRequest('/api/v1/users/wishlist/admin-accounts', { method: 'POST', token, payload: accountPayload });
  },

  async createUserAccount(accountPayload, token) {
    return sendWishlistRequest('/api/v1/users/wishlist/user-accounts', { method: 'POST', token, payload: accountPayload });
  },

  async createEmployeeAccount(accountPayload, token) {
    return sendWishlistRequest('/api/v1/users/wishlist/employee-accounts', { method: 'POST', token, payload: accountPayload });
  },
};
