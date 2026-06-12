// ===== AI GENERATED: router/index =====
// Purpose: Initialize Vue Router and attach access guard
// Inputs: None
// Returns: Router instance
// Flow:
// 1. Create router with route definitions
// 2. Attach beforeEach guard
// 3. Export router instance

import { createRouter, createWebHistory } from 'vue-router';
import { routeDefinitions } from './routes.js';
import { evaluateRouteAccessGuard } from './accessGuard.js';

/**
 * @constant {Object} applicationRouter
 * @description Vue Router instance for TechReserve application.
 */
const applicationRouter = createRouter({
  history: createWebHistory(),
  routes: routeDefinitions,
});

applicationRouter.beforeEach((toRoute, fromRoute) => {
  return evaluateRouteAccessGuard(toRoute, fromRoute);
});

applicationRouter.onError((error, toRoute) => {
  const message = String(error?.message || '');
  const isDynamicImportFailure = /Failed to fetch dynamically imported module|Importing a module script failed|Loading chunk [\w-]+ failed/i.test(message);

  if (!isDynamicImportFailure || typeof window === 'undefined') {
    console.error('[Router] Navigation error:', error);
    return;
  }

  const reloadKey = 'techreserve:chunk-reload';
  const previousReloadTarget = window.sessionStorage.getItem(reloadKey);
  const nextReloadTarget = toRoute?.fullPath || window.location.pathname || '/';

  if (previousReloadTarget === nextReloadTarget) {
    window.sessionStorage.removeItem(reloadKey);
    console.error('[Router] Dynamic import failed after retry:', error);
    return;
  }

  window.sessionStorage.setItem(reloadKey, nextReloadTarget);
  window.location.assign(nextReloadTarget);
});

export default applicationRouter;
