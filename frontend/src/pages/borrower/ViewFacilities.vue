<template>
  <AdminSidebarLayoutComponent
    :role-label="'DELA CRUZ, JUAN'"
    :navigation-items="borrowerNavigationItems"
  >
    <section class="borrower-facilities">
      <header class="borrower-facilities__header">
        <div>
          <p class="borrower-facilities__eyebrow">Borrower Calendar</p>
          <h1>Calendar</h1>
          <p class="borrower-facilities__subcopy">Browse weekly reserved-room activity and explore equipment from one borrower-friendly workspace.</p>
        </div>
      </header>

      <section class="borrower-facilities__surface">
        <div class="borrower-facilities__surface-toolbar">
          <div class="borrower-facilities__segment-control">
            <button
              v-for="tabOption in facilityTabs"
              :key="tabOption.value"
              type="button"
              class="borrower-facilities__segment"
              :class="{ 'borrower-facilities__segment--active': activeFacilityTab === tabOption.value }"
              @click="activeFacilityTab = tabOption.value"
            >
              {{ tabOption.label }}
            </button>
          </div>

          <div class="borrower-facilities__toolbar-actions">
            <button type="button" class="borrower-facilities__icon-button" aria-label="Search availability">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="7" />
                <path d="m20 20-3.5-3.5" />
              </svg>
            </button>

            <div class="borrower-facilities__date-nav">
              <button type="button" class="borrower-facilities__icon-button" aria-label="Previous day" @click="shiftSelectedDate(-1)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="m15 18-6-6 6-6" />
                </svg>
              </button>
              <button type="button" class="borrower-facilities__today-button" @click="setSelectedDateToToday">Today</button>
              <button type="button" class="borrower-facilities__icon-button" aria-label="Next day" @click="shiftSelectedDate(1)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="m9 18 6-6-6-6" />
                </svg>
              </button>
            </div>

            <label class="borrower-facilities__view-select">
              <select v-model="calendarViewMode">
                <option value="weekly">Weekly View</option>
                <option value="daily">Daily Summary</option>
              </select>
            </label>

            <button type="button" class="borrower-facilities__reserve-button" @click="router.push({ name: ROUTE_NAMES.borrowerCreateReservation })">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14" />
                <path d="M5 12h14" />
              </svg>
              Add Reservation
            </button>
          </div>
        </div>

        <template v-if="activeFacilityTab !== 'equipment'">
          <div class="borrower-facilities__date-banner">
            <div class="borrower-facilities__date-tile">
              <span>{{ selectedMonthShortLabel }}</span>
              <strong>{{ selectedDayNumberLabel }}</strong>
            </div>
            <div class="borrower-facilities__date-copy">
              <h2>{{ selectedLongDateLabel }}</h2>
              <p>{{ selectedWeekdayLabel }}</p>
            </div>
          </div>

          <div class="borrower-facilities__calendar-layout">
            <aside class="borrower-facilities__sidebar">
              <section class="borrower-facilities__sidebar-card borrower-facilities__sidebar-card--calendar">
                <div class="borrower-facilities__mini-calendar-head">
                  <button type="button" class="borrower-facilities__mini-nav" aria-label="Previous month" @click="shiftSelectedMonth(-1)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="m15 18-6-6 6-6" />
                    </svg>
                  </button>
                  <strong>{{ selectedMonthLabel }}</strong>
                  <button type="button" class="borrower-facilities__mini-nav" aria-label="Next month" @click="shiftSelectedMonth(1)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="m9 18 6-6-6-6" />
                    </svg>
                  </button>
                </div>

                <div class="borrower-facilities__mini-weekdays">
                  <span v-for="weekday in compactWeekdayLabels" :key="weekday">{{ weekday }}</span>
                </div>

                <div class="borrower-facilities__mini-grid">
                  <button
                    v-for="dayCell in monthCalendarCells"
                    :key="dayCell.key"
                    type="button"
                    class="borrower-facilities__mini-day"
                    :class="{
                      'borrower-facilities__mini-day--muted': !dayCell.inCurrentMonth,
                      'borrower-facilities__mini-day--active': dayCell.dateValue === selectedVenueDate,
                    }"
                    @click="selectedVenueDate = dayCell.dateValue"
                  >
                    {{ dayCell.dayNumber }}
                  </button>
                </div>
              </section>

              <section class="borrower-facilities__sidebar-card">
                <p class="borrower-facilities__card-label">Selected Date Details</p>
                <div class="borrower-facilities__detail-list">
                  <div class="borrower-facilities__detail-item">
                    <span class="borrower-facilities__detail-icon">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 7v5l3 3" />
                      </svg>
                    </span>
                    <div>
                      <strong>{{ selectedDaySummary.availableCount }}</strong>
                      <p>rooms already reserved for this date</p>
                    </div>
                  </div>
                  <div class="borrower-facilities__detail-item">
                    <span class="borrower-facilities__detail-icon">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" />
                        <path d="M16 2v4" />
                        <path d="M8 2v4" />
                        <path d="M3 10h18" />
                      </svg>
                    </span>
                    <div>
                      <strong>{{ selectedLongDateLabel }}</strong>
                      <p>{{ selectedWeekdayLabel }}</p>
                    </div>
                  </div>
                  <div class="borrower-facilities__detail-item">
                    <span class="borrower-facilities__detail-icon">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                      </svg>
                    </span>
                    <div>
                      <strong>{{ calendarViewMode === 'weekly' ? 'Weekly View' : 'Daily Summary' }}</strong>
                      <p>{{ selectedDaySummary.bookableCount }} reserved rooms shown on this board</p>
                    </div>
                  </div>
                </div>
              </section>

              <section class="borrower-facilities__sidebar-card">
                <p class="borrower-facilities__card-label">Legend</p>
                <div class="borrower-facilities__legend-list">
                  <div class="borrower-facilities__legend-row">
                    <span class="borrower-facilities__legend-dot borrower-facilities__legend-dot--available"></span>
                    <div>
                      <strong>Reserved</strong>
                      <p>Already reserved for that date.</p>
                    </div>
                  </div>
                  <div class="borrower-facilities__legend-row">
                    <span class="borrower-facilities__legend-dot borrower-facilities__legend-dot--future"></span>
                    <div>
                      <strong>Reserved Block</strong>
                      <p>Another blocked room entry for the selected date.</p>
                    </div>
                  </div>
                  <div class="borrower-facilities__legend-row">
                    <span class="borrower-facilities__legend-dot borrower-facilities__legend-dot--maintenance"></span>
                    <div>
                      <strong>Other Block</strong>
                      <p>Unavailable because of another scheduled block.</p>
                    </div>
                  </div>
                </div>
              </section>
            </aside>

            <section class="borrower-facilities__board-panel">
              <p v-if="venueError" class="borrower-facilities__feedback borrower-facilities__feedback--error">{{ venueError }}</p>
              <div v-else-if="venueLoading" class="borrower-facilities__loading-panel">
                <p>Loading weekly venue availability...</p>
              </div>
              <div v-else class="borrower-facilities__board">
                <div class="borrower-facilities__time-rail">
                  <span class="borrower-facilities__time-rail-spacer"></span>
                  <span v-for="timeLabel in timeRailLabels" :key="timeLabel">{{ timeLabel }}</span>
                </div>

                <div class="borrower-facilities__week-grid" :class="{ 'borrower-facilities__week-grid--daily': calendarViewMode === 'daily' }">
                  <article
                    v-for="dayColumn in visibleCalendarColumns"
                    :key="dayColumn.dateValue"
                    class="borrower-facilities__day-column"
                    :class="{ 'borrower-facilities__day-column--selected': dayColumn.dateValue === selectedVenueDate }"
                  >
                    <header class="borrower-facilities__day-header">
                      <span>{{ dayColumn.weekdayLabel }}</span>
                      <strong>{{ dayColumn.shortDateLabel }}</strong>
                      <small>{{ dayColumn.availableCount }} reserved</small>
                    </header>

                    <div class="borrower-facilities__day-body">
                      <button
                        v-for="entry in dayColumn.entries"
                        :key="`${dayColumn.dateValue}-${entry.venueIdentifier}`"
                        type="button"
                        class="borrower-facilities__availability-card"
                        :class="`borrower-facilities__availability-card--${entry.statusTone}`"
                        @click="handleViewVenueDetails(entry)"
                      >
                        <span class="borrower-facilities__availability-meta">{{ entry.metaLine }}</span>
                        <strong>{{ entry.venueName }}</strong>
                        <p>{{ entry.descriptionLine }}</p>
                        <div class="borrower-facilities__availability-footer">
                          <span>{{ entry.footerLabel }}</span>
                          <span>{{ entry.capacityLabel }}</span>
                        </div>
                      </button>

                      <div v-if="dayColumn.entries.length === 0" class="borrower-facilities__empty-day">
                        <strong>No reserved rooms surfaced</strong>
                        <p>No reserved rooms were returned for this date.</p>
                      </div>
                    </div>
                  </article>
                </div>
              </div>
            </section>
          </div>
        </template>

        <section
          v-if="activeFacilityTab === 'all' || activeFacilityTab === 'venue'"
          class="borrower-facilities__section-card"
        >
          <div class="borrower-facilities__section-head">
            <div>
              <p class="borrower-facilities__section-eyebrow">Venue Directory</p>
              <h3>Reserved Rooms for {{ selectedLongDateLabel }}</h3>
            </div>

            <div class="borrower-facilities__section-actions">
              <label class="borrower-facilities__inline-field">
                <span>Status</span>
                <select v-model="venueFilterValue">
                  <option value="all">All Reserved Rooms</option>
                  <option value="available">Reserved</option>
                  <option value="future">Reserved Blocks</option>
                  <option value="maintenance">Other Blocks</option>
                </select>
              </label>

              <button
                type="button"
                class="borrower-facilities__sort-button"
                :title="venueSortOrder === 'asc' ? 'Sort A-Z' : 'Sort Z-A'"
                @click="venueSortOrder = venueSortOrder === 'asc' ? 'desc' : 'asc'"
              >
                <svg v-if="venueSortOrder === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="12" y1="5" x2="12" y2="19" />
                  <polyline points="19 12 12 19 5 12" />
                </svg>
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="12" y1="19" x2="12" y2="5" />
                  <polyline points="5 12 12 5 19 12" />
                </svg>
              </button>
            </div>
          </div>

          <div v-if="venueCardRecords.length === 0" class="borrower-facilities__empty-state">
            <p>No reserved rooms were returned for the current date.</p>
          </div>

          <div v-else class="borrower-facilities__venue-grid">
            <button
              v-for="venue in paginatedVenueCards"
              :key="venue.venueIdentifier || venue.venueName"
              type="button"
              class="borrower-facilities__venue-card"
              :class="`borrower-facilities__venue-card--${venue.venueStatusTone}`"
              @click="handleViewVenueDetails(venue)"
            >
              <div class="borrower-facilities__venue-media">
                <img
                  :src="resolveVenuePhoto(venue)"
                  :alt="`${venue.venueName} photo`"
                  class="borrower-facilities__venue-image"
                />
              </div>
              <div class="borrower-facilities__venue-copy">
                <div class="borrower-facilities__venue-topline">
                  <h4>{{ venue.venueName }}</h4>
                  <span class="borrower-facilities__status-pill" :class="`borrower-facilities__status-pill--${venue.venueStatusTone}`">
                    {{ venue.venueStatusLabel }}
                  </span>
                </div>
                <p>{{ venue.venueLocation || 'Location unavailable' }}</p>
                <div class="borrower-facilities__venue-meta">
                  <span>Capacity {{ venue.capacityLimit || 'N/A' }}</span>
                  <span>{{ venue.floorLevel || 'Other Floor' }}</span>
                </div>
                <small>Reservation Availability Start Date: {{ formatDisplayDate(venue.availabilityDate) }}</small>
              </div>
            </button>
          </div>

          <div v-if="venueTotalPages > 1" class="borrower-facilities__pagination">
            <button type="button" :disabled="venueCurrentPage === 1" @click="venueCurrentPage -= 1">Previous</button>
            <span>Page {{ venueCurrentPage }} of {{ venueTotalPages }}</span>
            <button type="button" :disabled="venueCurrentPage === venueTotalPages" @click="venueCurrentPage += 1">Next</button>
          </div>
        </section>

        <section
          v-if="activeFacilityTab === 'all' || activeFacilityTab === 'equipment'"
          class="borrower-facilities__section-card"
        >
          <div class="borrower-facilities__section-head">
            <div>
              <p class="borrower-facilities__section-eyebrow">Equipment Library</p>
              <h3>{{ activeFacilityTab === 'all' ? 'Featured equipment' : 'Available equipment inventory' }}</h3>
            </div>

            <div class="borrower-facilities__section-actions">
              <label class="borrower-facilities__inline-field borrower-facilities__inline-field--search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="11" cy="11" r="7" />
                  <path d="m20 20-3.5-3.5" />
                </svg>
                <input v-model.trim="equipmentSearchQuery" type="text" placeholder="Search equipment" />
              </label>

              <label class="borrower-facilities__inline-field">
                <span>Status</span>
                <select v-model="equipmentFilterValue">
                  <option value="all">All</option>
                  <option value="available">Available</option>
                  <option value="maintenance">Under Maintenance</option>
                </select>
              </label>

              <button
                type="button"
                class="borrower-facilities__sort-button"
                :title="equipmentSortOrder === 'asc' ? 'Sort A-Z' : 'Sort Z-A'"
                @click="equipmentSortOrder = equipmentSortOrder === 'asc' ? 'desc' : 'asc'"
              >
                <svg v-if="equipmentSortOrder === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="12" y1="5" x2="12" y2="19" />
                  <polyline points="19 12 12 19 5 12" />
                </svg>
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="12" y1="19" x2="12" y2="5" />
                  <polyline points="5 12 12 5 19 12" />
                </svg>
              </button>
            </div>
          </div>

          <p v-if="equipmentError" class="borrower-facilities__feedback borrower-facilities__feedback--error">{{ equipmentError }}</p>

          <div v-if="equipmentLoading" class="borrower-facilities__empty-state">
            <p>Loading equipment records...</p>
          </div>

          <div v-else-if="equipmentCardsToRender.length === 0" class="borrower-facilities__empty-state">
            <p>No equipment matched your current filter.</p>
          </div>

          <div v-else class="borrower-facilities__equipment-grid">
            <button
              v-for="equipment in equipmentCardsToRender"
              :key="equipment.equipmentIdentifier"
              type="button"
              class="borrower-facilities__equipment-card"
              :class="{
                'borrower-facilities__equipment-card--available': equipment.equipmentState === 'Available',
                'borrower-facilities__equipment-card--unavailable': equipment.equipmentState !== 'Available',
              }"
              @click="handleViewEquipmentDetails(equipment)"
            >
              <div class="borrower-facilities__equipment-media">
                <img
                  :src="resolveEquipmentPhoto(equipment)"
                  :alt="`${equipment.equipmentName} photo`"
                  class="borrower-facilities__equipment-image"
                />
              </div>
              <div class="borrower-facilities__equipment-copy">
                <div class="borrower-facilities__equipment-topline">
                  <h4>{{ equipment.equipmentName }}</h4>
                  <span class="borrower-facilities__status-pill" :class="equipment.equipmentState === 'Available' ? 'borrower-facilities__status-pill--available' : 'borrower-facilities__status-pill--maintenance'">
                    {{ formatEquipmentStatus(equipment) }}
                  </span>
                </div>
                <p>{{ equipment.description || equipment.scheduleDescription || 'No description provided.' }}</p>
                <div class="borrower-facilities__equipment-meta">
                  <span>{{ equipment.equipmentCategory || equipment.categoryName || 'N/A' }}</span>
                  <span>{{ equipment.equipmentBrand || 'N/A' }}</span>
                  <span>Qty {{ equipment.availableQuantity }}</span>
                </div>
              </div>
            </button>
          </div>

          <div v-if="activeFacilityTab === 'equipment' && equipmentTotalPages > 1" class="borrower-facilities__pagination">
            <button type="button" :disabled="equipmentCurrentPage === 1" @click="equipmentCurrentPage -= 1">Previous</button>
            <span>Page {{ equipmentCurrentPage }} of {{ equipmentTotalPages }}</span>
            <button type="button" :disabled="equipmentCurrentPage === equipmentTotalPages" @click="equipmentCurrentPage += 1">Next</button>
          </div>
        </section>
      </section>

      <div class="borrower-facilities__footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </div>

      <VenueDetailsModalComponent
        :show="Boolean(viewVenueRecord || viewVenueLoading || viewVenueError)"
        :venue="viewVenueRecord"
        :error-message="viewVenueLoading ? 'Loading venue details...' : viewVenueError"
        @close="closeVenueDetails"
      />

      <EquipmentDetailsModalComponent
        :show="Boolean(viewEquipmentRecord || viewEquipmentLoading || viewEquipmentError)"
        :equipment="viewEquipmentRecord"
        :error-message="viewEquipmentLoading ? 'Loading equipment details...' : viewEquipmentError"
        title="View Equipment Details"
        subtitle="Equipment information from the TechReserve equipment database."
        :show-admin-fields="false"
        @close="closeEquipmentDetails"
      />
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/ViewFacilities.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';
import equipmentApi from '@/modules/reservation/services/equipmentApi.js';
import venueApi from '@/modules/reservation/services/venueApi.js';
import VenueDetailsModalComponent from '@/modules/facility/components/VenueDetailsModalComponent.vue';
import EquipmentDetailsModalComponent from '@/modules/facility/components/EquipmentDetailsModalComponent.vue';
import { formatEquipmentStatus, resolveEquipmentPhoto } from '@/modules/facility/utils/equipmentPresentation.js';
import {
  deriveVenueAvailabilityForDate,
  isVenueFloorPlaceholderRecord,
  resolveVenuePhoto,
} from '@/modules/facility/utils/venueFormValidation.js';
import { formatDisplayDate } from '@/shared/utils/dateTimeDisplay.js';

