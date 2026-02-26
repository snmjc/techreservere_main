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
        <span
          v-for="venueRecord in floorGroup.venueRecords"
          :key="venueRecord.venueName"
          class="facility-venue-chip"
          :class="{
            'facility-venue-chip--available': venueRecord.venueAvailable,
            'facility-venue-chip--unavailable': !venueRecord.venueAvailable,
          }"
        >
          {{ venueRecord.venueName }}
        </span>
      </div>
    </div>
    <div v-if="filteredVenueFloorGroups.length === 0" class="facility-venue-empty-state">
      No venues found.
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

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

/**
 * @function filteredVenueFloorGroups
 * @description Filters venue floor groups based on the availability filter.
 * @returns {Array<Object>}
 */
const filteredVenueFloorGroups = computed(() => {
  if (props.availabilityFilter === 'all') {
    return props.venueFloorGroups;
  }
  const isAvailableFilter = props.availabilityFilter === 'available';
  return props.venueFloorGroups
    .map((floorGroup) => ({
      ...floorGroup,
      venueRecords: floorGroup.venueRecords.filter(
        (venue) => venue.venueAvailable === isAvailableFilter
      ),
    }))
    .filter((floorGroup) => floorGroup.venueRecords.length > 0);
});
</script>
