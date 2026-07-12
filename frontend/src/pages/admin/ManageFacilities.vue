<!-- ===== AI GENERATED: AdminManageFacilitiesPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="manage-facilities-hero-header">
      <div class="manage-facilities-hero-copy">
        <p class="manage-facilities-hero-eyebrow">Facility Operations</p>
        <h1>Manage Facilities</h1>
        <p class="manage-facilities-hero-subcopy">Review venue availability, maintain equipment records, and manage classroom schedules from one admin workspace.</p>
      </div>
      <button class="manage-facilities-hero-back" type="button" @click="handleGoBack">
        ← Go Back
      </button>
    </section>

    <section class="manage-facilities-workspace">
      <div class="manage-facilities-tabs-row">
        <div class="manage-facilities-tab-cluster manage-facilities-tab-cluster--classic" role="tablist" aria-label="Facility tabs">
          <button
            class="manage-facilities-tab-button"
            :class="{ 'manage-facilities-tab-button--active': activeFacilityTab === 'all' }"
            @click="handleFacilityTabChange('all')"
          >
            All
          </button>
          <button
            class="manage-facilities-tab-button"
            :class="{ 'manage-facilities-tab-button--active': activeFacilityTab === 'venue' }"
            @click="handleFacilityTabChange('venue')"
          >
            Venues
          </button>
          <button
            class="manage-facilities-tab-button"
            :class="{ 'manage-facilities-tab-button--active': activeFacilityTab === 'equipment' }"
            @click="handleFacilityTabChange('equipment')"
          >
            Equipment
          </button>
          <button
            class="manage-facilities-tab-button"
            :class="{ 'manage-facilities-tab-button--active': activeFacilityTab === 'classroom-schedules' }"
            @click="handleFacilityTabChange('classroom-schedules')"
          >
            Classroom Schedules
          </button>
        </div>

        <div v-if="activeFacilityTab === 'equipment' || activeFacilityTab === 'all'" class="manage-facilities-inline-actions">
          <button class="manage-facilities-add-button manage-facilities-add-button--compact" @click="handleAddFacility">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"/>
              <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            {{ addButtonLabel }}
          </button>
        </div>
      </div>

      <div v-if="activeFacilityTab === 'equipment' || activeFacilityTab === 'all'" class="manage-facilities-filter-row">
        <button
          class="manage-facilities-filter-pill"
          :class="{ 'manage-facilities-filter-pill--active': availabilityFilter === 'all' }"
          @click="availabilityFilter = 'all'"
        >
          All
        </button>
        <button
          class="manage-facilities-filter-pill"
          :class="{ 'manage-facilities-filter-pill--active': availabilityFilter === 'available' }"
          @click="availabilityFilter = 'available'"
        >
          Available
        </button>
        <button
          class="manage-facilities-filter-pill"
          :class="{ 'manage-facilities-filter-pill--active': availabilityFilter === 'unavailable' }"
          @click="availabilityFilter = 'unavailable'"
        >
          Unavailable
        </button>
      </div>

      <div v-if="activeFacilityTab === 'equipment' || activeFacilityTab === 'all'" class="manage-facilities-search-sort-row">
        <div class="manage-facilities-search-group">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          <input
            v-model="searchQuery"
            type="text"
            class="manage-facilities-search-input"
            :placeholder="searchPlaceholder"
          />
        </div>
        <div class="manage-facilities-sort-group manage-facilities-sort-group--showing">
          <label class="manage-facilities-sort-label manage-facilities-sort-label--inline">{{ showingLabel }}</label>
          <select id="facilityShowingSelect" v-model="showingFilterValue" class="manage-facilities-showing-select">
            <option
              v-for="filterOption in showingFilterOptions"
              :key="filterOption.value"
              :value="filterOption.value"
            >
              {{ filterOption.label }}
            </option>
          </select>
        </div>
        <div class="manage-facilities-sort-group manage-facilities-sort-group--sort">
          <label class="manage-facilities-sort-label manage-facilities-sort-label--inline">Sort:</label>
          <select v-model="sortValue" class="manage-facilities-sort-select">
            <option value="asc">Name (A-Z)</option>
            <option value="desc">Name (Z-A)</option>
          </select>
        </div>
        <div v-if="activeFacilityTab === 'equipment'" class="manage-facilities-view-toggle" aria-label="Equipment view type">
          <button
            type="button"
            class="manage-facilities-view-toggle-button"
            :class="{ 'manage-facilities-view-toggle-button--active': equipmentViewMode === 'card' }"
            @click="equipmentViewMode = 'card'"
          >
            Card View
          </button>
          <button
            type="button"
            class="manage-facilities-view-toggle-button"
            :class="{ 'manage-facilities-view-toggle-button--active': equipmentViewMode === 'list' }"
            @click="equipmentViewMode = 'list'"
          >
            List View
          </button>
        </div>
      </div>

      <div v-if="activeFacilityTab === 'venue'">
        <div v-if="loading && venuesList.length === 0" class="manage-facilities-loading">Loading venue operations...</div>
        <p v-else-if="venueError && venuesList.length === 0" class="manage-facilities-modal-error">{{ venueError }}</p>
        <section v-else class="manage-facilities-venue-shell">
          <div class="manage-facilities-venue-surface">
            <div class="manage-facilities-venue-surface-toolbar">
              <div class="manage-facilities-venue-toolbar-actions">
                <button type="button" class="manage-facilities-venue-icon-button" aria-label="Search venues">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="7" />
                    <path d="m20 20-3.5-3.5" />
                  </svg>
                </button>

                <div class="manage-facilities-venue-date-nav">
                  <button type="button" class="manage-facilities-venue-icon-button" aria-label="Previous day" @click="shiftSelectedVenueDate(-1)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="m15 18-6-6 6-6" />
                    </svg>
                  </button>
                  <button type="button" class="manage-facilities-venue-today-button" @click="setSelectedVenueDateToToday">Today</button>
                  <button type="button" class="manage-facilities-venue-icon-button" aria-label="Next day" @click="shiftSelectedVenueDate(1)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="m9 18 6-6-6-6" />
                    </svg>
                  </button>
                </div>

                <label class="manage-facilities-venue-view-select">
                  <select v-model="venueCalendarViewMode">
                    <option value="weekly">Weekly View</option>
                  </select>
                </label>

                <button type="button" class="manage-facilities-venue-reserve-button" @click="handleAddFacility">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14" />
                    <path d="M5 12h14" />
                  </svg>
                  Add Venue
                </button>
              </div>
            </div>

            <div class="manage-facilities-venue-overview-strip">
              <div class="manage-facilities-venue-date-banner manage-facilities-venue-date-banner--dashboard">
                <div class="manage-facilities-venue-date-tile">
                  <span>{{ selectedVenueMonthShortLabel }}</span>
                  <strong>{{ selectedVenueDayNumberLabel }}</strong>
                </div>
                <div class="manage-facilities-venue-date-copy">
                  <h2>{{ selectedVenueLongDateLabel }}</h2>
                  <p>{{ selectedVenueWeekdayLabel }}</p>
                </div>
              </div>

              <div class="manage-facilities-venue-stat-grid">
                <article class="manage-facilities-venue-stat-card manage-facilities-venue-stat-card--available">
                  <span class="manage-facilities-venue-stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <rect x="7" y="3" width="10" height="18" rx="2" />
                      <path d="M10 7h4" />
                      <path d="M10 11h4" />
                    </svg>
                  </span>
                  <div>
                    <strong>{{ venueDashboardSummary.availableCount }}</strong>
                    <p>Available</p>
                  </div>
                </article>
                <article class="manage-facilities-venue-stat-card manage-facilities-venue-stat-card--reserved">
                  <span class="manage-facilities-venue-stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <rect x="7" y="3" width="10" height="18" rx="2" />
                      <path d="M12 7v10" />
                    </svg>
                  </span>
                  <div>
                    <strong>{{ venueDashboardSummary.reservedCount }}</strong>
                    <p>Reserved</p>
                  </div>
                </article>
                <article class="manage-facilities-venue-stat-card manage-facilities-venue-stat-card--maintenance">
                  <span class="manage-facilities-venue-stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M9 3h6" />
                      <path d="M10 3v4l-4.5 7.5A4 4 0 0 0 8.9 21h6.2a4 4 0 0 0 3.4-6.5L14 7V3" />
                      <path d="M9 14h6" />
                    </svg>
                  </span>
                  <div>
                    <strong>{{ venueDashboardSummary.maintenanceCount }}</strong>
                    <p>Under Maintenance</p>
                  </div>
                </article>
                <article class="manage-facilities-venue-stat-card manage-facilities-venue-stat-card--peak">
                  <span class="manage-facilities-venue-stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="8" />
                      <path d="M12 8v4l2.5 2.5" />
                    </svg>
                  </span>
                  <div>
                    <strong>Peak Time</strong>
                    <p>{{ venueDashboardSummary.peakTimeLabel }}</p>
                  </div>
                </article>
              </div>
            </div>

            <div class="manage-facilities-venue-dashboard">
              <aside class="manage-facilities-venue-calendar-sidebar">
                <section class="manage-facilities-venue-sidebar-card manage-facilities-venue-sidebar-card--calendar">
                  <div class="manage-facilities-venue-mini-calendar-head">
                    <button type="button" class="manage-facilities-venue-mini-nav" aria-label="Previous month" @click="shiftSelectedVenueMonth(-1)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m15 18-6-6 6-6" />
                      </svg>
                    </button>
                    <strong>{{ selectedVenueMonthLabel }}</strong>
                    <button type="button" class="manage-facilities-venue-mini-nav" aria-label="Next month" @click="shiftSelectedVenueMonth(1)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m9 18 6-6-6-6" />
                      </svg>
                    </button>
                  </div>

                  <div class="manage-facilities-venue-mini-weekdays">
                    <span v-for="weekday in compactWeekdayLabels" :key="weekday">{{ weekday }}</span>
                  </div>

                  <div class="manage-facilities-venue-mini-grid">
                    <button
                      v-for="dayCell in venueMonthCalendarCells"
                      :key="dayCell.key"
                      type="button"
                      class="manage-facilities-venue-mini-day"
                      :class="{
                        'manage-facilities-venue-mini-day--muted': !dayCell.inCurrentMonth,
                        'manage-facilities-venue-mini-day--active': dayCell.dateValue === selectedVenueCalendarDate,
                      }"
                      @click="selectVenueDate(dayCell.dateValue)"
                    >
                      {{ dayCell.dayNumber }}
                    </button>
                  </div>
                </section>

                <section class="manage-facilities-venue-sidebar-card">
                  <p class="manage-facilities-venue-card-label">Venue Summary</p>
                  <div class="manage-facilities-venue-summary-list">
                    <div class="manage-facilities-venue-summary-row">
                      <span class="manage-facilities-venue-summary-dot manage-facilities-venue-summary-dot--available"></span>
                      <div>
                        <strong>{{ venueDashboardSummary.availableCount }}</strong>
                        <p>Available venues</p>
                      </div>
                    </div>
                    <div class="manage-facilities-venue-summary-row">
                      <span class="manage-facilities-venue-summary-dot manage-facilities-venue-summary-dot--reserved"></span>
                      <div>
                        <strong>{{ venueDashboardSummary.reservedCount }}</strong>
                        <p>Reserved venues</p>
                      </div>
                    </div>
                    <div class="manage-facilities-venue-summary-row">
                      <span class="manage-facilities-venue-summary-dot manage-facilities-venue-summary-dot--maintenance"></span>
                      <div>
                        <strong>{{ venueDashboardSummary.maintenanceCount }}</strong>
                        <p>Under maintenance</p>
                      </div>
                    </div>
                    <div class="manage-facilities-venue-summary-row">
                      <span class="manage-facilities-venue-summary-dot manage-facilities-venue-summary-dot--peak"></span>
                      <div>
                        <strong>Peak Time</strong>
                        <p>{{ venueDashboardSummary.peakTimeLabel }}</p>
                      </div>
                    </div>
                  </div>
                </section>

                <section class="manage-facilities-venue-sidebar-card">
                  <p class="manage-facilities-venue-card-label">Filters</p>
                  <div class="manage-facilities-venue-filter-list">
                    <label class="manage-facilities-venue-filter-field">
                      <span>Floor</span>
                      <select v-model="showingFilterValue">
                        <option v-for="filterOption in showingFilterOptions" :key="filterOption.value" :value="filterOption.value">
                          {{ filterOption.label }}
                        </option>
                      </select>
                    </label>
                    <label class="manage-facilities-venue-filter-field">
                      <span>Building</span>
                      <select v-model="venueBuildingFilter">
                        <option value="all">All Buildings</option>
                      </select>
                    </label>
                    <label class="manage-facilities-venue-filter-field">
                      <span>Capacity</span>
                      <select v-model="venueCapacityFilter">
                        <option v-for="capacityOption in venueCapacityOptions" :key="capacityOption.value" :value="capacityOption.value">
                          {{ capacityOption.label }}
                        </option>
                      </select>
                    </label>
                  </div>
                </section>
              </aside>

              <section class="manage-facilities-venue-board-panel">
                <div class="manage-facilities-venue-board manage-facilities-venue-board--matrix">
                  <div class="manage-facilities-venue-schedule">
                    <div class="manage-facilities-venue-schedule-rail">
                      <span
                        v-for="timeLabel in venueScheduleRailLabels"
                        :key="timeLabel"
                        class="manage-facilities-venue-schedule-rail-label"
                      >
                        {{ timeLabel }}
                      </span>
                    </div>

                    <div class="manage-facilities-venue-schedule-grid">
                      <article
                        v-for="dayColumn in venueWeekScheduleColumns"
                        :key="dayColumn.dateValue"
                        class="manage-facilities-venue-schedule-day"
                        :class="{ 'manage-facilities-venue-schedule-day--selected': dayColumn.dateValue === selectedVenueCalendarDate }"
                      >
                        <header class="manage-facilities-venue-schedule-day-header">
                          <span>{{ dayColumn.weekdayLabel }}</span>
                          <strong>{{ dayColumn.shortDateLabel }}</strong>
                          <small>{{ dayColumn.entryCountLabel }}</small>
                        </header>

                        <div class="manage-facilities-venue-schedule-day-body">
                          <template v-if="dayColumn.entries.length > 0">
                            <section
                              v-for="timeSlot in dayColumn.timeSlots"
                              :key="`${dayColumn.dateValue}-${timeSlot.key}`"
                              class="manage-facilities-venue-schedule-slot"
                            >
                              <button
                                v-for="entry in timeSlot.entries"
                                :key="entry.key"
                                type="button"
                                class="manage-facilities-venue-schedule-card"
                                @click="handleVenueBlockClick(entry.block, entry.venueRow)"
                              >
                                <span>Blocked reservation window</span>
                                <strong>{{ entry.venueName }}</strong>
                                <p>{{ entry.locationLabel }}</p>
                                <div class="manage-facilities-venue-schedule-card-meta">
                                  <small>Blocked</small>
                                  <small>{{ entry.capacityLabel }}</small>
                                </div>
                              </button>
                            </section>
                          </template>
                          <div v-else class="manage-facilities-venue-schedule-empty">
                            <strong>No reserved rooms surfaced</strong>
                            <p>No reserved rooms were returned for this date.</p>
                          </div>
                        </div>
                      </article>
                    </div>
                  </div>
                </div>

                <section class="manage-facilities-venue-card-section manage-facilities-venue-card-section--directory">
                  <div class="manage-facilities-section-heading manage-facilities-section-heading--directory">
                    <div>
                      <h3>List of Venues</h3>
                    </div>
                    <div class="manage-facilities-venue-directory-actions">
                      <label class="manage-facilities-sort-group manage-facilities-sort-group--sort">
                        <span class="manage-facilities-sort-label manage-facilities-sort-label--inline">Sort by:</span>
                        <select v-model="sortValue" class="manage-facilities-sort-select">
                          <option value="asc">Name (A-Z)</option>
                          <option value="desc">Name (Z-A)</option>
                        </select>
                      </label>
                    </div>
                  </div>

                  <div v-if="paginatedVenueListRecords.length > 0" class="manage-facilities-venue-directory-grid">
                    <article
                      v-for="venueRecord in paginatedVenueListRecords"
                      :key="`directory-${venueRecord.venueIdentifier}`"
                      class="manage-facilities-venue-directory-card"
                    >
                      <div class="manage-facilities-venue-directory-media">
                        <img :src="resolveVenuePhoto(venueRecord)" :alt="`${venueRecord.venueName} photo`" class="manage-facilities-venue-directory-photo" />
                      </div>
                      <div class="manage-facilities-venue-directory-copy">
                        <div class="manage-facilities-venue-directory-topline">
                          <div>
                            <h4>{{ venueRecord.venueName }}</h4>
                            <p>{{ venueRecord.floorLevel || 'Other Floor' }} <span>|</span> {{ venueRecord.venueLocation || 'Tech Center' }}</p>
                          </div>
                          <span class="manage-facilities-venue-directory-status" :class="`manage-facilities-venue-directory-status--${venueRecord.directoryStatusTone}`">
                            {{ venueRecord.directoryStatusLabel }}
                          </span>
                        </div>
                        <div class="manage-facilities-venue-directory-meta">
                          <span>Capacity {{ venueRecord.capacityLimit || 'N/A' }}</span>
                          <span>{{ venueRecord.directoryMetaLabel }}</span>
                        </div>
                        <p>{{ venueRecord.description || 'Venue details and availability notes are available through the admin actions below.' }}</p>
                        <div class="manage-facilities-venue-directory-utilization">
                          <div class="manage-facilities-venue-directory-utilization-copy">
                            <span>Utilization</span>
                            <strong>{{ venueRecord.utilizationPercent }}%</strong>
                          </div>
                          <div class="manage-facilities-venue-directory-progress">
                            <div class="manage-facilities-venue-directory-progress-bar" :style="{ width: `${venueRecord.utilizationPercent}%` }"></div>
                          </div>
                        </div>
                        <div class="manage-facilities-venue-directory-actions-row">
                          <button type="button" @click="handleViewVenue(venueRecord)">View</button>
                          <button type="button" @click="handleEditVenue(venueRecord)">Edit</button>
                          <button type="button" class="manage-facilities-venue-directory-delete" @click="handleDeleteVenue(venueRecord)">Delete</button>
                        </div>
                      </div>
                    </article>
                  </div>

                  <div v-else class="manage-facilities-venue-card-empty">
                    <strong>No venues match the active filters</strong>
                    <p>Try changing the floor or capacity filters to surface more venue records.</p>
                  </div>

                  <div class="manage-facilities-venue-directory-footer">
                    <span>Showing {{ venueListDisplayStart }} to {{ venueListDisplayEnd }} of {{ venueDirectoryRecords.length }} venues</span>
                    <div class="manage-facilities-venue-directory-pagination">
                      <button type="button" :disabled="venueListCurrentPage === 1" @click="venueListCurrentPage -= 1">Previous</button>
                      <span>Page {{ venueListCurrentPage }} of {{ venueListTotalPages }}</span>
                      <button type="button" :disabled="venueListCurrentPage === venueListTotalPages" @click="venueListCurrentPage += 1">Next</button>
                      <label>
                        <select v-model.number="venueListPageSize">
                          <option :value="4">4 per page</option>
                          <option :value="8">8 per page</option>
                          <option :value="12">12 per page</option>
                        </select>
                      </label>
                    </div>
                  </div>
                </section>
              </section>
            </div>
          </div>
        </section>
      </div>

      <div v-else-if="activeFacilityTab === 'all'">
        <div v-if="loading && venuesList.length === 0" class="manage-facilities-loading">Loading venue operations...</div>
        <p v-else-if="venueError && venuesList.length === 0" class="manage-facilities-modal-error">{{ venueError }}</p>
        <div v-else class="manage-facilities-venue-layout">
          <aside class="manage-facilities-venue-sidebar">
            <VenueAvailabilityCalendarComponent
              :venues="calendarVenueRecords"
              :selected-date="selectedVenueCalendarDate"
              @update:selected-date="selectedVenueCalendarDate = $event"
            />
          </aside>

          <div class="manage-facilities-venue-content">
            <FacilityVenueListComponent
              :venue-floor-groups="paginatedVenueFloorGroups"
              :availability-filter="availabilityFilter"
              @view-venue="handleViewVenue"
              @edit-venue="handleEditVenue"
              @delete-venue="handleDeleteVenue"
            />
            <div class="manage-facilities-venue-directory-footer">
              <span>Showing {{ venueListDisplayStart }} to {{ venueListDisplayEnd }} of {{ venueDirectoryRecords.length }} venues</span>
              <div class="manage-facilities-venue-directory-pagination">
                <button type="button" :disabled="venueListCurrentPage === 1" @click="venueListCurrentPage -= 1">Previous</button>
                <span>Page {{ venueListCurrentPage }} of {{ venueListTotalPages }}</span>
                <button type="button" :disabled="venueListCurrentPage === venueListTotalPages" @click="venueListCurrentPage += 1">Next</button>
                <label>
                  <select v-model.number="venueListPageSize">
                    <option :value="4">4 per page</option>
                    <option :value="8">8 per page</option>
                    <option :value="12">12 per page</option>
                  </select>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="activeFacilityTab === 'classroom-schedules'" class="classroom-schedule-shell">
        <div class="classroom-schedule-section-heading">
          <h3>Classroom Schedules</h3>
          <p>View and manage all classroom schedules and reservation blocks from one shared calendar.</p>
        </div>
        <div v-if="classScheduleLoading" class="manage-facilities-loading">Loading classroom schedules...</div>
        <p v-else-if="classScheduleError" class="manage-facilities-modal-error">{{ classScheduleError }}</p>
        <div v-else class="classroom-schedule-layout">
          <aside class="classroom-schedule-sidebar">
            <section class="classroom-schedule-date-card">
              <div class="classroom-schedule-date-badge">
                <span>{{ selectedClassroomDateMonthShort }}</span>
                <strong>{{ selectedClassroomDateDayNumber }}</strong>
              </div>
              <div>
                <h3>{{ selectedClassroomDateHeading }}</h3>
                <p>{{ selectedClassroomDateWeekday }}</p>
              </div>
            </section>

            <section class="classroom-schedule-mini-calendar">
              <div class="classroom-schedule-mini-calendar__header">
                <button type="button" @click="shiftClassroomMonth(-1)">‹</button>
                <strong>{{ classroomMonthHeading }}</strong>
                <button type="button" @click="shiftClassroomMonth(1)">›</button>
              </div>
              <div class="classroom-schedule-mini-calendar__weekdays">
                <span v-for="weekdayLabel in miniCalendarWeekdays" :key="weekdayLabel">{{ weekdayLabel }}</span>
              </div>
              <div class="classroom-schedule-mini-calendar__grid">
                <button
                  v-for="calendarDay in classroomMonthDays"
                  :key="calendarDay.dateKey"
                  type="button"
                  class="classroom-schedule-mini-calendar__day"
                  :class="{
                    'is-outside': !calendarDay.isCurrentMonth,
                    'is-selected': calendarDay.dateKey === selectedClassroomDate,
                    'has-schedules': calendarDay.hasSchedules,
                  }"
                  @click="selectClassroomDate(calendarDay.dateKey)"
                >
                  {{ calendarDay.dayNumber }}
                </button>
              </div>
            </section>

            <section class="classroom-schedule-selected-card">
              <h4>Selected Schedule</h4>
              <template v-if="selectedClassScheduleRecord">
                <strong>{{ selectedClassScheduleRecord.courseCode || selectedClassScheduleRecord.blockLabel }}</strong>
                <p>{{ selectedClassScheduleRecord.courseName || selectedClassScheduleRecord.blockLabel }}</p>
                <div class="classroom-schedule-selected-meta">
                  <span>{{ selectedClassScheduleRecord.venueNameSnapshot || selectedClassScheduleRecord.venueName || 'Venue not set' }}</span>
                  <span>{{ formatClassroomScheduleTime(selectedClassScheduleRecord.startTime, selectedClassScheduleRecord.endTime) }}</span>
                  <span>{{ selectedClassScheduleRecord.instructorName || 'Instructor not specified' }}</span>
                </div>
                <button type="button" class="classroom-schedule-sidebar-button" @click="openClassScheduleDetails(selectedClassScheduleRecord)">
                  View Details
                </button>
              </template>
              <p v-else class="classroom-schedule-empty-copy">No classroom schedule is selected for this date.</p>
            </section>

            <section class="classroom-schedule-legend-card">
              <h4>Legend</h4>
              <div class="classroom-schedule-legend-list">
                <span><i class="classroom-schedule-legend-dot classroom-schedule-legend-dot--class"></i>Class Schedule</span>
                <span><i class="classroom-schedule-legend-dot classroom-schedule-legend-dot--reserved"></i>Reserved</span>
                <span><i class="classroom-schedule-legend-dot classroom-schedule-legend-dot--equipment"></i>Equipment Reservation</span>
                <span><i class="classroom-schedule-legend-dot classroom-schedule-legend-dot--pending"></i>Pending</span>
                <span><i class="classroom-schedule-legend-dot classroom-schedule-legend-dot--maintenance"></i>Maintenance</span>
              </div>
            </section>
          </aside>

          <section class="classroom-schedule-board-card">
            <div class="classroom-schedule-board-toolbar">
              <button type="button" class="classroom-schedule-board-icon-button classroom-schedule-board-icon-button--search" aria-label="Search schedules">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="11" cy="11" r="7" />
                  <path d="m20 20-3.5-3.5" />
                </svg>
              </button>
              <button type="button" class="classroom-schedule-board-icon-button" @click="jumpClassroomDateByDays(-7)">‹</button>
              <button type="button" class="classroom-schedule-board-today" @click="selectClassroomDate(todayDateKey)">Today</button>
              <button type="button" class="classroom-schedule-board-icon-button" @click="jumpClassroomDateByDays(7)">›</button>
              <div class="classroom-schedule-board-spacer"></div>
              <div class="classroom-schedule-board-toolbar-group classroom-schedule-board-toolbar-group--end">
                <select v-model="classroomBoardView" class="classroom-schedule-board-view-select">
                  <option value="Weekly View">Weekly View</option>
                </select>
                <div class="classroom-schedule-action-menu">
                  <button
                    type="button"
                    class="manage-facilities-add-button manage-facilities-add-button--compact classroom-schedule-add-button"
                    @click="toggleClassScheduleActionMenu"
                  >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="12" y1="5" x2="12" y2="19"/>
                      <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Add Schedule
                  </button>
                  <div v-if="showClassScheduleActionMenu" class="classroom-schedule-action-menu__panel">
                    <button type="button" @click="openManualClassScheduleModal">Add Manually</button>
                    <button type="button" @click="openQuickAddScheduleModal()">Quick Add</button>
                    <button type="button" @click="openImportSchedulesModal">Import Schedules</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="classroom-schedule-board-grid">
              <div class="classroom-schedule-board-grid__header"></div>
              <div
                v-for="weekDate in classroomWeekDates"
                :key="weekDate.dateKey"
                class="classroom-schedule-board-day-header"
                :class="{ 'is-selected': weekDate.dateKey === selectedClassroomDate }"
              >
                <span>{{ weekDate.weekday }}</span>
                <strong>{{ weekDate.monthDay }}</strong>
              </div>

              <div class="classroom-schedule-board-time-column">
                <span v-for="timeLabel in classroomTimeLabels" :key="timeLabel">{{ timeLabel }}</span>
              </div>

              <div
                v-for="weekDate in classroomWeekDates"
                :key="`${weekDate.dateKey}-column`"
                class="classroom-schedule-board-day-column"
                @dblclick="openQuickAddScheduleModal(weekDate.dateKey)"
              >
                <button
                  v-for="scheduleRecord in getSchedulesForClassroomDay(weekDate.dateKey)"
                  :key="scheduleRecord.scheduleBlockIdentifier"
                  type="button"
                  class="classroom-schedule-event"
                  :class="classroomScheduleEventClass(scheduleRecord.blockType)"
                  :style="buildClassroomScheduleStyle(scheduleRecord)"
                  @click="openClassScheduleDetails(scheduleRecord)"
                >
                  <small>{{ formatClassroomScheduleTime(scheduleRecord.startTime, scheduleRecord.endTime) }}</small>
                  <strong>{{ scheduleRecord.courseCode || scheduleRecord.blockLabel }}</strong>
                  <span>{{ scheduleRecord.courseName || scheduleRecord.blockLabel }}</span>
                  <small>{{ scheduleRecord.venueNameSnapshot || scheduleRecord.venueName || 'Venue' }}</small>
                  <small v-if="scheduleRecord.capacityLimit">Capacity {{ scheduleRecord.capacityLimit }}</small>
                </button>
              </div>
            </div>
          </section>
        </div>
      </div>
    </section>

    <div v-if="activeFacilityTab === 'equipment' && equipmentLoading && equipmentList.length === 0" class="manage-facilities-loading">Loading equipment...</div>
    <p v-else-if="activeFacilityTab === 'equipment' && equipmentError && equipmentList.length === 0" class="manage-facilities-modal-error">{{ equipmentError }}</p>
    <section v-else-if="activeFacilityTab === 'equipment'" class="manage-facilities-equipment-section">
      <div class="manage-facilities-dispatch-summary">
        <div class="manage-facilities-dispatch-summary-card">
          <span class="manage-facilities-summary-label">Dispatched today</span>
          <strong class="manage-facilities-summary-value">{{ dispatchedEquipmentCount }}</strong>
          <p class="manage-facilities-summary-note">Equipment items currently out for today's prepared or deployed reservations.</p>
        </div>
        <div class="manage-facilities-dispatch-summary-card">
          <span class="manage-facilities-summary-label">Units to surrender</span>
          <strong class="manage-facilities-summary-value">{{ dispatchedEquipmentUnits }}</strong>
          <p class="manage-facilities-summary-note">Total equipment quantity expected to be surrendered after today's activities.</p>
        </div>
        <div class="manage-facilities-dispatch-summary-list">
          <h3>Needs surrender monitoring</h3>
          <p v-if="dispatchSummaryRecords.length === 0" class="manage-facilities-summary-note">No equipment is marked as dispatched for today.</p>
          <ul v-else>
            <li v-for="equipmentRecord in dispatchSummaryRecords" :key="equipmentRecord.equipmentIdentifier">
              <strong>{{ formatEquipmentText(equipmentRecord.equipmentName) }}</strong>
              <span>{{ equipmentRecord.dispatchedTodayQuantity }} unit<span v-if="equipmentRecord.dispatchedTodayQuantity !== 1">s</span> across {{ equipmentRecord.dispatchedTodayReservationCount }} reservation<span v-if="equipmentRecord.dispatchedTodayReservationCount !== 1">s</span></span>
            </li>
          </ul>
        </div>
      </div>

      <FacilityEquipmentGridComponent
        :equipment-records="paginatedEquipmentRecords"
        :availability-filter="availabilityFilter"
        :selected-equipment-identifier="selectedEquipmentCard?.equipmentIdentifier || null"
        :view-mode="equipmentViewMode"
        @edit-equipment="handleEditEquipment"
        @delete-equipment="openDeleteEquipmentModal"
        @view-equipment="handleViewEquipment"
        @select-equipment="handleSelectEquipment"
      />
      <div class="manage-facilities-venue-directory-footer manage-facilities-equipment-pagination-footer">
        <span>Showing {{ equipmentDisplayStart }} to {{ equipmentDisplayEnd }} of {{ filteredEquipmentRecords.length }} equipment records</span>
        <div class="manage-facilities-venue-directory-pagination">
          <button type="button" :disabled="equipmentCurrentPage === 1" @click="equipmentCurrentPage -= 1">Previous</button>
          <span>Page {{ equipmentCurrentPage }} of {{ equipmentTotalPages }}</span>
          <button type="button" :disabled="equipmentCurrentPage === equipmentTotalPages" @click="equipmentCurrentPage += 1">Next</button>
          <label>
            <select v-model.number="equipmentPageSize">
              <option :value="6">6 per page</option>
              <option :value="12">12 per page</option>
              <option :value="24">24 per page</option>
            </select>
          </label>
        </div>
      </div>
    </section>

    <div class="manage-facilities-page-footer">
      TechReserve facilities operations workspace.
    </div>

    <div
      v-if="showClassScheduleModal"
      class="manage-facilities-modal-overlay"
      @click.self="closeClassScheduleModal"
    >
      <section class="manage-facilities-delete-modal classroom-schedule-modal">
        <button class="manage-facilities-modal-close" type="button" aria-label="Close" @click="closeClassScheduleModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
          </svg>
        </button>

        <div class="manage-facilities-modal-heading">
          <h2>{{ classScheduleFormMode === 'edit' ? 'Edit Classroom Schedule' : 'Add Classroom Schedule' }}</h2>
          <p>Manage classroom schedule blocks that should appear in the facilities calendar and block venue booking conflicts.</p>
        </div>

        <form class="classroom-schedule-form" @submit.prevent="submitClassSchedule">
          <div class="classroom-schedule-form-section-title">Schedule Information</div>
          <div class="classroom-schedule-form-grid">
            <label>
              <span>Course Code</span>
              <input v-model.trim="classScheduleForm.courseCode" type="text" placeholder="IT321" />
            </label>
            <label>
              <span>Course Name</span>
              <input v-model.trim="classScheduleForm.courseName" type="text" placeholder="Data Structures" />
            </label>
            <label>
              <span>Instructor</span>
              <input v-model.trim="classScheduleForm.instructorName" type="text" placeholder="Prof. Juan Dela Cruz" />
            </label>
            <label>
              <span>Venue / Room</span>
              <select v-model.number="classScheduleForm.venueIdentifier">
                <option :value="0">Select room</option>
                <option v-for="venueOption in classroomVenueOptions" :key="venueOption.venueIdentifier" :value="venueOption.venueIdentifier">
                  {{ venueOption.venueName }}
                </option>
              </select>
            </label>
            <label>
              <span>Schedule Type</span>
              <select v-model="classScheduleForm.blockType">
                <option v-for="typeOption in classroomScheduleTypeOptions" :key="typeOption" :value="typeOption">{{ typeOption }}</option>
              </select>
            </label>
            <label>
              <span>Academic Year</span>
              <select v-model="classScheduleForm.academicYear">
                <option value="">Select academic year</option>
                <option v-for="academicYearOption in academicYearOptions" :key="academicYearOption" :value="academicYearOption">{{ academicYearOption }}</option>
              </select>
            </label>
            <label>
              <span>Semester</span>
              <select v-model="classScheduleForm.semesterLabel">
                <option value="">Select semester</option>
                <option v-for="semesterOption in semesterOptions" :key="semesterOption" :value="semesterOption">{{ semesterOption }}</option>
              </select>
            </label>
            <label>
              <span>Capacity</span>
              <input v-model.number="classScheduleForm.capacityLimit" type="number" min="0" placeholder="40" />
            </label>
          </div>

          <div class="classroom-schedule-form-days">
            <span>Days</span>
            <div class="classroom-schedule-form-days__grid">
              <label v-for="dayLabel in classroomDayOptions" :key="dayLabel">
                <input v-model="classScheduleForm.daysOfWeek" type="checkbox" :value="dayLabel" />
                <span>{{ dayLabel }}</span>
              </label>
            </div>
          </div>

          <div class="classroom-schedule-form-grid">
            <label>
              <span>Start Time</span>
              <input v-model="classScheduleForm.startTime" type="time" />
            </label>
            <label>
              <span>End Time</span>
              <input v-model="classScheduleForm.endTime" type="time" />
            </label>
            <label>
              <span>Start Date</span>
              <input v-model="classScheduleForm.dateRangeStart" type="date" />
            </label>
            <label>
              <span>End Date</span>
              <input v-model="classScheduleForm.dateRangeEnd" type="date" />
            </label>
          </div>

          <p class="classroom-schedule-form-duration">Duration: {{ activeClassScheduleDuration }}</p>

          <label class="classroom-schedule-form-notes">
            <span>Notes</span>
            <textarea v-model.trim="classScheduleForm.notes" rows="3" placeholder="Regular class schedule notes"></textarea>
          </label>

          <p v-if="classScheduleModalError" class="manage-facilities-modal-error">{{ classScheduleModalError }}</p>

          <div class="manage-facilities-modal-actions">
            <button class="manage-facilities-cancel-button" type="button" @click="closeClassScheduleModal">Cancel</button>
            <button class="manage-facilities-delete-confirm-button manage-facilities-delete-confirm-button--neutral" type="submit" :disabled="isSavingClassSchedule">
              {{ isSavingClassSchedule ? 'Saving...' : (classScheduleFormMode === 'edit' ? 'Save Schedule' : 'Add Schedule') }}
            </button>
          </div>
        </form>
      </section>
    </div>

    <div
      v-if="viewClassScheduleRecord"
      class="manage-facilities-modal-overlay"
      @click.self="closeClassScheduleDetails"
    >
      <section class="manage-facilities-delete-modal classroom-schedule-detail-modal">
        <button class="manage-facilities-modal-close" type="button" aria-label="Close" @click="closeClassScheduleDetails">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
          </svg>
        </button>
        <div class="manage-facilities-modal-heading">
          <h2>Schedule Details</h2>
          <p>Review, edit, or remove this classroom schedule block.</p>
        </div>

        <div class="classroom-schedule-detail-hero">
          <div>
            <h3>{{ viewClassScheduleRecord.courseCode || viewClassScheduleRecord.blockLabel }}</h3>
            <p>{{ viewClassScheduleRecord.courseName || 'Classroom schedule block' }}</p>
          </div>
          <span class="classroom-schedule-detail-badge" :class="classroomScheduleEventClass(viewClassScheduleRecord.blockType)">
            {{ viewClassScheduleRecord.blockType }}
          </span>
        </div>

        <div class="classroom-schedule-detail-grid">
          <div><span>Course</span><strong>{{ viewClassScheduleRecord.courseCode || viewClassScheduleRecord.blockLabel }}</strong></div>
          <div><span>Course Name</span><strong>{{ viewClassScheduleRecord.courseName || viewClassScheduleRecord.blockLabel }}</strong></div>
          <div><span>Instructor</span><strong>{{ viewClassScheduleRecord.instructorName || 'Not specified' }}</strong></div>
          <div><span>Room</span><strong>{{ viewClassScheduleRecord.venueNameSnapshot || viewClassScheduleRecord.venueName || 'Not specified' }}</strong></div>
          <div><span>Days</span><strong>{{ formatScheduleDays(viewClassScheduleRecord.daysOfWeek) }}</strong></div>
          <div><span>Time</span><strong>{{ formatClassroomScheduleTime(viewClassScheduleRecord.startTime, viewClassScheduleRecord.endTime) }}</strong></div>
          <div><span>Date Range</span><strong>{{ formatScheduleDateRange(viewClassScheduleRecord.dateRangeStart || viewClassScheduleRecord.blockDate, viewClassScheduleRecord.dateRangeEnd || viewClassScheduleRecord.blockDate) }}</strong></div>
          <div><span>Schedule Type</span><strong>{{ viewClassScheduleRecord.blockType }}</strong></div>
          <div><span>Capacity</span><strong>{{ viewClassScheduleRecord.capacityLimit || 'N/A' }}</strong></div>
          <div><span>Created By</span><strong>{{ currentAdminDisplayName }}</strong></div>
          <div><span>Notes</span><strong>{{ viewClassScheduleRecord.notes || 'No notes added.' }}</strong></div>
        </div>

        <div class="manage-facilities-modal-actions">
          <button class="manage-facilities-cancel-button" type="button" @click="openEditClassSchedule(viewClassScheduleRecord)">Edit Schedule</button>
          <button class="manage-facilities-delete-confirm-button" type="button" :disabled="isDeletingClassSchedule" @click="deleteClassSchedule(viewClassScheduleRecord)">
            {{ isDeletingClassSchedule ? 'Deleting...' : 'Delete Schedule' }}
          </button>
        </div>
      </section>
    </div>

    <div
      v-if="showImportSchedulesModal"
      class="manage-facilities-modal-overlay"
      @click.self="closeImportSchedulesModal"
    >
      <section class="manage-facilities-delete-modal classroom-schedule-import-modal">
        <button class="manage-facilities-modal-close" type="button" aria-label="Close" @click="closeImportSchedulesModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
          </svg>
        </button>
        <div class="manage-facilities-modal-heading">
          <h2>Import Classroom Schedules</h2>
          <p>Upload a schedule file to stage classroom schedule imports for admin review.</p>
        </div>

        <div class="classroom-schedule-import-shell">
          <div class="classroom-schedule-import-steps">
            <span
              v-for="(step, stepIndex) in importScheduleSteps"
              :key="step.key"
              :class="{
                'is-active': step.key === importScheduleStep,
                'is-complete': stepIndex < importScheduleStepIndex,
              }"
            >
              {{ step.label }}
            </span>
          </div>
          <p v-if="importScheduleError" class="manage-facilities-modal-error">{{ importScheduleError }}</p>

          <template v-if="importScheduleStep === 'upload'">
            <label class="classroom-schedule-import-dropzone">
              <input type="file" accept=".xlsx,.xls,.csv" @change="handleImportFileChange" />
              <strong>{{ importScheduleFile ? importScheduleFile.name : 'Drag and drop your file here, or click to browse' }}</strong>
              <span>Supports `.xlsx`, `.xls`, and `.csv` files.</span>
            </label>
            <div class="classroom-schedule-import-toolbar">
              <button class="manage-facilities-cancel-button classroom-schedule-import-template-button" type="button" @click="downloadClassScheduleImportTemplate">
                Download Official Template
              </button>
            </div>
            <div v-if="importScheduleFile" class="classroom-schedule-import-file-row">
              <div>
                <strong>{{ importScheduleFile.name }}</strong>
                <span>{{ formatImportFileSize(importScheduleFile.size) }}</span>
              </div>
              <span class="classroom-schedule-import-file-check">
                {{ importScheduleParsing ? 'Parsing...' : importScheduleParsedRowCount > 0 ? 'Ready' : 'Waiting' }}
              </span>
            </div>

            <div v-if="importScheduleHeaders.length > 0" class="classroom-schedule-import-summary">
              <article>
                <strong>{{ importScheduleHeaders.length }}</strong>
                <span>Detected columns</span>
              </article>
              <article>
                <strong>{{ importScheduleParsedRowCount }}</strong>
                <span>Parsed rows</span>
              </article>
              <article>
                <strong>{{ importScheduleDetectedSheetLabel }}</strong>
                <span>Detected sheet</span>
              </article>
              <article>
                <strong>{{ importScheduleDetectedHeaderRowLabel }}</strong>
                <span>Detected header row</span>
              </article>
            </div>

            <p class="classroom-schedule-import-note">Upload a classroom schedule file, then map each detected Excel column before review and import.</p>
            <p v-if="importScheduleDetectionNotice" class="classroom-schedule-import-note classroom-schedule-import-note--status">{{ importScheduleDetectionNotice }}</p>

            <div class="classroom-schedule-import-template-panel">
              <strong>Use the official classroom schedule template for the smoothest import.</strong>
              <span>Extra Excel columns are allowed and can be left as Ignore during mapping.</span>
            </div>
          </template>

          <template v-else-if="importScheduleStep === 'map'">
            <div class="classroom-schedule-import-summary">
              <article>
                <strong>{{ importScheduleMinimumRequiredMappingsCount }}</strong>
                <span>Minimum required mappings</span>
              </article>
              <article>
                <strong>{{ importScheduleMappedHeaderCount }}</strong>
                <span>Excel columns mapped</span>
              </article>
              <article>
                <strong>{{ importScheduleRemainingRequiredMappingsCount }}</strong>
                <span>Required mappings left</span>
              </article>
              <article>
                <strong>{{ importScheduleParsedRowCount }}</strong>
                <span>Rows ready for review</span>
              </article>
            </div>

            <div class="classroom-schedule-import-detection-panel">
              <div>
                <strong>{{ importScheduleDetectedSheetLabel }}</strong>
                <span>Detected sheet</span>
              </div>
              <div>
                <strong>{{ importScheduleDetectedHeaderRowLabel }}</strong>
                <span>Detected header row</span>
              </div>
            </div>

            <p class="classroom-schedule-import-note">Map each file column to the matching classroom schedule field. Leave unrelated columns as Ignore.</p>
            <p v-if="importScheduleDetectionNotice" class="classroom-schedule-import-note classroom-schedule-import-note--status">{{ importScheduleDetectionNotice }}</p>

            <div v-if="importScheduleMissingMappings.length > 0" class="classroom-schedule-import-warning">
              {{ importScheduleMissingMappingsNotice }}
            </div>

            <div class="classroom-schedule-import-mapping-grid">
              <article v-for="header in importScheduleHeaders" :key="header" class="classroom-schedule-import-mapping-card">
                <div class="classroom-schedule-import-mapping-card__header">
                  <strong>{{ header }}</strong>
                  <small
                    class="classroom-schedule-import-mapping-card__status"
                    :class="`classroom-schedule-import-mapping-card__status--${getImportMappingStatusForHeader(header).tone}`"
                  >
                    {{ getImportMappingStatusForHeader(header).label }}
                  </small>
                  <span>Sample: {{ importSchedulePreviewCell(header, importSchedulePreviewRow) }}</span>
                </div>
                <select
                  :value="getImportSelectedTargetForHeader(header)"
                  @change="updateImportTargetMapping(header, $event.target.value)"
                >
                  <option value="">Ignore this column</option>
                  <option
                    v-for="targetOption in importScheduleTargetOptions"
                    :key="targetOption.key"
                    :value="targetOption.key"
                    :disabled="isImportTargetTaken(targetOption.key, header)"
                  >
                    {{ targetOption.label }}{{ targetOption.required ? ' *' : '' }}
                  </option>
                </select>
              </article>
            </div>
          </template>

          <template v-else>
            <div class="classroom-schedule-import-summary">
              <article>
                <strong>{{ importScheduleReadyReviewRows.length }}</strong>
                <span>Ready rows</span>
              </article>
              <article>
                <strong>{{ importScheduleInvalidReviewRows.length }}</strong>
                <span>Invalid rows</span>
              </article>
              <article>
                <strong>{{ importScheduleParsedRowCount }}</strong>
                <span>Total rows</span>
              </article>
              <article v-if="importScheduleHasResults">
                <strong>{{ importScheduleResult.importedCount }}</strong>
                <span>Imported rows</span>
              </article>
            </div>

            <p v-if="importScheduleStep === 'review'" class="classroom-schedule-import-note">Review invalid rows first, then confirm the ready rows that will be imported.</p>
            <p v-else class="classroom-schedule-import-note">Import results are listed below. Rows that failed to save include their backend error.</p>

            <div v-if="importScheduleImporting" class="classroom-schedule-import-progress">
              Importing {{ importScheduleResult?.processedCount || 0 }} of {{ importScheduleValidReviewRows.length }} valid rows...
            </div>

            <div class="classroom-schedule-import-review-list">
              <template v-if="importScheduleStep === 'review'">
                <section v-if="importScheduleInvalidReviewRows.length > 0" class="classroom-schedule-import-review-section">
                  <div class="classroom-schedule-import-review-section__header">
                    <strong>Needs attention</strong>
                    <span>{{ importScheduleInvalidReviewRows.length }} row<span v-if="importScheduleInvalidReviewRows.length !== 1">s</span></span>
                  </div>
                  <article
                    v-for="reviewRow in importScheduleInvalidReviewRows"
                    :key="reviewRow.rowNumber"
                    class="classroom-schedule-import-review-card"
                    :class="{
                      'is-valid': reviewRow.isValid,
                      'is-invalid': !reviewRow.isValid,
                      'is-imported': reviewRow.importStatus === 'imported',
                      'is-failed': reviewRow.importStatus === 'failed',
                    }"
                  >
                    <div class="classroom-schedule-import-review-card__top">
                      <div>
                        <strong>Row {{ reviewRow.rowNumber }}</strong>
                        <span>{{ reviewRow.venueLabel }} | {{ reviewRow.scheduleLabel }}</span>
                      </div>
                      <span class="classroom-schedule-import-review-card__badge">
                        {{ reviewRow.importStatusLabel }}
                      </span>
                    </div>

                    <div class="classroom-schedule-import-review-card__details">
                      <span>{{ reviewRow.dateLabel }}</span>
                      <span>{{ reviewRow.timeLabel }}</span>
                      <span>{{ reviewRow.typeLabel }}</span>
                    </div>

                    <ul v-if="reviewRow.errors.length > 0" class="classroom-schedule-import-review-card__errors">
                      <li v-for="errorMessage in reviewRow.errors" :key="`${reviewRow.rowNumber}-${errorMessage}`">{{ errorMessage }}</li>
                    </ul>
                  </article>
                </section>

                <section v-if="importScheduleReadyReviewRows.length > 0" class="classroom-schedule-import-review-section classroom-schedule-import-review-section--ready">
                  <div class="classroom-schedule-import-review-section__header">
                    <strong>Ready to import</strong>
                    <span>{{ importScheduleReadyReviewRows.length }} row<span v-if="importScheduleReadyReviewRows.length !== 1">s</span></span>
                  </div>
                  <article
                    v-for="reviewRow in importScheduleReadyReviewRows"
                    :key="reviewRow.rowNumber"
                    class="classroom-schedule-import-review-card"
                    :class="{
                      'is-valid': reviewRow.isValid,
                      'is-invalid': !reviewRow.isValid,
                      'is-imported': reviewRow.importStatus === 'imported',
                      'is-failed': reviewRow.importStatus === 'failed',
                    }"
                  >
                    <div class="classroom-schedule-import-review-card__top">
                      <div>
                        <strong>Row {{ reviewRow.rowNumber }}</strong>
                        <span>{{ reviewRow.venueLabel }} | {{ reviewRow.scheduleLabel }}</span>
                      </div>
                      <span class="classroom-schedule-import-review-card__badge">
                        {{ reviewRow.importStatusLabel }}
                      </span>
                    </div>

                    <div class="classroom-schedule-import-review-card__details">
                      <span>{{ reviewRow.dateLabel }}</span>
                      <span>{{ reviewRow.timeLabel }}</span>
                      <span>{{ reviewRow.typeLabel }}</span>
                    </div>

                    <ul v-if="reviewRow.errors.length > 0" class="classroom-schedule-import-review-card__errors">
                      <li v-for="errorMessage in reviewRow.errors" :key="`${reviewRow.rowNumber}-${errorMessage}`">{{ errorMessage }}</li>
                    </ul>
                  </article>
                </section>
              </template>

              <template v-else>
                <article
                  v-for="reviewRow in importScheduleDisplayReviewRows"
                  :key="reviewRow.rowNumber"
                  class="classroom-schedule-import-review-card"
                  :class="{
                    'is-valid': reviewRow.isValid,
                    'is-invalid': !reviewRow.isValid,
                    'is-imported': reviewRow.importStatus === 'imported',
                    'is-failed': reviewRow.importStatus === 'failed',
                  }"
                >
                  <div class="classroom-schedule-import-review-card__top">
                    <div>
                      <strong>Row {{ reviewRow.rowNumber }}</strong>
                      <span>{{ reviewRow.venueLabel }} | {{ reviewRow.scheduleLabel }}</span>
                    </div>
                    <span class="classroom-schedule-import-review-card__badge">
                      {{ reviewRow.importStatusLabel }}
                    </span>
                  </div>

                  <div class="classroom-schedule-import-review-card__details">
                    <span>{{ reviewRow.dateLabel }}</span>
                    <span>{{ reviewRow.timeLabel }}</span>
                    <span>{{ reviewRow.typeLabel }}</span>
                  </div>

                  <ul v-if="reviewRow.errors.length > 0" class="classroom-schedule-import-review-card__errors">
                    <li v-for="errorMessage in reviewRow.errors" :key="`${reviewRow.rowNumber}-${errorMessage}`">{{ errorMessage }}</li>
                  </ul>
                </article>
              </template>
            </div>
          </template>
        </div>

        <div class="manage-facilities-modal-actions">
          <button
            v-if="importScheduleStep !== 'upload' && !importScheduleImporting"
            class="manage-facilities-cancel-button"
            type="button"
            @click="goToPreviousImportStep"
          >
            Back
          </button>
          <button class="manage-facilities-cancel-button" type="button" @click="closeImportSchedulesModal">
            {{ importScheduleHasResults && importScheduleStep === 'import' ? 'Close' : 'Cancel' }}
          </button>
          <button
            v-if="importScheduleStep === 'upload'"
            class="manage-facilities-delete-confirm-button manage-facilities-delete-confirm-button--neutral"
            type="button"
            :disabled="!importScheduleCanAdvanceFromUpload"
            @click="goToNextImportStep"
          >
            Next
          </button>
          <button
            v-else-if="importScheduleStep === 'map'"
            class="manage-facilities-delete-confirm-button manage-facilities-delete-confirm-button--neutral"
            type="button"
            :disabled="!importScheduleCanAdvanceFromMap"
            @click="goToNextImportStep"
          >
            Review Rows
          </button>
          <button
            v-else-if="importScheduleStep === 'review'"
            class="manage-facilities-delete-confirm-button manage-facilities-delete-confirm-button--neutral"
            type="button"
            :disabled="!importScheduleCanImport"
            @click="submitImportedSchedules"
          >
            {{ importScheduleImporting ? 'Importing...' : `Import ${importScheduleValidReviewRows.length} Valid Row${importScheduleValidReviewRows.length === 1 ? '' : 's'}` }}
          </button>
        </div>
      </section>
    </div>

    <div
      v-if="showQuickClassScheduleModal"
      class="manage-facilities-modal-overlay"
      @click.self="closeQuickAddScheduleModal"
    >
      <section class="manage-facilities-delete-modal classroom-schedule-quick-modal">
        <button class="manage-facilities-modal-close" type="button" aria-label="Close" @click="closeQuickAddScheduleModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
          </svg>
        </button>

        <div class="manage-facilities-modal-heading">
          <h2>Quick Add Schedule</h2>
          <p>{{ quickAddScheduleSummary }}</p>
        </div>

        <form class="classroom-schedule-form classroom-schedule-form--compact" @submit.prevent="submitQuickAddSchedule">
          <div class="classroom-schedule-form-grid">
            <label>
              <span>Course Code</span>
              <input v-model.trim="quickClassScheduleForm.courseCode" type="text" placeholder="IT325" />
            </label>
            <label>
              <span>Course Name</span>
              <input v-model.trim="quickClassScheduleForm.courseName" type="text" placeholder="Database Systems" />
            </label>
            <label>
              <span>Instructor</span>
              <input v-model.trim="quickClassScheduleForm.instructorName" type="text" placeholder="Prof. Maria Santos" />
            </label>
            <label>
              <span>Room</span>
              <select v-model.number="quickClassScheduleForm.venueIdentifier">
                <option :value="0">Select room</option>
                <option v-for="venueOption in classroomVenueOptions" :key="venueOption.venueIdentifier" :value="venueOption.venueIdentifier">
                  {{ venueOption.venueName }}
                </option>
              </select>
            </label>
            <label>
              <span>Schedule Type</span>
              <select v-model="quickClassScheduleForm.blockType">
                <option v-for="typeOption in classroomScheduleTypeOptions" :key="typeOption" :value="typeOption">{{ typeOption }}</option>
              </select>
            </label>
            <label>
              <span>Capacity</span>
              <input v-model.number="quickClassScheduleForm.capacityLimit" type="number" min="0" placeholder="40" />
            </label>
          </div>

          <label class="classroom-schedule-form-notes">
            <span>Notes</span>
            <textarea v-model.trim="quickClassScheduleForm.notes" rows="3" placeholder="Afternoon class session"></textarea>
          </label>

          <p v-if="quickClassScheduleError" class="manage-facilities-modal-error">{{ quickClassScheduleError }}</p>

          <div class="manage-facilities-modal-actions">
            <button class="manage-facilities-cancel-button" type="button" @click="closeQuickAddScheduleModal">Cancel</button>
            <button class="manage-facilities-delete-confirm-button manage-facilities-delete-confirm-button--neutral" type="submit" :disabled="isSavingQuickClassSchedule">
              {{ isSavingQuickClassSchedule ? 'Saving...' : 'Save Schedule' }}
            </button>
          </div>
        </form>
      </section>
    </div>

    <VenueModalComponent
      :show="showVenueModal"
      :venue="selectedVenue"
      @close="handleVenueModalClose"
      @saved="handleVenueModalSaved"
    />

    <div
      v-if="selectedVenueReservationEntry || venueReservationModalLoading || venueReservationModalError"
      class="manage-facilities-modal-overlay"
      @click.self="closeVenueReservationModal"
    >
      <section class="manage-facilities-delete-modal manage-facilities-venue-reservation-modal">
        <button class="manage-facilities-modal-close" type="button" aria-label="Close" @click="closeVenueReservationModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
          </svg>
        </button>

        <div class="manage-facilities-modal-heading manage-facilities-venue-reservation-modal__heading">
          <div class="manage-facilities-venue-reservation-modal__heading-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="3" />
              <path d="M16 2v4" />
              <path d="M8 2v4" />
              <path d="M3 10h18" />
            </svg>
          </div>
          <div>
            <h2>{{ venueReservationModalHeading }}</h2>
            <p>{{ venueReservationModalCountLabel }}</p>
          </div>
        </div>

        <p v-if="venueReservationModalError" class="manage-facilities-modal-error">{{ venueReservationModalError }}</p>
        <div v-else-if="venueReservationModalLoading" class="manage-facilities-venue-reservation-modal__loading">Loading reservation details...</div>
        <div v-else-if="selectedVenueReservationEntry" class="manage-facilities-venue-reservation-modal__body">
          <section class="manage-facilities-venue-reservation-modal__card">
            <div class="manage-facilities-venue-reservation-modal__status-row">
              <span
                class="manage-facilities-venue-reservation-modal__status-badge"
                :class="`manage-facilities-venue-reservation-modal__status-badge--${selectedVenueReservationEntry.statusTone}`"
              >
                {{ selectedVenueReservationEntry.statusLabel }}
              </span>
            </div>

            <div class="manage-facilities-venue-reservation-modal__overview">
              <div class="manage-facilities-venue-reservation-modal__overview-grid">
                <article class="manage-facilities-venue-reservation-modal__info-item">
                  <span>Borrower</span>
                  <strong>{{ selectedVenueReservationEntry.borrowerName }}</strong>
                </article>
                <article class="manage-facilities-venue-reservation-modal__info-item">
                  <span>Venue / Room</span>
                  <strong>{{ selectedVenueReservationEntry.venueLabel }}</strong>
                </article>
                <article class="manage-facilities-venue-reservation-modal__info-item">
                  <span>Purpose</span>
                  <strong>{{ selectedVenueReservationEntry.purposeLabel }}</strong>
                </article>
              </div>

              <article class="manage-facilities-venue-reservation-modal__remarks-card">
                <span>Remarks</span>
                <p>{{ selectedVenueReservationEntry.remarksLabel }}</p>
              </article>
            </div>

            <section class="manage-facilities-venue-reservation-modal__schedule-card">
              <div class="manage-facilities-venue-reservation-modal__section-label">Date &amp; Time</div>
              <div class="manage-facilities-venue-reservation-modal__schedule-grid">
                <article class="manage-facilities-venue-reservation-modal__info-item">
                  <span>Start Date</span>
                  <strong>{{ selectedVenueReservationEntry.startDateLabel }}</strong>
                </article>
                <article class="manage-facilities-venue-reservation-modal__info-item">
                  <span>End Date</span>
                  <strong>{{ selectedVenueReservationEntry.endDateLabel }}</strong>
                </article>
                <article class="manage-facilities-venue-reservation-modal__info-item">
                  <span>Start Time</span>
                  <strong>{{ selectedVenueReservationEntry.startTimeLabel }}</strong>
                </article>
                <article class="manage-facilities-venue-reservation-modal__info-item">
                  <span>End Time</span>
                  <strong>{{ selectedVenueReservationEntry.endTimeLabel }}</strong>
                </article>
              </div>
              <div class="manage-facilities-venue-reservation-modal__duration-pill">
                Total Duration: {{ selectedVenueReservationEntry.durationLabel }}
              </div>
            </section>

            <section class="manage-facilities-venue-reservation-modal__meta-grid">
              <article class="manage-facilities-venue-reservation-modal__info-item">
                <span>Status</span>
                <strong>{{ selectedVenueReservationEntry.statusLabel }}</strong>
              </article>
              <article class="manage-facilities-venue-reservation-modal__info-item">
                <span>Reserved On</span>
                <strong>{{ selectedVenueReservationEntry.reservedOnLabel }}</strong>
              </article>
              <article class="manage-facilities-venue-reservation-modal__info-item">
                <span>Approved By</span>
                <strong>{{ selectedVenueReservationEntry.approvedByLabel }}</strong>
              </article>
              <article class="manage-facilities-venue-reservation-modal__info-item">
                <span>Approved On</span>
                <strong>{{ selectedVenueReservationEntry.approvedOnLabel }}</strong>
              </article>
            </section>
          </section>

          <div class="manage-facilities-modal-actions">
            <button class="manage-facilities-cancel-button" type="button" @click="closeVenueReservationModal">Close</button>
          </div>
        </div>
      </section>
    </div>

    <VenueDetailsModalComponent
      :show="Boolean(viewVenueRecord || viewVenueLoading || viewVenueError)"
      :venue="viewVenueRecord"
      :error-message="viewVenueLoading ? 'Loading venue details...' : viewVenueError"
      @close="closeVenueDetails"
    />

    <VenueDeleteModalComponent
      :show="Boolean(deleteVenueRecord)"
      :venue="deleteVenueRecord"
      :current-admin-email="currentAdminEmail"
      :confirm-email="deleteConfirmEmail"
      :confirm-password="deleteConfirmPassword"
      :error-message="deleteVenueError"
      :is-deleting="isDeletingVenue"
      :is-ready="isDeleteVenueReady"
      @update:confirm-email="deleteConfirmEmail = $event"
      @update:confirm-password="deleteConfirmPassword = $event"
      @close="closeDeleteVenueModal"
      @confirm="confirmDeleteVenue"
    />

    <EquipmentModalComponent
      :show="showEquipmentModal"
      :equipment="selectedEquipment"
      @close="handleEquipmentModalClose"
      @saved="handleEquipmentModalSaved"
    />

    <EquipmentDetailsModalComponent
      :show="Boolean(viewEquipmentRecord)"
      :equipment="viewEquipmentRecord"
      :show-admin-fields="true"
      title="View Equipment Details"
      subtitle="Equipment details for admin review and editing."
      @close="closeEquipmentDetails"
    />

    <div
      v-if="deleteEquipmentRecord"
      class="manage-facilities-modal-overlay"
      @click.self="!isDeletingEquipment && closeDeleteEquipmentModal()"
    >
      <section class="manage-facilities-delete-modal manage-facilities-equipment-details-modal">
        <button
          class="manage-facilities-modal-close"
          type="button"
          aria-label="Close"
          :disabled="isDeletingEquipment"
          @click="closeDeleteEquipmentModal"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
          </svg>
        </button>

        <div class="manage-facilities-modal-heading">
          <h2>Delete Equipment</h2>
          <p>This action permanently removes the selected equipment record from TechReserve.</p>
        </div>

        <div class="manage-facilities-equipment-details-layout">
          <div class="manage-facilities-equipment-photo-card">
            <img
              :src="resolveEquipmentPhoto(deleteEquipmentRecord)"
              :alt="`${formatEquipmentText(deleteEquipmentRecord.equipmentName)} photo`"
              class="manage-facilities-equipment-photo"
              :style="resolveEquipmentPhotoStyle(deleteEquipmentRecord)"
            />
          </div>

          <dl class="manage-facilities-equipment-details-grid">
            <div><dt>Equipment Name</dt><dd>{{ formatEquipmentText(deleteEquipmentRecord.equipmentName) }}</dd></div>
            <div><dt>Equipment Type/Category</dt><dd>{{ formatEquipmentText(deleteEquipmentRecord.equipmentCategory || deleteEquipmentRecord.categoryName) }}</dd></div>
            <div><dt>Equipment Brand</dt><dd>{{ formatEquipmentText(deleteEquipmentRecord.equipmentBrand) }}</dd></div>
            <div><dt>Available Quantity</dt><dd>{{ formatEquipmentQuantity(deleteEquipmentRecord.availableQuantity) }}</dd></div>
            <div><dt>Operational Status</dt><dd>{{ formatEquipmentStatus(deleteEquipmentRecord) }}</dd></div>
            <div><dt>QR Code</dt><dd>{{ formatEquipmentText(deleteEquipmentRecord.barcode) }}</dd></div>
            <div><dt>Asset ID</dt><dd>{{ formatEquipmentText(deleteEquipmentRecord.assetId || deleteEquipmentRecord.serialNumber) }}</dd></div>
            <div class="manage-facilities-equipment-details-grid__full">
              <dt>Description</dt>
              <dd>{{ formatEquipmentText(deleteEquipmentRecord.description || deleteEquipmentRecord.scheduleDescription) }}</dd>
            </div>
          </dl>
        </div>

        <label class="manage-facilities-confirm-field">
          <span>Type your admin email to confirm deletion:</span>
          <input
            v-model.trim="deleteEquipmentConfirmEmail"
            type="email"
            :placeholder="currentAdminEmail || 'admin@techreserve.edu.ph'"
            autocomplete="off"
          />
        </label>

        <label class="manage-facilities-confirm-field">
          <span>Type your admin password to confirm deletion:</span>
          <input
            v-model="deleteEquipmentConfirmPassword"
            type="password"
            placeholder="Admin password"
            autocomplete="current-password"
          />
        </label>

        <p v-if="deleteEquipmentError" class="manage-facilities-modal-error">{{ deleteEquipmentError }}</p>

        <div class="manage-facilities-modal-actions">
          <button
            class="manage-facilities-cancel-button"
            type="button"
            :disabled="isDeletingEquipment"
            @click="closeDeleteEquipmentModal"
          >
            Cancel
          </button>
          <button
            class="manage-facilities-delete-confirm-button"
            type="button"
            :disabled="isDeletingEquipment || !isDeleteEquipmentReady"
            @click="confirmDeleteEquipment"
          >
            {{ isDeletingEquipment ? 'Deleting...' : 'Delete Equipment' }}
          </button>
        </div>
      </section>
    </div>

    <DataRequestStatusFloater :items="facilityStatusItems" />
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import DataRequestStatusFloater from '@/shared/components/DataRequestStatusFloater.vue';
import '@/shared/components/adminSidebarLayout.css';
import '@/pages/borrower/css/Logs.css';
import './css/ManageFacilitiesPage.css';
import './css/ManageFacilitiesVenueBoard.css';
import './css/ManageFacilitiesVenueCalendar.css';
import './css/ManageFacilitiesGrids.css';
import './css/ManageFacilitiesClassSchedule.css';
import './css/ManageFacilitiesScheduleImport.css';
import './css/ManageFacilitiesModals.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import FacilityVenueListComponent from '@/modules/facility/components/FacilityVenueListComponent.vue';
import FacilityEquipmentGridComponent from '@/modules/facility/components/FacilityEquipmentGridComponent.vue';
import VenueAvailabilityCalendarComponent from '@/modules/facility/components/VenueAvailabilityCalendarComponent.vue';
import VenueDeleteModalComponent from '@/modules/facility/components/VenueDeleteModalComponent.vue';
import VenueDetailsModalComponent from '@/modules/facility/components/VenueDetailsModalComponent.vue';
import VenueModalComponent from '@/modules/facility/components/VenueModalComponent.vue';
import EquipmentDetailsModalComponent from '@/modules/facility/components/EquipmentDetailsModalComponent.vue';
import EquipmentModalComponent from '@/modules/facility/components/EquipmentModalComponent.vue';
import reservationApi from '@/modules/reservation/services/reservationApi.js';
import venueApi from '@/modules/reservation/services/venueApi.js';
import equipmentApi from '@/modules/reservation/services/equipmentApi.js';
import classScheduleApi from '@/modules/reservation/services/classScheduleApi.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import {
  deriveVenueAvailabilityForDate,
  isVenueFloorPlaceholderRecord,
  resolveVenuePhoto,
} from '@/modules/facility/utils/venueFormValidation.js';
import { sortVenueFloorLabels, VENUE_FLOOR_OPTIONS } from '@/modules/facility/utils/venueFloorOptions.js';
import {
  formatEquipmentQuantity,
  formatEquipmentStatus,
  formatEquipmentText,
  resolveEquipmentPhoto,
  resolveEquipmentPhotoStyle,
} from '@/modules/facility/utils/equipmentPresentation.js';
import {
  buildInitialImportHeaderSelections,
  countRecurringImportMatches,
  createEmptyImportDetectionMetadata,
  downloadClassScheduleImportTemplate,
  importScheduleTargetOptions,
  normalizeImportAliasKey,
  normalizeImportedCapacity,
  normalizeImportedDate,
  normalizeImportedDayList,
  normalizeImportedText,
  normalizeImportedTime,
  parseImportedScheduleFile,
} from './services/classScheduleImportUtils.js';

