<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="equipment-page">
      <header class="equipment-page__header">
        <div>
          <p class="equipment-page__eyebrow">Admin Equipment Management</p>
          <h1 class="equipment-page__title">Equipment Inventory Register</h1>
          <p class="equipment-page__subtitle">
            Maintain a clear equipment inventory with exact quantities, specific models, tracked barcodes, asset tags, serial details, and structured item specifications.
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
            placeholder="Search by name, model, brand, barcode, asset tag, or serial"
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
        <button
          class="equipment-page__ghost-button"
          type="button"
          :disabled="isDetailedReportsLoading"
          @click="openDetailedReportsModal"
        >
          {{ isDetailedReportsLoading ? 'Preparing Reports...' : 'Detailed XLSX Reports' }}
        </button>
        <button class="equipment-page__ghost-button" type="button" @click="exportInventory('pdf')">PDF</button>
        <button class="equipment-page__ghost-button" type="button" @click="exportInventory('print')">Print</button>
      </section>

      <section class="equipment-page__summary">
        <article class="equipment-page__summary-card">
          <span>Inventory Records</span>
          <strong>{{ equipmentList.length }}</strong>
        </article>
        <article class="equipment-page__summary-card">
          <span>Available Units</span>
          <strong>{{ availableCount }}</strong>
        </article>
        <article class="equipment-page__summary-card">
          <span>Maintenance Units</span>
          <strong>{{ maintenanceCount }}</strong>
        </article>
        <article class="equipment-page__summary-card">
          <span>Incomplete Records</span>
          <strong>{{ incompleteInventoryCount }}</strong>
        </article>
      </section>

      <article class="equipment-page__inventory-note">
        <strong>Inventory standard</strong>
        <p>
          Each equipment record should clearly show the quantity breakdown, brand, model, structured specifications,
          and tracked unit identifiers such as barcode, asset tag, serial number, condition, and storage location.
        </p>
      </article>

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
              <th>Record Completeness</th>
              <th>Last Updated</th>
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
              <h2>View Inventory Record</h2>
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
            <div><dt>Total Quantity</dt><dd>{{ viewEquipment.totalQuantity || viewEquipment.availableQuantity || 0 }}</dd></div>
            <div><dt>Available Units</dt><dd>{{ viewEquipment.availableQuantity }}</dd></div>
            <div><dt>Reserved Units</dt><dd>{{ viewEquipment.reservedQuantity || 0 }}</dd></div>
            <div><dt>Maintenance Units</dt><dd>{{ viewEquipment.underMaintenanceQuantity || 0 }}</dd></div>
            <div><dt>Unavailable Units</dt><dd>{{ viewEquipment.unavailableQuantity || 0 }}</dd></div>
            <div><dt>Status</dt><dd>{{ viewEquipment.operationalStatus || viewEquipment.equipmentState }}</dd></div>
            <div><dt>Record Completeness</dt><dd>{{ inventoryHealthLabel(viewEquipment) }}</dd></div>
            <div><dt>Primary Barcode</dt><dd>{{ viewEquipment.barcode || 'N/A' }}</dd></div>
            <div><dt>Primary Asset Tag</dt><dd>{{ viewEquipment.assetId || 'N/A' }}</dd></div>
            <div><dt>Description</dt><dd>{{ viewEquipment.description || viewEquipment.scheduleDescription || 'N/A' }}</dd></div>
            <div><dt>Remarks</dt><dd>{{ viewEquipment.remarks || 'No remarks provided' }}</dd></div>
            <div><dt>Created</dt><dd>{{ formatDateTime(viewEquipment.createdTimestamp) }}</dd></div>
            <div><dt>Updated</dt><dd>{{ formatDateTime(viewEquipment.updatedTimestamp || viewEquipment.createdTimestamp) }}</dd></div>
          </dl>
          <div class="equipment-modal__specs" v-if="Array.isArray(viewEquipment.specifications) && viewEquipment.specifications.length > 0">
            <p class="equipment-modal__specs-title">Structured Item Specifications</p>
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
                  <th>Unit Code</th>
                  <th>Barcode</th>
                  <th>Asset Tag</th>
                  <th>Serial Number</th>
                  <th>Condition</th>
                  <th>Availability</th>
                  <th>Storage Location</th>
                  <th>Date Acquired</th>
                  <th>Maintenance State</th>
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
                  <td>{{ formatDateOnly(unit.dateAcquired) }}</td>
                  <td>{{ unit.maintenanceState || 'Operational' }}</td>
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
              <p class="equipment-modal__eyebrow">Inventory Record</p>
              <h2>{{ formMode === 'create' ? 'Add Inventory Record' : 'Update Inventory Record' }}</h2>
            </div>
            <button type="button" class="equipment-modal__close" :disabled="isSaving" @click="closeFormModal">X</button>
          </header>

          <div class="equipment-modal__grid">
            <label>
              <span>Equipment Name</span>
              <input v-model.trim="form.equipmentName" type="text" maxlength="150" />
            </label>
            <label>
              <span>Equipment Category</span>
              <input v-model.trim="form.equipmentCategory" type="text" maxlength="120" />
            </label>
            <label>
              <span>Brand</span>
              <input v-model.trim="form.equipmentBrand" type="text" maxlength="120" />
            </label>
            <label>
              <span>Specific Model</span>
              <input v-model.trim="form.equipmentModel" type="text" maxlength="160" placeholder="e.g. Sony PXW-Z90" />
            </label>
            <label>
              <span>Total Quantity / Unit Count</span>
              <input v-model.number="form.availableQuantity" type="number" min="1" />
            </label>
            <label>
              <span>Operational Status</span>
              <select v-model="form.operationalStatus">
                <option v-for="status in equipmentStatuses" :key="status" :value="status">{{ status }}</option>
              </select>
            </label>
            <label>
              <span>Primary Barcode</span>
              <input v-model.trim="form.barcode" type="text" maxlength="120" placeholder="Optional parent-level barcode" />
            </label>
            <label>
              <span>Primary Asset Tag</span>
              <input v-model.trim="form.assetId" type="text" maxlength="13" placeholder="F123-456-789" />
            </label>
            <label class="equipment-modal__full-width">
              <span>Description</span>
              <textarea
                v-model.trim="form.description"
                rows="4"
                placeholder="Describe the equipment, its model-specific use, and important operating details."
              />
            </label>
            <label class="equipment-modal__full-width">
              <span>Admin Remarks</span>
              <textarea
                v-model.trim="form.remarks"
                rows="3"
                placeholder="Inventory handling notes, maintenance context, storage reminders, or receiving notes."
              />
            </label>
          </div>
          <div class="equipment-modal__full-width equipment-modal__spec-editor">
            <p class="equipment-modal__section-note">
              Add the required structured specifications for this model such as wattage, connector type, battery type, dimensions, compatibility, or included accessories.
            </p>
            <div class="equipment-page__actions" style="justify-content: space-between; margin-bottom: 0.75rem;">
              <strong>Required Structured Specifications</strong>
              <button type="button" @click="addSpecificationRow">Add Specification</button>
            </div>
            <table class="equipment-page__table">
              <thead>
                <tr>
                  <th>Specification Label</th>
                  <th>Specification Value</th>
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
            <p class="equipment-modal__section-note">
              Each physical unit should have its own unit code, barcode, asset tag, serial number, condition, availability, and storage location.
            </p>
            <div class="equipment-page__actions" style="justify-content: space-between; margin-bottom: 0.75rem;">
              <strong>Tracked Physical Units</strong>
              <button type="button" @click="addUnitRow">Add Unit</button>
            </div>
            <table class="equipment-page__table">
              <thead>
                <tr>
                  <th>Unit Code</th>
                  <th>Barcode</th>
                  <th>Asset Tag</th>
                  <th>Serial Number</th>
                  <th>Condition</th>
                  <th>Availability</th>
                  <th>Storage Location</th>
                  <th>Date Acquired</th>
                  <th>Maintenance State</th>
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
                  <td><input v-model="unit.dateAcquired" type="date" /></td>
                  <td><input v-model.trim="unit.maintenanceState" type="text" /></td>
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

      <div
        v-if="isDetailedReportsModalOpen"
        class="equipment-modal__overlay"
        @click.self="closeDetailedReportsModal"
      >
        <section class="equipment-modal equipment-modal--wide">
          <header class="equipment-modal__header">
            <div>
              <p class="equipment-modal__eyebrow">Equipment Reports</p>
              <h2>Generate Detailed XLSX Reports</h2>
            </div>
            <button
              type="button"
              class="equipment-modal__close"
              :disabled="isDetailedReportsLoading"
              @click="closeDetailedReportsModal"
            >
              X
            </button>
          </header>

          <div class="equipment-report-builder">
            <p class="equipment-report-builder__intro">
              Choose a simple timeframe, then export either the inventory register or the reservation report as an XLSX file.
            </p>

            <div class="equipment-report-builder__filters">
              <label>
                <span>Timeframe</span>
                <select v-model="selectedDetailedTimeframe" :disabled="isDetailedReportsLoading">
                  <option value="days">Days</option>
                  <option value="weekly">Weekly</option>
                  <option value="monthly">Monthly</option>
                  <option value="yearly">Yearly</option>
                </select>
              </label>

              <label>
                <span>Reference Date</span>
                <input
                  v-model="reportReferenceDate"
                  type="date"
                  :disabled="isDetailedReportsLoading"
                />
              </label>

              <div class="equipment-report-builder__range">
                <strong>Applied Range</strong>
                <span>{{ detailedReportRangeLabel }}</span>
              </div>
            </div>

            <p
              v-if="detailedReportsError"
              class="equipment-page__feedback equipment-page__feedback--error"
            >
              {{ detailedReportsError }}
            </p>
            <p v-else-if="isDetailedReportsLoading" class="equipment-page__feedback">
              Loading report data...
            </p>

            <div class="equipment-report-builder__grid">
              <article class="equipment-report-card">
                <div>
                  <h3>Inventory XLSX</h3>
                  <p>Exports equipment and unit-level inventory details inside the selected timeframe.</p>
                  <small>{{ inventoryDetailedReportRows.length }} row(s) ready</small>
                </div>
                <button
                  type="button"
                  class="equipment-page__primary-button"
                  :disabled="inventoryDetailedReportRows.length === 0 || isDetailedReportsLoading"
                  @click="exportDetailedInventoryReport"
                >
                  Generate Inventory XLSX
                </button>
              </article>

              <article class="equipment-report-card">
                <div>
                  <h3>Reservation XLSX</h3>
                  <p>Exports reservation request details inside the selected timeframe.</p>
                  <small>{{ reservationDetailedReportRows.length }} row(s) ready</small>
                </div>
                <button
                  type="button"
                  class="equipment-page__primary-button"
                  :disabled="reservationDetailedReportRows.length === 0 || isDetailedReportsLoading"
                  @click="exportDetailedReservationReport"
                >
                  Generate Reservation XLSX
                </button>
              </article>
            </div>
          </div>

          <footer class="equipment-modal__footer">
            <button
              type="button"
              class="equipment-modal__secondary"
              :disabled="isDetailedReportsLoading"
              @click="closeDetailedReportsModal"
            >
              Close
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
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import {
  normalizeEquipmentForm,
  validateEquipmentForm,
} from '@/modules/facility/utils/equipmentFormValidation.js';
import {
  exportRowsToExcel,
  exportElementToPdf,
  exportRowsToCsv,
  printElement,
} from '@/shared/utils/adminExport.js';

