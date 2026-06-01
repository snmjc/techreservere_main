export function getPostLogoutRedirectUrl() {
  const isDevelopmentHost = ['localhost', '127.0.0.1'].includes(window.location.hostname);
  return isDevelopmentHost
    ? 'http://localhost:5173/'
    : 'http://techreserve.farahkenawy.codes/';
}

export function redirectToPostLogoutHome() {
  window.location.href = getPostLogoutRedirectUrl();
}
