<template>
  <div v-if="show" class="manage-facilities-modal-overlay" @click.self="emit('close')">
    <section class="manage-facilities-equipment-details-modal manage-facilities-venue-details-modal manage-facilities-venue-dialog">
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

      <div v-else class="manage-facilities-venue-details-sheet">
        <div class="manage-facilities-venue-photo-banner">
          <img
            :src="resolveVenuePhoto(venue)"
            :alt="`${formatVenueText(venue?.venueName)} photo`"
            class="manage-facilities-equipment-photo"
          />
        </div>

        <div class="manage-facilities-venue-details-list">
          <article class="manage-facilities-venue-detail-row">
            <span class="manage-facilities-venue-detail-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M4 20h16" />
                <path d="M6 20V8l6-4 6 4v12" />
                <path d="M10 20v-5h4v5" />
              </svg>
            </span>
            <div>
              <p>Venue Name</p>
              <strong>{{ formatVenueText(venue?.venueName) }}</strong>
            </div>
          </article>

          <article class="manage-facilities-venue-detail-row">
            <span class="manage-facilities-venue-detail-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M3 10.5 12 3l9 7.5" />
                <path d="M5 9.5V20h14V9.5" />
                <path d="M9 20v-5h6v5" />
              </svg>
            </span>
            <div>
              <p>Floor</p>
              <strong>{{ formatVenueText(venue?.floorLevel) }}</strong>
            </div>
          </article>

          <article class="manage-facilities-venue-detail-row">
            <span class="manage-facilities-venue-detail-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M12 21s6-4.35 6-10a6 6 0 1 0-12 0c0 5.65 6 10 6 10Z" />
                <circle cx="12" cy="11" r="2.5" />
              </svg>
            </span>
            <div>
              <p>Location</p>
              <strong>{{ formatVenueText(venue?.venueLocation) }}</strong>
            </div>
          </article>

          <article class="manage-facilities-venue-detail-row">
            <span class="manage-facilities-venue-detail-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                <circle cx="9.5" cy="7" r="3" />
                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 4.13a3 3 0 0 1 0 5.74" />
              </svg>
            </span>
            <div>
              <p>Capacity</p>
              <strong>{{ formatVenueCapacity(venue?.capacityLimit) }}</strong>
            </div>
          </article>

          <article class="manage-facilities-venue-detail-row">
            <span class="manage-facilities-venue-detail-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <rect x="3" y="4" width="18" height="18" rx="2" />
                <path d="M16 2v4M8 2v4M3 10h18" />
              </svg>
            </span>
            <div>
              <p>Availability Date</p>
              <strong>{{ formatDisplayDate(venue?.availabilityDate) }}</strong>
            </div>
          </article>

          <article class="manage-facilities-venue-detail-row">
            <span class="manage-facilities-venue-detail-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <circle cx="12" cy="12" r="9" />
                <path d="m9 12 2 2 4-4" />
              </svg>
            </span>
            <div>
              <p>Operational Status</p>
              <strong>
                <span
                  class="manage-facilities-venue-status-badge"
                  :class="String(venue?.operationalStatus || '').trim() === 'Active'
                    ? 'manage-facilities-venue-status-badge--available'
                    : 'manage-facilities-venue-status-badge--unavailable'"
                >
                  {{ formatVenueText(venue?.operationalStatus) }}
                </span>
              </strong>
            </div>
          </article>

          <article class="manage-facilities-venue-detail-row">
            <span class="manage-facilities-venue-detail-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M3 12h18" />
                <path d="M12 3v18" />
                <circle cx="12" cy="12" r="9" />
              </svg>
            </span>
            <div>
              <p>Availability</p>
              <strong>
                <span
                  class="manage-facilities-venue-status-badge"
                  :class="venue?.availabilityStatus === 'Available'
                    ? 'manage-facilities-venue-status-badge--available'
                    : 'manage-facilities-venue-status-badge--unavailable'"
                >
                  {{ formatVenueText(venue?.availabilityStatus) }}
                </span>
              </strong>
            </div>
          </article>

          <article class="manage-facilities-venue-detail-row manage-facilities-venue-detail-row--full">
            <span class="manage-facilities-venue-detail-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M8 7h8" />
                <path d="M8 12h8" />
                <path d="M8 17h5" />
                <rect x="4" y="3" width="16" height="18" rx="2" />
              </svg>
            </span>
            <div>
              <p>Description</p>
              <strong class="manage-facilities-venue-detail-copy">{{ formatVenueText(venue?.description) }}</strong>
            </div>
          </article>
        </div>
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
