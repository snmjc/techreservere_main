<!-- ===== AI GENERATED: AdminManageFacilitiesPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <h2 class="manage-facilities-page-heading">Facilities</h2>

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
          :placeholder="activeFacilityTab === 'venue' ? 'Search by venue name, location, or floor...' : 'Search by equipment name, type, brand, barcode, or asset ID...'"
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

    <div class="manage-facilities-showing-row">
      <div class="manage-facilities-showing-group">
        <label class="manage-facilities-showing-label">Showing:</label>
        <select id="facilityShowingSelect" v-model="showingFilterValue" class="manage-facilities-showing-select">
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

    <div v-if="activeFacilityTab === 'venue'">
      <div v-if="loading" class="manage-facilities-loading">Loading venues...</div>
      <p v-else-if="venueError" class="manage-facilities-modal-error">{{ venueError }}</p>
      <template v-else>
        <VenueAvailabilityCalendarComponent
          :venues="calendarVenueRecords"
          :selected-date="selectedVenueCalendarDate"
          @update:selected-date="selectedVenueCalendarDate = $event"
        />
        <FacilityVenueListComponent
          :venue-floor-groups="venueFloorGroups"
          :availability-filter="availabilityFilter"
          @view-venue="handleViewVenue"
          @edit-venue="handleEditVenue"
          @delete-venue="handleDeleteVenue"
        />
      </template>
    </div>

    <div v-if="activeFacilityTab === 'equipment' && equipmentLoading" class="manage-facilities-loading">Loading equipment...</div>
    <p v-else-if="activeFacilityTab === 'equipment' && equipmentError" class="manage-facilities-modal-error">{{ equipmentError }}</p>
    <FacilityEquipmentGridComponent
      v-else-if="activeFacilityTab === 'equipment'"
      :equipment-records="filteredEquipmentRecords"
      :availability-filter="availabilityFilter"
      :selected-equipment-identifier="selectedEquipmentCard?.equipmentIdentifier || null"
      @edit-equipment="handleEditEquipment"
      @delete-equipment="openDeleteEquipmentModal"
      @view-equipment="handleViewEquipment"
      @select-equipment="handleSelectEquipment"
    />

    <div class="manage-facilities-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>

    <VenueModalComponent
      :show="showVenueModal"
      :venue="selectedVenue"
      @close="handleVenueModalClose"
      @saved="handleVenueModalSaved"
    />

    <VenueDetailsModalComponent
      :show="Boolean(viewVenueRecord || viewVenueLoading || viewVenueError)"
      :venue="viewVenueRecord"
      :error-message="viewVenueLoading ? 'Loading venue details...' : viewVenueError"
      @close="closeVenueDetails"
    />

    <VenueDeleteModalComponent
      :show="Boolean(deleteVenueRecord)"
      :venue="deleteVenueRecord"
      :current-admin-email="currentAdminEmail"
      :confirm-email="deleteConfirmEmail"
      :confirm-password="deleteConfirmPassword"
      :error-message="deleteVenueError"
      :is-deleting="isDeletingVenue"
      :is-ready="isDeleteVenueReady"
      @update:confirm-email="deleteConfirmEmail = $event"
      @update:confirm-password="deleteConfirmPassword = $event"
      @close="closeDeleteVenueModal"
      @confirm="confirmDeleteVenue"
    />

    <EquipmentModalComponent
      :show="showEquipmentModal"
      :equipment="selectedEquipment"
      @close="handleEquipmentModalClose"
      @saved="handleEquipmentModalSaved"
    />

    <EquipmentDetailsModalComponent
      :show="Boolean(viewEquipmentRecord)"
      :equipment="viewEquipmentRecord"
      :show-admin-fields="true"
      title="View Equipment Details"
      subtitle="Equipment details for admin review and editing."
      @close="closeEquipmentDetails"
    />

    <div
      v-if="deleteEquipmentRecord"
      class="manage-facilities-modal-overlay"
      @click.self="!isDeletingEquipment && closeDeleteEquipmentModal()"
    >
      <section class="manage-facilities-delete-modal manage-facilities-equipment-details-modal">
        <button
          class="manage-facilities-modal-close"
          type="button"
          aria-label="Close"
          :disabled="isDeletingEquipment"
          @click="closeDeleteEquipmentModal"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
          </svg>
        </button>

        <div class="manage-facilities-modal-heading">
          <h2>Delete Equipment</h2>
          <p>This action permanently removes the selected equipment record from TechReserve.</p>
        </div>

        <div class="manage-facilities-equipment-details-layout">
          <div class="manage-facilities-equipment-photo-card">
            <img
              :src="resolveEquipmentPhoto(deleteEquipmentRecord)"
              :alt="`${formatEquipmentText(deleteEquipmentRecord.equipmentName)} photo`"
              class="manage-facilities-equipment-photo"
            />
          </div>

          <dl class="manage-facilities-equipment-details-grid">
            <div><dt>Equipment Name</dt><dd>{{ formatEquipmentText(deleteEquipmentRecord.equipmentName) }}</dd></div>
            <div><dt>Equipment Type/Category</dt><dd>{{ formatEquipmentText(deleteEquipmentRecord.equipmentCategory || deleteEquipmentRecord.categoryName) }}</dd></div>
            <div><dt>Equipment Brand</dt><dd>{{ formatEquipmentText(deleteEquipmentRecord.equipmentBrand) }}</dd></div>
            <div><dt>Available Quantity</dt><dd>{{ formatEquipmentQuantity(deleteEquipmentRecord.availableQuantity) }}</dd></div>
            <div><dt>Operational Status</dt><dd>{{ formatEquipmentStatus(deleteEquipmentRecord) }}</dd></div>
            <div><dt>Barcode</dt><dd>{{ formatEquipmentText(deleteEquipmentRecord.barcode) }}</dd></div>
            <div><dt>Asset ID</dt><dd>{{ formatEquipmentText(deleteEquipmentRecord.assetId || deleteEquipmentRecord.serialNumber) }}</dd></div>
            <div class="manage-facilities-equipment-details-grid__full">
              <dt>Description</dt>
              <dd>{{ formatEquipmentText(deleteEquipmentRecord.description || deleteEquipmentRecord.scheduleDescription) }}</dd>
            </div>
          </dl>
        </div>

        <label class="manage-facilities-confirm-field">
          <span>Type your admin email to confirm deletion:</span>
          <input
            v-model.trim="deleteEquipmentConfirmEmail"
            type="email"
            :placeholder="currentAdminEmail || 'admin@techreserve.edu.ph'"
            autocomplete="off"
          />
        </label>

        <label class="manage-facilities-confirm-field">
          <span>Type your admin password to confirm deletion:</span>
          <input
            v-model="deleteEquipmentConfirmPassword"
            type="password"
            placeholder="Admin password"
            autocomplete="current-password"
          />
        </label>

        <p v-if="deleteEquipmentError" class="manage-facilities-modal-error">{{ deleteEquipmentError }}</p>

        <div class="manage-facilities-modal-actions">
          <button
            class="manage-facilities-cancel-button"
            type="button"
            :disabled="isDeletingEquipment"
            @click="closeDeleteEquipmentModal"
          >
            Cancel
          </button>
          <button
            class="manage-facilities-delete-confirm-button"
            type="button"
            :disabled="isDeletingEquipment || !isDeleteEquipmentReady"
            @click="confirmDeleteEquipment"
          >
            {{ isDeletingEquipment ? 'Deleting...' : 'Delete Equipment' }}
          </button>
        </div>
      </section>
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/ManageFacilities.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import FacilityVenueListComponent from '@/modules/facility/components/FacilityVenueListComponent.vue';
import FacilityEquipmentGridComponent from '@/modules/facility/components/FacilityEquipmentGridComponent.vue';
import VenueAvailabilityCalendarComponent from '@/modules/facility/components/VenueAvailabilityCalendarComponent.vue';
import VenueDeleteModalComponent from '@/modules/facility/components/VenueDeleteModalComponent.vue';
import VenueDetailsModalComponent from '@/modules/facility/components/VenueDetailsModalComponent.vue';
import VenueModalComponent from '@/modules/facility/components/VenueModalComponent.vue';
import EquipmentDetailsModalComponent from '@/modules/facility/components/EquipmentDetailsModalComponent.vue';
import EquipmentModalComponent from '@/modules/facility/components/EquipmentModalComponent.vue';
import venueApi from '@/modules/reservation/services/venueApi.js';
import equipmentApi from '@/modules/reservation/services/equipmentApi.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import {
  deriveVenueAvailabilityForDate,
} from '@/modules/facility/utils/venueFormValidation.js';
import {
  formatEquipmentQuantity,
  formatEquipmentStatus,
  formatEquipmentText,
  resolveEquipmentPhoto,
} from '@/modules/facility/utils/equipmentPresentation.js';

