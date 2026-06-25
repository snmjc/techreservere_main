<template>
  <AdminSidebarLayoutComponent
    :role-label="''"
    :navigation-items="borrowerNavigationItems"
  >
    <section class="borrower-facilities">
      <header class="borrower-facilities__header">
        <div>
          <p class="borrower-facilities__eyebrow">Borrower Calendar</p>
          <h1>Calendar</h1>
          <p class="borrower-facilities__subcopy">Browse weekly reserved-room activity and explore equipment from one borrower-friendly workspace.</p>
        </div>
      </header>

      <section class="borrower-facilities__surface">
        <div class="borrower-facilities__surface-toolbar">
          <div class="borrower-facilities__segment-control">
            <button
              v-for="tabOption in facilityTabs"
              :key="tabOption.value"
              type="button"
              class="borrower-facilities__segment"
              :class="{ 'borrower-facilities__segment--active': activeFacilityTab === tabOption.value }"
              @click="activeFacilityTab = tabOption.value"
            >
              {{ tabOption.label }}
            </button>
          </div>

          <div class="borrower-facilities__toolbar-actions">
            <button type="button" class="borrower-facilities__icon-button" aria-label="Search availability">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="7" />
                <path d="m20 20-3.5-3.5" />
              </svg>
            </button>

            <div class="borrower-facilities__date-nav">
              <button type="button" class="borrower-facilities__icon-button" aria-label="Previous day" @click="shiftSelectedDate(-1)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="m15 18-6-6 6-6" />
                </svg>
              </button>
              <button type="button" class="borrower-facilities__today-button" @click="setSelectedDateToToday">Today</button>
              <button type="button" class="borrower-facilities__icon-button" aria-label="Next day" @click="shiftSelectedDate(1)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="m9 18 6-6-6-6" />
                </svg>
              </button>
            </div>

            <label class="borrower-facilities__view-select">
              <select v-model="calendarViewMode">
                <option value="weekly">Weekly View</option>
                <option value="daily">Daily Summary</option>
              </select>
            </label>

            <button type="button" class="borrower-facilities__reserve-button" @click="router.push({ name: ROUTE_NAMES.borrowerCreateReservation })">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14" />
                <path d="M5 12h14" />
              </svg>
              Add Reservation
            </button>
          </div>
        </div>

        <template v-if="activeFacilityTab !== 'equipment'">
          <div class="borrower-facilities__date-banner">
            <div class="borrower-facilities__date-tile">
              <span>{{ selectedMonthShortLabel }}</span>
              <strong>{{ selectedDayNumberLabel }}</strong>
            </div>
            <div class="borrower-facilities__date-copy">
              <h2>{{ selectedLongDateLabel }}</h2>
              <p>{{ selectedWeekdayLabel }}</p>
            </div>
          </div>

          <div class="borrower-facilities__calendar-layout">
            <aside class="borrower-facilities__sidebar">
              <section class="borrower-facilities__sidebar-card borrower-facilities__sidebar-card--calendar">
                <div class="borrower-facilities__mini-calendar-head">
                  <button type="button" class="borrower-facilities__mini-nav" aria-label="Previous month" @click="shiftSelectedMonth(-1)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="m15 18-6-6 6-6" />
                    </svg>
                  </button>
                  <strong>{{ selectedMonthLabel }}</strong>
                  <button type="button" class="borrower-facilities__mini-nav" aria-label="Next month" @click="shiftSelectedMonth(1)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="m9 18 6-6-6-6" />
                    </svg>
                  </button>
                </div>

                <div class="borrower-facilities__mini-weekdays">
                  <span v-for="weekday in compactWeekdayLabels" :key="weekday">{{ weekday }}</span>
                </div>

                <div class="borrower-facilities__mini-grid">
                  <button
                    v-for="dayCell in monthCalendarCells"
                    :key="dayCell.key"
                    type="button"
                    class="borrower-facilities__mini-day"
                    :class="{
                      'borrower-facilities__mini-day--muted': !dayCell.inCurrentMonth,
                      'borrower-facilities__mini-day--active': dayCell.dateValue === selectedVenueDate,
                    }"
                    @click="selectedVenueDate = dayCell.dateValue"
                  >
                    {{ dayCell.dayNumber }}
                  </button>
                </div>
              </section>

              <section class="borrower-facilities__sidebar-card">
                <p class="borrower-facilities__card-label">Selected Date Details</p>
                <div class="borrower-facilities__detail-list">
                  <div class="borrower-facilities__detail-item">
                    <span class="borrower-facilities__detail-icon">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 7v5l3 3" />
                      </svg>
                    </span>
                    <div>
                      <strong>{{ selectedDaySummary.availableCount }}</strong>
                      <p>rooms already reserved for this date</p>
                    </div>
                  </div>
                  <div class="borrower-facilities__detail-item">
                    <span class="borrower-facilities__detail-icon">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" />
                        <path d="M16 2v4" />
                        <path d="M8 2v4" />
                        <path d="M3 10h18" />
                      </svg>
                    </span>
                    <div>
                      <strong>{{ selectedLongDateLabel }}</strong>
                      <p>{{ selectedWeekdayLabel }}</p>
                    </div>
                  </div>
                  <div class="borrower-facilities__detail-item">
                    <span class="borrower-facilities__detail-icon">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                      </svg>
                    </span>
                    <div>
                      <strong>{{ calendarViewMode === 'weekly' ? 'Weekly View' : 'Daily Summary' }}</strong>
                      <p>{{ selectedDaySummary.bookableCount }} reserved rooms shown on this board</p>
                    </div>
                  </div>
                </div>
              </section>

              <section class="borrower-facilities__sidebar-card">
                <p class="borrower-facilities__card-label">Legend</p>
                <div class="borrower-facilities__legend-list">
                  <div class="borrower-facilities__legend-row">
                    <span class="borrower-facilities__legend-dot borrower-facilities__legend-dot--available"></span>
                    <div>
                      <strong>Reserved</strong>
                      <p>Already reserved for that date.</p>
                    </div>
                  </div>
                  <div class="borrower-facilities__legend-row">
                    <span class="borrower-facilities__legend-dot borrower-facilities__legend-dot--future"></span>
                    <div>
                      <strong>Reserved Block</strong>
                      <p>Another blocked room entry for the selected date.</p>
                    </div>
                  </div>
                  <div class="borrower-facilities__legend-row">
                    <span class="borrower-facilities__legend-dot borrower-facilities__legend-dot--maintenance"></span>
                    <div>
                      <strong>Other Block</strong>
                      <p>Unavailable because of another scheduled block.</p>
                    </div>
                  </div>
                </div>
              </section>
            </aside>

            <section class="borrower-facilities__board-panel">
              <p v-if="venueError" class="borrower-facilities__feedback borrower-facilities__feedback--error">{{ venueError }}</p>
              <div v-else-if="venueLoading" class="borrower-facilities__loading-panel">
                <p>Loading weekly venue availability...</p>
              </div>
              <div v-else class="borrower-facilities__board">
                <div class="borrower-facilities__time-rail">
                  <span class="borrower-facilities__time-rail-spacer"></span>
                  <span v-for="timeLabel in timeRailLabels" :key="timeLabel">{{ timeLabel }}</span>
                </div>

                <div class="borrower-facilities__week-grid" :class="{ 'borrower-facilities__week-grid--daily': calendarViewMode === 'daily' }">
                  <article
                    v-for="dayColumn in visibleCalendarColumns"
                    :key="dayColumn.dateValue"
                    class="borrower-facilities__day-column"
                    :class="{ 'borrower-facilities__day-column--selected': dayColumn.dateValue === selectedVenueDate }"
                  >
                    <header class="borrower-facilities__day-header">
                      <span>{{ dayColumn.weekdayLabel }}</span>
                      <strong>{{ dayColumn.shortDateLabel }}</strong>
                      <small>{{ dayColumn.availableCount }} reserved</small>
                    </header>

                    <div class="borrower-facilities__day-body">
                      <button
                        v-for="entry in dayColumn.entries"
                        :key="`${dayColumn.dateValue}-${entry.venueIdentifier}`"
                        type="button"
                        class="borrower-facilities__availability-card"
                        :class="`borrower-facilities__availability-card--${entry.statusTone}`"
                        @click="handleOpenCalendarReservation(entry)"
                      >
                        <span class="borrower-facilities__availability-meta">{{ entry.metaLine }}</span>
                        <strong>{{ entry.venueName }}</strong>
                        <p>{{ entry.descriptionLine }}</p>
                        <div class="borrower-facilities__availability-footer">
                          <span>{{ entry.footerLabel }}</span>
                          <span>{{ entry.capacityLabel }}</span>
                        </div>
                      </button>

                      <div v-if="dayColumn.entries.length === 0" class="borrower-facilities__empty-day">
                        <strong>No reserved rooms surfaced</strong>
                        <p>No reserved rooms were returned for this date.</p>
                      </div>
                    </div>
                  </article>
                </div>
              </div>
            </section>
          </div>
        </template>

        <section
          v-if="activeFacilityTab === 'all' || activeFacilityTab === 'venue'"
          class="borrower-facilities__section-card borrower-facilities__section-card--venues"
        >
          <div class="borrower-facilities__section-head borrower-facilities__section-head--venues">
            <div>
              <h3>List of Venues</h3>
            </div>

            <div class="borrower-facilities__section-actions borrower-facilities__section-actions--venues">
              <label class="borrower-facilities__inline-field">
                <span>Sort by:</span>
                <select v-model="venueDirectorySortOrder">
                  <option value="asc">Name (A-Z)</option>
                  <option value="desc">Name (Z-A)</option>
                </select>
              </label>
            </div>
          </div>

          <p v-if="venueDirectoryError" class="borrower-facilities__feedback borrower-facilities__feedback--error">{{ venueDirectoryError }}</p>

          <div v-if="venueDirectoryLoading" class="borrower-facilities__empty-state">
            <p>Loading venues...</p>
          </div>

          <div v-else-if="paginatedVenueDirectoryCards.length === 0" class="borrower-facilities__empty-state">
            <p>No venues were returned for the selected date.</p>
          </div>

          <div v-else class="borrower-facilities__venue-grid borrower-facilities__venue-grid--directory">
            <article
              v-for="venue in paginatedVenueDirectoryCards"
              :key="venue.venueIdentifier || venue.venueName"
              class="borrower-facilities__venue-card borrower-facilities__venue-card--directory"
            >
              <div class="borrower-facilities__venue-media borrower-facilities__venue-media--directory">
                <img
                  :src="resolveVenuePhoto(venue)"
                  :alt="`${venue.venueName} photo`"
                  class="borrower-facilities__venue-image"
                />
              </div>
              <div class="borrower-facilities__venue-copy borrower-facilities__venue-copy--directory">
                <div class="borrower-facilities__venue-topline">
                  <div>
                    <h4>{{ venue.venueName }}</h4>
                    <p>{{ venue.floorLevel || 'Other Floor' }} <span class="borrower-facilities__meta-separator">|</span> {{ venue.venueLocation || 'Tech Center' }}</p>
                  </div>
                  <span class="borrower-facilities__status-pill" :class="`borrower-facilities__status-pill--${venue.venueStatusTone}`">
                    {{ venue.venueStatusLabel }}
                  </span>
                </div>
                <div class="borrower-facilities__venue-meta">
                  <span>Capacity {{ venue.capacityLimit || 'N/A' }}</span>
                  <span>{{ venue.venueLocation || 'Venue Space' }}</span>
                </div>
                <p>{{ venue.description || 'Venue details are available when you open this facility card.' }}</p>
                <small>Reservation Availability Start Date: {{ formatDisplayDate(venue.availabilityDate) }}</small>

                <div class="borrower-facilities__venue-actions">
                  <button
                    type="button"
                    class="borrower-facilities__card-action-button"
                    @click="handleViewVenueDetails(venue)"
                  >
                    View
                  </button>
                </div>
              </div>
            </article>
          </div>

          <div class="borrower-facilities__venues-footer">
            <span>Showing {{ venueDirectoryDisplayStart }} to {{ venueDirectoryDisplayEnd }} of {{ venueDirectoryRecords.length }} venues</span>
            <div v-if="venueDirectoryTotalPages > 1" class="borrower-facilities__pagination">
              <button type="button" :disabled="venueDirectoryCurrentPage === 1" @click="venueDirectoryCurrentPage -= 1">Previous</button>
              <span>Page {{ venueDirectoryCurrentPage }} of {{ venueDirectoryTotalPages }}</span>
              <button type="button" :disabled="venueDirectoryCurrentPage === venueDirectoryTotalPages" @click="venueDirectoryCurrentPage += 1">Next</button>
            </div>
          </div>
        </section>

        <section
          v-if="activeFacilityTab === 'all' || activeFacilityTab === 'equipment'"
          class="borrower-facilities__section-card"
        >
          <div class="borrower-facilities__section-head">
            <div>
              <p class="borrower-facilities__section-eyebrow">Equipment Library</p>
              <h3>{{ activeFacilityTab === 'all' ? 'Featured equipment' : 'Available equipment inventory' }}</h3>
            </div>

            <div class="borrower-facilities__section-actions">
              <div class="borrower-facilities__equipment-controls">
                <div class="borrower-facilities__equipment-filter-stack">
                  <label class="borrower-facilities__inline-field borrower-facilities__inline-field--search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="11" cy="11" r="7" />
                      <path d="m20 20-3.5-3.5" />
                    </svg>
                    <input v-model.trim="equipmentSearchQuery" type="text" placeholder="Search equipment" />
                  </label>

                  <div class="borrower-facilities__equipment-filter-row">
                    <label class="borrower-facilities__inline-field borrower-facilities__inline-field--status">
                      <span>Status</span>
                      <select v-model="equipmentFilterValue">
                        <option value="all">All</option>
                        <option value="available">Available</option>
                        <option value="maintenance">Under Maintenance</option>
                      </select>
                    </label>

                    <button
                      type="button"
                      class="borrower-facilities__sort-button"
                      :title="equipmentSortOrder === 'asc' ? 'Sort A-Z' : 'Sort Z-A'"
                      @click="equipmentSortOrder = equipmentSortOrder === 'asc' ? 'desc' : 'asc'"
                    >
                      <svg v-if="equipmentSortOrder === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <polyline points="19 12 12 19 5 12" />
                      </svg>
                      <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="19" x2="12" y2="5" />
                        <polyline points="5 12 12 5 19 12" />
                      </svg>
                    </button>
                  </div>
                </div>

                <div class="borrower-facilities__view-toggle" aria-label="Equipment view type">
                  <button
                    type="button"
                    class="borrower-facilities__view-toggle-button"
                    :class="{ 'borrower-facilities__view-toggle-button--active': equipmentViewMode === 'card' }"
                    @click="equipmentViewMode = 'card'"
                  >
                    Card View
                  </button>
                  <button
                    type="button"
                    class="borrower-facilities__view-toggle-button"
                    :class="{ 'borrower-facilities__view-toggle-button--active': equipmentViewMode === 'list' }"
                    @click="equipmentViewMode = 'list'"
                  >
                    List View
                  </button>
                </div>
              </div>
            </div>
          </div>

          <p v-if="equipmentError" class="borrower-facilities__feedback borrower-facilities__feedback--error">{{ equipmentError }}</p>

          <div v-if="equipmentLoading" class="borrower-facilities__empty-state">
            <p>Loading equipment records...</p>
          </div>

          <div v-else-if="equipmentCardsToRender.length === 0" class="borrower-facilities__empty-state">
            <p>No equipment matched your current filter.</p>
          </div>

          <div v-else-if="equipmentViewMode === 'card'" class="borrower-facilities__equipment-grid">
            <button
              v-for="equipment in equipmentCardsToRender"
              :key="equipment.equipmentIdentifier"
              type="button"
              class="borrower-facilities__equipment-card"
              :class="{
                'borrower-facilities__equipment-card--available': equipment.equipmentState === 'Available',
                'borrower-facilities__equipment-card--unavailable': equipment.equipmentState !== 'Available',
              }"
              @click="handleViewEquipmentDetails(equipment)"
            >
              <div class="borrower-facilities__equipment-media">
                <img
                  :src="resolveEquipmentPhoto(equipment)"
                  :alt="`${equipment.equipmentName} photo`"
                  class="borrower-facilities__equipment-image"
                />
              </div>
              <div class="borrower-facilities__equipment-copy">
                <div class="borrower-facilities__equipment-topline">
                  <h4>{{ equipment.equipmentName }}</h4>
                  <span class="borrower-facilities__status-pill" :class="equipment.equipmentState === 'Available' ? 'borrower-facilities__status-pill--available' : 'borrower-facilities__status-pill--maintenance'">
                    {{ formatEquipmentStatus(equipment) }}
                  </span>
                </div>
                <p class="borrower-facilities__equipment-brand-copy">{{ equipment.brandGroupLabel || equipment.equipmentBrand || 'Unbranded' }}</p>
                <p>{{ getEquipmentSummaryNote(equipment) }}</p>
                <div class="borrower-facilities__equipment-meta">
                  <span>{{ equipment.equipmentCategory || equipment.categoryName || 'N/A' }}</span>
                  <span>{{ equipment.groupedQuantityLabel || formatGroupedQuantity(equipment) }}</span>
                  <span>{{ equipment.availableQuantityLabel || formatAvailableQuantity(equipment) }}</span>
                </div>
                <small v-if="getEquipmentInventoryPreview(equipment)" class="borrower-facilities__equipment-preview">
                  {{ getEquipmentInventoryPreview(equipment) }}
                </small>
              </div>
            </button>
          </div>

          <div v-else class="borrower-facilities__equipment-list">
            <div class="borrower-facilities__equipment-list-head">
              <span>Equipment</span>
              <span>Status</span>
              <span>Category</span>
              <span>Quantity</span>
              <span>Action</span>
            </div>

            <article
              v-for="equipment in equipmentCardsToRender"
              :key="`list-${equipment.equipmentIdentifier}`"
              class="borrower-facilities__equipment-list-row"
            >
              <div class="borrower-facilities__equipment-list-primary">
                <img
                  :src="resolveEquipmentPhoto(equipment)"
                  :alt="`${equipment.equipmentName} photo`"
                  class="borrower-facilities__equipment-list-image"
                />
                <div class="borrower-facilities__equipment-list-copy">
                  <strong>{{ equipment.equipmentName }}</strong>
                  <p>{{ equipment.brandGroupLabel || equipment.equipmentBrand || 'Unbranded' }}</p>
                  <p>{{ getEquipmentSummaryNote(equipment) }}</p>
                  <small v-if="getEquipmentInventoryPreview(equipment)">{{ getEquipmentInventoryPreview(equipment) }}</small>
                </div>
              </div>
              <span class="borrower-facilities__equipment-list-status" :class="equipment.equipmentState === 'Available' ? 'borrower-facilities__equipment-list-status--available' : 'borrower-facilities__equipment-list-status--maintenance'">
                {{ formatEquipmentStatus(equipment) }}
              </span>
              <span>{{ equipment.equipmentCategory || equipment.categoryName || 'N/A' }}</span>
              <span>{{ equipment.groupedQuantityLabel || formatGroupedQuantity(equipment) }} · {{ equipment.availableQuantityLabel || formatAvailableQuantity(equipment) }}</span>
              <button
                type="button"
                class="borrower-facilities__card-action-button"
                @click="handleViewEquipmentDetails(equipment)"
              >
                View
              </button>
            </article>
          </div>

          <div v-if="activeFacilityTab === 'equipment' && equipmentTotalPages > 1" class="borrower-facilities__pagination">
            <button type="button" :disabled="equipmentCurrentPage === 1" @click="equipmentCurrentPage -= 1">Previous</button>
            <span>Page {{ equipmentCurrentPage }} of {{ equipmentTotalPages }}</span>
            <button type="button" :disabled="equipmentCurrentPage === equipmentTotalPages" @click="equipmentCurrentPage += 1">Next</button>
          </div>
        </section>
      </section>

      <div class="borrower-facilities__footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </div>

      <VenueDetailsModalComponent
        :show="Boolean(viewVenueRecord || viewVenueLoading || viewVenueError)"
        :venue="viewVenueRecord"
        :error-message="viewVenueLoading ? 'Loading venue details...' : viewVenueError"
        @close="closeVenueDetails"
      />

      <EquipmentDetailsModalComponent
        :show="Boolean(viewEquipmentRecord || viewEquipmentLoading || viewEquipmentError)"
        :equipment="viewEquipmentRecord"
        :error-message="viewEquipmentLoading ? 'Loading equipment details...' : viewEquipmentError"
        title="View Equipment Details"
        subtitle="Equipment information from the TechReserve equipment database."
        :show-admin-fields="false"
        @close="closeEquipmentDetails"
      />

      <div
        v-if="calendarReservationModalRecord"
        class="borrower-facilities__reservation-modal-overlay"
        @click.self="closeCalendarReservationModal"
      >
        <section class="borrower-facilities__reservation-modal">
          <button
            type="button"
            class="borrower-facilities__reservation-modal-close"
            aria-label="Close reservation details"
            @click="closeCalendarReservationModal"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>

          <div class="borrower-facilities__reservation-modal-heading">
            <p>Venue Reservation Details</p>
            <h2>{{ calendarReservationModalRecord.venueName }}</h2>
            <span>{{ calendarReservationModalRecord.floorLevel || 'Other Floor' }} | {{ calendarReservationModalRecord.venueLocation || 'Venue location unavailable' }}</span>
          </div>

          <div class="borrower-facilities__reservation-modal-list">
            <article
              v-for="detail in calendarReservationDetails"
              :key="detail.reservationIdentifier || `${detail.entryType}-${detail.timeRangeLabel}-${detail.reservedByName}`"
              class="borrower-facilities__reservation-modal-card"
            >
              <div class="borrower-facilities__reservation-modal-card-top">
                <strong>{{ detail.timeRangeLabel }}</strong>
                <span
                  class="borrower-facilities__reservation-modal-status"
                  :class="detail.entryType === 'reservation'
                    ? 'borrower-facilities__reservation-modal-status--reserved'
                    : 'borrower-facilities__reservation-modal-status--blocked'"
                >
                  {{ detail.entryType === 'reservation' ? 'Reserved' : 'Blocked' }}
                </span>
              </div>

              <dl class="borrower-facilities__reservation-modal-grid">
                <div>
                  <dt>Reserved By</dt>
                  <dd>{{ detail.reservedByName || 'Unavailable' }}</dd>
                </div>
                <div>
                  <dt>Organization</dt>
                  <dd>{{ detail.organizationName || 'N/A' }}</dd>
                </div>
                <div>
                  <dt>Reservation Code</dt>
                  <dd>{{ detail.reservationCode || 'N/A' }}</dd>
                </div>
                <div>
                  <dt>Status</dt>
                  <dd>{{ detail.statusLabel || 'N/A' }}</dd>
                </div>
                <div>
                  <dt>Activity</dt>
                  <dd>{{ detail.activityType || 'N/A' }}</dd>
                </div>
                <div>
                  <dt>Schedule</dt>
                  <dd>{{ detail.startDateTimeLabel && detail.endDateTimeLabel ? `${detail.startDateTimeLabel} - ${detail.endDateTimeLabel}` : detail.timeRangeLabel }}</dd>
                </div>
                <div class="borrower-facilities__reservation-modal-grid-full">
                  <dt>Purpose / Notes</dt>
                  <dd>{{ detail.purposeDescription || 'No additional details provided.' }}</dd>
                </div>
              </dl>
            </article>
          </div>
        </section>
      </div>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/ViewFacilities.css';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';
