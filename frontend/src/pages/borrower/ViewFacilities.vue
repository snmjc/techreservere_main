<!-- ===== AI GENERATED: BorrowerViewFacilitiesPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'DELA CRUZ, JUAN'"
    :navigation-items="borrowerNavigationItems"
  >
    <!-- Page Header -->
    <div class="view-facilities-page-header">
      <h2 class="view-facilities-page-heading">Facilities</h2>
    </div>

    <!-- Tabs Section -->
    <div class="view-facilities-tabs">
      <button
        class="view-facilities-tab"
        :class="{ 'view-facilities-tab--active': activeFacilityTab === 'venue' }"
        @click="activeFacilityTab = 'venue'"
      >
        Venue
      </button>
      <button
        class="view-facilities-tab"
        :class="{ 'view-facilities-tab--active': activeFacilityTab === 'equipment' }"
        @click="activeFacilityTab = 'equipment'"
      >
        Equipment
      </button>
    </div>

    <!-- Venue Tab Content -->
    <div v-if="activeFacilityTab === 'venue'" class="view-facilities-content">
      <!-- Filter & Legend Section -->
      <div class="view-facilities-toolbar">
        <div class="view-facilities-filter-group">
          <label for="venueFilter" class="view-facilities-filter-label">Filter:</label>
          <select v-model="venueFilterValue" id="venueFilter" class="view-facilities-filter-select">
            <option value="all">All Venues</option>
            <option value="available">Available Only</option>
            <option value="unavailable">Unavailable Only</option>
          </select>
          <button class="view-facilities-sort-button" @click="venueSortOrder = venueSortOrder === 'asc' ? 'desc' : 'asc'" :title="venueSortOrder === 'asc' ? 'Sort A-Z' : 'Sort Z-A'">
            <svg v-if="venueSortOrder === 'asc'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <polyline points="19 12 12 19 5 12"></polyline>
            </svg>
            <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="19" x2="12" y2="5"></line>
              <polyline points="5 12 12 5 19 12"></polyline>
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

      <!-- Venues Grid by Floor -->
      <div class="view-facilities-venues-grid">
        <div
          v-for="floorGroup in filteredVenueFloorGroups"
          :key="floorGroup.floorLabel"
          class="view-facilities-floor-section"
        >
          <h3 class="view-facilities-floor-heading">{{ floorGroup.floorLabel }}</h3>
          <div class="view-facilities-venue-grid">
            <div
              v-for="venue in floorGroup.venueRecords"
              :key="venue.venueName"
              class="view-facilities-venue-card"
              :class="{
                'view-facilities-venue-card--available': venue.venueAvailable,
                'view-facilities-venue-card--unavailable': !venue.venueAvailable,
              }"
            >
              <div class="view-facilities-venue-card-header">
                <h4 class="view-facilities-venue-name">{{ venue.venueName }}</h4>
                <div class="view-facilities-venue-status">
                  <span class="view-facilities-status-badge" :class="venue.venueAvailable ? 'available' : 'unavailable'">
                    {{ venue.venueAvailable ? 'Available' : 'Unavailable' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="filteredVenueFloorGroups.length === 0" class="view-facilities-empty-state">
          <p>No venues found matching your filter.</p>
        </div>
      </div>
    </div>

    <!-- Equipment Tab Content -->
    <div v-if="activeFacilityTab === 'equipment'" class="view-facilities-content">
      <div class="view-facilities-toolbar">
        <div class="view-facilities-filter-group">
          <input
            v-model.trim="equipmentSearchQuery"
            type="text"
            class="view-facilities-search-input"
            placeholder="Search equipment"
          />
          <label for="equipmentFilter" class="view-facilities-filter-label">Filter:</label>
          <select v-model="equipmentFilterValue" id="equipmentFilter" class="view-facilities-filter-select">
            <option value="all">All</option>
            <option value="available">Available</option>
            <option value="maintenance">Under Maintenance</option>
          </select>
          <button class="view-facilities-sort-button" @click="equipmentSortOrder = equipmentSortOrder === 'asc' ? 'desc' : 'asc'" :title="equipmentSortOrder === 'asc' ? 'Sort A-Z' : 'Sort Z-A'">
            <svg v-if="equipmentSortOrder === 'asc'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <polyline points="19 12 12 19 5 12"></polyline>
            </svg>
            <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="19" x2="12" y2="5"></line>
              <polyline points="5 12 12 5 19 12"></polyline>
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
            Restricted from requestors
          </span>
        </div>
      </div>

      <p v-if="equipmentError" class="view-facilities-feedback view-facilities-feedback--error">{{ equipmentError }}</p>
      <div v-if="equipmentLoading" class="view-facilities-empty-state">
        <p>Loading equipment records...</p>
      </div>
      <div v-else class="view-facilities-equipment-grid">
        <div
          v-for="equipment in filteredEquipment"
          :key="equipment.equipmentIdentifier"
          class="view-facilities-equipment-chip view-facilities-equipment-chip--card"
          :class="{
            'view-facilities-equipment-chip--available': equipment.equipmentState === 'Available',
            'view-facilities-equipment-chip--unavailable': equipment.equipmentState !== 'Available',
          }"
        >
          <div class="view-facilities-equipment-chip-header">
            <span class="view-facilities-equipment-chip-name">{{ equipment.equipmentName }}</span>
            <span class="view-facilities-status-badge" :class="equipment.equipmentState === 'Available' ? 'available' : 'unavailable'">
              {{ equipment.equipmentState }}
            </span>
          </div>
          <p class="view-facilities-equipment-detail"><strong>Category:</strong> {{ equipment.categoryName }}</p>
          <p class="view-facilities-equipment-detail"><strong>Available:</strong> {{ equipment.availableQuantity }} / {{ equipment.totalQuantity }}</p>
          <p class="view-facilities-equipment-detail"><strong>Description:</strong> {{ equipment.scheduleDescription || 'N/A' }}</p>
        </div>
      </div>

      <div v-if="!equipmentLoading && filteredEquipment.length === 0" class="view-facilities-empty-state">
        <p>No equipment found matching your filter.</p>
      </div>
    </div>

    <!-- Footer -->
    <div class="view-facilities-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/ViewFacilities.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import equipmentApi from '@/modules/reservation/services/equipmentApi.js';

const activeFacilityTab = ref('venue');
const venueFilterValue = ref('all');
const venueSortOrder = ref('asc');
const equipmentFilterValue = ref('all');
const equipmentSortOrder = ref('asc');
const equipmentSearchQuery = ref('');
const equipmentList = ref([]);
const equipmentLoading = ref(false);
const equipmentError = ref('');

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
 * @function filteredVenueFloorGroups
 * @description Filters venue floor groups based on availability filter and applies sorting.
 * @returns {Array<Object>}
 */
const filteredVenueFloorGroups = computed(() => {
  let filtered = venueFloorGroupsList.value;
  
  // Apply availability filter
  if (venueFilterValue.value !== 'all') {
    const isAvailableFilter = venueFilterValue.value === 'available';
    filtered = filtered
      .map((floorGroup) => ({
        ...floorGroup,
        venueRecords: floorGroup.venueRecords.filter(
          (venue) => venue.venueAvailable === isAvailableFilter
        ),
      }))
      .filter((floorGroup) => floorGroup.venueRecords.length > 0);
  }
  
  // Apply sorting to venue names within each floor group
  return filtered.map((floorGroup) => ({
    ...floorGroup,
    venueRecords: [...floorGroup.venueRecords].sort((a, b) => {
      const nameA = a.venueName.toLowerCase();
      const nameB = b.venueName.toLowerCase();
      if (venueSortOrder.value === 'asc') {
        return nameA.localeCompare(nameB);
      } else {
        return nameB.localeCompare(nameA);
      }
    }),
  }));
});

/**
 * @function filteredEquipment
 * @description Filters equipment categories based on availability filter and applies sorting.
 * @returns {Array<Object>}
 */
const filteredEquipment = computed(() => {
  const normalizedQuery = equipmentSearchQuery.value.toLowerCase();

  let filtered = equipmentList.value.filter((equipment) => {
    return normalizedQuery === ''
      || equipment.equipmentName.toLowerCase().includes(normalizedQuery)
      || equipment.categoryName.toLowerCase().includes(normalizedQuery);
  });

  if (equipmentFilterValue.value === 'available') {
    filtered = filtered.filter((equipment) => equipment.equipmentState === 'Available');
  } else if (equipmentFilterValue.value === 'maintenance') {
    filtered = filtered.filter((equipment) => equipment.equipmentState === 'Under Maintenance');
  }

  return [...filtered].sort((a, b) => {
    const nameA = a.equipmentName.toLowerCase();
    const nameB = b.equipmentName.toLowerCase();
    if (equipmentSortOrder.value === 'asc') {
      return nameA.localeCompare(nameB);
    } else {
      return nameB.localeCompare(nameA);
    }
  });
});

onMounted(() => {
  fetchEquipment();
});

async function fetchEquipment() {
  try {
    equipmentLoading.value = true;
    equipmentError.value = '';
    const response = await equipmentApi.listEquipment();
    equipmentList.value = response?.data?.equipment || [];
  } catch (error) {
    equipmentList.value = [];
    equipmentError.value = error?.response?.data?.errorMessage || 'Failed to load equipment list.';
  } finally {
    equipmentLoading.value = false;
  }
}
</script>
