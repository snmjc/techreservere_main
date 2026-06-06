<template>
  <div class="facility-equipment-grid">
    <article
      v-for="equipmentRecord in filteredEquipmentRecords"
      :key="equipmentRecord.equipmentIdentifier"
      class="facility-equipment-card"
      :class="{
        'facility-equipment-card--available': resolveStatus(equipmentRecord) === 'Available',
        'facility-equipment-card--unavailable': resolveStatus(equipmentRecord) !== 'Available',
      }"
    >
      <div class="facility-equipment-card-media">
        <img
          :src="resolveImageUrl(equipmentRecord)"
          :alt="`${resolveTextValue(equipmentRecord.equipmentName)} photo`"
          class="facility-equipment-card-image"
          @error="handleImageError(equipmentRecord.equipmentIdentifier)"
        />
      </div>

      <div class="facility-equipment-card-body">
        <div class="facility-equipment-card-header">
          <div>
            <h3 class="facility-equipment-card-name">{{ resolveTextValue(equipmentRecord.equipmentName) }}</h3>
            <p class="facility-equipment-card-type">{{ resolveTextValue(equipmentRecord.equipmentCategory || equipmentRecord.categoryName) }}</p>
          </div>
          <span
            class="facility-equipment-card-status"
            :class="{
              'facility-equipment-card-status--available': resolveStatus(equipmentRecord) === 'Available',
              'facility-equipment-card-status--unavailable': resolveStatus(equipmentRecord) !== 'Available',
            }"
          >
            {{ resolveStatus(equipmentRecord) }}
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
        </dl>

        <button
          type="button"
          class="facility-equipment-card-button"
          @click="openDetails(equipmentRecord)"
        >
          View Details
        </button>
      </div>
    </article>

    <div v-if="filteredEquipmentRecords.length === 0" class="facility-equipment-empty-state">
      No equipment found.
    </div>
  </div>

  <div
    v-if="selectedEquipment"
    class="facility-equipment-modal-overlay"
    @click.self="closeDetails"
  >
    <section class="facility-equipment-modal">
      <header class="facility-equipment-modal-header">
        <div>
          <p class="facility-equipment-modal-eyebrow">Equipment Details</p>
          <h2>{{ resolveTextValue(selectedEquipment.equipmentName) }}</h2>
        </div>
        <button
          type="button"
          class="facility-equipment-modal-close"
          aria-label="Close equipment details"
          @click="closeDetails"
        >
          ×
        </button>
      </header>

      <div class="facility-equipment-modal-content">
        <div class="facility-equipment-modal-media">
          <img
            :src="resolveImageUrl(selectedEquipment)"
            :alt="`${resolveTextValue(selectedEquipment.equipmentName)} photo`"
            class="facility-equipment-modal-image"
            @error="handleImageError(selectedEquipment.equipmentIdentifier)"
          />
        </div>

        <dl class="facility-equipment-modal-details">
          <div><dt>Equipment Name</dt><dd>{{ resolveTextValue(selectedEquipment.equipmentName) }}</dd></div>
          <div><dt>Equipment Type</dt><dd>{{ resolveTextValue(selectedEquipment.equipmentCategory || selectedEquipment.categoryName) }}</dd></div>
          <div><dt>Equipment Brand</dt><dd>{{ resolveTextValue(selectedEquipment.equipmentBrand) }}</dd></div>
          <div><dt>Available Quantity</dt><dd>{{ resolveQuantityValue(selectedEquipment.availableQuantity) }}</dd></div>
          <div><dt>Status</dt><dd>{{ resolveStatus(selectedEquipment) }}</dd></div>
          <div><dt>Barcode</dt><dd>{{ resolveTextValue(selectedEquipment.barcode) }}</dd></div>
          <div><dt>Asset ID</dt><dd>{{ resolveTextValue(selectedEquipment.assetId) }}</dd></div>
          <div class="facility-equipment-modal-details--full">
            <dt>Description</dt>
            <dd>{{ resolveTextValue(selectedEquipment.description || selectedEquipment.scheduleDescription) }}</dd>
          </div>
        </dl>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const PLACEHOLDER_IMAGE = `data:image/svg+xml;utf8,${encodeURIComponent(`
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 480 320">
    <defs>
      <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="#eff6f0"/>
        <stop offset="100%" stop-color="#dcefe1"/>
      </linearGradient>
    </defs>
    <rect width="480" height="320" fill="url(#g)"/>
    <rect x="66" y="56" width="348" height="208" rx="24" fill="#ffffff" stroke="#b7d4c0" stroke-width="6"/>
    <circle cx="168" cy="138" r="28" fill="#d3ead8"/>
    <path d="M114 228l68-62 46 44 58-70 80 88H114z" fill="#bfe1c8"/>
    <text x="240" y="286" text-anchor="middle" font-family="Arial, sans-serif" font-size="28" font-weight="700" fill="#386641">No Photo</text>
  </svg>
`)}`;

const props = defineProps({
  equipmentRecords: {
    type: Array,
    required: true,
  },
  availabilityFilter: {
    type: String,
    required: false,
    default: 'all',
  },
});

const selectedEquipment = ref(null);
const failedImageIdentifiers = ref({});

const filteredEquipmentRecords = computed(() => {
  const records = props.equipmentRecords || [];
  if (props.availabilityFilter === 'all') {
    return records;
  }

  const isAvailableFilter = props.availabilityFilter === 'available';
  return records.filter((record) => (resolveStatus(record) === 'Available') === isAvailableFilter);
});