import equipmentApi from '@/modules/reservation/services/equipmentApi.js';
import venueApi from '@/modules/reservation/services/venueApi.js';
import VenueDetailsModalComponent from '@/modules/facility/components/VenueDetailsModalComponent.vue';
import EquipmentDetailsModalComponent from '@/modules/facility/components/EquipmentDetailsModalComponent.vue';
import { groupBorrowerEquipmentRecords } from '@/modules/facility/utils/equipmentGrouping.js';
import { formatEquipmentStatus, resolveEquipmentPhoto } from '@/modules/facility/utils/equipmentPresentation.js';
import {
  deriveVenueAvailabilityForDate,
  isVenueFloorPlaceholderRecord,
  resolveVenuePhoto,
} from '@/modules/facility/utils/venueFormValidation.js';
import { formatDisplayDate } from '@/shared/utils/dateTimeDisplay.js';

const router = useRouter();

const facilityTabs = [
  { value: 'all', label: 'All' },
  { value: 'venue', label: 'Venues' },
  { value: 'equipment', label: 'Equipment' },
];

const compactWeekdayLabels = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
const timeRailLabels = ['8 AM', '10 AM', '12 PM', '2 PM', '4 PM'];
const activeFacilityTab = ref('all');
const calendarViewMode = ref('weekly');
const selectedVenueDate = ref(getTodayDateInputValue());
const venueDirectorySortOrder = ref('asc');
const venueDirectoryCurrentPage = ref(1);
const venueDirectoryPageSize = 4;
const equipmentFilterValue = ref('all');
const equipmentSortOrder = ref('asc');
const equipmentSearchQuery = ref('');
const equipmentViewMode = ref('card');
const equipmentCurrentPage = ref(1);
const equipmentPageSize = 8;
const weeklyVenueMap = ref({});
const venueLoading = ref(false);
const venueError = ref('');
const venueDirectoryRecords = ref([]);
const venueDirectoryLoading = ref(false);
const venueDirectoryError = ref('');
const viewVenueRecord = ref(null);
const viewVenueLoading = ref(false);
const viewVenueError = ref('');
const calendarReservationModalRecord = ref(null);
const viewEquipmentRecord = ref(null);
const viewEquipmentLoading = ref(false);
const viewEquipmentError = ref('');
const equipmentList = ref([]);
const equipmentLoading = ref(false);
const equipmentError = ref('');

