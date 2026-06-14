<template>
  <AdminSidebarLayoutComponent
    :role-label="userFullName"
    :navigation-items="borrowerNavigationItems"
  >
    <section class="past-records-page">
      <header class="past-records-hero">
        <div class="past-records-hero-copy">
          <p class="past-records-kicker">Personal Archive</p>
          <h1>Past Reservation Records</h1>
          <p>Track the outcome of your previous reservations, review details quickly, and revisit completed or closed requests.</p>
        </div>
        <div class="past-records-hero-badge">
          <strong>{{ filteredRecordList.length }}</strong>
          <span>Visible entries</span>
        </div>
      </header>

      <section class="past-records-stats">
        <article
          v-for="card in summaryCards"
          :key="card.label"
          class="past-records-stat-card"
          :class="`past-records-stat-card--${card.tone}`"
        >
          <span class="past-records-stat-icon">{{ card.icon }}</span>
          <div>
            <p>{{ card.label }}</p>
            <strong>{{ card.value }}</strong>
            <small>{{ card.caption }}</small>
          </div>
        </article>
      </section>

      <section class="past-records-shell">
        <div class="past-records-main">
          <div class="past-records-toolbar">
            <label class="past-records-search-field">
              <span>Search</span>
              <input
                v-model.trim="searchQueryText"
                type="search"
                placeholder="Search by ID, facility, type, or status"
              />
            </label>

            <div class="past-records-toolbar-controls">
              <label>
                <span>Sort by</span>
                <select v-model="orderByValue">
                  <option value="date">Requested date</option>
                  <option value="facility">Facility</option>
                  <option value="status">Status</option>
                </select>
              </label>
              <label>
                <span>Showing</span>
                <select v-model="showingFilterValue">
                  <option value="all">All</option>
                  <option value="10">10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                </select>
              </label>
              <button
                class="past-records-sort-toggle"
                type="button"
                :title="sortOrder === 'asc' ? 'Sort descending' : 'Sort ascending'"
                @click="toggleSortOrder"
              >
                {{ sortOrder === 'asc' ? 'Asc' : 'Desc' }}
              </button>
            </div>
          </div>

          <div class="past-records-filter-strip">
            <button
              v-for="tab in recordTabs"
              :key="tab.value"
              type="button"
              class="past-records-filter-pill"
              :class="{ 'past-records-filter-pill--active': activeRecordTab === tab.value }"
              @click="activeRecordTab = tab.value"
            >
              {{ tab.label }}
              <span>{{ tab.count }}</span>
            </button>
          </div>

          <div class="past-records-table-card">
            <table class="past-records-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Facility</th>
                  <th>Type</th>
                  <th>Qty</th>
                  <th>Requested</th>
                  <th>Needed</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading">
                  <td colspan="8" class="past-records-empty-state">Loading archived reservations...</td>
                </tr>
                <tr v-else-if="filteredRecordList.length === 0">
                  <td colspan="8" class="past-records-empty-state">No past records match the current filters.</td>
                </tr>
                <tr
                  v-for="record in filteredRecordList"
                  v-else
                  :key="record.requestIdentifier"
                  class="past-records-row"
                  :class="{ 'past-records-row--active': selectedRecord?.requestIdentifier === record.requestIdentifier }"
                  @click="selectRecord(record)"
                >
                  <td class="past-records-id">{{ record.requestIdentifier }}</td>
                  <td>
                    <div class="past-records-facility">
                      <span class="past-records-facility-mark">{{ record.facilityName.charAt(0) }}</span>
                      <div>
                        <strong>{{ record.facilityName }}</strong>
                        <span>{{ record.requestPurpose }}</span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="past-records-type-pill" :class="`past-records-type-pill--${getTypeTone(record.requestType)}`">
                      {{ record.requestType }}
                    </span>
                  </td>
                  <td>{{ record.requestQuantity }}</td>
                  <td>{{ formatDate(record.requestedDate) }}</td>
                  <td>{{ formatDate(record.neededDate) }}</td>
                  <td>
                    <span class="past-records-status-pill" :class="`past-records-status-pill--${getStatusTone(record.recordStatus)}`">
                      {{ record.recordStatus }}
                    </span>
                  </td>
                  <td>
                    <button type="button" class="past-records-view-button" @click.stop="selectRecord(record)">View</button>
                  </td>
                </tr>
              </tbody>
            </table>

            <div class="past-records-table-footer">
              <span>Showing {{ filteredRecordList.length }} of {{ pastRecordsList.length }} records</span>
            </div>
          </div>
        </div>

        <aside class="past-records-detail-card" v-if="selectedRecord">
          <div class="past-records-detail-head">
            <div>
              <p>Selected Record</p>
              <h2>{{ selectedRecord.requestIdentifier }}</h2>
            </div>
            <span class="past-records-status-pill" :class="`past-records-status-pill--${getStatusTone(selectedRecord.recordStatus)}`">
              {{ selectedRecord.recordStatus }}
            </span>
          </div>

          <div class="past-records-detail-grid">
            <div>
              <span>Facility</span>
              <strong>{{ selectedRecord.facilityName }}</strong>
              <small>{{ selectedRecord.requestType }}</small>
            </div>
            <div>
              <span>Quantity</span>
              <strong>{{ selectedRecord.requestQuantity }}</strong>
              <small>Requested units</small>
            </div>
            <div>
              <span>Requested</span>
              <strong>{{ formatDate(selectedRecord.requestedDate) }}</strong>
              <small>Submission date</small>
            </div>
            <div>
              <span>Needed</span>
              <strong>{{ formatDate(selectedRecord.neededDate) }}</strong>
              <small>Event schedule</small>
            </div>
          </div>

          <div class="past-records-detail-section">
            <h3>Reservation Information</h3>
            <dl>
              <div>
                <dt>Requester</dt>
                <dd>{{ selectedRecord.requesterFullName }}</dd>
              </div>
              <div>
                <dt>Role</dt>
                <dd>{{ selectedRecord.requesterRole }}</dd>
              </div>
              <div>
                <dt>Purpose</dt>
                <dd>{{ selectedRecord.requestPurpose }}</dd>
              </div>
              <div>
                <dt>Remarks</dt>
                <dd>{{ selectedRecord.remarks }}</dd>
              </div>
            </dl>
          </div>

          <div class="past-records-detail-section">
            <h3>Timeline</h3>
            <ul class="past-records-timeline">
              <li v-for="entry in buildTimeline(selectedRecord)" :key="entry.label">
                <span class="past-records-timeline-dot" />
                <div>
                  <strong>{{ entry.label }}</strong>
                  <small>{{ entry.date }}</small>
                  <p>{{ entry.note }}</p>
                </div>
              </li>
            </ul>
          </div>
        </aside>
      </section>

      <div class="past-records-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </div>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore';
