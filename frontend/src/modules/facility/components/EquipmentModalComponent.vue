<!-- ===== AI GENERATED: EquipmentModalComponent ===== -->
<template>
  <div v-if="show" class="equipment-modal-overlay" @click="handleOverlayClick">
    <div class="equipment-modal-content" @click.stop>
      <div class="equipment-modal-header">
        <h3>{{ isEditMode ? 'Edit Equipment' : 'Add Equipment' }}</h3>
        <button class="equipment-modal-close-button" @click="handleClose">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
      <div class="equipment-modal-body">
        <div class="equipment-modal-form-group">
          <label class="equipment-modal-label">Equipment Name</label>
          <input
            v-model="formData.equipmentName"
            type="text"
            class="equipment-modal-input"
            placeholder="Enter equipment name"
          />
        </div>
        <div class="equipment-modal-form-group">
          <label class="equipment-modal-label">Category Name</label>
          <input
            v-model="formData.categoryName"
            type="text"
            class="equipment-modal-input"
            placeholder="Enter category"
          />
        </div>
        <div class="equipment-modal-form-group">
          <label class="equipment-modal-label">Total Quantity</label>
          <input
            v-model.number="formData.totalQuantity"
            type="number"
            class="equipment-modal-input"
            placeholder="Enter quantity"
          />
        </div>
        <div class="equipment-modal-form-group">
          <label class="equipment-modal-label">Operational Status</label>
          <select v-model="formData.operationalStatus" class="equipment-modal-input">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
            <option value="Maintenance">Maintenance</option>
          </select>
        </div>
        <div class="equipment-modal-form-group">
          <label class="equipment-modal-label">Schedule Description</label>
          <textarea
            v-model="formData.scheduleDescription"
            class="equipment-modal-input"
            rows="3"
            placeholder="Enter schedule description (optional)"
          />
        </div>
      </div>
      <div class="equipment-modal-footer">
        <button class="equipment-modal-button equipment-modal-button--cancel" @click="handleClose">
          Cancel
        </button>
        <button class="equipment-modal-button equipment-modal-button--save" @click="handleSave">
          {{ isEditMode ? 'Update' : 'Add' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import equipmentApi from '@/modules/reservation/services/equipmentApi.js';

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  equipment: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'saved']);

const isEditMode = ref(false);
const formData = ref({
  equipmentName: '',
  categoryName: '',
  totalQuantity: 0,
  operationalStatus: 'Active',
  scheduleDescription: ''
});

watch(() => props.show, (newVal) => {
  if (newVal) {
    isEditMode.value = !!props.equipment;
    if (props.equipment) {
      formData.value = {
        equipmentName: props.equipment.equipmentName || '',
        categoryName: props.equipment.categoryName || '',
        totalQuantity: props.equipment.totalQuantity || 0,
        operationalStatus: props.equipment.operationalStatus || 'Active',
        scheduleDescription: props.equipment.scheduleDescription || ''
      };
    } else {
      formData.value = {
        equipmentName: '',
        categoryName: '',
        totalQuantity: 0,
        operationalStatus: 'Active',
        scheduleDescription: ''
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
    if (isEditMode.value && props.equipment) {
      await equipmentApi.updateEquipment(props.equipment.equipmentIdentifier, formData.value);
    } else {
      await equipmentApi.createEquipment(formData.value);
    }
    emit('saved');
    handleClose();
  } catch (error) {
    console.error('Error saving equipment:', error);
    alert('Failed to save equipment. Please try again.');
  }
}
</script>

<style scoped>
.equipment-modal-overlay {
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

.equipment-modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.equipment-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.equipment-modal-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.equipment-modal-close-button {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.equipment-modal-close-button:hover {
  opacity: 0.7;
}

.equipment-modal-body {
  padding: 20px;
}

.equipment-modal-form-group {
  margin-bottom: 16px;
}

.equipment-modal-label {
  display: block;
  margin-bottom: 6px;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
}

.equipment-modal-input {
  width: 100%;
  padding: 10px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.equipment-modal-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.equipment-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid #e5e7eb;
}

.equipment-modal-button {
  padding: 10px 20px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  border: none;
}

.equipment-modal-button--cancel {
  background: #f3f4f6;
  color: #374151;
}

.equipment-modal-button--cancel:hover {
  background: #e5e7eb;
}

.equipment-modal-button--save {
  background: #3b82f6;
  color: white;
}

.equipment-modal-button--save:hover {
  background: #2563eb;
}
</style>