let activeWeekRequestSequence = 0;

const selectedDateObject = computed(() => parseDateValue(selectedVenueDate.value) || new Date());
const selectedMonthShortLabel = computed(() => new Intl.DateTimeFormat('en-US', { month: 'short' }).format(selectedDateObject.value).toUpperCase());
const selectedDayNumberLabel = computed(() => new Intl.DateTimeFormat('en-US', { day: 'numeric' }).format(selectedDateObject.value));
const selectedLongDateLabel = computed(() => new Intl.DateTimeFormat('en-US', {
  month: 'long',
  day: 'numeric',
  year: 'numeric',
  timeZone: 'Asia/Manila',
}).format(selectedDateObject.value));
const selectedWeekdayLabel = computed(() => new Intl.DateTimeFormat('en-US', {
  weekday: 'long',
  timeZone: 'Asia/Manila',
}).format(selectedDateObject.value));
const selectedMonthLabel = computed(() => new Intl.DateTimeFormat('en-US', {
  month: 'long',
  year: 'numeric',
  timeZone: 'Asia/Manila',
}).format(selectedDateObject.value));

const visibleWeekDates = computed(() => buildWeekDateValues(selectedVenueDate.value));
const selectedDayVenues = computed(() => weeklyVenueMap.value[selectedVenueDate.value] || []);
const selectedDaySummary = computed(() => {
  const venues = selectedDayVenues.value;
  return {
    availableCount: venues.length,
    bookableCount: venues.length,
  };
});

