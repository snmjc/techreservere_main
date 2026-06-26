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
            <div><dt>QR Code</dt><dd>{{ viewEquipment.barcode || 'N/A' }}</dd></div>
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
@import './css/Equipment.css';
</style>

