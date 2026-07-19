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
          <span class="admin-past-records-stat-icon" v-html="card.iconSvg"></span>
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

            <div class="admin-past-records-toolbar-actions">
              <button class="admin-past-records-action-button admin-past-records-action-button--ghost" type="button" @click="openPastRecordsReportModal">
                Generate Report
              </button>
              <button class="admin-past-records-action-button" type="button" @click="exportAllPastRecords">
                Export All
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
                <tr v-if="requestStore.isLoadingReservations">
                  <td colspan="8" class="admin-past-records-empty-state">Loading archived reservations...</td>
                </tr>
                <tr v-else-if="paginatedRecordList.length === 0">
                  <td colspan="8" class="admin-past-records-empty-state">No past records match the current filters.</td>
                </tr>
                <tr
                  v-for="record in paginatedRecordList"
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
              <span>Showing {{ pageStart }} to {{ pageEnd }} of {{ filteredRecordList.length }} records</span>
            </div>

            <div class="admin-past-records-pagination">
              <button type="button" :disabled="currentPage === 1" @click="currentPage -= 1">Previous</button>
              <span>Page {{ currentPage }} of {{ totalPages }}</span>
              <button type="button" :disabled="currentPage === totalPages" @click="currentPage += 1">Next</button>
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

      <div
        v-if="showPastRecordsReportModal"
        class="admin-past-records-report-overlay"
        @click.self="closePastRecordsReportModal"
      >
        <section class="admin-past-records-report-modal">
          <button class="admin-past-records-report-close" type="button" aria-label="Close" @click="closePastRecordsReportModal">X</button>

          <div class="admin-past-records-report-header">
            <span class="admin-past-records-report-eyebrow">Excel Report Builder</span>
            <h2>Generate Report</h2>
            <p>Build a hardcoded Excel report for archived venue or equipment reservations using a simple timeframe and filter setup.</p>
          </div>

          <div class="admin-past-records-report-builder">
            <div class="admin-past-records-report-grid">
              <section class="admin-past-records-report-section">
                <span class="admin-past-records-report-step">1. Report Type</span>
                <small class="admin-past-records-report-hint">Choose what you want to report.</small>
                <div class="admin-past-records-report-type-grid">
                  <label class="admin-past-records-report-choice">
                    <input v-model="pastRecordsReportType" type="radio" value="venue" />
                    <span>
                      <strong>Venue Report</strong>
                      <small>Generate report for venues</small>
                    </span>
                  </label>
                  <label class="admin-past-records-report-choice">
                    <input v-model="pastRecordsReportType" type="radio" value="equipment" />
                    <span>
                      <strong>Equipment Report</strong>
                      <small>Generate report for equipment</small>
                    </span>
                  </label>
                </div>
              </section>

              <section class="admin-past-records-report-section">
                <span class="admin-past-records-report-step">2. Report Category</span>
                <small class="admin-past-records-report-hint">Select the category to include.</small>
                <select v-model="pastRecordsReportCategory">
                  <option
                    v-for="option in pastRecordsReportCategoryOptions"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
              </section>

              <section class="admin-past-records-report-section admin-past-records-report-section--wide">
                <span class="admin-past-records-report-step">3. Timeframe</span>
                <small class="admin-past-records-report-hint">Select the time period for your report.</small>
                <div class="admin-past-records-report-timeframe-grid">
                  <button
                    v-for="option in pastRecordsReportTimeframeOptions"
                    :key="option.value"
                    type="button"
                    class="admin-past-records-report-timeframe"
                    :class="{ 'admin-past-records-report-timeframe--active': pastRecordsReportTimeframe === option.value }"
                    @click="pastRecordsReportTimeframe = option.value"
                  >
                    {{ option.label }}
                  </button>
                </div>
              </section>

              <section class="admin-past-records-report-section">
                <span class="admin-past-records-report-step">Select Date</span>
                <div class="admin-past-records-report-date-layout">
                  <div class="admin-past-records-report-date-column">
                    <input v-model="pastRecordsReportStartDate" type="date" />
                    <input
                      v-if="pastRecordsReportTimeframe === 'custom'"
                      v-model="pastRecordsReportEndDate"
                      type="date"
                    />
                    <small>{{ pastRecordsReportRangeLabel }}</small>
                  </div>
                  <div class="admin-past-records-report-date-summary">
                    <strong>{{ pastRecordsReportTimeframeOptions.find((option) => option.value === pastRecordsReportTimeframe)?.label || 'Custom Range' }} report</strong>
                    <span>{{ formatDate(pastRecordsReportStartDate) }}</span>
                    <small>{{ pastRecordsReportRangeLabel }}</small>
                  </div>
                </div>
              </section>

              <section class="admin-past-records-report-section admin-past-records-report-section--wide">
                <span class="admin-past-records-report-step">4. Additional Filters (Optional)</span>
                <small class="admin-past-records-report-hint">Narrow down the results with additional filters.</small>
                <div class="admin-past-records-report-filters-grid">
                  <label>
                    <span>{{ pastRecordsPrimaryFilterLabel }}</span>
                    <select v-model="pastRecordsReportFacilityFilter">
                      <option value="all">All Facilities</option>
                      <option
                        v-for="option in pastRecordsReportCategoryOptions.filter((option) => option.value !== 'all')"
                        :key="`facility-${option.value}`"
                        :value="option.value"
                      >
                        {{ option.label }}
                      </option>
                    </select>
                  </label>

                  <label>
                    <span>{{ pastRecordsSecondaryFilterLabel }}</span>
                    <select v-model="pastRecordsReportSecondaryFilter">
                      <option
                        v-for="option in pastRecordsReportSecondaryFilterOptions"
                        :key="option.value"
                        :value="option.value"
                      >
                        {{ option.label }}
                      </option>
                    </select>
                  </label>

                  <label>
                    <span>Reservation Status</span>
                    <select v-model="pastRecordsReportStatusFilter">
                      <option value="all">All Statuses</option>
                      <option value="completed">Completed</option>
                      <option value="rejected">Rejected</option>
                      <option value="cancelled">Cancelled</option>
                    </select>
                  </label>
                </div>
              </section>

              <section class="admin-past-records-report-section admin-past-records-report-section--wide">
                <span class="admin-past-records-report-step">5. Excel Contents</span>
                <small class="admin-past-records-report-hint">Choose what to include in your report.</small>
                <div class="admin-past-records-report-contents-grid">
                  <label class="admin-past-records-report-checkbox">
                    <input v-model="pastRecordsReportIncludeDetails" type="checkbox" />
                    <div>
                      <strong>Detailed records</strong>
                      <small>Full list of archived reservations</small>
                    </div>
                  </label>
                  <label class="admin-past-records-report-checkbox admin-past-records-report-checkbox--wide">
                    <input v-model="pastRecordsReportIncludeReservationInfo" type="checkbox" />
                    <div>
                      <strong>Reservation information</strong>
                      <small>Reservation and booking details</small>
                    </div>
                  </label>
                </div>
              </section>
            </div>

            <p v-if="pastRecordsReportError" class="admin-past-records-report-error">{{ pastRecordsReportError }}</p>

            <div v-if="pastRecordsReportPreviewVisible" class="admin-past-records-report-preview">
              <article>
                <strong>{{ pastRecordsReportPreviewSummary.title }}</strong>
                <span>{{ pastRecordsReportPreviewSummary.recordCount }} record(s)</span>
                <span>{{ pastRecordsReportPreviewSummary.statusLabel }}</span>
              </article>
              <article>
                <strong>Applied Range</strong>
                <span>{{ pastRecordsReportRangeLabel }}</span>
                <span>{{ pastRecordsReportPreviewSummary.generatedLabel }}</span>
              </article>
            </div>

            <div class="admin-past-records-report-footer">
              <div class="admin-past-records-report-note">
                The report will be generated in Excel format and downloaded to your device.
              </div>

              <footer class="admin-past-records-report-actions">
                <button type="button" class="admin-past-records-report-button admin-past-records-report-button--secondary" @click="closePastRecordsReportModal">
                  Cancel
                </button>
                <button type="button" class="admin-past-records-report-button admin-past-records-report-button--secondary" @click="previewPastRecordsReport">
                  Preview Records
                </button>
                <button
                  type="button"
                  class="admin-past-records-report-button"
                  :disabled="isExportingPastRecordsReport"
                  @click="exportPastRecordsReportWorkbook"
                >
                  {{ isExportingPastRecordsReport ? 'Exporting...' : 'Export Excel' }}
                </button>
              </footer>
            </div>
          </div>
        </section>
      </div>
      <DataRequestStatusFloater :items="pastRecordsStatusItems" />
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import * as XLSX from 'xlsx';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import DataRequestStatusFloater from '@/shared/components/DataRequestStatusFloater.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/PastRecords.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { useRequestStore } from '@/modules/request/store/requestStore.js';
import { createTextPlaceholderDataUrl } from '@/shared/utils/mockImage.js';

