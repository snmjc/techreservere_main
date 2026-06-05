import { toTitleCase } from './wishlistTextHelpers.js';

const VERIFIED_STATUSES = ['approved', 'verified', 'accepted'];
const DENIED_STATUSES = ['rejected', 'denied'];
const INVITED_STATUSES = ['invited', 'unverified'];

export function resolveRequestStatus(status, inviteAcceptedAt, inviteExpiresAt) {
  const normalized = String(status || 'pending').toLowerCase();
  if (inviteAcceptedAt || VERIFIED_STATUSES.includes(normalized)) return 'verified';
  if (DENIED_STATUSES.includes(normalized)) return 'rejected';
  if (isExpiredInvite(inviteExpiresAt)) return 'expired';
  if (isActiveInvite(inviteExpiresAt)) return 'unverified';
  if (INVITED_STATUSES.includes(normalized)) return 'unverified';
  if (normalized === 'pending' || normalized === 'not_invited') return 'not_invited';
  return normalized;
}

export function getStatusLabel(status) {
  const normalized = String(status || '').toLowerCase();
  if (VERIFIED_STATUSES.includes(normalized)) return 'Verified';
  if (normalized === 'not_invited' || normalized === 'pending') return 'Not invited';
  if (INVITED_STATUSES.includes(normalized)) return 'Unverified';
  if (normalized === 'expired') return 'Expired';
  if (DENIED_STATUSES.includes(normalized)) return 'Denied';
  return 'Unverified';
}

export function getStatusClass(status) {
  const normalized = String(status || '').toLowerCase();
  if (DENIED_STATUSES.includes(normalized)) return 'admin-wishlist-status--denied';
  if (INVITED_STATUSES.includes(normalized)) return 'admin-wishlist-status--invited';
  if (VERIFIED_STATUSES.includes(normalized)) return 'admin-wishlist-status--accepted';
  if (normalized === 'expired') return 'admin-wishlist-status--expired';
  return 'admin-wishlist-status--pending';
}

export function getInviteSentStatus(account) {
  if (!account?.inviteSentAt) return 'Not sent';
  const inviteStatus = String(account.inviteStatus || '').trim();
  return inviteStatus ? toTitleCase(inviteStatus) : 'Sent';
}

export function getAcceptedStatus(account) {
  if (account?.inviteAcceptedAt) return 'Accepted';
  if (isExpiredInvite(account?.inviteExpiresAt)) return 'Expired';
  if (account?.inviteSentAt) return 'Pending acceptance';
  return 'Not accepted';
}

function isExpiredInvite(inviteExpiresAt) {
  return Boolean(inviteExpiresAt) && new Date(inviteExpiresAt).getTime() < Date.now();
}

function isActiveInvite(inviteExpiresAt) {
  return Boolean(inviteExpiresAt) && new Date(inviteExpiresAt).getTime() >= Date.now();
}
