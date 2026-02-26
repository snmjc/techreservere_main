<!-- ===== AI GENERATED: BorrowerViewFacilitiesPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'DELA CRUZ, JUAN'"
    :navigation-items="borrowerNavigationItems"
  >
    <!-- Page Heading -->
    <h2 class="view-facilities-page-heading">View Facilities</h2>

    <!-- Tabs: Venue | Equipment -->
    <div class="view-facilities-tabs-row">
      <button
        class="view-facilities-tab-button"
        :class="{ 'view-facilities-tab-button--active': activeFacilityTab === 'venue' }"
        @click="handleFacilityTabChange('venue')"
      >
        Venue
      </button>
      <div class="view-facilities-tab-divider"></div>
      <button
        class="view-facilities-tab-button"
        :class="{ 'view-facilities-tab-button--active': activeFacilityTab === 'equipment' }"
        @click="handleFacilityTabChange('equipment')"
      >
        Equipment
      </button>
    </div>

    <!-- Filter Pills -->
    <div class="view-facilities-filter-row">
      <button
        class="view-facilities-filter-pill"
        :class="{ 'view-facilities-filter-pill--active': availabilityFilter === 'all' }"
        @click="availabilityFilter = 'all'"
      >
        All
      </button>
      <button
        class="view-facilities-filter-pill"
        :class="{ 'view-facilities-filter-pill--active': availabilityFilter === 'available' }"
        @click="availabilityFilter = 'available'"
      >
        Available
      </button>
      <button
        class="view-facilities-filter-pill"
        :class="{ 'view-facilities-filter-pill--active': availabilityFilter === 'unavailable' }"
        @click="availabilityFilter = 'unavailable'"
      >
        Unavailable
      </button>
    </div>

    <!-- Showing Row + Legend -->
    <div class="view-facilities-showing-row">
      <div class="view-facilities-showing-group">
        <label class="view-facilities-showing-label" for="viewFacilityShowingSelect">Showing:</label>
        <select
          id="viewFacilityShowingSelect"
          v-model="showingFilterValue"
          class="view-facilities-showing-select"
        >
          <option value="all">All</option>
        </select>
        <button class="view-facilities-sort-button" aria-label="Sort">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19" />
            <polyline points="19 12 12 19 5 12" />
          </svg>
        </button>
      </div>
      <div class="view-facilities-legend">
        <span class="view-facilities-legend-item">
          <span class="view-facilities-legend-dot view-facilities-legend-dot--available"></span>
          Available
        </span>
        <span class="view-facilities-legend-item">
          <span class="view-facilities-legend-dot view-facilities-legend-dot--unavailable"></span>
          Unavailable
        </span>
      </div>
    </div>

    <!-- Venue Tab Content -->
    <div v-if="activeFacilityTab === 'venue'" class="view-facilities-venue-list">
      <div
        v-for="floorGroup in filteredVenueFloorGroups"
        :key="floorGroup.floorLabel"
        class="view-facilities-venue-floor-group"
      >
        <p class="view-facilities-venue-floor-label">{{ floorGroup.floorLabel }}</p>
        <div class="view-facilities-venue-chips-row">
          <span
            v-for="venueRecord in floorGroup.venueRecords"
            :key="venueRecord.venueName"
            class="view-facilities-venue-chip"
            :class="{
              'view-facilities-venue-chip--available': venueRecord.venueAvailable,
              'view-facilities-venue-chip--unavailable': !venueRecord.venueAvailable,
            }"
          >
            {{ venueRecord.venueName }}
          </span>
        </div>
      </div>
      <div v-if="filteredVenueFloorGroups.length === 0" class="view-facilities-venue-empty-state">
        No venues found.
      </div>
    </div>

    <!-- Equipment Tab Content -->
    <div v-if="activeFacilityTab === 'equipment'" class="view-facilities-equipment-grid">
      <span
        v-for="equipmentCategory in filteredEquipmentCategories"
        :key="equipmentCategory.categoryName"
        class="view-facilities-equipment-chip"
        :class="{
          'view-facilities-equipment-chip--available': equipmentCategory.categoryAvailable,
          'view-facilities-equipment-chip--unavailable': !equipmentCategory.categoryAvailable,
        }"
      >
        {{ equipmentCategory.categoryName }}
      </span>
      <div v-if="filteredEquipmentCategories.length === 0" class="view-facilities-equipment-empty-state">
        No equipment found.
      </div>
    </div>

    <!-- Footer -->
    <div class="view-facilities-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/borrowerViewFacilitiesPage.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';

const activeFacilityTab = ref('venue');
const availabilityFilter = ref('all');
const showingFilterValue = ref('all');

/**
 * @function handleFacilityTabChange
 * @description Switches active tab and resets filters.
 * @param {string} tabName - 'venue' or 'equipment'
 * @returns {void}
 */
