<!-- ===== AI GENERATED: AdminManageFacilitiesPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <!-- Page Heading -->
    <h2 class="manage-facilities-page-heading">Facilities</h2>

    <!-- Tabs: Venue | Equipment -->
    <div class="manage-facilities-tabs-row">
      <button
        class="manage-facilities-tab-button"
        :class="{ 'manage-facilities-tab-button--active': activeFacilityTab === 'venue' }"
        @click="handleFacilityTabChange('venue')"
      >
        Venue
      </button>
      <div class="manage-facilities-tab-divider"></div>
      <button
        class="manage-facilities-tab-button"
        :class="{ 'manage-facilities-tab-button--active': activeFacilityTab === 'equipment' }"
        @click="handleFacilityTabChange('equipment')"
      >
        Equipment
      </button>

      <div class="manage-facilities-toolbar-spacer"></div>

      <!-- Action Buttons -->
      <button class="manage-facilities-edit-button">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
        </svg>
        {{ activeFacilityTab === 'venue' ? 'Edit Venue' : 'Edit Equipment' }}
      </button>
      <button class="manage-facilities-add-button">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"/>
          <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        {{ activeFacilityTab === 'venue' ? 'Add Venue' : 'Add Equipment' }}
      </button>
    </div>

    <!-- Filter Pills -->
    <div class="manage-facilities-filter-row">
      <button
        class="manage-facilities-filter-pill"
        :class="{ 'manage-facilities-filter-pill--active': availabilityFilter === 'all' }"
        @click="availabilityFilter = 'all'"
      >
        All
      </button>
      <button
        class="manage-facilities-filter-pill"
        :class="{ 'manage-facilities-filter-pill--active': availabilityFilter === 'available' }"
        @click="availabilityFilter = 'available'"
      >
        Available
      </button>
      <button
        class="manage-facilities-filter-pill"
        :class="{ 'manage-facilities-filter-pill--active': availabilityFilter === 'unavailable' }"
        @click="availabilityFilter = 'unavailable'"
      >
        Unavailable
      </button>
    </div>

    <!-- Showing Row + Legend -->
    <div class="manage-facilities-showing-row">
      <div class="manage-facilities-showing-group">
        <label class="manage-facilities-showing-label" for="facilityShowingSelect">Showing:</label>
        <select
          id="facilityShowingSelect"
          v-model="showingFilterValue"
          class="manage-facilities-showing-select"
        >
          <option value="all">All</option>
        </select>
        <button class="manage-facilities-sort-button" aria-label="Sort">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <polyline points="19 12 12 19 5 12"/>
          </svg>
        </button>
      </div>
      <div class="manage-facilities-legend">
        <span class="manage-facilities-legend-item">
          <span class="manage-facilities-legend-dot manage-facilities-legend-dot--available"></span>
          Available
        </span>
        <span class="manage-facilities-legend-item">
          <span class="manage-facilities-legend-dot manage-facilities-legend-dot--unavailable"></span>
          Unavailable
        </span>
      </div>
    </div>

    <!-- Venue Tab Content -->
    <FacilityVenueListComponent
      v-if="activeFacilityTab === 'venue'"
      :venue-floor-groups="venueFloorGroupsList"
      :availability-filter="availabilityFilter"
    />

    <!-- Equipment Tab Content -->
    <FacilityEquipmentGridComponent
      v-if="activeFacilityTab === 'equipment'"
      :equipment-categories="equipmentCategoriesList"
      :availability-filter="availabilityFilter"
    />

    <!-- Footer -->
    <div class="manage-facilities-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/adminManageFacilitiesPage.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import FacilityVenueListComponent from '@/modules/facility/components/FacilityVenueListComponent.vue';
import FacilityEquipmentGridComponent from '@/modules/facility/components/FacilityEquipmentGridComponent.vue';

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
 * @description Static venue data grouped by floor for display.
 */
const venueFloorGroupsList = ref([
  {
    floorLabel: '5th Floor',
    venueRecords: [
      { venueName: 'BF Roofteck', venueAvailable: true },
    ],
  },
  {
    floorLabel: '3rd Floor',
    venueRecords: [
      { venueName: 'GF MFA', venueAvailable: false },
      { venueName: 'Basketball without edit Soccer', venueAvailable: false },
      { venueName: 'Basketball gym with edit Soccer', venueAvailable: true },
      { venueName: 'Guard house with Security', venueAvailable: true },
    ],
  },
  {
    floorLabel: 'MH Floor',
    venueRecords: [
      { venueName: 'FS03 Audio Visual Room', venueAvailable: true },
      { venueName: 'FS03 Class Room', venueAvailable: true },
    ],
  },
  {
    floorLabel: '4th Floor',
    venueRecords: [
      { venueName: 'F401 Multipurpose Hall', venueAvailable: true },
      { venueName: 'F502 Auditorium', venueAvailable: true },
      { venueName: 'F404 Multimedia Room', venueAvailable: true },
    ],
  },
  {
    floorLabel: '1st Floor',
    venueRecords: [
      { venueName: 'BF Golic Lounge 1', venueAvailable: false },
      { venueName: 'BF Golic Lounge 2', venueAvailable: false },
      { venueName: 'BF Boss Lounge 1', venueAvailable: true },
      { venueName: 'BF Student Lounge', venueAvailable: true },
    ],
  },
  {
    floorLabel: 'GF / 1st Floor',
    venueRecords: [
      { venueName: 'FMO1', venueAvailable: false },
      { venueName: 'FBO5', venueAvailable: false },
      { venueName: 'FMB6', venueAvailable: false },
      { venueName: 'V706', venueAvailable: true },
      { venueName: 'FH1', venueAvailable: true },
    ],
  },
  {
    floorLabel: 'Pool',
    venueRecords: [
      { venueName: 'FEU Tech Swimming Pool', venueAvailable: false },
    ],
  },
  {
    floorLabel: 'Outdoor',
    venueRecords: [
      { venueName: 'CF FIT Student', venueAvailable: true },
    ],
  },
]);

/**
 * @constant {Array<Object>} equipmentCategoriesList
 * @description Static equipment category data for display.
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
</script>
