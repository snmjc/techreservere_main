<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="admin-past-records-page">
      <header class="admin-past-records-hero">
        <div class="admin-past-records-hero-copy">
          <p class="admin-past-records-kicker">Archive Overview</p>
          <h1>Past Reservation Records</h1>
          <p>Review completed, rejected, and cancelled reservations with a cleaner at-a-glance workflow.</p>
        </div>
        <div class="admin-past-records-hero-badge">
          <strong>{{ filteredRecordList.length }}</strong>
          <span>Visible records</span>
        </div>
      </header>

      <section class="admin-past-records-stats">
        <article
          v-for="card in summaryCards"
          :key="card.label"
          class="admin-past-records-stat-card"
          :class="`admin-past-records-stat-card--${card.tone}`"
        >
          <span class="admin-past-records-stat-icon">{{ card.icon }}</span>
          <div>
            <p>{{ card.label }}</p>
            <strong>{{ card.value }}</strong>
            <small>{{ card.caption }}</small>
          </div>
        </article>
      </section>

      <section class="admin-past-records-shell">
        <div class="admin-past-records-main">
          <div class="admin-past-records-toolbar">
            <label class="admin-past-records-search-field">
              <span>Search</span>
              <input
                v-model.trim="searchQueryText"
                type="search"
                placeholder="Search by ID, borrower, facility, or type"
              />
            </label>

            <div class="admin-past-records-toolbar-controls">
              <label>
                <span>Sort by</span>
                <select v-model="sortByValue">
                  <option value="requestedDate">Requested date</option>
                  <option value="borrower">Borrower</option>
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
                class="admin-past-records-sort-toggle"
                type="button"
                :title="sortOrderAscending ? 'Sort descending' : 'Sort ascending'"
                @click="sortOrderAscending = !sortOrderAscending"
              >
                {{ sortOrderAscending ? 'Asc' : 'Desc' }}
              </button>
            </div>
          </div>

          <div class="admin-past-records-filter-strip">
            <button
              v-for="tab in recordTabs"
              :key="tab.value"
              type="button"
              class="admin-past-records-filter-pill"
              :class="{ 'admin-past-records-filter-pill--active': activeRecordTab === tab.value }"
              @click="activeRecordTab = tab.value"
            >
              {{ tab.label }}
              <span>{{ tab.count }}</span>
            </button>
          </div>

          <div class="admin-past-records-table-card">
            <table class="admin-past-records-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Borrower</th>
                  <th>Facility</th>
                  <th>Type</th>
                  <th>Qty</th>
                  <th>Requested</th>
                  <th>Needed</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="filteredRecordList.length === 0">
                  <td colspan="8" class="admin-past-records-empty-state">No past records match the current filters.</td>
                </tr>
                <tr
                  v-for="record in filteredRecordList"
                  v-else
                  :key="record.requestIdentifier"
                  class="admin-past-records-row"
                  :class="{ 'admin-past-records-row--active': selectedRecord?.requestIdentifier === record.requestIdentifier }"
                  @click="selectRecord(record)"
                >
                  <td class="admin-past-records-id">{{ record.requestIdentifier }}</td>
                  <td>
                    <div class="admin-past-records-borrower">
                      <img :src="record.borrowerAvatar" :alt="record.requesterFullName" />
                      <div>
                        <strong>{{ record.requesterFullName }}</strong>
                        <span>{{ record.requesterRole }}</span>
                      </div>
                    </div>
                  </td>
                  <td>{{ record.facilityName }}</td>
                  <td>
                    <span
                      class="admin-past-records-type-pill"
                      :class="`admin-past-records-type-pill--${getTypeTone(record.requestType)}`"
                    >
                      {{ record.requestType }}
                    </span>
                  </td>
                  <td>{{ record.requestQuantity }}</td>
                  <td>{{ formatDate(record.requestedDate) }}</td>
                  <td>{{ formatDate(record.neededDate) }}</td>
                  <td>
                    <span
                      class="admin-past-records-status-pill"
                      :class="`admin-past-records-status-pill--${getStatusTone(record.recordStatus)}`"
                    >
                      {{ record.recordStatus }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>

            <div class="admin-past-records-table-footer">
              <span>Showing {{ filteredRecordList.length }} of {{ mockPastRecords.length }} records</span>
            </div>
          </div>
        </div>

        <aside
          class="admin-past-records-detail-card"
          :class="{ 'admin-past-records-detail-card--placeholder': !selectedRecord }"
        >
          <template v-if="selectedRecord">
            <div class="admin-past-records-detail-topbar">
              <div>
                <p>Reservation Details</p>
                <h2>Reservation Details</h2>
              </div>
              <button class="admin-past-records-detail-close" type="button" @click="selectedRecord = null">x</button>
            </div>

            <div class="admin-past-records-detail-statusbar">
              <span
                class="admin-past-records-status-pill"
                :class="`admin-past-records-status-pill--${getStatusTone(selectedRecord.recordStatus)}`"
              >
                {{ selectedRecord.recordStatus }}
              </span>
              <strong>{{ selectedRecord.requestIdentifier }}</strong>
            </div>

            <div class="admin-past-records-detail-section">
              <h3>Reservation Information</h3>
              <dl class="admin-past-records-detail-list">
                <div class="admin-past-records-detail-item">
                  <dt>Borrower</dt>
                  <dd class="admin-past-records-detail-borrower">
                    <img :src="selectedRecord.borrowerAvatar" :alt="selectedRecord.requesterFullName" />
                    <span>
                      <strong>{{ selectedRecord.requesterFullName }}</strong>
                      <small>{{ selectedRecord.requesterRole }}</small>
                    </span>
                  </dd>
                </div>
                <div class="admin-past-records-detail-item">
                  <dt>Facility</dt>
                  <dd>{{ selectedRecord.facilityName }}</dd>
                </div>
                <div class="admin-past-records-detail-item">
                  <dt>Type</dt>
                  <dd>
                    <span
                      class="admin-past-records-type-pill"
                      :class="`admin-past-records-type-pill--${getTypeTone(selectedRecord.requestType)}`"
                    >
                      {{ selectedRecord.requestType }}
                    </span>
                  </dd>
                </div>
                <div class="admin-past-records-detail-item">
                  <dt>Quantity</dt>
                  <dd>{{ selectedRecord.requestQuantity }}</dd>
                </div>
                <div class="admin-past-records-detail-item">
                  <dt>Requested Date</dt>
                  <dd>{{ formatDateTime(selectedRecord.requestedDate) }}</dd>
                </div>
                <div class="admin-past-records-detail-item">
                  <dt>Needed Date</dt>
                  <dd>{{ formatDateTime(selectedRecord.neededDate) }}</dd>
                </div>
                <div class="admin-past-records-detail-item">
                  <dt>Status</dt>
                  <dd>{{ selectedRecord.recordStatus }}</dd>
                </div>
                <div class="admin-past-records-detail-item">
                  <dt>Remarks</dt>
                  <dd>{{ selectedRecord.remarks }}</dd>
                </div>
              </dl>
            </div>

            <div class="admin-past-records-detail-section">
              <h3>Timeline</h3>
              <ul class="admin-past-records-timeline">
                <li v-for="entry in buildTimeline(selectedRecord)" :key="entry.label">
                  <span class="admin-past-records-timeline-dot" />
                  <div>
                    <strong>{{ entry.label }}</strong>
                    <small>{{ entry.date }}</small>
                    <p>{{ entry.note }}</p>
                  </div>
                </li>
              </ul>
            </div>

            <button class="admin-past-records-detail-download" type="button" @click="downloadSelectedRecord">
              Download Details (PDF)
            </button>
          </template>
          <template v-else>
            <div class="admin-past-records-detail-empty">
              <p>Reservation Details</p>
              <h2>Select a record</h2>
              <span>Choose any reservation from the list to open its full details, timeline, and export action here.</span>
            </div>
          </template>
        </aside>
      </section>

      <div class="admin-past-records-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </div>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/PastRecords.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import { createTextPlaceholderDataUrl } from '@/shared/utils/mockImage.js';

const APP_FONT_STACK = "'Inter', 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif";
const requestStore = useRequestStore();
const activeRecordTab = ref('all');
const searchQueryText = ref('');
const showingFilterValue = ref('all');
const sortOrderAscending = ref(false);
const sortByValue = ref('requestedDate');
const selectedRecord = ref(null);

const mockPastRecords = ref([
  {
    requestIdentifier: 'RES-001',
    requesterFullName: 'Maria Santos',
    requesterRole: 'Faculty',
    borrowerAvatar: createTextPlaceholderDataUrl('MS'),
    requestedDate: '2026-04-15',
    neededDate: '2026-05-10',
    facilityName: '18F Roofdeck',
    requestQuantity: 150,
    requestType: 'Venue',
    requestPurpose: 'Graduation Ceremony',
    dateProcessed: '2026-05-10',
    recordStatus: 'Completed',
    remarks: 'Reservation finished successfully without incident.',
  },
  {
    requestIdentifier: 'RES-002',
    requesterFullName: 'Juan Dela Cruz',
    requesterRole: 'Staff',
    borrowerAvatar: createTextPlaceholderDataUrl('JD'),
    requestedDate: '2026-04-20',
    neededDate: '2026-05-05',
    facilityName: 'Chairs',
    requestQuantity: 200,
    requestType: 'Equipment',
    requestPurpose: 'Conference Setup',
    dateProcessed: '2026-05-05',
    recordStatus: 'Completed',
    remarks: 'Equipment released and returned complete.',
  },
  {
    requestIdentifier: 'RES-003',
    requesterFullName: 'Ana Garcia',
    requesterRole: 'Student',
    borrowerAvatar: createTextPlaceholderDataUrl('AG'),
    requestedDate: '2026-04-10',
    neededDate: '2026-04-25',
    facilityName: 'F407',
    requestQuantity: 50,
    requestType: 'Venue',
    requestPurpose: 'Club Meeting',
    dateProcessed: '2026-04-25',
    recordStatus: 'Rejected',
    remarks: 'Request conflicted with a higher-priority venue booking.',
  },
  {
    requestIdentifier: 'RES-004',
    requesterFullName: 'Pedro Reyes',
    requesterRole: 'Faculty',
    borrowerAvatar: createTextPlaceholderDataUrl('PR'),
    requestedDate: '2026-04-12',
    neededDate: '2026-05-01',
    facilityName: 'Microphone and Podium',
    requestQuantity: 5,
    requestType: 'Equipment',
    requestPurpose: 'Seminar',
    dateProcessed: '2026-05-01',
    recordStatus: 'Completed',
    remarks: 'Audio support completed on schedule.',
  },
  {
    requestIdentifier: 'RES-005',
    requesterFullName: 'Rosa Mendoza',
    requesterRole: 'Student',
    borrowerAvatar: createTextPlaceholderDataUrl('RM'),
    requestedDate: '2026-04-18',
    neededDate: '2026-05-08',
    facilityName: 'F503 and Tables',
    requestQuantity: 80,
    requestType: 'Venue + Equipment',
    requestPurpose: 'Workshop',
    dateProcessed: '2026-05-08',
    recordStatus: 'Completed',
    remarks: 'Venue handoff and equipment return both completed.',
  },
  {
    requestIdentifier: 'RES-006',
    requesterFullName: 'Carlos Lopez',
    requesterRole: 'Staff',
    borrowerAvatar: createTextPlaceholderDataUrl('CL'),
    requestedDate: '2026-04-22',
    neededDate: '2026-05-02',
    facilityName: 'LED Video Wall',
    requestQuantity: 1,
    requestType: 'Equipment',
    requestPurpose: 'Presentation',
    dateProcessed: '2026-05-02',
    recordStatus: 'Cancelled',
    remarks: 'Requester cancelled before preparation was completed.',
  },
  {
    requestIdentifier: 'RES-007',
    requesterFullName: 'Lisa Wong',
    requesterRole: 'Faculty',
    borrowerAvatar: createTextPlaceholderDataUrl('LW'),
    requestedDate: '2026-04-08',
    neededDate: '2026-04-28',
    facilityName: 'F608',
    requestQuantity: 60,
    requestType: 'Venue',
    requestPurpose: 'Exam',
    dateProcessed: '2026-04-28',
    recordStatus: 'Rejected',
    remarks: 'Room capacity did not meet the request requirements.',
  },
  {
    requestIdentifier: 'RES-008',
    requesterFullName: 'Miguel Torres',
    requesterRole: 'Student',
    borrowerAvatar: createTextPlaceholderDataUrl('MT'),
    requestedDate: '2026-04-25',
    neededDate: '2026-05-09',
    facilityName: 'Stage and Sound System',
    requestQuantity: 1,
    requestType: 'Equipment',
    requestPurpose: 'Concert',
    dateProcessed: '2026-05-09',
    recordStatus: 'Completed',
    remarks: 'Sound check and deployment completed successfully.',
  },
]);

onMounted(async () => {
  try {
    await requestStore.fetchReservations();
  } catch (error) {
    console.error('Error fetching past records:', error);
  }
});

const summaryCards = computed(() => {
  const total = mockPastRecords.value.length || 1;
  const completed = mockPastRecords.value.filter((record) => record.recordStatus === 'Completed').length;
  const rejected = mockPastRecords.value.filter((record) => record.recordStatus === 'Rejected').length;
  const cancelled = mockPastRecords.value.filter((record) => record.recordStatus === 'Cancelled').length;

  return [
    { label: 'Total Records', value: mockPastRecords.value.length, caption: 'All archived reservations', icon: '▦', tone: 'total' },
    { label: 'Completed', value: completed, caption: `${Math.round((completed / total) * 100)}% of all records`, icon: '✓', tone: 'completed' },
    { label: 'Rejected', value: rejected, caption: `${Math.round((rejected / total) * 100)}% of all records`, icon: '×', tone: 'rejected' },
    { label: 'Cancelled', value: cancelled, caption: `${Math.round((cancelled / total) * 100)}% of all records`, icon: '!', tone: 'cancelled' },
  ];
});

const recordTabs = computed(() => [
  { label: 'All', value: 'all', count: mockPastRecords.value.length },
  { label: 'Completed', value: 'completed', count: mockPastRecords.value.filter((record) => record.recordStatus === 'Completed').length },
  { label: 'Rejected', value: 'rejected', count: mockPastRecords.value.filter((record) => record.recordStatus === 'Rejected').length },
  { label: 'Cancelled', value: 'cancelled', count: mockPastRecords.value.filter((record) => record.recordStatus === 'Cancelled').length },
]);

const filteredRecordList = computed(() => {
  let recordsFiltered = [...mockPastRecords.value];

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
        record.requesterFullName,
        record.requesterRole,
        record.facilityName,
        record.requestType,
        record.requestPurpose,
      ].some((value) => String(value).toLowerCase().includes(queryLower))
    );
  }

  recordsFiltered.sort((first, second) => {
    const firstValue = resolveSortValue(first, sortByValue.value);
    const secondValue = resolveSortValue(second, sortByValue.value);

    if (firstValue < secondValue) return sortOrderAscending.value ? -1 : 1;
    if (firstValue > secondValue) return sortOrderAscending.value ? 1 : -1;
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
      return;
    }

    const matched = records.find((record) => record.requestIdentifier === selectedRecord.value.requestIdentifier);
    selectedRecord.value = matched || null;
  },
  { immediate: true }
);