const MANAGE_FACILITIES_EQUIPMENT_CACHE_KEY = 'techreserve_manage_facilities_equipment_cache';
const MANAGE_FACILITIES_VENUE_CACHE_KEY = 'techreserve_manage_facilities_venue_cache';

const authStore = useAuthenticationStore();
const route = useRoute();
const router = useRouter();
const activeFacilityTab = ref('venue');
const availabilityFilter = ref('all');
const showingFilterValue = ref('all');
const sortValue = ref('asc');
const searchQuery = ref('');

const showVenueModal = ref(false);
const showEquipmentModal = ref(false);
const selectedVenue = ref(null);
const selectedVenueCard = ref(null);
const selectedEquipment = ref(null);
const selectedEquipmentCard = ref(null);
const viewEquipmentRecord = ref(null);
const viewVenueRecord = ref(null);
const viewVenueLoading = ref(false);
const viewVenueError = ref('');

const venuesList = ref(readSessionCache(MANAGE_FACILITIES_VENUE_CACHE_KEY));
const reservedVenueMap = ref({});
const equipmentList = ref(readSessionCache(MANAGE_FACILITIES_EQUIPMENT_CACHE_KEY));
const loading = ref(false);
const venueError = ref('');
const equipmentLoading = ref(false);
const equipmentError = ref('');
const venueDataState = ref(venuesList.value.length > 0 ? 'cached' : 'idle');
const equipmentDataState = ref(equipmentList.value.length > 0 ? 'cached' : 'idle');
const classScheduleDataState = ref('idle');
const equipmentViewMode = ref('card');
const venueBuildingFilter = ref('all');
const venueCapacityFilter = ref('all');
const venueListCurrentPage = ref(1);
const venueListPageSize = ref(4);
const equipmentCurrentPage = ref(1);
const equipmentPageSize = ref(12);
const selectedVenueCalendarDate = ref(getTodayDateInputValue());
const venueMonthCursor = ref(selectedVenueCalendarDate.value.slice(0, 7));
const venueCalendarViewMode = ref('weekly');
const deleteEquipmentRecord = ref(null);
const deleteEquipmentConfirmEmail = ref('');
const deleteEquipmentConfirmPassword = ref('');
const deleteEquipmentError = ref('');
const isDeletingEquipment = ref(false);
const deleteVenueRecord = ref(null);
const deleteConfirmEmail = ref('');
const deleteConfirmPassword = ref('');
const deleteVenueError = ref('');
const isDeletingVenue = ref(false);
const classScheduleList = ref([]);
const classScheduleLoading = ref(false);
const classScheduleError = ref('');
const selectedClassroomDate = ref(getTodayDateInputValue());
const classroomMonthCursor = ref(selectedClassroomDate.value.slice(0, 7));
const facilityStatusItems = computed(() => [
  {
    key: 'venues',
    label: 'Venues',
    state: venueDataState.value,
  },
  {
    key: 'equipment',
    label: 'Equipment',
    state: equipmentDataState.value,
  },
  {
    key: 'class-schedules',
    label: 'Class Schedules',
    state: classScheduleDataState.value,
  },
]);
const classroomBoardView = ref('Weekly View');
const showClassScheduleActionMenu = ref(false);
const showClassScheduleModal = ref(false);
const showImportSchedulesModal = ref(false);
const showQuickClassScheduleModal = ref(false);
const classScheduleFormMode = ref('create');
const classScheduleModalError = ref('');
const isSavingClassSchedule = ref(false);
const quickClassScheduleError = ref('');
const isSavingQuickClassSchedule = ref(false);
const selectedClassScheduleRecord = ref(null);
const viewClassScheduleRecord = ref(null);
const isDeletingClassSchedule = ref(false);
const importScheduleFile = ref(null);
const importScheduleStep = ref('upload');
const importScheduleError = ref('');
const importScheduleParsing = ref(false);
const importScheduleHeaders = ref([]);
const importScheduleRows = ref([]);
const importScheduleHeaderSelections = ref({});
const importScheduleAutoMappedHeaders = ref({});
const importScheduleDetectionMetadata = ref(createEmptyImportDetectionMetadata());
const importScheduleReviewRows = ref([]);
const importScheduleImporting = ref(false);
const importScheduleResult = ref(null);
const classScheduleForm = ref(createEmptyClassScheduleForm());
const quickClassScheduleForm = ref(createEmptyQuickAddScheduleForm());
const selectedVenueReservationEntry = ref(null);
const venueReservationModalLoading = ref(false);
const venueReservationModalError = ref('');
let activeReservedVenueWeekRequestSequence = 0;