const visibleCalendarColumns = computed(() => {
  const sourceDates = calendarViewMode.value === 'daily'
    ? [selectedVenueDate.value]
    : visibleWeekDates.value;

  return sourceDates.map((dateValue) => {
    const dateObject = parseDateValue(dateValue) || selectedDateObject.value;
    const venues = weeklyVenueMap.value[dateValue] || [];
    const entries = venues.map((venue) => {
      const statusTone = resolveVenueStatusTone(venue, dateValue);

      return {
        ...venue,
        statusTone,
        metaLine: resolveAvailabilityMetaLine(venue, dateValue),
        descriptionLine: venue.venueLocation || venue.description || 'Venue details available on click.',
        footerLabel: statusTone === 'future'
          ? `Blocked on ${formatDisplayDate(dateValue)}`
          : 'Reserved for this date',
        capacityLabel: `Cap ${venue.capacityLimit || 'N/A'}`,
      };
    });

    return {
      dateValue,
      weekdayLabel: new Intl.DateTimeFormat('en-US', { weekday: 'long', timeZone: 'Asia/Manila' }).format(dateObject),
      shortDateLabel: new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', timeZone: 'Asia/Manila' }).format(dateObject),
      availableCount: entries.length,
      entries,
    };
  });
});

const calendarReservationDetails = computed(() => {
  const reservationDetails = calendarReservationModalRecord.value?.reservationDetails;
  return Array.isArray(reservationDetails) ? reservationDetails : [];
});

