<template>
  <div v-if="show" class="equipment-modal-overlay" @click="handleOverlayClick">
    <div class="equipment-modal-content" @click.stop>
      <div class="equipment-modal-header">
        <div>
          <p class="equipment-modal-eyebrow">Equipment Record</p>
          <h3>{{ isEditMode ? 'Edit Equipment' : 'Add Equipment' }}</h3>
        </div>
        <button
          class="equipment-modal-close-button"
          type="button"
          :disabled="isSaving"
          @click="handleCancel"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18" />
            <line x1="6" y1="6" x2="18" y2="18" />
          </svg>
        </button>
      </div>

      <div class="equipment-modal-body">
        <div class="equipment-modal-grid">
          <label class="equipment-modal-form-group">
            <span class="equipment-modal-label">Equipment Name</span>
            <input
              v-model.trim="formData.equipmentName"
              type="text"
              class="equipment-modal-input"
              placeholder="Equipment Name"
              maxlength="150"
            />
          </label>

          <label class="equipment-modal-form-group">
            <span class="equipment-modal-label">Equipment Type/Category</span>
            <input
              v-model.trim="formData.equipmentCategory"
              type="text"
              class="equipment-modal-input"
              placeholder="Equipment Type/Category"
              maxlength="100"
            />
          </label>

          <label class="equipment-modal-form-group">
            <span class="equipment-modal-label">Equipment Brand</span>
            <input
              v-model.trim="formData.equipmentBrand"
              type="text"
              class="equipment-modal-input"
              placeholder="Equipment Brand"
              maxlength="100"
            />
          </label>

          <label class="equipment-modal-form-group">
            <span class="equipment-modal-label">Available Quantity</span>
            <input
              v-model.number="formData.availableQuantity"
              type="number"
              min="1"
              step="1"
              class="equipment-modal-input"
              placeholder="Available Quantity"
            />
          </label>

          <label class="equipment-modal-form-group">
            <span class="equipment-modal-label">Operational Status / Status</span>
            <select v-model="formData.operationalStatus" class="equipment-modal-input">
              <option value="">Select status</option>
              <option v-for="status in equipmentStatuses" :key="status" :value="status">{{ status }}</option>
            </select>
          </label>

          <label class="equipment-modal-form-group">
            <span class="equipment-modal-label">Barcode</span>
            <input
              v-model.trim="formData.barcode"
              type="text"
              class="equipment-modal-input"
              placeholder="Barcode"
              maxlength="120"
            />
          </label>

          <label class="equipment-modal-form-group">
            <span class="equipment-modal-label">Asset ID</span>
            <input
              v-model.trim="formData.assetId"
              type="text"
              class="equipment-modal-input"
              placeholder="F123-456-789"
              maxlength="13"
            />
            <small class="equipment-modal-hint">Required format: Fxxx-xxx-xxx</small>
          </label>

          <label class="equipment-modal-form-group equipment-modal-form-group--full">
            <span class="equipment-modal-label">Description</span>
            <textarea
              v-model.trim="formData.description"
              class="equipment-modal-input equipment-modal-textarea"
              rows="4"
              placeholder="Description"
            />
          </label>
        </div>

        <p v-if="errorMessage" class="equipment-modal-feedback equipment-modal-feedback--error">{{ errorMessage }}</p>
      </div>

      <div class="equipment-modal-footer">
        <button
          class="equipment-modal-button equipment-modal-button--cancel"
          type="button"
          :disabled="isSaving"
          @click="handleCancel"
        >
          Cancel
        </button>
        <button
          class="equipment-modal-button equipment-modal-button--save"
          type="button"
          :disabled="isSaving || !isFormReady"
          @click="handleSave"
        >
          {{ isSaving ? (isEditMode ? 'Saving...' : 'Saving...') : (isEditMode ? 'Save Changes' : 'Save Equipment') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import equipmentApi from '@/modules/reservation/services/equipmentApi.js';
import {
  normalizeEquipmentForm,
  validateEquipmentForm,
} from '@/modules/facility/utils/equipmentFormValidation.js';

const equipmentStatuses = ['Available', 'Unavailable', 'Under Maintenance', 'Retired'];

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  equipment: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'saved']);

const isSaving = ref(false);
const errorMessage = ref('');
const formData = ref(createEmptyForm());

const isEditMode = computed(() => Boolean(props.equipment?.equipmentIdentifier));
const isFormReady = computed(() => validateEquipmentForm(formData.value) === '');

watch(
  () => [props.show, props.equipment],
  ([isVisible]) => {
    if (!isVisible) {
      return;
    }

    errorMessage.value = '';
    formData.value = createFormFromEquipment(props.equipment);
  },
  { immediate: true }
);

