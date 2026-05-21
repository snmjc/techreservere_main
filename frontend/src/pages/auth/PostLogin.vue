<template>
  <div class="post-login">
    <p>Signing you in...</p>
  </div>
</template>

<script setup>
import { watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth, useUser } from '@clerk/vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { getClerkToken } from '@/modules/authentication/utils/clerkAuthUtils.js';
import { resolveRole } from '@/modules/authentication/utils/roleUtils.js';

const router = useRouter();
const { isLoaded, isSignedIn, user } = useUser();
const { getToken } = useAuth();
const authStore = useAuthenticationStore();

async function ensureBackendAccount(clerkUser, roleDesignation, token) {
  try {
    const headers = {
      'Content-Type': 'application/json',
    };

    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }

    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/v1/users/register`, {
      method: 'POST',
      headers,
      body: JSON.stringify({
        clerkUserId: clerkUser.id,
        firstName: clerkUser.firstName || '',
        lastName: clerkUser.lastName || '',
        emailAddress: clerkUser.primaryEmailAddress?.emailAddress || '',
        role: roleDesignation,
        contactNumber: clerkUser.publicMetadata?.contactNumber || '',
        department: clerkUser.publicMetadata?.department || '',
      }),
    });

    if (!response.ok) {
      const responseText = await response.text();
      throw new Error(`Backend account registration failed with ${response.status}: ${responseText}`);
    }
  } catch (error) {
    console.error('[PostLogin] Failed to ensure backend account:', error);
  }
}

async function routeAfterLogin() {
  if (!isLoaded.value || !isSignedIn.value || !user.value) return;

  let token = null;
  try {
    token = await getClerkToken(getToken);
  } catch (e) {
    token = null;
  }

  const emailAddress = user.value.primaryEmailAddress?.emailAddress || '';
  const roleDesignation = resolveRole(user.value.publicMetadata?.role, emailAddress);

  await ensureBackendAccount(user.value, roleDesignation, token);

  authStore.setClerkAuth(token, {
    accountIdentifier: user.value.id,
    firstName: user.value.firstName || '',
    lastName: user.value.lastName || '',
    emailAddress,
    roleDesignation,
    contactNumber: user.value.publicMetadata?.contactNumber || '',
    isActive: true,
    authProvider: 'clerk',
  });

  if (authStore.userRole === 'ROLE_ADMIN') {
    router.replace({ name: 'adminDashboardPage' });
  } else {
    router.replace({ name: 'borrowerMyReservationsPage' });
  }
}

watch([isLoaded, isSignedIn, user], async ([loaded, signedIn, clerkUser]) => {
  if (!loaded) return;

  if (!signedIn) {
    if (authStore.accountData?.authProvider === 'clerk') {
      authStore.performLogout();
    }
    router.replace({ name: 'clerkLoginPage' });
    return;
  }

  if (!clerkUser) return;
  await routeAfterLogin();
}, { immediate: true });
</script>

<style scoped>
.post-login {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.05rem;
  color: #555;
}
</style>