const EQUIPMENT_PAGE_CACHE_KEY = 'techreserve_equipment_page_cache';
const equipmentStatuses = ['Available', 'Unavailable', 'Under Maintenance', 'Retired'];
const DEFAULT_REPORT_REFERENCE_DATE = '2026-07-17';

const requestStore = useRequestStore();

const equipmentList = ref(readEquipmentCache());
const isLoading = ref(false);
const isExportingExcel = ref(false);
const isDetailedReportsLoading = ref(false);
const isDetailedReportsModalOpen = ref(false);
const pageError = ref('');
const exportError = ref('');
const detailedReportsError = ref('');
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
const selectedDetailedTimeframe = ref('monthly');
const reportReferenceDate = ref(DEFAULT_REPORT_REFERENCE_DATE);
const detailedReportEquipmentRecords = ref([]);

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
  {
    key: 'equipment-detailed-reports',
    label: 'Detailed XLSX Reports',
    state: isDetailedReportsLoading.value ? 'loading' : 'idle',
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
  equipmentList.value.reduce((total, equipment) => total + Number(equipment.availableQuantity || 0), 0)
);
const maintenanceCount = computed(() =>
  equipmentList.value.reduce((total, equipment) => total + Number(equipment.underMaintenanceQuantity || 0), 0)
);
const retiredCount = computed(() =>
  equipmentList.value.filter((equipment) => equipment.equipmentState === 'Retired').length
);
const incompleteInventoryCount = computed(() =>
  equipmentList.value.filter((equipment) => isInventoryIncomplete(equipment)).length
);
const detailedReportRange = computed(() =>
  resolveDetailedReportRange(selectedDetailedTimeframe.value, reportReferenceDate.value)
);
const detailedReportRangeLabel = computed(() => (
  `${formatDetailedReportDate(detailedReportRange.value.startDateIso)} - ${formatDetailedReportDate(detailedReportRange.value.endDateIso)}`
));
const allReservationDetailedRecords = computed(() => {
  const uniqueRecords = new Map();
  [
    ...(requestStore.pendingRequestsList || []),
    ...(requestStore.approvedRequestsList || []),
    ...(requestStore.activeReservationsList || []),
    ...(requestStore.pastRecordsList || []),
  ].forEach((record) => {
    const recordKey = String(record?.requestIdentifier || record?.reservationIdentifier || record?.requestDisplayIdentifier || '');
    if (recordKey && !uniqueRecords.has(recordKey)) {
      uniqueRecords.set(recordKey, record);
    }
  });
  return [...uniqueRecords.values()];
});
const inventoryDetailedReportRows = computed(() => detailedReportEquipmentRecords.value
  .filter((record) => isDateWithinRange(record?.createdTimestamp, detailedReportRange.value))
  .flatMap((record) => {
    const units = Array.isArray(record?.units) && record.units.length > 0
      ? record.units
      : [{
          equipmentUnitIdentifierCode: '',
          barcode: record?.barcode || '',
          assetTag: record?.assetId || '',
          serialNumber: record?.assetId || '',
          conditionStatus: '',
          availabilityStatus: record?.operationalStatus || record?.equipmentState || '',
          storageLocation: '',
          remarks: record?.remarks || '',
        }];

    return units.map((unit, unitIndex) => ({
      equipmentId: record?.equipmentIdentifier || 'N/A',
      equipmentName: record?.equipmentName || 'Unnamed Equipment',
      category: record?.equipmentCategory || record?.categoryName || 'Uncategorized',
      brand: record?.equipmentBrand || 'N/A',
      model: record?.equipmentModel || 'N/A',
      totalQuantity: Number(record?.totalQuantity ?? record?.availableQuantity ?? units.length ?? 0),
      availableQuantity: Number(record?.availableQuantity ?? 0),
      reservedQuantity: Number(record?.reservedQuantity ?? 0),
      maintenanceQuantity: Number(record?.underMaintenanceQuantity ?? 0),
      unavailableQuantity: Number(record?.unavailableQuantity ?? 0),
      unitNumber: unitIndex + 1,
      unitCode: unit?.equipmentUnitIdentifierCode || 'N/A',
      barcode: unit?.barcode || 'N/A',
      assetTag: unit?.assetTag || 'N/A',
      serialNumber: unit?.serialNumber || 'N/A',
      condition: unit?.conditionStatus || 'N/A',
      availability: unit?.availabilityStatus || 'N/A',
      storageLocation: unit?.storageLocation || 'N/A',
      remarks: unit?.remarks || record?.remarks || 'N/A',
      dateAdded: formatDateTime(record?.createdTimestamp),
    }));
  }));