const authStore = useAuthenticationStore();
const route = useRoute();
const router = useRouter();
const activeFacilityTab = ref('venue');
const availabilityFilter = ref('all');
const showingFilterValue = ref('all');
const sortValue = ref('asc');
const searchQuery = ref('');

const showVenueModal = ref(false);
const showEquipmentModal = ref(false);
const selectedVenue = ref(null);
const selectedVenueCard = ref(null);
const selectedEquipment = ref(null);
const selectedEquipmentCard = ref(null);
const viewEquipmentRecord = ref(null);
const viewVenueRecord = ref(null);
const viewVenueLoading = ref(false);
const viewVenueError = ref('');

const venuesList = ref([]);
const equipmentList = ref([]);
const loading = ref(false);
const venueError = ref('');
const equipmentLoading = ref(false);
const equipmentError = ref('');
const selectedVenueCalendarDate = ref(getTodayDateInputValue());
const deleteEquipmentRecord = ref(null);
const deleteEquipmentConfirmEmail = ref('');
const deleteEquipmentConfirmPassword = ref('');
const deleteEquipmentError = ref('');
const isDeletingEquipment = ref(false);
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

const isDeleteEquipmentReady = computed(() =>
  Boolean(deleteEquipmentRecord.value)
  && deleteEquipmentConfirmEmail.value.trim() !== ''
  && deleteEquipmentConfirmPassword.value.trim() !== ''
);

