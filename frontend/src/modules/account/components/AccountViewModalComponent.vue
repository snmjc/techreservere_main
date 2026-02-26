<!-- ===== AI GENERATED: AccountViewModalComponent ===== -->
<template>
  <div v-if="accountRecord" class="account-view-modal-overlay" @click.self="handleCloseModal">
    <div class="account-view-modal-container">
      <!-- Close Button -->
      <button class="account-view-modal-close-button" aria-label="Close" @click="handleCloseModal">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>

      <!-- Modal Heading -->
      <h2 class="account-view-modal-heading">View Account</h2>

      <!-- Account Details -->
      <div class="account-view-modal-details">
        <!-- Top Row: ID + Account Type -->
        <div class="account-view-modal-row account-view-modal-row--split">
          <div class="account-view-modal-field">
            <span class="account-view-modal-label">ID Number:</span>
            <span class="account-view-modal-value">{{ accountRecord.accountIdNumber }}</span>
          </div>
          <div class="account-view-modal-field account-view-modal-field--right">
            <span class="account-view-modal-label">Account Type:</span>
            <span class="account-view-modal-value">{{ accountRecord.accountType }}</span>
          </div>
        </div>

        <!-- Name Fields -->
        <div class="account-view-modal-row">
          <div class="account-view-modal-field">
            <span class="account-view-modal-label">Last Name:</span>
            <span class="account-view-modal-value">{{ accountRecord.accountLastName }}</span>
          </div>
        </div>
        <div class="account-view-modal-row">
          <div class="account-view-modal-field">
            <span class="account-view-modal-label">First Name:</span>
            <span class="account-view-modal-value">{{ accountRecord.accountFirstName }}</span>
          </div>
        </div>

        <!-- Email -->
        <div class="account-view-modal-row">
          <div class="account-view-modal-field">
            <span class="account-view-modal-label">FIT Email Address:</span>
            <span class="account-view-modal-value">{{ accountRecord.accountEmailAddress }}</span>
          </div>
        </div>

        <!-- Role -->
        <div class="account-view-modal-row">
          <div class="account-view-modal-field">
            <span class="account-view-modal-label">Role:</span>
            <span class="account-view-modal-value">{{ accountRecord.accountRole }}</span>
          </div>
        </div>

        <!-- Status + Dates -->
        <div class="account-view-modal-row account-view-modal-row--status-block">
          <div class="account-view-modal-field">
            <span class="account-view-modal-label">Account Status:</span>
            <span class="account-view-modal-value">{{ accountRecord.accountStatus }}</span>
          </div>
          <div class="account-view-modal-field">
            <span class="account-view-modal-label">Account Created On:</span>
            <span class="account-view-modal-value">{{ accountRecord.accountCreatedOn }}</span>
          </div>
          <div class="account-view-modal-field">
            <span class="account-view-modal-label">Last Login:</span>
            <span class="account-view-modal-value">{{ accountRecord.accountLastLogin }}</span>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="account-view-modal-actions">
        <button class="account-view-modal-action-button account-view-modal-action-button--edit" @click="handleEditAccountClick">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
          Edit Account
        </button>
        <button class="account-view-modal-action-button account-view-modal-action-button--reset" @click="handleResetAccessClick">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="1 4 1 10 7 10"/>
            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
          </svg>
          Reset Access
        </button>
        <button class="account-view-modal-action-button account-view-modal-action-button--disable" @click="handleDisableAccountClick">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
          </svg>
          Disable Account
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * @typedef {Object} AccountViewModalProps
 * @property {Object|null} accountRecord - The account record to display
 */
const props = defineProps({
  accountRecord: {
    type: Object,
    required: false,
    default: null,
  },
});

const emit = defineEmits([
  'closeAccountModal',
  'editAccountRecord',
  'resetAccountAccess',
  'disableAccountRecord',
]);

/**
 * @function handleCloseModal
 * @description Emits close event to parent.
 * @returns {void}
 */
function handleCloseModal() {
  emit('closeAccountModal');
}

/**
 * @function handleEditAccountClick
 * @description Emits edit event with current account record.
 * @returns {void}
 */
function handleEditAccountClick() {
  emit('editAccountRecord', props.accountRecord);
}

/**
 * @function handleResetAccessClick
 * @description Emits reset access event with current account record.
 * @returns {void}
 */
function handleResetAccessClick() {
  emit('resetAccountAccess', props.accountRecord);
}

/**
 * @function handleDisableAccountClick
 * @description Emits disable event with current account record.
 * @returns {void}
 */
function handleDisableAccountClick() {
  emit('disableAccountRecord', props.accountRecord);
}
</script>
