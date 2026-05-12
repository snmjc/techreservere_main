<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <!-- Page Header -->
    <div class="equipment-page-header">
      <h2 class="equipment-page-heading">Facilities</h2>
    </div>

    <!-- Tabs Section -->
    <div class="equipment-tabs">
      <button
        class="equipment-tab"
        :class="{ 'equipment-tab--active': activeTab === 'venue' }"
        @click="activeTab = 'venue'"
      >
        Venue
      </button>
      <button
        class="equipment-tab"
        :class="{ 'equipment-tab--active': activeTab === 'equipment' }"
        @click="activeTab = 'equipment'"
      >
        Equipment
      </button>
    </div>

    <!-- Equipment Tab Content -->
    <div v-if="activeTab === 'equipment'" class="equipment-content">
      <!-- Toolbar -->
      <div class="equipment-toolbar">
        <div class="equipment-toolbar-left">
          <button class="equipment-edit-button" @click="handleEditEquipment">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
            Edit Equipment
          </button>
          <button class="equipment-add-button" @click="handleAddEquipment">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Add Equipment
          </button>
        </div>
        <div class="equipment-filter-group">
          <label for="equipmentFilter" class="equipment-filter-label">Showing:</label>
          <select v-model="filterValue" id="equipmentFilter" class="equipment-filter-select">
            <option value="all">All</option>
            <option value="available">Available</option>
            <option value="unavailable">Unavailable</option>
          </select>
        </div>
      </div>

      <!-- Legend -->
      <div class="equipment-legend">
        <span class="equipment-legend-item">
          <span class="equipment-legend-dot equipment-legend-dot--available"></span>
          Available
        </span>
        <span class="equipment-legend-item">
          <span class="equipment-legend-dot equipment-legend-dot--unavailable"></span>
          Unavailable
        </span>
      </div>

      <!-- Equipment Grid -->
      <div class="equipment-grid">
        <div
          v-for="equipment in filteredEquipment"
          :key="equipment.equipmentIdentifier"
          class="equipment-chip"
          :class="{
            'equipment-chip--available': equipment.equipmentAvailable,
            'equipment-chip--unavailable': !equipment.equipmentAvailable,
          }"
          @click="handleEquipmentClick(equipment)"
        >
          <span class="equipment-chip-name">{{ equipment.equipmentName }}</span>
        </div>
      </div>

      <div v-if="filteredEquipment.length === 0" class="equipment-empty-state">
        <p>No equipment found matching your filter.</p>
      </div>
    </div>

    <!-- Venue Tab Content -->
    <div v-if="activeTab === 'venue'" class="equipment-content">
      <p class="equipment-placeholder">Venue management is available in the Manage Facilities section.</p>
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/Equipment.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';

const activeTab = ref('equipment');
const filterValue = ref('all');

// Mock equipment data
const equipmentList = ref([
  { equipmentIdentifier: 1, equipmentName: 'Chairs', equipmentAvailable: true, quantity: 200 },
  { equipmentIdentifier: 2, equipmentName: 'Tables', equipmentAvailable: true, quantity: 50 },
  { equipmentIdentifier: 3, equipmentName: 'Podium', equipmentAvailable: true, quantity: 5 },
  { equipmentIdentifier: 4, equipmentName: 'Microphone', equipmentAvailable: true, quantity: 10 },
  { equipmentIdentifier: 5, equipmentName: 'AUX Cord', equipmentAvailable: true, quantity: 20 },
  { equipmentIdentifier: 6, equipmentName: 'Sound System', equipmentAvailable: false, quantity: 0 },
  { equipmentIdentifier: 7, equipmentName: 'Extension Cord', equipmentAvailable: true, quantity: 15 },
  { equipmentIdentifier: 8, equipmentName: 'Stage', equipmentAvailable: false, quantity: 0 },
  { equipmentIdentifier: 9, equipmentName: 'Panel Board', equipmentAvailable: true, quantity: 8 },
  { equipmentIdentifier: 10, equipmentName: 'White Screen', equipmentAvailable: true, quantity: 12 },
  { equipmentIdentifier: 11, equipmentName: 'Philippine Flag', equipmentAvailable: false, quantity: 0 },
  { equipmentIdentifier: 12, equipmentName: 'FEU Tech Flag', equipmentAvailable: true, quantity: 6 },
  { equipmentIdentifier: 13, equipmentName: 'LED Video Wall', equipmentAvailable: true, quantity: 2 },
  { equipmentIdentifier: 14, equipmentName: 'Others', equipmentAvailable: true, quantity: 5 },
]);

const filteredEquipment = computed(() => {
  if (filterValue.value === 'all') {
    return equipmentList.value;
  }
  const isAvailableFilter = filterValue.value === 'available';
  return equipmentList.value.filter(
    (equipment) => equipment.equipmentAvailable === isAvailableFilter
  );
});

function handleAddEquipment() {
  console.log('Add new equipment');
  alert('Add equipment functionality coming soon');
}

function handleEditEquipment() {
  console.log('Edit equipment');
  alert('Edit equipment functionality coming soon');
}

function handleEquipmentClick(equipment) {
  console.log('Equipment clicked:', equipment);
  alert(`${equipment.equipmentName} - Available: ${equipment.equipmentAvailable}`);
}
</script>
