import { apiUrl as buildApiUrl } from '@/shared/utils/apiBase.js';
import {
  AUTH_STORAGE_KEYS,
  readStoredJson,
  writeStoredJson,
  writeStoredToken,
} from './authStorage.js';

const ROLE_ALIASES = Object.freeze({
  ADMIN: 'ROLE_ADMIN',
  BORROWER: 'ROLE_BORROWER',
  EMPLOYEE: 'ROLE_STAFF',
  FACULTY: 'ROLE_BORROWER',
  ROLE_FACULTY: 'ROLE_BORROWER',
  STUDENT: 'ROLE_BORROWER',
  ROLE_STUDENT: 'ROLE_BORROWER',
  ROLE_ADMIN: 'ROLE_ADMIN',
  ROLE_BORROWER: 'ROLE_BORROWER',
  ROLE_STAFF: 'ROLE_STAFF',
  STAFF: 'ROLE_STAFF',
});

export function normalizeRole(rawRole) {
  if (!rawRole) return null;

  const value = String(rawRole).trim();
  if (!value) return null;

  const upper = value.toUpperCase();
  return ROLE_ALIASES[upper] ?? (upper.startsWith('ROLE_') ? upper : value);
}

export function buildSessionAccount(account, authProvider = null) {
  const sessionAccount = {
    ...account,
    roleDesignation: normalizeRole(account?.roleDesignation ?? account?.role),
  };

  return authProvider ? { ...sessionAccount, authProvider } : sessionAccount;
}

export function isActiveSession(account, token) {
  return account !== null && (token !== null || account?.authProvider === 'clerk');
}

export function getAccountRole(account) {
  return normalizeRole(account?.roleDesignation ?? account?.role);
}

export function getAccountFullName(account) {
  if (!account) return '';
  return `${account.firstName} ${account.lastName}`.trim();
}

export function resolveInitialClerkAccount(account) {
  const storedAccount = readStoredJson(AUTH_STORAGE_KEYS.clerkAccount);
  if (storedAccount) return storedAccount;

  if (account?.authProvider !== 'clerk') return null;

  writeStoredJson(AUTH_STORAGE_KEYS.clerkAccount, account);
  return account;
}

export async function resolveClerkAccount(getTokenFn) {
  try {
    const token = await getTokenFn();
    return token ? fetchCurrentAccount(token) : readStoredJson(AUTH_STORAGE_KEYS.clerkAccount);
  } catch (error) {
    return readStoredJson(AUTH_STORAGE_KEYS.clerkAccount);
  }
}

export function persistAuthSession(token, account) {
  writeStoredToken(token);
  writeStoredJson(AUTH_STORAGE_KEYS.account, account);
}

export function persistClerkSession(token, account) {
  persistAuthSession(token || null, account);
  writeStoredJson(AUTH_STORAGE_KEYS.clerkAccount, account);
}

export function clearStoredClerkAccount() {
  localStorage.removeItem(AUTH_STORAGE_KEYS.clerkAccount);
}

async function fetchCurrentAccount(token) {
  const response = await fetch(buildApiUrl('/api/v1/users/me'), {
    headers: {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
  });

  if (!response.ok) {
    clearStoredClerkAccount();
    return null;
  }

  const account = extractAccount(await response.json());
  if (!account) clearStoredClerkAccount();
  if (account) writeStoredJson(AUTH_STORAGE_KEYS.clerkAccount, account);

  return account;
}

function extractAccount(data) {
  return data?.data?.account ?? null;
}
