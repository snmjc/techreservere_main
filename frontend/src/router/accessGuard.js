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
export function evaluateRouteAccessGuard(toRoute, fromRoute) {
  const requiresAuth = toRoute.meta?.requiresAuth ?? false;
  const authStore = useAuthenticationStore();

  if (!requiresAuth) {
    if (toRoute.name === 'loginPage' && authStore.isAuthenticated) {
      if (authStore.userRole === 'ROLE_ADMIN') {
        return { name: 'adminDashboardPage' };
      }
      return { name: 'borrowerMyReservationsPage' };
    }
    return true;
  }

  if (!authStore.isAuthenticated) {
    return { name: 'loginPage' };
  }

  const allowedRoles = toRoute.meta?.allowedRoles ?? null;

  if (allowedRoles !== null) {
    const roleAllowed = allowedRoles.includes(authStore.userRole);

    if (!roleAllowed) {
      return { name: 'loginPage' };
    }
  }

  return true;
}
