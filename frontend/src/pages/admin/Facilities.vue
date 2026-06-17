<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="facilities-page">
      <header class="facilities-hero">
        <div>
          <p class="facilities-kicker">Facility administration</p>
          <h1>Manage Facilities</h1>
          <p>Review venue and equipment availability, keep facility records organized, and manage inventory from one place.</p>
        </div>

        <div class="facilities-hero-actions">
          <button
            class="facilities-hero-button facilities-hero-button--secondary"
            type="button"
            :disabled="activeTab === 'venue' ? !selectedVenueRecord : !selectedEquipmentRecord"
            @click="handleHeroEdit"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 20h9" />
              <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" />
            </svg>
            {{ activeTab === 'venue' ? 'Edit Venue' : 'Edit Equipment' }}
          </button>
          <button class="facilities-hero-button facilities-hero-button--primary" type="button" @click="handleHeroAdd">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 5v14" />
              <path d="M5 12h14" />
            </svg>
            {{ activeTab === 'venue' ? 'Add Venue' : 'Add Equipment' }}
          </button>
        </div>
      </header>

      <div class="facilities-tabs">
        <button
          class="facilities-tab"
          :class="{ 'facilities-tab--active': activeTab === 'venue' }"
          type="button"
          @click="setActiveTab('venue')"
        >
          Venue
        </button>
        <button
          class="facilities-tab"
          :class="{ 'facilities-tab--active': activeTab === 'equipment' }"
          type="button"
          @click="setActiveTab('equipment')"
        >
          Equipment
        </button>
      </div>

      <section v-if="activeTab === 'venue'" class="facilities-panel">
        <div class="facilities-filter-bar">
          <label class="facilities-search">
            <span class="sr-only">Search venue</span>
            <input
              v-model.trim="venueSearchQuery"
              type="search"
              placeholder="Search venue..."
            />
          </label>

          <label>
            <span>Floor</span>
            <select v-model="venueFloorFilter">
              <option value="all">All Floors</option>
              <option v-for="floor in venueFloorOptions" :key="floor" :value="floor">{{ floor }}</option>
            </select>
          </label>

          <label>
            <span>Status</span>
            <select v-model="venueStatusFilter">
              <option value="all">All</option>
              <option value="available">Available</option>
              <option value="unavailable">Unavailable</option>
            </select>
          </label>

          <label>
            <span>Sort By</span>
            <select v-model="venueSortValue">
              <option value="name-asc">Venue Name (A - Z)</option>
              <option value="name-desc">Venue Name (Z - A)</option>
              <option value="floor-asc">Floor (Low - High)</option>
              <option value="floor-desc">Floor (High - Low)</option>
              <option value="capacity-desc">Capacity (High - Low)</option>
              <option value="capacity-asc">Capacity (Low - High)</option>
            </select>
          </label>

          <button class="facilities-reset-button" type="button" @click="resetVenueFilters">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 6h18" />
              <path d="M7 12h10" />
              <path d="M10 18h4" />
            </svg>
            Reset Filters
          </button>
        </div>

        <div class="facilities-table-card">
          <div class="facilities-table-wrap">
            <table class="facilities-table">
              <thead>
                <tr>
                  <th>Floor</th>
                  <th>Venue Name</th>
                  <th>Status</th>
                  <th>Capacity</th>
                  <th>Type</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="venue in paginatedVenues"
                  :key="venue.venueIdentifier"
                  :class="{ 'is-selected': selectedVenueRecord?.venueIdentifier === venue.venueIdentifier }"
                  @click="selectedVenueRecord = venue"
                >
                  <td>{{ formatFloorLabel(venue.floorLevel) }}</td>
                  <td class="facilities-table-name">{{ venue.venueName }}</td>
                  <td>
                    <span
                      class="facilities-status-pill"
                      :class="venue.venueAvailable ? 'facilities-status-pill--available' : 'facilities-status-pill--unavailable'"
                    >
                      {{ venue.venueAvailable ? 'Available' : 'Unavailable' }}
                    </span>
                  </td>
                  <td>{{ formatCapacity(venue.capacityLimit) }}</td>
                  <td>{{ resolveVenueType(venue) }}</td>
                  <td>
                    <div class="facilities-row-actions">
                      <button
                        class="facilities-row-action"
                        type="button"
                        title="View Venue"
                        @click.stop="handleViewVenue(venue)"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" />
                          <circle cx="12" cy="12" r="3" />
                        </svg>
                      </button>
                      <button
                        class="facilities-row-action"
                        type="button"
                        title="Edit Venue"
                        @click.stop="handleEditVenue(venue)"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M12 20h9" />
                          <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!venueLoading && paginatedVenues.length === 0">
                  <td colspan="6" class="facilities-table-empty">No venues found matching the selected filters.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="facilities-table-footer">
            <p>Showing {{ venuePageStart }} to {{ venuePageEnd }} of {{ filteredVenueRecords.length }} venues</p>

            <div class="facilities-pagination">
              <button type="button" :disabled="venueCurrentPage === 1" @click="venueCurrentPage -= 1">&laquo;</button>
              <button type="button" :disabled="venueCurrentPage === 1" @click="venueCurrentPage -= 1">&lsaquo;</button>
              <button
                v-for="pageNumber in visibleVenuePages"
                :key="pageNumber"
                type="button"
                :class="{ 'is-active': pageNumber === venueCurrentPage }"
                @click="venueCurrentPage = pageNumber"
              >
                {{ pageNumber }}
              </button>
              <button type="button" :disabled="venueCurrentPage === venueTotalPages" @click="venueCurrentPage += 1">&rsaquo;</button>
              <button type="button" :disabled="venueCurrentPage === venueTotalPages" @click="venueCurrentPage += 1">&raquo;</button>
            </div>

            <label class="facilities-page-size">
              <span>Items per page:</span>
              <select v-model.number="venuePageSize">
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="15">15</option>
              </select>
            </label>
          </div>
        </div>

        <div class="facilities-legend-card">
          <strong>Legend:</strong>
          <span><i class="facilities-legend-dot facilities-legend-dot--available" />Available <small>The venue is free and can be reserved.</small></span>
          <span><i class="facilities-legend-dot facilities-legend-dot--unavailable" />Unavailable <small>The venue is already reserved or not accessible.</small></span>
        </div>
      </section>

      <section v-else class="facilities-panel">
        <div class="facilities-filter-bar facilities-filter-bar--equipment">
          <label class="facilities-search">
            <span class="sr-only">Search equipment</span>
            <input
              v-model.trim="equipmentSearchQuery"
              type="search"
              placeholder="Search by equipment name, type, brand, barcode, or asset ID..."
            />
          </label>

          <label>
            <span>Status</span>
            <select v-model="equipmentStatusFilter">
              <option value="all">All Status</option>
              <option value="available">Available</option>
              <option value="unavailable">Unavailable</option>
              <option value="maintenance">Under Maintenance</option>
              <option value="retired">Retired</option>
            </select>
          </label>

          <label>
            <span>Sort By</span>
            <select v-model="equipmentSortValue">
              <option value="name-asc">Equipment Name (A - Z)</option>
              <option value="name-desc">Equipment Name (Z - A)</option>
              <option value="updated-desc">Recently Updated</option>
              <option value="quantity-desc">Available Quantity (High - Low)</option>
              <option value="quantity-asc">Available Quantity (Low - High)</option>
            </select>
          </label>

          <label>
            <span>Showing</span>
            <select v-model.number="equipmentPageSize">
              <option :value="6">6</option>
              <option :value="9">9</option>
              <option :value="12">12</option>
            </select>
          </label>

          <button class="facilities-reset-button" type="button" @click="resetEquipmentFilters">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 6h18" />
              <path d="M7 12h10" />
              <path d="M10 18h4" />
            </svg>
            Reset Filters
          </button>
        </div>

        <p v-if="equipmentError" class="facilities-feedback facilities-feedback--error">{{ equipmentError }}</p>
        <div v-if="equipmentLoading" class="facilities-empty-state">
          <p>Loading equipment records...</p>
        </div>

        <template v-else>
          <FacilityEquipmentGridComponent
            :equipment-records="paginatedEquipment"
            availability-filter="all"
            :selected-equipment-identifier="selectedEquipmentRecord?.equipmentIdentifier || null"
            @view-equipment="handleViewEquipment"
            @edit-equipment="handleEditEquipment"
            @delete-equipment="openDeleteEquipmentModal"
            @select-equipment="handleSelectEquipment"
          />

          <div class="facilities-table-card" v-if="filteredEquipmentRecords.length > 0">
            <div class="facilities-table-footer">
              <p>Showing {{ equipmentPageStart }} to {{ equipmentPageEnd }} of {{ filteredEquipmentRecords.length }} equipment records</p>

              <div class="facilities-pagination">
                <button type="button" :disabled="equipmentCurrentPage === 1" @click="equipmentCurrentPage -= 1">&laquo;</button>
                <button type="button" :disabled="equipmentCurrentPage === 1" @click="equipmentCurrentPage -= 1">&lsaquo;</button>
                <button
                  v-for="pageNumber in visibleEquipmentPages"
                  :key="pageNumber"
                  type="button"
                  :class="{ 'is-active': pageNumber === equipmentCurrentPage }"
                  @click="equipmentCurrentPage = pageNumber"
                >
                  {{ pageNumber }}
                </button>
                <button type="button" :disabled="equipmentCurrentPage === equipmentTotalPages" @click="equipmentCurrentPage += 1">&rsaquo;</button>
                <button type="button" :disabled="equipmentCurrentPage === equipmentTotalPages" @click="equipmentCurrentPage += 1">&raquo;</button>
              </div>
            </div>
          </div>
        </template>
      </section>
    </section>

    <EquipmentModalComponent
      :show="showEquipmentModal"
      :equipment="selectedEquipmentDraft"
      @close="handleEquipmentModalClose"
      @saved="handleEquipmentModalSaved"
    />

    <EquipmentDetailsModalComponent
      :show="Boolean(viewEquipmentRecord)"
      :equipment="viewEquipmentRecord"
      title="View Equipment Details"
      subtitle="Equipment details for admin review."
      :show-admin-fields="true"
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
import './css/Facilities.css';
import './css/ManageFacilities.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import equipmentApi from '@/modules/reservation/services/equipmentApi.js';
import FacilityEquipmentGridComponent from '@/modules/facility/components/FacilityEquipmentGridComponent.vue';
import EquipmentDetailsModalComponent from '@/modules/facility/components/EquipmentDetailsModalComponent.vue';
import EquipmentModalComponent from '@/modules/facility/components/EquipmentModalComponent.vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import {
  formatEquipmentQuantity,
  formatEquipmentStatus,
  formatEquipmentText,
  resolveEquipmentPhoto,
} from '@/modules/facility/utils/equipmentPresentation.js';