const router = useRouter();

const facilityTabs = [
  { value: 'all', label: 'All' },
  { value: 'venue', label: 'Venues' },
  { value: 'equipment', label: 'Equipment' },
];

const compactWeekdayLabels = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
const timeRailLabels = ['8 AM', '10 AM', '12 PM', '2 PM', '4 PM'];
const activeFacilityTab = ref('all');
const calendarViewMode = ref('weekly');
const venueFilterValue = ref('all');
const venueSortOrder = ref('asc');
const selectedVenueDate = ref(getTodayDateInputValue());
const venueCurrentPage = ref(1);
const venuePageSize = 6;
const equipmentFilterValue = ref('all');
const equipmentSortOrder = ref('asc');
const equipmentSearchQuery = ref('');
const equipmentCurrentPage = ref(1);
const equipmentPageSize = 8;
const weeklyVenueMap = ref({});
const venueLoading = ref(false);
const venueError = ref('');
const viewVenueRecord = ref(null);
const viewVenueLoading = ref(false);
const viewVenueError = ref('');
const viewEquipmentRecord = ref(null);
const viewEquipmentLoading = ref(false);
const viewEquipmentError = ref('');
const equipmentList = ref([]);
const equipmentLoading = ref(false);
const equipmentError = ref('');

let activeWeekRequestSequence = 0;

