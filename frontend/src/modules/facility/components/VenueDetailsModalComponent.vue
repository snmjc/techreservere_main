<template>
  <div v-if="show" class="manage-facilities-modal-overlay" @click.self="emit('close')">
    <section class="manage-facilities-equipment-details-modal manage-facilities-venue-details-modal">
      <button class="manage-facilities-modal-close" type="button" aria-label="Close" @click="emit('close')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6 6 18" />
          <path d="m6 6 12 12" />
        </svg>
      </button>

      <div class="manage-facilities-modal-heading">
        <h2>View Venue Details</h2>
        <p>Venue information from the TechReserve venue database.</p>
      </div>

      <p v-if="errorMessage" class="manage-facilities-modal-error">{{ errorMessage }}</p>

      <div v-else class="manage-facilities-equipment-details-layout">
        <div class="manage-facilities-equipment-photo-card">
          <img
            :src="resolveVenuePhoto(venue)"
            :alt="`${formatVenueText(venue?.venueName)} photo`"
            class="manage-facilities-equipment-photo"
          />
        </div>

        <dl class="manage-facilities-equipment-details-grid">
          <div><dt>Venue Name</dt><dd>{{ formatVenueText(venue?.venueName) }}</dd></div>
          <div><dt>Location</dt><dd>{{ formatVenueText(venue?.venueLocation) }}</dd></div>
          <div><dt>Capacity</dt><dd>{{ formatVenueCapacity(venue?.capacityLimit) }}</dd></div>
          <div><dt>Availability Date</dt><dd>{{ formatDisplayDate(venue?.availabilityDate) }}</dd></div>
          <div><dt>Operational Status</dt><dd>{{ formatVenueText(venue?.operationalStatus) }}</dd></div>
          <div><dt>Availability</dt><dd>{{ formatVenueText(venue?.availabilityStatus) }}</dd></div>
          <div><dt>Floor Level</dt><dd>{{ formatVenueText(venue?.floorLevel) }}</dd></div>
          <div class="manage-facilities-equipment-details-grid__full">
            <dt>Description</dt>
            <dd>{{ formatVenueText(venue?.description) }}</dd>
          </div>
        </dl>
      </div>

      <div class="manage-facilities-modal-actions">
        <button class="manage-facilities-cancel-button" type="button" @click="emit('close')">Close</button>
      </div>
    </section>
  </div>
</template>

<script setup>
import { formatDisplayDate } from '@/shared/utils/dateTimeDisplay.js';
import {
  formatVenueCapacity,
  formatVenueText,
  resolveVenuePhoto,
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
  errorMessage: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['close']);
</script>
