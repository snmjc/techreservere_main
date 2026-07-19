<template>
  <AdminSidebarLayoutComponent :role-label="'ADMINISTRATOR'" :navigation-items="adminNavigationItems">
    <section class="equipment-page">
      <header class="equipment-page__header">
        <div>
          <p class="equipment-page__eyebrow">Admin Oversight</p>
          <h1 class="equipment-page__title">Audit Trail</h1>
          <p class="equipment-page__subtitle">Review equipment, venue, and reservation record activity with exact timestamps, while keeping account records excluded.</p>
        </div>
      </header>

      <section class="equipment-page__controls">
        <label class="equipment-page__search">
          <span class="equipment-page__label">Search</span>
          <input v-model.trim="filters.search" type="text" placeholder="Actor, record, or reason" />
        </label>
        <label class="equipment-page__filter">
          <span class="equipment-page__label">Scope</span>
          <select v-model="selectedAuditScope">
            <option value="both">Both</option>
            <option value="equipment">Equipment</option>
            <option value="venue">Venue</option>
            <option value="reservations">Reservations</option>
          </select>
        </label>
        <button class="equipment-page__ghost-button" type="button" @click="loadAuditLogs">Refresh</button>
      </section>

      <p v-if="errorMessage" class="equipment-page__feedback equipment-page__feedback--error">{{ errorMessage }}</p>

      <div class="equipment-page__table-wrap">
        <table class="equipment-page__table">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Actor</th>
              <th>Module</th>
              <th>Record</th>
              <th>Reason</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="isLoading">
              <td colspan="5">Loading audit logs...</td>
            </tr>
            <tr v-else-if="auditLogs.length === 0">
              <td colspan="5">No audit entries match the current filters.</td>
            </tr>
            <tr v-for="auditLog in auditLogs" :key="auditLog.auditLogIdentifier" v-else>
              <td>{{ formatDateTime(auditLog.occurredTimestamp) }}</td>
              <td>{{ auditLog.actorName || auditLog.performedByAccountId || 'System' }}</td>
              <td>{{ auditLog.module || 'General' }}</td>
              <td>{{ auditLog.targetDisplayLabel || `${auditLog.targetEntityType} #${auditLog.targetEntityIdentifier || ''}` }}</td>
              <td>{{ auditLog.reason || 'No reason recorded' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import auditLogApi from '@/modules/audit/services/auditLogApi.js';

const auditLogs = ref([]);
const errorMessage = ref('');
const isLoading = ref(false);
const selectedAuditScope = ref('both');
const filters = reactive({
  search: '',
});

onMounted(() => {
  loadAuditLogs();
});
watch(selectedAuditScope, () => {
  loadAuditLogs();
});

async function loadAuditLogs() {
  try {
    isLoading.value = true;
    errorMessage.value = '';
    const response = await auditLogApi.listAuditLogs({
      ...filters,
      scope: selectedAuditScope.value,
    });
    auditLogs.value = Array.isArray(response?.data?.auditLogs)
      ? response.data.auditLogs
      : [];
  } catch (error) {
    errorMessage.value = error?.response?.data?.errorMessage || 'Unable to load audit logs right now.';
  } finally {
    isLoading.value = false;
  }
}

function formatDateTime(value) {
  if (!value) return 'N/A';
  const parsedDate = new Date(value);
  if (Number.isNaN(parsedDate.getTime())) return 'N/A';
  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    second: '2-digit',
    hour12: true,
    timeZone: 'Asia/Manila',
    timeZoneName: 'short',
  }).format(parsedDate);
}
</script>

<style scoped>
@import './css/Equipment.css';
</style>