const router = useRouter();
const route = useRoute();
const authStore = useAuthenticationStore();

const activeTab = ref(route.query.tab === 'equipment' ? 'equipment' : 'venue');
const venueLoading = ref(false);
const venueSearchQuery = ref('');
const venueFloorFilter = ref('all');
const venueStatusFilter = ref('all');
const venueSortValue = ref('name-asc');
const venueCurrentPage = ref(1);
const venuePageSize = ref(10);
const selectedVenueRecord = ref(null);

const equipmentSearchQuery = ref('');
const equipmentStatusFilter = ref('all');
const equipmentSortValue = ref('name-asc');
const equipmentCurrentPage = ref(1);
const equipmentPageSize = ref(6);
const equipmentList = ref([]);
const equipmentLoading = ref(false);
const equipmentError = ref('');
const selectedEquipmentRecord = ref(null);
const selectedEquipmentDraft = ref(null);
const showEquipmentModal = ref(false);
const viewEquipmentRecord = ref(null);
const deleteEquipmentRecord = ref(null);
const deleteEquipmentConfirmEmail = ref('');
const deleteEquipmentConfirmPassword = ref('');
const deleteEquipmentError = ref('');
const isDeletingEquipment = ref(false);

const venueRecords = ref([
  { venueIdentifier: 1, venueName: '18F Roofdeck', venueAvailable: true, venueLocation: 'Rooftop', floorLevel: '18', capacityLimit: 150, venueType: 'Open Space' },
  { venueIdentifier: 2, venueName: '17F MPR', venueAvailable: true, venueLocation: 'Main Building', floorLevel: '17', capacityLimit: 200, venueType: 'Multipurpose Room' },
  { venueIdentifier: 3, venueName: 'Basketball without Aircon', venueAvailable: true, venueLocation: 'Sports Wing', floorLevel: '17', capacityLimit: 50, venueType: 'Sports Facility' },
  { venueIdentifier: 4, venueName: 'Basketball gym with Aircon', venueAvailable: false, venueLocation: 'Sports Wing', floorLevel: '17', capacityLimit: 50, venueType: 'Sports Facility' },
  { venueIdentifier: 5, venueName: 'Basketball gym with Aircon and Green Matting', venueAvailable: true, venueLocation: 'Sports Wing', floorLevel: '17', capacityLimit: 50, venueType: 'Sports Facility' },
  { venueIdentifier: 6, venueName: 'F1603 Audio Visual Room', venueAvailable: false, venueLocation: 'Tech Building', floorLevel: '16', capacityLimit: 40, venueType: 'Audio Visual Room' },
  { venueIdentifier: 7, venueName: 'F1604 Case Room', venueAvailable: true, venueLocation: 'Tech Building', floorLevel: '16', capacityLimit: 40, venueType: 'Case Room' },
  { venueIdentifier: 8, venueName: 'F1502 Multipurpose Room', venueAvailable: true, venueLocation: 'Main Building', floorLevel: '15', capacityLimit: 100, venueType: 'Multipurpose Room' },
  { venueIdentifier: 9, venueName: 'F1503 Multipurpose Room', venueAvailable: true, venueLocation: 'Main Building', floorLevel: '15', capacityLimit: 100, venueType: 'Multipurpose Room' },
  { venueIdentifier: 10, venueName: 'F1504 Multipurpose Room', venueAvailable: true, venueLocation: 'Main Building', floorLevel: '15', capacityLimit: 100, venueType: 'Multipurpose Room' },
  { venueIdentifier: 11, venueName: '8F Exec. Lounge 1', venueAvailable: false, venueLocation: 'Executive Wing', floorLevel: '8', capacityLimit: 35, venueType: 'Executive Lounge' },
  { venueIdentifier: 12, venueName: '2F FIT Student Plaza', venueAvailable: false, venueLocation: 'Student Area', floorLevel: '2', capacityLimit: 180, venueType: 'Student Plaza' },
]);

