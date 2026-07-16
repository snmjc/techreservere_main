<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="admin-task-assignments-page">
      <div v-if="taskToastMessage" class="admin-task-assignments-toast">
        {{ taskToastMessage }}
      </div>

      <header class="admin-task-assignments-header">
        <div class="admin-task-assignments-header-copy">
          <p class="admin-task-assignments-kicker">Operations Workspace</p>
          <h1>Task Assignments</h1>
          <p>Monitor reservation workloads, review staff assignments, and keep every deployment on schedule.</p>
        </div>

        <div class="admin-task-assignments-header-actions">
          <button class="admin-task-assignments-secondary" type="button" :disabled="isLoading" @click="loadPageData">
            {{ isLoading ? 'Refreshing...' : 'Refresh' }}
          </button>
          <button class="admin-task-assignments-secondary" type="button" @click="openSmsTestModal">
            Test SMS
          </button>
          <button class="admin-task-assignments-secondary" type="button" @click="openTaskTemplateModal">
            Task Format
          </button>
          <button class="admin-task-assignments-primary" type="button" @click="openCreateModal">
            + Assign Task
          </button>
        </div>
      </header>

      <section class="admin-task-assignments-summary">
        <article
          v-for="card in summaryCards"
          :key="card.label"
          class="admin-task-summary-card"
          :class="`admin-task-summary-card--${card.tone}`"
        >
          <span class="admin-task-summary-card-icon">{{ card.icon }}</span>
          <div>
            <p>{{ card.label }}</p>
            <strong>{{ card.value }}</strong>
            <small>{{ card.caption }}</small>
          </div>
        </article>
      </section>

      <section class="admin-task-assignments-panel">
        <div class="admin-task-assignments-filters">
          <label class="admin-task-assignments-search">
            <span class="sr-only">Search tasks</span>
            <input
              v-model.trim="searchQuery"
              type="search"
              placeholder="Search reservation, facility, task, or personnel..."
            />
          </label>

          <label>
            <span>Status</span>
            <select v-model="statusFilter">
              <option value="all">All Status</option>
              <option v-for="option in statusOptions" :key="option" :value="option">{{ option }}</option>
              <option value="Overdue">Overdue</option>
            </select>
          </label>

          <label>
            <span>Personnel</span>
            <select v-model="personnelFilter">
              <option value="all">All Personnel</option>
              <option v-for="staff in staffFilterOptions" :key="staff.value" :value="staff.value">{{ staff.label }}</option>
            </select>
          </label>

          <label>
            <span>Sort</span>
            <select v-model="sortFilter">
              <option value="latest">Latest First</option>
              <option value="oldest">Oldest First</option>
              <option value="status">Status</option>
              <option value="reservation">Reservation ID</option>
            </select>
          </label>

          <label>
            <span>From</span>
            <input v-model="dateFilterStart" type="date" />
          </label>

          <label>
            <span>To</span>
            <input v-model="dateFilterEnd" type="date" />
          </label>
        </div>

        <p v-if="loadError" class="admin-task-assignments-error">{{ loadError }}</p>

        <div v-if="isLoading && tasks.length === 0" class="admin-task-assignments-state">
          Loading task assignments...
        </div>

        <div v-else-if="filteredTasks.length === 0" class="admin-task-assignments-state">
          No task assignments match the current filters.
        </div>

        <div v-else class="admin-task-assignments-table-wrap">
          <table class="admin-task-assignments-table">
            <thead>
              <tr>
                <th>Reservation ID</th>
                <th>Task Details</th>
                <th>Assigned To</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="task in paginatedTasks" :key="task.taskIdentifier">
                <td class="admin-task-cell-id">
                  <strong>{{ getReservationCode(task) }}</strong>
                  <small>Task #{{ task.taskIdentifier }}</small>
                </td>

                <td class="admin-task-cell-details">
                  <strong>{{ task.reservationLabel || formatReservationLabel(task.reservationIdentifier) }}</strong>
                  <span>{{ task.taskTitle }}</span>
                  <small>{{ task.taskDescription || task.taskType }}</small>
                  <small>{{ formatTaskSchedule(task) }}</small>
                </td>

                <td class="admin-task-cell-staff">
                  <strong>{{ formatStaffLabel(task) }}</strong>
                  <small>{{ task.assignedStaffRole || 'Technician' }}</small>
                </td>

                <td>
                  <span
                    class="admin-task-status-pill"
                    :class="`admin-task-status-pill--${getStatusTone(task)}`"
                  >
                    {{ getStatusLabel(task) }}
                  </span>
                </td>

                <td>
                  <div class="admin-task-actions">
                    <button type="button" class="admin-task-action admin-task-action--view" @click="openViewModal(task)">
                      View
                    </button>
                    <button type="button" class="admin-task-action admin-task-action--edit" @click="openUpdateModal(task)">
                      Update
                    </button>
                    <button
                      type="button"
                      class="admin-task-action admin-task-action--verify"
                      :disabled="!canVerifyTask(task)"
                      @click="openVerifyModal(task)"
                    >
                      Verify
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <footer class="admin-task-assignments-footer">
          <p>Showing {{ pageStart }} to {{ pageEnd }} of {{ filteredTasks.length }} task assignments</p>

          <div class="admin-task-assignments-pagination">
            <button type="button" :disabled="currentPage === 1" @click="currentPage -= 1">
              Prev
            </button>
            <span class="admin-task-assignments-pagination-label">Page {{ currentPage }} of {{ totalPages }}</span>
            <button
              v-for="pageNumber in visiblePageNumbers"
              :key="pageNumber"
              type="button"
              :class="{ 'is-active': pageNumber === currentPage }"
              @click="currentPage = pageNumber"
            >
              {{ pageNumber }}
            </button>
            <button type="button" :disabled="currentPage === totalPages" @click="currentPage += 1">
              Next
            </button>
          </div>
        </footer>

        <div class="admin-task-assignments-legend">
          <span><i class="legend-dot legend-dot--pending" />Pending</span>
          <span><i class="legend-dot legend-dot--progress" />In Progress</span>
          <span><i class="legend-dot legend-dot--done" />Completed</span>
          <span><i class="legend-dot legend-dot--overdue" />Overdue</span>
        </div>
      </section>
    </section>

    <div v-if="viewTask" class="admin-task-assignments-modal-overlay" @click.self="closeViewModal">
      <section class="admin-task-assignments-modal admin-task-assignments-modal--narrow">
        <header class="admin-task-assignments-modal-header">
          <div>
            <h2>View Task Assignment</h2>
            <p>Review the task assignment details.</p>
          </div>
          <button type="button" aria-label="Close" @click="closeViewModal">x</button>
        </header>

        <div class="admin-task-assignments-delete-summary">
          <p><strong>Reservation ID</strong><span>{{ getReservationCode(viewTask) }}</span></p>
          <p><strong>Reservation</strong><span>{{ viewTask.reservationLabel || formatReservationLabel(viewTask.reservationIdentifier) }}</span></p>
          <p><strong>Task Name</strong><span>{{ viewTask.taskTitle || 'N/A' }}</span></p>
          <p><strong>Description</strong><span>{{ viewTask.taskDescription || viewTask.taskType || 'N/A' }}</span></p>
          <p><strong>Assigned To</strong><span>{{ formatStaffLabel(viewTask) }}</span></p>
          <p><strong>Status</strong><span>{{ getStatusLabel(viewTask) }}</span></p>
          <p><strong>Schedule</strong><span>{{ formatTaskSchedule(viewTask) }}</span></p>
        </div>

        <footer class="admin-task-assignments-modal-actions">
          <button type="button" class="admin-task-assignments-secondary" @click="closeViewModal">Close</button>
        </footer>
      </section>
    </div>

    <div v-if="showTaskModal" class="admin-task-assignments-modal-overlay" @click.self="closeTaskModal">
      <section class="admin-task-assignments-modal">
        <header class="admin-task-assignments-modal-header">
          <div>
            <h2>{{ taskModalMode === 'create' ? 'Create Task Assignment' : 'Update Task Assignment' }}</h2>
            <p>Save the task details and staff assignment.</p>
          </div>
          <button type="button" aria-label="Close" @click="closeTaskModal">x</button>
        </header>

        <p v-if="modalError" class="admin-task-assignments-error">{{ modalError }}</p>

        <form class="admin-task-assignments-form" @submit.prevent="submitTaskForm">
          <label>
            <span>Task Name</span>
            <input v-model.trim="taskForm.taskTitle" type="text" maxlength="200" autocomplete="off" />
          </label>

          <label>
            <span>Description</span>
            <textarea v-model.trim="taskForm.taskDescription" rows="3"></textarea>
          </label>

          <label>
            <span>Task Type</span>
            <select v-model="taskForm.taskType">
              <option v-for="option in taskTypeOptions" :key="option" :value="option">{{ option }}</option>
            </select>
          </label>

          <label class="admin-task-assignments-reservation-field">
            <span>Reservation</span>
            <button
              type="button"
              class="admin-task-assignments-reservation-trigger"
              @click="openReservationSelectionModal"
            >
              <span>{{ selectedReservationOption ? selectedReservationOption.code : 'Select reservation' }}</span>
              <small v-if="selectedReservationOption">
                {{ [selectedReservationOption.organizationName, selectedReservationOption.scheduleLabel].filter(Boolean).join(' · ') }}
              </small>
              <small v-else>Open reservation directory</small>
            </button>
          </label>

          <label>
            <span>Assigned Staff</span>
            <button
              type="button"
              class="admin-task-assignments-staff-trigger"
              @click="openStaffSelectionModal"
            >
              <span>{{ selectedStaffOption ? selectedStaffOption.fullName : 'Select staff' }}</span>
              <small v-if="selectedStaffOption">{{ [selectedStaffOption.staffIdNumber, selectedStaffOption.position].filter(Boolean).join(' · ') }}</small>
              <small v-else>Open staff directory</small>
            </button>
          </label>

          <label>
            <span>Due Date</span>
            <input v-model="taskForm.dueDateTimestamp" type="datetime-local" />
          </label>

          <label>
            <span>Status</span>
            <select v-model="taskForm.taskStatus">
              <option v-for="option in statusOptions" :key="option" :value="option">{{ option }}</option>
            </select>
          </label>

          <section class="admin-task-assignments-override">
            <label class="admin-task-assignments-checkbox">
              <input v-model="taskForm.emergencyOverride" type="checkbox" />
              <span>Emergency override</span>
            </label>
            <div v-if="taskForm.emergencyOverride" class="admin-task-assignments-security-grid">
              <label>
                <span>Admin Email</span>
                <input v-model.trim="taskForm.confirmedAdminEmail" type="email" :placeholder="currentAdminEmail || 'admin@techreserve.edu.ph'" />
              </label>
              <label>
                <span>Admin Password</span>
                <input v-model="taskForm.confirmedAdminPassword" type="password" autocomplete="current-password" />
              </label>
            </div>
          </section>

          <footer class="admin-task-assignments-modal-actions">
            <p
              v-if="taskSubmissionFeedback.message"
              class="admin-task-assignments-submit-feedback"
              :class="`admin-task-assignments-submit-feedback--${taskSubmissionFeedback.tone}`"
            >
              {{ taskSubmissionFeedback.message }}
            </p>
            <button type="button" class="admin-task-assignments-secondary" :disabled="isSubmitting" @click="closeTaskModal">Cancel</button>
            <button type="submit" class="admin-task-assignments-primary" :disabled="isSubmitting">
              {{ isSubmitting ? (taskModalMode === 'create' ? 'Creating...' : 'Saving...') : (taskModalMode === 'create' ? 'Create' : 'Save Changes') }}
            </button>
          </footer>
        </form>
      </section>
    </div>

    <div v-if="showReservationSelectionModal" class="admin-task-assignments-modal-overlay" @click.self="closeReservationSelectionModal">
      <section class="admin-task-assignments-reservation-modal-shell">
        <section class="admin-task-assignments-modal admin-task-assignments-modal--reservation-picker">
          <header class="admin-task-assignments-modal-header">
            <div>
              <h2>Select Reservation</h2>
              <p>Choose the reservation record to link with this task assignment.</p>
            </div>
            <button type="button" aria-label="Close" @click="closeReservationSelectionModal">x</button>
          </header>

          <label class="admin-task-assignments-search admin-task-assignments-reservation-search">
            <span class="sr-only">Search reservations</span>
            <input
              v-model.trim="reservationSelectionQuery"
              type="search"
              placeholder="Search reservation, organization, or date..."
              autocomplete="off"
            />
          </label>

          <div class="admin-task-assignments-reservation-list" role="listbox" aria-label="Reservation options">
            <button
              v-for="reservation in paginatedReservationSelectionOptions"
              :key="reservation.value"
              type="button"
              class="admin-task-assignments-reservation-option"
              :class="{ 'is-selected': pendingSelectedReservationId === reservation.value }"
              @click="pendingSelectedReservationId = reservation.value"
            >
              <div>
                <strong>{{ reservation.code }}</strong>
                <span>{{ reservation.organizationName || 'No organization name' }}</span>
                <small>{{ reservation.scheduleLabel || 'No schedule recorded' }}</small>
              </div>
              <i aria-hidden="true">›</i>
            </button>
            <p v-if="paginatedReservationSelectionOptions.length === 0" class="admin-task-assignments-reservation-empty">
              No reservations match your search.
            </p>
          </div>

          <footer class="admin-task-assignments-reservation-footer">
            <p>Showing {{ reservationSelectionStart }} to {{ reservationSelectionEnd }} of {{ filteredReservationOptions.length }} results</p>
            <div class="admin-task-assignments-pagination">
              <button type="button" :disabled="reservationSelectionPage === 1" @click="reservationSelectionPage -= 1">Prev</button>
              <button
                v-for="pageNumber in visibleReservationSelectionPageNumbers"
                :key="pageNumber"
                type="button"
                :class="{ 'is-active': pageNumber === reservationSelectionPage }"
                @click="reservationSelectionPage = pageNumber"
              >
                {{ pageNumber }}
              </button>
              <button type="button" :disabled="reservationSelectionPage === reservationSelectionTotalPages" @click="reservationSelectionPage += 1">Next</button>
            </div>
          </footer>
        </section>

        <section class="admin-task-assignments-modal admin-task-assignments-modal--reservation-details">
          <header class="admin-task-assignments-modal-header">
            <div>
              <h2>Reservation Details</h2>
              <p>Review the linked request before applying it to the task assignment.</p>
            </div>
            <button type="button" aria-label="Close" @click="closeReservationSelectionModal">x</button>
          </header>

          <div v-if="reservationSelectionPreviewOption" class="admin-task-assignments-reservation-details">
            <p><strong>Reservation No.</strong><span>{{ reservationSelectionPreviewOption.code }}</span></p>
            <p><strong>Organization</strong><span>{{ reservationSelectionPreviewOption.organizationName || 'N/A' }}</span></p>
            <p><strong>Borrower</strong><span>{{ reservationSelectionPreviewOption.borrowerName || 'N/A' }}</span></p>
            <p><strong>Date</strong><span>{{ reservationSelectionPreviewOption.eventDateLabel || 'N/A' }}</span></p>
            <p><strong>Time</strong><span>{{ reservationSelectionPreviewOption.timeRangeLabel || 'N/A' }}</span></p>
            <p><strong>Venue</strong><span>{{ reservationSelectionPreviewOption.venueName || 'N/A' }}</span></p>
            <div class="admin-task-assignments-reservation-details-list">
              <strong>Equipment</strong>
              <ul v-if="reservationSelectionPreviewOption.equipmentList.length > 0">
                <li v-for="equipment in reservationSelectionPreviewOption.equipmentList" :key="equipment">{{ equipment }}</li>
              </ul>
              <span v-else>No equipment listed</span>
            </div>
            <p><strong>Participants</strong><span>{{ reservationSelectionPreviewOption.requestedQuantityLabel }}</span></p>
            <p><strong>Status</strong><span class="admin-task-reservation-status-pill">{{ reservationSelectionPreviewOption.statusLabel }}</span></p>
            <p><strong>Remarks</strong><span>{{ reservationSelectionPreviewOption.remarks || 'No remarks provided.' }}</span></p>
          </div>

          <div v-else class="admin-task-assignments-state">
            Select a reservation from the list to review its details.
          </div>

          <footer class="admin-task-assignments-modal-actions">
            <button type="button" class="admin-task-assignments-secondary" @click="pendingSelectedReservationId = ''">Back</button>
            <button
              type="button"
              class="admin-task-assignments-primary"
              :disabled="!reservationSelectionPreviewOption"
              @click="applySelectedReservation"
            >
              Select Reservation
            </button>
          </footer>
        </section>
      </section>
    </div>

    <div v-if="showSmsTestModal" class="admin-task-assignments-modal-overlay" @click.self="closeSmsTestModal">
      <section class="admin-task-assignments-modal admin-task-assignments-modal--narrow">
        <header class="admin-task-assignments-modal-header">
          <div>
            <h2>Send Test SMS</h2>
            <p>Send a direct TextBee test message without creating a task assignment.</p>
          </div>
          <button type="button" aria-label="Close" @click="closeSmsTestModal">x</button>
        </header>

        <p v-if="smsTestError" class="admin-task-assignments-error">{{ smsTestError }}</p>

        <form class="admin-task-assignments-form admin-task-sms-test-form" @submit.prevent="submitSmsTest">
          <div class="admin-task-sms-test-mode">
            <button
              type="button"
              :class="{ 'is-active': smsTestForm.messageMode === 'template' }"
              @click="smsTestForm.messageMode = 'template'"
            >
              Fill Template
            </button>
            <button
              type="button"
              :class="{ 'is-active': smsTestForm.messageMode === 'custom' }"
              @click="smsTestForm.messageMode = 'custom'"
            >
              Custom Message
            </button>
          </div>

          <label>
            <span>Recipient Number</span>
            <input
              v-model.trim="smsTestForm.phoneNumber"
              type="tel"
              inputmode="tel"
              autocomplete="tel"
              placeholder="09171234567"
            />
            <small>Use 09XXXXXXXXX or +639XXXXXXXXX.</small>
          </label>

          <template v-if="smsTestForm.messageMode === 'template'">
            <div class="admin-task-sms-template-grid">
              <label>
                <span>Assigned Staff</span>
                <input v-model.trim="smsTestForm.assignedStaff" type="text" placeholder="Alex Santos" />
              </label>

              <label>
                <span>Due Date</span>
                <input v-model.trim="smsTestForm.dueDate" type="text" placeholder="Jun 30, 2026 10:00 AM" />
              </label>

              <label>
                <span>Task Name</span>
                <input v-model.trim="smsTestForm.taskName" type="text" placeholder="Academic Preparation" />
              </label>

              <label>
                <span>Reservation Code</span>
                <input v-model.trim="smsTestForm.reservationCode" type="text" placeholder="TR-2026-010" />
              </label>

              <label class="admin-task-sms-template-purpose">
                <span>Reservation Purpose</span>
                <textarea
                  v-model.trim="smsTestForm.reservationPurpose"
                  rows="3"
                  maxlength="500"
                  placeholder="Prepare the requested equipment."
                ></textarea>
              </label>
            </div>

            <div class="admin-task-sms-preview">
              <strong>Message Preview</strong>
              <pre>{{ templateSmsMessage }}</pre>
            </div>
          </template>

          <label v-else>
            <span>Custom Message</span>
            <textarea
              v-model="smsTestForm.customMessage"
              rows="8"
              maxlength="1000"
              placeholder="Write the SMS message here."
            ></textarea>
            <small>{{ smsTestForm.customMessage.length }}/1000 characters</small>
          </label>

          <footer class="admin-task-assignments-modal-actions">
            <button type="button" class="admin-task-assignments-secondary" :disabled="isSendingTestSms" @click="closeSmsTestModal">
              Cancel
            </button>
            <button type="submit" class="admin-task-assignments-primary" :disabled="isSendingTestSms">
              {{ isSendingTestSms ? 'Sending...' : 'Send Test SMS' }}
            </button>
          </footer>
        </form>
      </section>
    </div>

    <div v-if="showTaskTemplateModal" class="admin-task-assignments-modal-overlay" @click.self="closeTaskTemplateModal">
      <section class="admin-task-assignments-modal admin-task-assignments-modal--wide">
        <header class="admin-task-assignments-modal-header">
          <div>
            <h2>Task Assignment Format</h2>
            <p>Server JSON format for automatic task assignments and staff SMS.</p>
          </div>
          <button type="button" aria-label="Close" @click="closeTaskTemplateModal">x</button>
        </header>

        <p v-if="taskTemplateError" class="admin-task-assignments-error">{{ taskTemplateError }}</p>

        <form class="admin-task-assignments-form admin-task-template-form" @submit.prevent="submitTaskTemplate">
          <label>
            <span>Task Name Format</span>
            <input v-model.trim="taskTemplateForm.taskTitle" type="text" maxlength="300" />
          </label>

          <label>
            <span>Task Type Format</span>
            <input v-model.trim="taskTemplateForm.taskType" type="text" maxlength="100" />
          </label>

          <label class="admin-task-template-full">
            <span>Description Format</span>
            <textarea v-model="taskTemplateForm.taskDescription" rows="4" maxlength="1000"></textarea>
          </label>

          <label class="admin-task-template-full">
            <span>SMS Format</span>
            <textarea v-model="taskTemplateForm.smsMessage" rows="8" maxlength="1000" readonly></textarea>
            <small>SMS content is hardcoded for delivery consistency.</small>
          </label>

          <section class="admin-task-template-preview">
            <div>
              <strong>Task Preview</strong>
              <p>{{ renderedTaskTemplatePreview.title }}</p>
              <small>{{ renderedTaskTemplatePreview.description }}</small>
            </div>
            <div>
              <strong>SMS Preview</strong>
              <pre>{{ renderedTaskTemplatePreview.sms }}</pre>
            </div>
          </section>

          <section class="admin-task-template-variables">
            <strong>Variables</strong>
            <div>
              <span v-for="(label, variableName) in taskTemplateVariables" :key="variableName">
                {{ formatTemplateVariable(variableName) }}
              </span>
            </div>
          </section>

          <footer class="admin-task-assignments-modal-actions">
            <button type="button" class="admin-task-assignments-secondary" :disabled="isSavingTaskTemplate" @click="closeTaskTemplateModal">
              Cancel
            </button>
            <button type="submit" class="admin-task-assignments-primary" :disabled="isSavingTaskTemplate">
              {{ isSavingTaskTemplate ? 'Saving...' : 'Save Format' }}
            </button>
          </footer>
        </form>
      </section>
    </div>

    <div v-if="verifyTask" class="admin-task-assignments-modal-overlay" @click.self="closeVerifyModal">
      <section class="admin-task-assignments-modal admin-task-assignments-modal--narrow">
        <header class="admin-task-assignments-modal-header">
          <div>
            <h2>Verify Task Assignment</h2>
            <p>Confirm this task assignment to mark it as completed.</p>
          </div>
          <button type="button" aria-label="Close" @click="closeVerifyModal">x</button>
        </header>

        <div class="admin-task-assignments-delete-summary">
          <p><strong>Task Name</strong><span>{{ verifyTask.taskTitle }}</span></p>
          <p><strong>Assigned Staff</strong><span>{{ formatStaffLabel(verifyTask) }}</span></p>
          <p><strong>Reservation</strong><span>{{ verifyTask.reservationLabel || formatReservationLabel(verifyTask.reservationIdentifier) }}</span></p>
          <p><strong>Status</strong><span>{{ getStatusLabel(verifyTask) }}</span></p>
        </div>

        <p v-if="modalError" class="admin-task-assignments-error">{{ modalError }}</p>

        <div class="admin-task-assignments-form">
          <label>
            <span>Admin Email</span>
            <input v-model.trim="deleteForm.confirmedAdminEmail" type="email" :placeholder="currentAdminEmail || 'admin@techreserve.edu.ph'" />
          </label>
          <label>
            <span>Admin Password</span>
            <input v-model="deleteForm.confirmedAdminPassword" type="password" autocomplete="current-password" />
          </label>
        </div>

        <footer class="admin-task-assignments-modal-actions">
          <button type="button" class="admin-task-assignments-secondary" :disabled="isSubmitting" @click="closeVerifyModal">Cancel</button>
          <button type="button" class="admin-task-assignments-primary" :disabled="!canVerifySubmit || isSubmitting" @click="confirmVerifyTask">
            {{ isSubmitting ? 'Verifying...' : 'Verify' }}
          </button>
        </footer>
      </section>
    </div>

    <div v-if="showStaffSelectionModal" class="admin-task-assignments-modal-overlay" @click.self="closeStaffSelectionModal">
      <section class="admin-task-assignments-modal admin-task-assignments-modal--staff-picker">
        <header class="admin-task-assignments-modal-header">
          <div>
            <h2>Select Staff</h2>
            <p>Choose the staff member who will handle this task assignment.</p>
          </div>
          <button type="button" aria-label="Close" @click="closeStaffSelectionModal">x</button>
        </header>

        <div class="admin-task-assignments-staff-toolbar">
          <label class="admin-task-assignments-search">
            <span class="sr-only">Search staff</span>
            <input
              v-model.trim="staffSelectionQuery"
              type="search"
              placeholder="Search by name or ID..."
            />
          </label>

          <label>
            <span class="sr-only">Filter staff by role</span>
            <select v-model="staffSelectionRoleFilter">
              <option value="all">All Staff</option>
              <option v-for="option in staffRoleFilterOptions" :key="option" :value="option">{{ option }}</option>
            </select>
          </label>
        </div>

        <div class="admin-task-assignments-staff-table-wrap">
          <table class="admin-task-assignments-staff-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Staff ID</th>
                <th>Position</th>
                <th>Select</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="staff in paginatedStaffSelectionOptions"
                :key="staff.value"
                :class="{ 'is-selected': pendingSelectedStaffId === staff.value }"
                @click="pendingSelectedStaffId = staff.value"
              >
                <td><strong>{{ staff.fullName }}</strong></td>
                <td>{{ staff.staffIdNumber || 'N/A' }}</td>
                <td>{{ staff.position || 'Staff' }}</td>
                <td>
                  <button
                    type="button"
                    class="admin-task-assignments-staff-row-action"
                    :class="{ 'is-selected': pendingSelectedStaffId === staff.value }"
                    @click.stop="pendingSelectedStaffId = staff.value"
                  >
                    {{ pendingSelectedStaffId === staff.value ? 'Selected' : 'Choose' }}
                  </button>
                </td>
              </tr>
              <tr v-if="paginatedStaffSelectionOptions.length === 0">
                <td colspan="4" class="admin-task-assignments-staff-empty">No staff matched your search.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <footer class="admin-task-assignments-staff-footer">
          <p>Showing {{ staffSelectionStart }} to {{ staffSelectionEnd }} of {{ filteredStaffSelectionOptions.length }} staff</p>
          <div class="admin-task-assignments-pagination">
            <button type="button" :disabled="staffSelectionPage === 1" @click="staffSelectionPage -= 1">Prev</button>
            <span class="admin-task-assignments-pagination-label">Page {{ staffSelectionPage }} of {{ staffSelectionTotalPages }}</span>
            <button type="button" :disabled="staffSelectionPage === staffSelectionTotalPages" @click="staffSelectionPage += 1">Next</button>
          </div>
          <div class="admin-task-assignments-modal-actions">
            <button type="button" class="admin-task-assignments-secondary" @click="closeStaffSelectionModal">Cancel</button>
            <button type="button" class="admin-task-assignments-primary" :disabled="!pendingSelectedStaffId" @click="applySelectedStaff">Select</button>
          </div>
        </footer>
      </section>
    </div>

    <DataRequestStatusFloater :items="taskAssignmentStatusItems" />
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import DataRequestStatusFloater from '@/shared/components/DataRequestStatusFloater.vue';
import '@/shared/components/adminSidebarLayout.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { apiUrl } from '@/shared/utils/apiBase.js';
import { buildAuthorizationHeaders, getStoredAuthToken } from '@/shared/utils/authToken.js';