const currentAdminEmail = computed(() =>
  authStore.accountData?.emailAddress || authStore.clerkAccountData?.emailAddress || ''
);
const currentAdminDisplayName = computed(() => {
  const account = authStore.accountData || authStore.clerkAccountData || {};
  const firstName = String(account.firstName || '').trim();
  const lastName = String(account.lastName || '').trim();
  const combinedName = `${firstName} ${lastName}`.trim();

  return combinedName || 'TechReserve Admin';
});

const isDeleteVenueReady = computed(() =>
  Boolean(deleteVenueRecord.value)
  && normalizeEmailForConfirmation(deleteConfirmEmail.value) === normalizeEmailForConfirmation(currentAdminEmail.value)
  && deleteConfirmPassword.value.trim() !== ''
);

const isDeleteEquipmentReady = computed(() =>
  Boolean(deleteEquipmentRecord.value)
  && deleteEquipmentConfirmEmail.value.trim() !== ''
  && deleteEquipmentConfirmPassword.value.trim() !== ''
);

const floorOrder = VENUE_FLOOR_OPTIONS;
const classroomDayOptions = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
const classroomScheduleTypeOptions = ['Class Schedule', 'Reserved', 'Equipment Reservation', 'Pending', 'Maintenance'];
const academicYearOptions = ['2025 - 2026', '2026 - 2027', '2027 - 2028', '2028 - 2029'];
const semesterOptions = ['1st Semester', '2nd Semester', 'Summer'];
const importScheduleSteps = [
  { key: 'upload', label: '1 Upload File' },
  { key: 'map', label: '2 Map Columns' },
  { key: 'review', label: '3 Review' },
  { key: 'import', label: '4 Import' },
];
const miniCalendarWeekdays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
const compactWeekdayLabels = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
const classroomTimeLabels = ['7 AM', '8 AM', '9 AM', '10 AM', '11 AM', '12 PM', '1 PM', '2 PM', '3 PM', '4 PM', '5 PM', '6 PM'];
const classroomQuickSlots = Array.from({ length: 12 }, (_, index) => {
  const startHour = 7 + index;
  const endHour = Math.min(startHour + 2, 19);

  return {
    index,
    label: formatHourLabel(startHour),
    startTime: `${String(startHour).padStart(2, '0')}:00`,
    endTime: `${String(endHour).padStart(2, '0')}:00`,
  };
});
const todayDateKey = getTodayDateInputValue();