const selectedDateObject = computed(() => parseDateValue(selectedVenueDate.value) || new Date());
const selectedMonthShortLabel = computed(() => new Intl.DateTimeFormat('en-US', { month: 'short' }).format(selectedDateObject.value).toUpperCase());
const selectedDayNumberLabel = computed(() => new Intl.DateTimeFormat('en-US', { day: 'numeric' }).format(selectedDateObject.value));
const selectedLongDateLabel = computed(() => new Intl.DateTimeFormat('en-US', {
  month: 'long',
  day: 'numeric',
  year: 'numeric',
  timeZone: 'Asia/Manila',
}).format(selectedDateObject.value));
const selectedWeekdayLabel = computed(() => new Intl.DateTimeFormat('en-US', {
  weekday: 'long',
  timeZone: 'Asia/Manila',
}).format(selectedDateObject.value));
const selectedMonthLabel = computed(() => new Intl.DateTimeFormat('en-US', {
  month: 'long',
  year: 'numeric',
  timeZone: 'Asia/Manila',
}).format(selectedDateObject.value));

const visibleWeekDates = computed(() => buildWeekDateValues(selectedVenueDate.value));
const selectedDayVenues = computed(() => weeklyVenueMap.value[selectedVenueDate.value] || []);
const selectedDaySummary = computed(() => {
  const venues = selectedDayVenues.value;
  return {
    availableCount: venues.length,
    bookableCount: venues.length,
  };
});

