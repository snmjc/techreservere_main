<!-- ===== AI GENERATED: BorrowerViewFacilitiesPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'DELA CRUZ, JUAN'"
    :navigation-items="borrowerNavigationItems"
  >
    <div class="view-facilities-page-header">
      <h2 class="view-facilities-page-heading">Facilities</h2>
    </div>

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

    <div v-if="activeFacilityTab === 'venue'" class="view-facilities-content">
      <div class="view-facilities-toolbar">
        <div class="view-facilities-filter-group">
          <label for="venueFilter" class="view-facilities-filter-label">Filter:</label>
          <select v-model="venueFilterValue" id="venueFilter" class="view-facilities-filter-select">
            <option value="all">All Venues</option>
            <option value="available">Available Only</option>
            <option value="unavailable">Unavailable Only</option>
          </select>
          <label for="venueDate" class="view-facilities-filter-label">Date:</label>
          <input
            id="venueDate"
            v-model="selectedVenueDate"
            type="date"
            class="view-facilities-filter-select view-facilities-filter-select--date"
          />
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

      <p v-if="venueError" class="view-facilities-feedback view-facilities-feedback--error">{{ venueError }}</p>
      <div v-if="venueLoading" class="view-facilities-empty-state">
        <p>Loading venue records...</p>
      </div>

      <div v-else class="view-facilities-venues-grid">
        <div
          v-for="floorGroup in filteredVenueFloorGroups"
          :key="floorGroup.floorLabel"
          class="view-facilities-floor-section"
        >
          <h3 class="view-facilities-floor-heading">{{ floorGroup.floorLabel }}</h3>
          <div class="view-facilities-venue-grid">
            <button
              v-for="venue in floorGroup.venueRecords"
              :key="venue.venueIdentifier || venue.venueName"
              type="button"
              class="view-facilities-venue-card"
              :class="{
                'view-facilities-venue-card--available': venue.venueAvailable,
                'view-facilities-venue-card--unavailable': !venue.venueAvailable,
              }"
              @click="handleViewVenueDetails(venue)"
            >
              <div class="view-facilities-venue-media">
                <img
                  :src="resolveVenuePhoto(venue)"
                  :alt="`${venue.venueName} photo`"
                  class="view-facilities-venue-image"
                />
              </div>
              <div class="view-facilities-venue-card-header">
                <h4 class="view-facilities-venue-name">{{ venue.venueName }}</h4>
                <div class="view-facilities-venue-status">
                  <span class="view-facilities-status-badge" :class="venue.venueAvailable ? 'available' : 'unavailable'">
                    {{ venue.venueAvailable ? 'Available' : 'Unavailable' }}
                  </span>
                </div>
              </div>
              <div class="view-facilities-venue-card-body">
                <p class="view-facilities-venue-detail"><strong>Location:</strong> {{ venue.venueLocation || 'N/A' }}</p>
                <p class="view-facilities-venue-detail"><strong>Capacity:</strong> {{ venue.capacityLimit || 'N/A' }}</p>
                <p class="view-facilities-venue-detail"><strong>Availability Date:</strong> {{ formatDisplayDate(venue.availabilityDate) }}</p>
                <p class="view-facilities-venue-detail"><strong>Operational Status:</strong> {{ venue.operationalStatus || 'N/A' }}</p>
              </div>
              <span class="view-facilities-venue-link">View Details</span>
            </button>
          </div>
        </div>

        <div v-if="filteredVenueFloorGroups.length === 0" class="view-facilities-empty-state">
          <p>No venues found matching your filter.</p>
        </div>
      </div>
    </div>

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
        <button
          v-for="equipment in filteredEquipment"
          :key="equipment.equipmentIdentifier"
          type="button"
          class="view-facilities-equipment-chip view-facilities-equipment-chip--card"
          :class="{
            'view-facilities-equipment-chip--available': equipment.equipmentState === 'Available',
            'view-facilities-equipment-chip--unavailable': equipment.equipmentState !== 'Available',
          }"
          @click="handleViewEquipmentDetails(equipment)"
        >
          <div class="view-facilities-equipment-media">
            <img
              :src="resolveEquipmentPhoto(equipment)"
              :alt="`${equipment.equipmentName} photo`"
              class="view-facilities-equipment-image"
            />
          </div>
          <div class="view-facilities-equipment-chip-header">
            <span class="view-facilities-equipment-chip-name">{{ equipment.equipmentName }}</span>
            <span class="view-facilities-status-badge" :class="equipment.equipmentState === 'Available' ? 'available' : 'unavailable'">
              {{ formatEquipmentStatus(equipment) }}
            </span>
          </div>
          <p class="view-facilities-equipment-detail"><strong>Category:</strong> {{ equipment.equipmentCategory || equipment.categoryName || 'N/A' }}</p>
          <p class="view-facilities-equipment-detail"><strong>Brand:</strong> {{ equipment.equipmentBrand || 'N/A' }}</p>
          <p class="view-facilities-equipment-detail"><strong>Available Quantity:</strong> {{ equipment.availableQuantity }}</p>
          <p class="view-facilities-equipment-detail"><strong>Description:</strong> {{ equipment.description || equipment.scheduleDescription || 'N/A' }}</p>
          <span class="view-facilities-equipment-link">View Details</span>
        </button>
      </div>

      <div v-if="!equipmentLoading && filteredEquipment.length === 0" class="view-facilities-empty-state">
        <p>No equipment found matching your filter.</p>
      </div>
    </div>

    <div class="view-facilities-page-footer">
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
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/ViewFacilities.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import equipmentApi from '@/modules/reservation/services/equipmentApi.js';
import venueApi from '@/modules/reservation/services/venueApi.js';
import VenueDetailsModalComponent from '@/modules/facility/components/VenueDetailsModalComponent.vue';
import EquipmentDetailsModalComponent from '@/modules/facility/components/EquipmentDetailsModalComponent.vue';
import { formatEquipmentStatus, resolveEquipmentPhoto } from '@/modules/facility/utils/equipmentPresentation.js';
import { resolveVenuePhoto } from '@/modules/facility/utils/venueFormValidation.js';
import { formatDisplayDate } from '@/shared/utils/dateTimeDisplay.js';

