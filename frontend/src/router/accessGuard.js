import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import {
  evaluateProtectedRouteAccess,
  evaluatePublicRouteAccess,
  resolveAccountStatus,
} from './routeAccessDecision.js';

export function evaluateRouteAccessGuard(toRoute) {
  const authStore = useAuthenticationStore();
  const routeContext = {
    toRoute,
    isSignedIn: authStore.isAuthenticated,
    accountStatus: resolveAccountStatus(authStore),
    userRole: authStore.userRole,
  };

  if (!(toRoute.meta?.requiresAuth ?? false)) {
    return evaluatePublicRouteAccess(routeContext);
  }

  return evaluateProtectedRouteAccess(routeContext);
}