const currentAdminEmail = computed(() =>
  authStore.accountData?.emailAddress || authStore.clerkAccountData?.emailAddress || ''
);

const isDeleteEquipmentReady = computed(() =>
  Boolean(deleteEquipmentRecord.value)
  && deleteEquipmentConfirmEmail.value.trim() !== ''
  && deleteEquipmentConfirmPassword.value.trim() !== ''
);

const venueFloorOptions = computed(() => [...new Set(
  venueRecords.value
    .map((venue) => formatFloorLabel(venue.floorLevel))
    .filter(Boolean),
)].sort((first, second) => extractFloorNumber(second) - extractFloorNumber(first)));

const filteredVenueRecords = computed(() => {
  const query = venueSearchQuery.value.trim().toLowerCase();

  return [...venueRecords.value]
    .filter((venue) => {
      if (venueFloorFilter.value !== 'all' && formatFloorLabel(venue.floorLevel) !== venueFloorFilter.value) {
        return false;
      }

      if (venueStatusFilter.value === 'available' && !venue.venueAvailable) return false;
      if (venueStatusFilter.value === 'unavailable' && venue.venueAvailable) return false;

      if (!query) return true;

      return [
        venue.venueName,
        venue.venueLocation,
        venue.venueType,
        formatFloorLabel(venue.floorLevel),
      ].filter(Boolean).join(' ').toLowerCase().includes(query);
    })
    .sort((first, second) => sortVenueRecords(first, second, venueSortValue.value));
});