import '@/shared/components/adminSidebarLayout.css';
import './css/PastRecords.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';

const authStore = useAuthenticationStore();
const activeRecordTab = ref('all');
const searchQueryText = ref('');
const showingFilterValue = ref('all');
const sortOrder = ref('desc');
const orderByValue = ref('date');
const loading = ref(false);
const selectedRecord = ref(null);

const pastRecordsList = ref([]);
const userFullName = computed(() => authStore.userFullName || 'USER');

const mockPastRecords = [
  {
    requestIdentifier: 'RES-001',
    requesterFullName: 'Juan Dela Cruz',
    requesterRole: 'Student',
    requestedDate: '2024-05-01',
    neededDate: '2024-05-15',
    facilityName: 'Classroom A',
    requestQuantity: 1,
    requestType: 'Venue',
    requestPurpose: 'Department presentation',
    recordStatus: 'Completed',
    remarks: 'Reservation was completed successfully.',
  },
  {
    requestIdentifier: 'RES-002',
    requesterFullName: 'Juan Dela Cruz',
    requesterRole: 'Student',
    requestedDate: '2024-04-28',
    neededDate: '2024-05-10',
    facilityName: 'Multipurpose Room',
    requestQuantity: 2,
    requestType: 'Equipment',
    requestPurpose: 'Organization workshop',
    recordStatus: 'Completed',
    remarks: 'Equipment was issued and returned on time.',
  },
  {
    requestIdentifier: 'RES-003',
    requesterFullName: 'Juan Dela Cruz',
    requesterRole: 'Student',
    requestedDate: '2024-04-20',
    neededDate: '2024-05-05',
    facilityName: 'Projector',
    requestQuantity: 1,
    requestType: 'Equipment',
    requestPurpose: 'Capstone consultation',
    recordStatus: 'Rejected',
    remarks: 'Requested item was unavailable during the schedule.',
  },
  {
    requestIdentifier: 'RES-004',
    requesterFullName: 'Juan Dela Cruz',
    requesterRole: 'Student',
    requestedDate: '2024-04-15',
    neededDate: '2024-04-25',
    facilityName: 'Classroom B',
    requestQuantity: 1,
    requestType: 'Venue',
    requestPurpose: 'Study session',
    recordStatus: 'Cancelled',
    remarks: 'Reservation was cancelled before approval.',
  },
  {
    requestIdentifier: 'RES-005',
    requesterFullName: 'Juan Dela Cruz',
    requesterRole: 'Student',
    requestedDate: '2024-04-10',
    neededDate: '2024-04-22',
    facilityName: 'Conference Room',
    requestQuantity: 3,
    requestType: 'Venue + Equipment',
    requestPurpose: 'Panel discussion',
    recordStatus: 'Completed',
    remarks: 'Venue and supporting items were prepared successfully.',
  },
  {
    requestIdentifier: 'RES-006',
    requesterFullName: 'Juan Dela Cruz',
    requesterRole: 'Student',
    requestedDate: '2024-04-05',
    neededDate: '2024-04-18',
    facilityName: 'LED Screen',
    requestQuantity: 2,
    requestType: 'Equipment',
    requestPurpose: 'Multimedia showcase',
    recordStatus: 'Rejected',
    remarks: 'Technical inventory was reserved for another event.',
  },
  {
    requestIdentifier: 'RES-007',
    requesterFullName: 'Juan Dela Cruz',
    requesterRole: 'Student',
    requestedDate: '2024-03-28',
    neededDate: '2024-04-10',
    facilityName: 'Auditorium',
    requestQuantity: 1,
    requestType: 'Venue',
    requestPurpose: 'General assembly',
    recordStatus: 'Completed',
    remarks: 'Reservation proceeded as scheduled.',
  },
  {
    requestIdentifier: 'RES-008',
    requesterFullName: 'Juan Dela Cruz',
    requesterRole: 'Student',
    requestedDate: '2024-03-20',
    neededDate: '2024-04-02',
    facilityName: 'Microphone Set',
    requestQuantity: 1,
    requestType: 'Equipment',
    requestPurpose: 'Speech practice',
    recordStatus: 'Cancelled',
    remarks: 'Requester withdrew the reservation before release.',
  },
];