const reservationDetailedReportRows = computed(() => allReservationDetailedRecords.value
  .filter((record) => isDateWithinRange(record?.requestedDate || record?.dateOfRequest, detailedReportRange.value))
  .map((record) => ({
    requestId: record?.requestDisplayIdentifier || record?.requestIdentifier || record?.reservationIdentifier || 'N/A',
    requester: record?.requesterFullName || 'Unknown Requester',
    role: record?.requesterRole || 'Borrower',
    type: record?.requestType || 'Unknown',
    status: String(record?.requestStatus || record?.recordStatus || 'Unknown'),
    quantity: Number(record?.requestQuantity ?? 0),
    dateRequested: formatDateTime(record?.requestedDate || record?.dateOfRequest),
    schedule: record?.requestSchedule || 'No schedule',
    purpose: record?.requestPurpose || record?.purposeDescription || record?.purpose || 'N/A',
  })));

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

async function openDetailedReportsModal() {
  isDetailedReportsModalOpen.value = true;
  await loadDetailedReportsData();
}

function closeDetailedReportsModal() {
  if (isDetailedReportsLoading.value) {
    return;
  }

  isDetailedReportsModalOpen.value = false;
  detailedReportsError.value = '';
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

function formatDateOnly(value) {
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
    dateAcquired: '',
    maintenanceState: 'Operational',
    warrantyDetails: '',
    remarks: '',
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
    dateAcquired: '',
    maintenanceState: 'Operational',
    warrantyDetails: '',
    remarks: '',
  }));
}

