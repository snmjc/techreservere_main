<template>
  <AdminSidebarLayoutComponent
    :role-label="'DELA CRUZ, JUAN'"
    :navigation-items="borrowerNavigationItems"
  >
    <section class="borrower-reservation-page">
      <div class="borrower-reservation-topline">
        <h1>Create Reservation</h1>
      </div>

      <div class="borrower-reservation-surface">
        <BorrowerReservationStepper :current-step="2" />

        <section class="borrower-reservation-card">
          <div class="borrower-step-tabs" v-if="showVenueSection && showEquipmentSection">
            <button type="button" :class="{ 'is-active': activeTab === 'venue' }" @click="activeTab = 'venue'">Venues</button>
            <button type="button" :class="{ 'is-active': activeTab === 'equipment' }" @click="activeTab = 'equipment'">Equipment</button>
          </div>

          <div class="borrower-reservation-panel" v-if="isVenueTab">
            <h2>Select Venue</h2>
            <p>Choose the venue you want to reserve.</p>

            <p v-if="selectionWarning" class="reservation-selection-warning">{{ selectionWarning }}</p>

            <div class="reservation-selection-filters">
              <label class="borrower-reservation-field">
                <span>Search</span>
                <input v-model.trim="venueSearchQuery" type="search" placeholder="Search venue..." />
              </label>
              <label class="borrower-reservation-field">
                <span>Floor</span>
                <select v-model="venueFloorFilter">
                  <option value="all">All Floors</option>
                  <option v-for="floor in venueFloorOptions" :key="floor" :value="floor">{{ floor }}</option>
                </select>
              </label>
              <label class="borrower-reservation-field">
                <span>Status</span>
                <select v-model="venueStatusFilter">
                  <option value="all">All</option>
                  <option value="available">Available</option>
                  <option value="unavailable">Unavailable</option>
                </select>
              </label>
              <label class="borrower-reservation-field">
                <span>Sort By</span>
                <select v-model="venueSortValue">
                  <option value="name-asc">Venue Name (A - Z)</option>
                  <option value="name-desc">Venue Name (Z - A)</option>
                  <option value="capacity-desc">Capacity (High - Low)</option>
                  <option value="capacity-asc">Capacity (Low - High)</option>
                </select>
              </label>
              <button class="reservation-selection-reset" type="button" @click="resetVenueFilters">Reset Filters</button>
            </div>

            <div class="reservation-selection-table-card">
              <div class="reservation-selection-table-wrap">
                <table class="reservation-selection-table">
                  <thead>
                    <tr>
                      <th>Floor</th>
                      <th>Venue Name</th>
                      <th>Status</th>
                      <th>Capacity</th>
                      <th>Type</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="venue in paginatedVenues"
                      :key="venue.venueIdentifier"
                      :class="{ 'is-selected': selectedVenueRecord?.venueIdentifier === venue.venueIdentifier }"
                    >
                      <td>{{ venue.floorLabel }}</td>
                      <td>{{ venue.venueName }}</td>
                      <td>
                        <span class="reservation-status-pill" :class="venue.venueAvailable ? 'is-available' : 'is-unavailable'">
                          {{ venue.venueAvailable ? 'Available' : 'Unavailable' }}
                        </span>
                      </td>
                      <td>{{ venue.capacityLimit }}</td>
                      <td>{{ venue.venueType }}</td>
                      <td>
                        <button
                          class="reservation-selection-action"
                          :class="{ 'is-selected': selectedVenueRecord?.venueIdentifier === venue.venueIdentifier }"
                          type="button"
                          :disabled="!venue.venueAvailable"
                          @click="handleVenueSelection(venue)"
                        >
                          {{ selectedVenueRecord?.venueIdentifier === venue.venueIdentifier ? 'Selected' : 'Select' }}
                        </button>
                      </td>
                    </tr>
                    <tr v-if="filteredVenues.length === 0">
                      <td colspan="6" class="reservation-selection-empty-cell">No venues match the selected filters.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-if="filteredVenues.length > venuePageSize" class="reservation-selection-pagination">
              <button type="button" :disabled="venuePage === 1" @click="venuePage -= 1">Previous</button>
              <span>Page {{ venuePage }} of {{ venueTotalPages }}</span>
              <button type="button" :disabled="venuePage === venueTotalPages" @click="venuePage += 1">Next</button>
            </div>
          </div>

          <div class="borrower-reservation-panel" v-if="isEquipmentTab">
            <h2>Select Equipment</h2>
            <p>Choose the equipment you want to reserve.</p>

            <div class="reservation-equipment-layout">
              <div>
                <div class="reservation-selection-filters reservation-selection-filters--equipment">
                  <label class="borrower-reservation-field">
                    <span>Search</span>
                    <input v-model.trim="equipmentSearchQuery" type="search" placeholder="Search equipment..." />
                  </label>
                  <label class="borrower-reservation-field">
                    <span>Category</span>
                    <select v-model="equipmentCategoryFilter">
                      <option value="all">All Categories</option>
                      <option v-for="category in equipmentCategoryOptions" :key="category" :value="category">{{ category }}</option>
                    </select>
                  </label>
                  <label class="borrower-reservation-field">
                    <span>Status</span>
                    <select v-model="equipmentStatusFilter">
                      <option value="all">All</option>
                      <option value="available">Available</option>
                      <option value="unavailable">Unavailable</option>
                    </select>
                  </label>
                  <button class="reservation-selection-reset" type="button" @click="resetEquipmentFilters">Clear Filters</button>
                </div>

                <div class="reservation-selection-table-card">
                  <div class="reservation-selection-table-wrap">
                    <table class="reservation-selection-table reservation-selection-table--equipment">
                      <thead>
                        <tr>
                          <th>Equipment</th>
                          <th>Category</th>
                          <th>Quantity Available</th>
                          <th>Status</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="equipment in paginatedEquipmentRecords" :key="equipment.equipmentIdentifier">
                          <td>
                            <div class="reservation-equipment-name">
                              <strong>{{ equipment.equipmentName }}</strong>
                              <small>{{ equipment.equipmentBrand || equipment.equipmentCategory || 'Equipment Item' }}</small>
                            </div>
                          </td>
                          <td>{{ equipment.equipmentCategory }}</td>
                          <td>{{ equipment.availableQuantity }} units</td>
                          <td>
                            <span class="reservation-status-pill" :class="equipment.availableQuantity > 0 ? 'is-available' : 'is-unavailable'">
                              {{ equipment.availableQuantity > 0 ? 'Available' : 'Unavailable' }}
                            </span>
                          </td>
                          <td>
                            <div class="reservation-equipment-action">
                              <div class="reservation-equipment-quantity-control">
                                <input
                                  :value="getEquipmentSelectedQuantity(equipment)"
                                  type="text"
                                  inputmode="numeric"
                                  pattern="[0-9]*"
                                  class="reservation-equipment-quantity-input"
                                  :class="{ 'is-invalid': hasEquipmentQuantityError(equipment.equipmentIdentifier) }"
                                  :aria-label="`Quantity for ${equipment.equipmentName}`"
                                  :disabled="equipment.availableQuantity <= 0"
                                  @input="handleEquipmentQuantityInput(equipment, $event)"
                                  @keydown="handleEquipmentQuantityKeydown"
                                  @paste="handleEquipmentQuantityPaste(equipment, $event)"
                                />
                                <small v-if="getEquipmentQuantityError(equipment.equipmentIdentifier)" class="reservation-equipment-quantity-error">
                                  {{ getEquipmentQuantityError(equipment.equipmentIdentifier) }}
                                </small>
                              </div>
                            </div>
                          </td>
                        </tr>
                        <tr v-if="filteredEquipmentRecords.length === 0">
                          <td colspan="5" class="reservation-selection-empty-cell">No equipment records match the selected filters.</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div v-if="filteredEquipmentRecords.length > equipmentPageSize" class="reservation-selection-pagination">
                  <button type="button" :disabled="equipmentPage === 1" @click="equipmentPage -= 1">Previous</button>
                  <span>Page {{ equipmentPage }} of {{ equipmentTotalPages }}</span>
                  <button type="button" :disabled="equipmentPage === equipmentTotalPages" @click="equipmentPage += 1">Next</button>
                </div>
              </div>

              <aside class="reservation-equipment-sidebar">
                <h3>Selected Equipment ({{ selectedEquipmentItems.length }}/{{ MAX_EQUIPMENT_SELECTION_COUNT }})</h3>
                <div v-if="selectedEquipmentItems.length" class="reservation-equipment-selected-list">
                  <article v-for="item in selectedEquipmentItems" :key="item.equipmentIdentifier" class="reservation-equipment-selected-item">
                    <strong>{{ item.equipmentName }}</strong>
                    <span>{{ item.selectedQuantity }} qty</span>
                  </article>
                </div>
                <p v-else class="borrower-reservation-help">You can review and edit selected equipment on the next step.</p>
                <p class="borrower-reservation-help">Maximum equipment selections at a time: {{ MAX_EQUIPMENT_SELECTION_COUNT }}.</p>
              </aside>
            </div>
          </div>

          <footer class="borrower-reservation-actions">
            <button class="borrower-reservation-button borrower-reservation-button--secondary" type="button" @click="navigateToPreviousPage">
              Previous
            </button>
            <button class="borrower-reservation-button borrower-reservation-button--primary" type="button" :disabled="isStepLoading" @click="navigateToNextPage">
              {{ isStepLoading ? 'Loading...' : 'Next: Additional Information' }}
            </button>
          </footer>
        </section>
      </div>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import BorrowerReservationStepper from '@/modules/reservation/components/BorrowerReservationStepper.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/CreateReservationWizard.css';
