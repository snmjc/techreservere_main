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
export function evaluateRouteAccessGuard(toRoute, fromRoute) {
  const requiresAuth = toRoute.meta?.requiresAuth ?? false;
  const authStore = useAuthenticationStore();

  // If a Clerk-signed-in user hits the login route, push them to their default dashboard.
  if (!requiresAuth) {
    if ((toRoute.name === 'loginPage' || toRoute.name === 'clerkLoginPage') && authStore.isAuthenticated) {
      if (authStore.userRole === 'ROLE_ADMIN') {
        return { name: 'adminDashboardPage' };
      }
      return { name: 'borrowerMyReservationsPage' };
    }
    return true;
  }

  if (!authStore.isAuthenticated) {
    return { name: 'clerkLoginPage' };
  }

  const allowedRoles = toRoute.meta?.allowedRoles ?? null;

  if (allowedRoles !== null) {
    const roleAllowed = allowedRoles.includes(authStore.userRole);

    if (!roleAllowed) {
      if (authStore.userRole === 'ROLE_ADMIN') {
        return { name: 'adminDashboardPage' };
      }
      if (authStore.userRole === 'ROLE_BORROWER') {
        return { name: 'borrowerMyReservationsPage' };
      }
      return { name: 'clerkLoginPage' };
    }
  }

  if (!canAccessRbac(authStore.userRole, toRoute.meta?.rbac)) {
    if (authStore.userRole === 'ROLE_ADMIN') {
      return { name: 'adminDashboardPage' };
    }
    if (authStore.userRole === 'ROLE_BORROWER') {
      return { name: 'borrowerMyReservationsPage' };
    }
    return { name: 'clerkLoginPage' };
  }

  return true;
}