function createEmptySpecification() {
  return {
    key: '',
    value: '',
  };
}

async function loadDetailedReportsData() {
  try {
    isDetailedReportsLoading.value = true;
    detailedReportsError.value = '';

    const [inventoryResponse] = await Promise.all([
      equipmentApi.listEquipment(),
      requestStore.fetchReservations({ clearOnError: false }),
    ]);

    detailedReportEquipmentRecords.value = Array.isArray(inventoryResponse?.data?.equipment)
      ? inventoryResponse.data.equipment
      : Array.isArray(inventoryResponse?.equipment)
        ? inventoryResponse.equipment
        : [];
  } catch (error) {
    detailedReportsError.value = error?.response?.data?.errorMessage || 'Unable to load detailed report data right now.';
  } finally {
    isDetailedReportsLoading.value = false;
  }
}

function resolveDetailedReportRange(timeframe, referenceDateIso) {
  const referenceDate = parseDateInputToLocalDate(referenceDateIso || DEFAULT_REPORT_REFERENCE_DATE);

  if (timeframe === 'days') {
    return {
      startDate: referenceDate,
      endDate: referenceDate,
      startDateIso: formatLocalDateIso(referenceDate),
      endDateIso: formatLocalDateIso(referenceDate),
    };
  }

  if (timeframe === 'weekly') {
    const weekStart = new Date(referenceDate);
    const dayOfWeek = weekStart.getDay();
    const distanceToMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
    weekStart.setDate(weekStart.getDate() - distanceToMonday);
    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekStart.getDate() + 6);
    return {
      startDate: weekStart,
      endDate: weekEnd,
      startDateIso: formatLocalDateIso(weekStart),
      endDateIso: formatLocalDateIso(weekEnd),
    };
  }

  if (timeframe === 'yearly') {
    const yearStart = new Date(referenceDate.getFullYear(), 0, 1);
    const yearEnd = new Date(referenceDate.getFullYear(), 11, 31);
    return {
      startDate: yearStart,
      endDate: yearEnd,
      startDateIso: formatLocalDateIso(yearStart),
      endDateIso: formatLocalDateIso(yearEnd),
    };
  }

  const monthStart = new Date(referenceDate.getFullYear(), referenceDate.getMonth(), 1);
  const monthEnd = new Date(referenceDate.getFullYear(), referenceDate.getMonth() + 1, 0);
  return {
    startDate: monthStart,
    endDate: monthEnd,
    startDateIso: formatLocalDateIso(monthStart),
    endDateIso: formatLocalDateIso(monthEnd),
  };
}