const APP_FONT_STACK = "'Inter', 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif";
const summaryIconAttributes = 'width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
const requestStore = useRequestStore();
const activeRecordTab = ref('all');
const searchQueryText = ref('');
const showingFilterValue = ref('10');
const sortOrderAscending = ref(false);
const sortByValue = ref('requestedDate');
const currentPage = ref(1);
const selectedRecord = ref(null);
const showPastRecordsReportModal = ref(false);
const pastRecordsReportError = ref('');
const pastRecordsReportPreviewVisible = ref(false);
const isExportingPastRecordsReport = ref(false);
const pastRecordsReportType = ref('venue');
const pastRecordsReportCategory = ref('all');
const pastRecordsReportTimeframe = ref('custom');
const pastRecordsReportStartDate = ref('2026-06-04');
const pastRecordsReportEndDate = ref('2026-06-18');
const pastRecordsReportFacilityFilter = ref('all');
const pastRecordsReportSecondaryFilter = ref('all');
const pastRecordsReportStatusFilter = ref('all');
const pastRecordsReportIncludeDetails = ref(true);
const pastRecordsReportIncludeReservationInfo = ref(true);

onMounted(async () => {
  try {
    await requestStore.fetchReservations();
  } catch (error) {
    console.error('Error fetching past records:', error);
  }
});

