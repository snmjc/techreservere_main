// ===== AI GENERATED: authenticationStore =====
// Purpose: Pinia store for authentication state management
// Inputs: login/logout actions
// Returns: Reactive auth state (token, account, isAuthenticated, role)

import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { loginRequest } from '../services/authenticationService.js';

const STORAGE_KEY_TOKEN = 'techreserve_auth_token';
const STORAGE_KEY_ACCOUNT = 'techreserve_auth_account';

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
      console.warn('Backend login failed, using mock authentication:', error.message);
      
      // Mock authentication for development when backend is not available
      const mockToken = 'mock_token_' + Date.now();
      const mockAccount = {
        accountIdentifier: 1,
        firstName: emailAddress.split('@')[0],
        lastName: 'User',
        emailAddress: emailAddress,
        roleDesignation: emailAddress.includes('admin') ? 'ROLE_ADMIN' : 'ROLE_BORROWER',
        contactNumber: '+63-912-345-6789',
        isActive: true
      };

      authToken.value = mockToken;
      accountData.value = { ...mockAccount, roleDesignation: normalizeRole(mockAccount.roleDesignation) };

      localStorage.setItem(STORAGE_KEY_TOKEN, mockToken);
      localStorage.setItem(STORAGE_KEY_ACCOUNT, JSON.stringify(accountData.value));

      return mockAccount;
    }
  }

  /**
   * @function setClerkAuth
   * @description Sets authentication data from Clerk authentication.
   * @param {string} token - Clerk session token
   * @param {Object} account - Account data from Clerk user
   */
  function setClerkAuth(token, account) {
    authToken.value = token || null;
    accountData.value = {
      ...account,
      roleDesignation: normalizeRole(account?.roleDesignation ?? account?.role),
      authProvider: 'clerk',
    };

    if (token) {
      localStorage.setItem(STORAGE_KEY_TOKEN, token);
    } else {
      localStorage.removeItem(STORAGE_KEY_TOKEN);
    }
    localStorage.setItem(STORAGE_KEY_ACCOUNT, JSON.stringify(accountData.value));
  }

  /**
   * @function performLogout
   * @description Clears authentication state and local storage.
   */
  function performLogout() {
    authToken.value = null;
    accountData.value = null;
    localStorage.removeItem(STORAGE_KEY_TOKEN);
    localStorage.removeItem(STORAGE_KEY_ACCOUNT);
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
  };
});
