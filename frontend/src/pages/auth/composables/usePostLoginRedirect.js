import { watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth, useUser } from '@clerk/vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { verifyClerkLoginAccess } from '@/modules/authentication/services/clerkLoginAccessService.js';
import { getClerkToken, signOutClerk } from '@/modules/authentication/utils/clerkAuthUtils.js';
import { resolveRole } from '@/modules/authentication/utils/roleUtils.js';
import { apiUrl } from '@/shared/utils/apiBase.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';

export function usePostLoginRedirect() {
  const router = useRouter();
  const { isLoaded, isSignedIn, user } = useUser();
  const { getToken, signOut } = useAuth();
  const authStore = useAuthenticationStore();

  async function routeAfterLogin() {
    if (!isLoaded.value || !isSignedIn.value || !user.value) return;

    const token = await resolveClerkSessionToken(getToken);
    const emailAddress = user.value.primaryEmailAddress?.emailAddress || '';
    const roleDesignation = resolveRole(user.value.publicMetadata?.role, emailAddress);
    const accessCheck = await verifyClerkLoginAccess(emailAddress);

    if (!accessCheck.success) {
      await resetToLogin(authStore, signOut, router);
      return;
    }

    const backendAccount = await ensureBackendAccount(user.value, roleDesignation, token);

    if (!backendAccount) {
      await resetToLogin(authStore, signOut, router);
      return;
    }

    routeWithBackendAccount({
      authStore,
      router,
      token,
      backendAccount,
      clerkUser: user.value,
      emailAddress,
      roleDesignation,
    });
  }

  watch([isLoaded, isSignedIn, user], async ([loaded, signedIn, clerkUser]) => {
    if (!loaded) return;

    if (!signedIn) {
      if (authStore.accountData?.authProvider === 'clerk') {
        authStore.performLogout();
      }
      router.replace({ name: ROUTE_NAMES.clerkLogin });
      return;
    }

    if (!clerkUser) return;
    await routeAfterLogin();
  }, { immediate: true });
}

async function resolveClerkSessionToken(getToken) {
  try {
    return await getClerkToken(getToken);
  } catch {
    return null;
  }
}

async function ensureBackendAccount(clerkUser, roleDesignation, token) {
  try {
    const response = await fetch(apiUrl('/api/v1/users/register'), {
      method: 'POST',
      headers: buildHeaders(token),
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

function routeWithBackendAccount({ authStore, router, token, backendAccount, clerkUser, emailAddress, roleDesignation }) {
  const backendStatus = resolveBackendAccountStatus(backendAccount);
  authStore.setClerkAuth(token, buildClerkAuthAccount(backendAccount, clerkUser, emailAddress, roleDesignation, backendStatus));

  if (backendStatus === 'disabled') {
    router.replace({ name: ROUTE_NAMES.accountDeactivated });
  } else if (authStore.clerkAccountStatus === 'pending') {
    router.replace({ name: ROUTE_NAMES.requestPending });
  } else if (authStore.userRole === 'ROLE_ADMIN') {
    router.replace({ name: ROUTE_NAMES.adminDashboard });
  } else if (authStore.userRole === 'ROLE_STAFF') {
    router.replace({ name: ROUTE_NAMES.settings });
  } else {
    router.replace({ name: ROUTE_NAMES.borrowerMyReservations });
  }
}

function buildClerkAuthAccount(backendAccount, clerkUser, emailAddress, roleDesignation, backendStatus) {
  return {
    ...backendAccount,
    accountIdentifier: backendAccount?.accountIdentifier || clerkUser.id,
    clerkUserId: clerkUser.id,
    firstName: backendStatus === 'disabled' ? backendAccount?.firstName || clerkUser.firstName || '' : clerkUser.firstName || '',
    lastName: backendStatus === 'disabled' ? backendAccount?.lastName || clerkUser.lastName || '' : clerkUser.lastName || '',
    emailAddress,
    roleDesignation: backendAccount?.roleDesignation || roleDesignation,
    contactNumber: clerkUser.publicMetadata?.contactNumber || '',
    status: backendStatus,
    isApproved: backendAccount.isApproved === true,
    isActive: backendStatus === 'disabled' ? false : backendAccount.isActive !== false,
    authProvider: 'clerk',
  };
}

async function resetToLogin(authStore, signOut, router) {
  authStore.performLogout();
  await signOutClerk(signOut);
  router.replace({ name: ROUTE_NAMES.clerkLogin });
}

function buildHeaders(token) {
  const headers = { 'Content-Type': 'application/json' };
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }
  return headers;
}

function resolveBackendAccountStatus(account) {
  if (account?.isActive === false || String(account?.status || '').toLowerCase() === 'disabled') {
    return 'disabled';
  }

  if (account?.isApproved === true || ['approved', 'active', 'verified'].includes(String(account?.status || '').toLowerCase())) {
    return 'approved';
  }

  return 'pending';
}
