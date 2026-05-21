// RBAC permissions derived from "RBAC FRS - Sheet1.pdf".
// Actions use the sheet notation: C = Create, R = Read, U = Update, D = Delete.

export const RBAC_ACTION = Object.freeze({
  CREATE: 'C',
  READ: 'R',
  UPDATE: 'U',
  DELETE: 'D',
});

export const RBAC_CAPABILITY = Object.freeze({
  ACCOUNT_MANAGEMENT: 'account.accountManagement',
  MANAGE_EQUIPMENT: 'equipment.manageEquipment',
  EQUIPMENT_DISPOSAL: 'equipment.equipmentDisposal',
  EQUIPMENT_MAINTENANCE: 'equipment.equipmentMaintenance',
  REPORT_EQUIPMENT: 'equipment.reportEquipment',
  SUBMIT_RESERVATION: 'reservation.submitVenueAndEquipmentReservation',
  VIEW_RESERVATIONS: 'reservation.viewReservations',
  UPDATE_RESERVATION_STATUS: 'reservation.updateReservationStatus',
  UPDATE_RESERVATION_INFORMATION: 'reservation.updateReservationInformation',
  PROCESS_DEPLOYMENT_RETURN: 'releaseReturn.processDeploymentReturn',
  VIEW_FORECAST_RISK: 'analytics.viewForecastRiskScores',
  MANAGE_VENUE: 'venue.manageVenue',
  REPORT_VENUE: 'venue.reportVenue',
  VENUE_MAINTENANCE: 'venue.venueMaintenance',
  VIEW_DASHBOARD: 'dashboard.viewDashboard',
  EXPORT_REPORTS: 'dashboard.exportReports',
  ANALYTICS_CONFIGURATION: 'dashboard.analyticsConfiguration',
  CREATE_TASK: 'task.createTask',
  READ_TASK: 'task.readTask',
  UPDATE_TASK: 'task.updateTask',
  ASSIGN_TASK: 'task.assignTask',
  NOTIFICATIONS: 'notifications.readNotifications',
});

export const RBAC_SCOPE = Object.freeze({
  ALL: 'all',
  OWN: 'own',
  AVAILABILITY_ONLY: 'availabilityOnly',
  SPECIFIC_ASSIGNED_ADMIN: 'specificAssignedAdmin',
});

export const rolePermissionMatrix = Object.freeze({
  ROLE_BORROWER: {
    [RBAC_CAPABILITY.ACCOUNT_MANAGEMENT]: ['C', 'R', 'U'],
    [RBAC_CAPABILITY.MANAGE_EQUIPMENT]: ['R'],
    [RBAC_CAPABILITY.REPORT_EQUIPMENT]: ['C', 'R'],
    [RBAC_CAPABILITY.SUBMIT_RESERVATION]: ['C'],
    [RBAC_CAPABILITY.VIEW_RESERVATIONS]: ['R'],
    [RBAC_CAPABILITY.UPDATE_RESERVATION_INFORMATION]: ['U'],
    [RBAC_CAPABILITY.PROCESS_DEPLOYMENT_RETURN]: ['R'],
    [RBAC_CAPABILITY.MANAGE_VENUE]: ['R'],
    [RBAC_CAPABILITY.REPORT_VENUE]: ['C'],
    [RBAC_CAPABILITY.NOTIFICATIONS]: ['R'],
  },
  ROLE_ADMIN: {
    [RBAC_CAPABILITY.ACCOUNT_MANAGEMENT]: ['C', 'R', 'U', 'D'],
    [RBAC_CAPABILITY.MANAGE_EQUIPMENT]: ['C', 'R', 'U', 'D'],
    [RBAC_CAPABILITY.EQUIPMENT_DISPOSAL]: ['C', 'R', 'U', 'D'],
    [RBAC_CAPABILITY.EQUIPMENT_MAINTENANCE]: ['C', 'R', 'U', 'D'],
    [RBAC_CAPABILITY.REPORT_EQUIPMENT]: ['C', 'R', 'U', 'D'],
    [RBAC_CAPABILITY.VIEW_RESERVATIONS]: ['R'],
    [RBAC_CAPABILITY.UPDATE_RESERVATION_STATUS]: ['U'],
    [RBAC_CAPABILITY.UPDATE_RESERVATION_INFORMATION]: ['U'],
    [RBAC_CAPABILITY.PROCESS_DEPLOYMENT_RETURN]: ['C', 'R', 'U'],
    [RBAC_CAPABILITY.VIEW_FORECAST_RISK]: ['R'],
    [RBAC_CAPABILITY.MANAGE_VENUE]: ['C', 'R', 'U'],
    [RBAC_CAPABILITY.REPORT_VENUE]: ['C', 'R', 'U'],
    [RBAC_CAPABILITY.VENUE_MAINTENANCE]: ['C', 'R', 'U'],
    [RBAC_CAPABILITY.VIEW_DASHBOARD]: ['R'],
    [RBAC_CAPABILITY.EXPORT_REPORTS]: ['C', 'R'],
    [RBAC_CAPABILITY.ANALYTICS_CONFIGURATION]: ['C', 'R', 'U', 'D'],
    [RBAC_CAPABILITY.CREATE_TASK]: ['C'],
    [RBAC_CAPABILITY.READ_TASK]: ['R'],
    [RBAC_CAPABILITY.UPDATE_TASK]: ['U'],
    [RBAC_CAPABILITY.ASSIGN_TASK]: ['U'],
    [RBAC_CAPABILITY.NOTIFICATIONS]: ['R'],
  },
});

export function hasRbacPermission(role, permission) {
  if (!permission?.capability || !permission?.action) {
    return true;
  }

  const allowedActions = rolePermissionMatrix[role]?.[permission.capability] ?? [];
  return allowedActions.includes(permission.action);
}

export function canAccessRbac(role, rbacDefinition) {
  if (!rbacDefinition) {
    return true;
  }

  if (Array.isArray(rbacDefinition.all) && rbacDefinition.all.length > 0) {
    return rbacDefinition.all.every((permission) => hasRbacPermission(role, permission));
  }

  if (Array.isArray(rbacDefinition.any) && rbacDefinition.any.length > 0) {
    return rbacDefinition.any.some((permission) => hasRbacPermission(role, permission));
  }

  return true;
}
