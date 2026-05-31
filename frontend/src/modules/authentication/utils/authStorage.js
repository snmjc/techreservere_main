export const AUTH_STORAGE_KEYS = Object.freeze({
  token: 'techreserve_auth_token',
  account: 'techreserve_auth_account',
  clerkAccount: 'techreserve_clerk_account',
});

export function readStoredToken() {
  const storedToken = localStorage.getItem(AUTH_STORAGE_KEYS.token);
  return storedToken && storedToken !== 'null' && storedToken !== 'undefined'
    ? storedToken
    : null;
}

export function readStoredJson(key) {
  const storedValue = localStorage.getItem(key);
  if (!storedValue || storedValue === 'undefined') return null;

  try {
    return JSON.parse(storedValue);
  } catch (error) {
    return null;
  }
}

export function writeStoredJson(key, value) {
  localStorage.setItem(key, JSON.stringify(value));
}

export function writeStoredToken(token) {
  if (token) {
    localStorage.setItem(AUTH_STORAGE_KEYS.token, token);
    return;
  }

  localStorage.removeItem(AUTH_STORAGE_KEYS.token);
}

export function clearAuthStorage() {
  localStorage.removeItem(AUTH_STORAGE_KEYS.token);
  localStorage.removeItem(AUTH_STORAGE_KEYS.account);
  localStorage.removeItem(AUTH_STORAGE_KEYS.clerkAccount);
}
