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

      <div v-if="isLoading" class="equipment-page__state-card">Loading equipment records...</div>
      <div v-else-if="filteredEquipment.length === 0" class="equipment-page__state-card">
        No equipment records match the current search and filter.
      </div>
      <div v-else class="equipment-page__table-wrap">
        <table class="equipment-page__table">
          <thead>
            <tr>
              <th>Equipment ID</th>
              <th>Name</th>
              <th>Category</th>
              <th>Quantity</th>
              <th>Available</th>
              <th>Status</th>
              <th>Updated</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="equipment in filteredEquipment" :key="equipment.equipmentIdentifier">
              <td>{{ equipment.equipmentIdentifier }}</td>
              <td>{{ equipment.equipmentName }}</td>
              <td>{{ equipment.equipmentCategory || equipment.categoryName }}</td>
              <td>{{ equipment.totalQuantity }}</td>
              <td>{{ equipment.availableQuantity }}</td>
              <td>
                <span
                  class="equipment-page__status-badge"
                  :class="statusBadgeClass(equipment.equipmentState)"
                >
                  {{ equipment.equipmentState }}
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

          <dl class="equipment-modal__details">
            <div><dt>Equipment ID</dt><dd>{{ viewEquipment.equipmentIdentifier }}</dd></div>
            <div><dt>Name</dt><dd>{{ viewEquipment.equipmentName }}</dd></div>
            <div><dt>Category</dt><dd>{{ viewEquipment.equipmentCategory || viewEquipment.categoryName }}</dd></div>
            <div><dt>Brand</dt><dd>{{ viewEquipment.equipmentBrand || 'N/A' }}</dd></div>
            <div><dt>Available Quantity</dt><dd>{{ viewEquipment.availableQuantity }}</dd></div>
            <div><dt>Status</dt><dd>{{ viewEquipment.operationalStatus || viewEquipment.equipmentState }}</dd></div>
            <div><dt>Operational Status</dt><dd>{{ viewEquipment.operationalStatus }}</dd></div>
            <div><dt>Barcode</dt><dd>{{ viewEquipment.barcode || 'N/A' }}</dd></div>
            <div><dt>Asset ID</dt><dd>{{ viewEquipment.assetId || 'N/A' }}</dd></div>
            <div><dt>Description</dt><dd>{{ viewEquipment.description || viewEquipment.scheduleDescription || 'N/A' }}</dd></div>
            <div><dt>Created</dt><dd>{{ formatDateTime(viewEquipment.createdTimestamp) }}</dd></div>
            <div><dt>Updated</dt><dd>{{ formatDateTime(viewEquipment.updatedTimestamp || viewEquipment.createdTimestamp) }}</dd></div>
          </dl>

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
              <span>Barcode</span>
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
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import equipmentApi from '@/modules/reservation/services/equipmentApi.js';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import {
  normalizeEquipmentForm,
  validateEquipmentForm,
} from '@/modules/facility/utils/equipmentFormValidation.js';

const equipmentStatuses = ['Available', 'Unavailable', 'Under Maintenance', 'Retired'];

const equipmentList = ref([]);
const isLoading = ref(false);
const pageError = ref('');
const searchQuery = ref('');
const statusFilter = ref('all');
const sortOrder = ref('asc');

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

const filteredEquipment = computed(() => {
  const normalizedQuery = searchQuery.value.toLowerCase();

  const filtered = equipmentList.value.filter((equipment) => {
    const matchesQuery = normalizedQuery === ''
      || String(equipment.equipmentName || '').toLowerCase().includes(normalizedQuery)
      || String(equipment.equipmentCategory || equipment.categoryName || '').toLowerCase().includes(normalizedQuery);

    const matchesStatus = statusFilter.value === 'all' || equipment.equipmentState === statusFilter.value;

    return matchesQuery && matchesStatus;
  });

  return [...filtered].sort((left, right) => {
    if (sortOrder.value === 'recent') {
      return new Date(right.updatedTimestamp || right.createdTimestamp).getTime()
        - new Date(left.updatedTimestamp || left.createdTimestamp).getTime();
    }

    const comparison = left.equipmentName.localeCompare(right.equipmentName);
    return sortOrder.value === 'asc' ? comparison : comparison * -1;
  });
});

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

