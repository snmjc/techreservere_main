<!-- ===== AI GENERATED: BorrowerActiveReservationsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="userFullName"
    :navigation-items="borrowerNavigationItems"
  >
    <section class="borrower-active-page">
      <div class="borrower-active-shell">
        <header class="borrower-active-header">
          <div>
            <h1>Active Reservations</h1>
          </div>
          <button class="borrower-active-back-link" type="button" @click="navigateBackToMyReservations">Go Back</button>
        </header>

        <div class="borrower-active-toolbar">
          <div class="borrower-active-toolbar-group">
            <label for="borrowerActiveSearch">Search:</label>
            <input
              id="borrowerActiveSearch"
              v-model="searchQueryText"
              type="text"
              class="borrower-active-search-input"
              placeholder="Name"
            />
          </div>

          <div class="borrower-active-toolbar-group borrower-active-toolbar-group--filters">
            <label for="borrowerActiveShowing">Showing:</label>
            <select id="borrowerActiveShowing" v-model="showingFilterValue" class="borrower-active-showing-select">
              <option value="all">All</option>
            </select>
            <button class="borrower-active-sort-button" type="button" aria-label="Sort active reservations">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 7h14" />
                <path d="M3 12h10" />
                <path d="M3 17h6" />
                <path d="m17 15 4 4 4-4" transform="translate(-1 -2)" />
              </svg>
            </button>
          </div>
        </div>

        <div class="borrower-active-table-card">
          <div class="borrower-active-table-scroll">
            <table class="borrower-active-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Role</th>
                  <th>Schedule</th>
                  <th>Facility</th>
                  <th>Quantity</th>
                  <th>Type</th>
                  <th>Purpose</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody v-if="filteredRecordList.length > 0">
                <tr
                  v-for="record in filteredRecordList"
                  :key="record.requestIdentifier + record.requesterFullName"
                >
                  <td class="borrower-active-cell borrower-active-cell--id">
                    {{ record.requestDisplayIdentifier || record.requestIdentifier }}
                  </td>
                  <td class="borrower-active-cell borrower-active-cell--name">
                    <strong>{{ record.requesterFullName }}</strong>
                  </td>
                  <td class="borrower-active-cell borrower-active-cell--role">
                    {{ record.requesterRole }}
                  </td>
                  <td class="borrower-active-cell borrower-active-cell--schedule">
                    <div class="borrower-active-schedule-stack">
                      <span>
                        <b>Requested</b>
                        {{ formatRequestedDate(record.requestedDate) }}
                      </span>
                      <span>
                        <b>Scheduled</b>
                        {{ record.requestSchedule }}
                      </span>
                    </div>
                  </td>
                  <td class="borrower-active-cell borrower-active-cell--facility">
                    <div class="borrower-active-facility">
                      <img :src="buildFacilityPlaceholder(record.facilityName)" :alt="record.facilityName" />
                      <span>{{ record.facilityName }}</span>
                    </div>
                  </td>
                  <td class="borrower-active-cell borrower-active-cell--quantity">
                    {{ formatQuantity(record.requestQuantity) }}
                  </td>
                  <td class="borrower-active-cell borrower-active-cell--type">
                    <span class="borrower-active-type-pill" :class="getTypeBadgeClass(record.requestType)">
                      {{ record.requestType }}
                    </span>
                  </td>
                  <td class="borrower-active-cell borrower-active-cell--purpose">
                    {{ record.requestPurpose }}
                  </td>
                  <td class="borrower-active-cell borrower-active-cell--status">
                    <span class="borrower-active-status-pill" :class="getStatusBadgeClass(record.requestStatus)">
                      {{ record.requestStatus || 'Active' }}
                    </span>
                  </td>
                </tr>
              </tbody>
              <tbody v-else>
                <tr>
                  <td colspan="9" class="borrower-active-empty-state">
                    {{ loading ? 'Loading active reservations...' : 'No active reservations.' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/ActiveReservations.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';

const router = useRouter();
const requestStore = useRequestStore();
const authStore = useAuthenticationStore();
const loading = ref(false);
const searchQueryText = ref('');
const showingFilterValue = ref('all');

const activeRecordsList = computed(() => requestStore.activeReservationsList || []);
const userFullName = computed(() => authStore.userFullName);

onMounted(async () => {
  try {
    loading.value = true;
    await requestStore.fetchReservations();
    const list = requestStore.activeReservationsList || [];
    console.log('Borrower Active Reservations - Count:', list.length);
  } catch (error) {
    console.error('Error fetching active reservations:', error);
  } finally {
    loading.value = false;
  }
});

const filteredRecordList = computed(() => {
  const queryLower = searchQueryText.value.toLowerCase().trim();
  const list = activeRecordsList.value || [];
  if (!queryLower) return list;
  return list.filter((record) =>
    record.requesterFullName?.toLowerCase().includes(queryLower) ||
    record.requestIdentifier?.toString().includes(queryLower)
  );
});

function getTypeBadgeClass(requestType) {
  const typeLower = String(requestType || '').toLowerCase();
  if (typeLower === 'venue') return 'borrower-active-type-pill--venue';
  if (typeLower === 'equipment') return 'borrower-active-type-pill--equipment';
  return 'borrower-active-type-pill--both';
}

function getStatusBadgeClass(status) {
  const normalizedStatus = String(status || '').trim().toLowerCase();
  if (normalizedStatus === 'deployed' || normalizedStatus === 'active') {
    return 'borrower-active-status-pill--deployed';
  }

  if (normalizedStatus === 'prepared' || normalizedStatus === 'approved') {
    return 'borrower-active-status-pill--prepared';
  }

  return 'borrower-active-status-pill--default';
}

function formatRequestedDate(value) {
  if (!value || value === 'N/A') {
    return 'N/A';
  }

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return String(value);
  }

  return new Intl.DateTimeFormat('en-PH', {
    timeZone: 'Asia/Manila',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).format(parsed);
}

function formatQuantity(value) {
  const count = Number(value);
  return Number.isFinite(count) && count > 0 ? count : 'N/A';
}

function buildFacilityPlaceholder(facilityName) {
  const label = String(facilityName || 'Facility').trim().slice(0, 24);
  const svg = `
    <svg xmlns="http://www.w3.org/2000/svg" width="72" height="44" viewBox="0 0 72 44">
      <defs>
        <linearGradient id="facility-card" x1="0" x2="1" y1="0" y2="1">
          <stop offset="0%" stop-color="#b8d8cb"/>
          <stop offset="100%" stop-color="#edf7f2"/>
        </linearGradient>
      </defs>
      <rect width="72" height="44" rx="8" fill="url(#facility-card)"/>
      <rect x="8" y="23" width="56" height="13" rx="4" fill="#d7ebe2"/>
      <rect x="12" y="10" width="18" height="9" rx="3" fill="#f8fdfb"/>
      <rect x="36" y="10" width="22" height="9" rx="3" fill="#f8fdfb"/>
      <text x="36" y="40" text-anchor="middle" font-size="6" font-family="Arial, sans-serif" fill="#426458">${label}</text>
    </svg>
  `;

  return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;
}

function navigateBackToMyReservations() {
  router.push({ name: ROUTE_NAMES.borrowerMyReservations });
}
</script>
