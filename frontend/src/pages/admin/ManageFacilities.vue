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
      <button class="manage-facilities-edit-button" @click="handleEditFacility">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
        </svg>
        {{ activeFacilityTab === 'venue' ? 'Edit Venue' : 'Edit Equipment' }}
      </button>
      <button class="manage-facilities-add-button" @click="handleAddFacility">
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

    <!-- Search and Sort Row -->
    <div class="manage-facilities-search-sort-row">
      <div class="manage-facilities-search-group">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input
          v-model="searchQuery"
          type="text"
          class="manage-facilities-search-input"
          :placeholder="activeFacilityTab === 'venue' ? 'Search by venue name or floor...' : 'Search by equipment name or category...'"
        />
      </div>
      <div class="manage-facilities-sort-group">
        <label class="manage-facilities-sort-label">Sort:</label>
        <select v-model="sortValue" class="manage-facilities-sort-select">
          <option value="asc">Name (A-Z)</option>
          <option value="desc">Name (Z-A)</option>
        </select>
      </div>
    </div>

    <!-- Showing Row + Legend -->
    <div class="manage-facilities-showing-row">
      <div class="manage-facilities-showing-group">
        <label class="manage-facilities-showing-label">Showing:</label>
        <select
          id="facilityShowingSelect"
          v-model="showingFilterValue"
          class="manage-facilities-showing-select"
        >
          <option value="all">All</option>
        </select>
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
    <div v-if="activeFacilityTab === 'venue' && loading" class="manage-facilities-loading">Loading venues...</div>
    <FacilityVenueListComponent
      v-else-if="activeFacilityTab === 'venue'"
      :venue-floor-groups="venueFloorGroups"
      :availability-filter="availabilityFilter"
      @edit-venue="handleEditVenue"
      @delete-venue="handleDeleteVenue"
      @toggle-availability="handleToggleAvailability"
    />

    <!-- Equipment Tab Content -->
    <div v-if="activeFacilityTab === 'equipment' && equipmentLoading" class="manage-facilities-loading">
      Loading equipment...
    </div>
    <p v-else-if="activeFacilityTab === 'equipment' && equipmentError" class="manage-facilities-modal-error">
      {{ equipmentError }}
    </p>
    <FacilityEquipmentGridComponent
      v-else-if="activeFacilityTab === 'equipment'"
      :equipment-records="filteredEquipmentRecords"
      :availability-filter="availabilityFilter"
    />

    <!-- Footer -->
    <div class="manage-facilities-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>

    <!-- Venue Modal -->
    <VenueModalComponent
      :show="showVenueModal"
      :venue="selectedVenue"
      @close="handleVenueModalClose"
      @saved="handleVenueModalSaved"
    />

    <!-- Equipment Modal -->
    <EquipmentModalComponent
      :show="showEquipmentModal"
      :equipment="selectedEquipment"
      @close="handleEquipmentModalClose"
      @saved="handleEquipmentModalSaved"
    />

    <div
      v-if="deleteVenueRecord"
      class="manage-facilities-modal-overlay"
      @click.self="!isDeletingVenue && closeDeleteVenueModal()"
    >
      <section class="manage-facilities-delete-modal">
        <button
          class="manage-facilities-modal-close"
          type="button"
          aria-label="Close"
          :disabled="isDeletingVenue"
          @click="closeDeleteVenueModal"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
          </svg>
        </button>

        <div class="manage-facilities-modal-heading">
          <h2>Delete Venue</h2>
          <p>This action permanently removes the venue from TechReserve.</p>
        </div>

        <div class="manage-facilities-delete-summary">
          <p><strong>Venue</strong><span>{{ deleteVenueRecord.venueName }}</span></p>
          <p><strong>Location</strong><span>{{ deleteVenueRecord.venueLocation || 'N/A' }}</span></p>
          <p><strong>Floor</strong><span>{{ deleteVenueRecord.floorLevel || 'N/A' }}</span></p>
        </div>

        <label class="manage-facilities-confirm-field">
          <span>Type your admin email to confirm deletion:</span>
          <input
            v-model.trim="deleteConfirmEmail"
            type="email"
            :placeholder="currentAdminEmail || 'admin@techreserve.edu.ph'"
            autocomplete="off"
          />
        </label>

        <label class="manage-facilities-confirm-field">
          <span>Type your admin password to confirm deletion:</span>
          <input
            v-model="deleteConfirmPassword"
            type="password"
            placeholder="Admin password"
            autocomplete="current-password"
          />
        </label>

        <p v-if="deleteVenueError" class="manage-facilities-modal-error">{{ deleteVenueError }}</p>

        <div class="manage-facilities-modal-actions">
          <button
            class="manage-facilities-cancel-button"
            type="button"
            :disabled="isDeletingVenue"
            @click="closeDeleteVenueModal"
          >
            Cancel
          </button>
          <button
            class="manage-facilities-delete-confirm-button"
            type="button"
            :disabled="isDeletingVenue || !isDeleteVenueReady"
            @click="confirmDeleteVenue"
          >
            {{ isDeletingVenue ? 'Deleting...' : 'Delete Venue' }}
          </button>
        </div>
      </section>
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/ManageFacilities.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import FacilityVenueListComponent from '@/modules/facility/components/FacilityVenueListComponent.vue';
import FacilityEquipmentGridComponent from '@/modules/facility/components/FacilityEquipmentGridComponent.vue';
import VenueModalComponent from '@/modules/facility/components/VenueModalComponent.vue';
import EquipmentModalComponent from '@/modules/facility/components/EquipmentModalComponent.vue';
import venueApi from '@/modules/reservation/services/venueApi.js';
import equipmentApi from '@/modules/reservation/services/equipmentApi.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';