const searchedAndSortedVenues = computed(() => filterAndSortVenues(
  venuesList.value,
  searchQuery.value,
  sortValue.value,
));

const calendarVenueRecords = computed(() =>
  searchedAndSortedVenues.value.filter((venueRecord) => matchesCalendarAvailability(
    venueRecord,
    availabilityFilter.value,
    selectedVenueCalendarDate.value,
  ))
);

const filteredEquipmentRecords = computed(() => filterAndSortEquipment(
  filteredEquipmentBaseRecords.value,
  searchQuery.value,
  availabilityFilter.value,
  sortValue.value,
));

const equipmentTotalPages = computed(() => Math.max(1, Math.ceil(filteredEquipmentRecords.value.length / equipmentPageSize.value)));
const paginatedEquipmentRecords = computed(() => {
  const startIndex = (equipmentCurrentPage.value - 1) * equipmentPageSize.value;
  return filteredEquipmentRecords.value.slice(startIndex, startIndex + equipmentPageSize.value);
});
const equipmentDisplayStart = computed(() => (
  filteredEquipmentRecords.value.length === 0
    ? 0
    : ((equipmentCurrentPage.value - 1) * equipmentPageSize.value) + 1
));
const equipmentDisplayEnd = computed(() => Math.min(
  equipmentCurrentPage.value * equipmentPageSize.value,
  filteredEquipmentRecords.value.length,
));

const venueFloorGroups = computed(() => buildVenueFloorGroups(
  filteredVenueRecords.value,
  floorOrder,
  selectedVenueCalendarDate.value,
));
const paginatedVenueFloorGroups = computed(() => buildVenueFloorGroups(
  paginatedVenueDirectoryRecords.value,
  floorOrder,
  selectedVenueCalendarDate.value,
));
const venueWeekDates = computed(() => buildWeekDates(selectedVenueCalendarDate.value));
const selectedVenueMonthLabel = computed(() => new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(new Date(`${venueMonthCursor.value}-01T00:00:00`)));
const selectedVenueLongDateLabel = computed(() => formatDisplayDateHeading(selectedVenueCalendarDate.value));
const selectedVenueWeekdayLabel = computed(() => new Intl.DateTimeFormat('en-US', { weekday: 'long' }).format(new Date(`${selectedVenueCalendarDate.value}T00:00:00`)));
const selectedVenueMonthShortLabel = computed(() => new Intl.DateTimeFormat('en-US', { month: 'short' }).format(new Date(`${selectedVenueCalendarDate.value}T00:00:00`)).toUpperCase());
const selectedVenueDayNumberLabel = computed(() => new Date(`${selectedVenueCalendarDate.value}T00:00:00`).getDate());
const venueMonthCalendarCells = computed(() => buildSimpleCalendarDays(venueMonthCursor.value));
const venueReservationModalHeading = computed(() => {
  const eventDateTime = selectedVenueReservationEntry.value?.eventDateTime || selectedVenueReservationEntry.value?.dateValue || selectedVenueCalendarDate.value;
  return `Reservations for ${formatDisplayDateHeadingWithWeekday(resolveDateValue(eventDateTime))}`;
});
const venueReservationModalCountLabel = computed(() => '1 reservation(s) found');
const selectedVenueSummary = computed(() => {
  const reservedEntries = getVenueEntriesForDate(selectedVenueCalendarDate.value);
  const visibleCount = reservedEntries.length;
  const availableCount = filteredVenueRecords.value.filter((venueRecord) => deriveVenueAvailabilityForDate(venueRecord, selectedVenueCalendarDate.value) === 'Available').length;

  return {
    visibleCount,
    availableCount,
    blockedCount: reservedEntries.length,
  };
});
const venueCalendarColumns = computed(() => venueWeekDates.value.map((weekDate) => {
  const entries = getVenueEntriesForDate(weekDate.dateKey);

  return {
    dateValue: weekDate.dateKey,
    weekdayLabel: weekDate.weekday,
    shortDateLabel: weekDate.monthDay,
    entryCountLabel: `${entries.length} reserved`,
    entries,
  };
}));
const selectedVenueReservedEntries = computed(() => getVenueEntriesForDate(selectedVenueCalendarDate.value));

const filteredVenueRecords = computed(() => {
  const selectedFloor = showingFilterValue.value;
  if (selectedFloor === 'all') {
    return searchedAndSortedVenues.value;
  }

  return searchedAndSortedVenues.value.filter((venueRecord) => venueRecord.floorLevel === selectedFloor);
});

const venueCapacityOptions = computed(() => {
  const capacityValues = filteredVenueRecords.value
    .map((venueRecord) => Number(venueRecord.capacityLimit ?? 0))
    .filter((capacityValue) => Number.isFinite(capacityValue) && capacityValue > 0);

  if (capacityValues.length === 0) {
    return [{ value: 'all', label: 'Any Capacity' }];
  }

  const maxCapacity = Math.max(...capacityValues);
  const options = [{ value: 'all', label: 'Any Capacity' }];

  if (capacityValues.some((capacityValue) => capacityValue <= 50)) {
    options.push({ value: '1-50', label: '1 - 50' });
  }
  if (capacityValues.some((capacityValue) => capacityValue >= 51 && capacityValue <= 100)) {
    options.push({ value: '51-100', label: '51 - 100' });
  }
  if (capacityValues.some((capacityValue) => capacityValue >= 101 && capacityValue <= 200)) {
    options.push({ value: '101-200', label: '101 - 200' });
  }
  if (maxCapacity > 200) {
    options.push({ value: '201+', label: '201+' });
  }

  return options;
});

const venueDirectoryRecords = computed(() => filteredVenueRecords.value.filter((venueRecord) => matchesVenueCapacityFilter(
  venueRecord,
  venueCapacityFilter.value,
)));

const venueDashboardSummary = computed(() => {
  const selectedDate = selectedVenueCalendarDate.value;
  const availableCount = venueDirectoryRecords.value.filter((venueRecord) => resolveVenueMatrixTone(venueRecord, selectedDate) === 'available').length;
  const reservedCount = venueDirectoryRecords.value.filter((venueRecord) => resolveVenueMatrixTone(venueRecord, selectedDate) === 'reserved').length;
  const maintenanceCount = venueDirectoryRecords.value.filter((venueRecord) => resolveVenueMatrixTone(venueRecord, selectedDate) === 'maintenance').length;

  return {
    availableCount,
    reservedCount,
    maintenanceCount,
    peakTimeLabel: resolveVenuePeakTimeLabel(selectedDate),
  };
});

const venueMatrixRows = computed(() => venueDirectoryRecords.value.map((venueRecord) => ({
  sourceRecord: venueRecord,
  venueIdentifier: venueRecord.venueIdentifier,
  venueName: venueRecord.venueName,
  floorLabel: venueRecord.floorLevel || 'Other Floor',
  capacityLabel: venueRecord.capacityLimit || 'N/A',
})));
const venueScheduleRailLabels = ['8 AM', '10 AM', '12 PM', '2 PM', '4 PM'];
const venueWeekScheduleColumns = computed(() => venueWeekDates.value.map((weekDate) => buildVenueWeekScheduleColumn(weekDate)));

const venueListTotalPages = computed(() => Math.max(1, Math.ceil(venueDirectoryRecords.value.length / venueListPageSize.value)));
const paginatedVenueDirectoryRecords = computed(() => {
  const startIndex = (venueListCurrentPage.value - 1) * venueListPageSize.value;
  return venueDirectoryRecords.value.slice(startIndex, startIndex + venueListPageSize.value);
});
const paginatedVenueListRecords = computed(() => paginatedVenueDirectoryRecords.value.map((venueRecord) => buildVenueDirectoryCardRecord(venueRecord)));
const venueListDisplayStart = computed(() => (
  venueDirectoryRecords.value.length === 0
    ? 0
    : ((venueListCurrentPage.value - 1) * venueListPageSize.value) + 1
));
const venueListDisplayEnd = computed(() => Math.min(
  venueListCurrentPage.value * venueListPageSize.value,
  venueDirectoryRecords.value.length,
));

const visibleVenueRecords = computed(() => filteredVenueRecords.value.filter((venueRecord) => matchesCalendarAvailability(
  venueRecord,
  availabilityFilter.value,
  selectedVenueCalendarDate.value,
)));

const filteredEquipmentBaseRecords = computed(() => {
  const selectedCategory = showingFilterValue.value;
  if (selectedCategory === 'all') {
    return equipmentList.value;
  }

  return equipmentList.value.filter((equipmentRecord) => resolveEquipmentCategory(equipmentRecord) === selectedCategory);
});

