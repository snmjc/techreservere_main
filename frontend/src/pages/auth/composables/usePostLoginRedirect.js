import { watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth, useUser } from '@clerk/vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { getClerkToken, signOutClerk } from '@/modules/authentication/utils/clerkAuthUtils.js';
import { consumePendingRememberSession } from '@/modules/authentication/utils/authStorage.js';
import { resolveRole } from '@/modules/authentication/utils/roleUtils.js';
import { apiUrl } from '@/shared/utils/apiBase.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';

export function usePostLoginRedirect() {
  const router = useRouter();
  const { isLoaded, isSignedIn, user } = useUser();
  const { getToken, signOut } = useAuth();
  const authStore = useAuthenticationStore();
  const rememberSession = consumePendingRememberSession();

  async function routeAfterLogin() {
    if (!isLoaded.value || !isSignedIn.value || !user.value) return;

    const token = await resolveClerkSessionToken(getToken);
    const emailAddress = user.value.primaryEmailAddress?.emailAddress || '';
    const roleDesignation = resolveRole(user.value.publicMetadata?.role, emailAddress);
    const backendRegistration = await ensureBackendAccount(user.value, roleDesignation, token);

    if (!backendRegistration.account) {
      await resetToLogin(
        authStore,
        signOut,
        router,
        backendRegistration.error || 'Unable to sign you in with TechReserve right now.'
      );
      return;
    }

    routeWithBackendAccount({
      authStore,
      router,
      token,
      rememberSession,
      backendAccount: backendRegistration.account,
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
        contactNumber: clerkUser.publicMetadata?.contactNumber || clerkUser.publicMetadata?.techreserve_contact_number || '',
        department: clerkUser.publicMetadata?.department || clerkUser.publicMetadata?.techreserve_department || '',
        idNumber: clerkUser.publicMetadata?.idNumber || clerkUser.publicMetadata?.techreserve_id_number || '',
      }),
    });

    if (!response.ok) {
      const result = await response.json().catch(() => ({}));
      return {
        account: null,
        error: result?.errorMessage || result?.message || 'Your TechReserve account could not be activated yet.',
      };
    }

    const result = await response.json().catch(() => ({}));
    return {
      account: result?.data?.account ?? null,
      error: '',
    };
  } catch (error) {
    console.error('[PostLogin] Failed to ensure backend account:', error);
    return {
      account: null,
      error: error?.message || 'Unable to complete TechReserve sign-in.',
    };
  }
}

function routeWithBackendAccount({ authStore, router, token, backendAccount, clerkUser, emailAddress, roleDesignation, rememberSession }) {
  const backendStatus = resolveBackendAccountStatus(backendAccount);
  authStore.setClerkAuth(
    token,
    buildClerkAuthAccount(backendAccount, clerkUser, emailAddress, roleDesignation, backendStatus),
    { rememberSession }
  );

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

async function resetToLogin(authStore, signOut, router, errorMessage = '') {
  authStore.performLogout();
  await signOutClerk(signOut);
  router.replace({
    name: ROUTE_NAMES.clerkLogin,
    query: errorMessage ? { error: errorMessage } : undefined,
  });
}

function buildHeaders(token) {
  const headers = { 'Content-Type': 'application/json' };
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }
  return headers;
}

function resolveBackendAccountStatus(account) {
  const normalizedStatus = String(account?.status || '').toLowerCase();

  if (account?.isActive === false || normalizedStatus === 'disabled') {
    return 'disabled';
  }

  if (normalizedStatus === 'accepted') {
    return 'accepted';
  }

  if (account?.isApproved === true || ['approved', 'active', 'verified'].includes(normalizedStatus)) {
    return 'approved';
  }

  return 'pending';
}
