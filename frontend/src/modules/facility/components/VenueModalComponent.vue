<!-- ===== AI GENERATED: VenueModalComponent ===== -->
<template>
  <div v-if="show" class="venue-modal-overlay" @click="handleOverlayClick">
    <div class="venue-modal-content" @click.stop>
      <div class="venue-modal-header">
        <h3>{{ isEditMode ? 'Edit Venue' : 'Add Venue' }}</h3>
        <button class="venue-modal-close-button" @click="handleClose">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
      <div class="venue-modal-body">
        <div class="venue-modal-form-group">
          <label class="venue-modal-label">Venue Name</label>
          <input
            v-model="formData.venueName"
            type="text"
            class="venue-modal-input"
            placeholder="Enter venue name"
          />
        </div>
        <div class="venue-modal-form-group">
          <label class="venue-modal-label">Location</label>
          <input
            v-model="formData.venueLocation"
            type="text"
            class="venue-modal-input"
            placeholder="Enter location"
          />
        </div>
        <div class="venue-modal-form-group">
          <label class="venue-modal-label">Floor Level</label>
          <select v-model="formData.floorLevel" class="venue-modal-input">
            <option value="">Select floor level</option>
            <option value="18th Floor">18th Floor</option>
            <option value="17th Floor">17th Floor</option>
            <option value="16th Floor">16th Floor</option>
            <option value="15th Floor">15th Floor</option>
            <option value="8th Floor">8th Floor</option>
            <option value="4th Floor">4th Floor</option>
            <option value="5th Floor">5th Floor</option>
            <option value="6th Floor">6th Floor</option>
            <option value="7th Floor">7th Floor</option>
            <option value="3rd Floor">3rd Floor</option>
            <option value="2nd Floor">2nd Floor</option>
            <option value="1st Floor">1st Floor</option>
            <option value="GF / 1st Floor">GF / 1st Floor</option>
            <option value="MH Floor">MH Floor</option>
            <option value="Pool">Pool</option>
            <option value="Outdoor">Outdoor</option>
          </select>
        </div>
        <div class="venue-modal-form-group">
          <label class="venue-modal-label">Capacity Limit</label>
          <input
            v-model.number="formData.capacityLimit"
            type="number"
            class="venue-modal-input"
            placeholder="Enter capacity"
          />
        </div>
        <div class="venue-modal-form-group">
          <label class="venue-modal-label">Description</label>
          <textarea
            v-model="formData.description"
            class="venue-modal-input"
            rows="3"
            placeholder="Enter description (optional)"
          />
        </div>
        <div class="venue-modal-form-group">
          <label class="venue-modal-label">Image URL</label>
          <input
            v-model="formData.imageUrl"
            type="text"
            class="venue-modal-input"
            placeholder="Enter image URL (optional)"
          />
        </div>
      </div>
      <div class="venue-modal-footer">
        <button class="venue-modal-button venue-modal-button--cancel" @click="handleClose">
          Cancel
        </button>
        <button class="venue-modal-button venue-modal-button--save" @click="handleSave">
          {{ isEditMode ? 'Update' : 'Add' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import venueApi from '@/modules/reservation/services/venueApi.js';

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  venue: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'saved']);

const isEditMode = ref(false);
const formData = ref({
  venueName: '',
  venueLocation: '',
  floorLevel: '',
  capacityLimit: null,
  description: '',
  imageUrl: ''
});

watch(() => props.show, (newVal) => {
  if (newVal) {
    isEditMode.value = !!props.venue;
    if (props.venue) {
      formData.value = {
        venueName: props.venue.venueName || '',
        venueLocation: props.venue.venueLocation || '',
        floorLevel: props.venue.floorLevel || '',
        capacityLimit: props.venue.capacityLimit || null,
        description: props.venue.description || '',
        imageUrl: props.venue.imageUrl || ''
      };
    } else {
      formData.value = {
        venueName: '',
        venueLocation: '',
        floorLevel: '',
        capacityLimit: null,
        description: '',
        imageUrl: ''
      };
    }
  }
});

function handleOverlayClick() {
  handleClose();
}

function handleClose() {
  emit('close');
}

async function handleSave() {
  try {
    const venueData = {
      venueName: formData.value.venueName,
      venueLocation: formData.value.venueLocation,
      floorLevel: formData.value.floorLevel,
      capacityLimit: formData.value.capacityLimit,
      description: formData.value.description,
      imageUrl: formData.value.imageUrl
    };
    if (isEditMode.value && props.venue) {
      await venueApi.updateVenue(props.venue.venueIdentifier, venueData);
    } else {
      await venueApi.createVenue(venueData);
    }
    emit('saved');
    handleClose();
  } catch (error) {
    console.error('Error saving venue:', error);
    alert('Failed to save venue. Please try again.');
  }
}
</script>

<style scoped>
.venue-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.venue-modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.venue-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.venue-modal-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.venue-modal-close-button {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.venue-modal-close-button:hover {
  opacity: 0.7;
}

.venue-modal-body {
  padding: 20px;
}

.venue-modal-form-group {
  margin-bottom: 16px;
}

.venue-modal-label {
  display: block;
  margin-bottom: 6px;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
}

.venue-modal-input {
  width: 100%;
  padding: 10px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.venue-modal-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.venue-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid #e5e7eb;
}

.venue-modal-button {
  padding: 10px 20px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  border: none;
}

.venue-modal-button--cancel {
  background: #f3f4f6;
  color: #374151;
}

.venue-modal-button--cancel:hover {
  background: #e5e7eb;
}

.venue-modal-button--save {
  background: #3b82f6;
  color: white;
}

.venue-modal-button--save:hover {
  background: #2563eb;
}
</style>
