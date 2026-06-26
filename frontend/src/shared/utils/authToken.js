import {
  readStoredToken,
  removeStoredToken,
  writeStoredToken,
} from '@/modules/authentication/utils/authStorage.js';

const LEGACY_TOKEN_KEY = 'authToken';

function decodeBase64Json(value, useUrlSafeAlphabet = false) {
  try {
    const normalizedValue = useUrlSafeAlphabet
      ? value.replace(/-/g, '+').replace(/_/g, '/')
      : value;
    const paddedValue = normalizedValue + '='.repeat((4 - normalizedValue.length % 4) % 4);
    return JSON.parse(atob(paddedValue));
  } catch {
    return null;
  }
}

function isExpiredTokenPayload(payload) {
  if (!payload || typeof payload !== 'object') return true;
  if (!Number.isFinite(payload.exp)) return false;
  return payload.exp <= Math.floor(Date.now() / 1000);
}

export function normalizeAuthToken(token) {
  if (typeof token !== 'string') return null;

  const trimmedToken = token.trim();
  if (!trimmedToken || trimmedToken === 'null' || trimmedToken === 'undefined') {
    return null;
  }

  const jwtParts = trimmedToken.split('.');
  if (jwtParts.length === 3) {
    const payload = decodeBase64Json(jwtParts[1], true);
    if (!payload?.sub || isExpiredTokenPayload(payload)) {
      return null;
    }
    return trimmedToken;
  }

  const localPayload = decodeBase64Json(trimmedToken);
  if (!localPayload?.accountId || isExpiredTokenPayload(localPayload)) {
    return null;
  }

  return trimmedToken;
}

export function getStoredAuthToken() {
  const storedToken = normalizeAuthToken(readStoredToken());
  if (storedToken) {
    return storedToken;
  }

  const legacyToken = normalizeAuthToken(localStorage.getItem(LEGACY_TOKEN_KEY))
    || normalizeAuthToken(sessionStorage.getItem(LEGACY_TOKEN_KEY));

  if (legacyToken) {
    return legacyToken;
  }

  removeStoredToken();
  localStorage.removeItem(LEGACY_TOKEN_KEY);
  sessionStorage.removeItem(LEGACY_TOKEN_KEY);
  return null;
}

export async function resolveAuthToken() {
  const storedToken = getStoredAuthToken();
  if (storedToken) {
    return storedToken;
  }

  return refreshAuthToken();
}

export async function refreshAuthToken() {
  const clerkSession = window?.Clerk?.session;
  if (!clerkSession || typeof clerkSession.getToken !== 'function') {
    removeStoredToken();
    return null;
  }

  try {
    const clerkToken = normalizeAuthToken(await clerkSession.getToken());
    if (!clerkToken) {
      removeStoredToken();
      return null;
    }

    // Mirror the live Clerk token into session storage so immediate follow-up
    // API calls work even if the Pinia auth state is already present.
    writeStoredToken(clerkToken, false);
    return clerkToken;
  } catch {
    removeStoredToken();
    return null;
  }
}

export function buildAuthorizationHeaders(token = getStoredAuthToken()) {
  const authToken = normalizeAuthToken(token) || getStoredAuthToken();
  return authToken
    ? { Authorization: `Bearer ${authToken}` }
    : {};
}

export function buildJsonAuthorizationHeaders(token = getStoredAuthToken()) {
  return {
    'Content-Type': 'application/json',
    ...buildAuthorizationHeaders(token),
  };
}