import './css/CreateReservationVenue.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { useReservationFormStore } from '@/modules/reservation/store/reservationFormStore.js';
import { useReservationData } from '@/modules/reservation/composables/useReservationData.js';
import { MAX_EQUIPMENT_SELECTION_COUNT } from '@/modules/reservation/utils/reservationWizard.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';

const router = useRouter();
const reservationFormStore = useReservationFormStore();
const { equipmentList, venueList, loadAllData } = useReservationData();

const activeTab = ref(reservationFormStore.reservationType === 'Equipment' ? 'equipment' : 'venue');
const venueSearchQuery = ref('');
const venueFloorFilter = ref('all');
const venueStatusFilter = ref('all');
const venueSortValue = ref('name-asc');
const equipmentSearchQuery = ref('');
const equipmentCategoryFilter = ref('all');
const equipmentStatusFilter = ref('all');
const selectedVenueRecord = ref(reservationFormStore.selectedVenueRecord);
const selectedEquipmentItems = ref(buildInitialEquipmentSelection());
const equipmentQuantityErrors = ref({});
const selectionWarning = ref('');
const isStepLoading = ref(false);
const venuePage = ref(1);
const equipmentPage = ref(1);
const venuePageSize = 6;
const equipmentPageSize = 6;

const fallbackEquipment = [
  { equipmentIdentifier: 1, equipmentName: 'Wireless Microphone', equipmentCategory: 'Audio', equipmentBrand: 'Shure', availableQuantity: 10 },
  { equipmentIdentifier: 2, equipmentName: 'Portable Speaker', equipmentCategory: 'Audio', equipmentBrand: 'JBL', availableQuantity: 4 },
  { equipmentIdentifier: 3, equipmentName: 'LCD Projector', equipmentCategory: 'Visual', equipmentBrand: 'Epson', availableQuantity: 3 },
  { equipmentIdentifier: 4, equipmentName: 'Laptop', equipmentCategory: 'Others', equipmentBrand: 'Dell', availableQuantity: 8 },
  { equipmentIdentifier: 5, equipmentName: 'HDMI Cable', equipmentCategory: 'Accessories', equipmentBrand: 'UGreen', availableQuantity: 25 },
  { equipmentIdentifier: 6, equipmentName: 'Audio Mixer', equipmentCategory: 'Audio', equipmentBrand: 'Yamaha', availableQuantity: 0 },
];

