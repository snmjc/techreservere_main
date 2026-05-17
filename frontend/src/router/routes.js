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
    redirect: '/clerk-login',
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
    path: '/clerk-login',
    name: 'clerkLoginPage',
    component: () => import('@/pages/auth/ClerkLogin.vue'),
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
    path: '/request-pending',
    name: 'requestPendingPage',
    component: () => import('@/pages/auth/RequestPending.vue'),
    meta: {
      requiresAuth: false,
      allowedRoles: null,
    },
  },
  {
    path: '/custom-signup',
    name: 'customSignUpPage',
    component: () => import('@/pages/auth/CustomSignUp.vue'),
    meta: {
      requiresAuth: false,
      allowedRoles: null,
    },
  },
  {
    path: '/student-registration',
    name: 'studentRegistrationPage',
    component: () => import('@/pages/auth/StudentRegistration.vue'),
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
  {
    path: '/admin/requestor-approval',
    name: 'requestorApprovalPage',
    component: () => import('@/pages/admin/RequestorApproval.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
    },
  },
  // Placeholder routes for admin sidebar navigation (pages to be built)
  {
    path: '/admin/users',
    name: 'adminUsersPage',
    component: () => import('@/pages/admin/AdminUsers.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
    },
  },
  {
    path: '/admin/invitations',
    name: 'adminInvitationsPage',
    component: () => import('@/pages/admin/AdminInvitations.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
    },
  },
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
    component: () => import('@/pages/admin/Facilities.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
    },
  },
  {
    path: '/admin/manage-equipment',
    name: 'adminManageEquipmentPage',
    component: () => import('@/pages/admin/Equipment.vue'),
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
  {
    path: '/borrower/view-reservation-list',
    name: 'borrowerViewReservationListPage',
    component: () => import('@/pages/borrower/ViewReservationList.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
    },
  },
  {
    path: '/borrower/active-reservations-logs',
    name: 'borrowerActiveReservationsLogsPage',
    component: () => import('@/pages/borrower/ActiveReservationsLogs.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
    },
  },
  {
    path: '/borrower/approved-requests-logs',
    name: 'borrowerApprovedRequestsLogsPage',
    component: () => import('@/pages/borrower/ApprovedRequestsLogs.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
    },
  },
  {
    path: '/borrower/pending-requests-logs',
    name: 'borrowerPendingRequestsLogsPage',
    component: () => import('@/pages/borrower/PendingRequestsLogs.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
    },
  },
  {
    path: '/borrower/completed-reservations-logs',
    name: 'borrowerCompletedReservationsLogsPage',
    component: () => import('@/pages/borrower/CompletedReservationsLogs.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
    },
  },
  {
    path: '/notifications',
    name: 'notificationPage',
    component: () => import('@/pages/notifications/NotificationPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER', 'ROLE_ADMIN'],
    },
  },
  {
    path: '/borrower/notifications',
    name: 'borrowerNotificationsPage',
    component: () => import('@/pages/borrower/BorrowerNotifications.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
    },
  },
  // Settings routes
  {
    path: '/settings',
    name: 'settingsPage',
    component: () => import('@/pages/settings/SettingsPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER', 'ROLE_ADMIN'],
    },
  },
  {
    path: '/settings/account',
    name: 'accountSettingsPage',
    component: () => import('@/pages/settings/SettingsPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER', 'ROLE_ADMIN'],
    },
  },
  {
    path: '/settings/security',
    name: 'securitySettingsPage',
    component: () => import('@/pages/settings/SettingsPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER', 'ROLE_ADMIN'],
    },
  },
  {
    path: '/settings/preferences',
    name: 'preferencesSettingsPage',
    component: () => import('@/pages/settings/SettingsPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER', 'ROLE_ADMIN'],
    },
  },
  {
    path: '/borrower/settings',
    name: 'borrowerSettingsPage',
    component: () => import('@/pages/settings/SettingsPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
    },
  },
];