const venueTotalPages = computed(() => Math.max(1, Math.ceil(filteredVenueRecords.value.length / venuePageSize.value)));
const paginatedVenues = computed(() => {
  const startIndex = (venueCurrentPage.value - 1) * venuePageSize.value;
  return filteredVenueRecords.value.slice(startIndex, startIndex + venuePageSize.value);
});
const venuePageStart = computed(() => filteredVenueRecords.value.length === 0 ? 0 : ((venueCurrentPage.value - 1) * venuePageSize.value) + 1);
const venuePageEnd = computed(() => Math.min(venueCurrentPage.value * venuePageSize.value, filteredVenueRecords.value.length));
const visibleVenuePages = computed(() => buildVisiblePages(venueTotalPages.value));

const filteredEquipmentRecords = computed(() => {
  const query = equipmentSearchQuery.value.trim().toLowerCase();

  return [...equipmentList.value]
    .filter((equipment) => {
      const statusValue = formatEquipmentStatus(equipment);
      if (equipmentStatusFilter.value === 'available' && statusValue !== 'Available') return false;
      if (equipmentStatusFilter.value === 'unavailable' && statusValue !== 'Unavailable') return false;
      if (equipmentStatusFilter.value === 'maintenance' && statusValue !== 'Under Maintenance') return false;
      if (equipmentStatusFilter.value === 'retired' && statusValue !== 'Retired') return false;

      if (!query) return true;

      return [
        equipment.equipmentName,
        equipment.equipmentCategory,
        equipment.categoryName,
        equipment.equipmentBrand,
        equipment.barcode,
        equipment.assetId,
        equipment.serialNumber,
      ].filter(Boolean).join(' ').toLowerCase().includes(query);
    })
    .sort((first, second) => sortEquipmentRecords(first, second, equipmentSortValue.value));
});