function handleFacilityTabChange(tabName) {
  activeFacilityTab.value = tabName;
  availabilityFilter.value = 'all';
}

/**
 * @constant {Array<Object>} venueFloorGroupsList
 * @description Static venue data grouped by floor for borrower view.
 */
const venueFloorGroupsList = ref([
  {
    floorLabel: '18th Floor',
    venueRecords: [
      { venueName: '18F Roofdeck', venueAvailable: true },
    ],
  },
  {
    floorLabel: '17th Floor',
    venueRecords: [
      { venueName: '17F MPR', venueAvailable: true },
      { venueName: 'Basketball without Aircon', venueAvailable: true },
      { venueName: 'Basketball gym with Aircon', venueAvailable: true },
      { venueName: 'Basketball gym with Aircon and Green Matting', venueAvailable: true },
    ],
  },
  {
    floorLabel: '16th Floor',
    venueRecords: [
      { venueName: 'F1603 Audio Visual Room', venueAvailable: false },
      { venueName: 'F1604 Case Room', venueAvailable: true },
    ],
  },
  {
    floorLabel: '15th Floor',
    venueRecords: [
      { venueName: 'F1502 Multipurpose Room', venueAvailable: false },
      { venueName: 'F1503 Multipurpose Room', venueAvailable: false },
      { venueName: 'F1504 Multipurpose Room', venueAvailable: true },
    ],
  },
  {
    floorLabel: '8th Floor',
    venueRecords: [
      { venueName: '8F Exec. Lounge 1', venueAvailable: false },
      { venueName: '8F Exec. Lounge 2', venueAvailable: true },
      { venueName: '8F Exec. Lounge 1 and 2 Combined', venueAvailable: true },
      { venueName: '8F Student Lounge', venueAvailable: true },
    ],
  },
  {
    floorLabel: '4th - 7th Floor',
    venueRecords: [
      { venueName: 'F407', venueAvailable: true },
      { venueName: 'F503', venueAvailable: true },
      { venueName: 'F608', venueAvailable: true },
      { venueName: 'F704', venueAvailable: true },
      { venueName: 'F711', venueAvailable: true },
    ],
  },
  {
    floorLabel: '3rd Floor',
    venueRecords: [
      { venueName: 'FEU Tech Swimming Pool', venueAvailable: true },
    ],
  },
  {
    floorLabel: '2nd Floor',
    venueRecords: [
      { venueName: '2F FIT Student Plaza', venueAvailable: false },
    ],
  },
]);

/**
 * @constant {Array<Object>} equipmentCategoriesList
 * @description Static equipment category data for borrower view.
 */
const equipmentCategoriesList = ref([
  { categoryName: 'Chairs', categoryAvailable: true },
  { categoryName: 'Tables', categoryAvailable: true },
  { categoryName: 'Podium', categoryAvailable: true },
  { categoryName: 'Microphone', categoryAvailable: true },
  { categoryName: 'Wifi Card', categoryAvailable: true },
  { categoryName: 'Board Eraser System', categoryAvailable: true },
  { categoryName: 'Extension Cord', categoryAvailable: true },
  { categoryName: 'Flood Board', categoryAvailable: true },
  { categoryName: 'Stage', categoryAvailable: true },
  { categoryName: 'White Screen', categoryAvailable: true },
  { categoryName: 'Philippine Flag', categoryAvailable: true },
  { categoryName: 'FEU Tech Logo', categoryAvailable: true },
  { categoryName: 'LED Video Wall', categoryAvailable: true },
  { categoryName: 'Others', categoryAvailable: true },
]);

/**
 * @function filteredVenueFloorGroups
 * @description Filters venue floor groups based on availability filter.
 * @returns {Array<Object>}
 */
const filteredVenueFloorGroups = computed(() => {
  if (availabilityFilter.value === 'all') {
    return venueFloorGroupsList.value;
  }
  const isAvailableFilter = availabilityFilter.value === 'available';
  return venueFloorGroupsList.value
    .map((floorGroup) => ({
      ...floorGroup,
      venueRecords: floorGroup.venueRecords.filter(
        (venue) => venue.venueAvailable === isAvailableFilter
      ),
    }))
    .filter((floorGroup) => floorGroup.venueRecords.length > 0);
});

/**
 * @function filteredEquipmentCategories
 * @description Filters equipment categories based on availability filter.
 * @returns {Array<Object>}
 */
const filteredEquipmentCategories = computed(() => {
  if (availabilityFilter.value === 'all') {
    return equipmentCategoriesList.value;
  }
  const isAvailableFilter = availabilityFilter.value === 'available';
  return equipmentCategoriesList.value.filter(
    (category) => category.categoryAvailable === isAvailableFilter
  );
});
</script>
