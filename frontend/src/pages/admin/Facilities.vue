<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <!-- Page Header -->
    <div class="facilities-page-header">
      <h2 class="facilities-page-heading">Facilities</h2>
    </div>

    <!-- Tabs Section -->
    <div class="facilities-tabs">
      <button
        class="facilities-tab"
        :class="{ 'facilities-tab--active': activeTab === 'venue' }"
        @click="activeTab = 'venue'"
      >
        Venue
      </button>
      <button
        class="facilities-tab"
        :class="{ 'facilities-tab--active': activeTab === 'equipment' }"
        @click="activeTab = 'equipment'"
      >
        Equipment
      </button>
    </div>

    <!-- Venue Tab Content -->
    <div v-if="activeTab === 'venue'" class="facilities-content">
      <!-- Add Venue Button -->
      <div class="facilities-toolbar">
        <button class="facilities-add-button" @click="handleAddVenue">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Add Venue
        </button>
      </div>

      <!-- Filter & Legend Section -->
      <div class="facilities-toolbar">
        <div class="facilities-filter-group">
          <label for="facilityFilter" class="facilities-filter-label">Filter:</label>
          <select v-model="venueFilterValue" id="facilityFilter" class="facilities-filter-select">
            <option value="all">All Venues</option>
            <option value="available">Available Only</option>
            <option value="unavailable">Unavailable Only</option>
          </select>
          <button class="facilities-sort-button" @click="venueSortOrder = venueSortOrder === 'asc' ? 'desc' : 'asc'" :title="venueSortOrder === 'asc' ? 'Sort A-Z' : 'Sort Z-A'">
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
        <div class="facilities-legend">
          <span class="facilities-legend-item">
            <span class="facilities-legend-dot facilities-legend-dot--available"></span>
            Available
          </span>
          <span class="facilities-legend-item">
            <span class="facilities-legend-dot facilities-legend-dot--unavailable"></span>
            Unavailable
          </span>
        </div>
      </div>

      <!-- Venues Grid by Floor -->
      <div class="facilities-venues-grid">
        <div
          v-for="floorGroup in filteredVenuesByFloor"
          :key="floorGroup.floorLabel"
          class="facilities-floor-section"
        >
          <h3 class="facilities-floor-heading">{{ floorGroup.floorLabel }}</h3>
          <div class="facilities-venue-grid">
            <div
              v-for="venue in floorGroup.venueRecords"
              :key="venue.venueIdentifier"
              class="facilities-venue-card"
              :class="{
                'facilities-venue-card--available': venue.venueAvailable,
                'facilities-venue-card--unavailable': !venue.venueAvailable,
              }"
            >
              <div class="facilities-venue-card-header">
                <h4 class="facilities-venue-name">{{ venue.venueName }}</h4>
                <div class="facilities-venue-status">
                  <span class="facilities-status-badge" :class="venue.venueAvailable ? 'available' : 'unavailable'">
                    {{ venue.venueAvailable ? 'Available' : 'Unavailable' }}
                  </span>
                </div>
              </div>
              <div class="facilities-venue-card-body">
                <p v-if="venue.venueLocation" class="facilities-venue-detail">
                  <span class="facilities-detail-label">Location:</span> {{ venue.venueLocation }}
                </p>
                <p v-if="venue.floorLevel" class="facilities-venue-detail">
                  <span class="facilities-detail-label">Floor:</span> {{ venue.floorLevel }}
                </p>
                <p v-if="venue.capacityLimit" class="facilities-venue-detail">
                  <span class="facilities-detail-label">Capacity:</span> {{ venue.capacityLimit }} persons
                </p>
              </div>
              <div class="facilities-venue-card-actions">
                <button class="facilities-action-btn facilities-action-btn--edit" @click="handleEditVenue(venue)" title="Edit">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                  </svg>
                </button>
                <button class="facilities-action-btn facilities-action-btn--toggle" @click="handleToggleAvailability(venue)" :title="venue.venueAvailable ? 'Mark Unavailable' : 'Mark Available'">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                </button>
                <button class="facilities-action-btn facilities-action-btn--delete" @click="handleDeleteVenue(venue)" title="Delete">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="filteredVenuesByFloor.length === 0" class="facilities-empty-state">
          <p>No venues found matching your filter.</p>
        </div>
      </div>
    </div>

    <!-- Equipment Tab Content -->
    <div v-if="activeTab === 'equipment'" class="facilities-content">
      <div class="facilities-toolbar">
        <div class="facilities-toolbar-left">
          <button class="facilities-edit-button" @click="openEquipmentManager">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
            Manage Equipment
          </button>
          <button class="facilities-add-button" @click="openEquipmentManager">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Open Full Lifecycle
          </button>
        </div>
        <div class="facilities-filter-group">
          <label for="equipmentFilter" class="facilities-filter-label">Filter:</label>
          <select v-model="equipmentFilterValue" id="equipmentFilter" class="facilities-filter-select">
            <option value="all">All</option>
            <option value="available">Available</option>
            <option value="unavailable">Unavailable</option>
            <option value="maintenance">Under Maintenance</option>
            <option value="retired">Retired</option>
          </select>
          <button class="facilities-sort-button" @click="equipmentSortOrder = equipmentSortOrder === 'asc' ? 'desc' : 'asc'" :title="equipmentSortOrder === 'asc' ? 'Sort A-Z' : 'Sort Z-A'">
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
      </div>

      <!-- Legend -->
      <div class="facilities-legend">
        <span class="facilities-legend-item">
          <span class="facilities-legend-dot facilities-legend-dot--available"></span>
          Available
        </span>
        <span class="facilities-legend-item">
          <span class="facilities-legend-dot facilities-legend-dot--unavailable"></span>
          Unavailable / Retired
        </span>
      </div>

      <p v-if="equipmentError" class="facilities-feedback facilities-feedback--error">{{ equipmentError }}</p>
      <div v-if="equipmentLoading" class="facilities-empty-state">
        <p>Loading equipment records...</p>
      </div>
      <div v-else class="facilities-equipment-grid">
        <div
          v-for="equipment in filteredEquipment"
          :key="equipment.equipmentIdentifier"
          class="facilities-equipment-chip facilities-equipment-chip--card"
          :class="{
            'facilities-equipment-chip--available': equipment.equipmentState === 'Available',
            'facilities-equipment-chip--unavailable': equipment.equipmentState !== 'Available',
          }"
        >
          <div class="facilities-equipment-chip-header">
            <span class="facilities-equipment-chip-name">{{ equipment.equipmentName }}</span>
            <span class="facilities-status-badge" :class="equipment.equipmentState === 'Available' ? 'available' : 'unavailable'">
              {{ equipment.equipmentState }}
            </span>
          </div>
          <p class="facilities-equipment-detail"><strong>Category:</strong> {{ equipment.categoryName }}</p>
          <p class="facilities-equipment-detail"><strong>Available:</strong> {{ equipment.availableQuantity }} / {{ equipment.totalQuantity }}</p>
          <p class="facilities-equipment-detail"><strong>Updated:</strong> {{ formatDateTime(equipment.updatedTimestamp || equipment.createdTimestamp) }}</p>
        </div>
      </div>

      <div v-if="!equipmentLoading && filteredEquipment.length === 0" class="facilities-empty-state">
        <p>No equipment records match the selected filter.</p>
      </div>
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/Facilities.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import equipmentApi from '@/modules/reservation/services/equipmentApi.js';