function openDetails(equipmentRecord) {
  selectedEquipment.value = equipmentRecord;
}

function closeDetails() {
  selectedEquipment.value = null;
}

function resolveImageUrl(equipmentRecord) {
  const recordIdentifier = equipmentRecord?.equipmentIdentifier;
  if (!recordIdentifier || failedImageIdentifiers.value[recordIdentifier]) {
    return PLACEHOLDER_IMAGE;
  }

  const imageUrl = String(equipmentRecord?.imageUrl || '').trim();
  return imageUrl === '' ? PLACEHOLDER_IMAGE : imageUrl;
}

function handleImageError(equipmentIdentifier) {
  if (!equipmentIdentifier || failedImageIdentifiers.value[equipmentIdentifier]) {
    return;
  }

  failedImageIdentifiers.value = {
    ...failedImageIdentifiers.value,
    [equipmentIdentifier]: true,
  };
}

function resolveTextValue(value) {
  const normalizedValue = String(value || '').trim();
  return normalizedValue === '' ? 'N/A' : normalizedValue;
}

function resolveQuantityValue(value) {
  return Number.isFinite(Number(value)) ? Number(value) : 'N/A';
}

function resolveStatus(equipmentRecord) {
  return resolveTextValue(equipmentRecord?.operationalStatus || equipmentRecord?.equipmentState);
}
</script>

<style scoped>
.facility-equipment-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1rem;
}

.facility-equipment-card {
  display: grid;
  grid-template-rows: 180px 1fr;
  overflow: hidden;
  background: #ffffff;
  border: 1px solid #d8e4dd;
  border-radius: 18px;
  box-shadow: 0 18px 38px rgba(15, 23, 42, 0.08);
}

.facility-equipment-card--available {
  border-color: #a9d4b8;
}

.facility-equipment-card--unavailable {
  border-color: #d7c2c2;
}

.facility-equipment-card-media {
  background: linear-gradient(135deg, #f6fbf7 0%, #ebf5ee 100%);
}

.facility-equipment-card-image,
.facility-equipment-modal-image {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.facility-equipment-card-body {
  display: grid;
  gap: 1rem;
  padding: 1rem;
}

.facility-equipment-card-header {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  align-items: flex-start;
}

.facility-equipment-card-name {
  margin: 0;
  color: #132a1d;
  font-size: 1rem;
  font-weight: 800;
}

.facility-equipment-card-type {
  margin: 0.35rem 0 0;
  color: #567061;
  font-size: 0.85rem;
  font-weight: 600;
}

.facility-equipment-card-status {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 98px;
  min-height: 32px;
  padding: 0 0.8rem;
  border-radius: 999px;
  font-size: 0.75rem;
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
  gap: 0.75rem;
  margin: 0;
}

.facility-equipment-card-details div,
.facility-equipment-modal-details div {
  padding: 0.8rem 0.9rem;
  background: #f7faf8;
  border: 1px solid #e4ede8;
  border-radius: 12px;
}

.facility-equipment-card-details dt,
.facility-equipment-modal-details dt {
  margin-bottom: 0.35rem;
  color: #6a7d72;
  font-size: 0.74rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.facility-equipment-card-details dd,
.facility-equipment-modal-details dd {
  margin: 0;
  color: #173321;
  font-size: 0.92rem;
  font-weight: 700;
}

.facility-equipment-card-button {
  min-height: 42px;
  border: none;
  border-radius: 12px;
  background: #1a6e3a;
  color: #ffffff;
  font: inherit;
  font-weight: 800;
  cursor: pointer;
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

.facility-equipment-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: rgba(15, 23, 42, 0.56);
}

.facility-equipment-modal {
  width: min(900px, 100%);
  background: #ffffff;
  border: 1px solid #d8e4dd;
  border-radius: 24px;
  box-shadow: 0 28px 70px rgba(15, 23, 42, 0.26);
  overflow: hidden;
}

.facility-equipment-modal-header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-start;
  padding: 1.25rem 1.4rem;
  border-bottom: 1px solid #e6efea;
}

.facility-equipment-modal-eyebrow {
  margin: 0 0 0.35rem;
  color: #5a7c67;
  font-size: 0.74rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.facility-equipment-modal-header h2 {
  margin: 0;
  color: #132a1d;
  font-size: 1.4rem;
}

.facility-equipment-modal-close {
  width: 40px;
  height: 40px;
  border: 1px solid #d1ddd5;
  border-radius: 999px;
  background: #ffffff;
  color: #54665c;
  font-size: 1.75rem;
  line-height: 1;
  cursor: pointer;
}

.facility-equipment-modal-content {
  display: grid;
  grid-template-columns: minmax(260px, 320px) 1fr;
}

.facility-equipment-modal-media {
  min-height: 320px;
  background: linear-gradient(135deg, #f6fbf7 0%, #ebf5ee 100%);
}

.facility-equipment-modal-details {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem;
  margin: 0;
  padding: 1.4rem;
}

.facility-equipment-modal-details--full {
  grid-column: 1 / -1;
}

@media (max-width: 820px) {
  .facility-equipment-modal-content {
    grid-template-columns: 1fr;
  }

  .facility-equipment-modal-media {
    min-height: 240px;
  }
}

@media (max-width: 640px) {
  .facility-equipment-card-details,
  .facility-equipment-modal-details {
    grid-template-columns: 1fr;
  }

  .facility-equipment-card-header {
    flex-direction: column;
  }
}
</style>
