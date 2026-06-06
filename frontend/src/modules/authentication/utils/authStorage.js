export const AUTH_STORAGE_KEYS = Object.freeze({
  token: 'techreserve_auth_token',
  account: 'techreserve_auth_account',
  clerkAccount: 'techreserve_clerk_account',
  rememberedLoginEmail: 'techreserve_remembered_login_email',
  pendingRememberSession: 'techreserve_pending_remember_session',
});

function getStorageEntries() {
  return [
    { storage: localStorage, persistent: true },
    { storage: sessionStorage, persistent: false },
  ];
}

function removeItemFromAllStorages(key) {
  for (const { storage } of getStorageEntries()) {
    storage.removeItem(key);
  }
}

function readFirstStoredValue(key) {
  for (const { storage } of getStorageEntries()) {
    const value = storage.getItem(key);
    if (value !== null && value !== 'undefined') {
      return value;
    }
  }

  return null;
}

export function readStoredToken() {
  const storedToken = readFirstStoredValue(AUTH_STORAGE_KEYS.token);
  return storedToken && storedToken !== 'null' && storedToken !== 'undefined'
    ? storedToken
    : null;
}

export function readStoredJson(key) {
  const storedValue = readFirstStoredValue(key);
  if (!storedValue || storedValue === 'undefined') return null;

  try {
    return JSON.parse(storedValue);
  } catch (error) {
    return null;
  }
}

export function writeStoredJson(key, value, persistent = true) {
  removeItemFromAllStorages(key);
  const targetStorage = persistent ? localStorage : sessionStorage;
  targetStorage.setItem(key, JSON.stringify(value));
}

export function writeStoredToken(token, persistent = true) {
  if (token) {
    removeItemFromAllStorages(AUTH_STORAGE_KEYS.token);
    const targetStorage = persistent ? localStorage : sessionStorage;
    targetStorage.setItem(AUTH_STORAGE_KEYS.token, token);
    return;
  }

  removeItemFromAllStorages(AUTH_STORAGE_KEYS.token);
}

export function clearAuthStorage() {
  removeItemFromAllStorages(AUTH_STORAGE_KEYS.token);
  removeItemFromAllStorages(AUTH_STORAGE_KEYS.account);
  removeItemFromAllStorages(AUTH_STORAGE_KEYS.clerkAccount);
  removeItemFromAllStorages(AUTH_STORAGE_KEYS.pendingRememberSession);
}

export function readRememberedLoginEmail() {
  return localStorage.getItem(AUTH_STORAGE_KEYS.rememberedLoginEmail);
}

export function writeRememberedLoginEmail(emailAddress) {
  localStorage.setItem(AUTH_STORAGE_KEYS.rememberedLoginEmail, emailAddress);
}

export function clearRememberedLoginEmail() {
  localStorage.removeItem(AUTH_STORAGE_KEYS.rememberedLoginEmail);
}

export function persistPendingRememberSession(rememberSession) {
  sessionStorage.setItem(AUTH_STORAGE_KEYS.pendingRememberSession, rememberSession ? 'true' : 'false');
}

export function consumePendingRememberSession() {
  const value = sessionStorage.getItem(AUTH_STORAGE_KEYS.pendingRememberSession);
  sessionStorage.removeItem(AUTH_STORAGE_KEYS.pendingRememberSession);
  return value === 'true';
}

export function isPersistentAuthStorage() {
  return Boolean(
    localStorage.getItem(AUTH_STORAGE_KEYS.token)
    || localStorage.getItem(AUTH_STORAGE_KEYS.account)
    || localStorage.getItem(AUTH_STORAGE_KEYS.clerkAccount)
  );
}