const pastRecordsList = computed(() =>
  (requestStore.pastRecordsList || []).map((record) => ({
    ...record,
    borrowerAvatar: createTextPlaceholderDataUrl(buildInitials(record.requesterFullName)),
    dateProcessed: record.neededDate || record.activityEndTime || record.activityTime || record.requestedDate,
  }))
);

const summaryCards = computed(() => {
  const total = pastRecordsList.value.length || 1;
  const completed = pastRecordsList.value.filter((record) => record.recordStatus === 'Completed').length;
  const rejected = pastRecordsList.value.filter((record) => record.recordStatus === 'Rejected').length;
  const cancelled = pastRecordsList.value.filter((record) => record.recordStatus === 'Cancelled').length;

  return [
    {
      label: 'Total Records',
      value: pastRecordsList.value.length,
      caption: 'All archived reservations',
      iconSvg: `<svg ${summaryIconAttributes}><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h10"/></svg>`,
      tone: 'total',
    },
    {
      label: 'Completed',
      value: completed,
      caption: `${Math.round((completed / total) * 100)}% of all records`,
      iconSvg: `<svg ${summaryIconAttributes}><circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/></svg>`,
      tone: 'completed',
    },
    {
      label: 'Rejected',
      value: rejected,
      caption: `${Math.round((rejected / total) * 100)}% of all records`,
      iconSvg: `<svg ${summaryIconAttributes}><circle cx="12" cy="12" r="9"/><path d="M9 9l6 6"/><path d="M15 9l-6 6"/></svg>`,
      tone: 'rejected',
    },
    {
      label: 'Cancelled',
      value: cancelled,
      caption: `${Math.round((cancelled / total) * 100)}% of all records`,
      iconSvg: `<svg ${summaryIconAttributes}><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg>`,
      tone: 'cancelled',
    },
  ];
});