const visibleCalendarColumns = computed(() => {
  const sourceDates = calendarViewMode.value === 'daily'
    ? [selectedVenueDate.value]
    : visibleWeekDates.value;

  return sourceDates.map((dateValue) => {
    const dateObject = parseDateValue(dateValue) || selectedDateObject.value;
    const venues = weeklyVenueMap.value[dateValue] || [];
    const entries = venues.slice(0, calendarViewMode.value === 'daily' ? 8 : 5).map((venue) => {
      const statusTone = resolveVenueStatusTone(venue, dateValue);

      return {
        ...venue,
        statusTone,
        metaLine: resolveAvailabilityMetaLine(venue, dateValue),
        descriptionLine: venue.venueLocation || venue.description || 'Venue details available on click.',
        footerLabel: statusTone === 'future'
          ? `Blocked on ${formatDisplayDate(dateValue)}`
          : 'Reserved for this date',
        capacityLabel: `Cap ${venue.capacityLimit || 'N/A'}`,
      };
    });

    return {
      dateValue,
      weekdayLabel: new Intl.DateTimeFormat('en-US', { weekday: 'long', timeZone: 'Asia/Manila' }).format(dateObject),
      shortDateLabel: new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', timeZone: 'Asia/Manila' }).format(dateObject),
      availableCount: entries.length,
      entries,
    };
  });
});

