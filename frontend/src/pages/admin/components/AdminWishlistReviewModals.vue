<template>
  <div v-if="selectedAccount" class="admin-wishlist-modal-overlay" @click.self="$emit('close-view')">
    <section class="admin-wishlist-view-modal">
      <button class="admin-wishlist-modal-close" type="button" aria-label="Close" @click="$emit('close-view')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6 6 18" />
          <path d="m6 6 12 12" />
        </svg>
      </button>

      <div class="admin-wishlist-modal-heading">
        <h2>View Request Details</h2>
        <p>Review requestor information, invite status, and Clerk invitation details from the database.</p>
      </div>

      <div class="admin-wishlist-view-account-grid">
        <div class="admin-wishlist-view-account-main">
          <p><strong>Last Name:</strong> <span>{{ selectedAccount.lastName }}</span></p>
          <p><strong>First Name:</strong> <span>{{ selectedAccount.firstName }}</span></p>
          <p><strong>ID Number:</strong> <span>{{ selectedAccount.rawIdNumber || selectedAccount.idNumber }}</span></p>
          <p><strong>Username:</strong> <span>{{ selectedAccount.username || 'N/A' }}</span></p>
          <p><strong>Email:</strong> <span>{{ selectedAccount.emailAddress }}</span></p>
          <p><strong>Role:</strong> <span>{{ selectedAccount.role }}</span></p>
          <p><strong>Account Status:</strong> <span>{{ getStatusLabel(selectedAccount.accountStatus) }}</span></p>
          <p><strong>Account Registered:</strong> <span>{{ formatDisplayDateTime(selectedAccount.registeredAt) }}</span></p>
          <p><strong>Account Type:</strong> <span>{{ selectedAccount.accountType }}</span></p>
          <p v-if="shouldShowProofDetails(selectedAccount)"><strong>Proof File:</strong> <span>{{ selectedAccount.supportingDocumentName || 'N/A' }}</span></p>
        </div>
        <div class="admin-wishlist-view-account-side">
          <p><strong>Invite Sent:</strong> <span>{{ getInviteSentStatus(selectedAccount) }}</span></p>
          <p><strong>Invited By:</strong> <span>{{ selectedAccount.inviteInvitedBy || 'N/A' }}</span></p>
          <p><strong>Invite Sent Date:</strong> <span>{{ formatNullableDateTime(selectedAccount.inviteSentAt) }}</span></p>
          <p><strong>Expiration Date:</strong> <span>{{ formatNullableDateTime(selectedAccount.inviteExpiresAt) }}</span></p>
          <p><strong>Accepted Status:</strong> <span>{{ getAcceptedStatus(selectedAccount) }}</span></p>
          <p><strong>Accepted Date:</strong> <span>{{ formatNullableDateTime(selectedAccount.inviteAcceptedAt) }}</span></p>
        </div>
      </div>

      <div v-if="shouldShowProofDetails(selectedAccount) && hasSupportingDocument(selectedAccount)" class="admin-wishlist-proof-actions">
        <button
          v-if="isPdfProof(selectedAccount)"
          class="admin-wishlist-proof-link admin-wishlist-proof-link--secondary"
          type="button"
          :disabled="previewIsLoading"
          @click="$emit('open-proof', selectedAccount)"
        >
          Preview PDF
        </button>
        <button
          class="admin-wishlist-proof-link"
          type="button"
          :disabled="previewIsLoading"
          @click="$emit('download-proof', selectedAccount)"
        >
          Download proof
        </button>
      </div>

      <div class="admin-wishlist-modal-actions">
        <button class="admin-wishlist-close-button" type="button" @click="$emit('close-view')">Close</button>
      </div>
    </section>
  </div>

  <div v-if="approvalAccount" class="admin-wishlist-modal-overlay admin-wishlist-modal-overlay--top" @click.self="!isProcessing && $emit('close-approval')">
    <section class="admin-wishlist-approval-modal">
      <button class="admin-wishlist-modal-close" type="button" aria-label="Close" :disabled="isProcessing" @click="$emit('close-approval')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6 6 18" />
          <path d="m6 6 12 12" />
        </svg>
      </button>

      <div class="admin-wishlist-modal-heading">
        <h2>{{ getInviteModalTitle(approvalAccount) }}</h2>
        <p>{{ getInviteModalDescription(approvalAccount) }}</p>
      </div>

      <div class="admin-wishlist-approval-profile">
        <span class="admin-wishlist-avatar" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="currentColor">
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21a8 8 0 0 1 16 0" />
          </svg>
        </span>
        <div class="admin-wishlist-invite-summary">
          <em :class="getAccountTypeBadgeClass(approvalAccount.accountType)">{{ approvalAccount.accountType }}</em>
          <div class="admin-wishlist-invite-details">
            <p><span>Last Name</span><strong>{{ approvalAccount.lastName }}</strong></p>
            <p><span>First Name</span><strong>{{ approvalAccount.firstName }}</strong></p>
            <p><span>ID Number</span><strong>{{ approvalAccount.rawIdNumber || approvalAccount.idNumber }}</strong></p>
            <p><span>Username</span><strong>{{ approvalAccount.username || 'N/A' }}</strong></p>
            <p><span>{{ getApprovalEmailLabel(approvalAccount) }}</span><strong>{{ approvalAccount.emailAddress }}</strong></p>
            <p><span>Role</span><strong>{{ approvalAccount.role }}</strong></p>
            <p v-if="approvalMode === 'resend'">
              <span>Last Invite Sent</span>
              <strong>{{ formatNullableDateTime(approvalAccount.inviteSentAt) }}</strong>
            </p>
          </div>
        </div>
      </div>

      <div v-if="hasSupportingDocument(approvalAccount)" class="admin-wishlist-proof-actions">
        <button
          v-if="isPdfProof(approvalAccount)"
          class="admin-wishlist-proof-link admin-wishlist-proof-link--secondary"
          type="button"
          :disabled="isProcessing || previewIsLoading"
          @click="$emit('open-proof', approvalAccount)"
        >
          Preview PDF
        </button>
        <button
          class="admin-wishlist-proof-link"
          type="button"
          :disabled="isProcessing || previewIsLoading"
          @click="$emit('download-proof', approvalAccount)"
        >
          Download proof
        </button>
      </div>

      <label class="admin-wishlist-confirm-field">
        <span>Type your admin email <strong>{{ currentAdminEmail || 'from your account' }}</strong> to confirm:</span>
        <input
          v-model.trim="approvalConfirmEmail"
          type="email"
          :placeholder="currentAdminEmail || 'admin@techreserve.edu.ph'"
          autocomplete="off"
          :disabled="isProcessing"
        />
      </label>

      <p v-if="approvalFormError" class="admin-wishlist-add-error">{{ approvalFormError }}</p>

      <div class="admin-wishlist-modal-actions">
        <button class="admin-wishlist-cancel-button" type="button" :disabled="isProcessing" @click="$emit('close-approval')">Cancel</button>
        <button
          class="admin-wishlist-send-invite-button"
          type="button"
          :disabled="isProcessing || !isApprovalConfirmationReady"
          @click="$emit('submit-invite')"
        >
          {{ isProcessing ? getProcessingLabel() : getInviteSubmitLabel(approvalAccount) }}
        </button>
      </div>
    </section>
  </div>

  <div v-if="denialAccount" class="admin-wishlist-modal-overlay admin-wishlist-modal-overlay--top" @click.self="$emit('close-denial')">
    <section class="admin-wishlist-denial-modal">
      <button class="admin-wishlist-modal-close" type="button" aria-label="Close" @click="$emit('close-denial')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6 6 18" />
          <path d="m6 6 12 12" />
        </svg>
      </button>

      <div class="admin-wishlist-modal-heading">
        <h2>Deny Account Request</h2>
        <p>Confirm the responsible admin before denying this request. This will prevent the account from being invited.</p>
      </div>

      <div class="admin-wishlist-approval-profile admin-wishlist-denial-profile">
        <span class="admin-wishlist-avatar" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="currentColor">
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21a8 8 0 0 1 16 0" />
          </svg>
        </span>
        <div class="admin-wishlist-invite-summary">
          <em :class="getAccountTypeBadgeClass(denialAccount.accountType)">{{ denialAccount.accountType }}</em>
          <div class="admin-wishlist-invite-details">
            <p><span>Name</span><strong>{{ denialAccount.fullName }}</strong></p>
            <p><span>ID Number</span><strong>{{ denialAccount.rawIdNumber || denialAccount.idNumber }}</strong></p>
            <p><span>Email to deny</span><strong>{{ denialAccount.emailAddress }}</strong></p>
            <p><span>Role</span><strong>{{ denialAccount.role }}</strong></p>
          </div>
        </div>
      </div>

      <label class="admin-wishlist-confirm-field">
        <span>Type your admin email <strong>{{ currentAdminEmail || 'from your account' }}</strong> to confirm denial:</span>
        <input v-model.trim="denialConfirmEmail" type="email" :placeholder="currentAdminEmail || 'admin@techreserve.edu.ph'" autocomplete="off" />
      </label>

      <label class="admin-wishlist-confirm-field">
        <span>Type your admin password to confirm denial:</span>
        <input v-model="denialConfirmPassword" type="password" placeholder="Admin password" autocomplete="current-password" />
      </label>

      <p v-if="denialFormError" class="admin-wishlist-add-error">{{ denialFormError }}</p>

      <div class="admin-wishlist-modal-actions">
        <button class="admin-wishlist-cancel-button" type="button" :disabled="isProcessing" @click="$emit('close-denial')">Cancel</button>
        <button
          class="admin-wishlist-deny-button"
          type="button"
          :disabled="isProcessing || !isDenialConfirmationReady"
          @click="$emit('deny-account')"
        >
          {{ isProcessing ? 'Denying...' : 'Deny Request' }}
        </button>
      </div>
    </section>
  </div>

  <div v-if="previewAccount" class="admin-wishlist-modal-overlay admin-wishlist-modal-overlay--top" @click.self="$emit('close-proof')">
    <section class="admin-wishlist-proof-modal">
      <button class="admin-wishlist-modal-close" type="button" aria-label="Close" @click="$emit('close-proof')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6 6 18" />
          <path d="m6 6 12 12" />
        </svg>
      </button>

      <div class="admin-wishlist-modal-heading">
        <h2>Validation PDF Preview</h2>
        <p>Review the submitted file before approving the request.</p>
      </div>

      <div class="admin-wishlist-proof-modal-meta">
        <p><strong>Requestor:</strong> <span>{{ previewAccount.fullName }}</span></p>
        <p><strong>File:</strong> <span>{{ previewAccount.supportingDocumentName || 'N/A' }}</span></p>
      </div>

      <p v-if="previewErrorMessage" class="admin-wishlist-add-error">{{ previewErrorMessage }}</p>
      <div v-else-if="previewIsLoading" class="admin-wishlist-empty-state">Loading supporting document...</div>
      <div v-else-if="previewDocumentUrl" class="admin-wishlist-proof-preview-frame">
        <iframe :src="previewDocumentUrl" :title="previewAccount.supportingDocumentName || 'Validation PDF preview'" />
      </div>

      <div class="admin-wishlist-proof-actions">
        <button
          v-if="hasSupportingDocument(previewAccount)"
          class="admin-wishlist-proof-link"
          type="button"
          :disabled="previewIsLoading"
          @click="$emit('download-proof', previewAccount)"
        >
          Download proof
        </button>
        <button class="admin-wishlist-close-button" type="button" @click="$emit('close-proof')">Close</button>
      </div>
    </section>
  </div>