onMounted(async () => {
  if (!reservationFormStore.hasReservationDetails()) {
    router.replace({ name: ROUTE_NAMES.borrowerCreateReservation });
    return;
  }

  await loadAllData({
    selectedDate: reservationFormStore.activityDate,
    startTime: reservationFormStore.activityTimeFrom,
    endTime: reservationFormStore.activityTimeTo,
  });
});

const showVenueSection = computed(() => ['Venue', 'Both'].includes(reservationFormStore.reservationType));
const showEquipmentSection = computed(() => ['Equipment', 'Both'].includes(reservationFormStore.reservationType));
const isVenueTab = computed(() => showVenueSection.value && (!showEquipmentSection.value || activeTab.value === 'venue'));
const isEquipmentTab = computed(() => showEquipmentSection.value && (!showVenueSection.value || activeTab.value === 'equipment'));

const normalizedVenueRecords = computed(() => venueList.value.map((venue) => ({
  ...venue,
  floorLabel: String(venue.floorLevel || 'No Floor'),
  venueType: inferVenueType(venue),
  venueAvailable: venue.availabilityStatus === 'Available',
})));

const venueFloorOptions = computed(() => [...new Set(normalizedVenueRecords.value.map((venue) => venue.floorLabel))]);

const filteredVenues = computed(() => {
  const query = venueSearchQuery.value.toLowerCase();
  return [...normalizedVenueRecords.value]
    .filter((venue) => {
      if (venueFloorFilter.value !== 'all' && venue.floorLabel !== venueFloorFilter.value) return false;
      if (venueStatusFilter.value === 'available' && !venue.venueAvailable) return false;
      if (venueStatusFilter.value === 'unavailable' && venue.venueAvailable) return false;
      return [venue.venueName, venue.floorLabel, venue.venueType].join(' ').toLowerCase().includes(query);
    })
    .sort((left, right) => {
      if (venueSortValue.value === 'name-desc') return right.venueName.localeCompare(left.venueName);
      if (venueSortValue.value === 'capacity-desc') return right.capacityLimit - left.capacityLimit;
      if (venueSortValue.value === 'capacity-asc') return left.capacityLimit - right.capacityLimit;
      return left.venueName.localeCompare(right.venueName);
    });
});