const monthCalendarCells = computed(() => buildMonthCalendarCells(selectedDateObject.value, selectedVenueDate.value));
const venueCardRecords = computed(() => (
  [...selectedDayVenues.value]
    .filter((venueRecord) => matchesVenueAvailability(venueRecord, venueFilterValue.value))
    .sort((left, right) => compareByName(left?.venueName, right?.venueName, venueSortOrder.value))
    .map((venueRecord) => {
      const venueStatusTone = resolveVenueStatusTone(venueRecord, selectedVenueDate.value);
      return {
        ...venueRecord,
        venueStatusTone,
        venueStatusLabel: resolveVenueStatusLabel(venueStatusTone),
      };
    })
));

const venueTotalPages = computed(() => Math.max(1, Math.ceil(venueCardRecords.value.length / venuePageSize)));
const paginatedVenueCards = computed(() => {
  const startIndex = (venueCurrentPage.value - 1) * venuePageSize;
  return venueCardRecords.value.slice(startIndex, startIndex + venuePageSize);
});

const filteredEquipment = computed(() => {
  const normalizedQuery = normalizeSearchText(equipmentSearchQuery.value);

  let filtered = equipmentList.value.filter((equipment) => (
    normalizedQuery === ''
    || getEquipmentSearchableValues(equipment).some((value) => normalizeSearchText(value).includes(normalizedQuery))
  ));

  if (equipmentFilterValue.value === 'available') {
    filtered = filtered.filter((equipment) => formatEquipmentStatus(equipment) === 'Available');
  } else if (equipmentFilterValue.value === 'maintenance') {
    filtered = filtered.filter((equipment) => formatEquipmentStatus(equipment) === 'Under Maintenance');
  }

  return [...filtered].sort((left, right) => {
    const leftName = normalizeSearchText(left?.equipmentName);
    const rightName = normalizeSearchText(right?.equipmentName);

    return equipmentSortOrder.value === 'asc'
      ? leftName.localeCompare(rightName)
      : rightName.localeCompare(leftName);
  });
});

