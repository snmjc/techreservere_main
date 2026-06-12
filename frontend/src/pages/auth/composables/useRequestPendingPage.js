import { computed, ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth, useUser } from '@clerk/vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { signOutClerk } from '@/modules/authentication/utils/clerkAuthUtils.js';
import { redirectToPostLogoutHome } from '@/modules/authentication/utils/logoutRedirect.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';

export function useRequestPendingPage() {
  const router = useRouter();
  const { user, isSignedIn } = useUser();
  const { signOut, getToken } = useAuth();
  const authStore = useAuthenticationStore();

  const isChecking = ref(false);
  const statusMessage = ref('');
  const statusMessageType = ref('info');

  const userEmail = computed(() => user.value?.primaryEmailAddress?.emailAddress || authStore.accountData?.emailAddress || '');
  const userName = computed(() => resolvePendingUserName(user.value, authStore));

  onMounted(() => {
    if (!isSignedIn.value && !authStore.accountData) {
      router.push({ name: ROUTE_NAMES.clerkLogin });
    }
  });

  async function handleLogout() {
    try {
      await signOutClerk(signOut);
    } finally {
      authStore.performLogout();
      redirectToPostLogoutHome();
    }
  }

  async function checkApprovalStatus() {
    startApprovalStatusCheck(statusMessage, statusMessageType, isChecking);

    try {
      const account = await authStore.loadClerkAccount(getToken);
      handleApprovalStatusResult(account, router, statusMessage, statusMessageType);
    } catch (error) {
      console.error('Error checking approval status:', error);
      setStatusMessage(statusMessage, statusMessageType, 'error', 'Unable to check approval status. Please try again later.');
    } finally {
      isChecking.value = false;
    }
  }

  return {
    isChecking,
    statusMessage,
    statusMessageType,
    userEmail,
    userName,
    handleLogout,
    checkApprovalStatus,
  };
}

function resolvePendingUserName(user, authStore) {
  const clerkName = `${user?.firstName || ''} ${user?.lastName || ''}`.trim();
  return clerkName || authStore.userFullName || '';
}

function startApprovalStatusCheck(statusMessage, statusMessageType, isChecking) {
  isChecking.value = true;
  statusMessage.value = '';
  statusMessageType.value = 'info';
}

function handleApprovalStatusResult(account, router, statusMessage, statusMessageType) {
  if (!account) {
    setStatusMessage(statusMessage, statusMessageType, 'error', 'Unable to verify your account right now. Please try again later.');
    return;
  }

  const status = String(account.status || '').toLowerCase();
  const role = String(account.roleDesignation || '').toUpperCase();

  if (isApprovedAccount(account, status)) {
    router.push({ name: resolveApprovedRouteName(role) });
    return;
  }

  if (isRejectedAccount(status)) {
    setStatusMessage(statusMessage, statusMessageType, 'error', 'Your registration was not approved. Please contact the administrator.');
    return;
  }

  setStatusMessage(statusMessage, statusMessageType, 'info', 'Your account is still pending approval. Please check again later.');
}

function isApprovedAccount(account, status) {
  return status === 'approved' || status === 'accepted' || account.isApproved === true;
}

function isRejectedAccount(status) {
  return status === 'rejected' || status === 'denied';
}

function resolveApprovedRouteName(role) {
  return role === 'ROLE_ADMIN' || role === 'ADMIN'
    ? ROUTE_NAMES.adminDashboard
    : ROUTE_NAMES.dashboard;
}

function setStatusMessage(statusMessage, statusMessageType, type, message) {
  statusMessageType.value = type;
  statusMessage.value = message;
}
