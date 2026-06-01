import { getSortRoleName, getUserRoleName } from './wishlistRoleHelpers.js';
import { getStatusLabel } from './wishlistStatusHelpers.js';

export function filterWishlistAccounts(accounts, filters) {
  const query = String(filters.searchText || '').trim().toLowerCase();
  const accountType = getWishlistAccountTypeForTab(filters.activeTab);

  return accounts
    .filter((account) => account.accountType === accountType)
    .filter((account) => matchesWishlistStatusFilter(account, filters.statusFilter))
    .filter((account) => matchesWishlistUserRoleFilter(account, filters.activeTab, filters.userRoleFilter))
    .filter((account) => matchesWishlistSearchQuery(account, query))
    .sort((first, second) => compareWishlistAccounts(first, second, filters.sortMode));
}

function getWishlistAccountTypeForTab(activeTab) {
  if (activeTab === 'admin') return 'Admin';
  if (activeTab === 'employee') return 'Employee';
  return 'User';
}

function matchesWishlistStatusFilter(account, statusFilter) {
  return statusFilter === 'all' || account.accountStatus === statusFilter;
}

function matchesWishlistUserRoleFilter(account, activeTab, userRoleFilter) {
  return activeTab !== 'user'
    || userRoleFilter === 'all'
    || getUserRoleName(account).toLowerCase() === userRoleFilter;
}

function matchesWishlistSearchQuery(account, query) {
  if (!query) return true;

  return [
    account.idNumber,
    account.firstName,
    account.lastName,
    account.emailAddress,
    account.roleLabel,
  ].some((value) => String(value).toLowerCase().includes(query));
}

function compareWishlistAccounts(first, second, sortMode) {
  const sorters = {
    name: compareWishlistNames,
    role: compareWishlistRoles,
    status: compareWishlistStatuses,
    newest: compareWishlistRegisteredDates,
  };

  return (sorters[sortMode] || sorters.newest)(first, second);
}

function compareWishlistNames(first, second) {
  return wishlistFullName(first).localeCompare(wishlistFullName(second));
}

function compareWishlistRoles(first, second) {
  return getSortRoleName(first).localeCompare(getSortRoleName(second)) || compareWishlistNames(first, second);
}

function compareWishlistStatuses(first, second) {
  return getStatusLabel(first.accountStatus).localeCompare(getStatusLabel(second.accountStatus));
}

function compareWishlistRegisteredDates(first, second) {
  return new Date(second.registeredAt).getTime() - new Date(first.registeredAt).getTime();
}

function wishlistFullName(account) {
  return `${account.lastName} ${account.firstName}`;
}
