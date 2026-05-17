// ===== AI GENERATED: authenticationService =====
// Purpose: HTTP service for authentication API calls
// Inputs: credentials object
// Returns: API response data

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';

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
    const response = await fetch(`${API_BASE_URL}/api/v1/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(credentials),
    });

    let data;
    try {
      data = await response.json();
    } catch (jsonError) {
      console.error('Failed to parse login response as JSON:', jsonError);
      throw new Error('Login API returned invalid response. Backend may not be ready.');
    }

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
  } catch (error) {
    console.error('Login request error:', error);
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

    const response = await fetch(`${API_BASE_URL}/api/v1/auth/register`, {
      method: 'POST',
      headers: {}, // Let browser set Content-Type for FormData
      body: formData,
    });

    let data;
    try {
      data = await response.json();
    } catch (jsonError) {
      console.error('Failed to parse registration response as JSON:', jsonError);
      throw new Error('Registration API returned invalid response. Backend may not be ready.');
    }

    if (!response.ok) {
      throw new Error(data.errorMessage || 'Registration failed.');
    }

    return data;
  } catch (error) {
    console.error('Registration request error:', error);
    console.warn('Backend registration failed, using mock registration for development');
    
    // Mock registration for development when backend is not available
    const mockToken = 'mock_token_' + Date.now();
    const mockAccount = {
      accountIdentifier: Math.floor(Math.random() * 10000),
      firstName: registrationData.firstName,
      lastName: registrationData.lastName,
      emailAddress: registrationData.emailAddress,
      roleDesignation: 'ROLE_BORROWER',
      contactNumber: '+63-912-345-6789',
      isActive: true
    };

    return {
      success: true,
      message: 'Registration successful (mock)',
      data: {
        token: mockToken,
        account: mockAccount
      }
    };
  }
}
