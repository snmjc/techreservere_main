const AUTH_PAGE_NAMES = ['loginPage', 'signUpPage'];

export function resolveAccountStatus(authStore) {
  const account = authStore.clerkAccountData || authStore.accountData || {};
  const rawStatus = authStore.clerkAccountStatus ?? account.status ?? account.accountStatus ?? 'approved';
  const normalizedStatus = String(rawStatus || '').trim().toLowerCase();

  if (account.isActive === false || normalizedStatus === 'disabled') {
    return 'disabled';
  }

  return normalizedStatus;
}

export function getDashboardRouteForRole(userRole) {
  return userRole === 'ROLE_ADMIN'
    ? { name: 'adminDashboardPage' }
    : { name: 'borrowerMyReservationsPage' };
}

export function evaluatePublicRouteAccess({ toRoute, isSignedIn, accountStatus, userRole }) {
  if (accountStatus === 'disabled' && toRoute.name !== 'accountDeactivatedPage') {
    return { name: 'accountDeactivatedPage' };
  }

  if (AUTH_PAGE_NAMES.includes(toRoute.name) && isSignedIn && accountStatus === 'approved') {
    return getDashboardRouteForRole(userRole);
  }

  return true;
}

export function evaluateProtectedRouteAccess({ toRoute, isSignedIn, accountStatus, userRole }) {
  if (!isSignedIn && toRoute.name !== 'handleSignInPage') {
    return { name: 'clerkLoginPage' };
  }

  if (accountStatus === 'pending') {
    return toRoute.name === 'requestPendingPage'
      ? true
      : { name: 'requestPendingPage' };
  }

  if (accountStatus === 'disabled') {
    return { name: 'accountDeactivatedPage' };
  }

  if (accountStatus === 'rejected') {
    return { name: 'clerkLoginPage' };
  }

  const allowedRoles = toRoute.meta?.allowedRoles ?? null;
  if (allowedRoles !== null && !allowedRoles.includes(userRole)) {
    return getDashboardRouteForRole(userRole);
  }

  return true;
}