const showingFilterOptions = computed(() => {
  if (activeFacilityTab.value === 'venue' || activeFacilityTab.value === 'all') {
    const floorOptions = sortVenueFloorLabels(
      searchedAndSortedVenues.value
        .map((venueRecord) => venueRecord.floorLevel)
        .filter(Boolean)
    );

    return [
      { value: 'all', label: 'All floors' },
      ...floorOptions
        .map((floorLabel) => ({ value: floorLabel, label: floorLabel })),
    ];
  }

  if (activeFacilityTab.value === 'classroom-schedules') {
    return [
      { value: 'all', label: 'All rooms' },
      ...classroomVenueOptions.value.map((venueOption) => ({
        value: String(venueOption.venueIdentifier),
        label: venueOption.venueName,
      })),
    ];
  }

  const categoryOptions = Array.from(new Set(
    equipmentList.value
      .map((equipmentRecord) => resolveEquipmentCategory(equipmentRecord))
      .filter((value) => value !== 'Uncategorized')
  )).sort((left, right) => left.localeCompare(right));

  return [
    { value: 'all', label: 'All categories' },
    ...categoryOptions.map((categoryLabel) => ({ value: categoryLabel, label: categoryLabel })),
  ];
});

const totalManagedCount = computed(() => (
  activeFacilityTab.value === 'venue' || activeFacilityTab.value === 'all'
    ? visibleVenueRecords.value.length
    : activeFacilityTab.value === 'classroom-schedules'
      ? filteredClassScheduleRecords.value.length
      : filteredEquipmentRecords.value.length
));

const availableManagedCount = computed(() => (
  activeFacilityTab.value === 'venue' || activeFacilityTab.value === 'all'
    ? visibleVenueRecords.value.filter((venueRecord) => matchesCalendarAvailability(
      venueRecord,
      'available',
      selectedVenueCalendarDate.value,
    )).length
    : activeFacilityTab.value === 'classroom-schedules'
      ? filteredClassScheduleRecords.value.length
      : filteredEquipmentRecords.value.filter((equipmentRecord) => formatEquipmentStatus(equipmentRecord) === 'Available').length
));

const unavailableManagedCount = computed(() => Math.max(totalManagedCount.value - availableManagedCount.value, 0));
const dispatchSummaryRecords = computed(() => [...equipmentList.value]
  .filter((equipmentRecord) => Number(equipmentRecord.dispatchedTodayQuantity ?? 0) > 0)
  .sort((left, right) => Number(right.dispatchedTodayQuantity ?? 0) - Number(left.dispatchedTodayQuantity ?? 0)));
const dispatchedEquipmentCount = computed(() => dispatchSummaryRecords.value.length);
const dispatchedEquipmentUnits = computed(() => dispatchSummaryRecords.value
  .reduce((totalUnits, equipmentRecord) => totalUnits + Number(equipmentRecord.dispatchedTodayQuantity ?? 0), 0));

const currentGroupingCount = computed(() => (
  activeFacilityTab.value === 'venue' || activeFacilityTab.value === 'all'
    ? venueFloorGroups.value.filter((floorGroup) => (floorGroup.venueRecords || []).some((venueRecord) => matchesCalendarAvailability(
      venueRecord,
      availabilityFilter.value,
      selectedVenueCalendarDate.value,
    ))).length
    : activeFacilityTab.value === 'classroom-schedules'
      ? new Set(filteredClassScheduleRecords.value.map((scheduleRecord) => scheduleRecord.venueIdentifier)).size
      : new Set(filteredEquipmentRecords.value.map((equipmentRecord) => resolveEquipmentCategory(equipmentRecord))).size
));

const selectedRecordLabel = computed(() => (
  activeFacilityTab.value === 'venue'
    ? selectedVenueCard.value?.venueName || ''
    : selectedEquipmentCard.value?.equipmentName || ''
));

const resultsSummaryCopy = computed(() => {
  if (activeFacilityTab.value === 'venue' || activeFacilityTab.value === 'all') {
    return `${totalManagedCount.value} venue${totalManagedCount.value === 1 ? '' : 's'} visible for ${formatSummaryDate(selectedVenueCalendarDate.value)}.`;
  }

  if (activeFacilityTab.value === 'classroom-schedules') {
    return `${totalManagedCount.value} classroom schedule block${totalManagedCount.value === 1 ? '' : 's'} in the current view.`;
  }

  return `${totalManagedCount.value} equipment record${totalManagedCount.value === 1 ? '' : 's'} match the current filters.`;
});

const addButtonLabel = computed(() => {
  if (activeFacilityTab.value === 'classroom-schedules') {
    return 'Add Schedule';
  }

  if (activeFacilityTab.value === 'equipment') {
    return 'Add Equipment';
  }

  return 'Add Venue';
});

const searchPlaceholder = computed(() => {
  if (activeFacilityTab.value === 'classroom-schedules') {
    return 'Search by course code, course name, instructor, or room...';
  }

  if (activeFacilityTab.value === 'equipment') {
    return 'Search by equipment name, type, brand, barcode, or asset ID...';
  }

  return 'Search by venue name, location, or floor...';
});

const showingLabel = computed(() => (
  activeFacilityTab.value === 'classroom-schedules' ? 'Room:' : 'Showing:'
));

const classroomVenueOptions = computed(() => searchedAndSortedVenues.value
  .filter((venueRecord) => !isVenueFloorPlaceholderRecord(venueRecord))
  .map((venueRecord) => ({
    venueIdentifier: venueRecord.venueIdentifier,
    venueName: venueRecord.venueName,
  }))
);

const filteredClassScheduleRecords = computed(() => filterAndSortClassSchedules(
  classScheduleList.value,
  searchQuery.value,
  showingFilterValue.value,
  sortValue.value,
  venuesList.value,
));

const classroomWeekDates = computed(() => buildWeekDates(selectedClassroomDate.value));
const classroomMonthHeading = computed(() => new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(new Date(`${classroomMonthCursor.value}-01T00:00:00`)));
const selectedClassroomDateHeading = computed(() => formatDisplayDateHeading(selectedClassroomDate.value));
const selectedClassroomDateWeekday = computed(() => new Intl.DateTimeFormat('en-US', { weekday: 'long' }).format(new Date(`${selectedClassroomDate.value}T00:00:00`)));
const selectedClassroomDateMonthShort = computed(() => new Intl.DateTimeFormat('en-US', { month: 'short' }).format(new Date(`${selectedClassroomDate.value}T00:00:00`)).toUpperCase());
const selectedClassroomDateDayNumber = computed(() => new Date(`${selectedClassroomDate.value}T00:00:00`).getDate());
const classroomMonthDays = computed(() => buildMiniCalendarDays(classroomMonthCursor.value, classScheduleList.value));
const activeClassScheduleDuration = computed(() => formatScheduleDuration(classScheduleForm.value.startTime, classScheduleForm.value.endTime));
const quickAddScheduleSummary = computed(() => {
  const selectedVenue = classroomVenueOptions.value.find((venueRecord) => Number(venueRecord.venueIdentifier) === Number(quickClassScheduleForm.value.venueIdentifier));
  const venueName = selectedVenue?.venueName || 'Select room';

  return `${formatDisplayDateHeading(quickClassScheduleForm.value.dateRangeStart)} | ${formatClassroomScheduleTime(quickClassScheduleForm.value.startTime, quickClassScheduleForm.value.endTime)} | ${venueName}`;
});
const importScheduleStepIndex = computed(() => importScheduleSteps.findIndex((step) => step.key === importScheduleStep.value));
const importScheduleParsedRowCount = computed(() => importScheduleRows.value.length);
const importScheduleMinimumRequiredMappingsCount = computed(() => 4);
const importScheduleMappedHeaderCount = computed(() => (
  Object.values(importScheduleHeaderSelections.value).filter(Boolean).length
));
const importScheduleTargetToHeaderMap = computed(() => Object.entries(importScheduleHeaderSelections.value).reduce((targetMap, [header, targetKey]) => {
  if (targetKey) {
    targetMap[targetKey] = header;
  }

  return targetMap;
}, {}));
const importScheduleDetectedSheetLabel = computed(() => importScheduleDetectionMetadata.value.sheetName || 'Not detected');
const importScheduleDetectedHeaderRowLabel = computed(() => (
  importScheduleDetectionMetadata.value.headerRowNumber > 0
    ? `Row ${importScheduleDetectionMetadata.value.headerRowNumber}`
    : 'Not detected'
));
const importScheduleHasWeakDetection = computed(() => (
  importScheduleDetectionMetadata.value.headerKeywordScore <= 1
  || importScheduleHeaders.value.length < 4
));
const importScheduleDetectionNotice = computed(() => {
  if (!importScheduleFile.value) {
    return '';
  }

  if (importScheduleHasWeakDetection.value) {
    return 'Detection confidence is low. Review the mapped columns carefully or retry with the official import template.';
  }

  return `Detected "${importScheduleDetectedSheetLabel.value}" with header ${importScheduleDetectedHeaderRowLabel.value}.`;
});
const importScheduleMissingMappings = computed(() => {
  const mappedTargets = new Set(Object.values(importScheduleHeaderSelections.value).filter(Boolean));
  const missingTargets = [];

  if (!mappedTargets.has('venueReference')) {
    missingTargets.push('Room / Venue');
  }
  if (!mappedTargets.has('startTime')) {
    missingTargets.push('Start Time');
  }
  if (!mappedTargets.has('endTime')) {
    missingTargets.push('End Time');
  }

  const hasSingleDate = mappedTargets.has('blockDate');
  const hasRecurringSet = mappedTargets.has('dateRangeStart') && mappedTargets.has('dateRangeEnd') && mappedTargets.has('daysOfWeek');
  if (!hasSingleDate && !hasRecurringSet) {
    missingTargets.push('Single Date or Recurring Start Date + Recurring End Date + Days of Week');
  }

  return missingTargets;
});
const importScheduleRemainingRequiredMappingsCount = computed(() => importScheduleMissingMappings.value.length);
const importScheduleMissingMappingsNotice = computed(() => {
  const missingTargets = importScheduleMissingMappings.value;
  if (missingTargets.length === 0) {
    return '';
  }

  if (missingTargets.length === 1) {
    return `1 more required mapping is needed to proceed: ${missingTargets[0]}`;
  }

  return `${missingTargets.length} required mappings are still needed: ${missingTargets.join(', ')}`;
});
const importSchedulePreviewRow = computed(() => importScheduleRows.value[0] || []);
const importScheduleValidReviewRows = computed(() => importScheduleReviewRows.value.filter((row) => row.isValid));
const importScheduleInvalidReviewRows = computed(() => importScheduleReviewRows.value.filter((row) => !row.isValid));
const importScheduleReadyReviewRows = computed(() => importScheduleReviewRows.value.filter((row) => row.importStatus === 'ready'));
const importScheduleDisplayReviewRows = computed(() => [
  ...importScheduleInvalidReviewRows.value,
  ...importScheduleReadyReviewRows.value,
  ...importScheduleReviewRows.value.filter((row) => row.importStatus === 'imported' || row.importStatus === 'failed'),
]);
const importScheduleHasResults = computed(() => Boolean(importScheduleResult.value));
const importScheduleCanAdvanceFromUpload = computed(() => (
  Boolean(importScheduleFile.value)
  && !importScheduleParsing.value
  && importScheduleHeaders.value.length > 0
  && importScheduleRows.value.length > 0
  && !importScheduleError.value
));
const importScheduleCanAdvanceFromMap = computed(() => (
  importScheduleHeaders.value.length > 0
  && importScheduleRows.value.length > 0
  && importScheduleMissingMappings.value.length === 0
));
const importScheduleCanImport = computed(() => (
  importScheduleValidReviewRows.value.length > 0
  && !importScheduleImporting.value
));

function handleFacilityTabChange(tabName) {
  if (activeFacilityTab.value === tabName) {
    return;
  }

  if (activeFacilityTab.value === 'venue' || activeFacilityTab.value === 'all') {
    handleVenueModalClose();
    closeVenueReservationModal();
    closeVenueDetails();
    closeDeleteVenueModal();
  }

  if (activeFacilityTab.value === 'equipment') {
    handleEquipmentModalClose();
    closeEquipmentDetails();
    closeDeleteEquipmentModal();
  }

  activeFacilityTab.value = tabName;
  availabilityFilter.value = 'all';
  searchQuery.value = '';
  showingFilterValue.value = 'all';
  updateFacilityTabQuery(tabName);

  if (tabName === 'classroom-schedules') {
    fetchClassSchedules();
  }
}

function handleEditFacility() {
  if (activeFacilityTab.value === 'venue') {
    if (!selectedVenueCard.value) {
      venueError.value = 'Use a venue card action to choose which venue you want to edit.';
      return;
    }

    handleEditVenue(selectedVenueCard.value);
    return;
  }

  if (!selectedEquipmentCard.value) {
    equipmentError.value = 'Select an equipment record first before editing.';
    return;
  }

  handleEditEquipment(selectedEquipmentCard.value);
}

function handleAddFacility() {
  if (activeFacilityTab.value === 'classroom-schedules') {
    openAddScheduleForDate(selectedClassroomDate.value);
    return;
  }

  if (activeFacilityTab.value === 'venue' || activeFacilityTab.value === 'all') {
    venueError.value = '';
    selectedVenue.value = null;
    showVenueModal.value = true;
    return;
  }

  selectedEquipment.value = null;
  equipmentError.value = '';
  showEquipmentModal.value = true;
}

function handleEditVenue(venueRecord) {
  selectedVenueCard.value = venueRecord;
  venueError.value = '';
  selectedVenue.value = venueRecord;
  showVenueModal.value = true;
}

async function handleViewVenue(venueRecord) {
  if (!venueRecord?.venueIdentifier) {
    return;
  }

  selectedVenueCard.value = venueRecord;
  viewVenueLoading.value = true;
  viewVenueError.value = '';
  viewVenueRecord.value = null;

  try {
    const response = await venueApi.getVenueById(venueRecord.venueIdentifier);
    viewVenueRecord.value = normalizeVenueRecord(response?.data || response);
  } catch (error) {
    viewVenueError.value = error?.response?.data?.errorMessage || 'Failed to load venue details.';
  } finally {
    viewVenueLoading.value = false;
  }
}

async function handleVenueBlockClick(block, venueRow) {
  if (block?.tone === 'reserved') {
    await openVenueReservationModal(block, venueRow);
    return;
  }

  await handleViewVenue(venueRow);
}

async function openVenueReservationModal(block, venueRow) {
  const reservationIdentifier = Number(block?.reservationIdentifier || block?.rawRangeRecord?.reservationIdentifier || 0);
  selectedVenueReservationEntry.value = normalizeVenueReservationEntry(null, block, venueRow);
  venueReservationModalLoading.value = reservationIdentifier > 0;
  venueReservationModalError.value = '';

  if (reservationIdentifier <= 0) {
    venueReservationModalLoading.value = false;
    return;
  }

  try {
    const response = await reservationApi.getReservationById(reservationIdentifier);
    selectedVenueReservationEntry.value = normalizeVenueReservationEntry(response?.data || response, block, venueRow);
  } catch (error) {
    venueReservationModalError.value = error?.response?.data?.errorMessage || 'Failed to load reservation details.';
  } finally {
    venueReservationModalLoading.value = false;
  }
}

function closeVenueReservationModal() {
  selectedVenueReservationEntry.value = null;
  venueReservationModalLoading.value = false;
  venueReservationModalError.value = '';
}

function closeVenueDetails() {
  viewVenueRecord.value = null;
  viewVenueError.value = '';
  viewVenueLoading.value = false;
}

function handleVenueModalClose() {
  showVenueModal.value = false;
  selectedVenue.value = null;
}

function handleVenueModalSaved() {
  showVenueModal.value = false;
  selectedVenue.value = null;
  fetchVenues();
}

function handleEquipmentModalClose() {
  showEquipmentModal.value = false;
  selectedEquipment.value = null;
}

function handleEquipmentModalSaved() {
  showEquipmentModal.value = false;
  selectedEquipment.value = null;
  fetchEquipment();
}

async function fetchVenues() {
  try {
    loading.value = true;
    venueError.value = '';
    venueDataState.value = venuesList.value.length > 0 ? 'cached-loading' : 'loading';
    const response = await listVenuesWithFallback({
      selectedDate: selectedVenueCalendarDate.value,
    });
    const venuePayload = response?.data?.venues || response?.venues || [];
    venuesList.value = Array.isArray(venuePayload)
      ? venuePayload.map(normalizeVenueRecord).filter(Boolean).filter((venueRecord) => !isVenueFloorPlaceholderRecord(venueRecord))
      : [];
    writeSessionCache(MANAGE_FACILITIES_VENUE_CACHE_KEY, venuesList.value);
    venueDataState.value = 'fresh';
  } catch (error) {
    venueDataState.value = venuesList.value.length > 0 ? 'cached' : 'error';
    venueError.value = error?.response?.data?.errorMessage || 'Failed to load venue records.';
  } finally {
    loading.value = false;
  }
}

async function listVenuesWithFallback(options = {}) {
  try {
    return await venueApi.listVenues(options);
  } catch (error) {
    if (!options.selectedDate) {
      throw error;
    }

    return venueApi.listVenues();
  }
}

async function fetchEquipment() {
  try {
    equipmentLoading.value = true;
    equipmentError.value = '';
    equipmentDataState.value = equipmentList.value.length > 0 ? 'cached-loading' : 'loading';
    const response = await equipmentApi.listEquipment();
    equipmentList.value = response?.data?.equipment || [];
    writeSessionCache(MANAGE_FACILITIES_EQUIPMENT_CACHE_KEY, equipmentList.value);
    equipmentDataState.value = 'fresh';
  } catch (error) {
    equipmentDataState.value = equipmentList.value.length > 0 ? 'cached' : 'error';
    equipmentError.value = error?.response?.data?.errorMessage || 'Failed to load equipment.';
  } finally {
    equipmentLoading.value = false;
  }
}

async function fetchReservedVenuesForVisibleWeek() {
  const currentRequestSequence = activeReservedVenueWeekRequestSequence + 1;
  activeReservedVenueWeekRequestSequence = currentRequestSequence;

  try {
    const weekDateValues = buildWeekDates(selectedVenueCalendarDate.value).map((weekDate) => weekDate.dateKey);
    const responses = await Promise.all(weekDateValues.map(async (dateValue) => {
      const response = await venueApi.listVenues({
        selectedDate: dateValue,
        includeUnavailable: true,
      });
      const venuePayload = response?.data?.venues || response?.venues || [];
      const normalizedVenues = Array.isArray(venuePayload)
        ? venuePayload
          .map(normalizeVenueRecord)
          .filter(Boolean)
          .filter((venueRecord) => !isVenueFloorPlaceholderRecord(venueRecord))
          .filter((venueRecord) => hasReservedVenueEntry(venueRecord, dateValue))
        : [];

      return [dateValue, normalizedVenues];
    }));

    if (currentRequestSequence !== activeReservedVenueWeekRequestSequence) {
      return;
    }

    reservedVenueMap.value = Object.fromEntries(responses);
  } catch (error) {
    if (currentRequestSequence !== activeReservedVenueWeekRequestSequence) {
      return;
    }

    reservedVenueMap.value = {};
  }
}

async function fetchClassSchedules() {
  try {
    classScheduleLoading.value = true;
    classScheduleError.value = '';
    classScheduleDataState.value = classScheduleList.value.length > 0 ? 'cached-loading' : 'loading';
    const { dateFrom, dateTo } = getScheduleQueryWindow(selectedClassroomDate.value);
    const response = await classScheduleApi.listScheduleBlocks({ dateFrom, dateTo });
    const schedulePayload = response?.data?.scheduleBlocks || response?.scheduleBlocks || [];
    classScheduleList.value = Array.isArray(schedulePayload)
      ? schedulePayload.map((scheduleRecord) => normalizeClassScheduleRecord(scheduleRecord))
      : [];
    classScheduleDataState.value = 'fresh';
    syncSelectedClassSchedule();
  } catch (error) {
    classScheduleList.value = [];
    classScheduleError.value = error?.response?.data?.errorMessage || 'Failed to load classroom schedules.';
    classScheduleDataState.value = 'error';
  } finally {
    classScheduleLoading.value = false;
  }
}

function readSessionCache(cacheKey) {
  if (typeof window === 'undefined') {
    return [];
  }

  try {
    const cachedValue = window.sessionStorage.getItem(cacheKey);
    const parsedValue = cachedValue ? JSON.parse(cachedValue) : [];
    return Array.isArray(parsedValue) ? parsedValue : [];
  } catch {
    return [];
  }
}

function writeSessionCache(cacheKey, records) {
  if (typeof window === 'undefined') {
    return;
  }

  try {
    window.sessionStorage.setItem(cacheKey, JSON.stringify(Array.isArray(records) ? records : []));
  } catch {
    // Cache writes are best-effort only.
  }
}

function openDeleteEquipmentModal(equipmentRecord) {
  selectedEquipmentCard.value = equipmentRecord;
  deleteEquipmentRecord.value = equipmentRecord;
  deleteEquipmentConfirmEmail.value = '';
  deleteEquipmentConfirmPassword.value = '';
  deleteEquipmentError.value = '';
  equipmentError.value = '';
}

function closeDeleteEquipmentModal() {
  if (isDeletingEquipment.value) return;
  resetDeleteEquipmentModalState();
}

function resetDeleteEquipmentModalState() {
  deleteEquipmentRecord.value = null;
  deleteEquipmentConfirmEmail.value = '';
  deleteEquipmentConfirmPassword.value = '';
  deleteEquipmentError.value = '';
}

