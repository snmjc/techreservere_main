export function resolveApiBaseUrl() {
  const configuredUrl = String(import.meta.env.VITE_API_BASE_URL || '').replace(/\/+$/, '');

  if (!configuredUrl) {
    return '';
  }

  if (typeof window === 'undefined') {
    return configuredUrl;
  }

  const frontendHost = window.location.hostname;
  const configuredHost = getUrlHost(configuredUrl);
  const isTunneledFrontend = frontendHost.endsWith('.trycloudflare.com');
  const isLocalConfiguredApi = configuredHost === 'localhost' || configuredHost === '127.0.0.1';

  if (isTunneledFrontend && isLocalConfiguredApi) {
    return '';
  }

  return configuredUrl;
}

export function apiUrl(path) {
  const normalizedPath = path.startsWith('/') ? path : `/${path}`;
  return `${resolveApiBaseUrl()}${normalizedPath}`;
}

function getUrlHost(value) {
  try {
    return new URL(value).hostname;
  } catch (error) {
    return '';
  }
}