const recordTabs = computed(() => [
  { label: 'All', value: 'all', count: pastRecordsList.value.length },
  { label: 'Completed', value: 'completed', count: pastRecordsList.value.filter((record) => record.recordStatus === 'Completed').length },
  { label: 'Rejected', value: 'rejected', count: pastRecordsList.value.filter((record) => record.recordStatus === 'Rejected').length },
  { label: 'Cancelled', value: 'cancelled', count: pastRecordsList.value.filter((record) => record.recordStatus === 'Cancelled').length },
]);

const filteredRecordList = computed(() => {
  let recordsFiltered = [...pastRecordsList.value];

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

  return recordsFiltered;
});

const resolvedPageSize = computed(() => Number(showingFilterValue.value) || 10);
const pastRecordsStatusItems = computed(() => [
  {
    key: 'past-reservations',
    label: 'Past Reservations',
    state: resolveReservationListState(pastRecordsList.value),
  },
]);
const totalPages = computed(() => Math.max(1, Math.ceil(filteredRecordList.value.length / resolvedPageSize.value)));
const paginatedRecordList = computed(() => {
  const startIndex = (currentPage.value - 1) * resolvedPageSize.value;
  return filteredRecordList.value.slice(startIndex, startIndex + resolvedPageSize.value);
});
const pageStart = computed(() => (
  filteredRecordList.value.length === 0
    ? 0
    : ((currentPage.value - 1) * resolvedPageSize.value) + 1
));
const pageEnd = computed(() => Math.min(currentPage.value * resolvedPageSize.value, filteredRecordList.value.length));
const pastRecordsReportTimeframeOptions = [
  { value: 'daily', label: 'Daily' },
  { value: 'weekly', label: 'Weekly' },
  { value: 'monthly', label: 'Monthly' },
  { value: 'yearly', label: 'Yearly' },
  { value: 'custom', label: 'Custom Range' },
];
const pastRecordsReportCategoryOptions = computed(() => (
  pastRecordsReportType.value === 'venue'
    ? [{ value: 'all', label: 'All Venues' }, ...Array.from(new Set(
      pastRecordsList.value
        .filter((record) => getTypeTone(record.requestType) === 'venue')
        .map((record) => String(record.facilityName || '').trim())
        .filter(Boolean)
    )).map((facilityName) => ({ value: facilityName, label: facilityName }))]
    : [{ value: 'all', label: 'All Equipment' }, ...Array.from(new Set(
      pastRecordsList.value
        .filter((record) => getTypeTone(record.requestType) !== 'venue')
        .map((record) => String(record.facilityName || '').trim())
        .filter(Boolean)
    )).map((facilityName) => ({ value: facilityName, label: facilityName }))]
));
const pastRecordsReportSecondaryFilterOptions = computed(() => (
  pastRecordsReportType.value === 'venue'
    ? [
      { value: 'all', label: 'All Types' },
      { value: 'venue', label: 'Venue' },
      { value: 'both', label: 'Both' },
    ]
    : [
      { value: 'all', label: 'All Types' },
      { value: 'equipment', label: 'Equipment' },
      { value: 'mixed', label: 'Both' },
    ]
));
const pastRecordsPrimaryFilterLabel = computed(() => (
  pastRecordsReportType.value === 'venue' ? 'Venue' : 'Equipment Group'
));
const pastRecordsSecondaryFilterLabel = computed(() => (
  pastRecordsReportType.value === 'venue' ? 'Venue Type' : 'Reservation Type'
));
const pastRecordsReportRange = computed(() => resolvePastRecordsReportRange(
  pastRecordsReportTimeframe.value,
  pastRecordsReportStartDate.value,
  pastRecordsReportEndDate.value,
));
const pastRecordsReportRangeLabel = computed(() => (
  `${formatDate(pastRecordsReportRange.value.start)} - ${formatDate(pastRecordsReportRange.value.end)}`
));
const filteredPastRecordsReportRows = computed(() => buildPastRecordsReportRows());
const pastRecordsReportPreviewSummary = computed(() => ({
  title: pastRecordsReportType.value === 'venue' ? 'Venue Report Preview' : 'Equipment Report Preview',
  recordCount: filteredPastRecordsReportRows.value.length,
  statusLabel: pastRecordsReportStatusFilter.value === 'all'
    ? 'All archive statuses'
    : `${pastRecordsReportStatusFilter.value} records only`,
  generatedLabel: `Generated ${formatDateTime(new Date().toISOString())}`,
}));