const authStore = useAuthenticationStore();
const activeFacilityTab = ref('venue');
const availabilityFilter = ref('all');
const showingFilterValue = ref('all');
const sortValue = ref('asc');
const searchQuery = ref('');

const showVenueModal = ref(false);
const showEquipmentModal = ref(false);
const selectedVenue = ref(null);
const selectedEquipment = ref(null);

const venuesList = ref([]);
const equipmentList = ref([]);
const loading = ref(false);
const equipmentLoading = ref(false);
const equipmentError = ref('');
const deleteVenueRecord = ref(null);
const deleteConfirmEmail = ref('');
const deleteConfirmPassword = ref('');
const deleteVenueError = ref('');
const isDeletingVenue = ref(false);

const currentAdminEmail = computed(() =>
  authStore.accountData?.emailAddress || authStore.clerkAccountData?.emailAddress || ''
);
const isDeleteVenueReady = computed(() =>
  Boolean(deleteVenueRecord.value)
  && normalizeEmailForConfirmation(deleteConfirmEmail.value) === normalizeEmailForConfirmation(currentAdminEmail.value)
  && deleteConfirmPassword.value.trim() !== ''
);

const floorOrder = [
  '18th Floor', '17th Floor', '16th Floor', '15th Floor', '8th Floor',
  '4th Floor', '5th Floor', '6th Floor', '7th Floor', '3rd Floor',
  '2nd Floor', '1st Floor', 'GF / 1st Floor', 'MH Floor', 'Pool', 'Outdoor'
];

const filteredVenues = computed(() => {
  let venues = [...venuesList.value];

  // Apply search
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    venues = venues.filter(venue =>
      venue.venueName?.toLowerCase().includes(query) ||
      venue.floorLevel?.toLowerCase().includes(query)
    );
  }

  // Apply availability filter
  if (availabilityFilter.value === 'available') {
    venues = venues.filter(venue => venue.availabilityStatus === 'Available');
  } else if (availabilityFilter.value === 'unavailable') {
    venues = venues.filter(venue => venue.availabilityStatus !== 'Available');
  }

  // Apply sort
  venues.sort((a, b) => {
    const nameA = a.venueName?.toLowerCase() || '';
    const nameB = b.venueName?.toLowerCase() || '';
    if (sortValue.value === 'asc') {
      return nameA.localeCompare(nameB);
    } else {
      return nameB.localeCompare(nameA);
    }
  });

  return venues;
});