async function confirmDeleteEquipment() {
  if (!deleteEquipmentRecord.value || isDeletingEquipment.value) return;

  if (!isDeleteEquipmentReady.value) {
    deleteEquipmentError.value = 'Please type your admin email and password to delete this equipment.';
    return;
  }

  try {
    isDeletingEquipment.value = true;
    deleteEquipmentError.value = '';

    await equipmentApi.deleteEquipment(deleteEquipmentRecord.value.equipmentIdentifier, {
      confirmedAdminEmail: deleteEquipmentConfirmEmail.value.trim(),
      confirmedAdminPassword: deleteEquipmentConfirmPassword.value,
    });

    const deletedIdentifier = deleteEquipmentRecord.value.equipmentIdentifier;
    resetDeleteEquipmentModalState();
    clearDeletedEquipmentSelection(deletedIdentifier);
    await fetchEquipment();
  } catch (error) {
    deleteEquipmentError.value = error?.response?.data?.errorMessage || 'Failed to delete equipment. Please try again.';
  } finally {
    isDeletingEquipment.value = false;
  }
}

function handleDeleteVenue(venueRecord) {
  selectedVenueCard.value = venueRecord;
  deleteVenueRecord.value = venueRecord;
  deleteConfirmEmail.value = '';
  deleteConfirmPassword.value = '';
  deleteVenueError.value = '';
}

function closeDeleteVenueModal() {
  if (isDeletingVenue.value) return;
  resetDeleteVenueModalState();
}

function resetDeleteVenueModalState() {
  deleteVenueRecord.value = null;
  deleteConfirmEmail.value = '';
  deleteConfirmPassword.value = '';
  deleteVenueError.value = '';
}

async function confirmDeleteVenue() {
  if (!deleteVenueRecord.value || isDeletingVenue.value) return;

  if (!currentAdminEmail.value) {
    deleteVenueError.value = 'Unable to verify the admin in-charge. Please sign in again.';
    return;
  }

  if (!isDeleteVenueReady.value) {
    deleteVenueError.value = 'Please type your exact admin email and password to delete this venue.';
    return;
  }

  try {
    isDeletingVenue.value = true;
    deleteVenueError.value = '';

    await venueApi.deleteVenue(deleteVenueRecord.value.venueIdentifier, {
      confirmedAdminEmail: normalizeEmailForConfirmation(deleteConfirmEmail.value),
      confirmedAdminPassword: deleteConfirmPassword.value,
    });

    if (selectedVenueCard.value?.venueIdentifier === deleteVenueRecord.value.venueIdentifier) {
      selectedVenueCard.value = null;
    }

    if (viewVenueRecord.value?.venueIdentifier === deleteVenueRecord.value.venueIdentifier) {
      closeVenueDetails();
    }

    resetDeleteVenueModalState();
    await fetchVenues();
  } catch (error) {
    deleteVenueError.value = error?.response?.data?.errorMessage || 'Failed to delete venue. Please try again.';
  } finally {
    isDeletingVenue.value = false;
  }
}

onMounted(() => {
  syncActiveFacilityTabFromRoute(route.query.tab);
  fetchVenues();
  fetchReservedVenuesForVisibleWeek();
  fetchEquipment();
  fetchClassSchedules();
});

watch(
  () => route.query.tab,
  (tabValue) => {
    syncActiveFacilityTabFromRoute(tabValue);
  }
);

watch(activeFacilityTab, () => {
  showingFilterValue.value = 'all';
  if (activeFacilityTab.value === 'venue' || activeFacilityTab.value === 'all') {
    fetchVenues();
    fetchReservedVenuesForVisibleWeek();
  }
});

watch(showingFilterOptions, (nextOptions) => {
  if (!nextOptions.some((option) => option.value === showingFilterValue.value)) {
    showingFilterValue.value = 'all';
  }
});

watch(venueCapacityOptions, (nextOptions) => {
  if (!nextOptions.some((option) => option.value === venueCapacityFilter.value)) {
    venueCapacityFilter.value = 'all';
  }
});

watch(selectedVenueCalendarDate, () => {
  venueMonthCursor.value = selectedVenueCalendarDate.value.slice(0, 7);
  if (activeFacilityTab.value === 'venue' || activeFacilityTab.value === 'all') {
    fetchVenues();
    fetchReservedVenuesForVisibleWeek();
  }
});

watch(selectedClassroomDate, () => {
  classroomMonthCursor.value = selectedClassroomDate.value.slice(0, 7);
  syncSelectedClassSchedule();

  if (activeFacilityTab.value === 'classroom-schedules') {
    fetchClassSchedules();
  }
});

watch([showingFilterValue, venueCapacityFilter, sortValue], () => {
  venueListCurrentPage.value = 1;
});

watch([searchQuery, showingFilterValue, availabilityFilter, sortValue], () => {
  equipmentCurrentPage.value = 1;
});

watch(searchQuery, () => {
  venueListCurrentPage.value = 1;
});

watch([venueListPageSize, venueDirectoryRecords], () => {
  if (venueListCurrentPage.value > venueListTotalPages.value) {
    venueListCurrentPage.value = venueListTotalPages.value;
  }
});

watch([equipmentPageSize, filteredEquipmentRecords], () => {
  if (equipmentCurrentPage.value > equipmentTotalPages.value) {
    equipmentCurrentPage.value = equipmentTotalPages.value;
  }
});

function handleEditEquipment(equipmentRecord) {
  selectedEquipmentCard.value = equipmentRecord;
  selectedEquipment.value = equipmentRecord;
  equipmentError.value = '';
  showEquipmentModal.value = true;
}

function handleViewEquipment(equipmentRecord) {
  selectedEquipmentCard.value = equipmentRecord;
  viewEquipmentRecord.value = equipmentRecord;
  equipmentError.value = '';
}

function handleSelectEquipment(equipmentRecord) {
  selectedEquipmentCard.value = equipmentRecord;
  equipmentError.value = '';
}

function closeEquipmentDetails() {
  viewEquipmentRecord.value = null;
}

function openEditFromDetails() {
  if (!viewEquipmentRecord.value) {
    return;
  }

  handleEditEquipment(viewEquipmentRecord.value);
  closeEquipmentDetails();
}

function normalizeEmailForConfirmation(emailAddress) {
  return String(emailAddress || '').replace(/[\s\u200B-\u200D\uFEFF]+/g, '').trim().toLowerCase();
}

function filterAndSortClassSchedules(scheduleRecords, rawQuery, selectedRoomValue, sortDirection, venueRecords) {
  const query = normalizeFilterQuery(rawQuery);
  const roomValue = String(selectedRoomValue || 'all');

  return [...scheduleRecords]
    .map((scheduleRecord) => withResolvedScheduleVenue(scheduleRecord, venueRecords))
    .filter((scheduleRecord) => {
      if (roomValue !== 'all' && String(scheduleRecord.venueIdentifier) !== roomValue) {
        return false;
      }

      if (query === '') {
        return true;
      }

      return [
        scheduleRecord.courseCode,
        scheduleRecord.courseName,
        scheduleRecord.blockLabel,
        scheduleRecord.instructorName,
        scheduleRecord.venueName,
        scheduleRecord.venueNameSnapshot,
      ].some((value) => normalizeFilterQuery(value).includes(query));
    })
    .sort((leftRecord, rightRecord) => compareByName(
      `${leftRecord.courseCode} ${leftRecord.courseName}`.trim() || leftRecord.blockLabel,
      `${rightRecord.courseCode} ${rightRecord.courseName}`.trim() || rightRecord.blockLabel,
      sortDirection,
    ));
}

function filterAndSortVenues(venues, rawQuery, sortDirection) {
  const query = normalizeFilterQuery(rawQuery);

  return [...venues]
    .filter((venue) => matchesVenueSearch(venue, query))
    .sort((left, right) => compareByName(left?.venueName, right?.venueName, sortDirection));
}

function filterAndSortEquipment(equipmentRecords, rawQuery, availability, sortDirection) {
  const query = normalizeFilterQuery(rawQuery);

  return [...equipmentRecords]
    .filter((equipmentRecord) => matchesEquipmentSearch(equipmentRecord, query))
    .filter((equipmentRecord) => matchesEquipmentAvailability(equipmentRecord, availability))
    .sort((left, right) => compareByName(left?.equipmentName, right?.equipmentName, sortDirection));
}

function buildVenueFloorGroups(venues, orderedFloors, selectedDate) {
  const groupedVenues = groupVenuesByFloor(venues, selectedDate);
  const sortedGroups = sortVenueGroupsByFloor(groupedVenues, orderedFloors);

  return Object.entries(sortedGroups).map(([floorLabel, venueRecords]) => ({
    floorLabel,
    venueRecords,
  }));
}

function normalizeFilterQuery(value) {
  return String(value || '').trim().toLowerCase();
}

function matchesVenueSearch(venue, query) {
  if (query === '') {
    return true;
  }

  return [venue?.venueName, venue?.venueLocation, venue?.floorLevel]
    .some((value) => normalizeFilterQuery(value).includes(query));
}

function matchesCalendarAvailability(venueRecord, availability, selectedDate) {
  if (availability === 'all') {
    return true;
  }

  const resolvedStatus = deriveVenueAvailabilityForDate(venueRecord, selectedDate);
  return availability === 'available'
    ? resolvedStatus === 'Available'
    : resolvedStatus !== 'Available';
}

function matchesEquipmentSearch(equipmentRecord, query) {
  if (query === '') {
    return true;
  }

  return getEquipmentSearchableValues(equipmentRecord)
    .some((value) => normalizeFilterQuery(value).includes(query));
}

function getEquipmentSearchableValues(equipmentRecord) {
  return [
    equipmentRecord?.equipmentName,
    equipmentRecord?.equipmentCategory || equipmentRecord?.categoryName,
    equipmentRecord?.equipmentBrand,
    equipmentRecord?.barcode,
    equipmentRecord?.assetId || equipmentRecord?.serialNumber,
  ];
}

function matchesEquipmentAvailability(equipmentRecord, availability) {
  if (availability === 'available') {
    return formatEquipmentStatus(equipmentRecord) === 'Available';
  }

  if (availability === 'unavailable') {
    return formatEquipmentStatus(equipmentRecord) !== 'Available';
  }

  return true;
}

function withResolvedScheduleVenue(scheduleRecord, venueRecords) {
  const matchedVenue = (venueRecords || []).find((venueRecord) => Number(venueRecord.venueIdentifier) === Number(scheduleRecord.venueIdentifier));

  return {
    ...scheduleRecord,
    venueName: matchedVenue?.venueName || scheduleRecord.venueNameSnapshot || '',
  };
}

function compareByName(leftName, rightName, sortDirection) {
  const normalizedLeft = normalizeFilterQuery(leftName);
  const normalizedRight = normalizeFilterQuery(rightName);

  return sortDirection === 'asc'
    ? normalizedLeft.localeCompare(normalizedRight)
    : normalizedRight.localeCompare(normalizedLeft);
}

function groupVenuesByFloor(venues, selectedDate) {
  return venues.reduce((groups, venue) => {
    const floor = venue?.floorLevel || 'Other';
    const normalizedVenue = mapVenueRecordForList(venue, selectedDate);

    if (!groups[floor]) {
      groups[floor] = [];
    }

    groups[floor].push(normalizedVenue);
    return groups;
  }, {});
}

function sortVenueGroupsByFloor(groupedVenues, orderedFloors) {
  const sortedGroups = {};

  orderedFloors.forEach((floor) => {
    if (groupedVenues[floor]) {
      sortedGroups[floor] = groupedVenues[floor];
    }
  });

  Object.keys(groupedVenues).forEach((floor) => {
    if (!sortedGroups[floor]) {
      sortedGroups[floor] = groupedVenues[floor];
    }
  });

  return sortedGroups;
}

function normalizeVenueRecord(venue) {
  if (!venue) {
    return null;
  }

  return {
    venueIdentifier: venue.venueIdentifier,
    venueName: venue.venueName || '',
    venueLocation: venue.venueLocation || '',
    floorLevel: venue.floorLevel || '',
    capacityLimit: venue.capacityLimit ?? null,
    availabilityDate: venue.availabilityDate || '',
    operationalStatus: venue.operationalStatus || '',
    availabilityStatus: venue.availabilityStatus || 'Unavailable',
    description: venue.description || '',
    imageUrl: venue.imageUrl || '',
    photoData: venue.imageUrl || '',
    reservationTimeRanges: Array.isArray(venue.reservationTimeRanges) ? venue.reservationTimeRanges : [],
    reservationDetails: Array.isArray(venue.reservationDetails) ? venue.reservationDetails : [],
  };
}

function resolveEquipmentCategory(equipmentRecord) {
  return formatEquipmentText(equipmentRecord?.equipmentCategory || equipmentRecord?.categoryName || 'Uncategorized');
}

function mapVenueRecordForList(venue, selectedDate) {
  const availabilityStatus = deriveVenueAvailabilityForDate(venue, selectedDate);

  return {
    ...venue,
    venueAvailable: availabilityStatus === 'Available',
    availabilityStatus,
  };
}

function clearDeletedEquipmentSelection(deletedIdentifier) {
  if (selectedEquipmentCard.value?.equipmentIdentifier === deletedIdentifier) {
    selectedEquipmentCard.value = null;
  }

  if (viewEquipmentRecord.value?.equipmentIdentifier === deletedIdentifier) {
    closeEquipmentDetails();
  }
}

