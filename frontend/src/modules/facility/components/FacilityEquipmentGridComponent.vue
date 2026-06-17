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
          <div class="facility-equipment-card-header-meta">
            <span
              class="facility-equipment-card-status"
              :class="{
                'facility-equipment-card-status--available': resolveEquipmentState(equipmentRecord) === 'Available',
                'facility-equipment-card-status--unavailable': resolveEquipmentState(equipmentRecord) !== 'Available',
              }"
            >
              {{ resolveEquipmentState(equipmentRecord) }}
            </span>

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
  grid-template-columns: repeat(auto-fill, minmax(240px, 320px));
  justify-content: start;
  gap: 0.85rem;
}

.facility-equipment-card {
  overflow: hidden;
  width: 100%;
  max-width: 320px;
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
  height: 116px;
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
  gap: 0.7rem;
  padding: 0.78rem;
}

.facility-equipment-card-header {
  display: flex;
  justify-content: flex-end;
  gap: 0.55rem;
  align-items: flex-start;
}

.facility-equipment-card-header-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.35rem;
}

.facility-equipment-card-status {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 88px;
  min-height: 26px;
  padding: 0 0.6rem;
  border-radius: 999px;
  font-size: 0.68rem;
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
  gap: 0.5rem;
  margin: 0;
}

.facility-equipment-card-details div {
  padding: 0.58rem 0.68rem;
  background: #f7faf8;
  border: 1px solid #e4ede8;
  border-radius: 8px;
}

.facility-equipment-card-details dt {
  margin-bottom: 0.18rem;
  color: #6a7d72;
  font-size: 0.65rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.facility-equipment-card-details dd {
  margin: 0;
  color: #173321;
  font-size: 0.78rem;
  font-weight: 700;
  line-height: 1.35;
  overflow-wrap: anywhere;
  word-break: break-word;
}

.facility-equipment-card-actions {
  display: flex;
  gap: 0.3rem;
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
  .facility-equipment-card-details {
    grid-template-columns: 1fr;
  }

  .facility-equipment-card-header {
    flex-direction: column;
  }

  .facility-equipment-card-header-meta {
    align-items: flex-start;
  }
}
</style>
