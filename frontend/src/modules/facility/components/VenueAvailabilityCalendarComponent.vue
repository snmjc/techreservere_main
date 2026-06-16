<template>
  <section class="venue-availability-calendar">
    <div class="venue-availability-calendar__header">
      <div>
        <p class="venue-availability-calendar__eyebrow">Venue Calendar</p>
        <h3>Availability for {{ formattedSelectedDate }}</h3>
      </div>
      <label class="venue-availability-calendar__date-picker">
        <span>Select date</span>
        <input :value="selectedDate" type="date" @input="emit('update:selectedDate', $event.target.value)" />
      </label>
    </div>

    <div class="venue-availability-calendar__legend">
      <span><i class="venue-availability-calendar__dot venue-availability-calendar__dot--available"></i> Available</span>
      <span><i class="venue-availability-calendar__dot venue-availability-calendar__dot--unavailable"></i> Unavailable</span>
    </div>

    <div v-if="rows.length === 0" class="venue-availability-calendar__empty">
      No venues to show for the selected date.
    </div>

    <div v-else class="venue-availability-calendar__grid">
      <article
        v-for="row in rows"
        :key="row.venueIdentifier"
        class="venue-availability-calendar__card"
        :class="row.availabilityClass"
      >
        <div class="venue-availability-calendar__card-header">
          <strong>{{ row.venueName }}</strong>
          <span>{{ row.availabilityStatus }}</span>
        </div>
        <p>{{ row.location }}</p>
        <p>Capacity: {{ row.capacity }}</p>
        <p>Operational: {{ row.operationalStatus }}</p>
        <p>Open from: {{ row.availableDate }}</p>
        <p v-if="row.reservationSummary.length">Booked slots: {{ row.reservationSummary.join(', ') }}</p>
        <p v-else>No booked slots for the selected date.</p>
      </article>
    </div>

    <p class="venue-availability-calendar__note">
      Venue availability is calculated from the venue record status plus overlapping reservations for the selected date.
    </p>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import { formatDisplayDate } from '@/shared/utils/dateTimeDisplay.js';
import {
  deriveVenueAvailabilityForDate,
  formatVenueCapacity,
  formatVenueText,
} from '@/modules/facility/utils/venueFormValidation.js';

const props = defineProps({
  venues: {
    type: Array,
    required: true,
  },
  selectedDate: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(['update:selectedDate']);

const formattedSelectedDate = computed(() => formatDisplayDate(props.selectedDate));
const rows = computed(() => props.venues.map((venueRecord) => {
  const availabilityStatus = deriveVenueAvailabilityForDate(venueRecord, props.selectedDate);

  return {
    venueIdentifier: venueRecord.venueIdentifier,
    venueName: formatVenueText(venueRecord.venueName),
    location: formatVenueText(venueRecord.venueLocation),
    capacity: formatVenueCapacity(venueRecord.capacityLimit),
    operationalStatus: formatVenueText(venueRecord.operationalStatus),
    availableDate: formatDisplayDate(venueRecord.availabilityDate),
    reservationSummary: Array.isArray(venueRecord.reservationTimeRanges) ? venueRecord.reservationTimeRanges : [],
    availabilityStatus,
    availabilityClass: availabilityStatus === 'Available'
      ? 'venue-availability-calendar__card--available'
      : 'venue-availability-calendar__card--unavailable',
  };
}));
</script>

<style scoped>
.venue-availability-calendar {
  margin: 1rem 0 1.2rem;
  padding: 1rem;
  background: linear-gradient(135deg, #f6fbf7 0%, #eef7f1 100%);
  border: 1px solid #dce8df;
  border-radius: 18px;
}

.venue-availability-calendar__header {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.8rem;
}

.venue-availability-calendar__eyebrow {
  margin: 0 0 0.2rem;
  color: #6b7280;
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.venue-availability-calendar__header h3 {
  margin: 0;
  color: #111827;
  font-size: 1.1rem;
}

.venue-availability-calendar__date-picker {
  display: grid;
  gap: 0.35rem;
  color: #374151;
  font-size: 0.82rem;
  font-weight: 700;
}

.venue-availability-calendar__date-picker input {
  min-height: 42px;
  padding: 0.65rem 0.75rem;
  color: #111827;
  background: #ffffff;
  border: 1px solid #cfe0d4;
  border-radius: 10px;
  font: inherit;
}

.venue-availability-calendar__legend {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1rem;
  color: #4b5563;
  font-size: 0.8rem;
  font-weight: 600;
}

.venue-availability-calendar__legend span {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.venue-availability-calendar__dot {
  width: 12px;
  height: 12px;
  border-radius: 999px;
}

.venue-availability-calendar__dot--available {
  background: #16a34a;
}

.venue-availability-calendar__dot--unavailable {
  background: #dc2626;
}

.venue-availability-calendar__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 0.75rem;
}

.venue-availability-calendar__card {
  padding: 0.85rem 0.95rem;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-left-width: 6px;
  border-radius: 14px;
}

.venue-availability-calendar__card--available {
  border-left-color: #16a34a;
}

.venue-availability-calendar__card--unavailable {
  border-left-color: #dc2626;
}

.venue-availability-calendar__card-header {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.4rem;
  color: #111827;
  font-size: 0.88rem;
}

.venue-availability-calendar__card p {
  margin: 0.2rem 0 0;
  color: #4b5563;
  font-size: 0.8rem;
}

.venue-availability-calendar__empty,
.venue-availability-calendar__note {
  color: #6b7280;
  font-size: 0.82rem;
}

.venue-availability-calendar__note {
  margin: 0.95rem 0 0;
}

@media (max-width: 720px) {
  .venue-availability-calendar__header {
    align-items: stretch;
    flex-direction: column;
  }
}
</style>