const filteredEquipmentRecords = computed(() => {
  let equipment = [...equipmentList.value];

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    equipment = equipment.filter((record) =>
      record.equipmentName?.toLowerCase().includes(query)
      || record.equipmentCategory?.toLowerCase().includes(query)
      || record.equipmentBrand?.toLowerCase().includes(query)
      || record.categoryName?.toLowerCase().includes(query)
    );
  }

  if (availabilityFilter.value === 'available') {
    equipment = equipment.filter((record) => record.equipmentState === 'Available');
  } else if (availabilityFilter.value === 'unavailable') {
    equipment = equipment.filter((record) => record.equipmentState !== 'Available');
  }

  return equipment.sort((left, right) => {
    const leftName = String(left.equipmentName || '').toLowerCase();
    const rightName = String(right.equipmentName || '').toLowerCase();

    return sortValue.value === 'asc'
      ? leftName.localeCompare(rightName)
      : rightName.localeCompare(leftName);
  });
});

const venueFloorGroups = computed(() => {
  const groups = {};
  const filtered = filteredVenues.value;

  filtered.forEach(venue => {
    const floor = venue.floorLevel || 'Other';
    if (!groups[floor]) {
      groups[floor] = [];
    }
    groups[floor].push(venue);
  });

  // Sort groups by floor order
  const sortedGroups = {};
  floorOrder.forEach(floor => {
    if (groups[floor]) {
      sortedGroups[floor] = groups[floor];
    }
  });

  // Add any floors not in the predefined order
  Object.keys(groups).forEach(floor => {
    if (!sortedGroups[floor]) {
      sortedGroups[floor] = groups[floor];
    }
  });

  return Object.entries(sortedGroups).map(([floorLabel, venueRecords]) => ({
    floorLabel,
    venueRecords: venueRecords.map(venue => ({
      venueIdentifier: venue.venueIdentifier,
      venueName: venue.venueName,
      venueAvailable: venue.availabilityStatus === 'Available',
      venueLocation: venue.venueLocation,
      floorLevel: venue.floorLevel,
      capacityLimit: venue.capacityLimit,
      description: venue.description,
      imageUrl: venue.imageUrl
    }))
  }));
});

/**
 * @function handleFacilityTabChange
 * @description Switches active tab and resets filters.
 * @param {string} tabName - 'venue' or 'equipment'
 * @returns {void}
 */
function handleFacilityTabChange(tabName) {
  activeFacilityTab.value = tabName;
  availabilityFilter.value = 'all';
  searchQuery.value = '';
}

/**
 * @function handleEditFacility
 * @description Opens edit modal for selected venue or equipment.
 * @returns {void}
 */
function handleEditFacility() {
  if (activeFacilityTab.value === 'venue') {
    selectedVenue.value = { venueIdentifier: 0, venueName: '', venueLocation: '', capacityLimit: null };
    showVenueModal.value = true;
  } else {
    selectedEquipment.value = null;
    showEquipmentModal.value = true;
  }
}

function handleEditVenue(venue) {
  selectedVenue.value = venue;
  showVenueModal.value = true;
}

/**
 * @function handleAddFacility
 * @description Opens add modal for new venue or equipment.
 * @returns {void}
 */
function handleAddFacility() {
  if (activeFacilityTab.value === 'venue') {
    selectedVenue.value = null;
    showVenueModal.value = true;
  } else {
    selectedEquipment.value = null;
    showEquipmentModal.value = true;
  }
}

/**
 * @function handleVenueModalClose
 * @description Closes the venue modal.
 * @returns {void}
 */
function handleVenueModalClose() {
  showVenueModal.value = false;
  selectedVenue.value = null;
}

