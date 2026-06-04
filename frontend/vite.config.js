import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
import path from 'path'

export default defineConfig(({ mode }) => {
  // Load env variables from frontend directory
  const env = loadEnv(mode, path.resolve(__dirname), '');
  const apiBase = env.VITE_API_BASE_URL || 'http://localhost:8000';
  const apiProxyTarget = env.VITE_API_PROXY_TARGET || apiBase || 'http://localhost:8000';
  const frontendTunnelHost = getUrlHost(env.VITE_DEV_FRONTEND_URL) || 'employers-mall-switches-bookstore.trycloudflare.com';
  const frontendTunnelUrl = `https://${frontendTunnelHost}`;
  const clerkSources = [
    'https://clerk.farahkenawy.codes',
    'https://*.clerk.accounts.dev',
    'https://*.clerk.dev',
    'https://*.clerk.com',
    'https://clerk.com',
    'https://cdn.clerk.com',
    'https://api.clerk.com',
    'https://img.clerk.com',
    'https://images.clerk.dev',
    'https://cdn.jsdelivr.net',
    'https://*.jsdelivr.net',
    'https://challenges.cloudflare.com',
  ].join(' ');

  return {
    // Load env files from the frontend directory so frontend/.env is used
    envDir: path.resolve(__dirname),
    plugins: [vue()],
    resolve: {
      alias: {
        '@': path.resolve(__dirname, 'src'),
      },
    },
    server: {
      // Bind to all interfaces so the dev server is reachable from the host
      host: true,
      port: 5173,
      allowedHosts: [frontendTunnelHost],
      // Dev-only CSP: allows Vue/DevTools and some dependencies that use eval in development.
      // Remove/replace with a strict CSP for production builds.
      headers: {
        'Cache-Control': 'no-store',
        'Content-Security-Policy': [
          "default-src 'self'",
          // Vite dev + some tooling may rely on eval/inline; keep this dev-only.
          `script-src 'self' 'unsafe-eval' 'unsafe-inline' blob: data: ${clerkSources}`,
          `script-src-elem 'self' 'unsafe-inline' blob: data: ${clerkSources}`,
          `style-src 'self' 'unsafe-inline' ${clerkSources}`,
          `img-src 'self' data: blob: ${clerkSources}`,
          // Allow API + HMR websocket + Clerk (if enabled)
          `connect-src 'self' ${apiBase} ${frontendTunnelUrl} wss://${frontendTunnelHost} ws://localhost:5173 ws://127.0.0.1:5173 ${clerkSources}`,
          `frame-src 'self' ${clerkSources}`,
          "font-src 'self' data: https://fonts.gstatic.com",
          "worker-src 'self' blob:",
          "frame-ancestors 'self'",
        ].join('; ')
      },
      // Use polling to reliably detect file changes when files are mounted from the host into Docker
      watch: {
        usePolling: true,
        interval: 100,
      },
      proxy: {
        // Proxy API requests to the backend to avoid CORS during local dev
        '/api': {
          target: apiProxyTarget,
          changeOrigin: true,
          secure: false,
          rewrite: (p) => p.replace(/^\/api/, '/api'),
        },
        '/health': {
          target: apiProxyTarget,
          changeOrigin: true,
          secure: false,
        },
      },
    },
  };
});

function getUrlHost(value) {
  try {
    return new URL(value).hostname;
  } catch (error) {
    return '';
  }
}