const venueTotalPages = computed(() => Math.max(1, Math.ceil(filteredVenues.value.length / venuePageSize)));
const paginatedVenues = computed(() => {
  const startIndex = (venuePage.value - 1) * venuePageSize;
  return filteredVenues.value.slice(startIndex, startIndex + venuePageSize);
});

const equipmentRecords = computed(() => {
  const apiRecords = equipmentList.value.map((item) => ({
    equipmentIdentifier: item.equipmentIdentifier,
    equipmentName: item.equipmentName,
    equipmentCategory: item.categoryName || 'Miscellaneous',
    equipmentBrand: item.equipmentBrand || item.categoryName || 'Equipment',
    availableQuantity: Number(item.availableQuantity ?? item.totalQuantity ?? 0),
  }));

  return apiRecords.length ? apiRecords : fallbackEquipment;
});

const equipmentCategoryOptions = computed(() => [...new Set(equipmentRecords.value.map((item) => item.equipmentCategory))]);

const filteredEquipmentRecords = computed(() => {
  const query = equipmentSearchQuery.value.toLowerCase();
  return equipmentRecords.value.filter((item) => {
    if (equipmentCategoryFilter.value !== 'all' && item.equipmentCategory !== equipmentCategoryFilter.value) return false;
    if (equipmentStatusFilter.value === 'available' && item.availableQuantity <= 0) return false;
    if (equipmentStatusFilter.value === 'unavailable' && item.availableQuantity > 0) return false;
    return [item.equipmentName, item.equipmentCategory, item.equipmentBrand].join(' ').toLowerCase().includes(query);
  });
});

