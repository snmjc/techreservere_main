<!-- ===== AI GENERATED: FacilityVenueListComponent ===== -->
<template>
  <div class="facility-venue-list">
    <div
      v-for="floorGroup in filteredVenueFloorGroups"
      :key="floorGroup.floorLabel"
      class="facility-venue-floor-group"
    >
      <p class="facility-venue-floor-label">{{ floorGroup.floorLabel }}</p>
      <div class="facility-venue-chips-row">
        <div
          v-for="venueRecord in floorGroup.venueRecords"
          :key="venueRecord.venueIdentifier || venueRecord.venueName"
          class="facility-venue-card"
          :class="{
            'facility-venue-card--available': venueRecord.venueAvailable,
            'facility-venue-card--unavailable': !venueRecord.venueAvailable,
          }"
        >
          <div class="facility-venue-card-header">
            <span class="facility-venue-card-name">{{ venueRecord.venueName }}</span>
            <div class="facility-venue-card-actions">
              <button
                class="facility-venue-card-action"
                @click="handleToggleAvailability(venueRecord)"
                title="Toggle availability"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M18.36 6.64a9 9 0 1 1-12.73 0"/>
                  <line x1="12" y1="2" x2="12" y2="12"/>
                </svg>
              </button>
              <button
                class="facility-venue-card-action"
                @click="handleEditVenue(venueRecord)"
                title="Edit venue"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
              </button>
              <button
                class="facility-venue-card-action facility-venue-card-action--delete"
                @click="handleDeleteVenue(venueRecord)"
                title="Delete venue"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"/>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
              </button>
            </div>
          </div>
          <div class="facility-venue-card-body">
            <span class="facility-venue-card-detail">{{ venueRecord.floorLevel || 'No floor' }}</span>
            <span class="facility-venue-card-detail">{{ venueRecord.venueLocation || 'No location' }}</span>
          </div>
        </div>
      </div>
    </div>
    <div v-if="filteredVenueFloorGroups.length === 0" class="facility-venue-empty-state">
      No venues found.
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const emit = defineEmits(['edit-venue', 'delete-venue', 'toggle-availability']);

/**
 * @typedef {Object} FacilityVenueListProps
 * @property {Array<Object>} venueFloorGroups - Array of floor groups with venues
 * @property {string} availabilityFilter - 'all', 'available', or 'unavailable'
 */
const props = defineProps({
  venueFloorGroups: {
    type: Array,
    required: true,
  },
  availabilityFilter: {
    type: String,
    required: false,
    default: 'all',
  },
});

function handleEditVenue(venue) {
  emit('edit-venue', venue);
}

function handleDeleteVenue(venue) {
  emit('delete-venue', venue.venueIdentifier);
}

function handleToggleAvailability(venue) {
  emit('toggle-availability', venue);
}

/**
 * @function filteredVenueFloorGroups
 * @description Filters venue floor groups based on the availability filter.
 * @returns {Array<Object>}
 */
const filteredVenueFloorGroups = computed(() => {
  const floorGroups = props.venueFloorGroups || [];
  if (props.availabilityFilter === 'all') {
    return floorGroups;
  }
  const isAvailableFilter = props.availabilityFilter === 'available';
  return floorGroups
    .map((floorGroup) => ({
      ...floorGroup,
      venueRecords: (floorGroup.venueRecords || []).filter(
        (venue) => venue.venueAvailable === isAvailableFilter
      ),
    }))
    .filter((floorGroup) => floorGroup.venueRecords.length > 0);
});
</script>
