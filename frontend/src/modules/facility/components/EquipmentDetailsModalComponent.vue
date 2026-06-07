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
          />
        </div>

        <dl class="equipment-details-modal-grid">
          <div><dt>Equipment Name</dt><dd>{{ formatEquipmentText(equipment?.equipmentName) }}</dd></div>
          <div><dt>Equipment Type/Category</dt><dd>{{ formatEquipmentText(equipment?.equipmentCategory || equipment?.categoryName) }}</dd></div>
          <div><dt>Equipment Brand</dt><dd>{{ formatEquipmentText(equipment?.equipmentBrand) }}</dd></div>
          <div><dt>Available Quantity</dt><dd>{{ formatEquipmentQuantity(equipment?.availableQuantity) }}</dd></div>
          <div><dt>Operational Status / Status</dt><dd>{{ formatEquipmentStatus(equipment) }}</dd></div>
          <div class="equipment-details-modal-grid__full">
            <dt>Description</dt>
            <dd>{{ formatEquipmentText(equipment?.description || equipment?.scheduleDescription) }}</dd>
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
  padding: 1rem;
  background: rgba(15, 23, 42, 0.56);
}

.equipment-details-modal {
  position: relative;
  width: min(760px, 100%);
  max-height: 92vh;
  overflow-y: auto;
  background: #ffffff;
  border-radius: 20px;
  border: 1px solid #d9e3dd;
  box-shadow: 0 24px 64px rgba(15, 23, 42, 0.24);
}

.equipment-details-modal-close {
  position: absolute;
  top: 1rem;
  right: 1rem;
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

.equipment-details-modal-heading,
.equipment-details-modal-actions {
  padding: 1.25rem;
}

.equipment-details-modal-heading {
  padding-right: 4.5rem;
  border-bottom: 1px solid #e8eeea;
}

.equipment-details-modal-heading h2 {
  margin: 0;
  color: #16361f;
  font-size: 1.35rem;
}

.equipment-details-modal-heading p {
  margin: 0.4rem 0 0;
  color: #4b6354;
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
  grid-template-columns: minmax(220px, 260px) minmax(0, 1fr);
  gap: 1.25rem;
  padding: 1.25rem;
}

.equipment-details-modal-photo-card {
  display: flex;
  align-items: flex-start;
}

.equipment-details-modal-photo {
  width: 100%;
  border-radius: 16px;
  border: 1px solid #d9e3dd;
  background: #f7faf8;
  object-fit: cover;
}

.equipment-details-modal-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem;
  margin: 0;
}

.equipment-details-modal-grid div {
  padding: 0.85rem 0.95rem;
  background: #f7faf8;
  border: 1px solid #e7efe9;
  border-radius: 14px;
}

.equipment-details-modal-grid dt {
  margin-bottom: 0.4rem;
  color: #607165;
  font-size: 0.8rem;
  font-weight: 700;
}

.equipment-details-modal-grid dd {
  margin: 0;
  color: #16361f;
  font-weight: 700;
}

.equipment-details-modal-grid__full {
  grid-column: 1 / -1;
}

.equipment-details-modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  border-top: 1px solid #e8eeea;
}

.equipment-details-modal-button {
  min-height: 44px;
  padding: 0.75rem 1rem;
  border-radius: 12px;
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
  .equipment-details-modal-layout,
  .equipment-details-modal-grid {
    grid-template-columns: 1fr;
  }
}
</style>