watch([activeRecordTab, searchQueryText, showingFilterValue, sortOrderAscending, sortByValue], () => {
  currentPage.value = 1;
});

watch(totalPages, (pageCount) => {
  if (currentPage.value > pageCount) {
    currentPage.value = pageCount;
  }
});

watch(
  paginatedRecordList,
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

watch(pastRecordsReportType, () => {
  pastRecordsReportCategory.value = 'all';
  pastRecordsReportFacilityFilter.value = 'all';
  pastRecordsReportSecondaryFilter.value = 'all';
  pastRecordsReportPreviewVisible.value = false;
});

function openPastRecordsReportModal() {
  pastRecordsReportError.value = '';
  pastRecordsReportPreviewVisible.value = false;
  pastRecordsReportType.value = 'venue';
  pastRecordsReportCategory.value = 'all';
  pastRecordsReportTimeframe.value = 'custom';
  pastRecordsReportStartDate.value = '2026-06-04';
  pastRecordsReportEndDate.value = '2026-06-18';
  pastRecordsReportFacilityFilter.value = 'all';
  pastRecordsReportSecondaryFilter.value = 'all';
  pastRecordsReportStatusFilter.value = 'all';
  pastRecordsReportIncludeDetails.value = true;
  pastRecordsReportIncludeReservationInfo.value = true;
  showPastRecordsReportModal.value = true;
}

function closePastRecordsReportModal() {
  if (isExportingPastRecordsReport.value) {
    return;
  }

  showPastRecordsReportModal.value = false;
  pastRecordsReportError.value = '';
  pastRecordsReportPreviewVisible.value = false;
}

function previewPastRecordsReport() {
  pastRecordsReportError.value = '';
  pastRecordsReportPreviewVisible.value = true;
}

function exportAllPastRecords() {
  const rows = filteredRecordList.value.map((record) => mapPastRecordToReportRow(record));
  exportPastRecordsWorkbook('techreserve-past-records-all', rows, true);
}

function exportPastRecordsReportWorkbook() {
  exportPastRecordsWorkbook(
    buildPastRecordsReportFileName(),
    filteredPastRecordsReportRows.value,
    false,
  );
}

function exportPastRecordsWorkbook(fileName, detailRows, isDirectExportAll) {
  try {
    isExportingPastRecordsReport.value = true;
    pastRecordsReportError.value = '';

    const workbook = XLSX.utils.book_new();
    const normalizedRows = Array.isArray(detailRows) && detailRows.length > 0
      ? buildPastRecordsDetailedExportRows(detailRows)
      : [buildPastRecordsDetailedEmptyRow('No archived reservations match the selected report filters.')];

    if (isDirectExportAll || pastRecordsReportIncludeDetails.value) {
      XLSX.utils.book_append_sheet(
        workbook,
        XLSX.utils.json_to_sheet(normalizedRows),
        'Detailed Records',
      );
    }

    if (!isDirectExportAll && pastRecordsReportIncludeReservationInfo.value) {
      XLSX.utils.book_append_sheet(
        workbook,
        XLSX.utils.json_to_sheet(
          Array.isArray(detailRows) && detailRows.length > 0
            ? buildPastRecordsReservationInfoRows(detailRows)
            : [buildPastRecordsReservationInfoEmptyRow('No reservation information matches the selected report filters.')]
        ),
        'Reservation Info',
      );
    }

    XLSX.writeFile(workbook, `${fileName}.xlsx`);
    if (!isDirectExportAll) {
      pastRecordsReportPreviewVisible.value = true;
    }
  } catch (error) {
    pastRecordsReportError.value = error?.message || 'Unable to export the archive report right now.';
  } finally {
    isExportingPastRecordsReport.value = false;
  }
}

function resolveSortValue(record, sortKey) {
  if (sortKey === 'borrower') return String(record.requesterFullName || '').toLowerCase();
  if (sortKey === 'facility') return String(record.facilityName || '').toLowerCase();
  if (sortKey === 'status') return String(record.recordStatus || '').toLowerCase();
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

function resolvePastRecordsReportRange(timeframe, startDateValue, endDateValue) {
  const safeStartDate = parseDateInput(startDateValue);

  if (timeframe === 'daily') {
    return {
      start: formatDateInputValue(safeStartDate),
      end: formatDateInputValue(safeStartDate),
    };
  }

  if (timeframe === 'weekly') {
    const weekStart = new Date(safeStartDate);
    weekStart.setDate(weekStart.getDate() - weekStart.getDay());
    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekStart.getDate() + 6);
    return {
      start: formatDateInputValue(weekStart),
      end: formatDateInputValue(weekEnd),
    };
  }

  if (timeframe === 'monthly') {
    return {
      start: formatDateInputValue(new Date(safeStartDate.getFullYear(), safeStartDate.getMonth(), 1)),
      end: formatDateInputValue(new Date(safeStartDate.getFullYear(), safeStartDate.getMonth() + 1, 0)),
    };
  }

  if (timeframe === 'yearly') {
    return {
      start: `${safeStartDate.getFullYear()}-01-01`,
      end: `${safeStartDate.getFullYear()}-12-31`,
    };
  }

  const safeEndDate = parseDateInput(endDateValue);
  const normalizedStart = safeStartDate <= safeEndDate ? safeStartDate : safeEndDate;
  const normalizedEnd = safeStartDate <= safeEndDate ? safeEndDate : safeStartDate;

  return {
    start: formatDateInputValue(normalizedStart),
    end: formatDateInputValue(normalizedEnd),
  };
}

function buildPastRecordsReportRows() {
  return pastRecordsList.value
    .filter((record) => matchesPastRecordsReportType(record))
    .filter((record) => matchesPastRecordsReportCategory(record))
    .filter((record) => matchesPastRecordsReportFacility(record))
    .filter((record) => matchesPastRecordsReportSecondary(record))
    .filter((record) => matchesPastRecordsReportStatus(record))
    .filter((record) => isPastRecordsReportDateInRange(record))
    .map((record) => mapPastRecordToReportRow(record));
}

function matchesPastRecordsReportType(record) {
  const tone = getTypeTone(record.requestType);
  return pastRecordsReportType.value === 'venue'
    ? tone === 'venue' || tone === 'mixed'
    : tone === 'equipment' || tone === 'mixed';
}

function matchesPastRecordsReportCategory(record) {
  return pastRecordsReportCategory.value === 'all'
    || String(record.facilityName || '').trim() === pastRecordsReportCategory.value;
}

function matchesPastRecordsReportFacility(record) {
  return pastRecordsReportFacilityFilter.value === 'all'
    || String(record.facilityName || '').trim() === pastRecordsReportFacilityFilter.value;
}

function matchesPastRecordsReportSecondary(record) {
  if (pastRecordsReportSecondaryFilter.value === 'all') {
    return true;
  }

  return getTypeTone(record.requestType) === pastRecordsReportSecondaryFilter.value;
}

function matchesPastRecordsReportStatus(record) {
  return pastRecordsReportStatusFilter.value === 'all'
    || String(record.recordStatus || '').toLowerCase() === pastRecordsReportStatusFilter.value;
}

function isPastRecordsReportDateInRange(record) {
  const comparisonDate = String(record.dateProcessed || record.neededDate || record.requestedDate || '').slice(0, 10);
  if (!comparisonDate) {
    return false;
  }

  return comparisonDate >= pastRecordsReportRange.value.start && comparisonDate <= pastRecordsReportRange.value.end;
}

function mapPastRecordToReportRow(record) {
  return {
    reportTimestamp: formatDateTime(new Date().toISOString()),
    reservationId: record.requestIdentifier,
    borrower: record.requesterFullName,
    role: record.requesterRole,
    facility: record.facilityName,
    type: record.requestType,
    quantity: record.requestQuantity,
    requestedDate: formatDateTime(record.requestedDate),
    neededDate: formatDateTime(record.neededDate),
    processedDate: formatDateTime(record.dateProcessed),
    status: record.recordStatus,
    purpose: record.requestPurpose || 'N/A',
    remarks: record.remarks || 'N/A',
  };
}

function buildPastRecordsDetailedExportRows(rows) {
  return rows.map((row) => ({
    ID: row.reservationId || 'N/A',
    Borrower: row.borrower || 'N/A',
    Facility: row.facility || 'N/A',
    Type: row.type || 'N/A',
    Qty: Number(row.quantity || 0),
    Requested: row.requestedDate || 'N/A',
    Needed: row.neededDate || 'N/A',
    Status: row.status || 'N/A',
    Timestamp: row.reportTimestamp || 'N/A',
  }));
}

function buildPastRecordsReservationInfoRows(rows) {
  return rows.map((row) => ({
    ID: row.reservationId || 'N/A',
    Borrower: row.borrower || 'N/A',
    Facility: row.facility || 'N/A',
    Type: row.type || 'N/A',
    Qty: Number(row.quantity || 0),
    Requested: row.requestedDate || 'N/A',
    Needed: row.neededDate || 'N/A',
    'Processed Date': row.processedDate || 'N/A',
    Status: row.status || 'N/A',
    Purpose: row.purpose || 'N/A',
    Remarks: row.remarks || 'N/A',
    Timestamp: row.reportTimestamp || 'N/A',
  }));
}

function buildPastRecordsDetailedEmptyRow(message) {
  return {
    ID: message,
    Borrower: '',
    Facility: '',
    Type: '',
    Qty: '',
    Requested: '',
    Needed: '',
    Status: '',
    Timestamp: '',
  };
}

function buildPastRecordsReservationInfoEmptyRow(message) {
  return {
    ID: message,
    Borrower: '',
    Facility: '',
    Type: '',
    Qty: '',
    Requested: '',
    Needed: '',
    'Processed Date': '',
    Status: '',
    Purpose: '',
    Remarks: '',
    Timestamp: '',
  };
}

function buildPastRecordsReportFileName() {
  return `techreserve-past-records-${pastRecordsReportType.value}-${pastRecordsReportRange.value.start}-to-${pastRecordsReportRange.value.end}`;
}

function parseDateInput(value) {
  const normalizedValue = String(value || '').trim();
  if (normalizedValue === '') {
    return new Date('2026-07-18T00:00:00');
  }

  const parsedDate = new Date(`${normalizedValue}T00:00:00`);
  return Number.isNaN(parsedDate.getTime()) ? new Date('2026-07-18T00:00:00') : parsedDate;
}

function formatDate(value) {
  const parsedDate = new Date(value);
  if (Number.isNaN(parsedDate.getTime())) {
    return 'N/A';
  }

  return parsedDate.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

function formatDateTime(value) {
  const parsedDate = new Date(value);
  if (Number.isNaN(parsedDate.getTime())) {
    return 'N/A';
  }

  return parsedDate.toLocaleString('en-US', {
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

function buildInitials(fullName) {
  const parts = String(fullName || '')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2);

  if (!parts.length) {
    return 'NA';
  }

  return parts.map((part) => part.charAt(0).toUpperCase()).join('');
}

function resolveReservationListState(records) {
  if (requestStore.isLoadingReservations && records.length > 0) {
    return 'cached-loading';
  }

  if (requestStore.isLoadingReservations) {
    return 'loading';
  }

  return records.length > 0 ? 'fresh' : 'idle';
}

function formatDateInputValue(dateValue) {
  const year = dateValue.getFullYear();
  const month = String(dateValue.getMonth() + 1).padStart(2, '0');
  const day = String(dateValue.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}
</script>
