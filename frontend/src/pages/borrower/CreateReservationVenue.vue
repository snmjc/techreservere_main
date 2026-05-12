<!-- ===== AI GENERATED: BorrowerCreateReservationVenuePage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'DELA CRUZ, JUAN'"
    :navigation-items="borrowerNavigationItems"
  >
    <!-- Page Header -->
    <div class="create-reservation-venue-page-header">
      <h2 class="create-reservation-venue-page-heading">Create Reservation</h2>
    </div>

    <!-- Form Subtitle -->
    <p class="create-reservation-venue-form-subtitle">{{ formSubtitle }}</p>

    <!-- Tabs Section -->
    <div class="create-reservation-tabs">
      <button
        v-if="showVenueSection"
        class="create-reservation-tab"
        :class="{ 'create-reservation-tab--active': activeTab === 'venue' }"
        @click="activeTab = 'venue'"
      >
        Venue
      </button>
      <button
        v-if="showEquipmentSection"
        class="create-reservation-tab"
        :class="{ 'create-reservation-tab--active': activeTab === 'equipment' }"
        @click="activeTab = 'equipment'"
      >
        Equipment
      </button>
    </div>

    <!-- ===== VENUE SECTION (shown when type is Venue or Both) ===== -->
    <template v-if="showVenueSection && activeTab === 'venue'">
      <!-- Toolbar: Filter + Legend -->
      <div class="create-reservation-toolbar">
        <div class="create-reservation-filter-group">
          <label class="create-reservation-filter-label" for="venueShowingSelect">Filter:</label>
          <select
            id="venueShowingSelect"
            v-model="showingFilterValue"
            class="create-reservation-filter-select"
          >
            <option value="all">All</option>
            <option value="available">Available</option>
            <option value="unavailable">Unavailable</option>
          </select>
          <button class="create-reservation-sort-button" @click="toggleSortOrder" :title="sortOrderValue === 'asc' ? 'Sort A-Z' : 'Sort Z-A'" aria-label="Sort">
            <svg v-if="sortOrderValue === 'asc'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19" /><polyline points="19 12 12 19 5 12" />
            </svg>
            <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="19" x2="12" y2="5" /><polyline points="5 12 12 5 19 12" />
            </svg>
          </button>
        </div>
        <div class="create-reservation-legend">
          <span class="create-reservation-legend-item">
            <span class="create-reservation-legend-dot create-reservation-legend-dot--available"></span>
            Available
          </span>
          <span class="create-reservation-legend-item">
            <span class="create-reservation-legend-dot create-reservation-legend-dot--unavailable"></span>
            Unavailable
          </span>
        </div>
      </div>

      <!-- Venue Selection Area -->
      <div class="create-reservation-venues-grid">
        <div
          v-for="floorGroup in filteredVenueFloorGroups"
          :key="floorGroup.floorLabel"
          class="create-reservation-floor-section"
        >
          <h3 class="create-reservation-floor-heading">{{ floorGroup.floorLabel }}</h3>
          <div class="create-reservation-venue-grid">
            <div
              v-for="venueRecord in floorGroup.venueRecords"
              :key="venueRecord.venueName"
              class="create-reservation-venue-card"
              :class="{
                'create-reservation-venue-card--available': venueRecord.venueAvailable && selectedVenueName !== venueRecord.venueName,
                'create-reservation-venue-card--unavailable': !venueRecord.venueAvailable,
                'create-reservation-venue-card--selected': selectedVenueName === venueRecord.venueName,
              }"
              @click="handleVenueChipSelection(venueRecord)"
            >
              <div class="create-reservation-venue-card-header">
                <h4 class="create-reservation-venue-name">{{ venueRecord.venueName }}</h4>
                <div class="create-reservation-venue-status">
                  <span class="create-reservation-status-badge" :class="venueRecord.venueAvailable ? 'available' : 'unavailable'">
                    {{ venueRecord.venueAvailable ? 'Available' : 'Unavailable' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-if="filteredVenueFloorGroups.length === 0" class="create-reservation-empty-state">
          No venues found.
        </div>
      </div>
    </template>

    <!-- ===== EQUIPMENT SECTION (shown when type is Equipment or Both) ===== -->
    <template v-if="showEquipmentSection && activeTab === 'equipment'">
      <!-- Toolbar: Filter + Legend -->
      <div class="create-reservation-toolbar">
        <div class="create-reservation-filter-group">
          <label class="create-reservation-filter-label" for="equipmentFilter">Filter:</label>
          <select
            id="equipmentFilter"
            v-model="equipmentFilterValue"
            class="create-reservation-filter-select"
          >
            <option value="all">All</option>
            <option value="available">Available</option>
            <option value="unavailable">Unavailable</option>
          </select>
        </div>
        <div class="create-reservation-legend">
          <span class="create-reservation-legend-item">
            <span class="create-reservation-legend-dot create-reservation-legend-dot--available"></span>
            Available
          </span>
          <span class="create-reservation-legend-item">
            <span class="create-reservation-legend-dot create-reservation-legend-dot--unavailable"></span>
            Unavailable
          </span>
        </div>
      </div>

      <div class="create-reservation-equipment-selection-area">
        <div class="create-reservation-equipment-table-wrapper">
          <table class="create-reservation-equipment-table">
            <thead>
              <tr class="create-reservation-equipment-table-header-row">
                <th class="create-reservation-equipment-table-header-cell">Equipment</th>
                <th class="create-reservation-equipment-table-header-cell">Available</th>
                <th class="create-reservation-equipment-table-header-cell">Quantity</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="equipmentItem in filteredEquipmentList"
                :key="equipmentItem.equipmentName"
                class="create-reservation-equipment-table-body-row"
                :class="{ 'create-reservation-equipment-table-body-row--unavailable': equipmentItem.availableCount === 0 }"
              >
                <td class="create-reservation-equipment-table-cell">{{ equipmentItem.equipmentName }}</td>
                <td class="create-reservation-equipment-table-cell">{{ equipmentItem.availableCount }}</td>
                <td class="create-reservation-equipment-table-cell">
                  <input
                    v-model.number="equipmentItem.selectedQuantity"
                    type="number"
                    min="0"
                    :max="equipmentItem.availableCount"
                    class="create-reservation-equipment-qty-input"
                    :disabled="equipmentItem.availableCount === 0"
                    placeholder="0"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- Navigation Buttons -->
    <div class="create-reservation-venue-selection-area">
      <div class="create-reservation-venue-form-actions">
        <button class="create-reservation-venue-prev-button" @click="navigateToPreviousPage">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"/>
          </svg>
          Previous Page
        </button>
        <button class="create-reservation-venue-next-button" @click="navigateToNextPage">
          Next Page
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Footer -->
    <div class="create-reservation-venue-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/CreateReservationVenue.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { useReservationFormStore } from '@/modules/reservation/store/reservationFormStore.js';
import { useReservationData } from '@/modules/reservation/composables/useReservationData.js';

const router = useRouter();
const reservationFormStore = useReservationFormStore();
const { equipmentList, venueList, loadAllData } = useReservationData();
const activeTab = ref('venue');
const showingFilterValue = ref('all');
const equipmentFilterValue = ref('all');
const sortOrderValue = ref('asc');
const selectedVenueName = ref(reservationFormStore.selectedVenueName);

onMounted(async () => {
  await loadAllData();
});

const showVenueSection = computed(() =>
  reservationFormStore.reservationType === 'Venue' || reservationFormStore.reservationType === 'Both'
);
const showEquipmentSection = computed(() =>
  reservationFormStore.reservationType === 'Equipment' || reservationFormStore.reservationType === 'Both'
);

const formSubtitle = computed(() => {
  const type = reservationFormStore.reservationType;
  if (type === 'Venue') return 'Venue Reservation Form';
  if (type === 'Equipment') return 'Equipment Reservation Form';
  return 'Venue and Equipment Reservation Form';
});

const equipmentItemsList = ref([
  { equipmentIdentifier: 1, equipmentName: 'Chairs', availableCount: 200, selectedQuantity: 0 },
  { equipmentIdentifier: 2, equipmentName: 'Tables', availableCount: 50, selectedQuantity: 0 },
  { equipmentIdentifier: 3, equipmentName: 'Podium', availableCount: 5, selectedQuantity: 0 },
  { equipmentIdentifier: 4, equipmentName: 'Microphone', availableCount: 10, selectedQuantity: 0 },
  { equipmentIdentifier: 5, equipmentName: 'AUX Cord', availableCount: 20, selectedQuantity: 0 },
  { equipmentIdentifier: 6, equipmentName: 'Sound System', availableCount: 0, selectedQuantity: 0 },
  { equipmentIdentifier: 7, equipmentName: 'Extension Cord', availableCount: 15, selectedQuantity: 0 },
  { equipmentIdentifier: 8, equipmentName: 'Stage', availableCount: 0, selectedQuantity: 0 },
  { equipmentIdentifier: 9, equipmentName: 'Panel Board', availableCount: 8, selectedQuantity: 0 },
  { equipmentIdentifier: 10, equipmentName: 'White Screen', availableCount: 12, selectedQuantity: 0 },
  { equipmentIdentifier: 11, equipmentName: 'Philippine Flag', availableCount: 0, selectedQuantity: 0 },
  { equipmentIdentifier: 12, equipmentName: 'FEU Tech Flag', availableCount: 6, selectedQuantity: 0 },
  { equipmentIdentifier: 13, equipmentName: 'LED Video Wall', availableCount: 0, selectedQuantity: 0 },
  { equipmentIdentifier: 14, equipmentName: 'Projector', availableCount: 0, selectedQuantity: 0 },
  { equipmentIdentifier: 15, equipmentName: 'Flood Board', availableCount: 0, selectedQuantity: 0 },
  { equipmentIdentifier: 16, equipmentName: 'Others', availableCount: 5, selectedQuantity: 0 },
]);

const venueFloorGroupsList = computed(() => {
  const improvised = [
    {
      floorLabel: '18th Floor',
      venueRecords: [
        { venueIdentifier: 1, venueName: '18F Roofdeck', venueAvailable: true },
      ],
    },
    {
      floorLabel: '17th Floor',
      venueRecords: [
        { venueIdentifier: 2, venueName: '17F MPR', venueAvailable: true },
        { venueIdentifier: 3, venueName: 'Basketball without Aircon', venueAvailable: true },
        { venueIdentifier: 4, venueName: 'Basketball gym with Aircon', venueAvailable: false },
        { venueIdentifier: 5, venueName: 'Basketball gym with Aircon and Green Matting', venueAvailable: true },
      ],
    },
    {
      floorLabel: '16th Floor',
      venueRecords: [
        { venueIdentifier: 6, venueName: 'F1603 Audio Visual Room', venueAvailable: false },
        { venueIdentifier: 7, venueName: 'F1604 Case Room', venueAvailable: true },
      ],
    },
    {
      floorLabel: '15th Floor',
      venueRecords: [
        { venueIdentifier: 8, venueName: 'F1502 Multipurpose Room', venueAvailable: true },
        { venueIdentifier: 9, venueName: 'F1503 Multipurpose Room', venueAvailable: false },
        { venueIdentifier: 10, venueName: 'F1504 Multipurpose Room', venueAvailable: true },
      ],
    },
    {
      floorLabel: '8th Floor',
      venueRecords: [
        { venueIdentifier: 11, venueName: '8F Exec. Lounge 1', venueAvailable: false },
        { venueIdentifier: 12, venueName: '8F Exec. Lounge 2', venueAvailable: true },
        { venueIdentifier: 13, venueName: '8F Exec. Lounge 1 and 2 Combined', venueAvailable: true },
        { venueIdentifier: 14, venueName: '8F Student Lounge', venueAvailable: true },
      ],
    },
    {
      floorLabel: '4th - 7th Floor',
      venueRecords: [
        { venueIdentifier: 15, venueName: 'F407', venueAvailable: true },
        { venueIdentifier: 16, venueName: 'F503', venueAvailable: true },
        { venueIdentifier: 17, venueName: 'F608', venueAvailable: true },
        { venueIdentifier: 18, venueName: 'F704', venueAvailable: true },
        { venueIdentifier: 19, venueName: 'F711', venueAvailable: true },
      ],
    },
    {
      floorLabel: '3rd Floor',
      venueRecords: [
        { venueIdentifier: 20, venueName: 'FEU Tech Swimming Pool', venueAvailable: true },
      ],
    },
    {
      floorLabel: '2nd Floor',
      venueRecords: [
        { venueIdentifier: 21, venueName: '2F FIT Student Plaza', venueAvailable: false },
      ],
    },
  ];
  return improvised;
});

/**
 * @function filteredVenueFloorGroups
 * @description Filters and sorts venue floor groups based on showing filter value and sort order.
 * @returns {Array<Object>}
 */
const filteredVenueFloorGroups = computed(() => {
  let filtered = venueFloorGroupsList.value;
  
  // Apply availability filter
  if (showingFilterValue.value !== 'all') {
    const isAvailableFilter = showingFilterValue.value === 'available';
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
      if (sortOrderValue.value === 'asc') {
        return nameA.localeCompare(nameB);
      } else {
        return nameB.localeCompare(nameA);
      }
    }),
  }));
});

