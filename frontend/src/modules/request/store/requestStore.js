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
  const pendingRequestsList = ref([
    {
      requestIdentifier: 44031,
      requesterFullName: 'Juan Dela Cruz',
      requesterRole: 'Student',
      requestSchedule: 'March 17, 2026 8:00 AM',
      requestQuantity: 60,
      requestType: 'Equipment',
      requestPurpose: 'ACM General Assembly',
      requesterDepartment: 'College of Engineering',
      requestedDate: 'February 20, 2026',
      activityTime: 'March 17, 2026',
      activityNameTitle: 'ACM General Assembly',
      participantCount: 60,
      requestStatus: 'Pending for Approval',
      reservationSummary: [
        { itemName: 'White Monobloc Chair', itemCount: 60 },
        { itemName: 'Table', itemCount: 5 },
        { itemName: 'Podium', itemCount: 1 },
      ],
      uploadedDocuments: [
        { fileName: 'ACM_APPandAPF.pdf' },
        { fileName: 'ACM_FloorPlan.pdf' },
      ],
    },
  ]);

  // ==========================================
  // APPROVED REQUESTS
  // ==========================================
  const approvedRequestsList = ref([
    {
      requestIdentifier: 60021,
      requesterFullName: 'Maria Lourdes Cruz',
      requesterRole: 'Faculty',
      requestSchedule: 'March 10, 2026 10:00 AM',
      requestQuantity: 40,
      requestType: 'Equipment',
      requestPurpose: 'Seminar',
      requesterDepartment: 'College of Engineering',
      requestedDate: 'February 10, 2026',
      activityTime: 'March 10, 2026',
      activityEndTime: '10:00-12:00',
      activityNameTitle: 'Engineering Seminar',
      participantCount: 40,
      requestStatus: 'Approved',
      reservationSummary: [
        { itemName: 'White Monobloc Chair', itemCount: 40 },
        { itemName: 'Microphone', itemCount: 2 },
      ],
      assignedPersonnel: 'Mr. Carlos Reyes',
    },
  ]);

  // ==========================================
  // ACTIVE RESERVATIONS
  // ==========================================
  const activeReservationsList = ref([
    {
      requestIdentifier: 60022,
      requesterFullName: 'Maria Lourdes Cruz',
      requesterRole: 'Faculty',
      requestSchedule: 'March 10, 2026',
      facilityName: 'N/A',
      requestQuantity: 40,
      requestType: 'Equipment',
      requestPurpose: 'Seminar',
      requesterDepartment: 'College of Engineering',
      requestedDate: 'February 10, 2026',
      activityDate: 'March 10, 2026',
      activityEndTime: '10:00-12:00',
      activityNameTitle: 'Engineering Seminar',
      participantCount: 40,
      deploymentStatus: 'Deployed/Released',
      reservationSummary: [
        { itemName: 'White Monobloc Chair', itemCount: 40, itemRecorded: false },
        { itemName: 'Microphone', itemCount: 2, itemRecorded: false },
      ],
      assignedPersonnel: 'Mr. Carlos Reyes',
      returnDateTime: 'March 10, 2026 10:00-12:00',
    },
  ]);

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
   * Approve a pending request → moves it to approved requests list.
   */
  function approvePendingRequest(requestRecord) {
    const index = pendingRequestsList.value.findIndex(
      (r) => r.requestIdentifier === requestRecord.requestIdentifier
    );
    if (index === -1) return;
    const record = pendingRequestsList.value.splice(index, 1)[0];
    approvedRequestsList.value.push({
      ...record,
      requestStatus: 'Approved',
      assignedPersonnel: 'Pending Assignment',
    });
  }

  /**
   * Reject a pending request → moves it to past records with "Rejected" status.
   */
  function rejectPendingRequest(requestRecord) {
    const index = pendingRequestsList.value.findIndex(
      (r) => r.requestIdentifier === requestRecord.requestIdentifier
    );
    if (index === -1) return;
    const record = pendingRequestsList.value.splice(index, 1)[0];
    pastRecordsList.value.push({
      requestIdentifier: record.requestIdentifier,
      requesterFullName: record.requesterFullName,
      requesterRole: record.requesterRole,
      requestedDate: record.requestedDate,
      neededDate: record.requestSchedule,
      facilityName: record.reservationSummary?.[0]?.itemName || 'N/A',
      facilityImage: 'https://placehold.co/40x40/1a6e3a/ffffff?text=F',
      requestQuantity: record.requestQuantity,
      requestType: record.requestType,
      requestPurpose: record.requestPurpose,
      dateProcessed: getNowTimestamp(),
      recordStatus: 'Rejected',
    });
  }

  // ==========================================
  // ACTIONS: APPROVED REQUESTS
  // ==========================================

  /**
   * Deploy/release an approved request → moves it to active reservations.
   */
  function deployApprovedRequest(requestRecord) {
    const index = approvedRequestsList.value.findIndex(
      (r) => r.requestIdentifier === requestRecord.requestIdentifier
    );
    if (index === -1) return;
    const record = approvedRequestsList.value.splice(index, 1)[0];
    activeReservationsList.value.push({
      ...record,
      requestSchedule: record.activityTime || record.requestSchedule,
      facilityName: record.reservationSummary?.[0]?.itemName || 'N/A',
      activityDate: record.activityTime || record.requestSchedule,
      deploymentStatus: 'Deployed/Released',
      returnDateTime: `${record.activityTime || record.requestSchedule} ${record.activityEndTime || ''}`,
      reservationSummary: (record.reservationSummary || []).map((item) => ({
        ...item,
        itemRecorded: false,
      })),
    });
  }

  /**
   * Cancel an approved request → moves it to past records with "Cancelled" status.
   */
  function cancelApprovedRequest(requestRecord) {
    const index = approvedRequestsList.value.findIndex(
      (r) => r.requestIdentifier === requestRecord.requestIdentifier
    );
    if (index === -1) return;
    const record = approvedRequestsList.value.splice(index, 1)[0];
    pastRecordsList.value.push({
      requestIdentifier: record.requestIdentifier,
      requesterFullName: record.requesterFullName,
      requesterRole: record.requesterRole,
      requestedDate: record.requestedDate,
      neededDate: record.requestSchedule,
      facilityName: record.reservationSummary?.[0]?.itemName || 'N/A',
      facilityImage: 'https://placehold.co/40x40/1a6e3a/ffffff?text=F',
      requestQuantity: record.requestQuantity,
      requestType: record.requestType,
      requestPurpose: record.requestPurpose,
      dateProcessed: getNowTimestamp(),
      recordStatus: 'Cancelled',
    });
  }

  // ==========================================
  // ACTIONS: ACTIVE RESERVATIONS
  // ==========================================

  /**
   * Complete/return an active reservation → moves it to past records with "Completed" status.
   */
  function completeActiveReservation(reservationRecord) {
    const index = activeReservationsList.value.findIndex(
      (r) => r.requestIdentifier === reservationRecord.requestIdentifier
    );
    if (index === -1) return;
    const record = activeReservationsList.value.splice(index, 1)[0];
    pastRecordsList.value.push({
      requestIdentifier: record.requestIdentifier,
      requesterFullName: record.requesterFullName,
      requesterRole: record.requesterRole,
      requestedDate: record.requestedDate,
      neededDate: record.requestSchedule || record.activityDate,
      facilityName: record.facilityName || 'N/A',
      facilityImage: 'https://placehold.co/40x40/1a6e3a/ffffff?text=F',
      requestQuantity: record.requestQuantity,
      requestType: record.requestType,
      requestPurpose: record.requestPurpose,
      dateProcessed: getNowTimestamp(),
      recordStatus: 'Completed',
    });
  }

  /**
   * Cancel an active reservation → moves it to past records with "Cancelled" status.
   */
  function cancelActiveReservation(reservationRecord) {
    const index = activeReservationsList.value.findIndex(
      (r) => r.requestIdentifier === reservationRecord.requestIdentifier
    );
    if (index === -1) return;
    const record = activeReservationsList.value.splice(index, 1)[0];
    pastRecordsList.value.push({
      requestIdentifier: record.requestIdentifier,
      requesterFullName: record.requesterFullName,
      requesterRole: record.requesterRole,
      requestedDate: record.requestedDate,
      neededDate: record.requestSchedule || record.activityDate,
      facilityName: record.facilityName || 'N/A',
      facilityImage: 'https://placehold.co/40x40/1a6e3a/ffffff?text=F',
      requestQuantity: record.requestQuantity,
      requestType: record.requestType,
      requestPurpose: record.requestPurpose,
      dateProcessed: getNowTimestamp(),
      recordStatus: 'Cancelled',
    });
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
      const response = await reservationApi.listReservations();
      if (response && response.reservations) {
        syncReservationsFromAPI(response.reservations);
      }
    } catch (error) {
      console.error('Failed to fetch reservations:', error);
    }
  }

  function syncReservationsFromAPI(apiReservations) {
    pendingRequestsList.value = [];
    approvedRequestsList.value = [];
    activeReservationsList.value = [];

    apiReservations.forEach((res) => {
      const mappedRecord = {
        requestIdentifier: res.reservationIdentifier,
        requesterFullName: 'User',
        requesterRole: 'Borrower',
        requestSchedule: res.eventDateTime,
        requestQuantity: res.requestedQuantity,
        requestType: res.requestedEquipmentList?.length > 0 ? 'Equipment' : 'Venue',
        requestPurpose: res.purposeDescription,
        requesterDepartment: res.organizationName,
        requestedDate: res.submissionTimestamp,
        activityTime: res.eventDateTime,
        activityNameTitle: res.activityType,
        participantCount: res.requestedQuantity,
        requestStatus: res.currentStatus,
        reservationSummary: res.requestedEquipmentList?.map((eq) => ({
          itemName: eq.name || eq,
          itemCount: 1,
        })) || [],
      };

      if (res.currentStatus === 'Pending Review') {
        pendingRequestsList.value.push(mappedRecord);
      } else if (res.currentStatus === 'Approved') {
        approvedRequestsList.value.push({
          ...mappedRecord,
          assignedPersonnel: 'Pending Assignment',
        });
      } else if (['Deployed', 'Prepared'].includes(res.currentStatus)) {
        activeReservationsList.value.push({
          ...mappedRecord,
          facilityName: 'N/A',
          deploymentStatus: 'Deployed/Released',
        });
      }
    });
  }

  async function addNewReservation(reservationData) {
    try {
      const response = await reservationApi.createReservation(reservationData);
      const resData = response.data || response;
      
      if (resData) {
        console.log('Reservation created successfully:', resData);
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