const equipmentTotalPages = computed(() => Math.max(1, Math.ceil(filteredEquipmentRecords.value.length / equipmentPageSize.value)));
const paginatedEquipment = computed(() => {
  const startIndex = (equipmentCurrentPage.value - 1) * equipmentPageSize.value;
  return filteredEquipmentRecords.value.slice(startIndex, startIndex + equipmentPageSize.value);
});
const equipmentPageStart = computed(() => filteredEquipmentRecords.value.length === 0 ? 0 : ((equipmentCurrentPage.value - 1) * equipmentPageSize.value) + 1);
const equipmentPageEnd = computed(() => Math.min(equipmentCurrentPage.value * equipmentPageSize.value, filteredEquipmentRecords.value.length));
const visibleEquipmentPages = computed(() => buildVisiblePages(equipmentTotalPages.value));

watch([venueSearchQuery, venueFloorFilter, venueStatusFilter, venueSortValue, venuePageSize], () => {
  venueCurrentPage.value = 1;
});

watch([equipmentSearchQuery, equipmentStatusFilter, equipmentSortValue, equipmentPageSize], () => {
  equipmentCurrentPage.value = 1;
});

watch(venueTotalPages, (pageCount) => {
  if (venueCurrentPage.value > pageCount) {
    venueCurrentPage.value = pageCount;
  }
});

watch(equipmentTotalPages, (pageCount) => {
  if (equipmentCurrentPage.value > pageCount) {
    equipmentCurrentPage.value = pageCount;
  }
});

watch(
  () => route.query.tab,
  (tabValue) => {
    activeTab.value = tabValue === 'equipment' ? 'equipment' : 'venue';
  }
);

onMounted(() => {
  fetchEquipment();
});

function setActiveTab(tabName) {
  if (activeTab.value === tabName) {
    return;
  }

  if (activeTab.value === 'equipment') {
    clearEquipmentTransientState();
  }

  activeTab.value = tabName;
  const nextQuery = { ...route.query };
  if (tabName === 'equipment') {
    nextQuery.tab = 'equipment';
  } else {
    delete nextQuery.tab;
  }
  router.replace({
    query: nextQuery,
  });
}

function handleHeroAdd() {
  if (activeTab.value === 'venue') {
    alert('Add venue functionality coming soon');
    return;
  }

  selectedEquipmentDraft.value = null;
  equipmentError.value = '';
  showEquipmentModal.value = true;
}

function handleHeroEdit() {
  if (activeTab.value === 'venue') {
    handleEditVenue(selectedVenueRecord.value);
    return;
  }

  if (!selectedEquipmentRecord.value) {
    equipmentError.value = 'Select an equipment record first before editing.';
    return;
  }

  handleEditEquipment(selectedEquipmentRecord.value);
}

function handleAddVenue() {
  alert('Add venue functionality coming soon');
}

function handleEditVenue(venue) {
  if (!venue) return;
  selectedVenueRecord.value = venue;
  alert(`Edit venue: ${venue.venueName}`);
}

function handleViewVenue(venue) {
  selectedVenueRecord.value = venue;
  alert(`View venue: ${venue.venueName}`);
}

function handleSelectEquipment(equipmentRecord) {
  selectedEquipmentRecord.value = equipmentRecord;
  equipmentError.value = '';
}

function handleEditEquipment(equipmentRecord) {
  selectedEquipmentRecord.value = equipmentRecord;
  selectedEquipmentDraft.value = equipmentRecord;
  equipmentError.value = '';
  showEquipmentModal.value = true;
}

async function handleViewEquipment(equipmentRecord) {
  if (!equipmentRecord?.equipmentIdentifier) {
    return;
  }

  selectedEquipmentRecord.value = equipmentRecord;
  equipmentError.value = '';

  try {
    const response = await equipmentApi.getEquipmentById(equipmentRecord.equipmentIdentifier);
    viewEquipmentRecord.value = response?.data || response;
  } catch (error) {
    equipmentError.value = error?.response?.data?.errorMessage || 'Failed to load equipment details.';
  }
}

