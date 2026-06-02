<template>
  <section v-if="isVisible" class="employee-work-log-section" aria-labelledby="employee-work-log-title">
    <div class="employee-work-log-header">
      <div>
        <h2 id="employee-work-log-title">Work Log Sheet</h2>
        <p>Tasks assigned to your employee account.</p>
      </div>
      <button class="btn btn-secondary" type="button" :disabled="loading" @click="$emit('refresh')">
        {{ loading ? 'Refreshing...' : 'Refresh' }}
      </button>
    </div>

    <p v-if="loading" class="settings-alert">Loading work logs...</p>
    <p v-else-if="error" class="settings-alert error">{{ error }}</p>
    <p v-else-if="workLogs.length === 0" class="employee-work-log-empty">No work logs found for this employee.</p>

    <div v-else class="employee-work-log-list">
      <article v-for="log in workLogs" :key="getLogKey(log)" class="employee-work-log-card">
        <button
          class="employee-work-log-summary"
          type="button"
          :aria-expanded="isExpanded(log)"
          @click="toggleLog(log)"
        >
          <span class="employee-work-log-main">
            <strong>{{ log.taskName || 'Untitled task' }}</strong>
            <span>{{ formatDateTime(log.taskDateTime) }}</span>
          </span>
          <span class="employee-work-log-status">{{ log.status || 'No status' }}</span>
        </button>

        <div v-if="isExpanded(log)" class="employee-work-log-details">
          <div class="employee-work-log-detail-group">
            <h3>Reservation Details</h3>
            <dl>
              <div>
                <dt>Reservation</dt>
                <dd>{{ reservationLabel(log.reservationDetails) }}</dd>
              </div>
              <div>
                <dt>Organization</dt>
                <dd>{{ log.reservationDetails?.organizationName || 'N/A' }}</dd>
              </div>
              <div>
                <dt>Event Date and Time</dt>
                <dd>{{ formatDateTime(log.reservationDetails?.eventDateTime) }}</dd>
              </div>
              <div>
                <dt>Purpose</dt>
                <dd>{{ log.reservationDetails?.purposeDescription || 'N/A' }}</dd>
              </div>
              <div>
                <dt>Equipment</dt>
                <dd>{{ formatEquipmentList(log.reservationDetails?.requestedEquipmentList) }}</dd>
              </div>
            </dl>
          </div>

          <div class="employee-work-log-detail-group">
            <h3>Assignment Details</h3>
            <dl>
              <div>
                <dt>Assigned Task</dt>
                <dd>{{ log.assignments?.assignedTask || log.taskName || 'N/A' }}</dd>
              </div>
              <div>
                <dt>Task Type</dt>
                <dd>{{ log.taskType || log.assignments?.assignmentType || 'N/A' }}</dd>
              </div>
              <div>
                <dt>Description</dt>
                <dd>{{ log.taskDescription || log.fullTaskInformation?.description || 'N/A' }}</dd>
              </div>
              <div>
                <dt>Created</dt>
                <dd>{{ formatDateTime(log.createdTimestamp) }}</dd>
              </div>
              <div>
                <dt>Updated</dt>
                <dd>{{ formatDateTime(log.updatedTimestamp) }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
  isVisible: {
    type: Boolean,
    default: false,
  },
  workLogs: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
});

defineEmits(['refresh']);

const expandedLogKeys = ref(new Set());

function toggleLog(log) {
  const key = getLogKey(log);
  const nextKeys = new Set(expandedLogKeys.value);

  if (nextKeys.has(key)) {
    nextKeys.delete(key);
  } else {
    nextKeys.add(key);
  }

  expandedLogKeys.value = nextKeys;
}

function isExpanded(log) {
  return expandedLogKeys.value.has(getLogKey(log));
}

function getLogKey(log) {
  return log.historyLogId || log.taskAssignmentId || log.taskIdentifier;
}

function reservationLabel(reservationDetails) {
  if (!reservationDetails) return 'No linked reservation';

  return reservationDetails.reservationCode
    || `Reservation #${reservationDetails.reservationIdentifier || 'N/A'}`;
}

function formatEquipmentList(equipmentList) {
  if (!Array.isArray(equipmentList) || equipmentList.length === 0) {
    return 'N/A';
  }

  return equipmentList.join(', ');
}

function formatDateTime(value) {
  if (!value) return 'N/A';

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return 'N/A';

  return new Intl.DateTimeFormat('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
}
</script>
