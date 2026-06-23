<!-- ===== AI GENERATED: AdminManageFacilitiesPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <div class="logs-page-header manage-facilities-page-header">
      <h2 class="logs-page-heading">Manage Facilities</h2>
      <button class="logs-go-back-button" type="button" @click="handleGoBack">
        ← Go Back
      </button>
    </div>

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

        <div class="manage-facilities-inline-actions">
          <button
            v-if="activeFacilityTab === 'classroom-schedules'"
            class="manage-facilities-add-button manage-facilities-add-button--compact"
            @click="openImportSchedulesModal"
          >
            Import Schedules
          </button>
          <button class="manage-facilities-add-button manage-facilities-add-button--compact" @click="handleAddFacility">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"/>
              <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            {{ addButtonLabel }}
          </button>
        </div>
      </div>

      <div v-if="activeFacilityTab !== 'classroom-schedules'" class="manage-facilities-filter-row">
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

      <div class="manage-facilities-search-sort-row">
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
      </div>

      <div v-if="activeFacilityTab === 'venue'">
        <div v-if="loading" class="manage-facilities-loading">Loading venue operations...</div>
        <p v-else-if="venueError" class="manage-facilities-modal-error">{{ venueError }}</p>
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
              :venue-floor-groups="venueFloorGroups"
              :availability-filter="availabilityFilter"
              @view-venue="handleViewVenue"
              @edit-venue="handleEditVenue"
              @delete-venue="handleDeleteVenue"
            />
          </div>
        </div>
      </div>

      <div v-else-if="activeFacilityTab === 'classroom-schedules'">
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
                <p>{{ selectedClassScheduleRecord.venueNameSnapshot || selectedClassScheduleRecord.venueName || 'Venue not set' }}</p>
                <p>{{ formatClassroomScheduleTime(selectedClassScheduleRecord.startTime, selectedClassScheduleRecord.endTime) }}</p>
                <p>{{ selectedClassScheduleRecord.instructorName || 'Instructor not specified' }}</p>
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
              <button type="button" class="classroom-schedule-board-icon-button" @click="jumpClassroomDateByDays(-7)">‹</button>
              <button type="button" class="classroom-schedule-board-today" @click="selectClassroomDate(todayDateKey)">Today</button>
              <button type="button" class="classroom-schedule-board-icon-button" @click="jumpClassroomDateByDays(7)">›</button>
              <div class="classroom-schedule-board-spacer"></div>
              <span class="classroom-schedule-board-view-badge">Weekly View</span>
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
                @dblclick="openAddScheduleForDate(weekDate.dateKey)"
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
                  <strong>{{ scheduleRecord.courseCode || scheduleRecord.blockLabel }}</strong>
                  <span>{{ scheduleRecord.courseName || scheduleRecord.blockLabel }}</span>
                  <small>{{ scheduleRecord.venueNameSnapshot || scheduleRecord.venueName || 'Venue' }}</small>
                  <small>{{ formatClassroomScheduleTime(scheduleRecord.startTime, scheduleRecord.endTime) }}</small>
                </button>
              </div>
            </div>
          </section>
        </div>
      </div>
    </section>

    <div v-if="activeFacilityTab === 'equipment' && equipmentLoading" class="manage-facilities-loading">Loading equipment...</div>
    <p v-else-if="activeFacilityTab === 'equipment' && equipmentError" class="manage-facilities-modal-error">{{ equipmentError }}</p>
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
        :equipment-records="filteredEquipmentRecords"
        :availability-filter="availabilityFilter"
        :selected-equipment-identifier="selectedEquipmentCard?.equipmentIdentifier || null"
        @edit-equipment="handleEditEquipment"
        @delete-equipment="openDeleteEquipmentModal"
        @view-equipment="handleViewEquipment"
        @select-equipment="handleSelectEquipment"
      />
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
              <input v-model.trim="classScheduleForm.academicYear" type="text" placeholder="2026 - 2027" />
            </label>
            <label>
              <span>Semester</span>
              <input v-model.trim="classScheduleForm.semesterLabel" type="text" placeholder="1st Semester" />
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
          <label class="classroom-schedule-import-dropzone">
            <input type="file" accept=".xlsx,.xls,.csv" @change="handleImportFileChange" />
            <strong>{{ importScheduleFile ? importScheduleFile.name : 'Drag and drop your file here, or click to browse' }}</strong>
            <span>Supports `.xlsx`, `.xls`, and `.csv` files.</span>
          </label>
          <p class="classroom-schedule-import-note">File import UI is ready. Manual entry is fully supported in this build, while bulk import parsing can be connected next.</p>
        </div>

        <div class="manage-facilities-modal-actions">
          <button class="manage-facilities-cancel-button" type="button" @click="closeImportSchedulesModal">Close</button>
        </div>
      </section>
    </div>

    <VenueModalComponent
      :show="showVenueModal"
      :venue="selectedVenue"
      @close="handleVenueModalClose"
      @saved="handleVenueModalSaved"
    />

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
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import '@/pages/borrower/css/Logs.css';
import './css/ManageFacilities.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import FacilityVenueListComponent from '@/modules/facility/components/FacilityVenueListComponent.vue';
import FacilityEquipmentGridComponent from '@/modules/facility/components/FacilityEquipmentGridComponent.vue';
import VenueAvailabilityCalendarComponent from '@/modules/facility/components/VenueAvailabilityCalendarComponent.vue';
import VenueDeleteModalComponent from '@/modules/facility/components/VenueDeleteModalComponent.vue';
import VenueDetailsModalComponent from '@/modules/facility/components/VenueDetailsModalComponent.vue';
import VenueModalComponent from '@/modules/facility/components/VenueModalComponent.vue';
import EquipmentDetailsModalComponent from '@/modules/facility/components/EquipmentDetailsModalComponent.vue';
import EquipmentModalComponent from '@/modules/facility/components/EquipmentModalComponent.vue';
import venueApi from '@/modules/reservation/services/venueApi.js';
import equipmentApi from '@/modules/reservation/services/equipmentApi.js';
import classScheduleApi from '@/modules/reservation/services/classScheduleApi.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import {
  deriveVenueAvailabilityForDate,
  isVenueFloorPlaceholderRecord,
} from '@/modules/facility/utils/venueFormValidation.js';
import {
  formatEquipmentQuantity,
  formatEquipmentStatus,
  formatEquipmentText,
  resolveEquipmentPhoto,
  resolveEquipmentPhotoStyle,
} from '@/modules/facility/utils/equipmentPresentation.js';

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