const equipmentTotalPages = computed(() => Math.max(1, Math.ceil(filteredEquipment.value.length / equipmentPageSize)));
const paginatedEquipment = computed(() => {
  const startIndex = (equipmentCurrentPage.value - 1) * equipmentPageSize;
  return filteredEquipment.value.slice(startIndex, startIndex + equipmentPageSize);
});

const featuredEquipment = computed(() => filteredEquipment.value.slice(0, 4));
const equipmentCardsToRender = computed(() => (
  activeFacilityTab.value === 'equipment'
    ? paginatedEquipment.value
    : featuredEquipment.value
));

watch(selectedVenueDate, async () => {
  venueCurrentPage.value = 1;
  await fetchVenuesForVisibleWeek();
}, { immediate: true });

watch(activeFacilityTab, () => {
  venueCurrentPage.value = 1;
  equipmentCurrentPage.value = 1;
});

watch([equipmentFilterValue, equipmentSortOrder, equipmentSearchQuery], () => {
  equipmentCurrentPage.value = 1;
});

watch(venueTotalPages, (nextPageCount) => {
  if (venueCurrentPage.value > nextPageCount) {
    venueCurrentPage.value = nextPageCount;
  }
});

watch(equipmentTotalPages, (nextPageCount) => {
  if (equipmentCurrentPage.value > nextPageCount) {
    equipmentCurrentPage.value = nextPageCount;
  }
});

fetchEquipment();

async function fetchVenuesForVisibleWeek() {
  const currentRequestSequence = activeWeekRequestSequence + 1;
  activeWeekRequestSequence = currentRequestSequence;
  venueLoading.value = true;
  venueError.value = '';

  try {
    const weekDateValues = buildWeekDateValues(selectedVenueDate.value);
    const responses = await Promise.all(weekDateValues.map(async (dateValue) => {
      const response = await venueApi.listVenues({
        selectedDate: dateValue,
        reservedOnly: true,
      });
      const venuePayload = response?.data?.venues || response?.venues || [];
      const normalizedVenues = Array.isArray(venuePayload)
        ? venuePayload
          .map((venueRecord) => normalizeVenueRecord(venueRecord))
          .filter(Boolean)
          .filter((venueRecord) => !isVenueFloorPlaceholderRecord(venueRecord))
          .filter((venueRecord) => Array.isArray(venueRecord.reservationTimeRanges) && venueRecord.reservationTimeRanges.length > 0)
        : [];

      return [dateValue, normalizedVenues];
    }));

    if (currentRequestSequence !== activeWeekRequestSequence) {
      return;
    }

    weeklyVenueMap.value = Object.fromEntries(responses);
  } catch (error) {
    if (currentRequestSequence !== activeWeekRequestSequence) {
      return;
    }

    weeklyVenueMap.value = {};
    venueError.value = error?.response?.data?.errorMessage || 'Failed to load weekly venue availability.';
  } finally {
    if (currentRequestSequence === activeWeekRequestSequence) {
      venueLoading.value = false;
    }
  }
}