function getTodayDateInputValue() {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function getScheduleQueryWindow(selectedDate) {
  const anchorDate = new Date(`${selectedDate}T00:00:00`);
  const rangeStart = new Date(anchorDate);
  const rangeEnd = new Date(anchorDate);
  rangeStart.setDate(rangeStart.getDate() - 45);
  rangeEnd.setDate(rangeEnd.getDate() + 45);

  return {
    dateFrom: formatDateInputValue(rangeStart),
    dateTo: formatDateInputValue(rangeEnd),
  };
}

function shiftSelectedVenueDate(dayOffset) {
  const nextDate = new Date(`${selectedVenueCalendarDate.value}T00:00:00`);
  nextDate.setDate(nextDate.getDate() + dayOffset);
  selectedVenueCalendarDate.value = formatDateInputValue(nextDate);
}

function setSelectedVenueDateToToday() {
  selectedVenueCalendarDate.value = todayDateKey;
}

function shiftSelectedVenueMonth(offset) {
  const [year, month] = venueMonthCursor.value.split('-').map((value) => Number(value));
  const nextDate = new Date(year, month - 1 + offset, 1);
  venueMonthCursor.value = `${nextDate.getFullYear()}-${String(nextDate.getMonth() + 1).padStart(2, '0')}`;
}

function selectVenueDate(dateValue) {
  selectedVenueCalendarDate.value = dateValue;
}

function getVenueEntriesForDate(dateValue) {
  return (reservedVenueMap.value[dateValue] || [])
    .filter((venueRecord) => hasReservedVenueEntry(venueRecord, dateValue))
    .map((venueRecord) => buildVenueCalendarEntry(venueRecord, dateValue));
}

function hasReservedVenueEntry(venueRecord, dateValue) {
  const reservationRanges = getVenueReservationRanges(venueRecord);
  if (reservationRanges.length === 0) {
    return false;
  }

  return reservationRanges.some((rangeRecord) => isReservationRangeScheduledForDate(rangeRecord, dateValue));
}

function isReservationRangeScheduledForDate(rangeRecord, dateValue) {
  const normalizedDate = String(dateValue || '').trim();
  const rangeDate = resolveReservationRangeDate(rangeRecord);

  if (rangeDate !== '') {
    return rangeDate === normalizedDate;
  }

  return true;
}

function buildVenueCalendarEntry(venueRecord, dateValue) {
  const parsedRanges = parseVenueTimeRanges(venueRecord, dateValue);
  const primaryRange = parsedRanges[0] || null;
  const metaLine = primaryRange?.timeLabel || 'Reserved schedule';

  return {
    sourceRecord: venueRecord,
    venueIdentifier: venueRecord.venueIdentifier,
    venueName: venueRecord.venueName,
    timeLabel: metaLine,
    startMinutes: primaryRange?.startMinutes ?? 480,
    endMinutes: primaryRange?.endMinutes ?? 540,
    metaLine,
    descriptionLine: venueRecord.venueLocation || venueRecord.floorLevel || 'Venue location not set',
    footerLabel: 'Reserved',
    capacityLabel: `Cap ${venueRecord.capacityLimit || 'N/A'}`,
  };
}

function buildVenueWeekScheduleColumn(weekDate) {
  const entries = getVenueEntriesForDate(weekDate.dateKey)
    .map((entry) => buildVenueWeekScheduleEntry(entry, weekDate.dateKey))
    .sort((leftEntry, rightEntry) => leftEntry.startMinutes - rightEntry.startMinutes);

  return {
    dateValue: weekDate.dateKey,
    weekdayLabel: weekDate.weekday,
    shortDateLabel: weekDate.monthDay,
    entryCountLabel: `${entries.length} reserved`,
    entries,
    timeSlots: buildVenueWeekScheduleSlots(entries),
  };
}

function buildVenueWeekScheduleEntry(entry, dateValue) {
  const venueRow = venueMatrixRows.value.find((row) => Number(row.venueIdentifier) === Number(entry.venueIdentifier)) || {
    venueIdentifier: entry.venueIdentifier,
    venueName: entry.venueName,
    capacityLabel: entry.capacityLabel,
    sourceRecord: entry.sourceRecord,
  };
  const block = {
    key: `${entry.venueIdentifier}-${dateValue}-${entry.startMinutes}`,
    tone: 'reserved',
    timeLabel: entry.timeLabel,
    title: entry.venueName,
    meta: entry.descriptionLine,
    reservationIdentifier: null,
    rawRangeRecord: entry.sourceRecord,
    dateValue,
  };

  return {
    key: block.key,
    block,
    venueRow,
    venueName: entry.venueName,
    locationLabel: entry.descriptionLine,
    capacityLabel: entry.capacityLabel,
    startMinutes: entry.startMinutes,
  };
}

function buildVenueWeekScheduleSlots(entries) {
  const slots = [
    { key: '0800', startMinutes: 480, endMinutes: 600, entries: [] },
    { key: '1000', startMinutes: 600, endMinutes: 720, entries: [] },
    { key: '1200', startMinutes: 720, endMinutes: 840, entries: [] },
    { key: '1400', startMinutes: 840, endMinutes: 960, entries: [] },
    { key: '1600', startMinutes: 960, endMinutes: 1080, entries: [] },
  ];

  entries.forEach((entry) => {
    const matchingSlot = slots.find((slot) => entry.startMinutes >= slot.startMinutes && entry.startMinutes < slot.endMinutes) || slots[slots.length - 1];
    matchingSlot.entries.push(entry);
  });

  return slots;
}

function matchesVenueCapacityFilter(venueRecord, capacityFilter) {
  if (capacityFilter === 'all') {
    return true;
  }

  const capacityValue = Number(venueRecord?.capacityLimit ?? 0);
  if (!Number.isFinite(capacityValue) || capacityValue <= 0) {
    return false;
  }

  if (capacityFilter === '1-50') {
    return capacityValue <= 50;
  }
  if (capacityFilter === '51-100') {
    return capacityValue >= 51 && capacityValue <= 100;
  }
  if (capacityFilter === '101-200') {
    return capacityValue >= 101 && capacityValue <= 200;
  }
  if (capacityFilter === '201+') {
    return capacityValue >= 201;
  }

  return true;
}

function buildVenueDirectoryCardRecord(venueRecord) {
  const directoryStatusTone = resolveVenueMatrixTone(venueRecord, selectedVenueCalendarDate.value);
  const reservedBlocks = countVenueReservedBlocksForWeek(venueRecord);
  const utilizationPercent = Math.min(
    directoryStatusTone === 'maintenance' ? 90 : Math.max(15, Math.round((reservedBlocks / Math.max(venueWeekDates.value.length, 1)) * 100)),
    100,
  );

  return {
    ...venueRecord,
    directoryStatusTone,
    directoryStatusLabel: resolveVenueDirectoryStatusLabel(directoryStatusTone),
    directoryMetaLabel: venueRecord.operationalStatus === 'Maintenance' ? 'Maintenance' : (venueRecord.description ? 'Managed Venue' : 'Venue Space'),
    utilizationPercent,
  };
}

function resolveVenueMatrixTone(venueRecord, dateValue, reservedVenueRecord = null) {
  const operationalStatus = String(venueRecord?.operationalStatus || '').trim();
  if (operationalStatus === 'Maintenance' || operationalStatus === 'Inactive') {
    return 'maintenance';
  }

  const reservedRecord = reservedVenueRecord || findReservedVenueRecord(venueRecord, dateValue);
  if (reservedRecord && hasReservedVenueEntry(reservedRecord, dateValue)) {
    return 'reserved';
  }

  return deriveVenueAvailabilityForDate(venueRecord, dateValue) === 'Available' ? 'available' : 'maintenance';
}

function resolveVenueDirectoryStatusLabel(statusTone) {
  if (statusTone === 'maintenance') {
    return 'Under Maintenance';
  }

  if (statusTone === 'reserved') {
    return 'Reserved';
  }

  return 'Available';
}

function resolveVenuePeakTimeLabel(dateValue) {
  const peakTimeCounter = new Map();

  getVenueEntriesForDate(dateValue).forEach((entry) => {
    const counterKey = entry.metaLine || 'No peak time';
    peakTimeCounter.set(counterKey, (peakTimeCounter.get(counterKey) || 0) + 1);
  });

  const [peakEntry] = [...peakTimeCounter.entries()].sort((leftEntry, rightEntry) => rightEntry[1] - leftEntry[1]);
  return peakEntry?.[0] || 'No peak time';
}

function countVenueReservedBlocksForWeek(venueRecord) {
  return venueWeekDates.value.reduce((totalBlocks, weekDate) => {
    const reservedVenueRecord = findReservedVenueRecord(venueRecord, weekDate.dateKey);
    return totalBlocks + (reservedVenueRecord ? buildVenueTimeBlocksForDate(reservedVenueRecord, weekDate.dateKey).reservedBlocks.length : 0);
  }, 0);
}

function findReservedVenueRecord(venueRecord, dateValue) {
  return (reservedVenueMap.value[dateValue] || []).find((reservedVenue) => (
    Number(reservedVenue?.venueIdentifier) === Number(venueRecord?.venueIdentifier)
  )) || null;
}

function buildVenueTimeBlocksForDate(venueRecord, dateValue) {
  const parsedRanges = parseVenueTimeRanges(venueRecord, dateValue);
  if (parsedRanges.length === 0) {
    return {
      reservedBlocks: [],
      availableBlocks: [{ startMinutes: 480, endMinutes: 1020, timeLabel: '8:00 AM - 5:00 PM' }],
    };
  }

  const availableBlocks = [];
  const reservedBlocks = [];
  let currentMinute = 480;

  parsedRanges.forEach((rangeRecord) => {
    if (rangeRecord.startMinutes > currentMinute) {
      availableBlocks.push({
        startMinutes: currentMinute,
        endMinutes: rangeRecord.startMinutes,
        timeLabel: `${formatMinutesDisplay(currentMinute)} - ${formatMinutesDisplay(rangeRecord.startMinutes)}`,
      });
    }

    reservedBlocks.push(rangeRecord);
    currentMinute = Math.max(currentMinute, rangeRecord.endMinutes);
  });

  if (currentMinute < 1020) {
    availableBlocks.push({
      startMinutes: currentMinute,
      endMinutes: 1020,
      timeLabel: `${formatMinutesDisplay(currentMinute)} - ${formatMinutesDisplay(1020)}`,
    });
  }

  return { reservedBlocks, availableBlocks };
}

function parseVenueTimeRanges(venueRecord, dateValue) {
  const reservationRanges = getVenueReservationRanges(venueRecord);

  return reservationRanges
    .filter((rangeRecord) => isReservationRangeScheduledForDate(rangeRecord, dateValue))
    .map((rangeRecord, rangeIndex) => normalizeVenueTimeRange(rangeRecord, rangeIndex))
    .filter(Boolean)
    .sort((leftRecord, rightRecord) => leftRecord.startMinutes - rightRecord.startMinutes);
}

function normalizeVenueTimeRange(rangeRecord, rangeIndex) {
  const normalizedRangeRecord = typeof rangeRecord === 'string'
    ? {
      title: 'Reserved',
      meta: 'Reserved schedule',
      timeLabel: String(rangeRecord).trim(),
    }
    : {
      title: String(
        rangeRecord?.reservationCode
          || rangeRecord?.reservedByName
          || rangeRecord?.label
          || rangeRecord?.blockLabel
          || rangeRecord?.title
          || 'Reserved'
      ).trim(),
      meta: String(
        rangeRecord?.organizationName
          || rangeRecord?.purposeDescription
          || rangeRecord?.activityType
          || rangeRecord?.statusLabel
          || 'Reserved schedule'
      ).trim(),
      timeLabel: String(
        rangeRecord?.timeRangeLabel
          || `${rangeRecord?.startTime || ''} - ${rangeRecord?.endTime || ''}`
      ).trim(),
    };
  const timeMatch = normalizedRangeRecord.timeLabel.match(/(\d{1,2}:\d{2}\s*[AP]M)\s*-\s*(\d{1,2}:\d{2}\s*[AP]M)/i);

  if (!timeMatch) {
    return {
      key: `fallback-${rangeIndex}`,
      timeLabel: normalizedRangeRecord.timeLabel || 'Reserved',
      title: normalizedRangeRecord.title || 'Reserved',
      meta: normalizedRangeRecord.meta || 'Scheduled block',
      startMinutes: 480 + (rangeIndex * 30),
      endMinutes: 540 + (rangeIndex * 30),
    };
  }

  const startMinutes = convertClockLabelToMinutes(timeMatch[1]);
  const endMinutes = convertClockLabelToMinutes(timeMatch[2]);

  return {
    key: `parsed-${rangeIndex}-${startMinutes}`,
    timeLabel: `${timeMatch[1].replace(/\s+/g, ' ').trim()} - ${timeMatch[2].replace(/\s+/g, ' ').trim()}`,
    title: normalizedRangeRecord.title || 'Reserved',
    meta: normalizedRangeRecord.meta || 'Reserved schedule',
    startMinutes,
    endMinutes: endMinutes > startMinutes ? endMinutes : startMinutes + 60,
    reservationIdentifier: Number(rangeRecord?.reservationIdentifier || 0) || null,
    rawRangeRecord: rangeRecord,
  };
}

function getVenueReservationRanges(venueRecord) {
  const reservationDetails = Array.isArray(venueRecord?.reservationDetails) ? venueRecord.reservationDetails : [];
  if (reservationDetails.length > 0) {
    return reservationDetails;
  }

  return Array.isArray(venueRecord?.reservationTimeRanges) ? venueRecord.reservationTimeRanges : [];
}

function resolveReservationRangeDate(rangeRecord) {
  const directDate = String(
    rangeRecord?.reservationDate
      || rangeRecord?.date
      || rangeRecord?.scheduleDate
      || rangeRecord?.startDate
      || rangeRecord?.blockDate
      || '',
  ).trim();

  if (directDate !== '') {
    return directDate;
  }

  const datedLabel = String(rangeRecord?.startDateTimeLabel || rangeRecord?.eventDateLabel || '').trim();
  if (datedLabel === '') {
    return '';
  }

  const parsedDate = new Date(datedLabel);
  if (Number.isNaN(parsedDate.getTime())) {
    return '';
  }

  return formatDateInputValue(parsedDate);
}

function convertClockLabelToMinutes(clockLabel) {
  const normalizedLabel = String(clockLabel || '').trim().toUpperCase();
  const timeMatch = normalizedLabel.match(/(\d{1,2}):(\d{2})\s*(AM|PM)/);
  if (!timeMatch) {
    return 480;
  }

  let hourValue = Number(timeMatch[1]) % 12;
  const minuteValue = Number(timeMatch[2]) || 0;
  if (timeMatch[3] === 'PM') {
    hourValue += 12;
  }

  return (hourValue * 60) + minuteValue;
}

function formatMinutesDisplay(totalMinutes) {
  const referenceDate = new Date();
  referenceDate.setHours(0, totalMinutes, 0, 0);
  return new Intl.DateTimeFormat('en-US', {
    hour: 'numeric',
    minute: '2-digit',
  }).format(referenceDate);
}

function normalizeClassScheduleRecord(scheduleRecord) {
  return {
    scheduleBlockIdentifier: Number(scheduleRecord?.scheduleBlockIdentifier || 0),
    venueIdentifier: Number(scheduleRecord?.venueIdentifier || 0),
    blockDate: String(scheduleRecord?.blockDate || ''),
    startTime: String(scheduleRecord?.startTime || ''),
    endTime: String(scheduleRecord?.endTime || ''),
    blockLabel: String(scheduleRecord?.blockLabel || '').trim(),
    blockType: String(scheduleRecord?.blockType || 'Class Schedule').trim() || 'Class Schedule',
    courseCode: String(scheduleRecord?.courseCode || '').trim(),
    courseName: String(scheduleRecord?.courseName || '').trim(),
    instructorName: String(scheduleRecord?.instructorName || '').trim(),
    daysOfWeek: Array.isArray(scheduleRecord?.daysOfWeek) ? scheduleRecord.daysOfWeek : [],
    dateRangeStart: String(scheduleRecord?.dateRangeStart || ''),
    dateRangeEnd: String(scheduleRecord?.dateRangeEnd || ''),
    venueNameSnapshot: String(scheduleRecord?.venueNameSnapshot || '').trim(),
    academicYear: String(scheduleRecord?.academicYear || '').trim(),
    semesterLabel: String(scheduleRecord?.semesterLabel || '').trim(),
    notes: String(scheduleRecord?.notes || '').trim(),
    capacityLimit: scheduleRecord?.capacityLimit ?? null,
  };
}

function buildWeekDates(selectedDate) {
  const anchorDate = new Date(`${selectedDate}T00:00:00`);
  const dayOfWeek = anchorDate.getDay();
  const mondayOffset = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
  const monday = new Date(anchorDate);
  monday.setDate(anchorDate.getDate() + mondayOffset);

  return Array.from({ length: 7 }, (_, index) => {
    const currentDate = new Date(monday);
    currentDate.setDate(monday.getDate() + index);

    return {
      dateKey: formatDateInputValue(currentDate),
      weekday: new Intl.DateTimeFormat('en-US', { weekday: 'long' }).format(currentDate),
      monthDay: new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric' }).format(currentDate),
    };
  });
}

function buildMiniCalendarDays(monthCursor, scheduleRecords) {
  const [year, month] = monthCursor.split('-').map((value) => Number(value));
  const firstDay = new Date(year, month - 1, 1);
  const gridStart = new Date(firstDay);
  gridStart.setDate(firstDay.getDate() - firstDay.getDay());

  return Array.from({ length: 42 }, (_, index) => {
    const currentDate = new Date(gridStart);
    currentDate.setDate(gridStart.getDate() + index);
    const dateKey = formatDateInputValue(currentDate);

    return {
      dateKey,
      dayNumber: currentDate.getDate(),
      isCurrentMonth: currentDate.getMonth() === month - 1,
      hasSchedules: (scheduleRecords || []).some((scheduleRecord) => scheduleRecord.blockDate === dateKey),
    };
  });
}

function buildSimpleCalendarDays(monthCursor) {
  const [year, month] = monthCursor.split('-').map((value) => Number(value));
  const firstDay = new Date(year, month - 1, 1);
  const gridStart = new Date(firstDay);
  gridStart.setDate(firstDay.getDate() - firstDay.getDay());

  return Array.from({ length: 42 }, (_, index) => {
    const currentDate = new Date(gridStart);
    currentDate.setDate(gridStart.getDate() + index);
    const dateKey = formatDateInputValue(currentDate);

    return {
      key: `${dateKey}-${index}`,
      dateValue: dateKey,
      dayNumber: currentDate.getDate(),
      inCurrentMonth: currentDate.getMonth() === month - 1,
    };
  });
}

function shiftClassroomMonth(offset) {
  const [year, month] = classroomMonthCursor.value.split('-').map((value) => Number(value));
  const nextDate = new Date(year, month - 1 + offset, 1);
  classroomMonthCursor.value = `${nextDate.getFullYear()}-${String(nextDate.getMonth() + 1).padStart(2, '0')}`;
}

function selectClassroomDate(dateKey) {
  selectedClassroomDate.value = dateKey;
}

function jumpClassroomDateByDays(dayOffset) {
  const nextDate = new Date(`${selectedClassroomDate.value}T00:00:00`);
  nextDate.setDate(nextDate.getDate() + dayOffset);
  selectClassroomDate(formatDateInputValue(nextDate));
}

function getSchedulesForClassroomDay(dateKey) {
  return filteredClassScheduleRecords.value.filter((scheduleRecord) => scheduleRecord.blockDate === dateKey);
}

function buildClassroomScheduleStyle(scheduleRecord) {
  const startMinutes = convertTimeToMinutes(scheduleRecord.startTime);
  const endMinutes = convertTimeToMinutes(scheduleRecord.endTime);
  const topOffset = Math.max(startMinutes - (7 * 60), 0);
  const duration = Math.max(endMinutes - startMinutes, 45);

  return {
    top: `${topOffset}px`,
    height: `${duration}px`,
  };
}

function buildQuickSlotStyle(quickSlot) {
  return {
    top: `${quickSlot.index * 60}px`,
    height: '60px',
  };
}

function convertTimeToMinutes(timeValue) {
  const [hourValue, minuteValue] = String(timeValue || '00:00').split(':').map((value) => Number(value));
  return (hourValue * 60) + (minuteValue || 0);
}

function classroomScheduleEventClass(blockType) {
  const normalizedType = normalizeFilterQuery(blockType);
  if (normalizedType.includes('reserve')) return 'classroom-schedule-event--reserved';
  if (normalizedType.includes('equipment')) return 'classroom-schedule-event--equipment';
  if (normalizedType.includes('pending')) return 'classroom-schedule-event--pending';
  if (normalizedType.includes('maintenance')) return 'classroom-schedule-event--maintenance';
  return 'classroom-schedule-event--class';
}

function formatClassroomScheduleTime(startTime, endTime) {
  return `${formatTimeDisplay(startTime)} - ${formatTimeDisplay(endTime)}`;
}

function formatTimeDisplay(timeValue) {
  if (!timeValue) return 'N/A';

  const [hourValue, minuteValue] = String(timeValue).split(':').map((value) => Number(value));
  const timeReference = new Date();
  timeReference.setHours(hourValue || 0, minuteValue || 0, 0, 0);

  return new Intl.DateTimeFormat('en-US', {
    hour: 'numeric',
    minute: '2-digit',
  }).format(timeReference);
}

function formatDisplayDateHeading(dateValue) {
  return new Intl.DateTimeFormat('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date(`${dateValue}T00:00:00`));
}

function formatDisplayDateHeadingWithWeekday(dateValue) {
  return new Intl.DateTimeFormat('en-US', {
    weekday: 'long',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date(`${dateValue}T00:00:00`));
}

function resolveDateValue(dateTimeValue) {
  const normalizedValue = String(dateTimeValue || '').trim();
  if (normalizedValue === '') {
    return selectedVenueCalendarDate.value;
  }

  if (/^\d{4}-\d{2}-\d{2}$/.test(normalizedValue)) {
    return normalizedValue;
  }

  const parsedDate = new Date(normalizedValue);
  if (Number.isNaN(parsedDate.getTime())) {
    return selectedVenueCalendarDate.value;
  }

  return formatDateInputValue(parsedDate);
}

function formatDateTimeLong(dateTimeValue) {
  const parsedDate = new Date(String(dateTimeValue || '').trim());
  if (Number.isNaN(parsedDate.getTime())) {
    return 'Not yet recorded';
  }

  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  }).format(parsedDate);
}

function formatTimeFromDateTime(dateTimeValue) {
  const parsedDate = new Date(String(dateTimeValue || '').trim());
  if (Number.isNaN(parsedDate.getTime())) {
    return 'N/A';
  }

  return new Intl.DateTimeFormat('en-US', {
    hour: 'numeric',
    minute: '2-digit',
  }).format(parsedDate);
}

function formatReservationDuration(startDateTimeValue, endDateTimeValue) {
  const startDate = new Date(String(startDateTimeValue || '').trim());
  const endDate = new Date(String(endDateTimeValue || '').trim());
  if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime()) || endDate <= startDate) {
    return 'N/A';
  }

  const durationMinutes = Math.round((endDate.getTime() - startDate.getTime()) / 60000);
  const durationHours = Math.floor(durationMinutes / 60);
  const remainderMinutes = durationMinutes % 60;

  if (remainderMinutes === 0) {
    return `${durationHours} hour${durationHours === 1 ? '' : 's'}`;
  }

  if (durationHours === 0) {
    return `${remainderMinutes} minute${remainderMinutes === 1 ? '' : 's'}`;
  }

  return `${durationHours} hour${durationHours === 1 ? '' : 's'} ${remainderMinutes} minute${remainderMinutes === 1 ? '' : 's'}`;
}

function normalizeReservationStatusTone(statusValue) {
  const normalizedStatus = normalizeFilterQuery(statusValue);
  if (normalizedStatus.includes('approved')) return 'approved';
  if (normalizedStatus.includes('pending')) return 'pending';
  if (normalizedStatus.includes('rejected') || normalizedStatus.includes('cancelled')) return 'inactive';
  return 'neutral';
}

function normalizeVenueReservationEntry(reservationRecord, block, venueRow) {
  const fallbackRecord = block?.rawRangeRecord || {};
  const startDateTime = String(
    reservationRecord?.eventDateTime
      || fallbackRecord?.startDateTimeLabel
      || ''
  ).trim();
  const endDateTime = String(
    reservationRecord?.endDateTime
      || fallbackRecord?.endDateTimeLabel
      || ''
  ).trim();
  const statusLabel = String(
    reservationRecord?.currentStatus
      || fallbackRecord?.statusLabel
      || 'Reserved'
  ).trim();
  const reservationCode = String(
    reservationRecord?.reservationCode
      || fallbackRecord?.reservationCode
      || ''
  ).trim();
  const venueName = String(venueRow?.venueName || reservationRecord?.venueName || 'Venue').trim();

  return {
    reservationIdentifier: Number(
      reservationRecord?.reservationIdentifier
        || block?.reservationIdentifier
        || fallbackRecord?.reservationIdentifier
        || 0
    ) || null,
    reservationCode,
    borrowerName: String(
      reservationRecord?.borrowerFullName
        || fallbackRecord?.reservedByName
        || 'Borrower record unavailable'
    ).trim() || 'Borrower record unavailable',
    venueLabel: reservationCode ? `${venueName} ${reservationCode}` : venueName,
    purposeLabel: String(
      reservationRecord?.purposeDescription
        || fallbackRecord?.purposeDescription
        || reservationRecord?.activityType
        || fallbackRecord?.activityType
        || 'No purpose recorded.'
    ).trim(),
    remarksLabel: String(
      reservationRecord?.borrowerRemarks
        || 'No remarks added.'
    ).trim(),
    statusLabel: statusLabel || 'Reserved',
    statusTone: normalizeReservationStatusTone(statusLabel),
    eventDateTime: startDateTime,
    dateValue: block?.dateValue || resolveDateValue(startDateTime),
    startDateLabel: startDateTime ? formatDisplayDateHeading(resolveDateValue(startDateTime)) : 'N/A',
    endDateLabel: endDateTime ? formatDisplayDateHeading(resolveDateValue(endDateTime)) : (startDateTime ? formatDisplayDateHeading(resolveDateValue(startDateTime)) : 'N/A'),
    startTimeLabel: startDateTime ? formatTimeFromDateTime(startDateTime) : (block?.timeLabel?.split('-')?.[0]?.trim() || 'N/A'),
    endTimeLabel: endDateTime ? formatTimeFromDateTime(endDateTime) : (block?.timeLabel?.split('-')?.[1]?.trim() || 'N/A'),
    durationLabel: formatReservationDuration(startDateTime, endDateTime),
    reservedOnLabel: reservationRecord?.submissionTimestamp ? formatDateTimeLong(reservationRecord.submissionTimestamp) : 'Not yet recorded',
    approvedByLabel: 'Not yet recorded',
    approvedOnLabel: 'Not yet recorded',
  };
}

function syncSelectedClassSchedule() {
  const scheduleForDate = filteredClassScheduleRecords.value.find((scheduleRecord) => scheduleRecord.blockDate === selectedClassroomDate.value);
  selectedClassScheduleRecord.value = scheduleForDate || null;
}

function toggleClassScheduleActionMenu() {
  showClassScheduleActionMenu.value = !showClassScheduleActionMenu.value;
}

function openManualClassScheduleModal() {
  showClassScheduleActionMenu.value = false;
  openAddScheduleForDate(selectedClassroomDate.value);
}

function openAddScheduleForDate(dateKey) {
  showClassScheduleActionMenu.value = false;
  classScheduleFormMode.value = 'create';
  classScheduleModalError.value = '';
  classScheduleForm.value = {
    ...createEmptyClassScheduleForm(),
    dateRangeStart: dateKey,
    dateRangeEnd: dateKey,
    blockDate: dateKey,
    daysOfWeek: [new Intl.DateTimeFormat('en-US', { weekday: 'long' }).format(new Date(`${dateKey}T00:00:00`))],
  };
  showClassScheduleModal.value = true;
}

function openQuickAddScheduleModal(dateKey = selectedClassroomDate.value, startTime = '13:00', endTime = '15:00') {
  showClassScheduleActionMenu.value = false;
  quickClassScheduleError.value = '';
  quickClassScheduleForm.value = {
    ...createEmptyQuickAddScheduleForm(),
    dateRangeStart: dateKey,
    dateRangeEnd: dateKey,
    blockDate: dateKey,
    startTime,
    endTime,
    daysOfWeek: [new Intl.DateTimeFormat('en-US', { weekday: 'long' }).format(new Date(`${dateKey}T00:00:00`))],
  };
  showQuickClassScheduleModal.value = true;
}

function closeQuickAddScheduleModal() {
  showQuickClassScheduleModal.value = false;
  quickClassScheduleError.value = '';
  quickClassScheduleForm.value = createEmptyQuickAddScheduleForm();
}

function openClassScheduleDetails(scheduleRecord) {
  selectedClassScheduleRecord.value = scheduleRecord;
  viewClassScheduleRecord.value = scheduleRecord;
}

function closeClassScheduleDetails() {
  viewClassScheduleRecord.value = null;
}

