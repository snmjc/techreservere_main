// ===== AI GENERATED: evaluateRouteAccessGuard =====
// Purpose: Centralized RBAC route guard evaluation
// Inputs: to (route), from (route)
// Returns: boolean or redirect object
// Flow:
// 1. Check if route requires authentication
// 2. Validate authentication state
// 3. Validate allowed roles
// 4. Redirect if unauthorized

import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { canAccessRbac } from '@/shared/constants/rbacPermissions.js';

/**
 * @function evaluateRouteAccessGuard
 * @description Evaluates route access based on authentication and role requirements.
 * @param {Object} toRoute - Target route object
 * @param {Object} fromRoute - Origin route object
 * @returns {boolean|Object} True to allow, or redirect object
 */
export function evaluateRouteAccessGuard(toRoute) {
  const requiresAuth = toRoute.meta?.requiresAuth ?? false;
  const authStore = useAuthenticationStore();

  const isSignedIn = authStore.isAuthenticated;
  const accountStatus = resolveAccountStatus(authStore);
  const userRole = authStore.userRole;

  console.log('[AccessGuard] Evaluating route:', toRoute.path, 'requiresAuth:', requiresAuth);
  console.log('[AccessGuard] Auth state:', { isSignedIn, accountStatus, userRole });

  // Public routes — allow access to login page even when signed in
  if (!requiresAuth) {
    if (accountStatus === 'disabled' && toRoute.name !== 'accountDeactivatedPage') {
      console.log('[AccessGuard] Disabled account, redirecting to deactivated page');
      return { name: 'accountDeactivatedPage' };
    }

    // Only redirect from old login/signup pages, not clerkLoginPage
    const authPages = ['loginPage', 'signUpPage'];
    if (authPages.includes(toRoute.name) && isSignedIn && accountStatus === 'approved') {
      console.log('[AccessGuard] Redirecting signed-in user from auth page to dashboard');
      return userRole === 'ROLE_ADMIN'
        ? { name: 'adminDashboardPage' }
        : { name: 'borrowerMyReservationsPage' };
    }
    console.log('[AccessGuard] Public route allowed');
    return true;
  }

  // Protected route — must be signed in
  // Allow handle-sign-in through so it can load account data from Clerk
  if (!isSignedIn && toRoute.name !== 'handleSignInPage') {
    console.log('[AccessGuard] Not signed in, redirecting to login');
    return { name: 'clerkLoginPage' };
  }

  // Pending users can only access request-pending page
  if (accountStatus === 'pending') {
    if (toRoute.name !== 'requestPendingPage') {
      console.log('[AccessGuard] Pending user, redirecting to request-pending');
      return { name: 'requestPendingPage' };
    }
    console.log('[AccessGuard] Pending user allowed on request-pending');
    return true;
  }

  // Disabled users can only view the deactivated notice page.
  if (accountStatus === 'disabled') {
    console.log('[AccessGuard] Disabled account, redirecting to deactivated page');
    return { name: 'accountDeactivatedPage' };
  }

  // Rejected users go back to login
  if (accountStatus === 'rejected') {
    console.log('[AccessGuard] Account rejected, redirecting to login');
    return { name: 'clerkLoginPage' };
  }

  // Check role
  const allowedRoles = toRoute.meta?.allowedRoles ?? null;
  if (allowedRoles !== null && !allowedRoles.includes(userRole)) {
    console.log('[AccessGuard] Role not allowed, redirecting to appropriate dashboard');
    return userRole === 'ROLE_ADMIN'
      ? { name: 'adminDashboardPage' }
      : { name: 'borrowerMyReservationsPage' };
  }

  console.log('[AccessGuard] Route allowed');
  return true;
}

function resolveAccountStatus(authStore) {
  const account = authStore.clerkAccountData || authStore.accountData || {};
  const rawStatus = authStore.clerkAccountStatus ?? account.status ?? account.accountStatus ?? 'approved';
  const normalizedStatus = String(rawStatus || '').trim().toLowerCase();

  if (account.isActive === false || normalizedStatus === 'disabled') {
    return 'disabled';
  }

  return normalizedStatus;
}