const router = useRouter();
const activeTab = ref('venue');
const venueFilterValue = ref('all');
const venueSortOrder = ref('asc');
const equipmentFilterValue = ref('all');
const equipmentSortOrder = ref('asc');
const equipmentList = ref([]);
const equipmentLoading = ref(false);
const equipmentError = ref('');

// Mock venue data organized by floor
const venueFloorGroupsList = ref([
  {
    floorLabel: '18th Floor',
    venueRecords: [
      { venueIdentifier: 1, venueName: '18F Roofdeck', venueAvailable: true, venueLocation: 'Rooftop', floorLevel: '18', capacityLimit: 500 },
    ],
  },
  {
    floorLabel: '17th Floor',
    venueRecords: [
      { venueIdentifier: 2, venueName: '17F MPR', venueAvailable: true, venueLocation: 'Main Building', floorLevel: '17', capacityLimit: 300 },
      { venueIdentifier: 3, venueName: 'Basketball without Aircon', venueAvailable: true, venueLocation: 'Sports Wing', floorLevel: '17', capacityLimit: 200 },
      { venueIdentifier: 4, venueName: 'Basketball gym with Aircon', venueAvailable: false, venueLocation: 'Sports Wing', floorLevel: '17', capacityLimit: 250 },
      { venueIdentifier: 5, venueName: 'Basketball gym with Aircon and Green Matting', venueAvailable: true, venueLocation: 'Sports Wing', floorLevel: '17', capacityLimit: 250 },
    ],
  },
  {
    floorLabel: '16th Floor',
    venueRecords: [
      { venueIdentifier: 6, venueName: 'F1603 Audio Visual Room', venueAvailable: false, venueLocation: 'Tech Building', floorLevel: '16', capacityLimit: 100 },
      { venueIdentifier: 7, venueName: 'F1604 Case Room', venueAvailable: true, venueLocation: 'Tech Building', floorLevel: '16', capacityLimit: 80 },
    ],
  },
  {
    floorLabel: '15th Floor',
    venueRecords: [
      { venueIdentifier: 8, venueName: 'F1502 Multipurpose Room', venueAvailable: true, venueLocation: 'Main Building', floorLevel: '15', capacityLimit: 150 },
      { venueIdentifier: 9, venueName: 'F1503 Multipurpose Room', venueAvailable: false, venueLocation: 'Main Building', floorLevel: '15', capacityLimit: 150 },
      { venueIdentifier: 10, venueName: 'F1504 Multipurpose Room', venueAvailable: true, venueLocation: 'Main Building', floorLevel: '15', capacityLimit: 150 },
    ],
  },
  {
    floorLabel: '8th Floor',
    venueRecords: [
      { venueIdentifier: 11, venueName: '8F Exec. Lounge 1', venueAvailable: false, venueLocation: 'Executive Wing', floorLevel: '8', capacityLimit: 50 },
      { venueIdentifier: 12, venueName: '8F Exec. Lounge 2', venueAvailable: true, venueLocation: 'Executive Wing', floorLevel: '8', capacityLimit: 50 },
      { venueIdentifier: 13, venueName: '8F Exec. Lounge 1 and 2 Combined', venueAvailable: true, venueLocation: 'Executive Wing', floorLevel: '8', capacityLimit: 100 },
      { venueIdentifier: 14, venueName: '8F Student Lounge', venueAvailable: true, venueLocation: 'Student Area', floorLevel: '8', capacityLimit: 75 },
    ],
  },
  {
    floorLabel: '4th - 7th Floor',
    venueRecords: [
      { venueIdentifier: 15, venueName: 'F407', venueAvailable: true, venueLocation: 'Main Building', floorLevel: '4', capacityLimit: 60 },
      { venueIdentifier: 16, venueName: 'F503', venueAvailable: true, venueLocation: 'Main Building', floorLevel: '5', capacityLimit: 60 },
      { venueIdentifier: 17, venueName: 'F608', venueAvailable: true, venueLocation: 'Main Building', floorLevel: '6', capacityLimit: 60 },
      { venueIdentifier: 18, venueName: 'F704', venueAvailable: true, venueLocation: 'Main Building', floorLevel: '7', capacityLimit: 60 },
      { venueIdentifier: 19, venueName: 'F711', venueAvailable: true, venueLocation: 'Main Building', floorLevel: '7', capacityLimit: 60 },
    ],
  },
  {
    floorLabel: '3rd Floor',
    venueRecords: [
      { venueIdentifier: 20, venueName: 'FEU Tech Swimming Pool', venueAvailable: true, venueLocation: 'Sports Complex', floorLevel: '3', capacityLimit: 300 },
    ],
  },
  {
    floorLabel: '2nd Floor',
    venueRecords: [
      { venueIdentifier: 21, venueName: '2F FIT Student Plaza', venueAvailable: false, venueLocation: 'Student Area', floorLevel: '2', capacityLimit: 400 },
    ],
  },
]);

