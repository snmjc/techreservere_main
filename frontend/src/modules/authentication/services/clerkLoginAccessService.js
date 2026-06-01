import { apiUrl } from '@/shared/utils/apiBase.js';

export async function verifyClerkLoginAccess(emailAddress) {
  try {
    const response = await fetch(apiUrl('/api/v1/auth/clerk-login-preflight'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ emailAddress }),
    });
    const result = await response.json().catch(() => ({}));

    if (!response.ok) {
      return {
        success: false,
        errorType: result.errorType || result.type || '',
        error: result.errorMessage || result.message || 'Please wait for an administrator invitation before signing in.',
      };
    }

    return { success: true, data: result.data ?? result };
  } catch (error) {
    return {
      success: false,
      error: error?.message || 'Unable to verify invitation status. Please try again.',
    };
  }
}
