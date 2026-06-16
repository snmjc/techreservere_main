<template>
  <div v-if="show && venue" class="manage-facilities-modal-overlay" @click.self="!isDeleting && emit('close')">
    <section class="manage-facilities-delete-modal manage-facilities-equipment-details-modal manage-facilities-venue-details-modal manage-facilities-venue-dialog manage-facilities-venue-delete-dialog">
      <button
        class="manage-facilities-modal-close"
        type="button"
        aria-label="Close"
        :disabled="isDeleting"
        @click="emit('close')"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6 6 18" />
          <path d="m6 6 12 12" />
        </svg>
      </button>

      <div class="manage-facilities-venue-delete-alert">
        <span class="manage-facilities-venue-delete-alert-icon">!</span>
      </div>

      <div class="manage-facilities-modal-heading manage-facilities-modal-heading--centered">
        <h2>Are you sure you want to delete this venue?</h2>
        <p>This action cannot be undone.</p>
      </div>

      <div class="manage-facilities-venue-delete-summary-card">
        <div class="manage-facilities-venue-delete-summary-grid">
          <div>
            <span>Venue Name</span>
            <strong>{{ formatVenueText(venue?.venueName) }}</strong>
          </div>
          <div>
            <span>Floor</span>
            <strong>{{ formatVenueText(venue?.floorLevel) }}</strong>
          </div>
          <div>
            <span>Location</span>
            <strong>{{ formatVenueText(venue?.venueLocation) }}</strong>
          </div>
          <div>
            <span>Capacity</span>
            <strong>{{ formatVenueCapacity(venue?.capacityLimit) }}</strong>
          </div>
          <div>
            <span>Availability Date</span>
            <strong>{{ formatDisplayDate(venue?.availabilityDate) }}</strong>
          </div>
          <div>
            <span>Status</span>
            <strong>{{ formatVenueText(venue?.availabilityStatus || venue?.operationalStatus) }}</strong>
          </div>
        </div>
      </div>

      <label class="manage-facilities-confirm-field">
        <span>Admin email confirmation</span>
        <input
          :value="confirmEmail"
          type="email"
          :placeholder="currentAdminEmail || 'admin@techreserve.edu.ph'"
          autocomplete="off"
          @input="emit('update:confirmEmail', $event.target.value)"
        />
      </label>

      <label class="manage-facilities-confirm-field">
        <span>Admin password confirmation</span>
        <input
          :value="confirmPassword"
          type="password"
          placeholder="Admin password"
          autocomplete="current-password"
          @input="emit('update:confirmPassword', $event.target.value)"
        />
      </label>

      <p v-if="errorMessage" class="manage-facilities-modal-error">{{ errorMessage }}</p>

      <div class="manage-facilities-modal-actions">
        <button class="manage-facilities-cancel-button" type="button" :disabled="isDeleting" @click="emit('close')">
          Cancel
        </button>
        <button
          class="manage-facilities-delete-confirm-button"
          type="button"
          :disabled="isDeleting || !isReady"
          @click="emit('confirm')"
        >
          {{ isDeleting ? 'Deleting...' : 'Delete Venue' }}
        </button>
      </div>
    </section>
  </div>
</template>

<script setup>
import { formatDisplayDate } from '@/shared/utils/dateTimeDisplay.js';
import {
  formatVenueCapacity,
  formatVenueText,
} from '@/modules/facility/utils/venueFormValidation.js';

defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  venue: {
    type: Object,
    default: null,
  },
  currentAdminEmail: {
    type: String,
    default: '',
  },
  confirmEmail: {
    type: String,
    default: '',
  },
  confirmPassword: {
    type: String,
    default: '',
  },
  errorMessage: {
    type: String,
    default: '',
  },
  isDeleting: {
    type: Boolean,
    default: false,
  },
  isReady: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close', 'confirm', 'update:confirmEmail', 'update:confirmPassword']);
</script>
