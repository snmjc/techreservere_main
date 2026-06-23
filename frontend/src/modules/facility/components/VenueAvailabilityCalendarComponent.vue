<template>
  <section class="venue-availability-calendar">
    <div class="venue-availability-calendar__header">
      <div>
        <p class="venue-availability-calendar__eyebrow">Venue Calendar</p>
        <h3>Check venue availability by date and time.</h3>
      </div>
    </div>

    <div class="venue-availability-calendar__date-toolbar">
      <label class="venue-availability-calendar__date-range-field">
        <span>Selected Date</span>
        <div class="venue-availability-calendar__date-input">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" />
            <path d="M16 2v4" />
            <path d="M8 2v4" />
            <path d="M3 10h18" />
          </svg>
          <input :value="selectedDate" type="date" @input="emit('update:selectedDate', $event.target.value)" />
        </div>
      </label>

      <div class="venue-availability-calendar__day-nav">
        <button type="button" @click="shiftSelectedDate(-1)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m15 18-6-6 6-6" />
          </svg>
        </button>
        <button type="button" @click="shiftSelectedDate(1)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m9 18 6-6-6-6" />
          </svg>
        </button>
      </div>
    </div>

    <div class="venue-availability-calendar__time-row">
      <label>
        <span>From</span>
        <div class="venue-availability-calendar__time-field">
          <input value="08:00 AM" type="text" readonly />
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 3" />
          </svg>
        </div>
      </label>
      <label>
        <span>To</span>
        <div class="venue-availability-calendar__time-field">
          <input value="05:00 PM" type="text" readonly />
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 3" />
          </svg>
        </div>
      </label>
    </div>

    <div class="venue-availability-calendar__weekday-row">
      <span v-for="weekday in weekdayLabels" :key="weekday">{{ weekday }}</span>
    </div>

    <div class="venue-availability-calendar__month-card">
      <p class="venue-availability-calendar__month-label">{{ monthLabel }}</p>
      <div class="venue-availability-calendar__month-grid">
        <span
          v-for="day in monthCells"
          :key="day.key"
          role="button"
          tabindex="0"
          class="venue-availability-calendar__month-cell"
          :class="{
            'venue-availability-calendar__month-cell--muted': !day.inCurrentMonth,
            'venue-availability-calendar__month-cell--active': day.isSelected,
          }"
          @click="emit('update:selectedDate', day.dateValue)"
          @keydown.enter.prevent="emit('update:selectedDate', day.dateValue)"
          @keydown.space.prevent="emit('update:selectedDate', day.dateValue)"
        >
          {{ day.dayNumber }}
        </span>
      </div>
    </div>

    <div class="venue-availability-calendar__legend">
      <p>Legend</p>
      <span><i class="venue-availability-calendar__dot venue-availability-calendar__dot--available"></i> Available</span>
      <small>Venue is available for the selected time.</small>
      <span><i class="venue-availability-calendar__dot venue-availability-calendar__dot--unavailable"></i> Unavailable</span>
      <small>Venue is fully booked for the selected time.</small>
      <span><i class="venue-availability-calendar__dot venue-availability-calendar__dot--partial"></i> Partially Booked</span>
      <small>Venue has limited availability.</small>
    </div>

    <p class="venue-availability-calendar__note">
      {{ availabilitySummaryCopy }}
    </p>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import { deriveVenueAvailabilityForDate } from '@/modules/facility/utils/venueFormValidation.js';

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

const weekdayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const selectedDateObject = computed(() => {
  const rawValue = String(props.selectedDate || '').trim();
  if (!rawValue) {
    return new Date();
  }

  const parsedDate = new Date(`${rawValue}T00:00:00`);
  return Number.isNaN(parsedDate.getTime()) ? new Date() : parsedDate;
});

const monthLabel = computed(() => new Intl.DateTimeFormat('en-US', {
  month: 'long',
  year: 'numeric',
}).format(selectedDateObject.value));

