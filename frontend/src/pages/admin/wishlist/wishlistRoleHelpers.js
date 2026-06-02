export function resolveAccountType(account, roleDesignation) {
  const normalizedRole = String(roleDesignation).toUpperCase();
  if (normalizedRole.includes('ADMIN')) return 'Admin';
  if (normalizedRole.includes('STAFF') || normalizedRole.includes('EMPLOYEE')) return 'Employee';
  return hasEmployeeDepartment(account) ? 'Employee' : 'User';
}

export function resolveRoleLabel(account, roleDesignation) {
  const accountType = account.accountType || resolveAccountType(account, roleDesignation);
  if (String(roleDesignation).toUpperCase().includes('ADMIN')) return 'Admin';
  if (accountType === 'Employee') return resolveRoleName({ ...account, accountType }, roleDesignation);
  return hasFacultyDepartment(account) ? 'User: Faculty' : 'User: Student';
}

export function resolveRoleName(account, roleDesignation) {
  if (String(roleDesignation).toUpperCase().includes('ADMIN')) return 'Administrator';
  const accountType = account.accountType || resolveAccountType(account, roleDesignation);
  return accountType === 'Employee'
    ? resolveEmployeeRoleName(account)
    : resolveUserRoleName(account);
}

export function getSortRoleName(account) {
  return account.accountType === 'User' ? getUserRoleName(account) : account.role;
}

export function getUserRoleName(account) {
  const roleText = `${account?.role || ''} ${account?.roleLabel || ''} ${account?.roleDesignation || ''}`.toLowerCase();
  return roleText.includes('faculty') ? 'Faculty' : 'Student';
}

export function getApprovalEmailLabel(account) {
  if (account?.accountType === 'Employee') return 'Employee company email';
  return account?.accountType === 'User' ? 'FIT email address' : 'Email address';
}

export function formatEmployeeRoleName(value) {
  const normalized = String(value || '').trim();
  if (!normalized || /^user:/i.test(normalized)) return '';
  if (/^role_/i.test(normalized)) return '';
  return normalized
    .replace(/[_-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

export function getEmailLabel(account) {
  return account?.accountType === 'Employee' ? 'Email Address:' : 'FIT Email Address:';
}

function resolveEmployeeRoleName(account) {
  const rawRole = account.role || account.roleName || account.roleLabel || '';
  const department = String(account.department || '');
  const employeeRole = formatEmployeeRoleName(rawRole);
  if (employeeRole) return employeeRole;
  if (/faculty/i.test(rawRole) || /faculty/i.test(department)) return 'Faculty';
  return formatEmployeeRoleName(department) || 'Technical Staff';
}

function resolveUserRoleName(account) {
  const rawRole = account.role || account.roleName || account.roleLabel || '';
  const department = String(account.department || '');
  return /faculty/i.test(rawRole) || /faculty/i.test(department) ? 'Faculty' : 'Student';
}

function hasEmployeeDepartment(account) {
  const department = String(account.department || '').toLowerCase();
  return department.includes('staff')
    || department.includes('employee')
    || department.includes('technical')
    || department.includes('maintenance');
}

function hasFacultyDepartment(account) {
  const department = String(account.department || '').toLowerCase();
  return department.includes('faculty') || department.includes('employee');
}
