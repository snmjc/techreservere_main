<template>
  <div v-if="show" class="venue-details-modal-overlay" @click.self="emit('close')">
    <section class="venue-details-modal">
      <button class="venue-details-modal-close" type="button" aria-label="Close" @click="emit('close')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6 6 18" />
          <path d="m6 6 12 12" />
        </svg>
      </button>

      <div class="venue-details-modal-heading">
        <h2>View Venue Details</h2>
        <p>Venue information from the TechReserve venue database.</p>
      </div>

      <p v-if="errorMessage" class="venue-details-modal-error">{{ errorMessage }}</p>

      <div v-else class="venue-details-modal-body">
        <div class="venue-details-modal-banner">
          <img
            :src="resolveVenuePhoto(venue)"
            :alt="`${formatVenueText(venue?.venueName)} photo`"
            class="venue-details-modal-image"
          />
        </div>

        <div class="venue-details-modal-grid">
          <article class="venue-details-modal-card">
            <span class="venue-details-modal-icon">
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

          <article class="venue-details-modal-card">
            <span class="venue-details-modal-icon">
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

          <article class="venue-details-modal-card">
            <span class="venue-details-modal-icon">
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

          <article class="venue-details-modal-card">
            <span class="venue-details-modal-icon">
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

          <article class="venue-details-modal-card">
            <span class="venue-details-modal-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <rect x="3" y="4" width="18" height="18" rx="2" />
                <path d="M16 2v4M8 2v4M3 10h18" />
              </svg>
            </span>
            <div>
              <p>Reservation Availability Start</p>
              <strong>{{ formatDisplayDate(venue?.availabilityDate) }}</strong>
            </div>
          </article>

          <article class="venue-details-modal-card">
            <span class="venue-details-modal-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <circle cx="12" cy="12" r="9" />
                <path d="m9 12 2 2 4-4" />
              </svg>
            </span>
            <div>
              <p>Operational Status</p>
              <strong>
                <span
                  class="venue-details-modal-status"
                  :class="String(venue?.operationalStatus || '').trim() === 'Active'
                    ? 'venue-details-modal-status--active'
                    : 'venue-details-modal-status--inactive'"
                >
                  {{ formatVenueText(venue?.operationalStatus) }}
                </span>
              </strong>
            </div>
          </article>

          <article class="venue-details-modal-card">
            <span class="venue-details-modal-icon">
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
                  class="venue-details-modal-status"
                  :class="venue?.availabilityStatus === 'Available'
                    ? 'venue-details-modal-status--active'
                    : 'venue-details-modal-status--inactive'"
                >
                  {{ formatVenueText(venue?.availabilityStatus) }}
                </span>
              </strong>
            </div>
          </article>
        </div>

        <article class="venue-details-modal-description">
          <span class="venue-details-modal-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M8 7h8" />
              <path d="M8 12h8" />
              <path d="M8 17h5" />
              <rect x="4" y="3" width="16" height="18" rx="2" />
            </svg>
          </span>
          <div>
            <p>Description</p>
            <strong>{{ formatVenueText(venue?.description) }}</strong>
          </div>
        </article>
      </div>

      <div class="venue-details-modal-actions">
        <button class="venue-details-modal-button" type="button" @click="emit('close')">Close</button>
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

<style scoped>
.venue-details-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: rgba(15, 23, 42, 0.56);
}

.venue-details-modal {
  position: relative;
  width: min(680px, 100%);
  max-height: 88vh;
  overflow-y: auto;
  background: #ffffff;
  border: 1px solid #d9e3dd;
  border-radius: 24px;
  box-shadow: 0 24px 64px rgba(15, 23, 42, 0.24);
}

.venue-details-modal-close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #d5ddd8;
  border-radius: 999px;
  background: #ffffff;
  color: #274434;
  cursor: pointer;
}

.venue-details-modal-close svg,
.venue-details-modal-icon svg {
  width: 18px;
  height: 18px;
}

.venue-details-modal-heading,
.venue-details-modal-actions {
  padding: 1rem 1.1rem;
}

.venue-details-modal-heading {
  padding-right: 4.5rem;
  border-bottom: 1px solid #e8eeea;
}

.venue-details-modal-heading h2 {
  margin: 0;
  color: #16361f;
  font-size: 1.35rem;
}

.venue-details-modal-heading p {
  margin: 0.4rem 0 0;
  color: #4b6354;
}

.venue-details-modal-error {
  margin: 1.25rem;
  padding: 0.85rem 1rem;
  border-radius: 12px;
  font-weight: 700;
  color: #912018;
  background: #fef3f2;
  border: 1px solid #f5d1cd;
}

.venue-details-modal-body {
  display: grid;
  gap: 1rem;
  padding: 1rem 1.1rem;
}

.venue-details-modal-banner {
  overflow: hidden;
  min-height: 160px;
  border: 1px solid #d9e3dd;
  border-radius: 18px;
  background: linear-gradient(135deg, #eff7f1 0%, #dcefe2 100%);
}

.venue-details-modal-image {
  display: block;
  width: 100%;
  min-height: 160px;
  max-height: 220px;
  object-fit: cover;
}

.venue-details-modal-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.95rem;
}

.venue-details-modal-card,
.venue-details-modal-description {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 0.8rem;
  align-items: start;
  padding: 0.95rem 1rem;
  border: 1px solid #e7efe9;
  border-radius: 16px;
  background: #f7faf8;
}

.venue-details-modal-icon {
  display: grid;
  place-items: center;
  width: 40px;
  height: 40px;
  border-radius: 14px;
  color: #1b7248;
  background: #eaf6ee;
}

.venue-details-modal-card p,
.venue-details-modal-description p {
  margin: 0 0 0.35rem;
  color: #607165;
  font-size: 0.8rem;
  font-weight: 700;
}

.venue-details-modal-card strong,
.venue-details-modal-description strong {
  color: #16361f;
  font-size: 0.98rem;
  line-height: 1.45;
}

.venue-details-modal-description {
  grid-template-columns: auto 1fr;
}

.venue-details-modal-status {
  display: inline-flex;
  align-items: center;
  min-height: 32px;
  padding: 0 0.85rem;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 800;
}

.venue-details-modal-status--active {
  color: #0d6a41;
  background: #dcf6e7;
}

.venue-details-modal-status--inactive {
  color: #8a5a0a;
  background: #fff1d5;
}

.venue-details-modal-actions {
  display: flex;
  justify-content: flex-end;
  border-top: 1px solid #e8eeea;
}

.venue-details-modal-button {
  min-height: 44px;
  padding: 0.75rem 1rem;
  border: 1px solid #d4ddd7;
  border-radius: 12px;
  background: #ffffff;
  color: #264434;
  font: inherit;
  font-weight: 700;
  cursor: pointer;
}

@media (max-width: 720px) {
  .venue-details-modal {
    max-height: 94vh;
  }

  .venue-details-modal-grid {
    grid-template-columns: 1fr;
  }
}
</style>