const monthCalendarCells = computed(() => buildMonthCalendarCells(selectedDateObject.value, selectedVenueDate.value));
const sortedVenueDirectoryRecords = computed(() => (
  [...venueDirectoryRecords.value]
    .sort((left, right) => compareByName(left?.venueName, right?.venueName, venueDirectorySortOrder.value))
    .map((venueRecord) => {
      const venueStatusTone = resolveVenueDirectoryStatusTone(venueRecord, selectedVenueDate.value);
      return {
        ...venueRecord,
        venueStatusTone,
        venueStatusLabel: resolveVenueDirectoryStatusLabel(venueStatusTone),
      };
    })
));

const venueDirectoryTotalPages = computed(() => Math.max(1, Math.ceil(sortedVenueDirectoryRecords.value.length / venueDirectoryPageSize)));
const paginatedVenueDirectoryCards = computed(() => {
  const startIndex = (venueDirectoryCurrentPage.value - 1) * venueDirectoryPageSize;
  return sortedVenueDirectoryRecords.value.slice(startIndex, startIndex + venueDirectoryPageSize);
});
const venueDirectoryDisplayStart = computed(() => (
  sortedVenueDirectoryRecords.value.length === 0
    ? 0
    : ((venueDirectoryCurrentPage.value - 1) * venueDirectoryPageSize) + 1
));
const venueDirectoryDisplayEnd = computed(() => Math.min(
  venueDirectoryCurrentPage.value * venueDirectoryPageSize,
  sortedVenueDirectoryRecords.value.length,
));

