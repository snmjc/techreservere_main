// ===== AI GENERATED: routes =====
// Purpose: Define all application route definitions
// Inputs: None
// Returns: Array of route definition objects
// Flow:
// 1. Define route objects with path, name, component, meta
// 2. Export as array

/**
 * @constant {Array<Object>} routeDefinitions
 * @description Centralized route definitions for TechReserve application.
 */
export const routeDefinitions = [
  {
    path: '/',
    redirect: '/login',
  },
  {
    path: '/login',
    name: 'loginPage',
    component: () => import('@/pages/LoginPage.vue'),
    meta: {
      requiresAuth: false,
      allowedRoles: null,
    },
  },
  {
    path: '/admin/dashboard',
    name: 'adminDashboardPage',
    component: () => import('@/pages/AdminDashboardPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['admin'],
    },
  },
  // Placeholder routes for admin sidebar navigation (pages to be built)
  {
    path: '/admin/manage-accounts',
    name: 'adminManageAccountsPage',
    component: () => import('@/pages/AdminManageAccountsPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['admin'],
    },
  },
  {
    path: '/admin/manage-facilities',
    name: 'adminManageFacilitiesPage',
    component: () => import('@/pages/AdminManageFacilitiesPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['admin'],
    },
  },
  {
    path: '/admin/pending-requests',
    name: 'adminPendingRequestsPage',
    component: () => import('@/pages/AdminPendingRequestsPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['admin'],
    },
  },
  {
    path: '/admin/approved-requests',
    name: 'adminApprovedRequestsPage',
    component: () => import('@/pages/AdminApprovedRequestsPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['admin'],
    },
  },
  {
    path: '/admin/active-reservations',
    name: 'adminActiveReservationsPage',
    component: () => import('@/pages/AdminActiveReservationsPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['admin'],
    },
  },
  {
    path: '/admin/past-records',
    name: 'adminPastRecordsPage',
    component: () => import('@/pages/AdminDashboardPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['admin'],
    },
  },
  {
    path: '/admin/reports-analytics',
    name: 'adminReportsAnalyticsPage',
    component: () => import('@/pages/AdminReportsAnalyticsPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['admin'],
    },
  },
  // Borrower/Requester routes
  {
    path: '/borrower/my-reservations',
    name: 'borrowerMyReservationsPage',
    component: () => import('@/pages/BorrowerMyReservationsPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['borrower'],
    },
  },
  {
    path: '/borrower/create-reservation',
    name: 'borrowerCreateReservationPage',
    component: () => import('@/pages/BorrowerCreateReservationPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['borrower'],
    },
  },
  {
    path: '/borrower/create-reservation/venue',
    name: 'borrowerCreateReservationVenuePage',
    component: () => import('@/pages/BorrowerCreateReservationVenuePage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['borrower'],
    },
  },
  {
    path: '/borrower/create-reservation/documents',
    name: 'borrowerCreateReservationDocumentsPage',
    component: () => import('@/pages/BorrowerCreateReservationDocumentsPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['borrower'],
    },
  },
  {
    path: '/borrower/active-reservations',
    name: 'borrowerActiveReservationsPage',
    component: () => import('@/pages/BorrowerActiveReservationsPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['borrower'],
    },
  },
  {
    path: '/borrower/approved-requests',
    name: 'borrowerApprovedRequestsPage',
    component: () => import('@/pages/BorrowerApprovedRequestsPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['borrower'],
    },
  },
  {
    path: '/borrower/pending-requests',
    name: 'borrowerPendingRequestsPage',
    component: () => import('@/pages/BorrowerPendingRequestsPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['borrower'],
    },
  },
  {
    path: '/borrower/view-facilities',
    name: 'borrowerViewFacilitiesPage',
    component: () => import('@/pages/BorrowerViewFacilitiesPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['borrower'],
    },
  },
  {
    path: '/borrower/past-records',
    name: 'borrowerPastRecordsPage',
    component: () => import('@/pages/BorrowerPastRecordsPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['borrower'],
    },
  },
];