onMounted(async () => {
  await loadPastRecords();
});

async function loadPastRecords() {
  loading.value = true;
  try {
    pastRecordsList.value = mockPastRecords;
  } catch (error) {
    console.error('Error loading past records:', error);
    pastRecordsList.value = mockPastRecords;
  } finally {
    loading.value = false;
  }
}

const summaryCards = computed(() => {
  const total = pastRecordsList.value.length || 1;
  const completed = pastRecordsList.value.filter((record) => record.recordStatus === 'Completed').length;
  const rejected = pastRecordsList.value.filter((record) => record.recordStatus === 'Rejected').length;
  const cancelled = pastRecordsList.value.filter((record) => record.recordStatus === 'Cancelled').length;

  return [
    { label: 'Total Records', value: pastRecordsList.value.length, caption: 'All archived requests', icon: '▦', tone: 'total' },
    { label: 'Completed', value: completed, caption: `${Math.round((completed / total) * 100)}% completed`, icon: '✓', tone: 'completed' },
    { label: 'Rejected', value: rejected, caption: `${Math.round((rejected / total) * 100)}% rejected`, icon: '×', tone: 'rejected' },
    { label: 'Cancelled', value: cancelled, caption: `${Math.round((cancelled / total) * 100)}% cancelled`, icon: '!', tone: 'cancelled' },
  ];
});