const TASK_ASSIGNMENTS_CACHE_KEY = 'techreserve_task_assignments_cache';
const DEFAULT_TASK_TEMPLATE_VARIABLES = Object.freeze({
  activityType: 'Reservation activity type',
  purposeDescription: 'Reservation purpose',
  reservationPurpose: 'Reservation purpose',
  reservationCode: 'Reservation code',
  reservationIdentifier: 'Reservation ID',
  organizationName: 'Organization name',
  venueName: 'Venue or facility name',
  requestedQuantity: 'Requested quantity',
  eventDate: 'Event date',
  eventTime: 'Event time',
  eventDateTime: 'Event date and time',
  endDateTime: 'End date and time',
  assignedStaff: 'Assigned staff name',
  dueDate: 'Task due date',
  taskName: 'Task name',
  taskType: 'Task type',
});
const DEFAULT_TASK_ASSIGNMENT_TEMPLATE = Object.freeze({
  taskTitle: '{activityType} Preparation',
  taskDescription: '{purposeDescription}',
  taskType: 'Preparation',
  smsMessage: "TechReserve: hi! {assignedStaff}.\n\nYou have task on {dueDate}, {taskName}: {reservationCode}.\n{reservationPurpose}\n\nIf you can't please do contact the Facilities Office for changing of staff",
  variables: DEFAULT_TASK_TEMPLATE_VARIABLES,
});