async function fetchEquipment() {
  try {
    isLoading.value = true;
    pageError.value = '';
    const response = await equipmentApi.listEquipment();
    equipmentList.value = response?.data?.equipment || [];
  } catch (error) {
    equipmentList.value = [];
    pageError.value = error?.response?.data?.errorMessage || 'Failed to load equipment records.';
  } finally {
    isLoading.value = false;
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
    availableQuantity: equipment.availableQuantity,
    operationalStatus: equipment.operationalStatus || equipment.equipmentState,
    description: equipment.description || equipment.scheduleDescription || '',
    barcode: equipment.barcode || '',
    assetId: equipment.assetId || '',
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

function createEmptyForm() {
  return {
    equipmentName: '',
    equipmentCategory: '',
    equipmentBrand: '',
    availableQuantity: 1,
    operationalStatus: 'Available',
    description: '',
    barcode: '',
    assetId: '',
  };
}
</script>

<style scoped>
.equipment-page {
  display: grid;
  gap: 1.25rem;
}

.equipment-page__header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-start;
  padding: 1.5rem;
  background: linear-gradient(135deg, #f7fbf6 0%, #eef8f3 100%);
  border: 1px solid #d8e6dc;
  border-radius: 18px;
}

.equipment-page__eyebrow,
.equipment-modal__eyebrow {
  margin: 0 0 0.35rem;
  font-size: 0.76rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #4f7a62;
  font-weight: 700;
}

.equipment-page__title {
  margin: 0;
  color: #16361f;
  font-size: 1.8rem;
}

.equipment-page__subtitle {
  margin: 0.4rem 0 0;
  color: #4b6354;
  max-width: 680px;
}

.equipment-page__controls {
  display: grid;
  grid-template-columns: minmax(220px, 1.7fr) repeat(2, minmax(180px, 0.8fr)) auto;
  gap: 0.9rem;
  align-items: end;
}

.equipment-page__search,
.equipment-page__filter {
  display: grid;
  gap: 0.35rem;
}

.equipment-page__label {
  color: #51635a;
  font-size: 0.83rem;
  font-weight: 700;
}

.equipment-page__search input,
.equipment-page__filter select,
.equipment-modal__grid input,
.equipment-modal__grid select,
.equipment-modal__grid textarea {
  width: 100%;
  min-height: 44px;
  padding: 0.75rem 0.85rem;
  border: 1px solid #ced9d1;
  border-radius: 12px;
  font: inherit;
  color: #16361f;
  background: #fff;
}

.equipment-page__search input:focus,
.equipment-page__filter select:focus,
.equipment-modal__grid input:focus,
.equipment-modal__grid select:focus,
.equipment-modal__grid textarea:focus {
  outline: none;
  border-color: #2f8f5b;
  box-shadow: 0 0 0 3px rgba(47, 143, 91, 0.12);
}

.equipment-page__primary-button,
.equipment-page__ghost-button,
.equipment-modal__primary,
.equipment-modal__secondary,
.equipment-modal__danger,
.equipment-page__actions button {
  min-height: 44px;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  border: 1px solid transparent;
  font: inherit;
  font-weight: 700;
  cursor: pointer;
}

.equipment-page__primary-button,
.equipment-modal__primary {
  color: #fff;
  background: #157347;
}

.equipment-page__ghost-button,
.equipment-modal__secondary,
.equipment-page__actions button {
  color: #264434;
  background: #fff;
  border-color: #d4ddd7;
}

.equipment-modal__danger,
.equipment-page__danger-action {
  color: #fff;
  background: #c53131;
}

.equipment-page__primary-button:disabled,
.equipment-page__ghost-button:disabled,
.equipment-modal__primary:disabled,
.equipment-modal__secondary:disabled,
.equipment-modal__danger:disabled,
.equipment-page__actions button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.equipment-page__summary {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.9rem;
}

.equipment-page__summary-card,
.equipment-page__state-card {
  padding: 1rem 1.1rem;
  background: #fff;
  border: 1px solid #dbe5df;
  border-radius: 16px;
}

.equipment-page__summary-card span {
  display: block;
  color: #56685e;
  font-size: 0.82rem;
  font-weight: 700;
}

.equipment-page__summary-card strong {
  display: block;
  margin-top: 0.45rem;
  color: #16361f;
  font-size: 1.6rem;
}

.equipment-page__table-wrap {
  overflow: auto;
  background: #fff;
  border: 1px solid #dbe5df;
  border-radius: 16px;
}

.equipment-page__table {
  width: 100%;
  border-collapse: collapse;
}

.equipment-page__table th,
.equipment-page__table td {
  padding: 0.95rem 1rem;
  text-align: left;
  border-bottom: 1px solid #edf2ee;
  vertical-align: middle;
}

.equipment-page__table th {
  color: #4b6354;
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.equipment-page__actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.equipment-page__status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 120px;
  padding: 0.4rem 0.7rem;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 700;
}

.equipment-page__status-badge--available {
  color: #0d6b42;
  background: #ddf8eb;
}

.equipment-page__status-badge--unavailable {
  color: #8c1f1f;
  background: #fee2e2;
}

.equipment-page__status-badge--maintenance {
  color: #9a5b00;
  background: #fff0c7;
}

.equipment-page__status-badge--retired {
  color: #475467;
  background: #eaecf0;
}

.equipment-page__feedback {
  margin: 0;
  padding: 0.85rem 1rem;
  border-radius: 12px;
  font-weight: 700;
}

.equipment-page__feedback--error {
  color: #912018;
  background: #fef3f2;
  border: 1px solid #f5d1cd;
}

.equipment-modal__overlay {
  position: fixed;
  inset: 0;
  z-index: 1100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: rgba(15, 23, 42, 0.52);
}

.equipment-modal {
  width: min(640px, 100%);
  background: #fff;
  border-radius: 20px;
  border: 1px solid #d9e3dd;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24);
}

.equipment-modal--wide {
  width: min(760px, 100%);
}

.equipment-modal__header,
.equipment-modal__footer {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: center;
  padding: 1.1rem 1.25rem;
}

.equipment-modal__header {
  border-bottom: 1px solid #edf2ee;
}

.equipment-modal__footer {
  justify-content: flex-end;
  border-top: 1px solid #edf2ee;
}

.equipment-modal__header h2 {
  margin: 0;
  color: #16361f;
}

.equipment-modal__close {
  width: 40px;
  height: 40px;
  border: 1px solid #d5ddd8;
  border-radius: 999px;
  background: #fff;
  font-size: 1.5rem;
  line-height: 1;
  cursor: pointer;
}

.equipment-modal__details {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem;
  padding: 1.25rem;
}

.equipment-modal__details div,
.equipment-modal__danger-summary p {
  padding: 0.85rem 0.95rem;
  background: #f7faf8;
  border: 1px solid #e7efe9;
  border-radius: 14px;
}

.equipment-modal__details dt {
  margin-bottom: 0.4rem;
  color: #607165;
  font-size: 0.8rem;
  font-weight: 700;
}

.equipment-modal__details dd {
  margin: 0;
  color: #16361f;
  font-weight: 700;
}

.equipment-modal__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
  padding: 1.25rem;
}

.equipment-modal__grid label {
  display: grid;
  gap: 0.35rem;
  color: #51635a;
  font-size: 0.86rem;
  font-weight: 700;
}

.equipment-modal__full-width {
  grid-column: 1 / -1;
}

.equipment-modal__grid textarea {
  min-height: 120px;
  resize: vertical;
}

.equipment-modal__danger-summary {
  display: grid;
  gap: 0.75rem;
  padding: 1.25rem;
}

.equipment-modal__danger-summary p {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  margin: 0;
}

.equipment-modal__danger-summary strong {
  color: #607165;
}

.equipment-modal__danger-summary span {
  color: #16361f;
  font-weight: 700;
  text-align: right;
}

@media (max-width: 900px) {
  .equipment-page__controls {
    grid-template-columns: 1fr 1fr;
  }

  .equipment-page__summary {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 720px) {
  .equipment-page__header {
    flex-direction: column;
  }

  .equipment-page__controls,
  .equipment-page__summary,
  .equipment-modal__details,
  .equipment-modal__grid {
    grid-template-columns: 1fr;
  }
}
</style>
