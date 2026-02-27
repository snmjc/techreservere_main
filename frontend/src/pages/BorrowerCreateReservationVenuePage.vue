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

    <!-- ===== VENUE SECTION (shown when type is Venue or Both) ===== -->
    <template v-if="showVenueSection">
      <!-- Toolbar: Showing + Legend -->
      <h3 class="create-reservation-venue-section-heading">Venue Selection</h3>
      <div class="create-reservation-venue-toolbar">
        <div class="create-reservation-venue-showing-group">
          <label class="create-reservation-venue-showing-label" for="venueShowingSelect">Showing:</label>
          <select
            id="venueShowingSelect"
            v-model="showingFilterValue"
            class="create-reservation-venue-showing-select"
          >
            <option value="all">All</option>
            <option value="available">Available</option>
            <option value="unavailable">Unavailable</option>
          </select>
          <button class="create-reservation-venue-sort-button" aria-label="Sort">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19" /><polyline points="19 12 12 19 5 12" />
            </svg>
          </button>
        </div>
        <div class="create-reservation-venue-legend">
          <span class="create-reservation-venue-legend-item">
            <span class="create-reservation-venue-legend-dot create-reservation-venue-legend-dot--available"></span>
            Available
          </span>
          <span class="create-reservation-venue-legend-item">
            <span class="create-reservation-venue-legend-dot create-reservation-venue-legend-dot--unavailable"></span>
            Unavailable
          </span>
        </div>
      </div>

      <!-- Venue Selection Area -->
      <div class="create-reservation-venue-selection-area">
        <div
          v-for="floorGroup in filteredVenueFloorGroups"
          :key="floorGroup.floorLabel"
          class="create-reservation-venue-floor-group"
        >
          <p class="create-reservation-venue-floor-label">{{ floorGroup.floorLabel }}</p>
          <div class="create-reservation-venue-chips-row">
            <span
              v-for="venueRecord in floorGroup.venueRecords"
              :key="venueRecord.venueName"
              class="create-reservation-venue-chip"
              :class="{
                'create-reservation-venue-chip--available': venueRecord.venueAvailable && selectedVenueName !== venueRecord.venueName,
                'create-reservation-venue-chip--unavailable': !venueRecord.venueAvailable,
                'create-reservation-venue-chip--selected': selectedVenueName === venueRecord.venueName,
              }"
              @click="handleVenueChipSelection(venueRecord)"
            >
              {{ venueRecord.venueName }}
            </span>
          </div>
        </div>
        <div v-if="filteredVenueFloorGroups.length === 0" class="create-reservation-venue-empty-state">
          No venues found.
        </div>
      </div>
    </template>

    <!-- ===== EQUIPMENT SECTION (shown when type is Equipment or Both) ===== -->
    <template v-if="showEquipmentSection">
      <h3 class="create-reservation-venue-section-heading">Equipment Selection</h3>
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
                v-for="equipmentItem in equipmentItemsList"
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
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/borrowerCreateReservationVenuePage.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { useReservationFormStore } from '@/modules/reservation/store/reservationFormStore.js';

const router = useRouter();
const reservationFormStore = useReservationFormStore();
const showingFilterValue = ref('all');
const selectedVenueName = ref(reservationFormStore.selectedVenueName);

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

/**
 * @constant {Array<Object>} equipmentItemsList
 * @description Static equipment data for reservation selection.
 */
const equipmentItemsList = ref([
  { equipmentName: 'White Monobloc Chair', availableCount: 200, selectedQuantity: 0 },
  { equipmentName: 'Table', availableCount: 50, selectedQuantity: 0 },
  { equipmentName: 'Podium', availableCount: 5, selectedQuantity: 0 },
  { equipmentName: 'Microphone', availableCount: 10, selectedQuantity: 0 },
  { equipmentName: 'Extension Cord', availableCount: 20, selectedQuantity: 0 },
  { equipmentName: 'Whiteboard', availableCount: 15, selectedQuantity: 0 },
  { equipmentName: 'Projector', availableCount: 8, selectedQuantity: 0 },
  { equipmentName: 'Stage', availableCount: 0, selectedQuantity: 0 },
  { equipmentName: 'Sound System', availableCount: 0, selectedQuantity: 0 },
]);

/**
 * @constant {Array<Object>} venueFloorGroupsList
 * @description Static venue data grouped by floor for reservation selection.
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
 * @description Filters venue floor groups based on showing filter value.
 * @returns {Array<Object>}
 */
const filteredVenueFloorGroups = computed(() => {
  if (showingFilterValue.value === 'all') {
    return venueFloorGroupsList.value;
  }
  const isAvailableFilter = showingFilterValue.value === 'available';
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