const authStore = useAuthenticationStore();
const isLoading = ref(false);
const isSubmitting = ref(false);
const isSendingTestSms = ref(false);
const isSavingTaskTemplate = ref(false);
const loadError = ref('');
const modalError = ref('');
const smsTestError = ref('');
const taskTemplateError = ref('');
const taskToastMessage = ref('');
const taskSubmissionFeedback = reactive({
  message: '',
  tone: 'success',
});
const tasks = ref(readTaskAssignmentsCache());
const reservationOptions = ref([]);
const staffOptions = ref([]);
const taskAssignmentTemplate = ref({ ...DEFAULT_TASK_ASSIGNMENT_TEMPLATE });
const tasksDataState = ref(tasks.value.length > 0 ? 'cached' : 'idle');
const reservationsDataState = ref('idle');
const accountsDataState = ref('idle');
const taskTemplateDataState = ref('idle');
const showTaskModal = ref(false);
const showSmsTestModal = ref(false);
const showTaskTemplateModal = ref(false);
const showReservationSelectionModal = ref(false);
const taskModalMode = ref('create');
const editingTask = ref(null);
const viewTask = ref(null);
const verifyTask = ref(null);
const showStaffSelectionModal = ref(false);
const reservationSelectionQuery = ref('');
const reservationSelectionPage = ref(1);
const staffSelectionQuery = ref('');
const staffSelectionRoleFilter = ref('all');
const staffSelectionPage = ref(1);
const pendingSelectedReservationId = ref('');
const pendingSelectedStaffId = ref('');

