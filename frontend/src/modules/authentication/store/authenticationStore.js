// ===== AI GENERATED: authenticationStore =====
// Purpose: Pinia store for authentication state management
// Inputs: login/logout actions
// Returns: Reactive auth state (token, account, isAuthenticated, role)

import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { loginRequest } from '../services/authenticationService.js';
import { apiUrl as buildApiUrl } from '@/shared/utils/apiBase.js';
import {
  AUTH_STORAGE_KEYS,
  clearAuthStorage,
  readStoredJson,
  readStoredToken,
  writeStoredJson,
  writeStoredToken,
} from '../utils/authStorage.js';

function normalizeRole(rawRole) {
  if (!rawRole) return null;
  const value = String(rawRole).trim();
  if (!value) return null;

  const upper = value.toUpperCase();
  if (upper === 'ROLE_ADMIN' || upper === 'ADMIN') return 'ROLE_ADMIN';
  if (upper === 'ROLE_BORROWER' || upper === 'BORROWER') return 'ROLE_BORROWER';

  return upper.startsWith('ROLE_') ? upper : value;
}

/**
 * @function useAuthenticationStore
 * @description Pinia store managing authentication state, login/logout, and session persistence.
 */
export const useAuthenticationStore = defineStore('authentication', () => {
  const authToken = ref(readStoredToken());
  const accountDataValue = readStoredJson(AUTH_STORAGE_KEYS.account);
  const accountData = ref(accountDataValue);

  const isAuthenticated = computed(() => {
    if (accountData.value === null) return false;
    // Clerk sessions may not provide a JWT token unless templates are configured; treat as signed-in when
    // an account exists and it is marked as coming from Clerk.
    if (authToken.value !== null) return true;
    return accountData.value?.authProvider === 'clerk';
  });
  const userRole = computed(() => {
    const roleFromAccount = accountData.value?.roleDesignation ?? accountData.value?.role ?? null;
    return normalizeRole(roleFromAccount);
  });
  const userFullName = computed(() => {
    if (!accountData.value) return '';
    return `${accountData.value.firstName} ${accountData.value.lastName}`;
  });

  // ── Clerk bridge ──────────────────────────────────────────────────────────
  let clerkAccountValue = readStoredJson(AUTH_STORAGE_KEYS.clerkAccount);

  if (!clerkAccountValue && accountDataValue?.authProvider === 'clerk') {
    clerkAccountValue = accountDataValue;
    writeStoredJson(AUTH_STORAGE_KEYS.clerkAccount, clerkAccountValue);
  }

  const clerkAccountData = ref(clerkAccountValue);

  const clerkIsSignedIn = computed(() => clerkAccountData.value !== null);
  const clerkAccountStatus = computed(() => clerkAccountData.value?.status ?? null);
  const clerkUserRole = computed(() => clerkAccountData.value?.roleDesignation ?? null);
  const clerkUserFullName = computed(() => {
    if (!clerkAccountData.value) return '';
    return `${clerkAccountData.value.firstName} ${clerkAccountData.value.lastName}`.trim();
  });

  async function loadClerkAccount(getTokenFn) {
    try {
      const token = await getTokenFn();
      
      // If no token available, try to load from localStorage as fallback
      if (!token) {
        const storedAccount = readStoredJson(AUTH_STORAGE_KEYS.clerkAccount);
        if (storedAccount) {
          clerkAccountData.value = storedAccount;
          return clerkAccountData.value;
        }
        return null;
      }

      const accountApiUrl = buildApiUrl('/api/v1/users/me');
      
      const response = await fetch(
        accountApiUrl,
        { 
          headers: { 
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json'
          } 
        }
      );

      if (!response.ok) {
        clerkAccountData.value = null;
        localStorage.removeItem(AUTH_STORAGE_KEYS.clerkAccount);
        return null;
      }

      const data = await response.json();
      
      if (!data.data || !data.data.account) {
        clerkAccountData.value = null;
        localStorage.removeItem(AUTH_STORAGE_KEYS.clerkAccount);
        return null;
      }
      
      clerkAccountData.value = data.data.account;
      
      writeStoredJson(AUTH_STORAGE_KEYS.clerkAccount, clerkAccountData.value);
      return clerkAccountData.value;
    } catch (err) {
      // Try fallback from localStorage on error
      const storedAccount = readStoredJson(AUTH_STORAGE_KEYS.clerkAccount);
      if (storedAccount) {
        clerkAccountData.value = storedAccount;
        return clerkAccountData.value;
      }
      return null;
    }
  }

  function setClerkSignedOut() {
    clerkAccountData.value = null;
    localStorage.removeItem(AUTH_STORAGE_KEYS.clerkAccount);
  }

  /**
   * @function performLogin
   * @description Authenticates user via backend API and stores session.
   * @param {string} emailAddress
   * @param {string} passwordText
   * @returns {Promise<Object>} Account data on success
   */
  async function performLogin(emailAddress, passwordText) {
    try {
      const response = await loginRequest({ emailAddress, passwordText });

      authToken.value = response.token;
      accountData.value = {
        ...response.account,
        roleDesignation: normalizeRole(response.account?.roleDesignation ?? response.account?.role),
      };

      writeStoredToken(response.token);
      writeStoredJson(AUTH_STORAGE_KEYS.account, accountData.value);

      return response.account;
    } catch (error) {
      console.warn('Backend login failed:', error.message);
      throw error;
    }
  }

  /**
   * @function setClerkAuth
   * @description Sets authentication data from Clerk authentication.
   * @param {string} token - Clerk session token
   * @param {Object} account - Account data from Clerk user
   */
  function setClerkAuth(token, account) {
    const normalizedAccount = {
      ...account,
      roleDesignation: normalizeRole(account?.roleDesignation ?? account?.role),
      authProvider: 'clerk',
    };

    authToken.value = token || null;
    accountData.value = normalizedAccount;
    clerkAccountData.value = normalizedAccount;

    if (token) {
      writeStoredToken(token);
    } else {
      writeStoredToken(null);
    }
    writeStoredJson(AUTH_STORAGE_KEYS.account, normalizedAccount);
    writeStoredJson(AUTH_STORAGE_KEYS.clerkAccount, normalizedAccount);
  }

  /**
   * @function performLogout
   * @description Clears authentication state and local storage.
   */
  function performLogout() {
    authToken.value = null;
    accountData.value = null;
    clerkAccountData.value = null;
    clearAuthStorage();
  }

  return {
    authToken,
    accountData,
    isAuthenticated,
    userRole,
    userFullName,
    performLogin,
    setClerkAuth,
    performLogout,
    clerkAccountData,
    clerkIsSignedIn,
    clerkAccountStatus,
    clerkUserRole,
    clerkUserFullName,
    loadClerkAccount,
    setClerkSignedOut,
  };
});
