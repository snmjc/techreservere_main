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
import { getClerkToken, signOutClerk } from '@/modules/authentication/utils/clerkAuthUtils.js';
import { resolveRole } from '@/modules/authentication/utils/roleUtils.js';
import { apiUrl } from '@/shared/utils/apiBase.js';

const router = useRouter();
const { isLoaded, isSignedIn, user } = useUser();
const { getToken, signOut } = useAuth();
const authStore = useAuthenticationStore();

function resolveBackendAccountStatus(account) {
  if (account?.isActive === false || String(account?.status || '').toLowerCase() === 'disabled') {
    return 'disabled';
  }

  if (account?.isApproved === true || ['approved', 'active', 'verified'].includes(String(account?.status || '').toLowerCase())) {
    return 'approved';
  }

  return 'pending';
}

async function ensureBackendAccount(clerkUser, roleDesignation, token) {
  try {
    const headers = {
      'Content-Type': 'application/json',
    };

    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }

    const response = await fetch(apiUrl('/api/v1/users/register'), {
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

    const result = await response.json().catch(() => ({}));
    return result?.data?.account ?? null;
  } catch (error) {
    console.error('[PostLogin] Failed to ensure backend account:', error);
    return null;
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

  const backendAccount = await ensureBackendAccount(user.value, roleDesignation, token);
  if (!backendAccount) {
    authStore.performLogout();
    await signOutClerk(signOut);
    router.replace({ name: 'clerkLoginPage' });
    return;
  }
  const backendStatus = resolveBackendAccountStatus(backendAccount);

  if (backendStatus === 'disabled') {
    authStore.setClerkAuth(token, {
      ...backendAccount,
      accountIdentifier: backendAccount?.accountIdentifier || user.value.id,
      clerkUserId: user.value.id,
      firstName: backendAccount?.firstName || user.value.firstName || '',
      lastName: backendAccount?.lastName || user.value.lastName || '',
      emailAddress,
      roleDesignation: backendAccount?.roleDesignation || roleDesignation,
      status: 'disabled',
      isApproved: backendAccount.isApproved === true,
      isActive: false,
      authProvider: 'clerk',
    });
    router.replace({ name: 'accountDeactivatedPage' });
    return;
  }

  authStore.setClerkAuth(token, {
    ...backendAccount,
    accountIdentifier: backendAccount?.accountIdentifier || user.value.id,
    clerkUserId: user.value.id,
    firstName: user.value.firstName || '',
    lastName: user.value.lastName || '',
    emailAddress,
    roleDesignation: backendAccount?.roleDesignation || roleDesignation,
    contactNumber: user.value.publicMetadata?.contactNumber || '',
    status: backendStatus,
    isApproved: backendAccount.isApproved === true,
    isActive: backendAccount.isActive !== false,
    authProvider: 'clerk',
  });

  if (authStore.clerkAccountStatus === 'pending') {
    router.replace({ name: 'requestPendingPage' });
  } else if (authStore.userRole === 'ROLE_ADMIN') {
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
