const ADMIN_EMAILS = new Set([
  'smmojica@fit.edu.ph',
]);

export function isAdminEmail(emailAddress) {
  return ADMIN_EMAILS.has(String(emailAddress || '').trim().toLowerCase());
}

export function resolveRole(rawRole, emailAddress = '') {
  if (isAdminEmail(emailAddress)) return 'ROLE_ADMIN';
  if (!rawRole) return 'ROLE_BORROWER';

  const value = String(rawRole).trim().toUpperCase();
  if (value === 'ROLE_ADMIN' || value === 'ADMIN') return 'ROLE_ADMIN';
  if (value === 'ROLE_BORROWER' || value === 'BORROWER') return 'ROLE_BORROWER';
  return value.startsWith('ROLE_') ? value : 'ROLE_BORROWER';
}
