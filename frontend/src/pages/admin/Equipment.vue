<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="equipment-page">
      <header class="equipment-page__header">
        <div>
          <p class="equipment-page__eyebrow">Admin Equipment Management</p>
          <h1 class="equipment-page__title">Equipment Lifecycle</h1>
          <p class="equipment-page__subtitle">
            Add, review, update, and delete equipment records without exposing admin actions to requestors.
          </p>
        </div>
        <button class="equipment-page__primary-button" type="button" @click="openCreateModal">
          Add Equipment
        </button>
      </header>

      <section class="equipment-page__controls">
        <label class="equipment-page__search">
          <span class="equipment-page__label">Search</span>
          <input
            v-model.trim="searchQuery"
            type="text"
            placeholder="Search by equipment name or category"
          />
        </label>

        <label class="equipment-page__filter">
          <span class="equipment-page__label">Category</span>
          <input
            v-model.trim="categoryFilter"
            type="text"
            placeholder="e.g. Audio Visual"
          />
        </label>

        <label class="equipment-page__filter">
          <span class="equipment-page__label">Status</span>
          <select v-model="statusFilter">
            <option value="all">All</option>
            <option value="Available">Available</option>
            <option value="Unavailable">Unavailable</option>
            <option value="Under Maintenance">Under Maintenance</option>
            <option value="Retired">Retired</option>
          </select>
        </label>

        <label class="equipment-page__filter">
          <span class="equipment-page__label">Condition</span>
          <input
            v-model.trim="conditionFilter"
            type="text"
            placeholder="e.g. Good"
          />
        </label>

        <label class="equipment-page__filter">
          <span class="equipment-page__label">Storage Location</span>
          <input
            v-model.trim="storageLocationFilter"
            type="text"
            placeholder="e.g. AV Room"
          />
        </label>

        <label class="equipment-page__filter">
          <span class="equipment-page__label">Acquired Range</span>
          <select v-model="acquiredDatePreset">
            <option value="all">All dates</option>
            <option value="today">Today</option>
            <option value="last-7-days">Last 7 days</option>
            <option value="last-30-days">Last 30 days</option>
            <option value="this-year">This year</option>
            <option value="custom">Custom range</option>
          </select>
        </label>

        <label v-if="acquiredDatePreset === 'custom'" class="equipment-page__filter">
          <span class="equipment-page__label">Acquired From</span>
          <input v-model="acquiredStartDate" type="date" />
        </label>

        <label v-if="acquiredDatePreset === 'custom'" class="equipment-page__filter">
          <span class="equipment-page__label">Acquired To</span>
          <input v-model="acquiredEndDate" type="date" />
        </label>

        <label class="equipment-page__filter">
          <span class="equipment-page__label">Sort</span>
          <select v-model="sortOrder">
            <option value="asc">Name (A-Z)</option>
            <option value="desc">Name (Z-A)</option>
            <option value="recent">Recently Updated</option>
          </select>
        </label>

        <button
          class="equipment-page__ghost-button"
          type="button"
          :disabled="isLoading"
          @click="fetchEquipment"
        >
          {{ isLoading ? 'Refreshing...' : 'Refresh' }}
        </button>
        <button class="equipment-page__ghost-button" type="button" @click="exportInventory('csv')">CSV</button>
        <button
          class="equipment-page__ghost-button"
          type="button"
          :disabled="isExportingExcel"
          @click="exportInventory('excel')"
        >
          {{ isExportingExcel ? 'Exporting...' : 'Excel' }}
        </button>
        <button class="equipment-page__ghost-button" type="button" @click="exportInventory('pdf')">PDF</button>
        <button class="equipment-page__ghost-button" type="button" @click="exportInventory('print')">Print</button>
      </section>

      <section class="equipment-page__summary">
        <article class="equipment-page__summary-card">
          <span>Total Records</span>
          <strong>{{ equipmentList.length }}</strong>
        </article>
        <article class="equipment-page__summary-card">
          <span>Available</span>
          <strong>{{ availableCount }}</strong>
        </article>
        <article class="equipment-page__summary-card">
          <span>Under Maintenance</span>
          <strong>{{ maintenanceCount }}</strong>
        </article>
        <article class="equipment-page__summary-card">
          <span>Retired</span>
          <strong>{{ retiredCount }}</strong>
        </article>
      </section>

      <p v-if="pageError" class="equipment-page__feedback equipment-page__feedback--error">{{ pageError }}</p>
      <p v-if="exportError" class="equipment-page__feedback equipment-page__feedback--error">{{ exportError }}</p>

      <div v-if="isLoading" class="equipment-page__state-card">Loading equipment records...</div>
      <div v-else-if="filteredEquipment.length === 0" class="equipment-page__state-card">
        No equipment records match the current search and filter.
      </div>
      <div v-else ref="inventorySurfaceRef" class="equipment-page__table-wrap">
        <table class="equipment-page__table">
          <thead>
            <tr>
              <th>Equipment ID</th>
              <th>Name</th>
              <th>Category</th>
              <th>Brand</th>
              <th>Model</th>
              <th>Total</th>
              <th>Available</th>
              <th>Reserved</th>
              <th>Maintenance</th>
              <th>Unavailable</th>
              <th>Inventory Health</th>
              <th>Updated</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="equipment in paginatedEquipment" :key="equipment.equipmentIdentifier">
              <td>{{ equipment.equipmentIdentifier }}</td>
              <td>{{ equipment.equipmentName }}</td>
              <td>{{ equipment.equipmentCategory || equipment.categoryName }}</td>
              <td>{{ equipment.equipmentBrand || 'N/A' }}</td>
              <td>{{ equipment.equipmentModel || 'N/A' }}</td>
              <td>{{ equipment.totalQuantity }}</td>
              <td>{{ equipment.availableQuantity }}</td>
              <td>{{ equipment.reservedQuantity || 0 }}</td>
              <td>{{ equipment.underMaintenanceQuantity || 0 }}</td>
              <td>{{ equipment.unavailableQuantity || 0 }}</td>
              <td>
                <span
                  class="equipment-page__status-badge"
                  :class="inventoryHealthBadgeClass(equipment)"
                >
                  {{ inventoryHealthLabel(equipment) }}
                </span>
              </td>
              <td>{{ formatDateTime(equipment.updatedTimestamp || equipment.createdTimestamp) }}</td>
              <td>
                <div class="equipment-page__actions">
                  <button type="button" @click="openViewModal(equipment)">View</button>
                  <button type="button" @click="openEditModal(equipment)">Update</button>
                  <button
                    type="button"
                    class="equipment-page__danger-action"
                    @click="openDeleteModal(equipment)"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="equipment-page__pagination">
          <button type="button" :disabled="equipmentCurrentPage === 1" @click="equipmentCurrentPage -= 1">Previous</button>
          <span>Showing {{ equipmentPageStart }}-{{ equipmentPageEnd }} of {{ filteredEquipment.length }}</span>
          <button type="button" :disabled="equipmentCurrentPage === equipmentTotalPages" @click="equipmentCurrentPage += 1">Next</button>
        </div>
      </div>

      <div v-if="viewEquipment" class="equipment-modal__overlay" @click.self="closeViewModal">
        <section class="equipment-modal">
          <header class="equipment-modal__header">
            <div>
              <p class="equipment-modal__eyebrow">Equipment Details</p>
              <h2>View Equipment</h2>
            </div>
            <button type="button" class="equipment-modal__close" @click="closeViewModal">X</button>
          </header>

          <p
            v-if="isInventoryIncomplete(viewEquipment)"
            class="equipment-page__feedback equipment-page__feedback--warning"
          >
            This inventory record needs review. Some required model, specification, or unit details are still missing.
          </p>

          <dl class="equipment-modal__details">
            <div><dt>Equipment ID</dt><dd>{{ viewEquipment.equipmentIdentifier }}</dd></div>
            <div><dt>Name</dt><dd>{{ viewEquipment.equipmentName }}</dd></div>
            <div><dt>Category</dt><dd>{{ viewEquipment.equipmentCategory || viewEquipment.categoryName }}</dd></div>
            <div><dt>Brand</dt><dd>{{ viewEquipment.equipmentBrand || 'N/A' }}</dd></div>
            <div><dt>Model</dt><dd>{{ viewEquipment.equipmentModel || 'N/A' }}</dd></div>
            <div><dt>Available Quantity</dt><dd>{{ viewEquipment.availableQuantity }}</dd></div>
            <div><dt>Reserved Quantity</dt><dd>{{ viewEquipment.reservedQuantity || 0 }}</dd></div>
            <div><dt>Unavailable Quantity</dt><dd>{{ viewEquipment.unavailableQuantity || 0 }}</dd></div>
            <div><dt>Status</dt><dd>{{ viewEquipment.operationalStatus || viewEquipment.equipmentState }}</dd></div>
            <div><dt>Operational Status</dt><dd>{{ viewEquipment.operationalStatus }}</dd></div>
            <div><dt>QR Code</dt><dd>{{ viewEquipment.barcode || 'N/A' }}</dd></div>
            <div><dt>Asset ID</dt><dd>{{ viewEquipment.assetId || 'N/A' }}</dd></div>
            <div><dt>Description</dt><dd>{{ viewEquipment.description || viewEquipment.scheduleDescription || 'N/A' }}</dd></div>
            <div><dt>Remarks</dt><dd>{{ viewEquipment.remarks || 'No remarks provided' }}</dd></div>
            <div><dt>Created</dt><dd>{{ formatDateTime(viewEquipment.createdTimestamp) }}</dd></div>
            <div><dt>Updated</dt><dd>{{ formatDateTime(viewEquipment.updatedTimestamp || viewEquipment.createdTimestamp) }}</dd></div>
          </dl>
          <div class="equipment-modal__specs" v-if="Array.isArray(viewEquipment.specifications) && viewEquipment.specifications.length > 0">
            <p class="equipment-modal__specs-title">Specifications</p>
            <div class="equipment-modal__specs-list">
              <div v-for="(specification, index) in viewEquipment.specifications" :key="`${specification.key}-${index}`">
                <strong>{{ specification.key || 'Specification' }}</strong>
                <span>{{ specification.value || 'N/A' }}</span>
              </div>
            </div>
          </div>
          <div class="equipment-modal__unit-table-wrap" v-if="Array.isArray(viewEquipment.units) && viewEquipment.units.length > 0">
            <table class="equipment-page__table">
              <thead>
                <tr>
                  <th>Unit ID</th>
                  <th>Barcode</th>
                  <th>Asset Tag</th>
                  <th>Serial</th>
                  <th>Condition</th>
                  <th>Availability</th>
                  <th>Location</th>
                  <th>Remarks</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="unit in viewEquipment.units" :key="unit.equipmentUnitIdentifier || unit.equipmentUnitIdentifierCode">
                  <td>{{ unit.equipmentUnitIdentifierCode }}</td>
                  <td>{{ unit.barcode || 'N/A' }}</td>
                  <td>{{ unit.assetTag || 'N/A' }}</td>
                  <td>{{ unit.serialNumber || 'N/A' }}</td>
                  <td>{{ unit.conditionStatus || 'Good' }}</td>
                  <td>{{ unit.availabilityStatus || 'Available' }}</td>
                  <td>{{ unit.storageLocation || 'N/A' }}</td>
                  <td>{{ unit.remarks || 'N/A' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <footer class="equipment-modal__footer">
            <button type="button" class="equipment-modal__secondary" @click="closeViewModal">Close</button>
          </footer>
        </section>
      </div>

      <div v-if="formModalOpen" class="equipment-modal__overlay" @click.self="closeFormModal">
        <section class="equipment-modal equipment-modal--wide">
          <header class="equipment-modal__header">
            <div>
              <p class="equipment-modal__eyebrow">Equipment Record</p>
              <h2>{{ formMode === 'create' ? 'Add Equipment' : 'Update Equipment' }}</h2>
            </div>
            <button type="button" class="equipment-modal__close" :disabled="isSaving" @click="closeFormModal">X</button>
          </header>

          <div class="equipment-modal__grid">
            <label>
              <span>Equipment Name</span>
              <input v-model.trim="form.equipmentName" type="text" maxlength="150" />
            </label>
            <label>
              <span>Category</span>
              <input v-model.trim="form.equipmentCategory" type="text" maxlength="120" />
            </label>
            <label>
              <span>Brand</span>
              <input v-model.trim="form.equipmentBrand" type="text" maxlength="120" />
            </label>
            <label>
              <span>Model</span>
              <input v-model.trim="form.equipmentModel" type="text" maxlength="160" />
            </label>
            <label>
              <span>Available Quantity</span>
              <input v-model.number="form.availableQuantity" type="number" min="1" />
            </label>
            <label>
              <span>Operational Status</span>
              <select v-model="form.operationalStatus">
                <option v-for="status in equipmentStatuses" :key="status" :value="status">{{ status }}</option>
              </select>
            </label>
            <label>
              <span>QR Code</span>
              <input v-model.trim="form.barcode" type="text" maxlength="120" />
            </label>
            <label>
              <span>Asset ID</span>
              <input v-model.trim="form.assetId" type="text" maxlength="13" placeholder="F123-456-789" />
            </label>
            <label class="equipment-modal__full-width">
              <span>Description</span>
              <textarea
                v-model.trim="form.description"
                rows="4"
                placeholder="Optional usage notes or description"
              />
            </label>
            <label class="equipment-modal__full-width">
              <span>Admin Remarks</span>
              <textarea
                v-model.trim="form.remarks"
                rows="3"
                placeholder="Inventory handling notes, maintenance context, or storage reminders"
              />
            </label>
          </div>
          <div class="equipment-modal__full-width equipment-modal__spec-editor">
            <div class="equipment-page__actions" style="justify-content: space-between; margin-bottom: 0.75rem;">
              <strong>Structured Specifications</strong>
              <button type="button" @click="addSpecificationRow">Add Specification</button>
            </div>
            <table class="equipment-page__table">
              <thead>
                <tr>
                  <th>Specification</th>
                  <th>Value</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(specification, index) in form.specifications" :key="`specification-${index}`">
                  <td><input v-model.trim="specification.key" type="text" placeholder="e.g. Connector Type" /></td>
                  <td><input v-model.trim="specification.value" type="text" placeholder="e.g. XLR" /></td>
                  <td><button type="button" class="equipment-page__danger-action" @click="removeSpecificationRow(index)">Remove</button></td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="equipment-modal__full-width equipment-modal__unit-table-wrap">
            <div class="equipment-page__actions" style="justify-content: space-between; margin-bottom: 0.75rem;">
              <strong>Equipment Units</strong>
              <button type="button" @click="addUnitRow">Add Unit</button>
            </div>
            <table class="equipment-page__table">
              <thead>
                <tr>
                  <th>Unit ID</th>
                  <th>Barcode</th>
                  <th>Asset Tag</th>
                  <th>Serial</th>
                  <th>Condition</th>
                  <th>Availability</th>
                  <th>Location</th>
                  <th>Remarks</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(unit, index) in form.units" :key="`${unit.equipmentUnitIdentifierCode}-${index}`">
                  <td><input v-model.trim="unit.equipmentUnitIdentifierCode" type="text" /></td>
                  <td><input v-model.trim="unit.barcode" type="text" /></td>
                  <td><input v-model.trim="unit.assetTag" type="text" /></td>
                  <td><input v-model.trim="unit.serialNumber" type="text" /></td>
                  <td><input v-model.trim="unit.conditionStatus" type="text" /></td>
                  <td><input v-model.trim="unit.availabilityStatus" type="text" /></td>
                  <td><input v-model.trim="unit.storageLocation" type="text" /></td>
                  <td><input v-model.trim="unit.remarks" type="text" /></td>
                  <td><button type="button" class="equipment-page__danger-action" @click="removeUnitRow(index)">Remove</button></td>
                </tr>
              </tbody>
            </table>
          </div>

          <p v-if="formError" class="equipment-page__feedback equipment-page__feedback--error">{{ formError }}</p>

          <footer class="equipment-modal__footer">
            <button type="button" class="equipment-modal__secondary" :disabled="isSaving" @click="closeFormModal">
              Cancel
            </button>
            <button
              type="button"
              class="equipment-modal__primary"
              :disabled="isSaving"
              @click="submitForm"
            >
              {{ isSaving ? (formMode === 'create' ? 'Creating...' : 'Saving...') : (formMode === 'create' ? 'Create Equipment' : 'Save Changes') }}
            </button>
          </footer>
        </section>
      </div>

      <div v-if="deleteEquipmentRecord" class="equipment-modal__overlay" @click.self="closeDeleteModal">
        <section class="equipment-modal">
          <header class="equipment-modal__header">
            <div>
              <p class="equipment-modal__eyebrow">Permanent Action</p>
              <h2>Delete Equipment</h2>
            </div>
            <button type="button" class="equipment-modal__close" :disabled="isDeleting" @click="closeDeleteModal">X</button>
          </header>

          <div class="equipment-modal__danger-summary">
            <p><strong>ID</strong><span>{{ deleteEquipmentRecord.equipmentIdentifier }}</span></p>
            <p><strong>Name</strong><span>{{ deleteEquipmentRecord.equipmentName }}</span></p>
            <p><strong>Category</strong><span>{{ deleteEquipmentRecord.equipmentCategory || deleteEquipmentRecord.categoryName }}</span></p>
            <p><strong>Status</strong><span>{{ deleteEquipmentRecord.operationalStatus || deleteEquipmentRecord.equipmentState }}</span></p>
          </div>

          <p v-if="deleteError" class="equipment-page__feedback equipment-page__feedback--error">{{ deleteError }}</p>

          <footer class="equipment-modal__footer">
            <button type="button" class="equipment-modal__secondary" :disabled="isDeleting" @click="closeDeleteModal">
              Cancel
            </button>
            <button type="button" class="equipment-modal__danger" :disabled="isDeleting" @click="confirmDelete">
              {{ isDeleting ? 'Deleting...' : 'Delete Equipment' }}
            </button>
          </footer>
        </section>
      </div>
      <DataRequestStatusFloater :items="equipmentStatusItems" />
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import DataRequestStatusFloater from '@/shared/components/DataRequestStatusFloater.vue';
import '@/shared/components/adminSidebarLayout.css';
import equipmentApi from '@/modules/reservation/services/equipmentApi.js';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import {
  normalizeEquipmentForm,
  validateEquipmentForm,
} from '@/modules/facility/utils/equipmentFormValidation.js';
import {
  exportElementToPdf,
  exportRowsToCsv,
  printElement,
} from '@/shared/utils/adminExport.js';

const EQUIPMENT_PAGE_CACHE_KEY = 'techreserve_equipment_page_cache';
const equipmentStatuses = ['Available', 'Unavailable', 'Under Maintenance', 'Retired'];

const equipmentList = ref(readEquipmentCache());
const isLoading = ref(false);
const isExportingExcel = ref(false);
const pageError = ref('');
const exportError = ref('');
const equipmentDataState = ref(equipmentList.value.length > 0 ? 'cached' : 'idle');
const searchQuery = ref('');
const categoryFilter = ref('');
const statusFilter = ref('all');
const conditionFilter = ref('');
const storageLocationFilter = ref('');
const acquiredDatePreset = ref('all');
const acquiredStartDate = ref('');
const acquiredEndDate = ref('');
const sortOrder = ref('asc');
const equipmentCurrentPage = ref(1);
const equipmentPageSize = 10;
const inventorySurfaceRef = ref(null);
let equipmentFetchTimer = null;

const viewEquipment = ref(null);
const formModalOpen = ref(false);
const formMode = ref('create');
const editingEquipmentIdentifier = ref(null);
const isSaving = ref(false);
const formError = ref('');
const deleteEquipmentRecord = ref(null);
const isDeleting = ref(false);
const deleteError = ref('');

const form = ref(createEmptyForm());
const equipmentStatusItems = computed(() => [
  {
    key: 'equipment',
    label: 'Equipment Records',
    state: equipmentDataState.value,
  },
]);

const filteredEquipment = computed(() => {
  return [...equipmentList.value].sort((left, right) => {
    if (sortOrder.value === 'recent') {
      return new Date(right.updatedTimestamp || right.createdTimestamp).getTime()
        - new Date(left.updatedTimestamp || left.createdTimestamp).getTime();
    }

    const comparison = left.equipmentName.localeCompare(right.equipmentName);
    return sortOrder.value === 'asc' ? comparison : comparison * -1;
  });
});
const equipmentTotalPages = computed(() => Math.max(1, Math.ceil(filteredEquipment.value.length / equipmentPageSize)));
const paginatedEquipment = computed(() => {
  const startIndex = (equipmentCurrentPage.value - 1) * equipmentPageSize;
  return filteredEquipment.value.slice(startIndex, startIndex + equipmentPageSize);
});
const equipmentPageStart = computed(() => (
  filteredEquipment.value.length === 0 ? 0 : ((equipmentCurrentPage.value - 1) * equipmentPageSize) + 1
));
const equipmentPageEnd = computed(() => Math.min(equipmentCurrentPage.value * equipmentPageSize, filteredEquipment.value.length));

const availableCount = computed(() =>
  equipmentList.value.filter((equipment) => equipment.equipmentState === 'Available').length
);
const maintenanceCount = computed(() =>
  equipmentList.value.filter((equipment) => equipment.equipmentState === 'Under Maintenance').length
);
const retiredCount = computed(() =>
  equipmentList.value.filter((equipment) => equipment.equipmentState === 'Retired').length
);

const isFormReady = computed(() => {
  return validateEquipmentForm(form.value) === '';
});

onMounted(() => {
  fetchEquipment();
});

watch([searchQuery, categoryFilter, statusFilter, conditionFilter, storageLocationFilter, acquiredDatePreset, acquiredStartDate, acquiredEndDate], () => {
  equipmentCurrentPage.value = 1;
  exportError.value = '';
  scheduleEquipmentFetch();
});

watch(sortOrder, () => {
  equipmentCurrentPage.value = 1;
});

watch(() => form.value.availableQuantity, (nextQuantity) => {
  if (!formModalOpen.value) {
    return;
  }

  const normalizedQuantity = Math.max(1, Number.parseInt(nextQuantity, 10) || 1);
  if (normalizedQuantity !== form.value.availableQuantity) {
    form.value.availableQuantity = normalizedQuantity;
    return;
  }

  const currentUnits = Array.isArray(form.value.units) ? [...form.value.units] : [];
  if (currentUnits.length === normalizedQuantity) {
    return;
  }

  if (currentUnits.length < normalizedQuantity) {
    const nextUnits = buildUnitRowsFromQuantity(normalizedQuantity);
    form.value.units = nextUnits.map((unit, index) => currentUnits[index] ? { ...unit, ...currentUnits[index] } : unit);
    return;
  }

  form.value.units = currentUnits.slice(0, normalizedQuantity);
}, { flush: 'sync' });

watch(equipmentTotalPages, (pageCount) => {
  if (equipmentCurrentPage.value > pageCount) {
    equipmentCurrentPage.value = pageCount;
  }
});

function scheduleEquipmentFetch() {
  if (equipmentFetchTimer !== null && typeof window !== 'undefined') {
    window.clearTimeout(equipmentFetchTimer);
  }

  if (typeof window === 'undefined') {
    fetchEquipment();
    return;
  }

  equipmentFetchTimer = window.setTimeout(() => {
    equipmentFetchTimer = null;
    fetchEquipment();
  }, 250);
}

async function fetchEquipment() {
  try {
    isLoading.value = true;
    pageError.value = '';
    equipmentDataState.value = equipmentList.value.length > 0 ? 'cached-loading' : 'loading';
    const response = await equipmentApi.listEquipment(buildEquipmentFilters());
    equipmentList.value = response?.data?.equipment || [];
    writeEquipmentCache(equipmentList.value);
    equipmentDataState.value = 'fresh';
  } catch (error) {
    pageError.value = error?.response?.data?.errorMessage || 'Failed to load equipment records.';
    equipmentDataState.value = equipmentList.value.length > 0 ? 'cached' : 'error';
  } finally {
    isLoading.value = false;
  }
}

function readEquipmentCache() {
  if (typeof window === 'undefined') return [];

  try {
    const cachedValue = window.sessionStorage.getItem(EQUIPMENT_PAGE_CACHE_KEY);
    const parsedValue = cachedValue ? JSON.parse(cachedValue) : [];
    return Array.isArray(parsedValue) ? parsedValue : [];
  } catch {
    return [];
  }
}

function writeEquipmentCache(records) {
  if (typeof window === 'undefined') return;

  try {
    window.sessionStorage.setItem(EQUIPMENT_PAGE_CACHE_KEY, JSON.stringify(Array.isArray(records) ? records : []));
  } catch {
    // Best-effort cache only.
  }
}

function openCreateModal() {
  formMode.value = 'create';
  editingEquipmentIdentifier.value = null;
  form.value = createEmptyForm();
  formError.value = '';
  formModalOpen.value = true;
}

function openEditModal(equipment) {
  formMode.value = 'edit';
  editingEquipmentIdentifier.value = equipment.equipmentIdentifier;
  form.value = {
    equipmentName: equipment.equipmentName,
    equipmentCategory: equipment.equipmentCategory || equipment.categoryName,
    equipmentBrand: equipment.equipmentBrand || '',
    equipmentModel: equipment.equipmentModel || '',
    availableQuantity: equipment.availableQuantity,
    operationalStatus: equipment.operationalStatus || equipment.equipmentState,
    description: equipment.description || equipment.scheduleDescription || '',
    remarks: equipment.remarks || '',
    barcode: equipment.barcode || '',
    assetId: equipment.assetId || '',
    specifications: Array.isArray(equipment.specifications) && equipment.specifications.length > 0
      ? equipment.specifications.map((specification) => ({ ...specification }))
      : [createEmptySpecification()],
    units: Array.isArray(equipment.units) && equipment.units.length > 0
      ? equipment.units.map((unit) => ({ ...unit }))
      : buildUnitRowsFromQuantity(equipment.availableQuantity, equipment.barcode, equipment.assetId),
  };
  formError.value = '';
  formModalOpen.value = true;
}

function openViewModal(equipment) {
  viewEquipment.value = equipment;
}

function closeViewModal() {
  viewEquipment.value = null;
}

function closeFormModal() {
  if (isSaving.value) return;
  formModalOpen.value = false;
  editingEquipmentIdentifier.value = null;
  form.value = createEmptyForm();
  formError.value = '';
}

async function submitForm() {
  if (isSaving.value) {
    return;
  }

  const validationMessage = validateEquipmentForm(form.value);
  if (validationMessage) {
    formError.value = validationMessage;
    return;
  }

  try {
    isSaving.value = true;
    formError.value = '';

    const payload = normalizeEquipmentForm(form.value);
    payload.barcode = payload.barcode || payload.units[0]?.barcode || '';
    payload.assetId = payload.assetId || payload.units[0]?.assetTag || payload.units[0]?.serialNumber || '';

    if (formMode.value === 'create') {
      await equipmentApi.createEquipment(payload);
    } else {
      await equipmentApi.updateEquipment(editingEquipmentIdentifier.value, payload);
    }

    closeFormModal();
    await fetchEquipment();
  } catch (error) {
    formError.value = error?.response?.data?.errorMessage || 'Unable to save equipment right now.';
  } finally {
    isSaving.value = false;
  }
}

function openDeleteModal(equipment) {
  deleteEquipmentRecord.value = equipment;
  deleteError.value = '';
}

function closeDeleteModal() {
  if (isDeleting.value) return;
  deleteEquipmentRecord.value = null;
  deleteError.value = '';
}

async function confirmDelete() {
  if (!deleteEquipmentRecord.value || isDeleting.value) {
    return;
  }

  try {
    isDeleting.value = true;
    deleteError.value = '';
    await equipmentApi.deleteEquipment(deleteEquipmentRecord.value.equipmentIdentifier);
    closeDeleteModal();
    await fetchEquipment();
  } catch (error) {
    deleteError.value = error?.response?.data?.errorMessage || 'Unable to delete the selected equipment.';
  } finally {
    isDeleting.value = false;
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
    hour: 'numeric',
    minute: '2-digit',
  }).format(parsedDate);
}

function statusBadgeClass(status) {
  return {
    'equipment-page__status-badge--available': status === 'Available',
    'equipment-page__status-badge--unavailable': status === 'Unavailable',
    'equipment-page__status-badge--maintenance': status === 'Under Maintenance',
    'equipment-page__status-badge--retired': status === 'Retired',
  };
}

function inventoryHealthLabel(equipment) {
  return isInventoryIncomplete(equipment) ? 'Needs Review' : 'Complete';
}

function inventoryHealthBadgeClass(equipment) {
  return isInventoryIncomplete(equipment)
    ? 'equipment-page__status-badge--maintenance'
    : 'equipment-page__status-badge--available';
}

function isInventoryIncomplete(equipment) {
  if (!equipment) {
    return true;
  }

  if (!String(equipment.equipmentBrand || '').trim() || !String(equipment.equipmentModel || '').trim()) {
    return true;
  }

  if (!Array.isArray(equipment.specifications) || equipment.specifications.length === 0) {
    return true;
  }

  const units = Array.isArray(equipment.units) ? equipment.units : [];
  if (units.length === 0 || units.length !== Number(equipment.totalQuantity || 0)) {
    return true;
  }

  return units.some((unit) => (
    !String(unit?.equipmentUnitIdentifierCode || '').trim()
    || !String(unit?.barcode || '').trim()
    || !String(unit?.assetTag || '').trim()
    || !String(unit?.conditionStatus || '').trim()
    || !String(unit?.storageLocation || '').trim()
  ));
}

function createEmptyForm() {
  return {
    equipmentName: '',
    equipmentCategory: '',
    equipmentBrand: '',
    equipmentModel: '',
    availableQuantity: 1,
    operationalStatus: 'Available',
    description: '',
    remarks: '',
    barcode: '',
    assetId: '',
    specifications: [createEmptySpecification()],
    units: buildUnitRowsFromQuantity(1),
  };
}

function addUnitRow() {
  form.value.units.push({
    equipmentUnitIdentifierCode: `UNIT-${form.value.units.length + 1}`,
    barcode: '',
    assetTag: '',
    serialNumber: '',
    conditionStatus: 'Good',
    availabilityStatus: 'Available',
    storageLocation: '',
  });
  form.value.availableQuantity = form.value.units.length;
}

function addSpecificationRow() {
  form.value.specifications.push(createEmptySpecification());
}

function removeSpecificationRow(index) {
  form.value.specifications.splice(index, 1);
  if (form.value.specifications.length === 0) {
    form.value.specifications = [createEmptySpecification()];
  }
}

function removeUnitRow(index) {
  form.value.units.splice(index, 1);
  form.value.availableQuantity = Math.max(1, form.value.units.length || 1);
  if (form.value.units.length === 0) {
    form.value.units = buildUnitRowsFromQuantity(1);
    form.value.availableQuantity = 1;
  }
}

function buildEquipmentFilters() {
  return {
    search: searchQuery.value,
    category: categoryFilter.value,
    status: normalizeFilterValue(statusFilter.value),
    condition: conditionFilter.value,
    storageLocation: storageLocationFilter.value,
    acquiredStartDate: acquiredDatePreset.value === 'custom' ? acquiredStartDate.value : '',
    acquiredEndDate: acquiredDatePreset.value === 'custom' ? acquiredEndDate.value : '',
    datePreset: normalizeFilterValue(acquiredDatePreset.value),
  };
}

function normalizeFilterValue(value) {
  return value === 'all' ? '' : value;
}

function buildUnitRowsFromQuantity(quantity, barcode = '', assetId = '') {
  return Array.from({ length: Math.max(1, Number(quantity || 1)) }, (_, index) => ({
    equipmentUnitIdentifierCode: `UNIT-${index + 1}`,
    barcode: index === 0 ? barcode : '',
    assetTag: index === 0 ? assetId : '',
    serialNumber: index === 0 ? assetId : '',
    conditionStatus: 'Good',
    availabilityStatus: 'Available',
    storageLocation: '',
    remarks: '',
  }));
}

function createEmptySpecification() {
  return {
    key: '',
    value: '',
  };
}

async function exportInventory(format) {
  exportError.value = '';

  const rows = filteredEquipment.value.map((equipment) => ({
    equipmentId: equipment.equipmentIdentifier,
    name: equipment.equipmentName,
    category: equipment.equipmentCategory || equipment.categoryName,
    brand: equipment.equipmentBrand || '',
    model: equipment.equipmentModel || '',
    totalQuantity: equipment.totalQuantity,
    availableQuantity: equipment.availableQuantity,
    reservedQuantity: equipment.reservedQuantity || 0,
    borrowedQuantity: equipment.borrowedQuantity || 0,
    underMaintenanceQuantity: equipment.underMaintenanceQuantity || 0,
    unavailableQuantity: equipment.unavailableQuantity || 0,
    status: equipment.operationalStatus || equipment.equipmentState,
    remarks: equipment.remarks || '',
    units: Array.isArray(equipment.units) ? equipment.units.length : 0,
  }));

  if (format === 'csv') {
    exportRowsToCsv('techreserve-inventory', rows);
    return;
  }

  if (format === 'excel') {
    if (filteredEquipment.value.length === 0) {
      exportError.value = 'No equipment records match the current filters.';
      return;
    }

    try {
      isExportingExcel.value = true;
      const response = await equipmentApi.exportEquipmentExcel(buildEquipmentFilters());
      downloadBlobResponse(response, `equipment_inventory_${new Date().toISOString().slice(0, 10)}.xlsx`);
    } catch (error) {
      exportError.value = error?.response?.data?.errorMessage || 'Unable to export equipment inventory to Excel right now.';
    } finally {
      isExportingExcel.value = false;
    }
    return;
  }

  if (format === 'pdf') {
    await exportElementToPdf('techreserve-inventory', inventorySurfaceRef.value);
    return;
  }

  printElement(inventorySurfaceRef.value, 'TechReserve Inventory Export');
}

function downloadBlobResponse(response, fallbackFileName) {
  if (typeof window === 'undefined') {
    return;
  }

  const blob = response?.data instanceof Blob
    ? response.data
    : new Blob([response?.data], { type: response?.headers?.['content-type'] || 'application/octet-stream' });
  const fileName = extractFileNameFromDisposition(response?.headers?.['content-disposition']) || fallbackFileName;
  const downloadUrl = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = downloadUrl;
  link.download = fileName;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  window.URL.revokeObjectURL(downloadUrl);
}

function extractFileNameFromDisposition(contentDisposition) {
  if (!contentDisposition) {
    return '';
  }

  const match = String(contentDisposition).match(/filename="([^"]+)"/i);
  return match?.[1] || '';
}
</script>

<style scoped>
@import './css/Equipment.css';
</style>

