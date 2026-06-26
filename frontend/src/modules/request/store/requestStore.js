import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import dashboardApi from '@/modules/dashboard/services/dashboardApi.js';
import reservationApi from '@/modules/reservation/services/reservationApi.js';
import taskApi from '@/modules/task/services/taskApi.js';
import { useNotificationStore } from '@/modules/notification/store/notificationStore.js';
import {
  addReservationRecordToBuckets,
  buildReservationBuckets,
  normalizeReservationListResponse,
} from '@/modules/request/services/requestReservationMapper.js';

/**
 * Shared Pinia store for reservation request lists used by admin pages.
 */
export const useRequestStore = defineStore('requestStore', () => {
  const notificationStore = useNotificationStore();
  const pendingRequestsList = ref([]);
  const approvedRequestsList = ref([]);
  const activeReservationsList = ref([]);
  const pastRecordsList = ref([]);
  const isLoadingReservations = ref(false);

  const pendingCount = computed(() => pendingRequestsList.value.length);
  const approvedCount = computed(() => approvedRequestsList.value.length);
  const activeCount = computed(() => activeReservationsList.value.length);
  const overdueCount = computed(() => 0);
  const completedCount = computed(() => pastRecordsList.value.filter((record) => record.recordStatus === 'Completed').length);

  async function approvePendingRequest(requestRecord, securityConfirmation = null) {
    await updateReservationStatusAndRefresh(
      requestRecord,
      'Approved',
      null,
      'Failed to approve request:',
      securityConfirmation,
    );
  }

  async function rejectPendingRequest(requestRecord, rejectionReason = null, securityConfirmation = null) {
    await updateReservationStatusAndRefresh(
      requestRecord,
      'Rejected',
      rejectionReason,
      'Failed to reject request:',
      securityConfirmation,
    );
  }

  async function deployApprovedRequest(requestRecord, securityConfirmation = null) {
    await updateReservationStatusAndRefresh(requestRecord, 'Deployed', null, 'Failed to deploy request:', securityConfirmation);
  }

  async function cancelApprovedRequest(requestRecord, cancellationReason = null, securityConfirmation = null) {
    await updateReservationStatusAndRefresh(requestRecord, 'Cancelled', cancellationReason, 'Failed to cancel request:', securityConfirmation);
  }

  async function cancelOwnRequest(requestRecord, cancellationReason) {
    await updateReservationStatusAndRefresh(requestRecord, 'Cancelled', cancellationReason, 'Failed to cancel reservation request:');
  }

  async function completeActiveReservation(reservationRecord, completionRemarks = null, securityConfirmation = null) {
    await updateReservationStatusAndRefresh(
      reservationRecord,
      'Completed',
      completionRemarks,
      'Failed to complete reservation:',
      securityConfirmation,
    );
  }

  async function cancelActiveReservation(reservationRecord, cancellationReason = null, securityConfirmation = null) {
    await updateReservationStatusAndRefresh(
      reservationRecord,
      'Cancelled',
      cancellationReason,
      'Failed to cancel reservation:',
      securityConfirmation,
    );
  }

  async function fetchDashboardData() {
    try {
      return await dashboardApi.getDashboardSummary();
    } catch (error) {
      console.error('Failed to fetch dashboard data:', error);
      return null;
    }
  }

  async function fetchReservations(options = {}) {
    const shouldClearOnError = options.clearOnError !== false;

    try {
      isLoadingReservations.value = true;
      const [reservationResponse, taskResponse] = await Promise.all([
        reservationApi.listReservations(),
        taskApi.listTasks().catch((error) => {
          console.error('Failed to fetch workflow tasks:', error);
          return { data: { tasks: [] } };
        }),
      ]);
      console.log('Raw API response:', reservationResponse);

      const apiReservations = normalizeReservationListResponse(reservationResponse);
      const apiTasks = Array.isArray(taskResponse?.data?.tasks)
        ? taskResponse.data.tasks
        : Array.isArray(taskResponse?.tasks)
          ? taskResponse.tasks
          : [];
      console.log('Fetched reservations from API:', apiReservations);

      syncReservationsFromAPI(apiReservations, apiTasks);
    } catch (error) {
      console.error('Failed to fetch reservations:', error);
      if (shouldClearOnError) {
        clearReservationLists();
      }
    } finally {
      isLoadingReservations.value = false;
    }
  }

  async function addNewReservation(reservationData) {
    try {
      const response = await reservationApi.createReservation(reservationData);
      const reservation = response.data || response;

      if (reservation) {
        console.log('Reservation created successfully:', reservation);
        await fetchReservations();
        await refreshNotifications();
        return reservation;
      }
    } catch (error) {
      throw new Error(
        error?.response?.data?.errorMessage
          || error?.message
          || 'Unable to submit reservation at this time.'
      );
    }
  }

  async function updateReservationStatusAndRefresh(requestRecord, status, reason, errorMessage, securityConfirmation = null) {
    try {
      await reservationApi.updateReservationStatus(requestRecord.requestIdentifier, status, reason, securityConfirmation);
      updateLocalReservationStatus(requestRecord, status, reason);
      fetchReservations({ clearOnError: false }).catch((error) => {
        console.warn('Background reservation refresh failed after status update:', error);
      });
      await refreshNotifications();
    } catch (error) {
      console.error(errorMessage, error);
      throw new Error(
        error?.response?.data?.errorMessage
          || error?.message
          || errorMessage.replace(/:$/, '.')
      );
    }
  }

  function updateLocalReservationStatus(requestRecord, status, reason = null) {
    const requestIdentifier = resolveRequestIdentifier(requestRecord);
    if (requestIdentifier <= 0) {
      return;
    }

    const existingRecord = removeRecordFromAllBuckets(requestIdentifier) || requestRecord;
    const updatedRecord = {
      ...existingRecord,
      requestStatus: status,
      recordStatus: status,
      remarks: resolveStatusRemarks(reason, existingRecord, status),
      cancellationReason: resolveStatusRemarks(reason, existingRecord, status),
    };

    addRecordToStatusBucket(updatedRecord, status);
  }

  function resolveRequestIdentifier(requestRecord) {
    return Number(requestRecord?.requestIdentifier || requestRecord?.reservationIdentifier || 0);
  }

  function removeRecordFromAllBuckets(requestIdentifier) {
    let removedRecord = null;
    const removeFromBucket = (records) => {
      const index = records.findIndex((record) => resolveRequestIdentifier(record) === requestIdentifier);
      if (index === -1) {
        return records;
      }

      const nextRecords = [...records];
      const [record] = nextRecords.splice(index, 1);
      removedRecord = removedRecord || record;
      return nextRecords;
    };

    pendingRequestsList.value = removeFromBucket(pendingRequestsList.value);
    approvedRequestsList.value = removeFromBucket(approvedRequestsList.value);
    activeReservationsList.value = removeFromBucket(activeReservationsList.value);
    pastRecordsList.value = removeFromBucket(pastRecordsList.value);

    return removedRecord;
  }

  function addRecordToStatusBucket(record, status) {
    const buckets = {
      pending: [],
      approved: [],
      active: [],
      past: [],
    };

    addReservationRecordToBuckets(buckets, record, status, record);

    pendingRequestsList.value = upsertRecordBatchByIdentifier(pendingRequestsList.value, buckets.pending);
    approvedRequestsList.value = upsertRecordBatchByIdentifier(approvedRequestsList.value, buckets.approved);
    activeReservationsList.value = upsertRecordBatchByIdentifier(activeReservationsList.value, buckets.active);
    pastRecordsList.value = upsertRecordBatchByIdentifier(pastRecordsList.value, buckets.past);
  }

  function upsertRecordByIdentifier(records, record) {
    const requestIdentifier = resolveRequestIdentifier(record);
    const nextRecords = records.filter((existingRecord) => resolveRequestIdentifier(existingRecord) !== requestIdentifier);
    return [record, ...nextRecords];
  }

  function upsertRecordBatchByIdentifier(records, nextRecords) {
    return nextRecords.reduce(
      (currentRecords, currentRecord) => upsertRecordByIdentifier(currentRecords, currentRecord),
      records,
    );
  }

  function resolveStatusRemarks(reason, existingRecord, status) {
    const normalizedReason = String(reason || '').trim();
    if (normalizedReason !== '') {
      return normalizedReason;
    }

    const existingRemarks = String(existingRecord?.remarks || existingRecord?.cancellationReason || '').trim();
    if (existingRemarks !== '') {
      return existingRemarks;
    }

    return status === 'Cancelled'
      ? 'Cancelled by requester'
      : `Reservation is currently marked as ${status}.`;
  }

  function syncReservationsFromAPI(apiReservations, taskRecords = []) {
    console.log('Syncing reservations from API:', apiReservations);

    const buckets = buildReservationBuckets(apiReservations, taskRecords);
    pendingRequestsList.value = buckets.pending;
    approvedRequestsList.value = buckets.approved;
    activeReservationsList.value = buckets.active;
    pastRecordsList.value = buckets.past;

    console.log(
      'After sync - Pending:',
      pendingRequestsList.value.length,
      'Approved:',
      approvedRequestsList.value.length,
      'Active:',
      activeReservationsList.value.length,
      'Past:',
      pastRecordsList.value.length,
    );
  }

  function clearReservationLists() {
    pendingRequestsList.value = [];
    approvedRequestsList.value = [];
    activeReservationsList.value = [];
    pastRecordsList.value = [];
  }

  async function refreshNotifications() {
    try {
      await notificationStore.fetchNotifications(true);
    } catch (error) {
      console.warn('Failed to refresh notifications after reservation update:', error);
    }
  }

  return {
    pendingRequestsList,
    approvedRequestsList,
    activeReservationsList,
    pastRecordsList,
    isLoadingReservations,
    pendingCount,
    approvedCount,
    activeCount,
    overdueCount,
    completedCount,
    approvePendingRequest,
    rejectPendingRequest,
    deployApprovedRequest,
    cancelApprovedRequest,
    cancelOwnRequest,
    completeActiveReservation,
    cancelActiveReservation,
    fetchDashboardData,
    fetchReservations,
    addNewReservation,
  };
});
