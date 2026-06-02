const PRODUCTION_FRONTEND_URL = 'https://techreserve.farahkenawy.codes/';
const DEVELOPMENT_FRONTEND_URL = 'http://localhost:5173/';

export function resolveFrontendBaseUrl() {
  const isLocalHost = typeof window !== 'undefined'
    && ['localhost', '127.0.0.1'].includes(window.location.hostname);

  const configuredUrl = isLocalHost
    ? import.meta.env.VITE_DEV_FRONTEND_URL
    : import.meta.env.VITE_FRONTEND_URL;

  return normalizeFrontendUrl(configuredUrl || (isLocalHost ? DEVELOPMENT_FRONTEND_URL : PRODUCTION_FRONTEND_URL));
}

export function frontendUrl(path = '/') {
  const normalizedPath = path.startsWith('/') ? path.slice(1) : path;
  return new URL(normalizedPath, resolveFrontendBaseUrl()).toString();
}

function normalizeFrontendUrl(value) {
  const url = String(value || PRODUCTION_FRONTEND_URL).trim();
  return url.endsWith('/') ? url : `${url}/`;
}