const floorOrder = [
  '18th Floor', '17th Floor', '16th Floor', '15th Floor', '8th Floor',
  '7th Floor', '6th Floor', '5th Floor', '4th Floor', '3rd Floor',
  '2nd Floor', '1st Floor', 'GF / 1st Floor', 'MH Floor', 'Pool', 'Outdoor',
];

const searchedAndSortedVenues = computed(() => filterAndSortVenues(
  venuesList.value,
  searchQuery.value,
  sortValue.value,
));

const calendarVenueRecords = computed(() =>
  searchedAndSortedVenues.value.filter((venueRecord) => matchesCalendarAvailability(
    venueRecord,
    availabilityFilter.value,
    selectedVenueCalendarDate.value,
  ))
);

const filteredEquipmentRecords = computed(() => filterAndSortEquipment(
  equipmentList.value,
  searchQuery.value,
  availabilityFilter.value,
  sortValue.value,
));

const venueFloorGroups = computed(() => buildVenueFloorGroups(
  searchedAndSortedVenues.value,
  floorOrder,
  selectedVenueCalendarDate.value,
));

function handleFacilityTabChange(tabName) {
  if (activeFacilityTab.value === tabName) {
    return;
  }

  if (activeFacilityTab.value === 'venue') {
    handleVenueModalClose();
    closeVenueDetails();
    closeDeleteVenueModal();
  }

  if (activeFacilityTab.value === 'equipment') {
    handleEquipmentModalClose();
    closeEquipmentDetails();
    closeDeleteEquipmentModal();
  }

  activeFacilityTab.value = tabName;
  availabilityFilter.value = 'all';
  searchQuery.value = '';
  updateFacilityTabQuery(tabName);
}

function handleEditFacility() {
  if (activeFacilityTab.value === 'venue') {
    if (!selectedVenueCard.value) {
      venueError.value = 'Use a venue card action to choose which venue you want to edit.';
      return;
    }

    handleEditVenue(selectedVenueCard.value);
    return;
  }

  if (!selectedEquipmentCard.value) {
    equipmentError.value = 'Select an equipment record first before editing.';
    return;
  }

  handleEditEquipment(selectedEquipmentCard.value);
}

