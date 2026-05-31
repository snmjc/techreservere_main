// ===== AI GENERATED: authenticationService =====
// Purpose: HTTP service for authentication API calls
// Inputs: credentials object
// Returns: API response data

import { apiUrl } from '@/shared/utils/apiBase.js';

async function parseJsonResponse(response, invalidResponseMessage) {
  try {
    return await response.json();
  } catch (jsonError) {
    throw new Error(invalidResponseMessage);
  }
}

function normalizeLoginResponse(data) {
  if (data.success && data.data) {
    return data.data;
  }

  if (data.token && data.account) {
    return data;
  }

  throw new Error('Invalid response format from server.');
}

function buildLoginError(response, data) {
  const loginError = new Error(data.errorMessage || 'Login failed.');
  loginError.errorType = data.errorType || null;
  loginError.statusCode = response.status;
  return loginError;
}

function isNetworkFetchError(error) {
  return error instanceof TypeError && error.message === 'Failed to fetch';
}

/**
 * @function loginRequest
 * @description Sends login credentials to the backend API.
 * @param {Object} credentials
 * @param {string} credentials.emailAddress
 * @param {string} credentials.passwordText
 * @returns {Promise<Object>} Response with token and account data
 */
export async function loginRequest(credentials) {
  try {
    const response = await fetch(apiUrl('/api/v1/auth/login'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(credentials),
    });

    const data = await parseJsonResponse(response, 'Login API returned invalid response. Backend may not be ready.');

    if (!response.ok) {
      throw buildLoginError(response, data);
    }

    return normalizeLoginResponse(data);
  } catch (error) {
    if (isNetworkFetchError(error)) {
      throw new Error('Backend API is not reachable. Please make sure the TechReserve backend is running and the frontend tunnel is proxying /api requests.');
    }
    throw error;
  }
}

/**
 * @function registerRequest
 * @description Sends registration data to the backend API.
 * @param {Object} registrationData
 * @param {string} registrationData.firstName
 * @param {string} registrationData.lastName
 * @param {string} registrationData.emailAddress
 * @param {string} registrationData.passwordText
 * @param {File} registrationData.supportingDocument (optional)
 * @returns {Promise<Object>} Response with account data
 */
export async function registerRequest(registrationData) {
  try {
    const formData = new FormData();
    formData.append('firstName', registrationData.firstName);
    formData.append('lastName', registrationData.lastName);
    formData.append('emailAddress', registrationData.emailAddress);
    formData.append('passwordText', registrationData.passwordText);
    
    if (registrationData.supportingDocument) {
      formData.append('supportingDocument', registrationData.supportingDocument);
    }

    const response = await fetch(apiUrl('/api/v1/auth/register'), {
      method: 'POST',
      headers: {}, // Let browser set Content-Type for FormData
      body: formData,
    });

    const data = await parseJsonResponse(response, 'Registration API returned invalid response. Backend may not be ready.');

    if (!response.ok) {
      throw new Error(data.errorMessage || 'Registration failed.');
    }

    return data;
  } catch (error) {
    throw error;
  }
}