const filteredVenuesByFloor = computed(() => {
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

const filteredEquipment = computed(() => {
  let filtered = equipmentList.value;

  if (equipmentFilterValue.value === 'available') {
    filtered = filtered.filter((equipment) => equipment.equipmentState === 'Available');
  } else if (equipmentFilterValue.value === 'unavailable') {
    filtered = filtered.filter((equipment) => equipment.equipmentState === 'Unavailable');
  } else if (equipmentFilterValue.value === 'maintenance') {
    filtered = filtered.filter((equipment) => equipment.equipmentState === 'Under Maintenance');
  } else if (equipmentFilterValue.value === 'retired') {
    filtered = filtered.filter((equipment) => equipment.equipmentState === 'Retired');
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

function handleAddVenue() {
  console.log('Add new venue');
  alert('Add venue functionality coming soon');
}

function handleEditVenue(venue) {
  console.log('Edit venue:', venue);
  alert(`Edit venue: ${venue.venueName}`);
}

function handleToggleAvailability(venue) {
  venue.venueAvailable = !venue.venueAvailable;
  console.log('Toggled availability for:', venue.venueName);
}

function handleDeleteVenue(venue) {
  const index = venueFloorGroupsList.value.findIndex(
    (group) => group.venueRecords.some((v) => v.venueIdentifier === venue.venueIdentifier)
  );
  if (index !== -1) {
    const venueIndex = venueFloorGroupsList.value[index].venueRecords.findIndex(
      (v) => v.venueIdentifier === venue.venueIdentifier
    );
    if (venueIndex !== -1) {
      venueFloorGroupsList.value[index].venueRecords.splice(venueIndex, 1);
      console.log('Deleted venue:', venue.venueName);
    }
  }
}

function openEquipmentManager() {
  router.push({ name: 'adminManageEquipmentPage' });
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

function formatDateTime(value) {
  if (!value) {
    return 'N/A';
  }

  const parsedDate = new Date(value);
  if (Number.isNaN(parsedDate.getTime())) {
    return 'N/A';
  }

  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(parsedDate);
}

onMounted(() => {
  fetchEquipment();
});
</script>