const groupedEquipmentRecords = computed(() => groupBorrowerEquipmentRecords(equipmentList.value));

const filteredEquipment = computed(() => {
  const normalizedQuery = normalizeSearchText(equipmentSearchQuery.value);

  let filtered = groupedEquipmentRecords.value.filter((equipment) => (
    normalizedQuery === ''
    || getEquipmentSearchableValues(equipment).some((value) => normalizeSearchText(value).includes(normalizedQuery))
  ));

  if (equipmentFilterValue.value === 'available') {
    filtered = filtered.filter((equipment) => formatEquipmentStatus(equipment) === 'Available');
  } else if (equipmentFilterValue.value === 'maintenance') {
    filtered = filtered.filter((equipment) => formatEquipmentStatus(equipment) === 'Under Maintenance');
  }

  return [...filtered].sort((left, right) => {
    const leftName = normalizeSearchText(left?.equipmentName);
    const rightName = normalizeSearchText(right?.equipmentName);

    return equipmentSortOrder.value === 'asc'
      ? leftName.localeCompare(rightName)
      : rightName.localeCompare(leftName);
  });
});

const equipmentTotalPages = computed(() => Math.max(1, Math.ceil(filteredEquipment.value.length / equipmentPageSize)));
const paginatedEquipment = computed(() => {
  const startIndex = (equipmentCurrentPage.value - 1) * equipmentPageSize;
  return filteredEquipment.value.slice(startIndex, startIndex + equipmentPageSize);
});

const featuredEquipment = computed(() => filteredEquipment.value.slice(0, 4));
const equipmentCardsToRender = computed(() => (
  activeFacilityTab.value === 'equipment'
    ? paginatedEquipment.value
    : featuredEquipment.value
));

watch(selectedVenueDate, async () => {
  venueDirectoryCurrentPage.value = 1;
  await fetchVenuesForVisibleWeek();
}, { immediate: true });

watch(activeFacilityTab, () => {
  venueDirectoryCurrentPage.value = 1;
  equipmentCurrentPage.value = 1;
});

watch(venueDirectorySortOrder, () => {
  venueDirectoryCurrentPage.value = 1;
});

watch([equipmentFilterValue, equipmentSortOrder, equipmentSearchQuery], () => {
  equipmentCurrentPage.value = 1;
});

watch(venueDirectoryTotalPages, (nextPageCount) => {
  if (venueDirectoryCurrentPage.value > nextPageCount) {
    venueDirectoryCurrentPage.value = nextPageCount;
  }
});

watch(equipmentTotalPages, (nextPageCount) => {
  if (equipmentCurrentPage.value > nextPageCount) {
    equipmentCurrentPage.value = nextPageCount;
  }
});

fetchEquipment();

