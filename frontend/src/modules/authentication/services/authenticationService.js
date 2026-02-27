// ===== AI GENERATED: authenticationService =====
// Purpose: HTTP service for authentication API calls
// Inputs: credentials object
// Returns: API response data

/**
 * @function loginRequest
 * @description Sends login credentials to the backend API.
 * @param {Object} credentials
 * @param {string} credentials.emailAddress
 * @param {string} credentials.passwordText
 * @returns {Promise<Object>} Response with token and account data
 */
export async function loginRequest(credentials) {
  const response = await fetch('/api/v1/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(credentials),
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.errorMessage || 'Login failed.');
  }

  return data;
}

/**
 * @function registerRequest
 * @description Sends registration data to the backend API.
 * @param {Object} registrationData
 * @param {string} registrationData.firstName
 * @param {string} registrationData.lastName
 * @param {string} registrationData.emailAddress
 * @param {string} registrationData.passwordText
 * @returns {Promise<Object>} Response with account data
 */
export async function registerRequest(registrationData) {
  const response = await fetch('/api/v1/auth/register', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(registrationData),
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.errorMessage || 'Registration failed.');
  }

  return data;
}
