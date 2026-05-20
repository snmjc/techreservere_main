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

  const isSignedIn = authStore.clerkIsSignedIn;
  const accountStatus = authStore.clerkAccountStatus;
  const userRole = authStore.clerkUserRole;

  console.log('[AccessGuard] Evaluating route:', toRoute.path, 'requiresAuth:', requiresAuth);
  console.log('[AccessGuard] Auth state:', { isSignedIn, accountStatus, userRole });

  // Public routes — allow access to login page even when signed in
  if (!requiresAuth) {
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

  // Rejected users go back to login
  if (accountStatus === 'rejected') {
    console.log('[AccessGuard] Rejected user, redirecting to login');
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
