// ===== AI GENERATED: authenticationStore =====
// Purpose: Pinia store for authentication state management
// Inputs: login/logout actions
// Returns: Reactive auth state (token, account, isAuthenticated, role)

import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { loginRequest } from '../services/authenticationService.js';

const STORAGE_KEY_TOKEN = 'techreserve_auth_token';
const STORAGE_KEY_ACCOUNT = 'techreserve_auth_account';

/**
 * @function useAuthenticationStore
 * @description Pinia store managing authentication state, login/logout, and session persistence.
 */
export const useAuthenticationStore = defineStore('authentication', () => {
  const authToken = ref(localStorage.getItem(STORAGE_KEY_TOKEN) || null);
  const accountData = ref(JSON.parse(localStorage.getItem(STORAGE_KEY_ACCOUNT) || 'null'));

  const isAuthenticated = computed(() => authToken.value !== null && accountData.value !== null);
  const userRole = computed(() => accountData.value?.roleDesignation || null);
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
    const response = await loginRequest({ emailAddress, passwordText });

    authToken.value = response.token;
    accountData.value = response.account;

    localStorage.setItem(STORAGE_KEY_TOKEN, response.token);
    localStorage.setItem(STORAGE_KEY_ACCOUNT, JSON.stringify(response.account));

    return response.account;
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
    performLogout,
  };
});