async function fetchEquipment() {
  try {
    equipmentLoading.value = true;
    equipmentError.value = '';
    const response = await equipmentApi.listEquipment();
    equipmentList.value = response?.data?.equipment || [];
  } catch (error) {
    equipmentList.value = [];
    equipmentError.value = error?.response?.data?.errorMessage || 'Failed to load equipment records.';
  } finally {
    equipmentLoading.value = false;
  }
}

async function handleViewVenueDetails(venueRecord) {
  const normalizedVenueRecord = normalizeVenueRecord(venueRecord);
  const venueIdentifier = normalizedVenueRecord?.venueIdentifier;

  viewVenueRecord.value = normalizedVenueRecord;
  viewVenueLoading.value = Boolean(venueIdentifier);
  viewVenueError.value = '';

  if (!venueIdentifier) {
    return;
  }

  try {
    const response = await venueApi.getVenueById(venueIdentifier);
    viewVenueRecord.value = normalizeVenueRecord(response?.data || response);
  } catch (error) {
    viewVenueError.value = error?.response?.data?.errorMessage || 'Failed to load venue details.';
  } finally {
    viewVenueLoading.value = false;
  }
}

async function handleViewEquipmentDetails(equipmentRecord) {
  if (!equipmentRecord?.equipmentIdentifier) {
    return;
  }

  viewEquipmentLoading.value = true;
  viewEquipmentError.value = '';
  viewEquipmentRecord.value = null;

  try {
    const response = await equipmentApi.getEquipmentById(equipmentRecord.equipmentIdentifier);
    viewEquipmentRecord.value = response?.data || response;
  } catch (error) {
    viewEquipmentError.value = error?.response?.data?.errorMessage || 'Failed to load equipment details.';
  } finally {
    viewEquipmentLoading.value = false;
  }
}

function closeVenueDetails() {
  viewVenueRecord.value = null;
  viewVenueError.value = '';
  viewVenueLoading.value = false;
}

function closeEquipmentDetails() {
  viewEquipmentRecord.value = null;
  viewEquipmentError.value = '';
  viewEquipmentLoading.value = false;
}

function normalizeVenueRecord(venue) {
  if (!venue) {
    return null;
  }

  return {
    venueIdentifier: venue.venueIdentifier ?? venue.venue_identifier ?? venue.id ?? venue.identifier ?? null,
    venueName: venue.venueName || '',
    venueLocation: venue.venueLocation || '',
    floorLevel: venue.floorLevel || 'Other',
    capacityLimit: venue.capacityLimit ?? null,
    availabilityDate: venue.availabilityDate || '',
    operationalStatus: venue.operationalStatus || '',
    availabilityStatus: venue.availabilityStatus || 'Unavailable',
    description: venue.description || '',
    imageUrl: venue.imageUrl || '',
    reservationTimeRanges: Array.isArray(venue.reservationTimeRanges) ? venue.reservationTimeRanges : [],
  };
}

function matchesVenueAvailability(venueRecord, filterValue) {
  const statusTone = resolveVenueStatusTone(venueRecord, selectedVenueDate.value);
  const hasReservationBlocks = Array.isArray(venueRecord?.reservationTimeRanges) && venueRecord.reservationTimeRanges.length > 0;

  if (filterValue === 'available') {
    return hasReservationBlocks && statusTone !== 'maintenance';
  }

  if (filterValue === 'future') {
    return statusTone === 'future';
  }

  if (filterValue === 'maintenance') {
    return statusTone === 'maintenance';
  }

  return true;
}

function resolveVenueStatusTone(venueRecord, dateValue) {
  const operationalStatus = String(venueRecord?.operationalStatus || '').trim();
  if (operationalStatus === 'Maintenance' || operationalStatus === 'Inactive') {
    return 'maintenance';
  }

  const availabilityStatus = deriveVenueAvailabilityForDate(venueRecord, dateValue);
  return availabilityStatus === 'Available' ? 'available' : 'future';
}

function resolveVenueStatusLabel(statusTone) {
  if (statusTone === 'maintenance') {
    return 'Maintenance';
  }

  if (statusTone === 'future') {
    return 'Reserved Block';
  }

  return 'Reserved';
}