const recordTabs = computed(() => [
  { label: 'All', value: 'all', count: pastRecordsList.value.length },
  { label: 'Completed', value: 'completed', count: pastRecordsList.value.filter((record) => record.recordStatus === 'Completed').length },
  { label: 'Rejected', value: 'rejected', count: pastRecordsList.value.filter((record) => record.recordStatus === 'Rejected').length },
  { label: 'Cancelled', value: 'cancelled', count: pastRecordsList.value.filter((record) => record.recordStatus === 'Cancelled').length },
]);

function toggleSortOrder() {
  sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
}

const filteredRecordList = computed(() => {
  let recordsFiltered = [...(pastRecordsList.value || [])];

  if (activeRecordTab.value !== 'all') {
    const tabStatusMap = {
      completed: 'Completed',
      rejected: 'Rejected',
      cancelled: 'Cancelled',
    };
    recordsFiltered = recordsFiltered.filter((record) => record.recordStatus === tabStatusMap[activeRecordTab.value]);
  }

  const queryLower = searchQueryText.value.toLowerCase();
  if (queryLower) {
    recordsFiltered = recordsFiltered.filter((record) =>
      [
        record.requestIdentifier,
        record.facilityName,
        record.requestType,
        record.recordStatus,
        record.requestPurpose,
      ].some((value) => String(value).toLowerCase().includes(queryLower))
    );
  }

  recordsFiltered.sort((first, second) => {
    const firstValue = resolveSortValue(first);
    const secondValue = resolveSortValue(second);

    if (firstValue < secondValue) return sortOrder.value === 'asc' ? -1 : 1;
    if (firstValue > secondValue) return sortOrder.value === 'asc' ? 1 : -1;
    return String(first.requestIdentifier).localeCompare(String(second.requestIdentifier));
  });

  if (showingFilterValue.value !== 'all') {
    recordsFiltered = recordsFiltered.slice(0, Number(showingFilterValue.value));
  }

  return recordsFiltered;
});

watch(
  filteredRecordList,
  (records) => {
    if (records.length === 0) {
      selectedRecord.value = null;
      return;
    }

    if (!selectedRecord.value) {
      selectedRecord.value = records[0];
      return;
    }

    const matched = records.find((record) => record.requestIdentifier === selectedRecord.value.requestIdentifier);
    selectedRecord.value = matched || records[0];
  },
  { immediate: true }
);

function resolveSortValue(record) {
  if (orderByValue.value === 'facility') return record.facilityName.toLowerCase();
  if (orderByValue.value === 'status') return record.recordStatus.toLowerCase();
  return new Date(record.requestedDate).getTime();
}

function selectRecord(record) {
  selectedRecord.value = record;
}

function getTypeTone(type) {
  const normalized = String(type).toLowerCase();
  if (normalized.includes('venue') && normalized.includes('equipment')) return 'mixed';
  if (normalized.includes('equipment')) return 'equipment';
  return 'venue';
}

function getStatusTone(status) {
  const normalized = String(status).toLowerCase();
  if (normalized === 'completed') return 'completed';
  if (normalized === 'rejected') return 'rejected';
  return 'cancelled';
}

function formatDate(value) {
  return new Date(value).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

function buildTimeline(record) {
  return [
    {
      label: 'Request Submitted',
      date: formatDate(record.requestedDate),
      note: 'Your reservation request was submitted successfully.',
    },
    {
      label: 'Needed Schedule',
      date: formatDate(record.neededDate),
      note: `${record.facilityName} was requested for this schedule.`,
    },
    {
      label: record.recordStatus,
      date: formatDate(record.neededDate),
      note: record.remarks,
    },
  ];
}
</script>
