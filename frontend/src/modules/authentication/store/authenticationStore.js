// ===== AI GENERATED: authenticationStore =====
// Purpose: Pinia store for authentication state management
// Inputs: login/logout actions
// Returns: Reactive auth state (token, account, isAuthenticated, role)

import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { loginRequest } from '../services/authenticationService.js';
import { apiUrl as buildApiUrl } from '@/shared/utils/apiBase.js';

const STORAGE_KEY_TOKEN = 'techreserve_auth_token';
const STORAGE_KEY_ACCOUNT = 'techreserve_auth_account';
const STORAGE_KEY_CLERK_ACCOUNT = 'techreserve_clerk_account';

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
  const storedToken = localStorage.getItem(STORAGE_KEY_TOKEN);
  const authToken = ref(
    storedToken && storedToken !== 'null' && storedToken !== 'undefined' ? storedToken : null
  );
  
  const accountString = localStorage.getItem(STORAGE_KEY_ACCOUNT);
  let accountDataValue = null;
  if (accountString && accountString !== 'undefined') {
    try {
      accountDataValue = JSON.parse(accountString);
    } catch (e) {
      console.error('Failed to parse account data from localStorage:', e);
      accountDataValue = null;
    }
  }
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
  const clerkAccountString = localStorage.getItem(STORAGE_KEY_CLERK_ACCOUNT);
  let clerkAccountValue = null;
  if (clerkAccountString && clerkAccountString !== 'undefined') {
    try { clerkAccountValue = JSON.parse(clerkAccountString); } catch (_) {}
  }

  if (!clerkAccountValue && accountDataValue?.authProvider === 'clerk') {
    clerkAccountValue = accountDataValue;
    localStorage.setItem(STORAGE_KEY_CLERK_ACCOUNT, JSON.stringify(clerkAccountValue));
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
      console.log('[loadClerkAccount] Starting...');
      const token = await getTokenFn();
      console.log('[loadClerkAccount] Token received:', token ? 'YES' : 'NO');
      
      // If no token available, try to load from localStorage as fallback
      if (!token) {
        console.log('[loadClerkAccount] No token, checking localStorage...');
        const storedAccount = localStorage.getItem(STORAGE_KEY_CLERK_ACCOUNT);
        if (storedAccount) {
          console.log('[loadClerkAccount] Using fallback from localStorage');
          clerkAccountData.value = JSON.parse(storedAccount);
          return clerkAccountData.value;
        }
        console.log('[loadClerkAccount] No token and no stored account');
        return null;
      }

      const accountApiUrl = buildApiUrl('/api/v1/users/me');
      console.log('[loadClerkAccount] Fetching from:', accountApiUrl);
      
      const response = await fetch(
        accountApiUrl,
        { 
          headers: { 
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json'
          } 
        }
      );

      console.log('[loadClerkAccount] Response status:', response.status, response.statusText);

      if (!response.ok) {
        const errorText = await response.text();
        console.log('[loadClerkAccount] Response not OK:', response.status, errorText);
        clerkAccountData.value = null;
        localStorage.removeItem(STORAGE_KEY_CLERK_ACCOUNT);
        
        // Don't throw error, just return null for auth failures
        if (response.status === 401 || response.status === 403) {
          console.log('[loadClerkAccount] Authentication failed, user may need to log in again');
        }
        return null;
      }

      const data = await response.json();
      console.log('[loadClerkAccount] Response data:', data);
      
      if (!data.data || !data.data.account) {
        console.log('[loadClerkAccount] No account data in response');
        clerkAccountData.value = null;
        localStorage.removeItem(STORAGE_KEY_CLERK_ACCOUNT);
        return null;
      }
      
      clerkAccountData.value = data.data.account;
      console.log('[loadClerkAccount] Account loaded successfully:', {
        id: clerkAccountData.value.accountIdentifier,
        status: clerkAccountData.value.status,
        role: clerkAccountData.value.roleDesignation
      });
      
      localStorage.setItem(STORAGE_KEY_CLERK_ACCOUNT, JSON.stringify(clerkAccountData.value));
      return clerkAccountData.value;
    } catch (err) {
      console.error('[loadClerkAccount] Network error:', err);
      // Try fallback from localStorage on error
      const storedAccount = localStorage.getItem(STORAGE_KEY_CLERK_ACCOUNT);
      if (storedAccount) {
        console.log('[loadClerkAccount] Using fallback from localStorage after error');
        clerkAccountData.value = JSON.parse(storedAccount);
        return clerkAccountData.value;
      }
      return null;
    }
  }

  function setClerkSignedOut() {
    clerkAccountData.value = null;
    localStorage.removeItem(STORAGE_KEY_CLERK_ACCOUNT);
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

      localStorage.setItem(STORAGE_KEY_TOKEN, response.token);
      localStorage.setItem(STORAGE_KEY_ACCOUNT, JSON.stringify(accountData.value));

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
      localStorage.setItem(STORAGE_KEY_TOKEN, token);
    } else {
      localStorage.removeItem(STORAGE_KEY_TOKEN);
    }
    localStorage.setItem(STORAGE_KEY_ACCOUNT, JSON.stringify(normalizedAccount));
    localStorage.setItem(STORAGE_KEY_CLERK_ACCOUNT, JSON.stringify(normalizedAccount));
  }

  /**
   * @function performLogout
   * @description Clears authentication state and local storage.
   */
  function performLogout() {
    authToken.value = null;
    accountData.value = null;
    clerkAccountData.value = null;
    localStorage.removeItem(STORAGE_KEY_TOKEN);
    localStorage.removeItem(STORAGE_KEY_ACCOUNT);
    localStorage.removeItem(STORAGE_KEY_CLERK_ACCOUNT);
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
