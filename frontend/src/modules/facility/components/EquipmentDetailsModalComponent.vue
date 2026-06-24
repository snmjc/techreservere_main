<template>
  <div v-if="show" class="equipment-details-modal-overlay" @click.self="emit('close')">
    <section class="equipment-details-modal">
      <button class="equipment-details-modal-close" type="button" aria-label="Close" @click="emit('close')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6 6 18" />
          <path d="m6 6 12 12" />
        </svg>
      </button>

      <div class="equipment-details-modal-heading">
        <h2>{{ title }}</h2>
        <p>{{ subtitle }}</p>
      </div>

      <p v-if="errorMessage" class="equipment-details-modal-error">{{ errorMessage }}</p>

      <div v-else class="equipment-details-modal-layout">
        <div class="equipment-details-modal-photo-card">
          <img
            :src="resolveEquipmentPhoto(equipment)"
            :alt="`${formatEquipmentText(equipment?.equipmentName)} photo`"
            class="equipment-details-modal-photo"
            :style="resolveEquipmentPhotoStyle(equipment)"
          />
        </div>

        <dl class="equipment-details-modal-grid">
          <div><dt>Equipment Name</dt><dd>{{ formatEquipmentText(equipment?.equipmentName) }}</dd></div>
          <div><dt>Equipment Type/Category</dt><dd>{{ formatEquipmentText(equipment?.equipmentCategory || equipment?.categoryName) }}</dd></div>
          <div><dt>Equipment Brand</dt><dd>{{ formatEquipmentText(equipment?.equipmentBrand) }}</dd></div>
          <div><dt>Available Quantity</dt><dd>{{ formatEquipmentQuantity(equipment?.availableQuantity) }}</dd></div>
          <div><dt>Operational Status / Status</dt><dd>{{ formatEquipmentStatus(equipment) }}</dd></div>
          <div v-if="Array.isArray(equipment?.inventoryItems) && equipment.inventoryItems.length"><dt>Grouped Units</dt><dd>{{ equipment.inventoryItems.length }}</dd></div>
          <div v-if="showAdminFields"><dt>QR Code</dt><dd>{{ formatEquipmentText(equipment?.barcode) }}</dd></div>
          <div v-if="showAdminFields"><dt>Asset ID</dt><dd>{{ formatEquipmentText(equipment?.assetId || equipment?.serialNumber) }}</dd></div>
          <div class="equipment-details-modal-grid__full">
            <dt>Remarks / Notes</dt>
            <dd>{{ formatEquipmentText(equipment?.description || equipment?.scheduleDescription) }}</dd>
          </div>
          <div
            v-if="!showAdminFields && Array.isArray(equipment?.inventoryItems) && equipment.inventoryItems.length"
            class="equipment-details-modal-grid__full"
          >
            <dt>Inventory Items</dt>
            <dd>
              <ul class="equipment-details-modal-item-list">
                <li v-for="item in equipment.inventoryItems" :key="item.equipmentIdentifier">
                  <strong>{{ formatEquipmentText(item.assetId || item.serialNumber) }}</strong>
                  <span>Barcode {{ formatEquipmentText(item.barcode) }}</span>
                  <span>{{ formatEquipmentStatus(item) }}</span>
                  <small v-if="item.description">{{ item.description }}</small>
                </li>
              </ul>
            </dd>
          </div>
        </dl>
      </div>

      <div class="equipment-details-modal-actions">
        <button class="equipment-details-modal-button equipment-details-modal-button--secondary" type="button" @click="emit('close')">
          {{ closeLabel }}
        </button>
        <button
          v-if="secondaryActionLabel"
          class="equipment-details-modal-button equipment-details-modal-button--primary"
          type="button"
          @click="emit('secondary-action')"
        >
          {{ secondaryActionLabel }}
        </button>
      </div>
    </section>
  </div>
</template>

<script setup>
import {
  formatEquipmentQuantity,
  formatEquipmentStatus,
  formatEquipmentText,
  resolveEquipmentPhoto,
  resolveEquipmentPhotoStyle,
} from '@/modules/facility/utils/equipmentPresentation.js';

defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  equipment: {
    type: Object,
    default: null,
  },
  errorMessage: {
    type: String,
    default: '',
  },
  title: {
    type: String,
    default: 'View Equipment Details',
  },
  subtitle: {
    type: String,
    default: 'Equipment information from the TechReserve equipment database.',
  },
  closeLabel: {
    type: String,
    default: 'Close',
  },
  secondaryActionLabel: {
    type: String,
    default: '',
  },
  showAdminFields: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close', 'secondary-action']);
</script>

<style scoped>
.equipment-details-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.75rem;
  background: rgba(15, 23, 42, 0.56);
}

.equipment-details-modal {
  position: relative;
  width: min(560px, 100%);
  max-height: 82vh;
  overflow-y: auto;
  background: #ffffff;
  border-radius: 18px;
  border: 1px solid #d9e3dd;
  box-shadow: 0 20px 48px rgba(15, 23, 42, 0.22);
}

.equipment-details-modal-close {
  position: absolute;
  top: 0.85rem;
  right: 0.85rem;
  background: #ffffff;
  border: 1px solid #d5ddd8;
  border-radius: 999px;
  cursor: pointer;
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.equipment-details-modal-heading,
.equipment-details-modal-actions {
  padding: 0.85rem 0.95rem;
}

.equipment-details-modal-heading {
  padding-right: 3.9rem;
  border-bottom: 1px solid #e8eeea;
}

.equipment-details-modal-heading h2 {
  margin: 0;
  color: #16361f;
  font-size: 1.15rem;
}

.equipment-details-modal-heading p {
  margin: 0.3rem 0 0;
  color: #4b6354;
  font-size: 0.92rem;
}

.equipment-details-modal-error {
  margin: 1.25rem;
  padding: 0.85rem 1rem;
  border-radius: 12px;
  font-weight: 700;
  color: #912018;
  background: #fef3f2;
  border: 1px solid #f5d1cd;
}

.equipment-details-modal-layout {
  display: grid;
  grid-template-columns: minmax(170px, 190px) minmax(0, 1fr);
  gap: 0.8rem;
  padding: 0.85rem 0.95rem;
}

.equipment-details-modal-photo-card {
  display: flex;
  align-items: flex-start;
}

.equipment-details-modal-photo {
  width: 100%;
  min-height: 150px;
  border-radius: 14px;
  border: 1px solid #d9e3dd;
  background: #f7faf8;
}

.equipment-details-modal-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
  margin: 0;
}

.equipment-details-modal-grid div {
  padding: 0.75rem 0.8rem;
  background: #f7faf8;
  border: 1px solid #e7efe9;
  border-radius: 12px;
}

.equipment-details-modal-grid dt {
  margin-bottom: 0.25rem;
  color: #607165;
  font-size: 0.74rem;
  font-weight: 700;
}

.equipment-details-modal-grid dd {
  margin: 0;
  color: #16361f;
  font-weight: 700;
  font-size: 0.9rem;
  line-height: 1.35;
}

.equipment-details-modal-grid__full {
  grid-column: 1 / -1;
}

.equipment-details-modal-item-list {
  display: grid;
  gap: 0.55rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.equipment-details-modal-item-list li {
  display: grid;
  gap: 0.12rem;
  padding: 0.7rem 0.8rem;
  background: #ffffff;
  border: 1px solid #dde7e0;
  border-radius: 10px;
}

.equipment-details-modal-item-list span,
.equipment-details-modal-item-list small {
  color: #607165;
  font-size: 0.78rem;
  font-weight: 600;
}

.equipment-details-modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.6rem;
  border-top: 1px solid #e8eeea;
}

.equipment-details-modal-button {
  min-height: 40px;
  padding: 0.65rem 0.9rem;
  border-radius: 10px;
  border: 1px solid transparent;
  font: inherit;
  font-weight: 700;
  cursor: pointer;
}

.equipment-details-modal-button--secondary {
  background: #ffffff;
  color: #264434;
  border-color: #d4ddd7;
}

.equipment-details-modal-button--primary {
  background: #157347;
  color: #ffffff;
}

@media (max-width: 720px) {
  .equipment-details-modal {
    width: min(520px, 100%);
    max-height: 90vh;
  }

  .equipment-details-modal-layout,
  .equipment-details-modal-grid {
    grid-template-columns: 1fr;
  }
}
</style>
