<template>
  <div v-if="show" class="equipment-modal-overlay" @click="handleOverlayClick">
    <section class="equipment-modal-card" @click.stop>
      <header class="equipment-modal-header">
        <h2>{{ isEditMode ? 'Edit Equipment' : 'Add Equipment' }}</h2>
        <button class="equipment-modal-close" type="button" :disabled="isSaving" aria-label="Close" @click="handleCancel">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
          </svg>
        </button>
      </header>

      <div class="equipment-modal-body">
        <div class="equipment-modal-grid">
          <label class="equipment-modal-field">
            <span>Equipment Name <em>*</em></span>
            <input
              v-model.trim="formData.equipmentName"
              type="text"
              maxlength="150"
              placeholder="Enter equipment name"
            />
          </label>

          <label class="equipment-modal-field">
            <span>Category / Type <em>*</em></span>
            <select v-model="formData.equipmentCategory">
              <option value="">Select category / type</option>
              <option v-for="option in categoryOptions" :key="option" :value="option">{{ option }}</option>
            </select>
          </label>

          <label class="equipment-modal-field">
            <span>Brand <em>*</em></span>
            <input
              v-model.trim="formData.equipmentBrand"
              type="text"
              maxlength="100"
              placeholder="Enter brand"
            />
          </label>

          <label class="equipment-modal-field">
            <span>Available Quantity <em>*</em></span>
            <div class="equipment-modal-input-with-suffix">
              <input
                v-model.number="formData.availableQuantity"
                type="number"
                min="1"
                step="1"
                placeholder="Enter quantity"
              />
              <small>units</small>
            </div>
          </label>

          <label class="equipment-modal-field">
            <span>Operational Status (Status) <em>*</em></span>
            <select v-model="formData.operationalStatus">
              <option value="">Select status</option>
              <option v-for="status in equipmentStatuses" :key="status" :value="status">{{ status }}</option>
            </select>
          </label>

          <label class="equipment-modal-field equipment-modal-field--full">
            <span>Description <em>*</em></span>
            <textarea
              v-model.trim="formData.description"
              rows="4"
              placeholder="Enter equipment description"
            />
          </label>

          <label class="equipment-modal-field">
            <span>Barcode <em>*</em></span>
            <input
              v-model.trim="formData.barcode"
              type="text"
              maxlength="120"
              placeholder="Enter barcode"
            />
            <small>Unique barcode for this equipment.</small>
          </label>

          <label class="equipment-modal-field">
            <span>Asset ID <em>*</em></span>
            <input
              v-model.trim="formData.assetId"
              type="text"
              maxlength="13"
              placeholder="Enter asset ID"
            />
            <small>Use the format F123-456-789.</small>
          </label>

          <label class="equipment-modal-field equipment-modal-field--full">
            <span>Photo (.jpg only)</span>
            <input
              ref="photoInputRef"
              type="file"
              accept=".jpg,.jpeg,image/jpeg"
              @change="handlePhotoFileChange"
            />
            <small>Optional. JPG files only.</small>
          </label>

          <div class="equipment-modal-field equipment-modal-field--full">
            <span>Photo Preview</span>
            <div class="equipment-modal-photo-preview">
              <img
                :src="photoPreviewSource"
                :alt="`${formData.equipmentName || 'Equipment'} photo preview`"
              />
            </div>
          </div>
        </div>

        <p v-if="errorMessage" class="equipment-modal-error">{{ errorMessage }}</p>
      </div>

      <footer class="equipment-modal-footer">
        <button class="equipment-modal-button equipment-modal-button--cancel" type="button" :disabled="isSaving" @click="handleCancel">
          Cancel
        </button>
        <button class="equipment-modal-button equipment-modal-button--save" type="button" :disabled="isSaving || !isFormReady" @click="handleSave">
          {{ isSaving ? 'Saving...' : (isEditMode ? 'Save Changes' : 'Add Equipment') }}
        </button>
      </footer>
    </section>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import equipmentApi from '@/modules/reservation/services/equipmentApi.js';
import {
  normalizeEquipmentForm,
  readPhotoFileAsDataUrl,
  validateEquipmentForm,
  validateEquipmentPhotoFile,
} from '@/modules/facility/utils/equipmentFormValidation.js';
import { resolveEquipmentPhoto } from '@/modules/facility/utils/equipmentPresentation.js';

