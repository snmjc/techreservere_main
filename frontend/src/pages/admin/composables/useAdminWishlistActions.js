import { computed, reactive, ref } from 'vue';
import { adminWishlistApi } from '@/services/adminWishlistApi.js';
import { normalizeEmailForConfirmation } from '../wishlist/adminWishlistHelpers.js';

export function useAdminWishlistActions({ authStore, currentAdminEmail, loadWishlistAccounts, showToast }) {
  const isProcessing = ref(false);
  const selectedAccount = ref(null);
  const approvalAccount = ref(null);
  const approvalMode = ref('send');
  const denialAccount = ref(null);
  const denialConfirmEmail = ref('');
  const denialConfirmPassword = ref('');
  const denialFormError = ref('');
  const deleteAccountRequest = ref(null);
  const deleteConfirmEmail = ref('');
  const deleteConfirmPassword = ref('');
  const deleteFormError = ref('');
  const approvalFormError = ref('');

  const approvalForm = reactive({
    emailAddress: '',
    role: 'ROLE_BORROWER',
    idNumber: '',
    lastName: '',
    firstName: '',
    confirmEmail: '',
  });

  const isApprovalConfirmationReady = computed(() => (
    Boolean(approvalAccount.value)
    && Boolean(currentAdminEmail.value)
    && normalizeEmailForConfirmation(approvalForm.confirmEmail) === normalizeEmailForConfirmation(currentAdminEmail.value)
  ));
  const isDenialConfirmationReady = computed(() => (
    Boolean(denialAccount.value)
    && normalizeEmailForConfirmation(denialConfirmEmail.value) === normalizeEmailForConfirmation(denialAccount.value.emailAddress)
    && denialConfirmPassword.value.trim() !== ''
  ));
  const isDeleteConfirmationReady = computed(() => (
    Boolean(deleteAccountRequest.value)
    && deleteConfirmEmail.value.trim() !== ''
    && deleteConfirmPassword.value.trim() !== ''
  ));

  function openViewModal(account) {
    selectedAccount.value = account;
  }

  function openApprovalModal(account = selectedAccount.value, mode = 'send') {
    if (!account || !canOpenApprovalMode(account, mode)) return;
    approvalAccount.value = account;
    approvalMode.value = ['resend', 'verify'].includes(mode) ? mode : 'send';
    setApprovalFormFromAccount(account);
  }

  function closeModals() {
    selectedAccount.value = null;
    closeApprovalModal();
    closeDenialModal();
    closeDeleteModal();
  }

  function closeApprovalModal() {
    approvalAccount.value = null;
    approvalMode.value = 'send';
    approvalForm.confirmEmail = '';
    approvalFormError.value = '';
  }

  function openDenialModal(account) {
    if (!account) return;
    denialAccount.value = account;
    denialConfirmEmail.value = '';
    denialConfirmPassword.value = '';
    denialFormError.value = '';
  }

  function closeDenialModal() {
    denialAccount.value = null;
    denialConfirmEmail.value = '';
    denialConfirmPassword.value = '';
    denialFormError.value = '';
  }

  function openDeleteModal(account) {
    if (!account) return;
    deleteAccountRequest.value = account;
    deleteConfirmEmail.value = '';
    deleteConfirmPassword.value = '';
    deleteFormError.value = '';
  }

  function closeDeleteModal() {
    deleteAccountRequest.value = null;
    deleteConfirmEmail.value = '';
    deleteConfirmPassword.value = '';
    deleteFormError.value = '';
  }

  async function verifyAccount() {
    if (!canSubmitApproval()) return;

    await runAction(async () => {
      const result = await getApprovalAction()(
        approvalAccount.value.accountIdentifier,
        authStore.authToken,
        { confirmedAdminEmail: normalizeEmailForConfirmation(approvalForm.confirmEmail) },
      );

      if (!result.success) {
        approvalFormError.value = result.error || 'Unable to send invite.';
        showToast(approvalFormError.value);
        return;
      }

      const successMessage = getInviteSuccessMessage();
      closeModals();
      showToast(successMessage);
      await loadWishlistAccounts();
    });
  }

  async function denyAccount() {
    if (!canSubmitDenial()) return;

    await runAction(async () => {
      const result = await adminWishlistApi.denyAccount(
        denialAccount.value.accountIdentifier,
        authStore.authToken,
        {
          confirmEmail: normalizeEmailForConfirmation(denialConfirmEmail.value),
          confirmedAdminPassword: denialConfirmPassword.value,
        },
      );

      await handleRequestDecisionResult(result, closeDenialModal, denialFormError, 'Account request denied.', 'Unable to deny account.');
    });
  }

  async function deleteWishlistAccount() {
    if (!canSubmitDeletion()) return;

    await runAction(async () => {
      const result = await adminWishlistApi.deleteAccountRequest(
        deleteAccountRequest.value.accountIdentifier,
        authStore.authToken,
        {
          confirmedAdminEmail: normalizeEmailForConfirmation(deleteConfirmEmail.value),
          confirmedAdminPassword: deleteConfirmPassword.value,
        },
      );

      await handleRequestDecisionResult(result, closeDeleteModal, deleteFormError, 'Account request deleted.', 'Unable to delete account request.');
    });
  }

  function canSendInvite(account) {
    return String(account?.accountStatus || '').toLowerCase() === 'not_invited' && !isProcessing.value;
  }

  function canResendInvite(account) {
    return String(account?.accountStatus || '').toLowerCase() === 'expired'
      && Boolean(account?.inviteSentAt)
      && !isProcessing.value;
  }

  function canVerifyEmail(account) {
    return String(account?.accountStatus || '').toLowerCase() === 'verified'
      && Boolean(account?.inviteAcceptedAt)
      && !isProcessing.value;
  }

  function getInviteModalTitle(account) {
    if (approvalMode.value === 'resend') return 'Resend Invite';
    if (approvalMode.value === 'verify') return 'Verify Email';
    return account?.accountType === 'Employee' ? 'Approve Employee' : 'Approve Account';
  }

  function getInviteModalDescription(account) {
    if (approvalMode.value === 'resend') {
      return 'The previous invitation expired. Confirm the responsible admin before resending a new invitation link.';
    }
    if (approvalMode.value === 'verify') {
      return 'The invitation was accepted. Confirm the responsible admin before approving system access.';
    }

    return account?.accountType === 'Employee'
      ? 'Review the worker information before approving access and sending the Clerk invitation email.'
      : 'This will send the Clerk invitation to the requestor email and keep the request in Request Hub until the invite is accepted.';
  }

  function getInviteSubmitLabel() {
    if (approvalMode.value === 'resend') return 'Resend Invite';
    if (approvalMode.value === 'verify') return 'Approve Access';
    return 'Approve & Email';
  }

  function getProcessingLabel() {
    if (approvalMode.value === 'verify') return 'Approving...';
    return 'Sending...';
  }

  function canOpenApprovalMode(account, mode) {
    const validators = {
      resend: canResendInvite,
      verify: canVerifyEmail,
      send: canSendInvite,
    };
    const messages = {
      resend: 'Resend invite is only available after the previous invitation expires.',
      verify: 'Verify email is only available after the user accepts the invitation.',
      send: 'Send invite is only available for accounts that are not invited.',
    };
    const normalizedMode = validators[mode] ? mode : 'send';
    if (validators[normalizedMode](account)) return true;

    showToast(messages[normalizedMode]);
    return false;
  }

  function setApprovalFormFromAccount(account) {
    approvalForm.emailAddress = account.emailAddress;
    approvalForm.role = account.roleDesignation;
    approvalForm.idNumber = account.rawIdNumber || account.idNumber;
    approvalForm.lastName = account.lastName;
    approvalForm.firstName = account.firstName;
    approvalForm.confirmEmail = '';
    approvalFormError.value = '';
  }

  function canSubmitApproval() {
    if (isProcessing.value || !approvalAccount.value) return false;
    if (!currentAdminEmail.value) {
      approvalFormError.value = 'Unable to confirm the responsible admin email. Please sign in again.';
      return false;
    }
    if (!isApprovalConfirmationReady.value) {
      approvalFormError.value = approvalMode.value === 'verify'
        ? 'Please type your exact admin email to approve access.'
        : 'Please type your exact admin email to send the invite.';
      return false;
    }
    return true;
  }

  function canSubmitDenial() {
    if (isProcessing.value || !denialAccount.value) return false;
    if (normalizeEmailForConfirmation(denialConfirmEmail.value) !== normalizeEmailForConfirmation(denialAccount.value.emailAddress)) {
      denialFormError.value = 'Please type the exact email address to deny this request.';
      return false;
    }
    if (denialConfirmPassword.value.trim() === '') {
      denialFormError.value = 'Please type your admin password to deny this request.';
      return false;
    }
    return true;
  }

  function canSubmitDeletion() {
    if (isProcessing.value || !deleteAccountRequest.value) return false;
    if (!currentAdminEmail.value) {
      deleteFormError.value = 'Unable to confirm the responsible admin email. Please sign in again.';
      return false;
    }
    if (normalizeEmailForConfirmation(deleteConfirmEmail.value) === '') {
      deleteFormError.value = 'Please type your admin email to delete this request.';
      return false;
    }
    if (normalizeEmailForConfirmation(deleteConfirmEmail.value) !== normalizeEmailForConfirmation(currentAdminEmail.value)) {
      deleteFormError.value = 'Please type your exact admin email to delete this request.';
      return false;
    }
    if (deleteConfirmPassword.value.trim() === '') {
      deleteFormError.value = 'Please type your admin password to delete this request.';
      return false;
    }
    return true;
  }

  async function runAction(action) {
    isProcessing.value = true;
    try {
      await action();
    } finally {
      isProcessing.value = false;
    }
  }

  function getApprovalAction() {
    return approvalMode.value === 'verify'
      ? adminWishlistApi.verifyEmailAndApproveAccount
      : adminWishlistApi.verifyAccount;
  }

  function getInviteSuccessMessage() {
    if (approvalMode.value === 'verify') return 'Email verified and account approved!';
    return 'Invitation sent. The request will stay in Request Hub until the user accepts it.';
  }

  async function handleRequestDecisionResult(result, closeModal, errorRef, successMessage, fallbackError) {
    if (!result.success) {
      errorRef.value = result.error || fallbackError;
      showToast(errorRef.value);
      return;
    }

    approvalAccount.value = null;
    selectedAccount.value = null;
    closeModal();
    await loadWishlistAccounts();
    showToast(successMessage);
  }

  return {
    isProcessing,
    selectedAccount,
    approvalAccount,
    approvalMode,
    approvalForm,
    approvalFormError,
    denialAccount,
    denialConfirmEmail,
    denialConfirmPassword,
    denialFormError,
    deleteAccountRequest,
    deleteConfirmEmail,
    deleteConfirmPassword,
    deleteFormError,
    isApprovalConfirmationReady,
    isDenialConfirmationReady,
    isDeleteConfirmationReady,
    openViewModal,
    openApprovalModal,
    closeModals,
    closeApprovalModal,
    openDenialModal,
    closeDenialModal,
    openDeleteModal,
    closeDeleteModal,
    verifyAccount,
    denyAccount,
    deleteWishlistAccount,
    canSendInvite,
    canResendInvite,
    canVerifyEmail,
    getInviteModalTitle,
    getInviteModalDescription,
    getInviteSubmitLabel,
    getProcessingLabel,
  };
}