function parseDateInputToLocalDate(value) {
  const [year, month, day] = String(value || DEFAULT_REPORT_REFERENCE_DATE).split('-').map((part) => Number.parseInt(part, 10));
  return new Date(year, (month || 1) - 1, day || 1);
}

function normalizeDateToDayBoundary(value) {
  const parsedDate = new Date(value);
  if (Number.isNaN(parsedDate.getTime())) {
    return null;
  }

  return new Date(parsedDate.getFullYear(), parsedDate.getMonth(), parsedDate.getDate());
}

function isDateWithinRange(value, range) {
  const normalizedDate = normalizeDateToDayBoundary(value);
  if (!normalizedDate) {
    return false;
  }

  return normalizedDate.getTime() >= range.startDate.getTime()
    && normalizedDate.getTime() <= range.endDate.getTime();
}

function formatLocalDateIso(value) {
  const year = value.getFullYear();
  const month = String(value.getMonth() + 1).padStart(2, '0');
  const day = String(value.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function formatDetailedReportDate(value) {
  const parsedDate = parseDateInputToLocalDate(value);
  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(parsedDate);
}

function buildDetailedReportFileName(reportType) {
  return `techreserve-${reportType}-${selectedDetailedTimeframe.value}-${detailedReportRange.value.startDateIso}-to-${detailedReportRange.value.endDateIso}`;
}

function exportDetailedInventoryReport() {
  if (inventoryDetailedReportRows.value.length === 0) {
    detailedReportsError.value = 'No inventory records match the selected timeframe.';
    return;
  }

  detailedReportsError.value = '';
  exportRowsToExcel(
    buildDetailedReportFileName('inventory'),
    inventoryDetailedReportRows.value,
    'Inventory Report',
  );
}

function exportDetailedReservationReport() {
  if (reservationDetailedReportRows.value.length === 0) {
    detailedReportsError.value = 'No reservation records match the selected timeframe.';
    return;
  }

  detailedReportsError.value = '';
  exportRowsToExcel(
    buildDetailedReportFileName('reservations'),
    reservationDetailedReportRows.value,
    'Reservation Report',
  );
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

