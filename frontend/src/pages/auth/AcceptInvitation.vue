<template>
  <div class="accept-invitation-page">
    <div class="accept-invitation-card">
      <div class="accept-invitation-spinner" />
      <h1>Opening your workspace...</h1>
      <p v-if="message">{{ message }}</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';
import { apiUrl } from '@/shared/utils/apiBase.js';

const route = useRoute();
const router = useRouter();
const authStore = useAuthenticationStore();
const message = ref('We are signing you in directly from your invitation link.');

onMounted(async () => {
  const token = String(route.query.token || '').trim();

  if (token === '') {
    router.replace({
      name: ROUTE_NAMES.clerkLogin,
      query: { error: 'Invitation link is missing its token. Please request a new invite.' },
    });
    return;
  }

  try {
    const response = await fetch(apiUrl('/api/v1/invitations/accept'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token }),
    });

    const result = await response.json().catch(() => ({}));
    if (!response.ok || !result?.data?.token || !result?.data?.account) {
      throw new Error(result?.errorMessage || result?.message || 'This invitation could not be accepted.');
    }

    authStore.setLocalAuth(result.data.token, result.data.account, { rememberSession: true });

    const role = String(result.data.account.roleDesignation || '').toUpperCase();
    if (role === 'ROLE_ADMIN') {
      router.replace({ name: ROUTE_NAMES.adminDashboard });
    } else if (role === 'ROLE_STAFF') {
      router.replace({ name: ROUTE_NAMES.employeeDashboard });
    } else {
      router.replace({ name: ROUTE_NAMES.dashboard });
    }
  } catch (error) {
    router.replace({
      name: ROUTE_NAMES.clerkLogin,
      query: { error: error?.message || 'This invitation could not be accepted. Please request a new one.' },
    });
  }
});
</script>

<style scoped>
.accept-invitation-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  background: linear-gradient(135deg, #0d5a3a, #0a783c);
}

.accept-invitation-card {
  width: min(100%, 420px);
  padding: 2rem 1.5rem;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.18);
  text-align: center;
}

.accept-invitation-card h1 {
  margin: 0 0 0.65rem;
  color: #111827;
  font-size: 1.7rem;
  font-weight: 900;
}

.accept-invitation-card p {
  margin: 0;
  color: #4b5563;
  font-size: 0.95rem;
  font-weight: 700;
  line-height: 1.5;
}

.accept-invitation-spinner {
  width: 38px;
  height: 38px;
  margin: 0 auto 1rem;
  border: 4px solid rgba(8, 120, 74, 0.16);
  border-top-color: #08784a;
  border-radius: 999px;
  animation: accept-invitation-spin 0.8s linear infinite;
}

@keyframes accept-invitation-spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