const equipmentTotalPages = computed(() => Math.max(1, Math.ceil(filteredEquipmentRecords.value.length / equipmentPageSize)));
const paginatedEquipmentRecords = computed(() => {
  const startIndex = (equipmentPage.value - 1) * equipmentPageSize;
  return filteredEquipmentRecords.value.slice(startIndex, startIndex + equipmentPageSize);
});

watch([venueSearchQuery, venueFloorFilter, venueStatusFilter, venueSortValue], () => {
  venuePage.value = 1;
});

watch([equipmentSearchQuery, equipmentCategoryFilter, equipmentStatusFilter], () => {
  equipmentPage.value = 1;
});

watch(venueTotalPages, (nextValue) => {
  if (venuePage.value > nextValue) {
    venuePage.value = nextValue;
  }
});

watch(equipmentTotalPages, (nextValue) => {
  if (equipmentPage.value > nextValue) {
    equipmentPage.value = nextValue;
  }
});

function buildInitialEquipmentSelection() {
  return (reservationFormStore.selectedEquipmentItems || []).map((item) => ({ ...item }));
}

function handleVenueSelection(venue) {
  if (!venue.venueAvailable) return;
  selectedVenueRecord.value = selectedVenueRecord.value?.venueIdentifier === venue.venueIdentifier ? null : venue;
}

function getEquipmentSelectedQuantity(equipment) {
  return selectedEquipmentItems.value.find((item) => item.equipmentIdentifier === equipment.equipmentIdentifier)?.selectedQuantity || 0;
}

function setEquipmentQuantity(equipment, nextQuantity) {
  const maxAvailableQuantity = Math.max(Number(equipment.availableQuantity || 0), 0);
  const normalizedQuantity = Math.max(Number.isFinite(nextQuantity) ? Math.trunc(nextQuantity) : 0, 0);
  const existingItem = selectedEquipmentItems.value.find((item) => item.equipmentIdentifier === equipment.equipmentIdentifier);

  if (normalizedQuantity > maxAvailableQuantity) {
    equipmentQuantityErrors.value = {
      ...equipmentQuantityErrors.value,
      [equipment.equipmentIdentifier]: `Only ${maxAvailableQuantity} unit${maxAvailableQuantity === 1 ? '' : 's'} available.`,
    };
    return;
  }

  clearEquipmentQuantityError(equipment.equipmentIdentifier);

  if (normalizedQuantity <= 0) {
    selectedEquipmentItems.value = selectedEquipmentItems.value.filter((item) => item.equipmentIdentifier !== equipment.equipmentIdentifier);
    return;
  }

  if (!existingItem && selectedEquipmentItems.value.length >= MAX_EQUIPMENT_SELECTION_COUNT) {
    equipmentQuantityErrors.value = {
      ...equipmentQuantityErrors.value,
      [equipment.equipmentIdentifier]: `You can only select up to ${MAX_EQUIPMENT_SELECTION_COUNT} equipment items at a time.`,
    };
    return;
  }

  if (existingItem) {
    existingItem.availableQuantity = maxAvailableQuantity;
    existingItem.selectedQuantity = normalizedQuantity;
    return;
  }

  selectedEquipmentItems.value.push({
    equipmentIdentifier: equipment.equipmentIdentifier,
    equipmentName: equipment.equipmentName,
    equipmentCategory: equipment.equipmentCategory,
    availableQuantity: maxAvailableQuantity,
    selectedQuantity: normalizedQuantity,
  });
}

function handleEquipmentQuantityInput(equipment, event) {
  const rawValue = String(event?.target?.value || '');
  const digitsOnly = rawValue.replace(/\D/g, '');
  event.target.value = digitsOnly;
  setEquipmentQuantity(equipment, digitsOnly === '' ? 0 : Number(digitsOnly));
}

function handleEquipmentQuantityKeydown(event) {
  const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];
  if (allowedKeys.includes(event.key) || ((event.ctrlKey || event.metaKey) && ['a', 'c', 'v', 'x'].includes(event.key.toLowerCase()))) {
    return;
  }

  if (!/^\d$/.test(event.key)) {
    event.preventDefault();
  }
}