const searchQuery = ref('');
const statusFilter = ref('all');
const personnelFilter = ref('all');
const sortFilter = ref('latest');
const dateFilterStart = ref('');
const dateFilterEnd = ref('');
const currentPage = ref(1);
const pageSize = 6;

const taskTypeOptions = ['Preparation', 'Deployment', 'Maintenance', 'Inspection', 'Return'];
const statusOptions = ['Pending', 'In Progress', 'Completed', 'Cancelled'];

const taskForm = reactive({
  taskTitle: '',
  taskDescription: '',
  taskType: 'Preparation',
  reservationIdentifier: '',
  assignedToAccountId: '',
  dueDateTimestamp: '',
  taskStatus: 'Pending',
  emergencyOverride: false,
  confirmedAdminEmail: '',
  confirmedAdminPassword: '',
});

const deleteForm = reactive({
  confirmedAdminEmail: '',
  confirmedAdminPassword: '',
});

const smsTestForm = reactive({
  phoneNumber: '',
  messageMode: 'template',
  assignedStaff: '',
  dueDate: '',
  taskName: '',
  reservationCode: '',
  reservationPurpose: '',
  customMessage: '',
});

const taskTemplateForm = reactive({
  taskTitle: DEFAULT_TASK_ASSIGNMENT_TEMPLATE.taskTitle,
  taskDescription: DEFAULT_TASK_ASSIGNMENT_TEMPLATE.taskDescription,
  taskType: DEFAULT_TASK_ASSIGNMENT_TEMPLATE.taskType,
  smsMessage: DEFAULT_TASK_ASSIGNMENT_TEMPLATE.smsMessage,
});

const templateSmsMessage = computed(() => {
  return renderTemplateString(taskAssignmentTemplate.value.smsMessage || DEFAULT_TASK_ASSIGNMENT_TEMPLATE.smsMessage, {
    assignedStaff: smsTestForm.assignedStaff.trim() || '<Assigned Staff>',
    dueDate: smsTestForm.dueDate.trim() || '<Due Date>',
    taskName: smsTestForm.taskName.trim() || '<Task Name>',
    reservationCode: smsTestForm.reservationCode.trim() || '<Reservation Code>',
    reservationPurpose: smsTestForm.reservationPurpose.trim() || '<Reservation Purpose>',
  });
});

const currentAdminEmail = computed(() => {
  const account = authStore.accountData || authStore.clerkAccountData || {};
  return String(account.emailAddress || account.email || '').trim();
});

const canVerifySubmit = computed(() => deleteForm.confirmedAdminEmail.trim() !== '' && deleteForm.confirmedAdminPassword.trim() !== '');
const taskAssignmentStatusItems = computed(() => [
  { key: 'tasks', label: 'Tasks', state: tasksDataState.value },
  { key: 'reservations', label: 'Reservation Options', state: reservationsDataState.value },
  { key: 'accounts', label: 'Staff Options', state: accountsDataState.value },
  { key: 'task-template', label: 'Task Format', state: taskTemplateDataState.value },
]);

const taskTemplateVariables = computed(() => taskAssignmentTemplate.value.variables || {});
const renderedTaskTemplatePreview = computed(() => {
  const sampleVariables = {
    activityType: 'Academic',
    purposeDescription: 'Prepare the requested equipment.',
    reservationPurpose: 'Prepare the requested equipment.',
    reservationCode: 'TR-2026-010',
    reservationIdentifier: '10',
    organizationName: 'Engineering Society',
    venueName: 'Room 401',
    requestedQuantity: '4',
    eventDate: 'Jun 30, 2026',
    eventTime: '10:00 AM',
    eventDateTime: 'Jun 30, 2026 10:00 AM',
    endDateTime: 'Jun 30, 2026 12:00 PM',
    assignedStaff: 'Alex Santos',
    dueDate: 'Jun 30, 2026 10:00 AM',
    taskName: 'Academic Preparation',
    taskType: 'Preparation',
  };

  return {
    title: renderTemplateString(taskTemplateForm.taskTitle, sampleVariables),
    description: renderTemplateString(taskTemplateForm.taskDescription, sampleVariables),
    sms: renderTemplateString(taskTemplateForm.smsMessage, sampleVariables),
  };
});

