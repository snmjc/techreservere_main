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
  const rawText = await response.text().catch(() => '');
  let result = {};

  if (rawText !== '') {
    try {
      result = JSON.parse(rawText);
    } catch {
      result = {};
    }
  }

  if (!response.ok) {
    const fallbackError = rawText
      ? `Request failed with HTTP ${response.status}: ${rawText.slice(0, 180)}`
      : `Request failed with HTTP ${response.status}.`;

    return {
      success: false,
      status: response.status,
      errorType: result.errorType || result.type || '',
      error: result.errorMessage || result.message || fallbackError,
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

async function fetchWishlistBlob(path, token) {
  try {
    const response = await fetch(apiUrl(path), {
      method: 'GET',
      headers: buildHeaders(token, false),
    });

    if (!response.ok) {
      const result = await response.json().catch(() => ({}));
      return {
        success: false,
        status: response.status,
        error: result.errorMessage || result.message || 'Unable to load the supporting document.',
      };
    }

    return {
      success: true,
      data: {
        blob: await response.blob(),
        mimeType: response.headers.get('Content-Type') || 'application/octet-stream',
      },
    };
  } catch (error) {
    return { success: false, error: error.message || 'Unable to load the supporting document.' };
  }
}

export const adminWishlistApi = {
  async getWishlistAccounts(token) {
    return sendWishlistRequest('/api/v1/users/wishlist', { token });
  },

  async sendInvite(accountIdentifier, token, payload = {}) {
    return sendWishlistRequest(`/api/v1/users/${accountIdentifier}/approve`, { method: 'POST', token, payload });
  },

  async resendInvite(accountIdentifier, token, payload = {}) {
    return sendWishlistRequest(`/api/v1/users/${accountIdentifier}/approve`, { method: 'POST', token, payload });
  },

  async denyAccount(accountIdentifier, token, payload = {}) {
    return sendWishlistRequest(`/api/v1/users/${accountIdentifier}/reject`, { method: 'POST', token, payload });
  },

  async deleteAccountRequest(accountIdentifier, token, payload = {}) {
    return sendWishlistRequest(`/api/v1/users/${accountIdentifier}/delete-request`, { method: 'DELETE', token, payload });
  },

  async getSupportingDocumentBlob(accountIdentifier, token) {
    return fetchWishlistBlob(`/api/v1/users/${accountIdentifier}/supporting-document`, token);
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