function resolveSortValue(record, sortKey) {
  if (sortKey === 'borrower') return record.requesterFullName.toLowerCase();
  if (sortKey === 'facility') return record.facilityName.toLowerCase();
  if (sortKey === 'status') return record.recordStatus.toLowerCase();
  return new Date(record.requestedDate).getTime();
}

function selectRecord(record) {
  selectedRecord.value = record;
}

function getStatusTone(status) {
  const normalized = String(status).toLowerCase();
  if (normalized === 'completed') return 'completed';
  if (normalized === 'rejected') return 'rejected';
  return 'cancelled';
}

function getTypeTone(type) {
  const normalized = String(type).toLowerCase();
  if (normalized.includes('venue') && normalized.includes('equipment')) return 'mixed';
  if (normalized.includes('equipment')) return 'equipment';
  return 'venue';
}

function formatDate(value) {
  return new Date(value).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

function formatDateTime(value) {
  return new Date(value).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
}

function buildTimeline(record) {
  return [
    {
      label: 'Request Submitted',
      date: formatDate(record.requestedDate),
      note: `${record.requesterFullName} submitted the reservation request.`,
    },
    {
      label: 'Needed Schedule',
      date: formatDate(record.neededDate),
      note: `${record.facilityName} was reserved for the requested schedule.`,
    },
    {
      label: record.recordStatus,
      date: formatDate(record.dateProcessed),
      note: record.remarks,
    },
  ];
}

function downloadSelectedRecord() {
  if (!selectedRecord.value || typeof window === 'undefined') return;

  const record = selectedRecord.value;
  const timelineMarkup = buildTimeline(record)
    .map(
      (entry) => `
        <div style="margin-bottom:16px;">
          <div style="font-weight:700; color:#111827;">${escapeHtml(entry.label)}</div>
          <div style="font-size:12px; color:#6b7280; margin:4px 0;">${escapeHtml(entry.date)}</div>
          <div style="font-size:14px; color:#1f2937; line-height:1.5;">${escapeHtml(entry.note)}</div>
        </div>
      `
    )
    .join('');

  const printWindow = window.open('', '_blank', 'width=900,height=720');
  if (!printWindow) return;

  printWindow.document.write(`
    <html>
      <head>
        <title>${escapeHtml(record.requestIdentifier)} Details</title>
      </head>
      <body style="font-family: ${APP_FONT_STACK}; padding: 32px; color: #0f172a;">
        <h1 style="margin: 0 0 8px;">Reservation Details</h1>
        <p style="margin: 0 0 24px; color: #475569;">${escapeHtml(record.requestIdentifier)} - ${escapeHtml(record.recordStatus)}</p>
        <h2 style="font-size: 18px;">Reservation Information</h2>
        <p><strong>Borrower:</strong> ${escapeHtml(record.requesterFullName)} (${escapeHtml(record.requesterRole)})</p>
        <p><strong>Facility:</strong> ${escapeHtml(record.facilityName)}</p>
        <p><strong>Type:</strong> ${escapeHtml(record.requestType)}</p>
        <p><strong>Quantity:</strong> ${escapeHtml(String(record.requestQuantity))}</p>
        <p><strong>Requested Date:</strong> ${escapeHtml(formatDateTime(record.requestedDate))}</p>
        <p><strong>Needed Date:</strong> ${escapeHtml(formatDateTime(record.neededDate))}</p>
        <p><strong>Remarks:</strong> ${escapeHtml(record.remarks)}</p>
        <h2 style="font-size: 18px; margin-top: 28px;">Timeline</h2>
        ${timelineMarkup}
      </body>
    </html>
  `);
  printWindow.document.close();
  printWindow.focus();
  printWindow.print();
}

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}
</script>
