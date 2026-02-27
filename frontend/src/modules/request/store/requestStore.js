import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

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
    {
      requestIdentifier: 70306,
      requesterFullName: 'Juan Dela Cruz',
      requesterRole: 'Student',
      requestSchedule: 'March 20, 2026 1:00 PM',
      requestQuantity: 1,
      requestType: 'Venue',
      requestPurpose: 'Workshop Meeting',
      requesterDepartment: 'College of Engineering',
      requestedDate: 'February 22, 2026',
      activityTime: 'March 20, 2026',
      activityNameTitle: 'Workshop Meeting',
      participantCount: 30,
      requestStatus: 'Pending for Approval',
      reservationSummary: [
        { itemName: 'F401 Multipurpose Hall', itemCount: 1 },
      ],
      uploadedDocuments: [
        { fileName: 'Workshop_APF.pdf' },
      ],
    },
    {
      requestIdentifier: 10174,
      requesterFullName: 'Ali Santos',
      requesterRole: 'Faculty',
      requestSchedule: 'March 22, 2026 10:00 AM',
      requestQuantity: 30,
      requestType: 'Both',
      requestPurpose: 'Department Meeting',
      requesterDepartment: 'Computer Science Dept',
      requestedDate: 'February 24, 2026',
      activityTime: 'March 22, 2026',
      activityNameTitle: 'CS Department Meeting',
      participantCount: 30,
      requestStatus: 'Pending for Approval',
      reservationSummary: [
        { itemName: 'F502 Auditorium', itemCount: 1 },
        { itemName: 'White Monobloc Chair', itemCount: 30 },
        { itemName: 'Microphone', itemCount: 2 },
      ],
      uploadedDocuments: [
        { fileName: 'CS_DeptMeeting_APF.pdf' },
      ],
    },
    {
      requestIdentifier: 10175,
      requesterFullName: 'Andrea Reyes',
      requesterRole: 'Faculty',
      requestSchedule: 'March 25, 2026 9:00 AM',
      requestQuantity: 50,
      requestType: 'Equipment',
      requestPurpose: 'Science Fair Setup',
      requesterDepartment: 'College of Science',
      requestedDate: 'February 25, 2026',
      activityTime: 'March 25, 2026',
      activityNameTitle: 'Science Fair 2026',
      participantCount: 200,
      requestStatus: 'Pending for Approval',
      reservationSummary: [
        { itemName: 'Table', itemCount: 20 },
        { itemName: 'Extension Cord', itemCount: 15 },
        { itemName: 'White Monobloc Chair', itemCount: 50 },
      ],
      uploadedDocuments: [
        { fileName: 'ScienceFair_APF.pdf' },
        { fileName: 'ScienceFair_Layout.pdf' },
      ],
    },
    {
      requestIdentifier: 97101,
      requesterFullName: 'Angelica Ramos',
      requesterRole: 'Faculty',
      requestSchedule: 'March 28, 2026 2:00 PM',
      requestQuantity: 1,
      requestType: 'Venue',
      requestPurpose: 'Faculty Orientation',
      requesterDepartment: 'HR Department',
      requestedDate: 'February 26, 2026',
      activityTime: 'March 28, 2026',
      activityNameTitle: 'New Faculty Orientation',
      participantCount: 25,
      requestStatus: 'Pending for Approval',
      reservationSummary: [
        { itemName: 'FS03 Audio Visual Room', itemCount: 1 },
      ],
      uploadedDocuments: [
        { fileName: 'Orientation_APF.pdf' },
      ],
    },
    {
      requestIdentifier: 19083,
      requesterFullName: 'Cristina Archamo',
      requesterRole: 'Student',
      requestSchedule: 'April 1, 2026 8:00 AM',
      requestQuantity: 100,
      requestType: 'Both',
      requestPurpose: 'University Week',
      requesterDepartment: 'Student Council',
      requestedDate: 'February 26, 2026',
      activityTime: 'April 1, 2026',
      activityNameTitle: 'FEU Tech University Week',
      participantCount: 500,
      requestStatus: 'Pending for Approval',
      reservationSummary: [
        { itemName: 'F401 Multipurpose Hall', itemCount: 1 },
        { itemName: 'Stage', itemCount: 1 },
        { itemName: 'White Monobloc Chair', itemCount: 100 },
        { itemName: 'Microphone', itemCount: 4 },
      ],
      uploadedDocuments: [
        { fileName: 'UniWeek_APF.pdf' },
        { fileName: 'UniWeek_Program.pdf' },
        { fileName: 'UniWeek_Layout.pdf' },
      ],
    },
    {
      requestIdentifier: 30950,
      requesterFullName: 'Alyssa Padilla',
      requesterRole: 'Student',
      requestSchedule: 'April 5, 2026 10:00 AM',
      requestQuantity: 40,
      requestType: 'Equipment',
      requestPurpose: 'Org Workshop',
      requesterDepartment: 'College of Engineering',
      requestedDate: 'February 26, 2026',
      activityTime: 'April 5, 2026',
      activityNameTitle: 'Engineering Org Workshop',
      participantCount: 40,
      requestStatus: 'Pending for Approval',
      reservationSummary: [
        { itemName: 'White Monobloc Chair', itemCount: 40 },
        { itemName: 'Table', itemCount: 8 },
      ],
      uploadedDocuments: [
        { fileName: 'OrgWorkshop_APF.pdf' },
      ],
    },
    {
      requestIdentifier: 50400,
      requesterFullName: 'Taylor Ocampo',
      requesterRole: 'Faculty',
      requestSchedule: 'April 8, 2026 1:00 PM',
      requestQuantity: 1,
      requestType: 'Venue',
      requestPurpose: 'Lab Orientation',
      requesterDepartment: 'IT Department',
      requestedDate: 'February 26, 2026',
      activityTime: 'April 8, 2026',
      activityNameTitle: 'IT Lab Orientation',
      participantCount: 35,
      requestStatus: 'Pending for Approval',
      reservationSummary: [
        { itemName: 'F404 Multimedia Room', itemCount: 1 },
      ],
      uploadedDocuments: [
        { fileName: 'LabOrientation_APF.pdf' },
      ],
    },
    {
      requestIdentifier: 84900,
      requesterFullName: 'Market Boo',
      requesterRole: 'Student',
      requestSchedule: 'April 10, 2026 9:00 AM',
      requestQuantity: 75,
      requestType: 'Both',
      requestPurpose: 'Cultural Night',
      requesterDepartment: 'Cultural Committee',
      requestedDate: 'February 26, 2026',
      activityTime: 'April 10, 2026',
      activityNameTitle: 'FEU Tech Cultural Night',
      participantCount: 300,
      requestStatus: 'Pending for Approval',
      reservationSummary: [
        { itemName: 'F502 Auditorium', itemCount: 1 },
        { itemName: 'White Monobloc Chair', itemCount: 75 },
        { itemName: 'Microphone', itemCount: 3 },
        { itemName: 'Stage', itemCount: 1 },
      ],
      uploadedDocuments: [
        { fileName: 'CulturalNight_APF.pdf' },
        { fileName: 'CulturalNight_Perf.pdf' },
      ],
    },
  ]);

  // ==========================================
  // APPROVED REQUESTS
  // ==========================================
  const approvedRequestsList = ref([
    {
      requestIdentifier: 59327,
      requesterFullName: 'Michael Garcia',
      requesterRole: 'Faculty',
      requestSchedule: 'March 08, 2026 8:00 AM',
      requestQuantity: 1,
      requestType: 'Venue',
      requestPurpose: 'Class',
      requesterDepartment: 'College of Computer Studies and Multimedia Arts',
      requestedDate: 'February 07, 2026',
      activityTime: 'March 08, 2026',
      activityEndTime: '15:00-16:50',
      activityNameTitle: 'IT0047 Make Up Class',
      participantCount: 40,
      requestStatus: 'Approved',
      reservationSummary: [
        { itemName: 'Room F403', itemCount: 'N/A' },
      ],
      assignedPersonnel: 'Ms. Gina Espino Kenawy',
    },
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
    {
      requestIdentifier: 70145,
      requesterFullName: 'Taylor Ocampo',
      requesterRole: 'Faculty',
      requestSchedule: 'March 12, 2026 1:00 PM',
      requestQuantity: 1,
      requestType: 'Venue',
      requestPurpose: 'Workshop',
      requesterDepartment: 'IT Department',
      requestedDate: 'February 12, 2026',
      activityTime: 'March 12, 2026',
      activityEndTime: '13:00-16:00',
      activityNameTitle: 'IT Workshop',
      participantCount: 35,
      requestStatus: 'Approved',
      reservationSummary: [
        { itemName: 'F404 Multimedia Room', itemCount: 'N/A' },
      ],
      assignedPersonnel: 'Ms. Ana Torres',
    },
    {
      requestIdentifier: 44032,
      requesterFullName: 'Juan Dela Cruz',
      requesterRole: 'Student',
      requestSchedule: 'March 17, 2026 8:00 AM',
      requestQuantity: 60,
      requestType: 'Both',
      requestPurpose: 'ACM General Assembly',
      requesterDepartment: 'College of Engineering',
      requestedDate: 'February 20, 2026',
      activityTime: 'March 17, 2026',
      activityEndTime: '08:00-12:00',
      activityNameTitle: 'ACM General Assembly',
      participantCount: 60,
      requestStatus: 'Approved',
      reservationSummary: [
        { itemName: 'F401 Multipurpose Hall', itemCount: 'N/A' },
        { itemName: 'White Monobloc Chair', itemCount: 60 },
        { itemName: 'Table', itemCount: 5 },
        { itemName: 'Podium', itemCount: 1 },
      ],
      assignedPersonnel: 'Mr. Roberto Santos',
    },
    {
      requestIdentifier: 80512,
      requesterFullName: 'Alex Mendoza',
      requesterRole: 'Faculty',
      requestSchedule: 'March 20, 2026 9:00 AM',
      requestQuantity: 75,
      requestType: 'Equipment',
      requestPurpose: 'Department Event',
      requesterDepartment: 'College of Science',
      requestedDate: 'February 22, 2026',
      activityTime: 'March 20, 2026',
      activityEndTime: '09:00-17:00',
      activityNameTitle: 'Science Week Opening',
      participantCount: 200,
      requestStatus: 'Approved',
      reservationSummary: [
        { itemName: 'White Monobloc Chair', itemCount: 75 },
        { itemName: 'Table', itemCount: 15 },
        { itemName: 'Extension Cord', itemCount: 10 },
      ],
      assignedPersonnel: 'Ms. Gina Espino Kenawy',
    },
  ]);

  // ==========================================
  // ACTIVE RESERVATIONS
  // ==========================================
  const activeReservationsList = ref([
    {
      requestIdentifier: 59328,
      requesterFullName: 'Michael Garcia',
      requesterRole: 'Faculty',
      requestSchedule: 'March 08, 2026',
      facilityName: 'Room F403',
      requestQuantity: 1,
      requestType: 'Venue',
      requestPurpose: 'Class',
      requesterDepartment: 'College of Computer Studies and Multimedia Arts',
      requestedDate: 'February 07, 2026',
      activityDate: 'March 08, 2026',
      activityEndTime: '15:00-16:50',
      activityNameTitle: 'IT0047 Make Up Class',
      participantCount: 40,
      deploymentStatus: 'Deployed/Released',
      reservationSummary: [
        { itemName: 'Room F403', itemCount: 'N/A', itemRecorded: true },
      ],
      assignedPersonnel: 'Ms. Gina Espino Kenawy',
      returnDateTime: 'March 08, 2026 15:00-16:50',
    },
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
    {
      requestIdentifier: 44033,
      requesterFullName: 'Juan Dela Cruz',
      requesterRole: 'Student',
      requestSchedule: 'March 17, 2026',
      facilityName: 'F401 Hall',
      requestQuantity: 60,
      requestType: 'Both',
      requestPurpose: 'ACM General Assembly',
      requesterDepartment: 'College of Engineering',
      requestedDate: 'February 20, 2026',
      activityDate: 'March 17, 2026',
      activityEndTime: '08:00-12:00',
      activityNameTitle: 'ACM General Assembly',
      participantCount: 60,
      deploymentStatus: 'Deployed/Released',
      reservationSummary: [
        { itemName: 'F401 Multipurpose Hall', itemCount: 'N/A', itemRecorded: true },
        { itemName: 'White Monobloc Chair', itemCount: 60, itemRecorded: false },
        { itemName: 'Table', itemCount: 5, itemRecorded: false },
        { itemName: 'Podium', itemCount: 1, itemRecorded: false },
      ],
      assignedPersonnel: 'Mr. Roberto Santos',
      returnDateTime: 'March 17, 2026 08:00-12:00',
    },
    {
      requestIdentifier: 27001,
      requesterFullName: 'Carlo Santos',
      requesterRole: 'Student',
      requestSchedule: 'March 18, 2026',
      facilityName: 'Utility',
      requestQuantity: 20,
      requestType: 'Equipment',
      requestPurpose: 'Club Meeting',
      requesterDepartment: 'Student Affairs',
      requestedDate: 'February 22, 2026',
      activityDate: 'March 18, 2026',
      activityEndTime: '14:00-16:00',
      activityNameTitle: 'Robotics Club Meeting',
      participantCount: 20,
      deploymentStatus: 'Deployed/Released',
      reservationSummary: [
        { itemName: 'White Monobloc Chair', itemCount: 20, itemRecorded: false },
        { itemName: 'Table', itemCount: 4, itemRecorded: false },
      ],
      assignedPersonnel: 'Ms. Ana Torres',
      returnDateTime: 'March 18, 2026 14:00-16:00',
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
  };
});
