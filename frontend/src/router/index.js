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

export default applicationRouter;
