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
        <article
          v-for="venueRecord in floorGroup.venueRecords"
          :key="venueRecord.venueIdentifier || venueRecord.venueName"
          class="facility-venue-card"
          :class="{
            'facility-venue-card--available': venueRecord.venueAvailable,
            'facility-venue-card--unavailable': !venueRecord.venueAvailable,
          }"
        >
          <img
            :src="resolveVenuePhoto(venueRecord)"
            :alt="`${venueRecord.venueName} photo`"
            class="facility-venue-card-photo"
          />

          <div class="facility-venue-card-header">
            <div>
              <span class="facility-venue-card-name">{{ venueRecord.venueName }}</span>
              <span class="facility-venue-card-status">{{ venueRecord.operationalStatus || 'N/A' }}</span>
            </div>
            <div class="facility-venue-card-actions">
              <button
                class="facility-venue-card-action"
                type="button"
                title="View venue details"
                @click="emit('view-venue', venueRecord)"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
              <button
                class="facility-venue-card-action"
                type="button"
                title="Edit venue"
                @click="emit('edit-venue', venueRecord)"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
              </button>
              <button
                class="facility-venue-card-action facility-venue-card-action--delete"
                type="button"
                title="Delete venue"
                @click="emit('delete-venue', venueRecord)"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"/>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
              </button>
            </div>
          </div>

          <div class="facility-venue-card-body">
            <span class="facility-venue-card-detail">{{ venueRecord.venueLocation || 'N/A' }}</span>
            <span class="facility-venue-card-detail">Capacity: {{ venueRecord.capacityLimit || 'N/A' }}</span>
            <span class="facility-venue-card-detail">Available: {{ venueRecord.availabilityDate || 'N/A' }}</span>
            <span class="facility-venue-card-detail">Status: {{ venueRecord.venueAvailable ? 'Available' : 'Unavailable' }}</span>
          </div>
        </article>
      </div>
    </div>
    <div v-if="filteredVenueFloorGroups.length === 0" class="facility-venue-empty-state">
      No venues found.
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { resolveVenuePhoto } from '@/modules/facility/utils/venueFormValidation.js';

const emit = defineEmits(['view-venue', 'edit-venue', 'delete-venue']);

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
