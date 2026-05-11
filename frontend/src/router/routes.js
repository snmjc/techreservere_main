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
    component: () => import('@/pages/auth/Login.vue'),
    meta: {
      requiresAuth: false,
      allowedRoles: null,
    },
  },
  {
    path: '/signup',
    name: 'signUpPage',
    component: () => import('@/pages/auth/SignUp.vue'),
    meta: {
      requiresAuth: false,
      allowedRoles: null,
    },
  },
  {
    path: '/admin/dashboard',
    name: 'adminDashboardPage',
    component: () => import('@/pages/admin/Dashboard.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
    },
  },
  // Placeholder routes for admin sidebar navigation (pages to be built)
  {
    path: '/admin/manage-accounts',
    name: 'adminManageAccountsPage',
    component: () => import('@/pages/admin/ManageAccounts.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
    },
  },
  {
    path: '/admin/manage-facilities',
    name: 'adminManageFacilitiesPage',
    component: () => import('@/pages/admin/ManageFacilities.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
    },
  },
  {
    path: '/admin/pending-requests',
    name: 'adminPendingRequestsPage',
    component: () => import('@/pages/admin/PendingRequests.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
    },
  },
  {
    path: '/admin/approved-requests',
    name: 'adminApprovedRequestsPage',
    component: () => import('@/pages/admin/ApprovedRequests.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
    },
  },
  {
    path: '/admin/active-reservations',
    name: 'adminActiveReservationsPage',
    component: () => import('@/pages/admin/ActiveReservations.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
    },
  },
  {
    path: '/admin/past-records',
    name: 'adminPastRecordsPage',
    component: () => import('@/pages/admin/PastRecords.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
    },
  },
  {
    path: '/admin/reports-analytics',
    name: 'adminReportsAnalyticsPage',
    component: () => import('@/pages/admin/ReportsAnalytics.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
    },
  },
  // Borrower/Requester routes
  {
    path: '/borrower/my-reservations',
    name: 'borrowerMyReservationsPage',
    component: () => import('@/pages/borrower/MyReservations.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
    },
  },
  {
    path: '/borrower/create-reservation',
    name: 'borrowerCreateReservationPage',
    component: () => import('@/pages/borrower/CreateReservation.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
    },
  },
  {
    path: '/borrower/create-reservation/venue',
    name: 'borrowerCreateReservationVenuePage',
    component: () => import('@/pages/borrower/CreateReservationVenue.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
    },
  },
  {
    path: '/borrower/create-reservation/documents',
    name: 'borrowerCreateReservationDocumentsPage',
    component: () => import('@/pages/borrower/CreateReservationDocuments.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
    },
  },
  {
    path: '/borrower/active-reservations',
    name: 'borrowerActiveReservationsPage',
    component: () => import('@/pages/borrower/ActiveReservations.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
    },
  },
  {
    path: '/borrower/approved-requests',
    name: 'borrowerApprovedRequestsPage',
    component: () => import('@/pages/borrower/ApprovedRequests.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
    },
  },
  {
    path: '/borrower/pending-requests',
    name: 'borrowerPendingRequestsPage',
    component: () => import('@/pages/borrower/PendingRequests.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
    },
  },
  {
    path: '/borrower/view-facilities',
    name: 'borrowerViewFacilitiesPage',
    component: () => import('@/pages/borrower/ViewFacilities.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
    },
  },
  {
    path: '/borrower/past-records',
    name: 'borrowerPastRecordsPage',
    component: () => import('@/pages/borrower/PastRecords.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
    },
  },
];
