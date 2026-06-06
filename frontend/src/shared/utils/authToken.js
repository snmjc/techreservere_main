import { readStoredToken } from '@/modules/authentication/utils/authStorage.js';

const LEGACY_TOKEN_KEY = 'authToken';
const CLERK_TOKEN_KEY = 'clerkToken';

export function getStoredAuthToken({ includeClerkToken = true } = {}) {
  return readStoredToken()
    || localStorage.getItem(LEGACY_TOKEN_KEY)
    || sessionStorage.getItem(LEGACY_TOKEN_KEY)
    || (includeClerkToken
      ? (localStorage.getItem(CLERK_TOKEN_KEY) || sessionStorage.getItem(CLERK_TOKEN_KEY))
      : null);
}

export function buildAuthorizationHeaders(token = getStoredAuthToken()) {
  return {
    Authorization: `Bearer ${token}`,
  };
}

export function buildJsonAuthorizationHeaders(token = getStoredAuthToken()) {
  return {
    'Content-Type': 'application/json',
    ...buildAuthorizationHeaders(token),
  };
}
