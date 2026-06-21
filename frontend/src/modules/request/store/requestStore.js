import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import dashboardApi from '@/modules/dashboard/services/dashboardApi.js';
import reservationApi from '@/modules/reservation/services/reservationApi.js';
import taskApi from '@/modules/task/services/taskApi.js';
import {
  buildReservationBuckets,
  normalizeReservationListResponse,
} from '@/modules/request/services/requestReservationMapper.js';

/**
 * Shared Pinia store for reservation request lists used by admin pages.
 */
export const useRequestStore = defineStore('requestStore', () => {
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

  async function approvePendingRequest(requestRecord) {
    await updateReservationStatusAndRefresh(requestRecord, 'Approved', null, 'Failed to approve request:');
  }

  async function rejectPendingRequest(requestRecord, rejectionReason = null) {
    await updateReservationStatusAndRefresh(requestRecord, 'Rejected', rejectionReason, 'Failed to reject request:');
  }

  async function deployApprovedRequest(requestRecord) {
    await updateReservationStatusAndRefresh(requestRecord, 'Deployed', null, 'Failed to deploy request:');
  }

  async function cancelApprovedRequest(requestRecord) {
    await updateReservationStatusAndRefresh(requestRecord, 'Cancelled', null, 'Failed to cancel request:');
  }

  async function cancelOwnRequest(requestRecord, cancellationReason) {
    await updateReservationStatusAndRefresh(requestRecord, 'Cancelled', cancellationReason, 'Failed to cancel reservation request:');
  }

  async function completeActiveReservation(reservationRecord) {
    await updateReservationStatusAndRefresh(reservationRecord, 'Completed', null, 'Failed to complete reservation:');
  }

  async function cancelActiveReservation(reservationRecord) {
    await updateReservationStatusAndRefresh(reservationRecord, 'Cancelled', null, 'Failed to cancel reservation:');
  }

  async function fetchDashboardData() {
    try {
      return await dashboardApi.getDashboardSummary();
    } catch (error) {
      console.error('Failed to fetch dashboard data:', error);
      return null;
    }
  }

  async function fetchReservations() {
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
      clearReservationLists();
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
        return reservation;
      }
    } catch (error) {
      console.error('Failed to create reservation:', error);
      throw error;
    }
  }

  async function updateReservationStatusAndRefresh(requestRecord, status, reason, errorMessage) {
    try {
      await reservationApi.updateReservationStatus(requestRecord.requestIdentifier, status, reason);
      await fetchReservations();
    } catch (error) {
      console.error(errorMessage, error);
      throw error;
    }
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