const summaryCards = computed(() => {
  const totalAssignments = tasks.value.length;
  const inProgress = tasks.value.filter((task) => normalizeStatus(task.taskStatus) === 'in_progress').length;
  const completed = tasks.value.filter((task) => normalizeStatus(task.taskStatus) === 'completed').length;
  const overdue = tasks.value.filter((task) => isTaskOverdue(task)).length;

  return [
    {
      label: 'Total Assignments',
      value: totalAssignments,
      caption: 'All recorded tasks',
      icon: '👥',
      tone: 'emerald',
    },
    {
      label: 'In Progress',
      value: inProgress,
      caption: 'Currently ongoing',
      icon: '🕘',
      tone: 'amber',
    },
    {
      label: 'Completed',
      value: completed,
      caption: 'Finished assignments',
      icon: '☑',
      tone: 'sky',
    },
    {
      label: 'Overdue',
      value: overdue,
      caption: 'Require immediate attention',
      icon: '❗',
      tone: 'rose',
    },
  ];
});

const staffFilterOptions = computed(() => tasks.value
  .map((task) => ({
    value: String(task.assignedToAccountId || ''),
    label: formatStaffLabel(task),
  }))
  .filter((staff) => staff.value && staff.label !== 'Unassigned')
  .filter((staff, index, list) => list.findIndex((entry) => entry.value === staff.value) === index)
  .sort((first, second) => first.label.localeCompare(second.label)));

const selectedStaffOption = computed(() => (
  staffOptions.value.find((staff) => staff.value === String(taskForm.assignedToAccountId || '')) || null
));
const selectedReservationOption = computed(() => (
  reservationOptions.value.find((reservation) => reservation.value === String(taskForm.reservationIdentifier || '')) || null
));

const filteredReservationOptions = computed(() => {
  const normalizedQuery = reservationSelectionQuery.value.trim().toLowerCase();

  if (!normalizedQuery) {
    return reservationOptions.value;
  }

  return reservationOptions.value.filter((reservation) => (
    [
      reservation.code,
      reservation.organizationName,
      reservation.scheduleLabel,
      reservation.label,
    ].filter(Boolean).join(' ').toLowerCase().includes(normalizedQuery)
  ));
});
const reservationSelectionTotalPages = computed(() => Math.max(1, Math.ceil(filteredReservationOptions.value.length / 5)));
const paginatedReservationSelectionOptions = computed(() => {
  const startIndex = (reservationSelectionPage.value - 1) * 5;
  return filteredReservationOptions.value.slice(startIndex, startIndex + 5);
});
const reservationSelectionStart = computed(() => filteredReservationOptions.value.length === 0 ? 0 : ((reservationSelectionPage.value - 1) * 5) + 1);
const reservationSelectionEnd = computed(() => Math.min(reservationSelectionPage.value * 5, filteredReservationOptions.value.length));
const reservationSelectionPreviewOption = computed(() => (
  reservationOptions.value.find((reservation) => reservation.value === String(pendingSelectedReservationId.value || '')) || null
));
const visibleReservationSelectionPageNumbers = computed(() => {
  const pageCount = reservationSelectionTotalPages.value;
  const current = reservationSelectionPage.value;
  const startPage = Math.max(1, current - 2);
  const endPage = Math.min(pageCount, startPage + 4);
  const adjustedStart = Math.max(1, endPage - 4);
  const pages = [];
  for (let pageNumber = adjustedStart; pageNumber <= endPage; pageNumber += 1) {
    pages.push(pageNumber);
  }
  return pages;
});

const staffRoleFilterOptions = computed(() => [...new Set(
  staffOptions.value.map((staff) => staff.position).filter(Boolean)
)].sort((first, second) => first.localeCompare(second)));

const filteredStaffSelectionOptions = computed(() => {
  const normalizedQuery = staffSelectionQuery.value.trim().toLowerCase();

  return staffOptions.value.filter((staff) => {
    if (staffSelectionRoleFilter.value !== 'all' && staff.position !== staffSelectionRoleFilter.value) {
      return false;
    }

    if (!normalizedQuery) {
      return true;
    }

    return [
      staff.fullName,
      staff.staffIdNumber,
      staff.position,
    ].filter(Boolean).join(' ').toLowerCase().includes(normalizedQuery);
  });
});

const staffSelectionTotalPages = computed(() => Math.max(1, Math.ceil(filteredStaffSelectionOptions.value.length / 8)));
const paginatedStaffSelectionOptions = computed(() => {
  const startIndex = (staffSelectionPage.value - 1) * 8;
  return filteredStaffSelectionOptions.value.slice(startIndex, startIndex + 8);
});
const staffSelectionStart = computed(() => filteredStaffSelectionOptions.value.length === 0 ? 0 : ((staffSelectionPage.value - 1) * 8) + 1);
const staffSelectionEnd = computed(() => Math.min(staffSelectionPage.value * 8, filteredStaffSelectionOptions.value.length));

const filteredTasks = computed(() => {
  const filteredList = tasks.value.filter((task) => {
    const query = searchQuery.value.trim().toLowerCase();
    const staffId = String(task.assignedToAccountId || '');
    const searchableText = [
      task.taskTitle,
      task.taskDescription,
      task.taskType,
      task.reservationLabel,
      getReservationCode(task),
      formatStaffLabel(task),
      task.assignedStaffRole,
    ].filter(Boolean).join(' ').toLowerCase();

    if (query && !searchableText.includes(query)) return false;

    if (statusFilter.value !== 'all') {
      if (statusFilter.value === 'Overdue') {
        if (!isTaskOverdue(task)) return false;
      } else if (normalizeStatus(task.taskStatus) !== normalizeStatus(statusFilter.value)) {
        return false;
      }
    }

    if (personnelFilter.value !== 'all' && personnelFilter.value !== staffId) return false;

    const taskDate = getComparableTaskDate(task);
    if (dateFilterStart.value && (!taskDate || taskDate < startOfDay(dateFilterStart.value))) return false;
    if (dateFilterEnd.value && (!taskDate || taskDate > endOfDay(dateFilterEnd.value))) return false;

    return true;
  });

  return filteredList.slice().sort((firstTask, secondTask) => compareTasks(firstTask, secondTask));
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredTasks.value.length / pageSize)));

const paginatedTasks = computed(() => {
  const start = (currentPage.value - 1) * pageSize;
  return filteredTasks.value.slice(start, start + pageSize);
});

const pageStart = computed(() => filteredTasks.value.length === 0 ? 0 : ((currentPage.value - 1) * pageSize) + 1);
const pageEnd = computed(() => Math.min(currentPage.value * pageSize, filteredTasks.value.length));

const visiblePageNumbers = computed(() => {
  const pages = [];
  for (let pageNumber = 1; pageNumber <= totalPages.value; pageNumber += 1) {
    pages.push(pageNumber);
  }
  return pages;
});

watch([searchQuery, statusFilter, personnelFilter, sortFilter, dateFilterStart, dateFilterEnd], () => {
  currentPage.value = 1;
});

watch(totalPages, (pageCount) => {
  if (currentPage.value > pageCount) {
    currentPage.value = pageCount;
  }
});

watch(reservationSelectionQuery, () => {
  reservationSelectionPage.value = 1;
});

watch(reservationSelectionTotalPages, (pageCount) => {
  if (reservationSelectionPage.value > pageCount) {
    reservationSelectionPage.value = pageCount;
  }
});

watch([staffSelectionQuery, staffSelectionRoleFilter], () => {
  staffSelectionPage.value = 1;
});

watch(staffSelectionTotalPages, (pageCount) => {
  if (staffSelectionPage.value > pageCount) {
    staffSelectionPage.value = pageCount;
  }
});

onMounted(() => {
  loadPageData();
});