function closeEquipmentDetails() {
  viewEquipmentRecord.value = null;
}

function openEditFromDetails() {
  if (!viewEquipmentRecord.value) {
    return;
  }

  selectedEquipmentDraft.value = viewEquipmentRecord.value;
  showEquipmentModal.value = true;
  closeEquipmentDetails();
}

function handleEquipmentModalClose() {
  showEquipmentModal.value = false;
  selectedEquipmentDraft.value = null;
}

async function handleEquipmentModalSaved() {
  showEquipmentModal.value = false;
  selectedEquipmentDraft.value = null;
  await fetchEquipment();
}

function openDeleteEquipmentModal(equipmentRecord) {
  selectedEquipmentRecord.value = equipmentRecord;
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
    if (selectedEquipmentRecord.value?.equipmentIdentifier === deletedIdentifier) {
      selectedEquipmentRecord.value = null;
    }
    if (viewEquipmentRecord.value?.equipmentIdentifier === deletedIdentifier) {
      closeEquipmentDetails();
    }
    await fetchEquipment();
  } catch (error) {
    deleteEquipmentError.value = error?.response?.data?.errorMessage || 'Failed to delete equipment. Please try again.';
  } finally {
    isDeletingEquipment.value = false;
  }
}

function resetVenueFilters() {
  venueSearchQuery.value = '';
  venueFloorFilter.value = 'all';
  venueStatusFilter.value = 'all';
  venueSortValue.value = 'name-asc';
  venuePageSize.value = 10;
}

function resetEquipmentFilters() {
  equipmentSearchQuery.value = '';
  equipmentStatusFilter.value = 'all';
  equipmentSortValue.value = 'name-asc';
  equipmentPageSize.value = 6;
}

function clearEquipmentTransientState() {
  showEquipmentModal.value = false;
  selectedEquipmentDraft.value = null;
  viewEquipmentRecord.value = null;
  closeDeleteEquipmentModal();
}

function buildVisiblePages(totalPages) {
  const pages = [];
  for (let pageNumber = 1; pageNumber <= totalPages; pageNumber += 1) {
    pages.push(pageNumber);
  }
  return pages.slice(0, 5);
}

function sortVenueRecords(first, second, sortValue) {
  if (sortValue === 'name-desc') {
    return second.venueName.localeCompare(first.venueName);
  }
  if (sortValue === 'floor-asc') {
    return extractFloorNumber(first.floorLevel) - extractFloorNumber(second.floorLevel);
  }
  if (sortValue === 'floor-desc') {
    return extractFloorNumber(second.floorLevel) - extractFloorNumber(first.floorLevel);
  }
  if (sortValue === 'capacity-desc') {
    return Number(second.capacityLimit || 0) - Number(first.capacityLimit || 0);
  }
  if (sortValue === 'capacity-asc') {
    return Number(first.capacityLimit || 0) - Number(second.capacityLimit || 0);
  }
  return first.venueName.localeCompare(second.venueName);
}

function sortEquipmentRecords(first, second, sortValue) {
  if (sortValue === 'name-desc') {
    return String(second.equipmentName || '').localeCompare(String(first.equipmentName || ''));
  }

  if (sortValue === 'updated-desc') {
    return new Date(second.updatedTimestamp || second.createdTimestamp).getTime()
      - new Date(first.updatedTimestamp || first.createdTimestamp).getTime();
  }

  if (sortValue === 'quantity-desc') {
    return Number(second.availableQuantity || 0) - Number(first.availableQuantity || 0);
  }

  if (sortValue === 'quantity-asc') {
    return Number(first.availableQuantity || 0) - Number(second.availableQuantity || 0);
  }

  return String(first.equipmentName || '').localeCompare(String(second.equipmentName || ''));
}

function formatFloorLabel(value) {
  if (!value && value !== 0) return 'No Floor';
  return `${value}th Floor`;
}

function extractFloorNumber(value) {
  const parsedNumber = Number.parseInt(String(value || '').replace(/[^\d-]/g, ''), 10);
  return Number.isNaN(parsedNumber) ? 0 : parsedNumber;
}

function formatCapacity(value) {
  return Number(value || 0);
}

function resolveVenueType(venue) {
  return venue.venueType || (String(venue.venueName || '').toLowerCase().includes('gym') ? 'Sports Facility' : 'Venue');
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
</script>