const monthCells = computed(() => buildCalendarMonth(selectedDateObject.value));
const venueAvailabilitySummary = computed(() => (
  (props.venues || []).reduce((summary, venueRecord) => {
    const availabilityStatus = deriveVenueAvailabilityForDate(venueRecord, props.selectedDate);
    if (availabilityStatus === 'Available') {
      summary.available += 1;
    } else {
      summary.unavailable += 1;
    }

    if (Array.isArray(venueRecord?.reservationTimeRanges) && venueRecord.reservationTimeRanges.length > 0) {
      summary.withReservationBlocks += 1;
    }

    return summary;
  }, {
    available: 0,
    unavailable: 0,
    withReservationBlocks: 0,
  })
));

const availabilitySummaryCopy = computed(() => {
  const summary = venueAvailabilitySummary.value;
  const dateLabel = new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(selectedDateObject.value);

  const reservationBlockCopy = summary.withReservationBlocks > 0
    ? ` ${summary.withReservationBlocks} venue${summary.withReservationBlocks === 1 ? '' : 's'} also show reservation blocks for that date.`
    : '';

  return `${summary.available} available and ${summary.unavailable} unavailable on ${dateLabel}.${reservationBlockCopy}`;
});

function shiftSelectedDate(dayOffset) {
  const nextDate = new Date(selectedDateObject.value);
  nextDate.setDate(nextDate.getDate() + dayOffset);
  emit('update:selectedDate', formatDateInputValue(nextDate));
}

function buildCalendarMonth(date) {
  const year = date.getFullYear();
  const month = date.getMonth();
  const firstDayOfMonth = new Date(year, month, 1);
  const lastDayOfMonth = new Date(year, month + 1, 0);
  const leadingDays = firstDayOfMonth.getDay();
  const trailingDays = 6 - lastDayOfMonth.getDay();
  const selectedKey = formatDateInputValue(date);
  const cells = [];

  for (let index = leadingDays; index > 0; index -= 1) {
    const cellDate = new Date(year, month, 1 - index);
    cells.push(createCalendarCell(cellDate, false, selectedKey));
  }

  for (let dayNumber = 1; dayNumber <= lastDayOfMonth.getDate(); dayNumber += 1) {
    const cellDate = new Date(year, month, dayNumber);
    cells.push(createCalendarCell(cellDate, true, selectedKey));
  }

  for (let index = 1; index <= trailingDays; index += 1) {
    const cellDate = new Date(year, month + 1, index);
    cells.push(createCalendarCell(cellDate, false, selectedKey));
  }

  return cells;
}

function createCalendarCell(date, inCurrentMonth, selectedKey) {
  return {
    key: `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`,
    dateValue: formatDateInputValue(date),
    dayNumber: date.getDate(),
    inCurrentMonth,
    isSelected: formatDateInputValue(date) === selectedKey,
  };
}

function formatDateInputValue(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}
</script>

<style scoped>
.venue-availability-calendar {
  width: min(100%, 980px);
  max-width: 980px;
  padding: 1rem 1rem 1.15rem;
  background: #ffffff;
  border: 1px solid #dfe4df;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
}

.venue-availability-calendar__header h3,
.venue-availability-calendar__header p,
.venue-availability-calendar__legend p,
.venue-availability-calendar__note,
.venue-availability-calendar__month-label {
  margin: 0;
}

.venue-availability-calendar__eyebrow {
  margin-bottom: 0.25rem;
  color: #111827;
  font-size: 1.12rem;
  font-weight: 700;
}

.venue-availability-calendar__header h3 {
  color: #4b5563;
  font-size: 0.78rem;
  font-weight: 500;
}

.venue-availability-calendar__date-toolbar,
.venue-availability-calendar__time-row,
.venue-availability-calendar__weekday-row,
.venue-availability-calendar__month-grid {
  display: grid;
}

.venue-availability-calendar__date-toolbar {
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 0.75rem;
  align-items: end;
  margin-top: 1rem;
}

.venue-availability-calendar__date-range-field {
  display: grid;
  gap: 0.35rem;
}

.venue-availability-calendar__date-range-field span {
  color: #374151;
  font-size: 0.8rem;
  font-weight: 500;
}

