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
        This request will be withdrawn while it is still pending review.
      </p>

      <label class="borrower-request-modal__field">
        <span>Reason for cancellation</span>
        <textarea v-model.trim="reasonText" rows="4" placeholder="Tell us why you want to cancel this request."></textarea>
      </label>

      <label class="borrower-request-modal__field">
        <span>Type <strong>{{ confirmationPhrase }}</strong> to confirm</span>
        <input v-model.trim="confirmationText" type="text" :placeholder="confirmationPhrase" />
      </label>

      <div class="borrower-request-modal__actions">
        <button type="button" class="borrower-request-modal__button borrower-request-modal__button--ghost" @click="handleClose">Keep Request</button>
        <button
          type="button"
          class="borrower-request-modal__button borrower-request-modal__button--danger"
          :disabled="!isSubmitEnabled || isSubmitting"
          @click="emit('confirm', { reason: reasonText, confirmationText })"
        >
          {{ isSubmitting ? 'Cancelling...' : 'Cancel Request' }}
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
});

const emit = defineEmits(['close', 'confirm']);
const confirmationPhrase = 'CANCEL';
const reasonText = ref('');
const confirmationText = ref('');

const isSubmitEnabled = computed(() => {
  return reasonText.value.length > 0 && confirmationText.value === confirmationPhrase;
});

watch(() => props.requestRecord, () => {
  reasonText.value = '';
  confirmationText.value = '';
});

function handleClose() {
  reasonText.value = '';
  confirmationText.value = '';
  emit('close');
}
</script>