const equipmentStatuses = ['Available', 'Unavailable', 'Under Maintenance', 'Retired'];
const defaultCategories = [
  'Audio / Microphone',
  'Audio',
  'Furniture',
  'Presentation',
  'Accessories',
  'Electrical',
  'Setup',
  'Decor',
  'Display',
  'Miscellaneous',
];

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
const photoInputRef = ref(null);
const isEditMode = computed(() => Boolean(props.equipment?.equipmentIdentifier));

const categoryOptions = computed(() => {
  const currentCategory = String(formData.value.equipmentCategory || '').trim();
  return [...new Set([...defaultCategories, currentCategory].filter(Boolean))];
});

const isFormReady = computed(() => validateEquipmentForm(formData.value) === '');
const photoPreviewSource = computed(() => {
  if (formData.value.photoData) {
    return formData.value.photoData;
  }

  return resolveEquipmentPhoto(props.equipment || formData.value);
});

watch(
  () => props.show,
  (isVisible) => {
    if (isVisible) {
      hydrateFromEquipment(props.equipment);
      return;
    }

    resetFormState();
  }
);

watch(
  () => props.equipment,
  (equipmentRecord) => {
    if (props.show) {
      hydrateFromEquipment(equipmentRecord);
    }
  }
);

function handleOverlayClick() {
  handleCancel();
}

function handleCancel() {
  if (isSaving.value) return;
  resetFormState();
  emit('close');
}

async function handleSave() {
  if (isSaving.value) return;

  const validationMessage = validateEquipmentForm(formData.value);
  if (validationMessage) {
    errorMessage.value = validationMessage;
    return;
  }

  try {
    isSaving.value = true;
    errorMessage.value = '';
    if (isEditMode.value && props.equipment?.equipmentIdentifier) {
      await equipmentApi.updateEquipment(props.equipment.equipmentIdentifier, normalizeEquipmentForm(formData.value));
    } else {
      await equipmentApi.createEquipment(normalizeEquipmentForm(formData.value));
    }
    emit('saved');
    emit('close');
  } catch (error) {
    errorMessage.value = error?.response?.data?.errorMessage || 'Failed to save equipment. Please try again.';
  } finally {
    isSaving.value = false;
  }
}

async function handlePhotoFileChange(event) {
  const [selectedFile] = Array.from(event?.target?.files || []);
  const photoValidationMessage = validateEquipmentPhotoFile(selectedFile);
  if (photoValidationMessage) {
    errorMessage.value = photoValidationMessage;
    resetPhotoInput();
    return;
  }

  if (!selectedFile) {
    formData.value = {
      ...formData.value,
      photoData: null,
    };
    return;
  }

  try {
    errorMessage.value = '';
    const encodedPhoto = await readPhotoFileAsDataUrl(selectedFile);
    formData.value = {
      ...formData.value,
      photoData: encodedPhoto,
    };
  } catch (error) {
    errorMessage.value = error?.message || 'Unable to read the selected equipment photo.';
    resetPhotoInput();
  }
}

function hydrateFromEquipment(equipmentRecord) {
  errorMessage.value = '';
  formData.value = equipmentRecord
    ? {
        equipmentName: equipmentRecord.equipmentName || '',
        equipmentCategory: equipmentRecord.equipmentCategory || equipmentRecord.categoryName || '',
        equipmentBrand: equipmentRecord.equipmentBrand || '',
        availableQuantity: Number(equipmentRecord.availableQuantity ?? equipmentRecord.totalQuantity ?? 1),
        operationalStatus: normalizeStatusValue(equipmentRecord.operationalStatus || equipmentRecord.equipmentState),
        description: equipmentRecord.description || equipmentRecord.scheduleDescription || '',
        barcode: equipmentRecord.barcode || '',
        assetId: equipmentRecord.assetId || equipmentRecord.serialNumber || '',
        photoData: equipmentRecord.photoData || null,
      }
    : createEmptyForm();

  resetPhotoInput();
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
    photoData: null,
  };
}

function resetFormState() {
  formData.value = createEmptyForm();
  errorMessage.value = '';
  resetPhotoInput();
}