function handleAddFacility() {
  if (activeFacilityTab.value === 'venue') {
    venueError.value = '';
    selectedVenue.value = null;
    showVenueModal.value = true;
    return;
  }

  selectedEquipment.value = null;
  equipmentError.value = '';
  showEquipmentModal.value = true;
}

function handleEditVenue(venueRecord) {
  selectedVenueCard.value = venueRecord;
  venueError.value = '';
  selectedVenue.value = venueRecord;
  showVenueModal.value = true;
}

async function handleViewVenue(venueRecord) {
  if (!venueRecord?.venueIdentifier) {
    return;
  }

  selectedVenueCard.value = venueRecord;
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

function closeVenueDetails() {
  viewVenueRecord.value = null;
  viewVenueError.value = '';
  viewVenueLoading.value = false;
}

function handleVenueModalClose() {
  showVenueModal.value = false;
  selectedVenue.value = null;
}

function handleVenueModalSaved() {
  showVenueModal.value = false;
  selectedVenue.value = null;
  fetchVenues();
}

function handleEquipmentModalClose() {
  showEquipmentModal.value = false;
  selectedEquipment.value = null;
}

function handleEquipmentModalSaved() {
  showEquipmentModal.value = false;
  selectedEquipment.value = null;
  fetchEquipment();
}

async function fetchVenues() {
  try {
    loading.value = true;
    venueError.value = '';
    const response = await venueApi.listVenues({
      selectedDate: selectedVenueCalendarDate.value,
    });
    const venuePayload = response?.data?.venues || response?.venues || [];
    venuesList.value = Array.isArray(venuePayload)
      ? venuePayload.map(normalizeVenueRecord).filter(Boolean)
      : [];
  } catch (error) {
    venuesList.value = [];
    venueError.value = error?.response?.data?.errorMessage || 'Failed to load venue records.';
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
    equipmentList.value = [];
    equipmentError.value = error?.response?.data?.errorMessage || 'Failed to load equipment.';
  } finally {
    equipmentLoading.value = false;
  }
}

function openDeleteEquipmentModal(equipmentRecord) {
  selectedEquipmentCard.value = equipmentRecord;
  deleteEquipmentRecord.value = equipmentRecord;
  deleteEquipmentConfirmEmail.value = '';
  deleteEquipmentConfirmPassword.value = '';
  deleteEquipmentError.value = '';
  equipmentError.value = '';
}

function closeDeleteEquipmentModal() {
  if (isDeletingEquipment.value) return;
  resetDeleteEquipmentModalState();
}

function resetDeleteEquipmentModalState() {
  deleteEquipmentRecord.value = null;
  deleteEquipmentConfirmEmail.value = '';
  deleteEquipmentConfirmPassword.value = '';
  deleteEquipmentError.value = '';
}

async function confirmDeleteEquipment() {
  if (!deleteEquipmentRecord.value || isDeletingEquipment.value) return;

  if (!isDeleteEquipmentReady.value) {
    deleteEquipmentError.value = 'Please type your admin email and password to delete this equipment.';
    return;
  }

  try {
    isDeletingEquipment.value = true;
    deleteEquipmentError.value = '';

    await equipmentApi.deleteEquipment(deleteEquipmentRecord.value.equipmentIdentifier, {
      confirmedAdminEmail: deleteEquipmentConfirmEmail.value.trim(),
      confirmedAdminPassword: deleteEquipmentConfirmPassword.value,
    });

    const deletedIdentifier = deleteEquipmentRecord.value.equipmentIdentifier;
    resetDeleteEquipmentModalState();
    clearDeletedEquipmentSelection(deletedIdentifier);
    await fetchEquipment();
  } catch (error) {
    deleteEquipmentError.value = error?.response?.data?.errorMessage || 'Failed to delete equipment. Please try again.';
  } finally {
    isDeletingEquipment.value = false;
  }
}

function handleDeleteVenue(venueRecord) {
  selectedVenueCard.value = venueRecord;
  deleteVenueRecord.value = venueRecord;
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
    deleteVenueError.value = '';

    await venueApi.deleteVenue(deleteVenueRecord.value.venueIdentifier, {
      confirmedAdminEmail: normalizeEmailForConfirmation(deleteConfirmEmail.value),
      confirmedAdminPassword: deleteConfirmPassword.value,
    });

    if (selectedVenueCard.value?.venueIdentifier === deleteVenueRecord.value.venueIdentifier) {
      selectedVenueCard.value = null;
    }

    if (viewVenueRecord.value?.venueIdentifier === deleteVenueRecord.value.venueIdentifier) {
      closeVenueDetails();
    }

    closeDeleteVenueModal();
    await fetchVenues();
  } catch (error) {
    deleteVenueError.value = error?.response?.data?.errorMessage || 'Failed to delete venue. Please try again.';
  } finally {
    isDeletingVenue.value = false;
  }
}