const venuesList = ref([]);
const equipmentList = ref([]);
const loading = ref(false);
const venueError = ref('');
const equipmentLoading = ref(false);
const equipmentError = ref('');
const selectedVenueCalendarDate = ref(getTodayDateInputValue());
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
const classroomMonthCursor = ref(getMonthCursor(getTodayDateInputValue()));
const showClassScheduleModal = ref(false);
const showImportSchedulesModal = ref(false);
const classScheduleFormMode = ref('create');
const classScheduleModalError = ref('');
const isSavingClassSchedule = ref(false);
const viewClassScheduleRecord = ref(null);
const isDeletingClassSchedule = ref(false);
const importScheduleFile = ref(null);
const classScheduleForm = ref(createEmptyClassScheduleForm());

const currentAdminEmail = computed(() =>
  authStore.accountData?.emailAddress || authStore.clerkAccountData?.emailAddress || ''
);

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

const floorOrder = [
  '18th Floor', '17th Floor', '16th Floor', '15th Floor', '8th Floor',
  '7th Floor', '6th Floor', '5th Floor', '4th Floor', '3rd Floor',
  '2nd Floor', '1st Floor', 'GF / 1st Floor', 'MH Floor', 'Pool', 'Outdoor',
];
const classroomDayOptions = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
const classroomScheduleTypeOptions = ['Class Schedule', 'Reserved', 'Equipment Reservation', 'Pending', 'Maintenance'];
const miniCalendarWeekdays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
const classroomTimeLabels = ['7 AM', '8 AM', '9 AM', '10 AM', '11 AM', '12 PM', '1 PM', '2 PM', '3 PM', '4 PM', '5 PM', '6 PM'];
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

const venueFloorGroups = computed(() => buildVenueFloorGroups(
  filteredVenueRecords.value,
  floorOrder,
  selectedVenueCalendarDate.value,
));

const filteredVenueRecords = computed(() => {
  const selectedFloor = showingFilterValue.value;
  if (selectedFloor === 'all') {
    return searchedAndSortedVenues.value;
  }

  return searchedAndSortedVenues.value.filter((venueRecord) => venueRecord.floorLevel === selectedFloor);
});

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
    const floorOptions = Array.from(new Set(
      searchedAndSortedVenues.value
        .map((venueRecord) => venueRecord.floorLevel)
        .filter(Boolean)
    ));

    return [
      { value: 'all', label: 'All floors' },
      ...floorOrder
        .filter((floorLabel) => floorOptions.includes(floorLabel))
        .map((floorLabel) => ({ value: floorLabel, label: floorLabel })),
      ...floorOptions
        .filter((floorLabel) => !floorOrder.includes(floorLabel))
        .sort((left, right) => left.localeCompare(right))
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

function handleFacilityTabChange(tabName) {
  if (activeFacilityTab.value === tabName) {
    return;
  }

  if (activeFacilityTab.value === 'venue') {
    handleVenueModalClose();
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
  if (activeFacilityTab.value === 'venue') {
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
    const response = await venueApi.listVenues({
      selectedDate: selectedVenueCalendarDate.value,
    });
    const venuePayload = response?.data?.venues || response?.venues || [];
    venuesList.value = Array.isArray(venuePayload)
      ? venuePayload.map(normalizeVenueRecord).filter(Boolean).filter((venueRecord) => !isVenueFloorPlaceholderRecord(venueRecord))
      : [];
  } catch (error) {
    venuesList.value = [];
    venueError.value = error?.response?.data?.errorMessage || 'Failed to load venue records.';
  } finally {
    loading.value = false;
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
    equipmentError.value = error?.response?.data?.errorMessage || 'Failed to load equipment.';
  } finally {
    equipmentLoading.value = false;
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
  fetchEquipment();
});

watch(
  () => route.query.tab,
  (tabValue) => {
    syncActiveFacilityTabFromRoute(tabValue);
  }
);

watch(activeFacilityTab, () => {
  showingFilterValue.value = 'all';
});

watch(showingFilterOptions, (nextOptions) => {
  if (!nextOptions.some((option) => option.value === showingFilterValue.value)) {
    showingFilterValue.value = 'all';
  }
});

watch(selectedVenueCalendarDate, () => {
  if (activeFacilityTab.value === 'venue') {
    fetchVenues();
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

function formatSummaryDate(dateValue) {
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date(`${dateValue}T00:00:00`));
}

function syncActiveFacilityTabFromRoute(tabValue) {
  const normalizedTab = String(tabValue || '').trim().toLowerCase();
  if (normalizedTab === 'equipment' || normalizedTab === 'venue') {
    activeFacilityTab.value = normalizedTab;
  }
}

function updateFacilityTabQuery(tabName) {
  const nextQuery = { ...route.query };

  if (tabName === 'equipment') {
    nextQuery.tab = 'equipment';
  } else {
    delete nextQuery.tab;
  }

  router.replace({ query: nextQuery });
}

function handleGoBack() {
  router.back();
}
</script>