function normalizeStatusValue(statusValue) {
  return statusValue === 'Active'
    ? 'Available'
    : statusValue === 'Maintenance'
      ? 'Under Maintenance'
      : statusValue === 'Inactive'
        ? 'Unavailable'
        : (statusValue || 'Available');
}

function resetPhotoInput() {
  if (photoInputRef.value) {
    photoInputRef.value.value = '';
  }
}
</script>

<style scoped>
.equipment-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1200;
  display: grid;
  place-items: center;
  padding: 1rem;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(2px);
}

.equipment-modal-card {
  width: min(640px, 100%);
  max-height: calc(100vh - 2rem);
  overflow: auto;
  background: #ffffff;
  border: 1px solid #dde6e0;
  border-radius: 12px;
  box-shadow: 0 24px 56px rgba(15, 23, 42, 0.2);
}

.equipment-modal-header,
.equipment-modal-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.1rem;
}

.equipment-modal-header {
  border-bottom: 1px solid #e6ede8;
}

.equipment-modal-footer {
  justify-content: flex-end;
  border-top: 1px solid #e6ede8;
}

.equipment-modal-header h2 {
  margin: 0;
  color: #24362d;
  font-size: 1rem;
  font-weight: 800;
}

.equipment-modal-close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  color: #687c70;
  background: transparent;
  border: 0;
  border-radius: 999px;
  cursor: pointer;
}

.equipment-modal-close svg {
  width: 14px;
  height: 14px;
}

.equipment-modal-body {
  padding: 0.95rem 1.1rem 1.1rem;
}

.equipment-modal-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.95rem 0.9rem;
}

.equipment-modal-field {
  display: grid;
  gap: 0.35rem;
}

.equipment-modal-field--full {
  grid-column: 1 / -1;
}

.equipment-modal-field span {
  color: #42584d;
  font-size: 0.66rem;
  font-weight: 800;
}

.equipment-modal-field em {
  color: #dc2626;
  font-style: normal;
}

.equipment-modal-field input,
.equipment-modal-field select,
.equipment-modal-field textarea {
  width: 100%;
  min-height: 40px;
  padding: 0.68rem 0.78rem;
  color: #22362c;
  background: #ffffff;
  border: 1px solid #d8e1db;
  border-radius: 8px;
  font: inherit;
  font-size: 0.75rem;
}

.equipment-modal-field textarea {
  min-height: 80px;
  resize: vertical;
}

.equipment-modal-input-with-suffix {
  position: relative;
}

.equipment-modal-input-with-suffix input {
  padding-right: 3.2rem;
}

.equipment-modal-input-with-suffix small {
  position: absolute;
  top: 50%;
  right: 0.75rem;
  transform: translateY(-50%);
  color: #8a9a90;
  font-size: 0.68rem;
  pointer-events: none;
}

.equipment-modal-field > small {
  color: #8a9a90;
  font-size: 0.61rem;
  font-weight: 700;
}

.equipment-modal-photo-preview {
  overflow: hidden;
  border: 1px solid #d8e1db;
  border-radius: 12px;
  background: #f4f8f5;
}

.equipment-modal-photo-preview img {
  display: block;
  width: 100%;
  max-height: 220px;
  object-fit: cover;
}

.equipment-modal-error {
  margin: 1rem 0 0;
  padding: 0.8rem 0.9rem;
  color: #991b1b;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  font-size: 0.78rem;
  font-weight: 800;
}

.equipment-modal-button {
  min-height: 36px;
  padding: 0 0.95rem;
  border-radius: 8px;
  font: inherit;
  font-size: 0.74rem;
  font-weight: 850;
  cursor: pointer;
}

.equipment-modal-button--cancel {
  color: #355043;
  background: #ffffff;
  border: 1px solid #d8e1db;
}

.equipment-modal-button--save {
  color: #ffffff;
  background: linear-gradient(135deg, #109451, #0c7b43);
  border: 1px solid #0c7b43;
}

@media (hover: hover) {
  .equipment-modal-close:hover {
    color: #2f4a3d;
    background: #f3f7f4;
  }
}

.equipment-modal-button:disabled,
.equipment-modal-close:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 720px) {
  .equipment-modal-grid {
    grid-template-columns: 1fr;
  }
}
</style>
