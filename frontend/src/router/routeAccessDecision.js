import { ROUTE_NAMES } from '@/router/routeNames.js';

const AUTH_PAGE_NAMES = [ROUTE_NAMES.login, ROUTE_NAMES.signUp];

export function resolveAccountStatus(authStore) {
  const account = authStore.clerkAccountData || authStore.accountData || {};
  const rawStatus = authStore.clerkAccountStatus ?? account.status ?? account.accountStatus ?? 'pending';
  const normalizedStatus = String(rawStatus || '').trim().toLowerCase();

  if (account.isActive === false || normalizedStatus === 'disabled') {
    return 'disabled';
  }

  if (account.isApproved === false && normalizedStatus !== 'approved') {
    return 'pending';
  }

  return normalizedStatus;
}

export function getDashboardRouteForRole(userRole) {
  if (userRole === 'ROLE_ADMIN') return { name: ROUTE_NAMES.adminDashboard };
  if (userRole === 'ROLE_STAFF') return { name: ROUTE_NAMES.settings };
  return { name: ROUTE_NAMES.borrowerMyReservations };
}

export function evaluatePublicRouteAccess({ toRoute, isSignedIn, accountStatus, userRole }) {
  if (accountStatus === 'disabled' && toRoute.name !== ROUTE_NAMES.accountDeactivated) {
    return { name: ROUTE_NAMES.accountDeactivated };
  }

  if (AUTH_PAGE_NAMES.includes(toRoute.name) && isSignedIn && accountStatus === 'approved') {
    return getDashboardRouteForRole(userRole);
  }

  return true;
}

export function evaluateProtectedRouteAccess({ toRoute, isSignedIn, accountStatus, userRole }) {
  if (!isSignedIn && toRoute.name !== ROUTE_NAMES.handleSignIn) {
    return { name: ROUTE_NAMES.clerkLogin };
  }

  if (accountStatus === 'pending') {
    return toRoute.name === ROUTE_NAMES.requestPending
      ? true
      : { name: ROUTE_NAMES.requestPending };
  }

  if (accountStatus === 'disabled') {
    return { name: ROUTE_NAMES.accountDeactivated };
  }

  if (accountStatus === 'rejected') {
    return { name: ROUTE_NAMES.clerkLogin };
  }

  const allowedRoles = toRoute.meta?.allowedRoles ?? null;
  if (allowedRoles !== null && !allowedRoles.includes(userRole)) {
    return getDashboardRouteForRole(userRole);
  }

  return true;
}