/**
 * @function filteredEquipmentList
 * @description Filters equipment based on availability filter.
 * @returns {Array<Object>}
 */
const filteredEquipmentList = computed(() => {
  let filtered = equipmentItemsList.value;
  
  // Apply availability filter
  if (equipmentFilterValue.value !== 'all') {
    const isAvailableFilter = equipmentFilterValue.value === 'available';
    filtered = filtered.filter((equipment) => {
      const isAvailable = equipment.availableCount > 0;
      return isAvailable === isAvailableFilter;
    });
  }
  
  return filtered;
});

/**
 * @function toggleSortOrder
 * @description Toggles between ascending (A-Z) and descending (Z-A) sort order.
 * @returns {void}
 */
function toggleSortOrder() {
  sortOrderValue.value = sortOrderValue.value === 'asc' ? 'desc' : 'asc';
}

/**
 * @function handleVenueChipSelection
 * @description Selects or deselects a venue chip. Only available venues are selectable.
 * @param {Object} venueRecord - The venue record clicked
 * @returns {void}
 */
function handleVenueChipSelection(venueRecord) {
  if (!venueRecord.venueAvailable) return;
  if (selectedVenueName.value === venueRecord.venueName) {
    selectedVenueName.value = null;
  } else {
    selectedVenueName.value = venueRecord.venueName;
  }
}

/**
 * @function navigateToPreviousPage
 * @description Navigates back to page 1 of the create reservation flow.
 * @returns {void}
 */
function navigateToPreviousPage() {
  router.push({ name: 'borrowerCreateReservationPage' });
}

/**
 * @function navigateToNextPage
 * @description Navigates to page 3 of the create reservation flow (documents/submit).
 * @returns {void}
 */
function navigateToNextPage() {
  reservationFormStore.selectedVenueName = selectedVenueName.value;
  reservationFormStore.selectedEquipmentItems = equipmentItemsList.value
    .filter((item) => item.selectedQuantity > 0)
    .map((item) => ({ equipmentName: item.equipmentName, selectedQuantity: item.selectedQuantity }));
  router.push({ name: 'borrowerCreateReservationDocumentsPage' });
}
</script>
