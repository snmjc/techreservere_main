<!-- ===== AI GENERATED: FacilityEquipmentGridComponent ===== -->
<template>
  <div class="facility-equipment-grid">
    <span
      v-for="equipmentCategory in filteredEquipmentCategories"
      :key="equipmentCategory.categoryName"
      class="facility-equipment-chip"
      :class="{
        'facility-equipment-chip--available': equipmentCategory.categoryAvailable,
        'facility-equipment-chip--unavailable': !equipmentCategory.categoryAvailable,
      }"
    >
      {{ equipmentCategory.categoryName }}
    </span>
    <div v-if="filteredEquipmentCategories.length === 0" class="facility-equipment-empty-state">
      No equipment found.
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

/**
 * @typedef {Object} FacilityEquipmentGridProps
 * @property {Array<Object>} equipmentCategories - Array of equipment category records
 * @property {string} availabilityFilter - 'all', 'available', or 'unavailable'
 */
const props = defineProps({
  equipmentCategories: {
    type: Array,
    required: true,
  },
  availabilityFilter: {
    type: String,
    required: false,
    default: 'all',
  },
});

/**
 * @function filteredEquipmentCategories
 * @description Filters equipment categories based on the availability filter.
 * @returns {Array<Object>}
 */
const filteredEquipmentCategories = computed(() => {
  if (props.availabilityFilter === 'all') {
    return props.equipmentCategories;
  }
  const isAvailableFilter = props.availabilityFilter === 'available';
  return props.equipmentCategories.filter(
    (category) => category.categoryAvailable === isAvailableFilter
  );
});
</script>