async function loadPageData() {
  isLoading.value = true;
  loadError.value = '';
  tasksDataState.value = tasks.value.length > 0 ? 'cached-loading' : 'loading';
  reservationsDataState.value = reservationOptions.value.length > 0 ? 'cached-loading' : 'loading';
  accountsDataState.value = staffOptions.value.length > 0 ? 'cached-loading' : 'loading';
  taskTemplateDataState.value = taskAssignmentTemplate.value.updatedAt ? 'cached-loading' : 'loading';
  const [tasksResult, reservationsResult, accountsResult, taskTemplateResult] = await Promise.all([
    requestJson('/api/v1/tasks'),
    requestJson('/api/v1/reservations'),
    requestJson('/api/v1/accounts'),
    requestJson('/api/v1/tasks/template'),
  ]);

  if (!tasksResult.success) {
    loadError.value = tasksResult.error || 'Unable to load task assignments.';
    tasksDataState.value = tasks.value.length > 0 ? 'cached' : 'error';
  } else {
    tasks.value = tasksResult.data.tasks || [];
    writeTaskAssignmentsCache(tasks.value);
    tasksDataState.value = 'fresh';
  }

  if (reservationsResult.success) {
    reservationOptions.value = normalizeReservations(reservationsResult.data.reservations || []);
    reservationsDataState.value = 'fresh';
  } else {
    reservationsDataState.value = reservationOptions.value.length > 0 ? 'cached' : 'error';
  }

  if (accountsResult.success) {
    staffOptions.value = normalizeStaff(accountsResult.data.accounts || []);
    accountsDataState.value = 'fresh';
  } else {
    accountsDataState.value = staffOptions.value.length > 0 ? 'cached' : 'error';
  }

  if (taskTemplateResult.success) {
    applyTaskTemplate(taskTemplateResult.data.template || taskTemplateResult.data);
    taskTemplateDataState.value = 'fresh';
  } else {
    taskTemplateDataState.value = taskAssignmentTemplate.value.updatedAt ? 'cached' : 'error';
  }

  if (!loadError.value && (!reservationsResult.success || !accountsResult.success || !taskTemplateResult.success)) {
    showTaskToast(reservationsResult.error || accountsResult.error || taskTemplateResult.error || 'Some task form options could not be loaded.');
  }

  isLoading.value = false;
}

function openCreateModal() {
  resetTaskForm();
  taskModalMode.value = 'create';
  editingTask.value = null;
  reservationSelectionQuery.value = '';
  pendingSelectedReservationId.value = '';
  showTaskModal.value = true;
}

function openSmsTestModal() {
  smsTestError.value = '';
  smsTestForm.phoneNumber = '';
  smsTestForm.messageMode = 'template';
  smsTestForm.assignedStaff = 'Assigned Staff';
  smsTestForm.dueDate = 'Jul 16, 2026 03:00 PM';
  smsTestForm.taskName = 'Reservation Preparation';
  smsTestForm.reservationCode = 'TR-2026-001';
  smsTestForm.reservationPurpose = 'Prepare the requested venue and equipment.';
  smsTestForm.customMessage = '';
  showSmsTestModal.value = true;
}

function openTaskTemplateModal() {
  taskTemplateError.value = '';
  syncTaskTemplateForm(taskAssignmentTemplate.value);
  showTaskTemplateModal.value = true;
}

function openViewModal(task) {
  viewTask.value = task;
}

function openUpdateModal(task) {
  resetTaskForm();
  taskModalMode.value = 'update';
  editingTask.value = task;
  taskForm.taskTitle = task.taskTitle || '';
  taskForm.taskDescription = task.taskDescription || '';
  taskForm.taskType = task.taskType || 'Preparation';
  taskForm.reservationIdentifier = task.reservationIdentifier ? String(task.reservationIdentifier) : '';
  taskForm.assignedToAccountId = task.assignedToAccountId ? String(task.assignedToAccountId) : '';
  taskForm.dueDateTimestamp = toDateTimeLocal(task.dueDateTimestamp);
  taskForm.taskStatus = task.taskStatus || 'Pending';
  reservationSelectionQuery.value = '';
  pendingSelectedReservationId.value = taskForm.reservationIdentifier;
  showTaskModal.value = true;
}

function openReservationSelectionModal() {
  reservationSelectionQuery.value = '';
  reservationSelectionPage.value = 1;
  pendingSelectedReservationId.value = String(taskForm.reservationIdentifier || filteredReservationOptions.value[0]?.value || '');
  showReservationSelectionModal.value = true;
}

function openStaffSelectionModal() {
  pendingSelectedStaffId.value = String(taskForm.assignedToAccountId || '');
  staffSelectionQuery.value = '';
  staffSelectionRoleFilter.value = 'all';
  staffSelectionPage.value = 1;
  showStaffSelectionModal.value = true;
}

function openVerifyModal(task) {
  if (!canVerifyTask(task)) {
    return;
  }

  verifyTask.value = task;
  resetDeleteForm();
  modalError.value = '';
}

function closeViewModal() {
  viewTask.value = null;
}

function closeTaskModal() {
  showTaskModal.value = false;
  editingTask.value = null;
  showReservationSelectionModal.value = false;
  resetTaskForm();
  modalError.value = '';
}

function closeReservationSelectionModal() {
  showReservationSelectionModal.value = false;
  pendingSelectedReservationId.value = String(taskForm.reservationIdentifier || '');
}

function closeStaffSelectionModal() {
  showStaffSelectionModal.value = false;
}

function applySelectedReservation() {
  if (!pendingSelectedReservationId.value) {
    return;
  }

  taskForm.reservationIdentifier = pendingSelectedReservationId.value;
  showReservationSelectionModal.value = false;
}

function applySelectedStaff() {
  taskForm.assignedToAccountId = pendingSelectedStaffId.value;
  showStaffSelectionModal.value = false;
}

function closeSmsTestModal() {
  if (isSendingTestSms.value) return;
  showSmsTestModal.value = false;
  smsTestError.value = '';
}

function closeTaskTemplateModal() {
  if (isSavingTaskTemplate.value) return;
  showTaskTemplateModal.value = false;
  taskTemplateError.value = '';
}

function closeVerifyModal() {
  verifyTask.value = null;
  resetDeleteForm();
  modalError.value = '';
}

async function submitTaskForm() {
  if (isSubmitting.value) return;

  const validationError = validateTaskForm();
  if (validationError) {
    modalError.value = validationError;
    return;
  }

  isSubmitting.value = true;
  modalError.value = '';
  resetTaskSubmissionFeedback();
  const payload = buildTaskPayload();
  const endpoint = taskModalMode.value === 'create'
    ? '/api/v1/tasks'
    : `/api/v1/tasks/${editingTask.value.taskIdentifier}`;
  const method = taskModalMode.value === 'create' ? 'POST' : 'PUT';
  const result = await requestJson(endpoint, { method, body: JSON.stringify(payload) });
  isSubmitting.value = false;

  if (!result.success) {
    modalError.value = result.error || 'Unable to save task assignment.';
    return;
  }

  await loadPageData();
  const smsExpected = taskModalMode.value === 'create'
    ? Boolean(payload.assignedToAccountId)
    : Boolean(payload.assignedToAccountId)
      && Number(payload.assignedToAccountId) !== Number(editingTask.value?.assignedToAccountId || 0);
  const feedback = buildTaskSubmissionFeedback(result.data?.warning, smsExpected);
  taskSubmissionFeedback.message = feedback.message;
  taskSubmissionFeedback.tone = feedback.tone;
  showTaskToast(feedback.message);
  window.clearTimeout(submitTaskForm.closeTimeoutId);
  submitTaskForm.closeTimeoutId = window.setTimeout(() => {
    if (taskSubmissionFeedback.message === feedback.message) {
      closeTaskModal();
    }
  }, feedback.tone === 'warning' ? 2600 : 1800);
}

async function submitSmsTest() {
  if (isSendingTestSms.value) return;

  if (smsTestForm.phoneNumber.trim() === '') {
    smsTestError.value = 'Recipient number is required.';
    return;
  }

  if (smsTestForm.messageMode !== 'template' && smsTestForm.customMessage.trim() === '') {
    smsTestError.value = 'Custom message is required.';
    return;
  }

  const message = smsTestForm.messageMode === 'template'
    ? templateSmsMessage.value
    : smsTestForm.customMessage.trim();

  isSendingTestSms.value = true;
  smsTestError.value = '';
  const result = await requestJson('/api/v1/tasks/sms/test', {
    method: 'POST',
    body: JSON.stringify({
      phoneNumber: smsTestForm.phoneNumber.trim(),
      message,
    }),
  });
  isSendingTestSms.value = false;

  if (!result.success) {
    smsTestError.value = result.error || 'Unable to send the test SMS.';
    return;
  }

  showSmsTestModal.value = false;
  showTaskToast(`Test SMS submitted to ${result.data?.delivery?.recipient || smsTestForm.phoneNumber}.`);
}

async function submitTaskTemplate() {
  if (isSavingTaskTemplate.value) return;

  const validationError = validateTaskTemplateForm();
  if (validationError) {
    taskTemplateError.value = validationError;
    return;
  }

  isSavingTaskTemplate.value = true;
  taskTemplateError.value = '';
  taskTemplateDataState.value = 'loading';
  const result = await requestJson('/api/v1/tasks/template', {
    method: 'PUT',
    body: JSON.stringify({
      taskTitle: taskTemplateForm.taskTitle.trim(),
      taskDescription: taskTemplateForm.taskDescription.trim(),
      taskType: taskTemplateForm.taskType.trim(),
      smsMessage: taskTemplateForm.smsMessage.trim(),
    }),
  });
  isSavingTaskTemplate.value = false;

  if (!result.success) {
    taskTemplateError.value = result.error || 'Unable to save task assignment format.';
    taskTemplateDataState.value = taskAssignmentTemplate.value.updatedAt ? 'cached' : 'error';
    return;
  }

  applyTaskTemplate(result.data.template || result.data);
  taskTemplateDataState.value = 'fresh';
  showTaskToast('Task assignment format saved.');
  closeTaskTemplateModal();
}

