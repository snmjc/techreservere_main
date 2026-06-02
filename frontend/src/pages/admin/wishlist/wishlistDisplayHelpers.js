export function getAccountTypeBadgeClass(accountType) {
  return {
    'admin-wishlist-account-type-badge--employee': accountType === 'Employee',
    'admin-wishlist-account-type-badge--admin': accountType === 'Admin',
    'admin-wishlist-account-type-badge--user': accountType === 'User',
  };
}