</template>

<script setup>
import {
  formatDisplayDateTime,
  formatNullableDateTime,
  getAcceptedStatus,
  getAccountTypeBadgeClass,
  getApprovalEmailLabel,
  getInviteSentStatus,
  getStatusLabel,
  isPdfProof,
} from '../wishlist/adminWishlistHelpers.js';
defineProps({
  selectedAccount: { type: Object, default: null },
  approvalAccount: { type: Object, default: null },
  approvalMode: { type: String, default: '' },
  approvalFormError: { type: String, default: '' },
  denialAccount: { type: Object, default: null },
  denialFormError: { type: String, default: '' },
  previewAccount: { type: Object, default: null },
  previewDocumentUrl: { type: String, default: '' },
  previewIsLoading: { type: Boolean, default: false },
  previewErrorMessage: { type: String, default: '' },
  currentAdminEmail: { type: String, default: '' },
  isProcessing: { type: Boolean, default: false },
  isApprovalConfirmationReady: { type: Boolean, default: false },
  isDenialConfirmationReady: { type: Boolean, default: false },
  getInviteModalTitle: { type: Function, required: true },
  getInviteModalDescription: { type: Function, required: true },
  getInviteSubmitLabel: { type: Function, required: true },
  getProcessingLabel: { type: Function, required: true },
});
defineEmits([
  'close-view',
  'close-approval',
  'close-denial',
  'close-proof',
  'open-proof',
  'download-proof',
  'submit-invite',
  'deny-account',
]);
const approvalConfirmEmail = defineModel('approvalConfirmEmail', { type: String, default: '' });
const denialConfirmEmail = defineModel('denialConfirmEmail', { type: String, default: '' });
const denialConfirmPassword = defineModel('denialConfirmPassword', { type: String, default: '' });
function hasSupportingDocument(account) {
  return Boolean(account?.supportingDocumentName && account?.supportingDocumentPath);
}

function shouldShowProofDetails(account) {
  return account?.accountType !== 'Admin';
}
</script>
