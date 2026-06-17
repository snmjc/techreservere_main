<template>
  <div class="facility-equipment-grid">
    <article
      v-for="equipmentRecord in filteredEquipmentRecords"
      :key="equipmentRecord.equipmentIdentifier"
      class="facility-equipment-card"
      :class="{
        'facility-equipment-card--selected': equipmentRecord.equipmentIdentifier === selectedEquipmentIdentifier,
      }"
      @click="emit('select-equipment', equipmentRecord)"
    >
      <div class="facility-equipment-card-media">
        <img
          :src="resolvePhotoSrc(equipmentRecord)"
          :alt="`${resolveTextValue(equipmentRecord.equipmentName)} photo`"
          class="facility-equipment-card-image"
        />
      </div>

      <div class="facility-equipment-card-body">
        <div class="facility-equipment-card-header">
          <div>
            <h3 class="facility-equipment-card-name">{{ resolveTextValue(equipmentRecord.equipmentName) }}</h3>
            <p class="facility-equipment-card-category">{{ resolveTextValue(equipmentRecord.equipmentCategory || equipmentRecord.categoryName) }}</p>
          </div>
          <span
            class="facility-equipment-card-status"
            :class="{
              'facility-equipment-card-status--available': resolveEquipmentState(equipmentRecord) === 'Available',
              'facility-equipment-card-status--unavailable': resolveEquipmentState(equipmentRecord) !== 'Available',
            }"
          >
            {{ resolveEquipmentState(equipmentRecord) }}
          </span>
        </div>

        <dl class="facility-equipment-card-details">
          <div>
            <dt>Brand</dt>
            <dd>{{ resolveTextValue(equipmentRecord.equipmentBrand) }}</dd>
          </div>
          <div>
            <dt>Available Qty</dt>
            <dd>{{ resolveQuantityValue(equipmentRecord.availableQuantity) }}</dd>
          </div>
          <div>
            <dt>Barcode</dt>
            <dd>{{ resolveTextValue(equipmentRecord.barcode) }}</dd>
          </div>
          <div>
            <dt>Asset ID</dt>
            <dd>{{ resolveTextValue(equipmentRecord.assetId || equipmentRecord.serialNumber) }}</dd>
          </div>
        </dl>

        <div class="facility-equipment-card-actions">
          <button type="button" class="facility-equipment-card-button facility-equipment-card-button--ghost" @click.stop="emit('view-equipment', equipmentRecord)">
            View Details
          </button>
          <button type="button" class="facility-equipment-card-button" @click.stop="emit('edit-equipment', equipmentRecord)">
            Edit Info
          </button>
          <button type="button" class="facility-equipment-card-button facility-equipment-card-button--danger" @click.stop="emit('delete-equipment', equipmentRecord)">
            Delete
          </button>
        </div>
      </div>
    </article>

    <div v-if="filteredEquipmentRecords.length === 0" class="facility-equipment-empty-state">
      No equipment found.
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  formatEquipmentQuantity,
  formatEquipmentStatus,
  formatEquipmentText,
  resolveEquipmentPhoto,
} from '@/modules/facility/utils/equipmentPresentation.js';

const props = defineProps({
  equipmentRecords: {
    type: Array,
    required: true,
  },
  availabilityFilter: {
    type: String,
    default: 'all',
  },
  selectedEquipmentIdentifier: {
    type: Number,
    default: null,
  },
});

const emit = defineEmits(['edit-equipment', 'view-equipment', 'select-equipment', 'delete-equipment']);

const filteredEquipmentRecords = computed(() => {
  const records = props.equipmentRecords || [];
  if (props.availabilityFilter === 'all') {
    return records;
  }

  const isAvailableFilter = props.availabilityFilter === 'available';
  return records.filter((record) => (resolveEquipmentState(record) === 'Available') === isAvailableFilter);
});

function resolvePhotoSrc(equipmentRecord) {
  return resolveEquipmentPhoto(equipmentRecord);
}

function resolveTextValue(value) {
  return formatEquipmentText(value);
}

function resolveQuantityValue(value) {
  return formatEquipmentQuantity(value);
}

function resolveEquipmentState(equipmentRecord) {
  return formatEquipmentStatus(equipmentRecord);
}
</script>

<style scoped>
.facility-equipment-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 280px));
  justify-content: start;
  gap: 0.75rem;
}

.facility-equipment-card {
  overflow: hidden;
  width: 100%;
  max-width: 280px;
  background: #ffffff;
  border: 1px solid #d8e4dd;
  border-radius: 12px;
  box-shadow: 0 10px 22px rgba(15, 23, 42, 0.06);
  cursor: pointer;
}

.facility-equipment-card--selected {
  border-color: #1a6e3a;
  box-shadow: 0 0 0 2px rgba(26, 110, 58, 0.18), 0 18px 38px rgba(15, 23, 42, 0.08);
}

.facility-equipment-card-media {
  height: 96px;
  background: linear-gradient(135deg, #f6fbf7 0%, #ebf5ee 100%);
}

.facility-equipment-card-image {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.facility-equipment-card-body {
  display: grid;
  gap: 0.6rem;
  padding: 0.65rem;
}

.facility-equipment-card-header {
  display: flex;
  justify-content: space-between;
  gap: 0.55rem;
  align-items: flex-start;
}

.facility-equipment-card-name {
  margin: 0;
  color: #132a1d;
  font-size: 0.78rem;
  font-weight: 800;
}

.facility-equipment-card-category {
  margin: 0.2rem 0 0;
  color: #567061;
  font-size: 0.7rem;
  font-weight: 600;
}

.facility-equipment-card-status {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 78px;
  min-height: 24px;
  padding: 0 0.55rem;
  border-radius: 999px;
  font-size: 0.64rem;
  font-weight: 800;
}

.facility-equipment-card-status--available {
  color: #156f3a;
  background: #def6e6;
}

.facility-equipment-card-status--unavailable {
  color: #a33434;
  background: #fde8e8;
}

.facility-equipment-card-details {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.45rem;
  margin: 0;
}

.facility-equipment-card-details div {
  padding: 0.5rem 0.6rem;
  background: #f7faf8;
  border: 1px solid #e4ede8;
  border-radius: 8px;
}

.facility-equipment-card-details dt {
  margin-bottom: 0.18rem;
  color: #6a7d72;
  font-size: 0.62rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.facility-equipment-card-details dd {
  margin: 0;
  color: #173321;
  font-size: 0.74rem;
  font-weight: 700;
}

.facility-equipment-card-actions {
  display: flex;
  gap: 0.45rem;
}

.facility-equipment-card-button {
  flex: 1;
  min-height: 32px;
  border: none;
  border-radius: 8px;
  background: #1a6e3a;
  color: #ffffff;
  font: inherit;
  font-size: 0.72rem;
  font-weight: 800;
  cursor: pointer;
}

.facility-equipment-card-button--ghost {
  background: #ffffff;
  color: #244235;
  border: 1px solid #d4ddd7;
}

.facility-equipment-card-button--danger {
  background: #a93434;
}

.facility-equipment-empty-state {
  grid-column: 1 / -1;
  padding: 2.5rem 1rem;
  text-align: center;
  color: #809084;
  background: #f7faf8;
  border: 1px dashed #c9d9cf;
  border-radius: 18px;
}

@media (max-width: 640px) {
  .facility-equipment-card-details {
    grid-template-columns: 1fr;
  }

  .facility-equipment-card-header,
  .facility-equipment-card-actions {
    flex-direction: column;
  }
}
</style>
