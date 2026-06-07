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

export const useAuthenticationStore = defineStore('authentication', () => {
  const authToken = ref(readStoredToken());
  const accountDataValue = readStoredJson(AUTH_STORAGE_KEYS.account);
  const accountData = ref(accountDataValue);
  const clerkAccountData = ref(resolveInitialClerkAccount(accountDataValue));

  const isAuthenticated = computed(() => isActiveSession(accountData.value, authToken.value));
  const userRole = computed(() => getAccountRole(accountData.value));
  const userFullName = computed(() => getAccountFullName(accountData.value));
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
      authToken.value = token || authToken.value || null;
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

    authToken.value = token || null;
    accountData.value = normalizedAccount;
    clerkAccountData.value = normalizedAccount;
    persistClerkSessionWithPreference(token, normalizedAccount, persistent);
  }

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
