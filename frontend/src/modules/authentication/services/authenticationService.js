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

  // Handle different response structures
  // Backend returns: { success: true, data: { token, account } }
  if (data.success && data.data) {
    return data.data;
  }

  // Fallback for direct structure: { token, account }
  if (data.token && data.account) {
    return data;
  }

  throw new Error('Invalid response format from server.');
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
  const formData = new FormData();
  formData.append('firstName', registrationData.firstName);
  formData.append('lastName', registrationData.lastName);
  formData.append('emailAddress', registrationData.emailAddress);
  formData.append('passwordText', registrationData.passwordText);
  
  if (registrationData.supportingDocument) {
    formData.append('supportingDocument', registrationData.supportingDocument);
  }

  const response = await fetch('/api/v1/auth/register', {
    method: 'POST',
    headers: {}, // Let browser set Content-Type for FormData
    body: formData,
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.errorMessage || 'Registration failed.');
  }

  return data;
}