function openEditClassSchedule(scheduleRecord) {
  closeClassScheduleDetails();
  classScheduleFormMode.value = 'edit';
  classScheduleModalError.value = '';
  classScheduleForm.value = {
    scheduleBlockIdentifier: scheduleRecord.scheduleBlockIdentifier,
    venueIdentifier: scheduleRecord.venueIdentifier,
    courseCode: scheduleRecord.courseCode,
    courseName: scheduleRecord.courseName,
    instructorName: scheduleRecord.instructorName,
    blockType: scheduleRecord.blockType,
    academicYear: scheduleRecord.academicYear,
    semesterLabel: scheduleRecord.semesterLabel,
    daysOfWeek: scheduleRecord.daysOfWeek?.length ? [...scheduleRecord.daysOfWeek] : [new Intl.DateTimeFormat('en-US', { weekday: 'long' }).format(new Date(`${scheduleRecord.blockDate}T00:00:00`))],
    startTime: scheduleRecord.startTime,
    endTime: scheduleRecord.endTime,
    dateRangeStart: scheduleRecord.dateRangeStart || scheduleRecord.blockDate,
    dateRangeEnd: scheduleRecord.dateRangeEnd || scheduleRecord.blockDate,
    notes: scheduleRecord.notes,
    capacityLimit: scheduleRecord.capacityLimit || '',
    blockDate: scheduleRecord.blockDate,
  };
  showClassScheduleModal.value = true;
}

function closeClassScheduleModal() {
  showClassScheduleModal.value = false;
  classScheduleFormMode.value = 'create';
  classScheduleModalError.value = '';
  classScheduleForm.value = createEmptyClassScheduleForm();
}

function buildClassSchedulePayload(scheduleFormState) {
  const selectedVenue = classroomVenueOptions.value.find((venueRecord) => Number(venueRecord.venueIdentifier) === Number(scheduleFormState.venueIdentifier));

  return {
    venueIdentifier: Number(scheduleFormState.venueIdentifier || 0),
    venueNameSnapshot: selectedVenue?.venueName || '',
    courseCode: scheduleFormState.courseCode,
    courseName: scheduleFormState.courseName,
    instructorName: scheduleFormState.instructorName,
    scheduleType: scheduleFormState.blockType,
    blockType: scheduleFormState.blockType,
    daysOfWeek: scheduleFormState.daysOfWeek,
    startTime: scheduleFormState.startTime,
    endTime: scheduleFormState.endTime,
    dateRangeStart: scheduleFormState.dateRangeStart,
    dateRangeEnd: scheduleFormState.dateRangeEnd,
    blockDate: scheduleFormState.blockDate || scheduleFormState.dateRangeStart,
    academicYear: scheduleFormState.academicYear,
    semesterLabel: scheduleFormState.semesterLabel,
    capacityLimit: scheduleFormState.capacityLimit || null,
    notes: scheduleFormState.notes,
    blockLabel: `${scheduleFormState.courseCode || ''} ${scheduleFormState.courseName || ''}`.trim(),
  };
}

async function submitClassSchedule() {
  const validationError = validateClassScheduleForm(classScheduleForm.value);
  if (validationError) {
    classScheduleModalError.value = validationError;
    return;
  }

  const payload = buildClassSchedulePayload(classScheduleForm.value);

  try {
    isSavingClassSchedule.value = true;
    classScheduleModalError.value = '';

    if (classScheduleFormMode.value === 'edit' && classScheduleForm.value.scheduleBlockIdentifier) {
      await classScheduleApi.updateScheduleBlock(classScheduleForm.value.scheduleBlockIdentifier, payload);
    } else {
      await classScheduleApi.createScheduleBlock(payload);
    }

    closeClassScheduleModal();
    await fetchClassSchedules();
  } catch (error) {
    classScheduleModalError.value = error?.response?.data?.errorMessage || 'Failed to save classroom schedule.';
  } finally {
    isSavingClassSchedule.value = false;
  }
}

async function submitQuickAddSchedule() {
  const validationError = validateClassScheduleForm(quickClassScheduleForm.value);
  if (validationError) {
    quickClassScheduleError.value = validationError;
    return;
  }

  const payload = buildClassSchedulePayload(quickClassScheduleForm.value);

  try {
    isSavingQuickClassSchedule.value = true;
    quickClassScheduleError.value = '';
    await classScheduleApi.createScheduleBlock(payload);
    closeQuickAddScheduleModal();
    await fetchClassSchedules();
  } catch (error) {
    quickClassScheduleError.value = error?.response?.data?.errorMessage || 'Failed to save quick schedule.';
  } finally {
    isSavingQuickClassSchedule.value = false;
  }
}

async function deleteClassSchedule(scheduleRecord) {
  if (!scheduleRecord?.scheduleBlockIdentifier || isDeletingClassSchedule.value) {
    return;
  }

  if (!window.confirm('Delete this class schedule block?')) {
    return;
  }

  try {
    isDeletingClassSchedule.value = true;
    await classScheduleApi.deleteScheduleBlock(scheduleRecord.scheduleBlockIdentifier);
    closeClassScheduleDetails();
    await fetchClassSchedules();
  } catch (error) {
    classScheduleError.value = error?.response?.data?.errorMessage || 'Failed to delete classroom schedule.';
  } finally {
    isDeletingClassSchedule.value = false;
  }
}

function openImportSchedulesModal() {
  showClassScheduleActionMenu.value = false;
  resetImportScheduleState();
  showImportSchedulesModal.value = true;
}

function closeImportSchedulesModal() {
  resetImportScheduleState();
  showImportSchedulesModal.value = false;
}

async function handleImportFileChange(event) {
  const selectedFile = event?.target?.files?.[0] || null;
  resetImportScheduleState();
  importScheduleFile.value = selectedFile;

  if (!selectedFile) {
    return;
  }

  try {
    importScheduleParsing.value = true;
    const {
      headers,
      rows,
      metadata,
    } = await parseImportedScheduleFile(selectedFile);

    if (headers.length === 0) {
      throw new Error('No columns were detected in the uploaded file.');
    }

    if (rows.length === 0) {
      throw new Error('The uploaded file does not contain any data rows to import.');
    }

    importScheduleHeaders.value = headers;
    importScheduleRows.value = rows;
    importScheduleDetectionMetadata.value = metadata || createEmptyImportDetectionMetadata();
    const { headerSelections, autoMappedHeaders } = buildInitialImportHeaderSelections(headers);
    importScheduleHeaderSelections.value = headerSelections;
    importScheduleAutoMappedHeaders.value = autoMappedHeaders;
  } catch (error) {
    importScheduleError.value = error instanceof Error ? error.message : 'Failed to parse the selected file.';
  } finally {
    importScheduleParsing.value = false;
  }
}

function createEmptyClassScheduleForm() {
  return {
    scheduleBlockIdentifier: null,
    venueIdentifier: 0,
    courseCode: '',
    courseName: '',
    instructorName: '',
    blockType: 'Class Schedule',
    academicYear: '',
    semesterLabel: '',
    daysOfWeek: [],
    startTime: '08:00',
    endTime: '10:00',
    dateRangeStart: selectedClassroomDate.value,
    dateRangeEnd: selectedClassroomDate.value,
    notes: '',
    capacityLimit: '',
    blockDate: selectedClassroomDate.value,
  };
}

function validateClassScheduleForm(scheduleFormState) {
  if (Number(scheduleFormState?.venueIdentifier || 0) <= 0) {
    return 'Please select a room before saving the classroom schedule.';
  }

  if (!String(scheduleFormState?.courseCode || '').trim() && !String(scheduleFormState?.courseName || '').trim()) {
    return 'Please provide at least a course code or course name.';
  }

  if (!String(scheduleFormState?.startTime || '').trim() || !String(scheduleFormState?.endTime || '').trim()) {
    return 'Please provide both a start time and end time.';
  }

  if (String(scheduleFormState.endTime) <= String(scheduleFormState.startTime)) {
    return 'End time must be later than start time.';
  }

  if (!String(scheduleFormState?.dateRangeStart || '').trim() || !String(scheduleFormState?.dateRangeEnd || '').trim()) {
    return 'Please provide both a start date and end date.';
  }

  if (String(scheduleFormState.dateRangeEnd) < String(scheduleFormState.dateRangeStart)) {
    return 'End date must be on or after the start date.';
  }

  if (!Array.isArray(scheduleFormState?.daysOfWeek) || scheduleFormState.daysOfWeek.length === 0) {
    return 'Select at least one day for the class schedule.';
  }

  return '';
}

function createEmptyQuickAddScheduleForm() {
  return {
    ...createEmptyClassScheduleForm(),
    academicYear: '2026 - 2027',
    semesterLabel: '1st Semester',
    startTime: '13:00',
    endTime: '15:00',
  };
}

function resetImportScheduleState() {
  importScheduleFile.value = null;
  importScheduleStep.value = 'upload';
  importScheduleError.value = '';
  importScheduleParsing.value = false;
  importScheduleHeaders.value = [];
  importScheduleRows.value = [];
  importScheduleHeaderSelections.value = {};
  importScheduleAutoMappedHeaders.value = {};
  importScheduleDetectionMetadata.value = createEmptyImportDetectionMetadata();
  importScheduleReviewRows.value = [];
  importScheduleImporting.value = false;
  importScheduleResult.value = null;
}

function goToPreviousImportStep() {
  if (importScheduleImporting.value) {
    return;
  }

  if (importScheduleStep.value === 'map') {
    importScheduleStep.value = 'upload';
    return;
  }

  if (importScheduleStep.value === 'review') {
    importScheduleStep.value = 'map';
    return;
  }

  if (importScheduleStep.value === 'import') {
    importScheduleStep.value = 'review';
  }
}

function goToNextImportStep() {
  if (importScheduleStep.value === 'upload' && importScheduleCanAdvanceFromUpload.value) {
    importScheduleStep.value = 'map';
    return;
  }

  if (importScheduleStep.value === 'map' && importScheduleCanAdvanceFromMap.value) {
    buildImportReviewRows();
    importScheduleStep.value = 'review';
  }
}

function buildImportReviewRows() {
  importScheduleReviewRows.value = importScheduleRows.value.map((rowValues, rowIndex) => buildImportReviewRow(rowValues, rowIndex));
  importScheduleResult.value = null;
}

function buildImportReviewRow(rowValues, rowIndex) {
  const errors = [];
  const venueValue = getImportMappedValue('venueReference', rowValues);
  const resolvedVenue = resolveImportedVenue(venueValue);
  const startTime = normalizeImportedTime(getImportMappedValue('startTime', rowValues));
  const endTime = normalizeImportedTime(getImportMappedValue('endTime', rowValues));
  const blockDate = normalizeImportedDate(getImportMappedValue('blockDate', rowValues));
  const dateRangeStart = normalizeImportedDate(getImportMappedValue('dateRangeStart', rowValues));
  const dateRangeEnd = normalizeImportedDate(getImportMappedValue('dateRangeEnd', rowValues));
  const daysOfWeek = normalizeImportedDayList(getImportMappedValue('daysOfWeek', rowValues));
  const courseCode = normalizeImportedText(getImportMappedValue('courseCode', rowValues));
  const courseName = normalizeImportedText(getImportMappedValue('courseName', rowValues));
  const instructorName = normalizeImportedText(getImportMappedValue('instructorName', rowValues));
  const blockType = normalizeImportedText(getImportMappedValue('blockType', rowValues)) || 'Class Schedule';
  const academicYear = normalizeImportedText(getImportMappedValue('academicYear', rowValues));
  const semesterLabel = normalizeImportedText(getImportMappedValue('semesterLabel', rowValues));
  const notes = normalizeImportedText(getImportMappedValue('notes', rowValues));
  const explicitLabel = normalizeImportedText(getImportMappedValue('blockLabel', rowValues));
  const capacityLimit = normalizeImportedCapacity(getImportMappedValue('capacityLimit', rowValues));
  const hasRecurringInputs = Boolean(dateRangeStart || dateRangeEnd || daysOfWeek.length > 0);
  const blockLabel = explicitLabel || `${courseCode} ${courseName}`.trim();

  if (!resolvedVenue) {
    errors.push('Room / venue must match an existing classroom venue.');
  }

  if (!startTime || !endTime) {
    errors.push('Valid start and end times are required.');
  } else if (endTime <= startTime) {
    errors.push('End time must be later than start time.');
  }

  if (hasRecurringInputs) {
    if (!dateRangeStart || !dateRangeEnd) {
      errors.push('Recurring rows require both start and end dates.');
    } else if (dateRangeEnd < dateRangeStart) {
      errors.push('Recurring end date must be on or after the start date.');
    } else if (daysOfWeek.length > 0 && countRecurringImportMatches(dateRangeStart, dateRangeEnd, daysOfWeek) === 0) {
      errors.push('No class schedule dates matched the selected day range.');
    }

    if (daysOfWeek.length === 0) {
      errors.push('Recurring rows require at least one day of week.');
    }
  } else if (!blockDate) {
    errors.push('A single date is required when recurring fields are not provided.');
  }

  if (!blockLabel) {
    errors.push('Provide a schedule label or at least a course code/name.');
  }

  if (capacityLimit === 'invalid') {
    errors.push('Capacity must be a whole number when provided.');
  }

  const payload = errors.length === 0
    ? {
      venueIdentifier: Number(resolvedVenue.venueIdentifier),
      venueNameSnapshot: resolvedVenue.venueName,
      courseCode,
      courseName,
      instructorName,
      scheduleType: blockType,
      blockType,
      daysOfWeek,
      startTime,
      endTime,
      dateRangeStart: hasRecurringInputs ? dateRangeStart : '',
      dateRangeEnd: hasRecurringInputs ? dateRangeEnd : '',
      blockDate: hasRecurringInputs ? dateRangeStart : blockDate,
      academicYear,
      semesterLabel,
      capacityLimit: typeof capacityLimit === 'number' ? capacityLimit : null,
      notes,
      blockLabel,
    }
    : null;

  return {
    rowNumber: rowIndex + 2,
    isValid: errors.length === 0,
    errors,
    payload,
    venueLabel: resolvedVenue?.venueName || normalizeImportedText(venueValue) || 'Unknown venue',
    scheduleLabel: blockLabel || 'Unlabeled schedule',
    dateLabel: hasRecurringInputs
      ? `${dateRangeStart || 'Missing start'} to ${dateRangeEnd || 'Missing end'} | ${daysOfWeek.length > 0 ? daysOfWeek.join(', ') : 'Missing days'}`
      : blockDate || 'Missing date',
    timeLabel: startTime && endTime ? formatClassroomScheduleTime(startTime, endTime) : 'Missing time',
    typeLabel: blockType,
    importStatus: errors.length === 0 ? 'ready' : 'invalid',
    importStatusLabel: errors.length === 0 ? 'Ready' : 'Invalid',
  };
}

async function submitImportedSchedules() {
  if (!importScheduleCanImport.value) {
    return;
  }

  const validRows = importScheduleReviewRows.value.filter((row) => row.isValid);
  importScheduleImporting.value = true;
  importScheduleStep.value = 'import';
  importScheduleError.value = '';
  importScheduleResult.value = {
    processedCount: 0,
    importedCount: 0,
    failedCount: 0,
  };

  for (const reviewRow of validRows) {
    try {
      await classScheduleApi.createScheduleBlock(reviewRow.payload);
      reviewRow.importStatus = 'imported';
      reviewRow.importStatusLabel = 'Imported';
      importScheduleResult.value.importedCount += 1;
    } catch (error) {
      const errorMessage = error?.response?.data?.errorMessage || 'Failed to save this row.';
      reviewRow.importStatus = 'failed';
      reviewRow.importStatusLabel = 'Failed';
      reviewRow.errors = [...reviewRow.errors, errorMessage];
      importScheduleResult.value.failedCount += 1;
    } finally {
      importScheduleResult.value.processedCount += 1;
    }
  }

  importScheduleImporting.value = false;

  if (importScheduleResult.value.importedCount > 0) {
    await fetchClassSchedules();
  }
}

function getImportSelectedTargetForHeader(headerName) {
  return importScheduleHeaderSelections.value[headerName] || '';
}

function updateImportTargetMapping(headerName, selectedTargetKey) {
  const normalizedTargetKey = String(selectedTargetKey || '').trim();
  const nextHeaderSelections = { ...importScheduleHeaderSelections.value };
  const nextAutoMappedHeaders = { ...importScheduleAutoMappedHeaders.value };

  Object.keys(nextHeaderSelections).forEach((header) => {
    if (header !== headerName && nextHeaderSelections[header] === normalizedTargetKey) {
      nextHeaderSelections[header] = '';
      nextAutoMappedHeaders[header] = false;
    }
  });

  nextHeaderSelections[headerName] = normalizedTargetKey;
  nextAutoMappedHeaders[headerName] = false;
  importScheduleHeaderSelections.value = nextHeaderSelections;
  importScheduleAutoMappedHeaders.value = nextAutoMappedHeaders;
}

function isImportTargetTaken(targetKey, activeHeaderName) {
  if (!targetKey) {
    return false;
  }

  return Object.entries(importScheduleHeaderSelections.value).some(([header, mappedTarget]) => (
    header !== activeHeaderName && mappedTarget === targetKey
  ));
}

function getImportMappedValue(targetKey, rowValues) {
  const headerName = importScheduleTargetToHeaderMap.value[targetKey] || '';
  if (!headerName) {
    return '';
  }

  const headerIndex = importScheduleHeaders.value.indexOf(headerName);
  return headerIndex >= 0 ? rowValues[headerIndex] ?? '' : '';
}

function resolveImportedVenue(venueValue) {
  const normalizedVenue = normalizeImportedText(venueValue);
  if (!normalizedVenue) {
    return null;
  }

  return classroomVenueOptions.value.find((venueOption) => (
    String(venueOption.venueIdentifier) === normalizedVenue
    || normalizeImportAliasKey(venueOption.venueName) === normalizeImportAliasKey(normalizedVenue)
  )) || null;
}

function importSchedulePreviewCell(header, rowValues) {
  const headerIndex = importScheduleHeaders.value.indexOf(header);
  return headerIndex >= 0 ? normalizeImportedText(rowValues[headerIndex] ?? '') || 'No sample value' : 'No sample value';
}

function getImportMappingStatusForHeader(headerName) {
  const targetKey = getImportSelectedTargetForHeader(headerName);
  if (!targetKey) {
    return {
      label: 'Ignored',
      tone: 'unmapped',
      mappedTarget: '',
    };
  }

  const targetLabel = importScheduleTargetOptions.find((targetOption) => targetOption.key === targetKey)?.label || targetKey;

  if (importScheduleAutoMappedHeaders.value[headerName]) {
    return {
      label: `Auto-mapped to ${targetLabel}`,
      tone: 'auto',
      mappedTarget: targetKey,
    };
  }

  return {
    label: `Mapped to ${targetLabel}`,
    tone: 'manual',
    mappedTarget: targetKey,
  };
}

function formatScheduleDuration(startTime, endTime) {
  const durationMinutes = Math.max(convertTimeToMinutes(endTime) - convertTimeToMinutes(startTime), 0);
  const durationHours = Math.floor(durationMinutes / 60);
  const remainderMinutes = durationMinutes % 60;

  if (durationHours <= 0 && remainderMinutes <= 0) {
    return '0 minutes';
  }

  if (remainderMinutes === 0) {
    return `${durationHours} hour${durationHours === 1 ? '' : 's'}`;
  }

  return `${durationHours} hour${durationHours === 1 ? '' : 's'} ${remainderMinutes} min`;
}

function formatHourLabel(hourValue) {
  const normalizedHour = hourValue % 24;
  const suffix = normalizedHour >= 12 ? 'PM' : 'AM';
  const displayHour = normalizedHour % 12 || 12;
  return `${displayHour} ${suffix}`;
}

function formatImportFileSize(fileSize) {
  const normalizedSize = Number(fileSize || 0);
  if (normalizedSize < 1024) {
    return `${normalizedSize} B`;
  }

  if (normalizedSize < 1024 * 1024) {
    return `${(normalizedSize / 1024).toFixed(1)} KB`;
  }

  return `${(normalizedSize / (1024 * 1024)).toFixed(1)} MB`;
}

function formatScheduleDays(daysOfWeek) {
  return Array.isArray(daysOfWeek) && daysOfWeek.length > 0 ? daysOfWeek.join(', ') : 'Not specified';
}

function formatScheduleDateRange(startDate, endDate) {
  const startLabel = startDate ? formatSummaryDate(startDate) : 'N/A';
  const endLabel = endDate ? formatSummaryDate(endDate) : 'N/A';
  return `${startLabel} - ${endLabel}`;
}

function formatDateInputValue(dateValue) {
  const year = dateValue.getFullYear();
  const month = String(dateValue.getMonth() + 1).padStart(2, '0');
  const day = String(dateValue.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function formatSummaryDate(dateValue) {
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date(`${dateValue}T00:00:00`));
}

function syncActiveFacilityTabFromRoute(tabValue) {
  const normalizedTab = String(tabValue || '').trim().toLowerCase();
  if (normalizedTab === 'equipment' || normalizedTab === 'venue' || normalizedTab === 'classroom-schedules' || normalizedTab === 'all') {
    activeFacilityTab.value = normalizedTab;
  }
}

function updateFacilityTabQuery(tabName) {
  const nextQuery = { ...route.query };

  if (tabName === 'equipment' || tabName === 'classroom-schedules' || tabName === 'all') {
    nextQuery.tab = tabName;
  } else {
    delete nextQuery.tab;
  }

  router.replace({ query: nextQuery });
}

function handleGoBack() {
  router.back();
}
</script>
