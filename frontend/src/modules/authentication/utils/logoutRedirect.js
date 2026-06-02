import { resolveFrontendBaseUrl } from '@/shared/utils/frontendBaseUrl.js';

export function getPostLogoutRedirectUrl() {
  return resolveFrontendBaseUrl();
}

export function redirectToPostLogoutHome() {
  window.location.href = getPostLogoutRedirectUrl();
}
