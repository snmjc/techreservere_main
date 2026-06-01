const PENDING_STATUSES = ['Pending Review', 'Pending'];
const ACTIVE_STATUSES = ['Deployed', 'Prepared', 'Active'];
const PAST_RECORD_STATUSES = ['Completed', 'Rejected', 'Cancelled'];

export function normalizeReservationListResponse(response) {
  if (Array.isArray(response)) {
    return response;
  }

  if (Array.isArray(response?.reservations)) {
    return response.reservations;
  }

  if (Array.isArray(response?.data)) {
    return response.data;
  }

  return [];
}

export function buildReservationBuckets(apiReservations = []) {
  const buckets = createEmptyReservationBuckets();

  apiReservations.forEach((reservation) => {
    addReservationToBucket(buckets, reservation);
  });

  return buckets;
}

function createEmptyReservationBuckets() {
  return {
    pending: [],
    approved: [],
    active: [],
    past: [],
  };
}

function addReservationToBucket(buckets, reservation) {
  const mappedRecord = mapReservationRecord(reservation);
  const status = reservation?.currentStatus || '';

  if (PENDING_STATUSES.includes(status)) {
    buckets.pending.push(mappedRecord);
    return;
  }

  if (status === 'Approved') {
    buckets.approved.push({
      ...mappedRecord,
      assignedPersonnel: 'Pending Assignment',
    });
    return;
  }

  if (ACTIVE_STATUSES.includes(status)) {
    buckets.active.push({
      ...mappedRecord,
      facilityName: 'N/A',
      deploymentStatus: 'Deployed/Released',
    });
    return;
  }

  if (PAST_RECORD_STATUSES.includes(status)) {
    buckets.past.push({
      ...mappedRecord,
      recordStatus: status,
    });
  }
}

function mapReservationRecord(reservation) {
  return {
    requestIdentifier: reservation?.reservationIdentifier || 0,
    requesterFullName: reservation?.organizationName || 'User',
    requesterRole: 'Borrower',
    requestSchedule: reservation?.eventDateTime || 'N/A',
    requestQuantity: reservation?.requestedQuantity || 0,
    requestType: reservation?.requestedEquipmentList?.length > 0 ? 'Equipment' : 'Venue',
    requestPurpose: reservation?.purposeDescription || 'N/A',
    requesterDepartment: reservation?.organizationName || 'N/A',
    requestedDate: reservation?.submissionTimestamp || 'N/A',
    activityTime: reservation?.eventDateTime || 'N/A',
    activityNameTitle: reservation?.activityType || 'N/A',
    participantCount: reservation?.requestedQuantity || 0,
    requestStatus: reservation?.currentStatus || 'Unknown',
    reservationSummary: mapRequestedEquipment(reservation?.requestedEquipmentList),
  };
}

function mapRequestedEquipment(requestedEquipmentList = []) {
  const equipmentList = Array.isArray(requestedEquipmentList) ? requestedEquipmentList : [];

  return equipmentList.map((equipment) => ({
    itemName: equipment?.name || equipment,
    itemCount: 1,
  }));
}
