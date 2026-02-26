import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
import path from 'path'

export default defineConfig(({ mode }) => {
  // Load env variables from frontend directory
  const env = loadEnv(mode, path.resolve(__dirname), '');
  const apiBase = env.VITE_API_BASE_URL || 'http://localhost:8067';

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
      // Use polling to reliably detect file changes when files are mounted from the host into Docker
      watch: {
        usePolling: true,
        interval: 100,
      },
      proxy: {
        // Proxy API requests to the backend to avoid CORS during local dev
        '/api': {
          target: apiBase,
          changeOrigin: true,
          secure: false,
          rewrite: (p) => p.replace(/^\/api/, '/api'),
        },
      },
    },
  };
});
