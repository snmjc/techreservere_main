import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { loginRequest } from '../services/authenticationService.js';
import {
  AUTH_STORAGE_KEYS,
  clearAuthStorage,
  readStoredJson,
  readStoredToken,
} from '../utils/authStorage.js';
import {
  buildSessionAccount,
  clearStoredClerkAccount,
  getAccountFullName,
  getAccountRole,
  isActiveSession,
  persistAuthSessionWithPreference,
  persistClerkSessionWithPreference,
  resolveClerkAccount,
  resolveInitialClerkAccount,
} from '../utils/authenticationSession.js';
import { getClerkToken } from '../utils/clerkAuthUtils.js';
import { getStoredAuthToken, normalizeAuthToken } from '@/shared/utils/authToken.js';

export const useAuthenticationStore = defineStore('authentication', () => {
  const authToken = ref(getStoredAuthToken());
  const accountDataValue = readStoredJson(AUTH_STORAGE_KEYS.account);
  const accountData = ref(accountDataValue);
  const clerkAccountData = ref(resolveInitialClerkAccount(accountDataValue));

  const activeAccount = computed(() => accountData.value || clerkAccountData.value);
  const isAuthenticated = computed(() => isActiveSession(activeAccount.value, authToken.value));
  const userRole = computed(() => getAccountRole(activeAccount.value));
  const userFullName = computed(() => getAccountFullName(activeAccount.value));
  const clerkIsSignedIn = computed(() => clerkAccountData.value !== null);
  const clerkAccountStatus = computed(() => clerkAccountData.value?.status ?? null);
  const clerkUserRole = computed(() => clerkAccountData.value?.roleDesignation ?? null);
  const clerkUserFullName = computed(() => getAccountFullName(clerkAccountData.value));

  async function loadClerkAccount(getTokenFn) {
    const token = await getClerkToken(getTokenFn).catch(() => null);
    const resolvedAccount = await resolveClerkAccount(() => Promise.resolve(token));
    const normalizedAccount = resolvedAccount ? buildSessionAccount(resolvedAccount, 'clerk') : null;

    clerkAccountData.value = normalizedAccount;

    if (normalizedAccount) {
      authToken.value = normalizeAuthToken(token) || getStoredAuthToken() || authToken.value || null;
      accountData.value = normalizedAccount;
      persistClerkSessionWithPreference(authToken.value, normalizedAccount, true);
    }

    if (!normalizedAccount && token === null) {
      authToken.value = null;
    }
    return clerkAccountData.value;
  }

  function setClerkSignedOut() {
    clerkAccountData.value = null;
    clearStoredClerkAccount();
  }

  async function performLogin(emailAddress, passwordText, options = {}) {
    const persistent = options.rememberSession === true;

    try {
      const response = await loginRequest({ emailAddress, passwordText });

      authToken.value = response.token;
      accountData.value = buildSessionAccount(response.account);
      persistAuthSessionWithPreference(response.token, accountData.value, persistent);

      return response.account;
    } catch (error) {
      console.warn('Backend login failed:', error.message);
      throw error;
    }
  }

  function setClerkAuth(token, account, options = {}) {
    const persistent = options.rememberSession === true;
    const normalizedAccount = buildSessionAccount(account, 'clerk');
    const resolvedToken = normalizeAuthToken(token);

    authToken.value = resolvedToken;
    accountData.value = normalizedAccount;
    clerkAccountData.value = normalizedAccount;
    persistClerkSessionWithPreference(resolvedToken, normalizedAccount, persistent);
  }

  function setLocalAuth(token, account, options = {}) {
    const persistent = options.rememberSession === true;
    const normalizedAccount = buildSessionAccount(account);
    const resolvedToken = normalizeAuthToken(token);

    authToken.value = resolvedToken;
    accountData.value = normalizedAccount;
    clerkAccountData.value = null;
    persistAuthSessionWithPreference(resolvedToken, normalizedAccount, persistent);
  }

  function performLogout() {
    authToken.value = null;
    accountData.value = null;
    clerkAccountData.value = null;
    clearAuthStorage();
  }

  return {
    authToken,
    activeAccount,
    accountData,
    isAuthenticated,
    userRole,
    userFullName,
    performLogin,
    setClerkAuth,
    setLocalAuth,
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