async function fetchVenuesForVisibleWeek() {
  const currentRequestSequence = activeWeekRequestSequence + 1;
  activeWeekRequestSequence = currentRequestSequence;
  venueLoading.value = true;
  venueError.value = '';
  venueDirectoryLoading.value = true;
  venueDirectoryError.value = '';

  try {
    const weekDateValues = buildWeekDateValues(selectedVenueDate.value);
    const [responses, venueDirectoryResponse] = await Promise.all([
      Promise.all(weekDateValues.map(async (dateValue) => {
        const response = await venueApi.listVenues({
          selectedDate: dateValue,
          reservedOnly: true,
        });
        const venuePayload = response?.data?.venues || response?.venues || [];
        const normalizedVenues = Array.isArray(venuePayload)
          ? venuePayload
            .map((venueRecord) => normalizeVenueRecord(venueRecord))
            .filter(Boolean)
            .filter((venueRecord) => !isVenueFloorPlaceholderRecord(venueRecord))
            .filter((venueRecord) => Array.isArray(venueRecord.reservationTimeRanges) && venueRecord.reservationTimeRanges.length > 0)
          : [];

        return [dateValue, normalizedVenues];
      })),
      venueApi.listVenues({
        selectedDate: selectedVenueDate.value,
        includeUnavailable: true,
      }),
    ]);

    if (currentRequestSequence !== activeWeekRequestSequence) {
      return;
    }

    const venueDirectoryPayload = venueDirectoryResponse?.data?.venues || venueDirectoryResponse?.venues || [];
    venueDirectoryRecords.value = Array.isArray(venueDirectoryPayload)
      ? venueDirectoryPayload
        .map((venueRecord) => normalizeVenueRecord(venueRecord))
        .filter(Boolean)
        .filter((venueRecord) => !isVenueFloorPlaceholderRecord(venueRecord))
      : [];
    weeklyVenueMap.value = Object.fromEntries(responses);
  } catch (error) {
    if (currentRequestSequence !== activeWeekRequestSequence) {
      return;
    }

    weeklyVenueMap.value = {};
    venueDirectoryRecords.value = [];
    venueError.value = error?.response?.data?.errorMessage || 'Failed to load weekly venue availability.';
    venueDirectoryError.value = error?.response?.data?.errorMessage || 'Failed to load venue directory.';
  } finally {
    if (currentRequestSequence === activeWeekRequestSequence) {
      venueLoading.value = false;
      venueDirectoryLoading.value = false;
    }
  }
}

async function fetchEquipment() {
  try {
    equipmentLoading.value = true;
    equipmentError.value = '';
    const response = await equipmentApi.listEquipment();
    equipmentList.value = response?.data?.equipment || [];
  } catch (error) {
    equipmentList.value = [];
    equipmentError.value = error?.response?.data?.errorMessage || 'Failed to load equipment records.';
  } finally {
    equipmentLoading.value = false;
  }
}

async function handleViewVenueDetails(venueRecord) {
  const normalizedVenueRecord = normalizeVenueRecord(venueRecord);
  const venueIdentifier = normalizedVenueRecord?.venueIdentifier;

  viewVenueRecord.value = normalizedVenueRecord;
  viewVenueLoading.value = Boolean(venueIdentifier);
  viewVenueError.value = '';

  if (!venueIdentifier) {
    return;
  }

  try {
    const response = await venueApi.getVenueById(venueIdentifier);
    viewVenueRecord.value = normalizeVenueRecord(response?.data || response);
  } catch (error) {
    viewVenueError.value = error?.response?.data?.errorMessage || 'Failed to load venue details.';
  } finally {
    viewVenueLoading.value = false;
  }
}

function handleOpenCalendarReservation(venueRecord) {
  calendarReservationModalRecord.value = normalizeVenueRecord(venueRecord);
}

async function handleViewEquipmentDetails(equipmentRecord) {
  if (!equipmentRecord?.equipmentIdentifier) {
    return;
  }

  viewEquipmentError.value = '';
  viewEquipmentLoading.value = false;
  viewEquipmentRecord.value = equipmentRecord;
}

function closeVenueDetails() {
  viewVenueRecord.value = null;
  viewVenueError.value = '';
  viewVenueLoading.value = false;
}

function closeCalendarReservationModal() {
  calendarReservationModalRecord.value = null;
}

function closeEquipmentDetails() {
  viewEquipmentRecord.value = null;
  viewEquipmentError.value = '';
  viewEquipmentLoading.value = false;
}

function normalizeVenueRecord(venue) {
  if (!venue) {
    return null;
  }

  return {
    venueIdentifier: venue.venueIdentifier ?? venue.venue_identifier ?? venue.id ?? venue.identifier ?? null,
    venueName: venue.venueName || '',
    venueLocation: venue.venueLocation || '',
    floorLevel: venue.floorLevel || 'Other',
    capacityLimit: venue.capacityLimit ?? null,
    availabilityDate: venue.availabilityDate || '',
    operationalStatus: venue.operationalStatus || '',
    availabilityStatus: venue.availabilityStatus || 'Unavailable',
    description: venue.description || '',
    imageUrl: venue.imageUrl || '',
    reservationTimeRanges: Array.isArray(venue.reservationTimeRanges) ? venue.reservationTimeRanges : [],
    reservationDetails: Array.isArray(venue.reservationDetails) ? venue.reservationDetails : [],
  };
}

function resolveVenueDirectoryStatusTone(venueRecord, dateValue) {
  const operationalStatus = String(venueRecord?.operationalStatus || '').trim();
  if (operationalStatus === 'Maintenance' || operationalStatus === 'Inactive') {
    return 'maintenance';
  }

  return deriveVenueAvailabilityForDate(venueRecord, dateValue) === 'Available' ? 'available' : 'future';
}

function resolveVenueDirectoryStatusLabel(statusTone) {
  if (statusTone === 'maintenance') {
    return 'Under Maintenance';
  }

  if (statusTone === 'future') {
    return 'Unavailable';
  }

  return 'Available';
}

function resolveVenueStatusTone(venueRecord, dateValue) {
  const operationalStatus = String(venueRecord?.operationalStatus || '').trim();
  if (operationalStatus === 'Maintenance' || operationalStatus === 'Inactive') {
    return 'maintenance';
  }

  const availabilityStatus = deriveVenueAvailabilityForDate(venueRecord, dateValue);
  return availabilityStatus === 'Available' ? 'available' : 'future';
}

function resolveAvailabilityMetaLine(venueRecord, dateValue) {
  const statusTone = resolveVenueStatusTone(venueRecord, dateValue);

  if (statusTone === 'future') {
    return 'Blocked reservation window';
  }

  if (statusTone === 'maintenance') {
    return 'Maintenance notice';
  }

  if (Array.isArray(venueRecord?.reservationTimeRanges) && venueRecord.reservationTimeRanges.length > 0) {
    const primaryReservationDetail = Array.isArray(venueRecord?.reservationDetails)
      ? venueRecord.reservationDetails[0]
      : null;

    return primaryReservationDetail?.timeRangeLabel || venueRecord.reservationTimeRanges[0];
  }

  return 'Reserved room';
}

