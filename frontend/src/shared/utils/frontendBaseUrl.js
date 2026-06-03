const PRODUCTION_FRONTEND_URL = 'https://techreserve.farahkenawy.codes/';
const DEVELOPMENT_FRONTEND_URL = 'https://employers-mall-switches-bookstore.trycloudflare.com/';

export function resolveFrontendBaseUrl() {
  const isDevelopmentHost = typeof window !== 'undefined'
    && (
      ['localhost', '127.0.0.1'].includes(window.location.hostname)
      || window.location.hostname.endsWith('.trycloudflare.com')
    );

  const configuredUrl = isDevelopmentHost
    ? import.meta.env.VITE_DEV_FRONTEND_URL
    : import.meta.env.VITE_FRONTEND_URL;

  return normalizeFrontendUrl(configuredUrl || (isDevelopmentHost ? DEVELOPMENT_FRONTEND_URL : PRODUCTION_FRONTEND_URL));
}

export function frontendUrl(path = '/') {
  const normalizedPath = path.startsWith('/') ? path.slice(1) : path;
  return new URL(normalizedPath, resolveFrontendBaseUrl()).toString();
}

function normalizeFrontendUrl(value) {
  const url = String(value || PRODUCTION_FRONTEND_URL).trim();
  return url.endsWith('/') ? url : `${url}/`;
}
