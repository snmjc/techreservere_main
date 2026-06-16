<template>
  <div v-if="show" class="venue-modal-overlay" @click="handleOverlayClick">
    <div class="venue-modal-content" @click.stop>
      <div class="venue-modal-header">
        <div>
          <p class="venue-modal-eyebrow">Venue Record</p>
          <h3>{{ isEditMode ? 'Edit Venue' : 'Add Venue' }}</h3>
        </div>
        <button class="venue-modal-close-button" type="button" :disabled="isSaving" @click="handleCancel">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>

      <div class="venue-modal-body">
        <div class="venue-modal-grid">
          <label class="venue-modal-form-group">
            <span class="venue-modal-label">Venue Name</span>
            <input v-model.trim="formData.venueName" type="text" class="venue-modal-input" placeholder="Venue name" maxlength="150" />
          </label>

          <label class="venue-modal-form-group">
            <span class="venue-modal-label">Location</span>
            <input v-model.trim="formData.venueLocation" type="text" class="venue-modal-input" placeholder="Location" maxlength="150" />
          </label>

          <label class="venue-modal-form-group">
            <span class="venue-modal-label">Floor Level</span>
            <select v-model="formData.floorLevel" class="venue-modal-input">
              <option value="">Select floor level</option>
              <option v-for="floor in floorOptions" :key="floor" :value="floor">{{ floor }}</option>
            </select>
          </label>

          <label class="venue-modal-form-group">
            <span class="venue-modal-label">Capacity</span>
            <input v-model.number="formData.capacityLimit" type="number" min="1" step="1" class="venue-modal-input" placeholder="Capacity" />
          </label>

          <label class="venue-modal-form-group">
            <span class="venue-modal-label">Availability Date</span>
            <input v-model="formData.availabilityDate" type="date" class="venue-modal-input" />
          </label>

          <label class="venue-modal-form-group">
            <span class="venue-modal-label">Operational Status</span>
            <select v-model="formData.operationalStatus" class="venue-modal-input">
              <option value="">Select status</option>
              <option v-for="status in venueOperationalStatuses" :key="status" :value="status">{{ status }}</option>
            </select>
          </label>

          <label class="venue-modal-form-group venue-modal-form-group--full">
            <span class="venue-modal-label">Photo of Venue</span>
            <input ref="photoInputRef" type="file" class="venue-modal-input venue-modal-file-input" accept=".jpg,image/jpeg" @change="handlePhotoChange" />
            <small class="venue-modal-hint">Optional. JPG only.</small>
          </label>

          <div v-if="photoPreviewSrc" class="venue-modal-photo-preview">
            <img :src="photoPreviewSrc" alt="Venue preview" />
          </div>

          <label class="venue-modal-form-group venue-modal-form-group--full">
            <span class="venue-modal-label">Description</span>
            <textarea v-model.trim="formData.description" class="venue-modal-input venue-modal-textarea" rows="4" placeholder="Venue description"></textarea>
          </label>
        </div>

        <p v-if="errorMessage" class="venue-modal-feedback venue-modal-feedback--error">{{ errorMessage }}</p>
      </div>

      <div class="venue-modal-footer">
        <button class="venue-modal-button venue-modal-button--cancel" type="button" :disabled="isSaving" @click="handleCancel">
          Cancel
        </button>
        <button class="venue-modal-button venue-modal-button--save" type="button" :disabled="isSaving || !isFormReady" @click="handleSave">
          {{ isSaving ? 'Saving...' : (isEditMode ? 'Save Changes' : 'Save Venue') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import venueApi from '@/modules/reservation/services/venueApi.js';
import {
  readVenuePhotoFileAsDataUrl,
  sanitizeVenuePayload,
  validateVenueForm,
  validateVenuePhotoFile,
  venueOperationalStatuses,
} from '@/modules/facility/utils/venueFormValidation.js';

const floorOptions = [
  '18th Floor', '17th Floor', '16th Floor', '15th Floor', '8th Floor',
  '7th Floor', '6th Floor', '5th Floor', '4th Floor', '3rd Floor',
  '2nd Floor', '1st Floor', 'GF / 1st Floor', 'MH Floor', 'Pool', 'Outdoor',
];

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  venue: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'saved']);

const isSaving = ref(false);
const errorMessage = ref('');
const photoInputRef = ref(null);
const formData = ref(createEmptyForm());

const isEditMode = computed(() => Boolean(props.venue?.venueIdentifier));
const isFormReady = computed(() => validateVenueForm(formData.value) === '');
const photoPreviewSrc = computed(() => formData.value.photoData || '');

watch(
  () => props.show,
  (isVisible) => {
    if (isVisible) {
      hydrateFromVenue(props.venue);
      return;
    }

    resetFormState();
  }
);

watch(
  () => props.venue,
  (venueRecord) => {
    if (props.show) {
      hydrateFromVenue(venueRecord);
    }
  }
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

  const validationMessage = validateVenueForm(formData.value);
  if (validationMessage) {
    errorMessage.value = validationMessage;
    return;
  }

  try {
    isSaving.value = true;
    errorMessage.value = '';

    const payload = sanitizeVenuePayload(formData.value);

    if (isEditMode.value && props.venue?.venueIdentifier) {
      await venueApi.updateVenue(props.venue.venueIdentifier, payload);
    } else {
      await venueApi.createVenue(payload);
    }

    resetFormState();
    emit('saved');
    emit('close');
  } catch (error) {
    errorMessage.value = resolveVenueSaveError(error);
  } finally {
    isSaving.value = false;
  }
}