.venue-availability-calendar__date-input {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  min-height: 38px;
  padding: 0 0.8rem;
  border: 1px solid #dde3de;
  border-radius: 8px;
}

.venue-availability-calendar__date-input svg,
.venue-availability-calendar__day-nav svg,
.venue-availability-calendar__time-field svg {
  width: 16px;
  height: 16px;
  color: #1f2937;
  flex: 0 0 auto;
}

.venue-availability-calendar__date-input input,
.venue-availability-calendar__time-field input {
  width: 100%;
  border: none;
  background: transparent;
  color: #111827;
  font: inherit;
  outline: none;
}

.venue-availability-calendar__date-input input {
  font-size: 0.83rem;
  font-weight: 600;
}

.venue-availability-calendar__day-nav {
  grid-auto-flow: column;
  gap: 0.4rem;
}

.venue-availability-calendar__day-nav button {
  display: grid;
  place-items: center;
  width: 34px;
  height: 34px;
  background: #ffffff;
  border: 1px solid #dde3de;
  border-radius: 8px;
  cursor: pointer;
}

.venue-availability-calendar__time-row {
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem;
  margin-top: 1.1rem;
}

.venue-availability-calendar__time-row label {
  display: grid;
  gap: 0.35rem;
}

.venue-availability-calendar__time-row span {
  color: #374151;
  font-size: 0.8rem;
  font-weight: 500;
}

.venue-availability-calendar__time-field {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-height: 36px;
  padding: 0 0.7rem;
  border: 1px solid #dde3de;
  border-radius: 8px;
}

.venue-availability-calendar__time-field input {
  font-size: 0.8rem;
  font-weight: 600;
}

.venue-availability-calendar__weekday-row {
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 0.35rem;
  margin-top: 1.2rem;
  color: #111827;
  font-size: 0.78rem;
  font-weight: 700;
  text-align: center;
}

.venue-availability-calendar__month-card {
  margin-top: 0.55rem;
  padding: 0.8rem 0.65rem;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
}

.venue-availability-calendar__month-label {
  margin-bottom: 0.8rem;
  color: #111827;
  font-size: 0.92rem;
  font-weight: 700;
  text-align: center;
}

.venue-availability-calendar__month-grid {
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 0.45rem;
}

.venue-availability-calendar__month-cell {
  display: grid;
  place-items: center;
  min-height: 44px;
  color: #111827;
  font-size: 0.86rem;
  border-radius: 999px;
  cursor: pointer;
  outline: none;
}

.venue-availability-calendar__month-cell--muted {
  color: #c0c7c2;
}

.venue-availability-calendar__month-cell--active {
  color: #ffffff;
  background: #16a34a;
  box-shadow: inset 0 0 0 2px #15803d;
}

.venue-availability-calendar__month-cell:focus-visible {
  box-shadow: inset 0 0 0 2px #15803d, 0 0 0 2px rgba(21, 128, 61, 0.2);
}

.venue-availability-calendar__legend {
  display: grid;
  gap: 0.35rem;
  margin-top: 1rem;
  padding-top: 0.9rem;
  border-top: 1px solid #ecefed;
}

.venue-availability-calendar__legend p {
  color: #111827;
  font-size: 0.85rem;
  font-weight: 700;
}

.venue-availability-calendar__legend span {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  color: #111827;
  font-size: 0.8rem;
  font-weight: 600;
}

.venue-availability-calendar__legend small {
  color: #4b5563;
  font-size: 0.76rem;
}

.venue-availability-calendar__dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
}

.venue-availability-calendar__dot--available {
  background: #22c55e;
}

.venue-availability-calendar__dot--unavailable {
  background: #dc2626;
}

.venue-availability-calendar__dot--partial {
  background: #f59e0b;
}

.venue-availability-calendar__note {
  margin-top: 0.9rem;
  color: #6b7280;
  font-size: 0.75rem;
}

@media (max-width: 720px) {
  .venue-availability-calendar {
    width: 100%;
  }

  .venue-availability-calendar__date-toolbar,
  .venue-availability-calendar__time-row {
    grid-template-columns: 1fr;
  }
}
</style>