/**
 * @function handleVenueModalSaved
 * @description Handles venue save event.
 * @returns {void}
 */
function handleVenueModalSaved() {
  showVenueModal.value = false;
  selectedVenue.value = null;
  fetchVenues();
}

/**
 * @function handleEquipmentModalClose
 * @description Closes the equipment modal.
 * @returns {void}
 */
function handleEquipmentModalClose() {
  showEquipmentModal.value = false;
  selectedEquipment.value = null;
}

/**
 * @function handleEquipmentModalSaved
 * @description Handles equipment save event.
 * @returns {void}
 */
function handleEquipmentModalSaved() {
  showEquipmentModal.value = false;
  selectedEquipment.value = null;
  fetchEquipment();
}

async function fetchVenues() {
  try {
    loading.value = true;
    const response = await venueApi.listVenues();
    venuesList.value = response.venues || [];
  } catch (error) {
    console.error('Error fetching venues:', error);
    venuesList.value = [];
  } finally {
    loading.value = false;
  }
}

async function fetchEquipment() {
  try {
    equipmentLoading.value = true;
    equipmentError.value = '';
    const response = await equipmentApi.listEquipment();
    equipmentList.value = response?.data?.equipment || [];
  } catch (error) {
    console.error('Error fetching equipment:', error);
    equipmentList.value = [];
    equipmentError.value = error?.response?.data?.errorMessage || 'Failed to load equipment.';
  } finally {
    equipmentLoading.value = false;
  }
}

function handleDeleteVenue(venueIdentifier) {
  deleteVenueRecord.value = venuesList.value.find((venue) => venue.venueIdentifier === venueIdentifier) || null;
  deleteConfirmEmail.value = '';
  deleteConfirmPassword.value = '';
  deleteVenueError.value = '';
}

function closeDeleteVenueModal() {
  if (isDeletingVenue.value) return;
  deleteVenueRecord.value = null;
  deleteConfirmEmail.value = '';
  deleteConfirmPassword.value = '';
  deleteVenueError.value = '';
}

async function confirmDeleteVenue() {
  if (!deleteVenueRecord.value || isDeletingVenue.value) return;

  if (!currentAdminEmail.value) {
    deleteVenueError.value = 'Unable to verify the admin in-charge. Please sign in again.';
    return;
  }

  if (!isDeleteVenueReady.value) {
    deleteVenueError.value = 'Please type your exact admin email and password to delete this venue.';
    return;
  }

  try {
    isDeletingVenue.value = true;
    await venueApi.deleteVenue(deleteVenueRecord.value.venueIdentifier, {
      confirmedAdminEmail: normalizeEmailForConfirmation(deleteConfirmEmail.value),
      confirmedAdminPassword: deleteConfirmPassword.value,
    });
    isDeletingVenue.value = false;
    closeDeleteVenueModal();
    await fetchVenues();
  } catch (error) {
    console.error('Error deleting venue:', error);
    deleteVenueError.value = error?.response?.data?.errorMessage || 'Failed to delete venue. Please try again.';
  } finally {
    isDeletingVenue.value = false;
  }
}

async function handleToggleAvailability(venue) {
  try {
    const newStatus = venue.venueAvailable ? 'Unavailable' : 'Available';
    await venueApi.updateVenue(venue.venueIdentifier, {
      venueName: venue.venueName,
      venueLocation: venue.venueLocation,
      floorLevel: venue.floorLevel,
      capacityLimit: venue.capacityLimit,
      description: venue.description,
      imageUrl: venue.imageUrl,
      availabilityStatus: newStatus
    });
    fetchVenues();
  } catch (error) {
    console.error('Error updating venue availability:', error);
    alert('Failed to update venue availability. Please try again.');
  }
}

onMounted(() => {
  fetchVenues();
  fetchEquipment();
});

function normalizeEmailForConfirmation(emailAddress) {
  return String(emailAddress || '').replace(/[\s\u200B-\u200D\uFEFF]+/g, '').trim().toLowerCase();
}

</script>