async function handlePhotoChange(event) {
  const [selectedFile] = Array.from(event.target?.files || []);
  if (!selectedFile) {
    return;
  }

  const fileValidationMessage = validateVenuePhotoFile(selectedFile);
  if (fileValidationMessage) {
    errorMessage.value = fileValidationMessage;
    clearPhotoInput();
    return;
  }

  try {
    formData.value.photoData = await readVenuePhotoFileAsDataUrl(selectedFile);
    errorMessage.value = '';
  } catch (error) {
    errorMessage.value = error.message || 'Unable to read the selected venue photo.';
    clearPhotoInput();
  }
}

function hydrateFromVenue(venueRecord) {
  errorMessage.value = '';
  formData.value = venueRecord
    ? {
        venueName: venueRecord.venueName || '',
        venueLocation: venueRecord.venueLocation || '',
        floorLevel: venueRecord.floorLevel || '',
        capacityLimit: Number(venueRecord.capacityLimit ?? 0) || 1,
        availabilityDate: venueRecord.availabilityDate || '',
        operationalStatus: venueRecord.operationalStatus || 'Active',
        description: venueRecord.description || '',
        photoData: String(venueRecord.photoData || '').trim() || String(venueRecord.imageUrl || '').trim() || null,
      }
    : createEmptyForm();

  clearPhotoInput();
}

function createEmptyForm() {
  return {
    venueName: '',
    venueLocation: '',
    floorLevel: '',
    capacityLimit: 1,
    availabilityDate: '',
    operationalStatus: 'Active',
    description: '',
    photoData: null,
  };
}

function resetFormState() {
  formData.value = createEmptyForm();
  errorMessage.value = '';
  clearPhotoInput();
}

function clearPhotoInput() {
  if (photoInputRef.value) {
    photoInputRef.value.value = '';
  }
}

function resolveVenueSaveError(error) {
  const statusCode = Number(error?.response?.status || 0);
  const apiMessage = String(error?.response?.data?.errorMessage || '').trim();

  if (apiMessage !== '') {
    return apiMessage;
  }

  if (statusCode === 413 || statusCode === 422) {
    return 'Venue photo is too large. Please upload a smaller JPG image.';
  }

  if (statusCode === 409) {
    return 'A venue with this name already exists.';
  }

  return 'Failed to save venue. Please try again.';
}
</script>

<style scoped>
.venue-modal-overlay {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: rgba(15, 23, 42, 0.48);
  z-index: 1000;
}

.venue-modal-content {
  width: min(760px, 100%);
  max-height: 90vh;
  overflow: auto;
  background: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 18px;
  box-shadow: 0 24px 50px rgba(15, 23, 42, 0.22);
}

.venue-modal-header,
.venue-modal-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 1.2rem 1.35rem;
}

.venue-modal-header {
  border-bottom: 1px solid #e5e7eb;
}

.venue-modal-footer {
  justify-content: flex-end;
  border-top: 1px solid #e5e7eb;
}

.venue-modal-eyebrow {
  margin: 0 0 0.2rem;
  color: #6b7280;
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.venue-modal-header h3 {
  margin: 0;
  color: #111827;
  font-size: 1.2rem;
  font-weight: 800;
}

.venue-modal-close-button {
  display: grid;
  place-items: center;
  width: 38px;
  height: 38px;
  color: #6b7280;
  background: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  cursor: pointer;
}

.venue-modal-body {
  padding: 1.35rem;
}

.venue-modal-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.venue-modal-form-group {
  display: grid;
  gap: 0.4rem;
}

.venue-modal-form-group--full,
.venue-modal-photo-preview {
  grid-column: 1 / -1;
}

.venue-modal-label {
  color: #374151;
  font-size: 0.84rem;
  font-weight: 700;
}

.venue-modal-input {
  min-height: 44px;
  width: 100%;
  padding: 0.7rem 0.8rem;
  color: #111827;
  background: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 10px;
  font: inherit;
}

.venue-modal-input:focus {
  outline: none;
  border-color: #1a6e3a;
  box-shadow: 0 0 0 3px rgba(26, 110, 58, 0.12);
}

.venue-modal-textarea {
  min-height: 110px;
  resize: vertical;
}

.venue-modal-file-input {
  padding: 0.55rem 0.8rem;
}

.venue-modal-hint {
  color: #6b7280;
  font-size: 0.78rem;
}

.venue-modal-photo-preview {
  padding: 0.75rem;
  background: #f7faf8;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
}

.venue-modal-photo-preview img {
  display: block;
  width: 100%;
  max-height: 280px;
  object-fit: cover;
  border-radius: 12px;
}

.venue-modal-feedback {
  margin: 1rem 0 0;
  font-size: 0.84rem;
  font-weight: 700;
}

.venue-modal-feedback--error {
  color: #b91c1c;
}

.venue-modal-button {
  min-height: 44px;
  padding: 0 1rem;
  border-radius: 10px;
  border: 1px solid transparent;
  font-weight: 800;
  cursor: pointer;
}

.venue-modal-button--cancel {
  color: #374151;
  background: #ffffff;
  border-color: #d1d5db;
}

.venue-modal-button--save {
  color: #ffffff;
  background: #1a6e3a;
}

.venue-modal-button:disabled,
.venue-modal-close-button:disabled {
  opacity: 0.62;
  cursor: not-allowed;
}

@media (max-width: 720px) {
  .venue-modal-grid {
    grid-template-columns: 1fr;
  }
}
</style>