function compareByName(leftName, rightName, sortDirection) {
  const normalizedLeft = normalizeSearchText(leftName);
  const normalizedRight = normalizeSearchText(rightName);

  return sortDirection === 'asc'
    ? normalizedLeft.localeCompare(normalizedRight)
    : normalizedRight.localeCompare(normalizedLeft);
}

function getEquipmentSearchableValues(equipment) {
  return [
    equipment?.equipmentName,
    equipment?.equipmentCategory || equipment?.categoryName,
    equipment?.equipmentBrand,
    equipment?.description || equipment?.scheduleDescription,
    ...(Array.isArray(equipment?.remarksNotes) ? equipment.remarksNotes : []),
    ...(Array.isArray(equipment?.inventoryItems)
      ? equipment.inventoryItems.flatMap((item) => [item?.assetId, item?.barcode])
      : []),
  ];
}

function getEquipmentSummaryNote(equipment) {
  const remarks = Array.isArray(equipment?.remarksNotes)
    ? equipment.remarksNotes.filter(Boolean)
    : [];

  return remarks[0]
    || equipment?.description
    || equipment?.scheduleDescription
    || 'Admin notes will appear here when provided.';
}

function formatGroupedQuantity(equipment) {
  const groupedItemCount = Math.max(Number(equipment?.groupedItemCount || 0), 0);
  return `${groupedItemCount} item${groupedItemCount === 1 ? '' : 's'} grouped`;
}

function formatAvailableQuantity(equipment) {
  const availableQuantity = Math.max(Number(equipment?.availableQuantity || 0), 0);
  return `${availableQuantity} available`;
}

function getEquipmentInventoryPreview(equipment) {
  const previewValues = Array.isArray(equipment?.inventoryPreview)
    ? equipment.inventoryPreview.filter(Boolean)
    : [];

  if (previewValues.length === 0) {
    return '';
  }

  const remainingCount = Math.max(Number(equipment?.groupedItemCount || 0) - previewValues.length, 0);
  return remainingCount > 0
    ? `Units: ${previewValues.join(', ')} +${remainingCount} more`
    : `Units: ${previewValues.join(', ')}`;
}

function normalizeSearchText(value) {
  return String(value || '').trim().toLowerCase();
}

function setSelectedDateToToday() {
  selectedVenueDate.value = getTodayDateInputValue();
}

function shiftSelectedDate(dayOffset) {
  const sourceDate = parseDateValue(selectedVenueDate.value) || new Date();
  sourceDate.setDate(sourceDate.getDate() + dayOffset);
  selectedVenueDate.value = formatDateInputValue(sourceDate);
}

function shiftSelectedMonth(monthOffset) {
  const sourceDate = parseDateValue(selectedVenueDate.value) || new Date();
  sourceDate.setMonth(sourceDate.getMonth() + monthOffset);
  selectedVenueDate.value = formatDateInputValue(sourceDate);
}

function buildWeekDateValues(dateValue) {
  const selectedDate = parseDateValue(dateValue) || new Date();
  const monday = getStartOfWeek(selectedDate);

  return Array.from({ length: 7 }, (_, index) => {
    const nextDate = new Date(monday);
    nextDate.setDate(monday.getDate() + index);
    return formatDateInputValue(nextDate);
  });
}

function buildMonthCalendarCells(date, selectedDateValue) {
  const year = date.getFullYear();
  const month = date.getMonth();
  const firstDayOfMonth = new Date(year, month, 1);
  const lastDayOfMonth = new Date(year, month + 1, 0);
  const leadingDays = firstDayOfMonth.getDay();
  const trailingDays = 6 - lastDayOfMonth.getDay();
  const cells = [];

  for (let index = leadingDays; index > 0; index -= 1) {
    const cellDate = new Date(year, month, 1 - index);
    cells.push(createCalendarCell(cellDate, false, selectedDateValue));
  }

  for (let dayNumber = 1; dayNumber <= lastDayOfMonth.getDate(); dayNumber += 1) {
    const cellDate = new Date(year, month, dayNumber);
    cells.push(createCalendarCell(cellDate, true, selectedDateValue));
  }

  for (let index = 1; index <= trailingDays; index += 1) {
    const cellDate = new Date(year, month + 1, index);
    cells.push(createCalendarCell(cellDate, false, selectedDateValue));
  }

  return cells;
}

function createCalendarCell(date, inCurrentMonth, selectedDateValue) {
  return {
    key: `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`,
    dateValue: formatDateInputValue(date),
    dayNumber: date.getDate(),
    inCurrentMonth,
    isSelected: formatDateInputValue(date) === selectedDateValue,
  };
}

function getStartOfWeek(sourceDate) {
  const normalizedDate = new Date(sourceDate);
  const dayIndex = normalizedDate.getDay();
  const mondayOffset = dayIndex === 0 ? -6 : 1 - dayIndex;
  normalizedDate.setDate(normalizedDate.getDate() + mondayOffset);
  normalizedDate.setHours(0, 0, 0, 0);
  return normalizedDate;
}

function parseDateValue(dateValue) {
  const normalizedValue = String(dateValue || '').trim();
  if (normalizedValue === '') {
    return null;
  }

  const parsedDate = new Date(`${normalizedValue}T00:00:00`);
  return Number.isNaN(parsedDate.getTime()) ? null : parsedDate;
}

function formatDateInputValue(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function getTodayDateInputValue() {
  return formatDateInputValue(new Date());
}
</script>
