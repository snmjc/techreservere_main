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
          :style="resolvePhotoStyle(equipmentRecord)"
        />
      </div>

      <div class="facility-equipment-card-body">
        <div class="facility-equipment-card-header">
          <div class="facility-equipment-card-title-group">
            <h3 class="facility-equipment-card-name">{{ resolveTextValue(equipmentRecord.equipmentName) }}</h3>
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

          <div class="facility-equipment-card-actions">
            <button type="button" class="facility-equipment-card-action" title="View equipment details" @click.stop="emit('view-equipment', equipmentRecord)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
            <button type="button" class="facility-equipment-card-action" title="Edit equipment" @click.stop="emit('edit-equipment', equipmentRecord)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
              </svg>
            </button>
            <button type="button" class="facility-equipment-card-action facility-equipment-card-action--delete" title="Delete equipment" @click.stop="emit('delete-equipment', equipmentRecord)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="facility-equipment-card-details">
          <p class="facility-equipment-card-detail"><strong>Location:</strong> {{ resolveEquipmentLocation(equipmentRecord) }}</p>
          <p class="facility-equipment-card-detail"><strong>Capacity:</strong> {{ resolveQuantityValue(equipmentRecord.availableQuantity) }}</p>
          <p class="facility-equipment-card-detail"><strong>Opens on:</strong> {{ resolveEquipmentDate(equipmentRecord) }}</p>
          <p class="facility-equipment-card-detail"><strong>Operations:</strong> {{ resolveEquipmentOperationalStatus(equipmentRecord) }}</p>
          <p class="facility-equipment-card-detail"><strong>Booking status:</strong> {{ resolveEquipmentState(equipmentRecord) }}</p>
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
  resolveEquipmentPhotoStyle,
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

function resolvePhotoStyle(equipmentRecord) {
  return resolveEquipmentPhotoStyle(equipmentRecord);
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

function resolveEquipmentLocation(equipmentRecord) {
  return resolveTextValue(
    equipmentRecord?.equipmentLocation
    || equipmentRecord?.location
    || equipmentRecord?.equipmentCategory
    || equipmentRecord?.categoryName
  );
}

function resolveEquipmentDate(equipmentRecord) {
  return resolveTextValue(
    equipmentRecord?.availabilityDate
    || equipmentRecord?.updatedTimestamp?.slice?.(0, 10)
    || equipmentRecord?.createdTimestamp?.slice?.(0, 10)
  );
}

function resolveEquipmentOperationalStatus(equipmentRecord) {
  return resolveTextValue(
    equipmentRecord?.operationalStatus
    || equipmentRecord?.equipmentStatus
    || (resolveEquipmentState(equipmentRecord) === 'Available' ? 'Active' : 'Inactive')
  );
}
</script>

<style scoped>
.facility-equipment-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(230px, 280px));
  justify-content: start;
  gap: 0.8rem;
}

.facility-equipment-card {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 280px;
  background: #ffffff;
  border: 2px solid #16a34a;
  border-radius: 12px;
  box-shadow: none;
  cursor: pointer;
}

.facility-equipment-card--selected {
  border-color: #15803d;
  box-shadow: 0 0 0 2px rgba(21, 128, 61, 0.12);
}

.facility-equipment-card-media {
  height: 112px;
  margin: 0.6rem 0.6rem 0;
  overflow: hidden;
  border-radius: 8px;
  background: linear-gradient(135deg, #f6fbf7 0%, #ebf5ee 100%);
}

.facility-equipment-card-image {
  display: block;
  width: 100%;
  height: 100%;
}

.facility-equipment-card-body {
  display: grid;
  gap: 0.45rem;
  padding: 0.65rem 0.7rem 0.75rem;
}

.facility-equipment-card-header {
  display: flex;
  justify-content: space-between;
  gap: 0.45rem;
  align-items: flex-start;
}

.facility-equipment-card-title-group {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  min-width: 0;
}

.facility-equipment-card-name {
  margin: 0;
  color: #111827;
  font-size: 0.8rem;
  font-weight: 700;
  line-height: 1.3;
  overflow-wrap: anywhere;
}

.facility-equipment-card-status {
  display: inline-flex;
  min-height: auto;
  padding: 0;
  border-radius: 0;
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.facility-equipment-card-status--available {
  color: #16a34a;
  background: transparent;
}

.facility-equipment-card-status--unavailable {
  color: #dc2626;
  background: transparent;
}

.facility-equipment-card-details {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.facility-equipment-card-detail {
  margin: 0;
  color: #374151;
  font-size: 0.72rem;
  line-height: 1.4;
  overflow-wrap: anywhere;
  word-break: break-word;
}

.facility-equipment-card-detail strong {
  color: inherit;
}

.facility-equipment-card-actions {
  display: flex;
  gap: 0.3rem;
  flex-shrink: 0;
}

.facility-equipment-card-action {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  background: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  cursor: pointer;
  color: #6b7280;
}

.facility-equipment-card-action:hover {
  background-color: #f3f4f6;
  color: #374151;
}

.facility-equipment-card-action--delete:hover {
  background-color: #fef2f2;
  color: #dc2626;
  border-color: #dc2626;
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
  .facility-equipment-card-header {
    flex-direction: column;
  }

  .facility-equipment-card-actions {
    align-self: flex-start;
  }
}
</style>
