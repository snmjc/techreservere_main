<template>
  <div class="handle-signin-wrapper">
    <!-- Loading State -->
    <div v-if="state === 'loading'" class="loading-wrapper">
      <p>Loading your account...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="state === 'error'" class="error-wrapper">
      <h3>Unable to sign in</h3>
      <p>{{ errorMessage }}</p>
      <div class="error-actions">
        <button @click="retry" class="btn btn-primary">Retry</button>
        <button @click="goToLogin" class="btn btn-secondary">Go to Login</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth, useUser } from '@clerk/vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';

const router = useRouter();
const { getToken } = useAuth();
const { isSignedIn } = useUser();
const authStore = useAuthenticationStore();

const state = ref('loading');
const errorMessage = ref('');

async function waitForClerkReady(maxWaitMs = 5000) {
  const start = Date.now();
  while (Date.now() - start < maxWaitMs) {
    if (isSignedIn.value) return true;
    await new Promise(r => setTimeout(r, 100));
  }
  return false;
}

async function processSignIn() {
  console.log('[HandleSignIn] Starting processSignIn...');
  state.value = 'loading';
  errorMessage.value = '';

  // Wait for Clerk to be ready
  console.log('[HandleSignIn] Waiting for Clerk ready...');
  const ready = await waitForClerkReady();
  console.log('[HandleSignIn] Clerk ready:', ready);
  if (!ready) {
    state.value = 'error';
    errorMessage.value = 'Clerk session did not initialize. Please try again.';
    return;
  }

  try {
    console.log('[HandleSignIn] Getting Clerk token...');
    const token = await getToken.value();
    console.log('[HandleSignIn] Token received:', token ? 'YES' : 'NO');
    if (!token) {
      state.value = 'error';
      errorMessage.value = 'Unable to get authentication token. Please try again.';
      return;
    }

    console.log('[HandleSignIn] Loading Clerk account...');
    const account = await authStore.loadClerkAccount(getToken.value);
    console.log('[HandleSignIn] Account loaded:', account);
    if (!account) {
      state.value = 'error';
      errorMessage.value = 'No account found for this user. Please sign up first.';
      return;
    }

    const accountStatus = account.status;
    const userRole = account.roleDesignation;
    console.log('[HandleSignIn] Account status:', accountStatus, 'Role:', userRole);

    if (accountStatus === 'pending') {
      console.log('[HandleSignIn] Redirecting to', ROUTE_NAMES.requestPending);
      router.push({ name: ROUTE_NAMES.requestPending });
    } else if (accountStatus === 'active' || accountStatus === 'approved' || accountStatus === 'accepted') {
      if (userRole === 'ROLE_ADMIN') {
        console.log('[HandleSignIn] Redirecting to', ROUTE_NAMES.adminDashboard);
        router.push({ name: ROUTE_NAMES.adminDashboard });
      } else if (userRole === 'ROLE_STAFF') {
        console.log('[HandleSignIn] Redirecting to', ROUTE_NAMES.employeeDashboard);
        router.push({ name: ROUTE_NAMES.employeeDashboard });
      } else {
        console.log('[HandleSignIn] Redirecting to', ROUTE_NAMES.borrowerMyReservations);
        router.push({ name: ROUTE_NAMES.borrowerMyReservations });
      }
    } else {
      state.value = 'error';
      errorMessage.value = `Account status is "${accountStatus}". Please contact support.`;
    }
  } catch (err) {
    console.error('[HandleSignIn] Failed to load account on login:', err);
    state.value = 'error';
    errorMessage.value = 'An error occurred while loading your account. Please try again.';
  }
}

function retry() {
  processSignIn();
}

function goToLogin() {
  window.location.href = '/clerk-login';
}

onMounted(() => {
  processSignIn();
});
</script>

<style scoped>
.handle-signin-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.loading-wrapper {
  font-size: 1.2rem;
  color: #666;
}

.error-wrapper {
  text-align: center;
  background: white;
  padding: 2.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  max-width: 400px;
}

.error-wrapper h3 {
  font-size: 1.5rem;
  margin-bottom: 0.75rem;
  color: #333;
}

.error-wrapper p {
  color: #666;
  margin-bottom: 1.5rem;
  font-size: 0.95rem;
}

.error-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: center;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-primary {
  background-color: #0a783c;
  color: white;
}

.btn-primary:hover {
  background-color: #086332;
}

.btn-secondary {
  background-color: #e9ecef;
  color: #333;
}

.btn-secondary:hover {
  background-color: #dee2e6;
}
</style>
