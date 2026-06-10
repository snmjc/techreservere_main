import { toTitleCase } from './wishlistTextHelpers.js';

const VERIFIED_STATUSES = ['verified'];
const APPROVED_STATUSES = ['approved', 'accepted'];
const DENIED_STATUSES = ['rejected', 'denied'];
const PENDING_STATUSES = ['pending', 'not_invited', 'unverified'];

export function resolveRequestStatus(status, inviteAcceptedAt, inviteExpiresAt, isVerified = false, isApproved = false) {
  const normalized = String(status || 'pending').toLowerCase();
  if (inviteAcceptedAt || isApproved || APPROVED_STATUSES.includes(normalized)) return 'approved';
  if (DENIED_STATUSES.includes(normalized)) return 'rejected';
  if (isExpiredInvite(inviteExpiresAt)) return 'expired';
  if (isVerified || VERIFIED_STATUSES.includes(normalized) || normalized === 'invited') return 'verified';
  if (isActiveInvite(inviteExpiresAt)) return 'verified';
  if (PENDING_STATUSES.includes(normalized)) return 'unverified';
  return 'unverified';
}

export function getStatusLabel(status) {
  const normalized = String(status || '').toLowerCase();
  if (APPROVED_STATUSES.includes(normalized)) return 'Approved';
  if (VERIFIED_STATUSES.includes(normalized)) return 'Verified';
  if (PENDING_STATUSES.includes(normalized)) return 'Pending';
  if (normalized === 'expired') return 'Expired';
  if (DENIED_STATUSES.includes(normalized)) return 'Denied';
  return 'Pending';
}

export function getStatusClass(status) {
  const normalized = String(status || '').toLowerCase();
  if (DENIED_STATUSES.includes(normalized)) return 'admin-wishlist-status--denied';
  if (APPROVED_STATUSES.includes(normalized)) return 'admin-wishlist-status--accepted';
  if (VERIFIED_STATUSES.includes(normalized)) return 'admin-wishlist-status--accepted';
  if (normalized === 'expired') return 'admin-wishlist-status--expired';
  if (PENDING_STATUSES.includes(normalized)) return 'admin-wishlist-status--pending';
  return 'admin-wishlist-status--pending';
}

export function getInviteSentStatus(account) {
  if (!account?.inviteSentAt) return 'Not sent';
  const inviteStatus = String(account.inviteStatus || '').trim();
  if (inviteStatus.toLowerCase() === 'expired') return 'Expired';
  if (inviteStatus.toLowerCase() === 'pending') return 'Invited';
  return inviteStatus ? toTitleCase(inviteStatus) : 'Invited';
}

export function getAcceptedStatus(account) {
  if (account?.inviteAcceptedAt || String(account?.accountStatus || '').toLowerCase() === 'approved') return 'Approved';
  if (isExpiredInvite(account?.inviteExpiresAt)) return 'Expired';
  if (account?.inviteSentAt) return 'Waiting for Acceptance';
  return 'Not accepted';
}

function isExpiredInvite(inviteExpiresAt) {
  return Boolean(inviteExpiresAt) && new Date(inviteExpiresAt).getTime() < Date.now();
}

function isActiveInvite(inviteExpiresAt) {
  return Boolean(inviteExpiresAt) && new Date(inviteExpiresAt).getTime() >= Date.now();
}