function handleOverlayClick() {
  handleCancel();
}

function handleCancel() {
  if (isSaving.value) {
    return;
  }

  resetFormState();
  emit('close');
}

async function handleSave() {
  if (isSaving.value) {
    return;
  }

  const validationMessage = validateEquipmentForm(formData.value);
  if (validationMessage) {
    errorMessage.value = validationMessage;
    return;
  }

  try {
    isSaving.value = true;
    errorMessage.value = '';

    const payload = normalizeEquipmentForm(formData.value);

    if (isEditMode.value) {
      await equipmentApi.updateEquipment(props.equipment.equipmentIdentifier, payload);
    } else {
      await equipmentApi.createEquipment(payload);
    }

    resetFormState();
    emit('saved');
    emit('close');
  } catch (error) {
    errorMessage.value = error?.response?.data?.errorMessage || 'Failed to save equipment. Please try again.';
  } finally {
    isSaving.value = false;
  }
}

function createFormFromEquipment(equipment) {
  if (!equipment) {
    return createEmptyForm();
  }

  return {
    equipmentName: equipment.equipmentName || '',
    equipmentCategory: equipment.equipmentCategory || equipment.categoryName || '',
    equipmentBrand: equipment.equipmentBrand || '',
    availableQuantity: Number(equipment.availableQuantity ?? equipment.totalQuantity ?? 0),
    operationalStatus: equipment.operationalStatus || equipment.equipmentState || '',
    description: equipment.description || equipment.scheduleDescription || '',
    barcode: equipment.barcode || '',
    assetId: equipment.assetId || '',
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

function resetFormState() {
  formData.value = createEmptyForm();
  errorMessage.value = '';
}
</script>

<style scoped>
.equipment-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.56);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.equipment-modal-content {
  background: #ffffff;
  border-radius: 20px;
  width: min(760px, 100%);
  max-height: 92vh;
  overflow-y: auto;
  border: 1px solid #d9e3dd;
  box-shadow: 0 24px 64px rgba(15, 23, 42, 0.24);
}

.equipment-modal-header,
.equipment-modal-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  padding: 1.1rem 1.25rem;
}

.equipment-modal-header {
  border-bottom: 1px solid #e8eeea;
}

.equipment-modal-footer {
  justify-content: flex-end;
  border-top: 1px solid #e8eeea;
}

.equipment-modal-eyebrow {
  margin: 0 0 0.35rem;
  color: #4f7a62;
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.equipment-modal-header h3 {
  margin: 0;
  font-size: 1.35rem;
  color: #16361f;
}

.equipment-modal-close-button {
  background: #ffffff;
  border: 1px solid #d5ddd8;
  border-radius: 999px;
  cursor: pointer;
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.equipment-modal-body {
  padding: 1.25rem;
}

.equipment-modal-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.equipment-modal-form-group {
  display: grid;
  gap: 0.45rem;
}

.equipment-modal-form-group--full {
  grid-column: 1 / -1;
}

.equipment-modal-label {
  color: #51635a;
  font-size: 0.85rem;
  font-weight: 700;
}

.equipment-modal-input {
  width: 100%;
  min-height: 46px;
  padding: 0.78rem 0.9rem;
  border: 1px solid #ced9d1;
  border-radius: 12px;
  font: inherit;
  color: #16361f;
  background: #ffffff;
}

.equipment-modal-input:focus {
  outline: none;
  border-color: #2f8f5b;
  box-shadow: 0 0 0 3px rgba(47, 143, 91, 0.12);
}

.equipment-modal-textarea {
  min-height: 120px;
  resize: vertical;
}

.equipment-modal-hint {
  color: #607165;
  font-size: 0.78rem;
}

.equipment-modal-feedback {
  margin: 1rem 0 0;
  padding: 0.85rem 1rem;
  border-radius: 12px;
  font-weight: 700;
}

.equipment-modal-feedback--error {
  color: #912018;
  background: #fef3f2;
  border: 1px solid #f5d1cd;
}

.equipment-modal-button {
  min-height: 44px;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  border: 1px solid transparent;
  font: inherit;
  font-weight: 700;
  cursor: pointer;
}

.equipment-modal-button--cancel {
  background: #ffffff;
  color: #264434;
  border-color: #d4ddd7;
}

.equipment-modal-button--save {
  background: #157347;
  color: #ffffff;
}

.equipment-modal-button:disabled,
.equipment-modal-close-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 720px) {
  .equipment-modal-grid {
    grid-template-columns: 1fr;
  }
}
</style>
