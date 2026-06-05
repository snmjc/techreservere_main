// ===== AI GENERATED: routes =====
// Purpose: Define all application route definitions
// Inputs: None
// Returns: Array of route definition objects
// Flow:
// 1. Define route objects with path, name, component, meta
// 2. Export as array

import { RBAC_ACTION, RBAC_CAPABILITY, RBAC_SCOPE } from '@/shared/constants/rbacPermissions.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';

const rbacAny = (...permissions) => ({ any: permissions });
const permission = (capability, action, scope = RBAC_SCOPE.ALL) => ({ capability, action, scope });

/**
 * @constant {Array<Object>} routeDefinitions
 * @description Centralized route definitions for TechReserve application.
 */
export const routeDefinitions = [
  {
    path: '/',
    redirect: { name: ROUTE_NAMES.clerkLogin },
  },
  {
    path: '/login',
    redirect: '/clerk-login',
  },
  {
    path: '/accept-invitation',
    redirect: (toRoute) => ({
      name: ROUTE_NAMES.clerkLogin,
      query: toRoute.query,
      hash: toRoute.hash,
    }),
  },
  {
    path: '/accept-invite',
    redirect: (toRoute) => ({
      name: ROUTE_NAMES.clerkLogin,
      query: toRoute.query,
      hash: toRoute.hash,
    }),
  },
  {
    path: '/clerk-login',
    name: ROUTE_NAMES.clerkLogin,
    component: () => import('@/pages/auth/ClerkLogin.vue'),
    meta: {
      requiresAuth: false,
      allowedRoles: null,
    },
  },
  {
    path: '/clerk-login/:pathMatch(.*)*',
    name: ROUTE_NAMES.clerkLogin,
    component: () => import('@/pages/auth/ClerkLogin.vue'),
    meta: {
      requiresAuth: false,
      allowedRoles: null,
    },
  },
  {
    path: '/auth/post-login',
    name: ROUTE_NAMES.postLogin,
    component: () => import('@/pages/auth/PostLogin.vue'),
    meta: {
      requiresAuth: false,
      allowedRoles: null,
    },
  },
  {
    path: '/account-deactivated',
    name: ROUTE_NAMES.accountDeactivated,
    component: () => import('@/pages/auth/AccountDeactivated.vue'),
    meta: {
      requiresAuth: false,
      allowedRoles: null,
    },
  },
  {
    path: '/signup',
    name: ROUTE_NAMES.signUp,
    redirect: '/sign-up',
    meta: {
      requiresAuth: false,
      allowedRoles: null,
    },
  },
  {
    path: '/request-pending',
    name: ROUTE_NAMES.requestPending,
    component: () => import('@/pages/auth/RequestPending.vue'),
    meta: {
      requiresAuth: false,
      allowedRoles: null,
    },
  },
  {
    path: '/dashboard',
    name: ROUTE_NAMES.dashboard,
    component: () => import('@/pages/borrower/MyReservations.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.VIEW_RESERVATIONS, RBAC_ACTION.READ, RBAC_SCOPE.OWN)),
    },
  },
  {
    path: '/sign-up',
    name: ROUTE_NAMES.customSignUp,
    component: () => import('@/pages/auth/CustomSignUp.vue'),
    beforeEnter: (toRoute) => {
      const queryKeys = Object.keys(toRoute.query || {});
      const hasInvitationQuery = queryKeys.some((key) => /clerk|ticket|invitation|redirect/i.test(key));
      if (hasInvitationQuery) {
        return {
          name: ROUTE_NAMES.clerkLogin,
          query: toRoute.query,
          hash: toRoute.hash,
        };
      }
      return true;
    },
    meta: {
      requiresAuth: false,
      allowedRoles: null,
    },
  },
  {
    path: '/custom-signup',
    redirect: '/sign-up',
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
    name: ROUTE_NAMES.adminDashboard,
    component: () => import('@/pages/admin/Dashboard.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.VIEW_DASHBOARD, RBAC_ACTION.READ)),
    },
  },
  {
    path: '/admin/requestor-approval',
    name: 'requestorApprovalPage',
    component: () => import('@/pages/admin/RequestorApproval.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.ACCOUNT_MANAGEMENT, RBAC_ACTION.UPDATE)),
    },
  },
  {
    path: '/admin/wishlist',
    alias: '/admin/requests-hub',
    name: 'adminWishlistPage',
    component: () => import('@/pages/admin/AdminWishlist.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.ACCOUNT_MANAGEMENT, RBAC_ACTION.UPDATE)),
    },
  },
  {
    path: '/admin/users',
    name: 'adminUsersPage',
    component: () => import('@/pages/admin/AdminUsers.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.ACCOUNT_MANAGEMENT, RBAC_ACTION.READ)),
    },
  },
  {
    path: '/admin/invitations',
    name: 'adminInvitationsPage',
    redirect: '/admin/wishlist',
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.ACCOUNT_MANAGEMENT, RBAC_ACTION.CREATE)),
    },
  },
  {
    path: '/admin/task-assignments',
    name: 'adminTaskAssignmentsPage',
    component: () => import('@/pages/admin/AdminTaskAssignments.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
      rbac: rbacAny(
        permission(RBAC_CAPABILITY.READ_TASK, RBAC_ACTION.READ),
        permission(RBAC_CAPABILITY.CREATE_TASK, RBAC_ACTION.CREATE),
        permission(RBAC_CAPABILITY.UPDATE_TASK, RBAC_ACTION.UPDATE, RBAC_SCOPE.SPECIFIC_ASSIGNED_ADMIN),
        permission(RBAC_CAPABILITY.ASSIGN_TASK, RBAC_ACTION.UPDATE),
      ),
    },
  },
  {
    path: '/admin/manage-accounts',
    name: 'adminManageAccountsPage',
    component: () => import('@/pages/admin/ManageAccounts.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.ACCOUNT_MANAGEMENT, RBAC_ACTION.READ)),
    },
  },
  {
    path: '/admin/manage-facilities',
    name: 'adminManageFacilitiesPage',
    component: () => import('@/pages/admin/Facilities.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.MANAGE_VENUE, RBAC_ACTION.READ)),
    },
  },
  {
    path: '/admin/manage-equipment',
    name: 'adminManageEquipmentPage',
    component: () => import('@/pages/admin/Equipment.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.MANAGE_EQUIPMENT, RBAC_ACTION.READ)),
    },
  },
  {
    path: '/admin/pending-requests',
    name: 'adminPendingRequestsPage',
    component: () => import('@/pages/admin/PendingRequests.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.VIEW_RESERVATIONS, RBAC_ACTION.READ)),
    },
  },
  {
    path: '/admin/approved-requests',
    name: 'adminApprovedRequestsPage',
    component: () => import('@/pages/admin/ApprovedRequests.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.UPDATE_RESERVATION_STATUS, RBAC_ACTION.UPDATE)),
    },
  },
  {
    path: '/admin/active-reservations',
    name: 'adminActiveReservationsPage',
    component: () => import('@/pages/admin/ActiveReservations.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.PROCESS_DEPLOYMENT_RETURN, RBAC_ACTION.READ)),
    },
  },
  {
    path: '/admin/past-records',
    name: 'adminPastRecordsPage',
    component: () => import('@/pages/admin/PastRecords.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.VIEW_RESERVATIONS, RBAC_ACTION.READ)),
    },
  },
  {
    path: '/admin/reports-analytics',
    name: 'adminReportsAnalyticsPage',
    component: () => import('@/pages/admin/ReportsAnalytics.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_ADMIN'],
      rbac: rbacAny(
        permission(RBAC_CAPABILITY.VIEW_FORECAST_RISK, RBAC_ACTION.READ),
        permission(RBAC_CAPABILITY.EXPORT_REPORTS, RBAC_ACTION.READ),
        permission(RBAC_CAPABILITY.ANALYTICS_CONFIGURATION, RBAC_ACTION.READ),
      ),
    },
  },
  // Borrower/Requester routes
  {
    path: '/borrower/my-reservations',
    name: ROUTE_NAMES.borrowerMyReservations,
    component: () => import('@/pages/borrower/MyReservations.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.VIEW_RESERVATIONS, RBAC_ACTION.READ, RBAC_SCOPE.OWN)),
    },
  },
  {
    path: '/borrower/create-reservation',
    name: ROUTE_NAMES.borrowerCreateReservation,
    component: () => import('@/pages/borrower/CreateReservation.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.SUBMIT_RESERVATION, RBAC_ACTION.CREATE)),
    },
  },
  {
    path: '/borrower/create-reservation/venue',
    name: 'borrowerCreateReservationVenuePage',
    component: () => import('@/pages/borrower/CreateReservationVenue.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.SUBMIT_RESERVATION, RBAC_ACTION.CREATE)),
    },
  },
  {
    path: '/borrower/create-reservation/documents',
    name: 'borrowerCreateReservationDocumentsPage',
    component: () => import('@/pages/borrower/CreateReservationDocuments.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.SUBMIT_RESERVATION, RBAC_ACTION.CREATE)),
    },
  },
  {
    path: '/borrower/active-reservations',
    name: 'borrowerActiveReservationsPage',
    component: () => import('@/pages/borrower/ActiveReservations.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.VIEW_RESERVATIONS, RBAC_ACTION.READ, RBAC_SCOPE.OWN)),
    },
  },
  {
    path: '/borrower/approved-requests',
    name: 'borrowerApprovedRequestsPage',
    component: () => import('@/pages/borrower/ApprovedRequests.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.VIEW_RESERVATIONS, RBAC_ACTION.READ, RBAC_SCOPE.OWN)),
    },
  },
  {
    path: '/borrower/pending-requests',
    name: 'borrowerPendingRequestsPage',
    component: () => import('@/pages/borrower/PendingRequests.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.VIEW_RESERVATIONS, RBAC_ACTION.READ, RBAC_SCOPE.OWN)),
    },
  },
  {
    path: '/borrower/view-facilities',
    name: 'borrowerViewFacilitiesPage',
    component: () => import('@/pages/borrower/ViewFacilities.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
      rbac: rbacAny(
        permission(RBAC_CAPABILITY.MANAGE_VENUE, RBAC_ACTION.READ, RBAC_SCOPE.AVAILABILITY_ONLY),
        permission(RBAC_CAPABILITY.MANAGE_EQUIPMENT, RBAC_ACTION.READ, RBAC_SCOPE.AVAILABILITY_ONLY),
      ),
    },
  },
  {
    path: '/borrower/past-records',
    name: 'borrowerPastRecordsPage',
    component: () => import('@/pages/borrower/PastRecords.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.VIEW_RESERVATIONS, RBAC_ACTION.READ, RBAC_SCOPE.OWN)),
    },
  },
  {
    path: '/borrower/view-reservation-list',
    name: ROUTE_NAMES.borrowerViewReservationList,
    component: () => import('@/pages/borrower/ViewReservationList.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.VIEW_RESERVATIONS, RBAC_ACTION.READ, RBAC_SCOPE.OWN)),
    },
  },
  {
    path: '/borrower/active-reservations-logs',
    name: 'borrowerActiveReservationsLogsPage',
    component: () => import('@/pages/borrower/ActiveReservationsLogs.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.VIEW_RESERVATIONS, RBAC_ACTION.READ, RBAC_SCOPE.OWN)),
    },
  },
  {
    path: '/borrower/approved-requests-logs',
    name: ROUTE_NAMES.borrowerApprovedRequestsLogs,
    component: () => import('@/pages/borrower/ApprovedRequestsLogs.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.VIEW_RESERVATIONS, RBAC_ACTION.READ, RBAC_SCOPE.OWN)),
    },
  },
  {
    path: '/borrower/pending-requests-logs',
    name: ROUTE_NAMES.borrowerPendingRequestsLogs,
    component: () => import('@/pages/borrower/PendingRequestsLogs.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.VIEW_RESERVATIONS, RBAC_ACTION.READ, RBAC_SCOPE.OWN)),
    },
  },
  {
    path: '/borrower/completed-reservations-logs',
    name: ROUTE_NAMES.borrowerCompletedReservationsLogs,
    component: () => import('@/pages/borrower/CompletedReservationsLogs.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.VIEW_RESERVATIONS, RBAC_ACTION.READ, RBAC_SCOPE.OWN)),
    },
  },
  {
    path: '/notifications',
    name: 'notificationPage',
    component: () => import('@/pages/notifications/NotificationPage.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER', 'ROLE_ADMIN'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.NOTIFICATIONS, RBAC_ACTION.READ)),
    },
  },
  {
    path: '/borrower/notifications',
    name: 'borrowerNotificationsPage',
    component: () => import('@/pages/borrower/BorrowerNotifications.vue'),
    meta: {
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.NOTIFICATIONS, RBAC_ACTION.READ)),
    },
  },
  // Settings routes
  {
    path: '/settings',
    name: 'settingsPage',
    component: () => import('@/pages/settings/SettingsPage.vue'),
    meta: {
      title: 'Settings',
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER', 'ROLE_ADMIN', 'ROLE_STAFF'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.ACCOUNT_MANAGEMENT, RBAC_ACTION.READ)),
    },
  },
  {
    path: '/settings/account',
    name: 'accountSettingsPage',
    component: () => import('@/pages/settings/SettingsPage.vue'),
    meta: {
      title: 'Settings',
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER', 'ROLE_ADMIN', 'ROLE_STAFF'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.ACCOUNT_MANAGEMENT, RBAC_ACTION.UPDATE)),
    },
  },
  {
    path: '/settings/security',
    name: 'securitySettingsPage',
    component: () => import('@/pages/settings/SettingsPage.vue'),
    meta: {
      title: 'Settings',
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER', 'ROLE_ADMIN', 'ROLE_STAFF'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.ACCOUNT_MANAGEMENT, RBAC_ACTION.UPDATE)),
    },
  },
  {
    path: '/settings/preferences',
    name: 'preferencesSettingsPage',
    component: () => import('@/pages/settings/SettingsPage.vue'),
    meta: {
      title: 'Settings',
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER', 'ROLE_ADMIN', 'ROLE_STAFF'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.ACCOUNT_MANAGEMENT, RBAC_ACTION.UPDATE)),
    },
  },
  {
    path: '/borrower/settings',
    name: 'borrowerSettingsPage',
    component: () => import('@/pages/settings/SettingsPage.vue'),
    meta: {
      title: 'Settings',
      requiresAuth: true,
      allowedRoles: ['ROLE_BORROWER'],
      rbac: rbacAny(permission(RBAC_CAPABILITY.ACCOUNT_MANAGEMENT, RBAC_ACTION.UPDATE)),
    },
  },
];
