<template>
  <div v-if="requestRecord" class="borrower-request-modal-overlay" @click.self="handleClose">
    <div class="borrower-request-modal borrower-request-modal--compact">
      <button type="button" class="borrower-request-modal__close" aria-label="Close" @click="handleClose">x</button>
      <div class="borrower-request-modal__header borrower-request-modal__header--stacked">
        <div>
          <p class="borrower-request-modal__eyebrow">Cancel Request</p>
          <h2>{{ requestRecord.requestDisplayIdentifier }}</h2>
        </div>
      </div>

      <p class="borrower-request-modal__warning">
        Are you sure you want to cancel this request? This action cannot be undone.
      </p>

      <label class="borrower-request-modal__field">
        <span>Reason for cancellation</span>
        <textarea v-model.trim="reasonText" rows="4" placeholder="Tell us why you want to cancel this request."></textarea>
      </label>

      <label class="borrower-request-modal__field">
        <span>Enter your email to confirm</span>
        <input v-model.trim="confirmationEmailText" type="email" :placeholder="confirmationEmail || 'your@email.com'" />
        <small v-if="confirmationEmail">Use {{ confirmationEmail }} to confirm this cancellation.</small>
        <small v-if="confirmationError" class="borrower-request-modal__error">{{ confirmationError }}</small>
      </label>

      <div class="borrower-request-modal__actions">
        <button type="button" class="borrower-request-modal__button borrower-request-modal__button--ghost" @click="handleClose">Cancel</button>
        <button
          type="button"
          class="borrower-request-modal__button borrower-request-modal__button--danger"
          :disabled="!isSubmitEnabled || isSubmitting"
          @click="handleConfirm"
        >
          {{ isSubmitting ? 'Cancelling...' : 'Confirm Cancellation' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  requestRecord: {
    type: Object,
    default: null,
  },
  isSubmitting: {
    type: Boolean,
    default: false,
  },
  confirmationEmail: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['close', 'confirm']);
const reasonText = ref('');
const confirmationEmailText = ref('');
const confirmationError = ref('');

const isSubmitEnabled = computed(() => {
  const expectedEmail = normalizeEmail(props.confirmationEmail);
  return reasonText.value.length > 0
    && expectedEmail !== ''
    && normalizeEmail(confirmationEmailText.value) === expectedEmail;
});

watch(() => props.requestRecord, () => {
  reasonText.value = '';
  confirmationEmailText.value = '';
  confirmationError.value = '';
});

watch(confirmationEmailText, () => {
  confirmationError.value = '';
});

function handleConfirm() {
  if (!isSubmitEnabled.value) {
    confirmationError.value = 'Enter your account email before confirming cancellation.';
    return;
  }

  emit('confirm', {
    reason: reasonText.value,
    confirmationEmail: confirmationEmailText.value,
  });
}

function handleClose() {
  reasonText.value = '';
  confirmationEmailText.value = '';
  confirmationError.value = '';
  emit('close');
}

function normalizeEmail(value) {
  return String(value || '').trim().toLowerCase();
}
</script>