async function confirmVerifyTask() {
  if (!verifyTask.value || isSubmitting.value || !canVerifySubmit.value) return;

  isSubmitting.value = true;
  modalError.value = '';

  if (normalizeEmailForConfirmation(deleteForm.confirmedAdminEmail) !== normalizeEmailForConfirmation(currentAdminEmail.value)) {
    isSubmitting.value = false;
    modalError.value = 'Please enter the exact admin email before verifying this task.';
    return;
  }

  const result = await requestJson(`/api/v1/tasks/${verifyTask.value.taskIdentifier}/status`, {
    method: 'PUT',
    body: JSON.stringify({
      taskStatus: 'Completed',
    }),
  });
  isSubmitting.value = false;

  if (!result.success) {
    modalError.value = result.error || 'Unable to verify task assignment.';
    return;
  }

  closeVerifyModal();
  await loadPageData();
  showTaskToast('Task assignment verified successfully.');
}

function validateTaskForm() {
  if (taskForm.taskTitle.trim() === '') return 'Task name is required.';
  if (taskForm.taskTitle.trim().length > 200) return 'Task name must not exceed 200 characters.';
  if (taskForm.taskType.trim() === '') return 'Task type is required.';
  if (!statusOptions.includes(taskForm.taskStatus)) return 'Please select a valid status.';

  const missingFields = [];
  if (!taskForm.reservationIdentifier) missingFields.push('reservation');
  if (!taskForm.assignedToAccountId) missingFields.push('assigned staff');
  if (!taskForm.dueDateTimestamp) missingFields.push('due date');

  if (missingFields.length > 0 && !taskForm.emergencyOverride) {
    return `Emergency override is required when saving without ${missingFields.join(', ')}.`;
  }

  if (taskForm.emergencyOverride) {
    if (taskForm.confirmedAdminEmail.trim() === '') return 'Admin email is required for emergency override.';
    if (taskForm.confirmedAdminPassword.trim() === '') return 'Admin password is required for emergency override.';
  }

  return '';
}

function buildTaskPayload() {
  return {
    taskTitle: taskForm.taskTitle.trim(),
    taskDescription: taskForm.taskDescription.trim(),
    taskType: taskForm.taskType,
    reservationIdentifier: taskForm.reservationIdentifier ? Number(taskForm.reservationIdentifier) : null,
    assignedToAccountId: taskForm.assignedToAccountId ? Number(taskForm.assignedToAccountId) : null,
    dueDateTimestamp: taskForm.dueDateTimestamp || null,
    taskStatus: taskForm.taskStatus,
    emergencyOverride: taskForm.emergencyOverride,
    confirmedAdminEmail: normalizeEmailForConfirmation(taskForm.confirmedAdminEmail),
    confirmedAdminPassword: taskForm.confirmedAdminPassword,
  };
}

function applyTaskTemplate(template) {
  const normalizedTemplate = {
    ...DEFAULT_TASK_ASSIGNMENT_TEMPLATE,
    ...(template && typeof template === 'object' ? template : {}),
  };
  taskAssignmentTemplate.value = normalizedTemplate;
  syncTaskTemplateForm(normalizedTemplate);
}

function syncTaskTemplateForm(template) {
  taskTemplateForm.taskTitle = template?.taskTitle || DEFAULT_TASK_ASSIGNMENT_TEMPLATE.taskTitle;
  taskTemplateForm.taskDescription = template?.taskDescription || DEFAULT_TASK_ASSIGNMENT_TEMPLATE.taskDescription;
  taskTemplateForm.taskType = template?.taskType || DEFAULT_TASK_ASSIGNMENT_TEMPLATE.taskType;
  taskTemplateForm.smsMessage = template?.smsMessage || DEFAULT_TASK_ASSIGNMENT_TEMPLATE.smsMessage;
}

function validateTaskTemplateForm() {
  if (taskTemplateForm.taskTitle.trim() === '') return 'Task name format is required.';
  if (taskTemplateForm.taskType.trim() === '') return 'Task type format is required.';
  if (taskTemplateForm.taskTitle.length > 300) return 'Task name format must not exceed 300 characters.';
  if (taskTemplateForm.taskDescription.length > 1000) return 'Description format must not exceed 1000 characters.';
  if (taskTemplateForm.smsMessage.length > 1000) return 'SMS format must not exceed 1000 characters.';

  const allowedVariables = new Set(Object.keys(taskTemplateVariables.value));
  const unknownVariables = [
    taskTemplateForm.taskTitle,
    taskTemplateForm.taskDescription,
    taskTemplateForm.taskType,
    taskTemplateForm.smsMessage,
  ].flatMap((template) => extractTemplateVariables(template))
    .filter((variableName, index, list) => !allowedVariables.has(variableName) && list.indexOf(variableName) === index);

  if (unknownVariables.length > 0) {
    return `Unknown variable(s): ${unknownVariables.map((variableName) => `{${variableName}}`).join(', ')}.`;
  }

  return '';
}

function extractTemplateVariables(template) {
  return [...String(template || '').matchAll(/\{([a-zA-Z][a-zA-Z0-9_]*)\}/g)]
    .map((match) => match[1]);
}

function formatTemplateVariable(variableName) {
  return `{${variableName}}`;
}

function renderTemplateString(template, variables) {
  return String(template || '').replace(/\{([a-zA-Z][a-zA-Z0-9_]*)\}/g, (_, variableName) => (
    variables[variableName] ?? ''
  )).trim();
}

