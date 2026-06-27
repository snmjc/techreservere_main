import { computed, ref } from 'vue';

const MANAGE_ACCOUNTS_TAB_CACHE_PREFIX = 'techreserve_manage_accounts_tab_v1';
const DEFAULT_CACHE_TTL_MS = 5 * 60 * 1000;

export function useManageAccountsTabResource({
  tabKey,
  label,
  cacheScope,
  fetchAccounts,
  cacheTtlMs = DEFAULT_CACHE_TTL_MS,
}) {
  const cachedSnapshot = readTabCache(tabKey, cacheScope.value);
  const data = ref(cachedSnapshot.data);
  const isLoading = ref(false);
  const error = ref('');
  const expiresAt = ref(cachedSnapshot.expiresAt);
  const state = ref(data.value.length > 0 ? 'cached' : 'idle');

  const statusItem = computed(() => ({
    key: `manage-accounts-${tabKey}`,
    label,
    state: state.value,
    expiresAt: expiresAt.value,
  }));

  async function load() {
    isLoading.value = true;
    error.value = '';
    state.value = data.value.length > 0 ? 'cached-loading' : 'loading';

    try {
      const freshAccounts = await fetchAccounts(tabKey);
      if (!Array.isArray(freshAccounts)) {
        throw new Error('Manage Accounts API returned an invalid account list.');
      }

      data.value = freshAccounts;
      expiresAt.value = writeTabCache(tabKey, cacheScope.value, data.value, cacheTtlMs);
      state.value = 'fresh';
    } catch (loadError) {
      error.value = loadError?.message || 'Unable to load accounts.';
      state.value = data.value.length > 0 ? 'cached' : 'error';
    } finally {
      isLoading.value = false;
    }
  }

  return {
    data,
    error,
    isLoading,
    load,
    state,
    statusItem,
  };
}

function readTabCache(tabKey, cacheScope) {
  if (typeof window === 'undefined') {
    return { data: [], expiresAt: null };
  }

  try {
    const cachedValue = window.sessionStorage.getItem(cacheKey(tabKey, cacheScope));
    const parsedValue = cachedValue ? JSON.parse(cachedValue) : null;
    if (!parsedValue || parsedValue.expiresAt <= Date.now()) {
      window.sessionStorage.removeItem(cacheKey(tabKey, cacheScope));
      return { data: [], expiresAt: null };
    }

    return {
      data: Array.isArray(parsedValue.data) ? parsedValue.data : [],
      expiresAt: Number(parsedValue.expiresAt) || null,
    };
  } catch {
    return { data: [], expiresAt: null };
  }
}

function writeTabCache(tabKey, cacheScope, data, cacheTtlMs) {
  if (typeof window === 'undefined') return null;

  try {
    const expiresAt = Date.now() + cacheTtlMs;
    window.sessionStorage.setItem(cacheKey(tabKey, cacheScope), JSON.stringify({
      data,
      expiresAt,
    }));
    return expiresAt;
  } catch {
    return null;
  }
}

function cacheKey(tabKey, cacheScope) {
  return `${MANAGE_ACCOUNTS_TAB_CACHE_PREFIX}_${cacheScope || 'anonymous'}_${tabKey}`;
}