onMounted(() => {
  syncActiveFacilityTabFromRoute(route.query.tab);
  fetchVenues();
  fetchEquipment();
});

watch(
  () => route.query.tab,
  (tabValue) => {
    syncActiveFacilityTabFromRoute(tabValue);
  }
);

watch(selectedVenueCalendarDate, () => {
  if (activeFacilityTab.value === 'venue') {
    fetchVenues();
  }
});

function handleEditEquipment(equipmentRecord) {
  selectedEquipmentCard.value = equipmentRecord;
  selectedEquipment.value = equipmentRecord;
  equipmentError.value = '';
  showEquipmentModal.value = true;
}

function handleViewEquipment(equipmentRecord) {
  selectedEquipmentCard.value = equipmentRecord;
  viewEquipmentRecord.value = equipmentRecord;
  equipmentError.value = '';
}

function handleSelectEquipment(equipmentRecord) {
  selectedEquipmentCard.value = equipmentRecord;
  equipmentError.value = '';
}

function closeEquipmentDetails() {
  viewEquipmentRecord.value = null;
}

function openEditFromDetails() {
  if (!viewEquipmentRecord.value) {
    return;
  }

  handleEditEquipment(viewEquipmentRecord.value);
  closeEquipmentDetails();
}

function normalizeEmailForConfirmation(emailAddress) {
  return String(emailAddress || '').replace(/[\s\u200B-\u200D\uFEFF]+/g, '').trim().toLowerCase();
}

function filterAndSortVenues(venues, rawQuery, sortDirection) {
  const query = normalizeFilterQuery(rawQuery);

  return [...venues]
    .filter((venue) => matchesVenueSearch(venue, query))
    .sort((left, right) => compareByName(left?.venueName, right?.venueName, sortDirection));
}

function filterAndSortEquipment(equipmentRecords, rawQuery, availability, sortDirection) {
  const query = normalizeFilterQuery(rawQuery);

  return [...equipmentRecords]
    .filter((equipmentRecord) => matchesEquipmentSearch(equipmentRecord, query))
    .filter((equipmentRecord) => matchesEquipmentAvailability(equipmentRecord, availability))
    .sort((left, right) => compareByName(left?.equipmentName, right?.equipmentName, sortDirection));
}

function buildVenueFloorGroups(venues, orderedFloors, selectedDate) {
  const groupedVenues = groupVenuesByFloor(venues, selectedDate);
  const sortedGroups = sortVenueGroupsByFloor(groupedVenues, orderedFloors);

  return Object.entries(sortedGroups).map(([floorLabel, venueRecords]) => ({
    floorLabel,
    venueRecords,
  }));
}

function normalizeFilterQuery(value) {
  return String(value || '').trim().toLowerCase();
}

function matchesVenueSearch(venue, query) {
  if (query === '') {
    return true;
  }

  return [venue?.venueName, venue?.venueLocation, venue?.floorLevel]
    .some((value) => normalizeFilterQuery(value).includes(query));
}

function matchesCalendarAvailability(venueRecord, availability, selectedDate) {
  if (availability === 'all') {
    return true;
  }

  const resolvedStatus = deriveVenueAvailabilityForDate(venueRecord, selectedDate);
  return availability === 'available'
    ? resolvedStatus === 'Available'
    : resolvedStatus !== 'Available';
}

