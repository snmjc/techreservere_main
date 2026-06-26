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
@import './css/AcceptInvitation.css';
</style>

