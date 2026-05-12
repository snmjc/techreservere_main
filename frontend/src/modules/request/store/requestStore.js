import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import dashboardApi from '@/modules/dashboard/services/dashboardApi.js';
import reservationApi from '@/modules/reservation/services/reservationApi.js';

/**
 * @description Shared Pinia store for managing reservation requests across all admin pages.
 * Actions move records between lists: pending → approved → active → past records.
 * Rejecting/cancelling sends records directly to past records.
 */
export const useRequestStore = defineStore('requestStore', () => {
  // ==========================================
  // PENDING REQUESTS
  // ==========================================
  const pendingRequestsList = ref([]);

  // ==========================================
  // APPROVED REQUESTS
  // ==========================================
  const approvedRequestsList = ref([]);

  // ==========================================
  // ACTIVE RESERVATIONS
  // ==========================================
  const activeReservationsList = ref([]);

  // ==========================================
  // PAST RECORDS
  // ==========================================
  const pastRecordsList = ref([]);

  // ==========================================
  // HELPER: get current timestamp string
  // ==========================================
  function getNowTimestamp() {
    const now = new Date();
    const mm = String(now.getMonth() + 1).padStart(2, '0');
    const dd = String(now.getDate()).padStart(2, '0');
    const yy = String(now.getFullYear()).slice(-2);
    const hh = String(now.getHours()).padStart(2, '0');
    const min = String(now.getMinutes()).padStart(2, '0');
    return `${mm}/${dd}/${yy} ${hh}:${min}`;
  }

  // ==========================================
  // ACTIONS: PENDING REQUESTS
  // ==========================================

  /**
   * Approve a pending request → moves it to approved requests.
   */
  async function approvePendingRequest(requestRecord) {
    try {
      await reservationApi.updateReservationStatus(requestRecord.requestIdentifier, 'Approved');
      await fetchReservations();
    } catch (error) {
      console.error('Failed to approve request:', error);
    }
  }

  /**
   * Reject a pending request → moves it to past records with "Rejected" status.
   */
  async function rejectPendingRequest(requestRecord, rejectionReason = null) {
    try {
      await reservationApi.updateReservationStatus(requestRecord.requestIdentifier, 'Rejected', rejectionReason);
      await fetchReservations();
    } catch (error) {
      console.error('Failed to reject request:', error);
    }
  }

  // ==========================================
  // ACTIONS: APPROVED REQUESTS
  // ==========================================

  /**
   * Deploy/release an approved request → moves it to active reservations.
   */
  async function deployApprovedRequest(requestRecord) {
    try {
      await reservationApi.updateReservationStatus(requestRecord.requestIdentifier, 'Deployed');
      await fetchReservations();
    } catch (error) {
      console.error('Failed to deploy request:', error);
    }
  }

  /**
   * Cancel an approved request → moves it to past records with "Cancelled" status.
   */
  async function cancelApprovedRequest(requestRecord) {
    try {
      await reservationApi.updateReservationStatus(requestRecord.requestIdentifier, 'Cancelled');
      await fetchReservations();
    } catch (error) {
      console.error('Failed to cancel request:', error);
    }
  }

  // ==========================================
  // ACTIONS: ACTIVE RESERVATIONS
  // ==========================================

  /**
   * Complete/return an active reservation → moves it to past records with "Completed" status.
   */
  async function completeActiveReservation(reservationRecord) {
    try {
      await reservationApi.updateReservationStatus(reservationRecord.requestIdentifier, 'Completed');
      await fetchReservations();
    } catch (error) {
      console.error('Failed to complete reservation:', error);
    }
  }

  /**
   * Cancel an active reservation → moves it to past records with "Cancelled" status.
   */
  async function cancelActiveReservation(reservationRecord) {
    try {
      await reservationApi.updateReservationStatus(reservationRecord.requestIdentifier, 'Cancelled');
      await fetchReservations();
    } catch (error) {
      console.error('Failed to cancel reservation:', error);
    }
  }

  // ==========================================
  // COMPUTED: Dashboard counts
  // ==========================================
  const pendingCount = computed(() => pendingRequestsList.value.length);
  const approvedCount = computed(() => approvedRequestsList.value.length);
  const activeCount = computed(() => activeReservationsList.value.length);
  const overdueCount = computed(() => 0); // placeholder
  const completedCount = computed(() => pastRecordsList.value.filter((r) => r.recordStatus === 'Completed').length);

  // ==========================================
  // API INTEGRATION: Fetch and sync data
  // ==========================================

  async function fetchDashboardData() {
    try {
      const response = await dashboardApi.getDashboardSummary();
      return response;
    } catch (error) {
      console.error('Failed to fetch dashboard data:', error);
      return null;
    }
  }

  async function fetchReservations() {
    try {
      console.warn('Reservations API not yet implemented, using empty list');
      pendingRequestsList.value = [];
      approvedRequestsList.value = [];
      activeReservationsList.value = [];
      pastRecordsList.value = [];
    } catch (error) {
      console.error('Failed to fetch reservations:', error);
      pendingRequestsList.value = [];
      approvedRequestsList.value = [];
      activeReservationsList.value = [];
      pastRecordsList.value = [];
    }
  }

  function syncReservationsFromAPI(apiReservations) {
    pendingRequestsList.value = [];
    approvedRequestsList.value = [];
    activeReservationsList.value = [];
    pastRecordsList.value = [];

    console.log('Syncing reservations from API:', apiReservations);

    (apiReservations || []).forEach((res) => {
      const mappedRecord = {
        requestIdentifier: res?.reservationIdentifier || 0,
        requesterFullName: res?.organizationName || 'User',
        requesterRole: 'Borrower',
        requestSchedule: res?.eventDateTime || 'N/A',
        requestQuantity: res?.requestedQuantity || 0,
        requestType: res?.requestedEquipmentList?.length > 0 ? 'Equipment' : 'Venue',
        requestPurpose: res?.purposeDescription || 'N/A',
        requesterDepartment: res?.organizationName || 'N/A',
        requestedDate: res?.submissionTimestamp || 'N/A',
        activityTime: res?.eventDateTime || 'N/A',
        activityNameTitle: res?.activityType || 'N/A',
        participantCount: res?.requestedQuantity || 0,
        requestStatus: res?.currentStatus || 'Unknown',
        reservationSummary: res?.requestedEquipmentList?.map((eq) => ({
          itemName: eq?.name || eq,
          itemCount: 1,
        })) || [],
      };

      console.log('Processing reservation:', res.reservationIdentifier, 'Status:', res.currentStatus);

      const status = res?.currentStatus || '';
      if (status === 'Pending Review' || status === 'Pending') {
        pendingRequestsList.value.push(mappedRecord);
      } else if (status === 'Approved') {
        approvedRequestsList.value.push({
          ...mappedRecord,
          assignedPersonnel: 'Pending Assignment',
        });
      } else if (['Deployed', 'Prepared', 'Active'].includes(status)) {
        activeReservationsList.value.push({
          ...mappedRecord,
          facilityName: 'N/A',
          deploymentStatus: 'Deployed/Released',
        });
      } else if (['Completed', 'Rejected', 'Cancelled'].includes(status)) {
        pastRecordsList.value.push({
          ...mappedRecord,
          recordStatus: status,
        });
      }
    });

    console.log('After sync - Pending:', pendingRequestsList.value.length, 
                'Approved:', approvedRequestsList.value.length, 
                'Active:', activeReservationsList.value.length, 
                'Past:', pastRecordsList.value.length);
  }

  async function addNewReservation(reservationData) {
    try {
      const response = await reservationApi.createReservation(reservationData);
      const resData = response.data || response;
      
      if (resData) {
        console.log('Reservation created successfully:', resData);
        // Refetch reservations to update all lists
        await fetchReservations();
        return resData;
      }
    } catch (error) {
      console.error('Failed to create reservation:', error);
      throw error;
    }
  }

  return {
    pendingRequestsList,
    approvedRequestsList,
    activeReservationsList,
    pastRecordsList,
    pendingCount,
    approvedCount,
    activeCount,
    overdueCount,
    completedCount,
    approvePendingRequest,
    rejectPendingRequest,
    deployApprovedRequest,
    cancelApprovedRequest,
    completeActiveReservation,
    cancelActiveReservation,
    fetchDashboardData,
    fetchReservations,
    addNewReservation,
  };
});