function matchesEquipmentSearch(equipmentRecord, query) {
  if (query === '') {
    return true;
  }

  return getEquipmentSearchableValues(equipmentRecord)
    .some((value) => normalizeFilterQuery(value).includes(query));
}

function getEquipmentSearchableValues(equipmentRecord) {
  return [
    equipmentRecord?.equipmentName,
    equipmentRecord?.equipmentCategory || equipmentRecord?.categoryName,
    equipmentRecord?.equipmentBrand,
    equipmentRecord?.barcode,
    equipmentRecord?.assetId || equipmentRecord?.serialNumber,
  ];
}

function matchesEquipmentAvailability(equipmentRecord, availability) {
  if (availability === 'available') {
    return formatEquipmentStatus(equipmentRecord) === 'Available';
  }

  if (availability === 'unavailable') {
    return formatEquipmentStatus(equipmentRecord) !== 'Available';
  }

  return true;
}

function compareByName(leftName, rightName, sortDirection) {
  const normalizedLeft = normalizeFilterQuery(leftName);
  const normalizedRight = normalizeFilterQuery(rightName);

  return sortDirection === 'asc'
    ? normalizedLeft.localeCompare(normalizedRight)
    : normalizedRight.localeCompare(normalizedLeft);
}

function groupVenuesByFloor(venues, selectedDate) {
  return venues.reduce((groups, venue) => {
    const floor = venue?.floorLevel || 'Other';
    const normalizedVenue = mapVenueRecordForList(venue, selectedDate);

    if (!groups[floor]) {
      groups[floor] = [];
    }

    groups[floor].push(normalizedVenue);
    return groups;
  }, {});
}

function sortVenueGroupsByFloor(groupedVenues, orderedFloors) {
  const sortedGroups = {};

  orderedFloors.forEach((floor) => {
    if (groupedVenues[floor]) {
      sortedGroups[floor] = groupedVenues[floor];
    }
  });

  Object.keys(groupedVenues).forEach((floor) => {
    if (!sortedGroups[floor]) {
      sortedGroups[floor] = groupedVenues[floor];
    }
  });

  return sortedGroups;
}

function normalizeVenueRecord(venue) {
  if (!venue) {
    return null;
  }

  return {
    venueIdentifier: venue.venueIdentifier,
    venueName: venue.venueName || '',
    venueLocation: venue.venueLocation || '',
    floorLevel: venue.floorLevel || '',
    capacityLimit: venue.capacityLimit ?? null,
    availabilityDate: venue.availabilityDate || '',
    operationalStatus: venue.operationalStatus || '',
    availabilityStatus: venue.availabilityStatus || 'Unavailable',
    description: venue.description || '',
    imageUrl: venue.imageUrl || '',
    photoData: venue.imageUrl || '',
    reservationTimeRanges: Array.isArray(venue.reservationTimeRanges) ? venue.reservationTimeRanges : [],
  };
}

function mapVenueRecordForList(venue, selectedDate) {
  const availabilityStatus = deriveVenueAvailabilityForDate(venue, selectedDate);

  return {
    ...venue,
    venueAvailable: availabilityStatus === 'Available',
    availabilityStatus,
  };
}

function clearDeletedEquipmentSelection(deletedIdentifier) {
  if (selectedEquipmentCard.value?.equipmentIdentifier === deletedIdentifier) {
    selectedEquipmentCard.value = null;
  }

  if (viewEquipmentRecord.value?.equipmentIdentifier === deletedIdentifier) {
    closeEquipmentDetails();
  }
}

function getTodayDateInputValue() {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function syncActiveFacilityTabFromRoute(tabValue) {
  const normalizedTab = String(tabValue || '').trim().toLowerCase();
  if (normalizedTab === 'equipment' || normalizedTab === 'venue') {
    activeFacilityTab.value = normalizedTab;
  }
}

function updateFacilityTabQuery(tabName) {
  const nextQuery = { ...route.query };

  if (tabName === 'equipment') {
    nextQuery.tab = 'equipment';
  } else {
    delete nextQuery.tab;
  }

  router.replace({ query: nextQuery });
}
</script>
