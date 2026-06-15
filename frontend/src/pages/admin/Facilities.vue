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
            :disabled="!selectedVenueRecord"
            @click="handleEditVenue(selectedVenueRecord)"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 20h9" />
              <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" />
            </svg>
            Edit Venue
          </button>
          <button class="facilities-hero-button facilities-hero-button--primary" type="button" @click="handleAddVenue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 5v14" />
              <path d="M5 12h14" />
            </svg>
            Add Venue
          </button>
        </div>
      </header>

      <div class="facilities-tabs">
        <button
          class="facilities-tab"
          :class="{ 'facilities-tab--active': activeTab === 'venue' }"
          type="button"
          @click="activeTab = 'venue'"
        >
          Venue
        </button>
        <button
          class="facilities-tab"
          :class="{ 'facilities-tab--active': activeTab === 'equipment' }"
          type="button"
          @click="activeTab = 'equipment'"
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
        <div class="facilities-equipment-toolbar">
          <div class="facilities-toolbar-left">
            <button class="facilities-hero-button facilities-hero-button--secondary" type="button" @click="openEquipmentManager">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 20h9" />
                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" />
              </svg>
              Manage Equipment
            </button>
            <button class="facilities-hero-button facilities-hero-button--primary" type="button" @click="openEquipmentManager">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14" />
                <path d="M5 12h14" />
              </svg>
              Open Full Lifecycle
            </button>
          </div>

          <label class="facilities-inline-filter">
            <span>Status</span>
            <select v-model="equipmentFilterValue">
              <option value="all">All</option>
              <option value="available">Available</option>
              <option value="unavailable">Unavailable</option>
              <option value="maintenance">Under Maintenance</option>
              <option value="retired">Retired</option>
            </select>
          </label>
        </div>

        <p v-if="equipmentError" class="facilities-feedback facilities-feedback--error">{{ equipmentError }}</p>
        <div v-if="equipmentLoading" class="facilities-empty-state">
          <p>Loading equipment records...</p>
        </div>
        <div v-else class="facilities-equipment-grid">
          <div
            v-for="equipment in filteredEquipment"
            :key="equipment.equipmentIdentifier"
            class="facilities-equipment-card"
            :class="equipment.equipmentState === 'Available' ? 'facilities-equipment-card--available' : 'facilities-equipment-card--unavailable'"
          >
            <div class="facilities-equipment-card-header">
              <strong>{{ equipment.equipmentName }}</strong>
              <span class="facilities-status-pill" :class="equipment.equipmentState === 'Available' ? 'facilities-status-pill--available' : 'facilities-status-pill--unavailable'">
                {{ equipment.equipmentState }}
              </span>
            </div>
            <p><strong>Category:</strong> {{ equipment.categoryName }}</p>
            <p><strong>Available:</strong> {{ equipment.availableQuantity }} / {{ equipment.totalQuantity }}</p>
            <p><strong>Updated:</strong> {{ formatDateTime(equipment.updatedTimestamp || equipment.createdTimestamp) }}</p>
          </div>
        </div>

        <div v-if="!equipmentLoading && filteredEquipment.length === 0" class="facilities-empty-state">
          <p>No equipment records match the selected filter.</p>
        </div>
      </section>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/Facilities.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import equipmentApi from '@/modules/reservation/services/equipmentApi.js';

const router = useRouter();
const activeTab = ref('venue');
const equipmentFilterValue = ref('all');
const equipmentSortOrder = ref('asc');
const equipmentList = ref([]);
const equipmentLoading = ref(false);
const equipmentError = ref('');
const venueLoading = ref(false);

const venueSearchQuery = ref('');
const venueFloorFilter = ref('all');
const venueStatusFilter = ref('all');
const venueSortValue = ref('name-asc');
const venueCurrentPage = ref(1);
const venuePageSize = ref(10);
const selectedVenueRecord = ref(null);

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

const visibleVenuePages = computed(() => {
  const pages = [];
  for (let pageNumber = 1; pageNumber <= venueTotalPages.value; pageNumber += 1) {
    pages.push(pageNumber);
  }
  return pages.slice(0, 5);
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

  return [...filtered].sort((first, second) => {
    const nameA = first.equipmentName.toLowerCase();
    const nameB = second.equipmentName.toLowerCase();
    if (equipmentSortOrder.value === 'asc') {
      return nameA.localeCompare(nameB);
    }
    return nameB.localeCompare(nameA);
  });
});

watch([venueSearchQuery, venueFloorFilter, venueStatusFilter, venueSortValue, venuePageSize], () => {
  venueCurrentPage.value = 1;
});

watch(venueTotalPages, (pageCount) => {
  if (venueCurrentPage.value > pageCount) {
    venueCurrentPage.value = pageCount;
  }
});

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

function openEquipmentManager() {
  router.push({ name: 'adminManageEquipmentPage' });
}

function resetVenueFilters() {
  venueSearchQuery.value = '';
  venueFloorFilter.value = 'all';
  venueStatusFilter.value = 'all';
  venueSortValue.value = 'name-asc';
  venuePageSize.value = 10;
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

function formatDateTime(value) {
  if (!value) return 'N/A';

  const parsedDate = new Date(value);
  if (Number.isNaN(parsedDate.getTime())) return 'N/A';

  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(parsedDate);
}

onMounted(() => {
  venueLoading.value = false;
  fetchEquipment();
});
</script>
