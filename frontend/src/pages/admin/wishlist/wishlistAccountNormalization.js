import { normalizeEmailForConfirmation } from './wishlistTextHelpers.js';
import { resolveRequestStatus } from './wishlistStatusHelpers.js';
import { resolveAccountType, resolveRoleLabel, resolveRoleName } from './wishlistRoleHelpers.js';

export function normalizeWishlistAccount(account) {
  const source = normalizeWishlistAccountSource(account);
  const accountType = account.accountType || resolveAccountType(account, source.roleDesignation);

  return {
    ...account,
    accountIdentifier: account.accountIdentifier || account.account_identifier || source.idNumber,
    rawIdNumber: String(source.idNumber),
    idNumber: formatIdNumber(source.idNumber),
    firstName: source.firstName,
    lastName: source.lastName,
    fullName: `${source.firstName} ${source.lastName}`.trim(),
    emailAddress: source.emailAddress,
    username: account.username || source.username || '',
    contactNumber: source.contactNumber,
    supportingDocumentName: account.supportingDocumentName || account.signup_supporting_document_name || null,
    supportingDocumentMimeType: account.supportingDocumentMimeType || account.signup_supporting_document_mime_type || null,
    supportingDocumentPath: account.supportingDocumentPath || account.signup_supporting_document_path || null,
    supportingDocumentSizeBytes: account.supportingDocumentSizeBytes || account.signup_supporting_document_size_bytes || null,
    supportingDocumentUploadedAt: account.supportingDocumentUploadedAt || account.signup_supporting_document_uploaded_at || null,
    supportingDocumentVerificationStatus: account.supportingDocumentVerificationStatus || account.signup_supporting_document_verification_status || null,
    supportingDocumentData: account.supportingDocumentData || account.signup_supporting_document_data || null,
    roleDesignation: source.roleDesignation,
    role: resolveRoleName(account, source.roleDesignation),
    roleLabel: account.roleLabel || resolveRoleLabel(account, source.roleDesignation),
    accountType,
    accountStatus: source.accountStatus,
    registeredAt: account.registeredAt || account.createdTimestamp || account.created_timestamp || null,
    inviteStatus: source.inviteStatus,
    invitationStatus: source.invitationStatus,
    inviteInvitedBy: source.inviteInvitedBy,
    inviteSentAt: source.inviteSentAt,
    inviteExpiresAt: source.inviteExpiresAt,
    inviteAcceptedAt: source.inviteAcceptedAt,
    initials: `${source.firstName.charAt(0)}${source.lastName.charAt(0)}`.toUpperCase() || 'TR',
  };
}

export function getUniqueRequestAccounts(accounts) {
  const accountsByEmail = new Map();
  accounts.forEach((account) => {
    const emailKey = normalizeEmailForConfirmation(account.emailAddress);
    if (!emailKey) return;

    const existingAccount = accountsByEmail.get(emailKey);
    if (!existingAccount || isNewerAccount(account, existingAccount)) {
      accountsByEmail.set(emailKey, account);
    }
  });

  return Array.from(accountsByEmail.values());
}

export function isPdfProof(account) {
  const mimeType = String(account?.supportingDocumentMimeType || '').toLowerCase();
  const fileName = String(account?.supportingDocumentName || '').toLowerCase();
  return mimeType === 'application/pdf' || fileName.endsWith('.pdf');
}

export function formatIdNumber(idNumber) {
  const value = String(idNumber);
  if (value.includes('*') || value === 'N/A') return value;
  if (value.length <= 4) return value;
  return `${value.slice(0, 4)}*****`;
}

function normalizeWishlistAccountSource(account) {
  const inviteAcceptedAt = account.inviteAcceptedAt || account.invite_accepted_at || null;
  const inviteExpiresAt = account.inviteExpiresAt || account.invite_expires_at || null;

  return {
    roleDesignation: account.roleDesignation || account.role_designation || 'ROLE_BORROWER',
    firstName: account.firstName || account.first_name || '',
    lastName: account.lastName || account.last_name || '',
    emailAddress: account.emailAddress || account.email_address || '',
    username: account.username || '',
    inviteSentAt: account.inviteSentAt || account.invite_sent_at || null,
    inviteExpiresAt,
    inviteAcceptedAt,
    inviteStatus: account.inviteStatus || account.invite_status || null,
    invitationStatus: account.invitationStatus || account.invitation_status || 'not_sent',
    inviteInvitedBy: account.inviteInvitedBy || account.invite_invited_by || account.sentBy || account.sent_by || null,
    accountStatus: resolveRequestStatus(
      account.accountStatus || account.status,
      inviteAcceptedAt,
      inviteExpiresAt,
      account.isVerified ?? account.is_verified ?? false,
      account.isApproved ?? account.is_approved ?? false,
    ),
    idNumber: account.idNumber || account.studentIdNumber || account.accountIdentifier || account.account_identifier || 'N/A',
    contactNumber: account.contactNumber || account.contact_number || account.phone || 'N/A',
  };
}

function isNewerAccount(account, existingAccount) {
  const accountInviteTime = getTimestamp(account.inviteSentAt || account.invitedAt);
  const existingInviteTime = getTimestamp(existingAccount.inviteSentAt || existingAccount.invitedAt);

  if (accountInviteTime !== existingInviteTime) {
    return accountInviteTime > existingInviteTime;
  }

  return getTimestamp(account.registeredAt) > getTimestamp(existingAccount.registeredAt);
}

function getTimestamp(value) {
  const timestamp = new Date(value || 0).getTime();
  return Number.isNaN(timestamp) ? 0 : timestamp;
}