function resolveAvailabilityMetaLine(venueRecord, dateValue) {
  const statusTone = resolveVenueStatusTone(venueRecord, dateValue);

  if (statusTone === 'future') {
    return 'Blocked reservation window';
  }

  if (statusTone === 'maintenance') {
    return 'Maintenance notice';
  }

  if (Array.isArray(venueRecord?.reservationTimeRanges) && venueRecord.reservationTimeRanges.length > 0) {
    return venueRecord.reservationTimeRanges[0];
  }

  return 'Reserved room';
}

function compareByName(leftName, rightName, sortDirection) {
  const normalizedLeft = normalizeSearchText(leftName);
  const normalizedRight = normalizeSearchText(rightName);

  return sortDirection === 'asc'
    ? normalizedLeft.localeCompare(normalizedRight)
    : normalizedRight.localeCompare(normalizedLeft);
}

function getEquipmentSearchableValues(equipment) {
  return [
    equipment?.equipmentName,
    equipment?.equipmentCategory || equipment?.categoryName,
    equipment?.equipmentBrand,
    equipment?.description || equipment?.scheduleDescription,
  ];
}

function normalizeSearchText(value) {
  return String(value || '').trim().toLowerCase();
}

function setSelectedDateToToday() {
  selectedVenueDate.value = getTodayDateInputValue();
}

function shiftSelectedDate(dayOffset) {
  const sourceDate = parseDateValue(selectedVenueDate.value) || new Date();
  sourceDate.setDate(sourceDate.getDate() + dayOffset);
  selectedVenueDate.value = formatDateInputValue(sourceDate);
}

function shiftSelectedMonth(monthOffset) {
  const sourceDate = parseDateValue(selectedVenueDate.value) || new Date();
  sourceDate.setMonth(sourceDate.getMonth() + monthOffset);
  selectedVenueDate.value = formatDateInputValue(sourceDate);
}

function buildWeekDateValues(dateValue) {
  const selectedDate = parseDateValue(dateValue) || new Date();
  const monday = getStartOfWeek(selectedDate);

  return Array.from({ length: 7 }, (_, index) => {
    const nextDate = new Date(monday);
    nextDate.setDate(monday.getDate() + index);
    return formatDateInputValue(nextDate);
  });
}

function buildMonthCalendarCells(date, selectedDateValue) {
  const year = date.getFullYear();
  const month = date.getMonth();
  const firstDayOfMonth = new Date(year, month, 1);
  const lastDayOfMonth = new Date(year, month + 1, 0);
  const leadingDays = firstDayOfMonth.getDay();
  const trailingDays = 6 - lastDayOfMonth.getDay();
  const cells = [];

  for (let index = leadingDays; index > 0; index -= 1) {
    const cellDate = new Date(year, month, 1 - index);
    cells.push(createCalendarCell(cellDate, false, selectedDateValue));
  }

  for (let dayNumber = 1; dayNumber <= lastDayOfMonth.getDate(); dayNumber += 1) {
    const cellDate = new Date(year, month, dayNumber);
    cells.push(createCalendarCell(cellDate, true, selectedDateValue));
  }

  for (let index = 1; index <= trailingDays; index += 1) {
    const cellDate = new Date(year, month + 1, index);
    cells.push(createCalendarCell(cellDate, false, selectedDateValue));
  }

  return cells;
}

function createCalendarCell(date, inCurrentMonth, selectedDateValue) {
  return {
    key: `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`,
    dateValue: formatDateInputValue(date),
    dayNumber: date.getDate(),
    inCurrentMonth,
    isSelected: formatDateInputValue(date) === selectedDateValue,
  };
}

function getStartOfWeek(sourceDate) {
  const normalizedDate = new Date(sourceDate);
  const dayIndex = normalizedDate.getDay();
  const mondayOffset = dayIndex === 0 ? -6 : 1 - dayIndex;
  normalizedDate.setDate(normalizedDate.getDate() + mondayOffset);
  normalizedDate.setHours(0, 0, 0, 0);
  return normalizedDate;
}

function parseDateValue(dateValue) {
  const normalizedValue = String(dateValue || '').trim();
  if (normalizedValue === '') {
    return null;
  }

  const parsedDate = new Date(`${normalizedValue}T00:00:00`);
  return Number.isNaN(parsedDate.getTime()) ? null : parsedDate;
}

function formatDateInputValue(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function getTodayDateInputValue() {
  return formatDateInputValue(new Date());
}
</script>