function handleEquipmentQuantityPaste(equipment, event) {
  event.preventDefault();
  const pastedText = String(event.clipboardData?.getData('text') || '');
  const digitsOnly = pastedText.replace(/\D/g, '');
  const inputElement = event.target;
  inputElement.value = digitsOnly;
  setEquipmentQuantity(equipment, digitsOnly === '' ? 0 : Number(digitsOnly));
}

function getEquipmentQuantityError(equipmentIdentifier) {
  return equipmentQuantityErrors.value[equipmentIdentifier] || '';
}

function hasEquipmentQuantityError(equipmentIdentifier) {
  return getEquipmentQuantityError(equipmentIdentifier) !== '';
}

function clearEquipmentQuantityError(equipmentIdentifier) {
  if (!equipmentQuantityErrors.value[equipmentIdentifier]) {
    return;
  }

  const nextErrors = { ...equipmentQuantityErrors.value };
  delete nextErrors[equipmentIdentifier];
  equipmentQuantityErrors.value = nextErrors;
}

function resetVenueFilters() {
  venueSearchQuery.value = '';
  venueFloorFilter.value = 'all';
  venueStatusFilter.value = 'all';
  venueSortValue.value = 'name-asc';
}

function resetEquipmentFilters() {
  equipmentSearchQuery.value = '';
  equipmentCategoryFilter.value = 'all';
  equipmentStatusFilter.value = 'all';
}

function navigateToPreviousPage() {
  router.push({ name: ROUTE_NAMES.borrowerCreateReservation });
}

function navigateToNextPage() {
  selectionWarning.value = '';

  if (showVenueSection.value && !selectedVenueRecord.value && reservationFormStore.reservationType !== 'Equipment') {
    selectionWarning.value = 'Select at least one venue before moving to the next step.';
    return;
  }

  if (showEquipmentSection.value && !selectedEquipmentItems.value.length && reservationFormStore.reservationType !== 'Venue') {
    selectionWarning.value = 'Select at least one equipment item before moving to the next step.';
    return;
  }

  const invalidEquipmentItem = selectedEquipmentItems.value.find((item) => {
    const matchingEquipment = equipmentRecords.value.find((equipment) => equipment.equipmentIdentifier === item.equipmentIdentifier);
    return !matchingEquipment || item.selectedQuantity < 1 || item.selectedQuantity > Number(matchingEquipment.availableQuantity || 0);
  });

  if (invalidEquipmentItem) {
    const matchingEquipment = equipmentRecords.value.find((equipment) => equipment.equipmentIdentifier === invalidEquipmentItem.equipmentIdentifier);
    if (matchingEquipment) {
      equipmentQuantityErrors.value = {
        ...equipmentQuantityErrors.value,
        [matchingEquipment.equipmentIdentifier]: `Enter a quantity from 1 to ${matchingEquipment.availableQuantity}.`,
      };
    }
    selectionWarning.value = 'Fix invalid equipment quantities before moving to the next step.';
    return;
  }

  reservationFormStore.selectedVenueName = selectedVenueRecord.value?.venueName || null;
  reservationFormStore.selectedVenueRecord = selectedVenueRecord.value;
  reservationFormStore.selectedEquipmentItems = selectedEquipmentItems.value.filter((item) => item.selectedQuantity > 0);
  reservationFormStore.persistForm();
  isStepLoading.value = true;
  window.setTimeout(() => {
    router.push({ name: 'borrowerCreateReservationAdditionalPage' });
  }, 250);
}

function inferVenueType(venue) {
  const normalizedName = String(venue?.venueName || '').toLowerCase();
  if (normalizedName.includes('gym')) return 'Sports Facility';
  if (normalizedName.includes('audio visual')) return 'Audio Visual Room';
  if (normalizedName.includes('room')) return 'Venue';
  return 'Venue';
}
</script>