const activeFacilityTab = ref('venue');
const venueFilterValue = ref('all');
const venueSortOrder = ref('asc');
const selectedVenueDate = ref(getTodayDateInputValue());
const equipmentFilterValue = ref('all');
const equipmentSortOrder = ref('asc');
const equipmentSearchQuery = ref('');
const venueList = ref([]);
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

const filteredVenueFloorGroups = computed(() => {
  const filteredVenues = venueList.value
    .filter((venueRecord) => matchesVenueAvailability(venueRecord, venueFilterValue.value))
    .sort((left, right) => compareByName(left?.venueName, right?.venueName, venueSortOrder.value));

  return Object.entries(groupVenuesByFloor(filteredVenues)).map(([floorLabel, venueRecords]) => ({
    floorLabel,
    venueRecords,
  }));
});

const filteredEquipment = computed(() => {
  const normalizedQuery = normalizeSearchText(equipmentSearchQuery.value);

  let filtered = equipmentList.value.filter((equipment) => {
    return normalizedQuery === ''
      || getEquipmentSearchableValues(equipment).some((value) => normalizeSearchText(value).includes(normalizedQuery));
  });

  if (equipmentFilterValue.value === 'available') {
    filtered = filtered.filter((equipment) => formatEquipmentStatus(equipment) === 'Available');
  } else if (equipmentFilterValue.value === 'maintenance') {
    filtered = filtered.filter((equipment) => formatEquipmentStatus(equipment) === 'Under Maintenance');
  }

  return [...filtered].sort((a, b) => {
    const nameA = normalizeSearchText(a?.equipmentName);
    const nameB = normalizeSearchText(b?.equipmentName);
    return equipmentSortOrder.value === 'asc'
      ? nameA.localeCompare(nameB)
      : nameB.localeCompare(nameA);
  });
});

onMounted(() => {
  fetchVenues();
  fetchEquipment();
});

watch(selectedVenueDate, () => {
  fetchVenues();
});

async function fetchVenues() {
  try {
    venueLoading.value = true;
    venueError.value = '';
    const response = await venueApi.listVenues({
      selectedDate: selectedVenueDate.value,
    });
    const venuePayload = response?.data?.venues || response?.venues || [];
    venueList.value = Array.isArray(venuePayload)
      ? venuePayload.map(normalizeVenueRecord).filter(Boolean)
      : [];
  } catch (error) {
    venueList.value = [];
    venueError.value = error?.response?.data?.errorMessage || 'Failed to load venue records.';
  } finally {
    venueLoading.value = false;
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
  if (!venueRecord?.venueIdentifier) {
    return;
  }

  viewVenueLoading.value = true;
  viewVenueError.value = '';
  viewVenueRecord.value = null;

  try {
    const response = await venueApi.getVenueById(venueRecord.venueIdentifier);
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
    venueIdentifier: venue.venueIdentifier,
    venueName: venue.venueName || '',
    venueLocation: venue.venueLocation || '',
    floorLevel: venue.floorLevel || 'Other',
    capacityLimit: venue.capacityLimit ?? null,
    availabilityDate: venue.availabilityDate || '',
    operationalStatus: venue.operationalStatus || '',
    availabilityStatus: venue.availabilityStatus || 'Unavailable',
    description: venue.description || '',
    imageUrl: venue.imageUrl || '',
    venueAvailable: venue.availabilityStatus === 'Available',
  };
}

function matchesVenueAvailability(venueRecord, filterValue) {
  if (filterValue === 'available') {
    return venueRecord.venueAvailable;
  }

  if (filterValue === 'unavailable') {
    return !venueRecord.venueAvailable;
  }

  return true;
}

function groupVenuesByFloor(venues) {
  return venues.reduce((groups, venueRecord) => {
    const floorLabel = venueRecord.floorLevel || 'Other';
    if (!groups[floorLabel]) {
      groups[floorLabel] = [];
    }

    groups[floorLabel].push(venueRecord);
    return groups;
  }, {});
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

function getTodayDateInputValue() {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}
</script>