async function requestJson(path, options = {}) {
  try {
    const response = await fetch(apiUrl(path), {
      method: options.method || 'GET',
      headers: buildHeaders(Boolean(options.body)),
      body: options.body,
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
      return { success: false, error: result.errorMessage || result.message || 'Request failed.' };
    }
    return { success: true, data: result.data || result };
  } catch (error) {
    return { success: false, error: error?.message || 'Request failed.' };
  }
}

function buildHeaders(includeJson = false) {
  const authToken = authStore.authToken || getStoredAuthToken();
  return {
    ...(includeJson ? { 'Content-Type': 'application/json' } : {}),
    ...buildAuthorizationHeaders(authToken),
  };
}

function readTaskAssignmentsCache() {
  if (typeof window === 'undefined') {
    return [];
  }

  try {
    const cachedValue = window.sessionStorage.getItem(TASK_ASSIGNMENTS_CACHE_KEY);
    const parsedValue = cachedValue ? JSON.parse(cachedValue) : [];
    return Array.isArray(parsedValue) ? parsedValue : [];
  } catch {
    return [];
  }
}

function writeTaskAssignmentsCache(records) {
  if (typeof window === 'undefined') {
    return;
  }

  try {
    window.sessionStorage.setItem(TASK_ASSIGNMENTS_CACHE_KEY, JSON.stringify(Array.isArray(records) ? records : []));
  } catch {
    // Cache writes are best-effort only.
  }
}

function normalizeReservations(reservations) {
  return reservations.map((reservation) => ({
    value: String(reservation.reservationIdentifier || reservation.reservation_identifier),
    code: reservation.reservationCode || reservation.reservation_code || `#${reservation.reservationIdentifier || reservation.reservation_identifier}`,
    organizationName: reservation.organizationName || reservation.organization_name || '',
    scheduleLabel: reservation.eventDateTime ? formatDateTime(reservation.eventDateTime) : '',
    borrowerName: reservation.borrowerFullName || reservation.borrower_full_name || 'N/A',
    eventDateLabel: formatReservationEventDate(reservation.eventDateTime),
    timeRangeLabel: reservation.activityTimeRange || buildReservationTimeRange(reservation.eventDateTime, reservation.endDateTime),
    venueName: reservation.venueName || reservation.venue_name || 'N/A',
    equipmentList: normalizeReservationEquipmentList(reservation.requestedEquipmentList || reservation.requested_equipment_list),
    requestedQuantityLabel: String(reservation.requestedQuantity ?? reservation.requested_quantity ?? 0),
    statusLabel: reservation.currentStatus || reservation.current_status || 'Unknown',
    remarks: reservation.borrowerRemarks || reservation.borrower_remarks || reservation.purposeDescription || reservation.purpose_description || '',
    label: [
      reservation.reservationCode || reservation.reservation_code || `#${reservation.reservationIdentifier || reservation.reservation_identifier}`,
      reservation.organizationName || reservation.organization_name,
      reservation.eventDateTime ? formatDateTime(reservation.eventDateTime) : '',
    ].filter(Boolean).join(' - '),
  })).filter((reservation) => reservation.value);
}

function normalizeStaff(accounts) {
  return accounts
    .filter((account) => resolveAccountType(account) === 'Employee')
    .map((account) => ({
      value: String(account.accountIdentifier || account.account_identifier),
      fullName: `${account.firstName || account.first_name || ''} ${account.lastName || account.last_name || ''}`.trim(),
      staffIdNumber: resolveStaffIdNumber(account),
      position: account.roleLabel || account.department || 'Staff',
      label: [
        `${account.firstName || account.first_name || ''} ${account.lastName || account.last_name || ''}`.trim(),
        resolveStaffIdNumber(account),
        account.roleLabel || account.department,
      ].filter(Boolean).join(' - '),
    }))
    .filter((staff) => staff.value);
}

function resolveStaffIdNumber(account) {
  return account.staffEmployeeIdNumber
    || account.staff_employee_id_number
    || account.rawIdNumber
    || account.idNumber
    || account.id_number
    || '';
}

function resolveAccountType(account) {
  const role = String(account.roleDesignation || account.role_designation || '').toUpperCase();
  const department = String(account.department || '').toLowerCase();
  if (role.includes('STAFF') || department.includes('staff') || department.includes('maintenance') || department.includes('support')) return 'Employee';
  return account.accountType || account.account_type || '';
}

function normalizeReservationEquipmentList(equipmentList) {
  if (!Array.isArray(equipmentList)) {
    return [];
  }

  return equipmentList.map((equipment) => {
    const itemName = typeof equipment === 'string'
      ? equipment
      : equipment?.name || equipment?.equipmentName || equipment?.equipment_name || 'Equipment';
    const quantity = Number(equipment?.quantity ?? equipment?.selectedQuantity ?? equipment?.selected_quantity ?? 1) || 1;
    return quantity > 1 ? `${itemName} (${quantity})` : String(itemName);
  });
}

function formatReservationEventDate(value) {
  if (!value) return 'N/A';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return 'N/A';
  return new Intl.DateTimeFormat('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  }).format(date);
}

function buildReservationTimeRange(startValue, endValue) {
  const startLabel = formatReservationClockTime(startValue);
  const endLabel = formatReservationClockTime(endValue);

  if (startLabel && endLabel) {
    return `${startLabel} - ${endLabel}`;
  }

  return startLabel || endLabel || 'N/A';
}

function formatReservationClockTime(value) {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  return new Intl.DateTimeFormat('en-US', {
    hour: 'numeric',
    minute: '2-digit',
  }).format(date);
}

function formatStaffLabel(task) {
  if (task.assignedStaffName) {
    return [task.assignedStaffName, task.assignedStaffIdNumber].filter(Boolean).join(' - ');
  }
  return task.assignedToAccountId ? `Account #${task.assignedToAccountId}` : 'Unassigned';
}

function formatReservationLabel(reservationIdentifier) {
  return reservationIdentifier ? `Reservation #${reservationIdentifier}` : 'No linked reservation';
}

function getReservationCode(task) {
  const label = String(task.reservationLabel || '').trim();
  if (label.includes(' - ')) {
    return label.split(' - ')[0];
  }
  if (label) return label;
  return task.reservationIdentifier ? `RES-${String(task.reservationIdentifier).padStart(4, '0')}` : `TASK-${String(task.taskIdentifier).padStart(4, '0')}`;
}

function formatTaskSchedule(task) {
  const rawValue = task.dueDateTimestamp || task.createdTimestamp;
  if (!rawValue) return 'No schedule set';
  return `${formatScheduleDate(rawValue)} - ${formatScheduleTime(rawValue)}`;
}

function normalizeStatus(status) {
  return String(status || '').trim().toLowerCase().replace(/\s+/g, '_');
}

function isTaskOverdue(task) {
  const dueDate = task.dueDateTimestamp ? new Date(task.dueDateTimestamp) : null;
  if (!dueDate || Number.isNaN(dueDate.getTime())) return false;
  return dueDate.getTime() < Date.now() && !['completed', 'cancelled'].includes(normalizeStatus(task.taskStatus));
}

function getStatusLabel(task) {
  return isTaskOverdue(task) ? 'Overdue' : (task.taskStatus || 'Pending');
}

function getStatusTone(task) {
  if (isTaskOverdue(task)) return 'overdue';

  const status = normalizeStatus(task.taskStatus);
  if (status === 'completed') return 'completed';
  if (status === 'in_progress') return 'progress';
  if (status === 'cancelled') return 'neutral';
  return 'pending';
}

function canVerifyTask(task) {
  if (isTaskOverdue(task)) return false;
  const status = normalizeStatus(task.taskStatus);
  return status === 'pending' || status === 'in_progress';
}

function compareTasks(firstTask, secondTask) {
  if (sortFilter.value === 'reservation') {
    return getReservationCode(firstTask).localeCompare(getReservationCode(secondTask));
  }

  if (sortFilter.value === 'status') {
    return getStatusLabel(firstTask).localeCompare(getStatusLabel(secondTask));
  }

  const firstDate = getComparableTaskDate(firstTask)?.getTime() || 0;
  const secondDate = getComparableTaskDate(secondTask)?.getTime() || 0;
  return sortFilter.value === 'oldest'
    ? firstDate - secondDate
    : secondDate - firstDate;
}

function getComparableTaskDate(task) {
  const rawValue = task.dueDateTimestamp || task.createdTimestamp;
  if (!rawValue) return null;
  const parsedDate = new Date(rawValue);
  if (Number.isNaN(parsedDate.getTime())) return null;
  return parsedDate;
}

function startOfDay(dateValue) {
  return new Date(`${dateValue}T00:00:00`);
}

function endOfDay(dateValue) {
  return new Date(`${dateValue}T23:59:59.999`);
}

function formatDateTime(value) {
  if (!value) return 'N/A';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
}

function formatScheduleDate(value) {
  if (!value) return 'No schedule set';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('en-US', {
    month: 'long',
    day: '2-digit',
    year: 'numeric',
  }).format(date);
}

function formatScheduleTime(value) {
  if (!value) return 'No time';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return 'No time';
  return new Intl.DateTimeFormat('en-US', {
    hour: 'numeric',
    minute: '2-digit',
  }).format(date);
}

function toDateTimeLocal(value) {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  const offsetDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
  return offsetDate.toISOString().slice(0, 16);
}

function normalizeEmailForConfirmation(value) {
  return String(value || '').replace(/[\u200B-\u200D\uFEFF]/g, '').replace(/\s+/g, '').trim().toLowerCase();
}

function showTaskToast(message) {
  taskToastMessage.value = message;
  window.clearTimeout(showTaskToast.timeoutId);
  showTaskToast.timeoutId = window.setTimeout(() => {
    if (taskToastMessage.value === message) {
      taskToastMessage.value = '';
    }
  }, 3200);
}

showTaskToast.timeoutId = null;
submitTaskForm.closeTimeoutId = null;

function buildTaskSubmissionFeedback(warning, smsExpected = true) {
  const normalizedWarning = typeof warning === 'string' ? warning.trim() : '';
  if (normalizedWarning !== '') {
    return {
      message: normalizedWarning,
      tone: 'warning',
    };
  }

  return {
    message: smsExpected
      ? 'Task assignment saved and SMS sent to assigned staff.'
      : 'Task assignment saved. No SMS was needed because the assigned staff did not change.',
    tone: 'success',
  };
}

function resetTaskSubmissionFeedback() {
  taskSubmissionFeedback.message = '';
  taskSubmissionFeedback.tone = 'success';
}

function resetTaskForm() {
  window.clearTimeout(submitTaskForm.closeTimeoutId);
  taskForm.taskTitle = '';
  taskForm.taskDescription = '';
  taskForm.taskType = 'Preparation';
  taskForm.reservationIdentifier = '';
  taskForm.assignedToAccountId = '';
  taskForm.dueDateTimestamp = '';
  taskForm.taskStatus = 'Pending';
  taskForm.emergencyOverride = false;
  taskForm.confirmedAdminEmail = '';
  taskForm.confirmedAdminPassword = '';
  reservationSelectionQuery.value = '';
  reservationSelectionPage.value = 1;
  pendingSelectedReservationId.value = '';
  pendingSelectedStaffId.value = '';
  showReservationSelectionModal.value = false;
  showStaffSelectionModal.value = false;
  resetTaskSubmissionFeedback();
}

function resetDeleteForm() {
  deleteForm.confirmedAdminEmail = '';
  deleteForm.confirmedAdminPassword = '';
}
</script>

<style scoped>
@import './css/AdminTaskAssignments.css';
</style>

