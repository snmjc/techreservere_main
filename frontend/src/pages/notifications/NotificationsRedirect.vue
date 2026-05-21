<template>
  <div style="padding: 2rem;">
    Redirecting…
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';

const router = useRouter();
const authStore = useAuthenticationStore();

onMounted(() => {
  const role = authStore.userRole;
  if (role === 'ROLE_ADMIN') {
    router.replace({ name: 'adminNotificationsPage' });
    return;
  }
  router.replace({ name: 'borrowerNotificationsPage' });
});
</script>

