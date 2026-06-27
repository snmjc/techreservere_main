import { computed, ref } from 'vue';

const DASHBOARD_SECTION_CACHE_PREFIX = 'techreserve_dashboard_section_v2';
const DEFAULT_CACHE_TTL_MS = 5 * 60 * 1000;

export function useDashboardSectionResource({
  sectionKey,
  defaultValue,
  fetchSection,
  hasData = defaultHasData,
  cacheTtlMs = DEFAULT_CACHE_TTL_MS,
}) {
  const cachedSnapshot = readSectionCache(sectionKey, defaultValue);
  const data = ref(cachedSnapshot.data);
  const isLoading = ref(false);
  const error = ref('');
  const cacheExpiresAt = ref(cachedSnapshot.expiresAt);
  const state = ref(hasData(data.value) ? 'cached' : 'idle');

  const statusItem = computed(() => ({
    key: sectionKey,
    label: sectionLabel(sectionKey),
    state: state.value,
    expiresAt: cacheExpiresAt.value,
  }));

  async function load(range) {
    isLoading.value = true;
    error.value = '';
    state.value = hasData(data.value) ? 'cached-loading' : 'loading';

    try {
      const freshData = await fetchSection(range);
      data.value = freshData ?? defaultValue;
      cacheExpiresAt.value = writeSectionCache(sectionKey, data.value, cacheTtlMs);
      state.value = 'fresh';
    } catch (loadError) {
      error.value = resolveErrorMessage(loadError);
      if (!hasData(data.value)) {
        data.value = defaultValue;
      }
      state.value = hasData(data.value) ? 'cached' : 'error';
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

function readSectionCache(sectionKey, defaultValue) {
  if (typeof window === 'undefined') {
    return { data: defaultValue, expiresAt: null };
  }

  try {
    const cachedValue = window.sessionStorage.getItem(cacheKey(sectionKey));
    const parsedValue = cachedValue ? JSON.parse(cachedValue) : null;
    if (!parsedValue || parsedValue.expiresAt <= Date.now()) {
      window.sessionStorage.removeItem(cacheKey(sectionKey));
      return { data: defaultValue, expiresAt: null };
    }
    return {
      data: parsedValue.data ?? defaultValue,
      expiresAt: Number(parsedValue.expiresAt) || null,
    };
  } catch {
    return { data: defaultValue, expiresAt: null };
  }
}

function writeSectionCache(sectionKey, data, cacheTtlMs) {
  if (typeof window === 'undefined') return null;

  try {
    const expiresAt = Date.now() + cacheTtlMs;
    window.sessionStorage.setItem(cacheKey(sectionKey), JSON.stringify({
      data,
      expiresAt,
    }));
    return expiresAt;
  } catch {
    // Cache writes are best-effort only.
    return null;
  }
}

function cacheKey(sectionKey) {
  return `${DASHBOARD_SECTION_CACHE_PREFIX}_${sectionKey}`;
}

function defaultHasData(value) {
  if (Array.isArray(value)) return value.length > 0;
  if (!value || typeof value !== 'object') return Boolean(value);
  return Object.values(value).some((item) => {
    if (Array.isArray(item)) return item.length > 0;
    return Number(item || 0) > 0 || Boolean(item);
  });
}

function sectionLabel(sectionKey) {
  return sectionKey
    .split('-')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ');
}

function resolveErrorMessage(error) {
  return error?.response?.data?.errorMessage
    || error?.message
    || 'Unable to load this dashboard section.';
}
