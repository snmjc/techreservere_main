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

  if (Array.isArray(response?.data?.reservations)) {
    return response.data.reservations;
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
  const requestedEquipmentList = Array.isArray(reservation?.requestedEquipmentList)
    ? reservation.requestedEquipmentList
    : [];
  const reservationSummary = mapRequestedEquipment(requestedEquipmentList);
  const requestType = resolveRequestType(reservation, requestedEquipmentList);
  const requestScheduleStart = reservation?.eventDateTime || null;
  const requestScheduleEnd = reservation?.endDateTime || null;

  return {
    requestIdentifier: reservation?.reservationIdentifier || 0,
    requestDisplayIdentifier: reservation?.reservationCode || reservation?.reservationIdentifier || 'N/A',
    reservationIdentifier: reservation?.reservationIdentifier || 0,
    reservationCode: reservation?.reservationCode || reservation?.reservationIdentifier || 'N/A',
    requesterFullName: resolveRequesterFullName(reservation),
    requesterRole: reservation?.requesterRole || reservation?.roleDesignation || reservation?.userRole || 'Borrower',
    requesterId: reservation?.idNumber || reservation?.accountIdentifier || reservation?.userIdentifier || null,
    contactEmail: reservation?.borrowerEmailAddress || reservation?.emailAddress || reservation?.requesterEmail || reservation?.organizationEmail || null,
    contactNumber: reservation?.borrowerContactNumber || reservation?.contactNumber || reservation?.phoneNumber || reservation?.mobileNumber || null,
    requestSchedule: formatReservationScheduleRange(requestScheduleStart, requestScheduleEnd),
    requestScheduleStart,
    requestScheduleEnd,
    requestQuantity: reservation?.requestedQuantity || 0,
    requestType,
    requestPurpose: reservation?.purposeDescription || 'N/A',
    activityTitle: reservation?.activityTitle || reservation?.activityNameTitle || reservation?.activityType || 'N/A',
    typeOfActivity: reservation?.typeOfActivity || reservation?.activityCategory || reservation?.activityType || 'N/A',
    facilityName: getReservationFacilityName(reservation, requestedEquipmentList),
    requesterDepartment: reservation?.organizationName || 'N/A',
    requestedDate: reservation?.submissionTimestamp || 'N/A',
    neededDate: reservation?.endDateTime || reservation?.eventDateTime || 'N/A',
    activityTime: reservation?.activityTimeRange || reservation?.eventDateTime || 'N/A',
    activityEndTime: reservation?.endDateTime || reservation?.eventDateTime || 'N/A',
    activityNameTitle: reservation?.activityType || 'N/A',
    participantCount: reservation?.requestedQuantity || 0,
    requestStatus: reservation?.currentStatus || 'Unknown',
    cancellationReason: reservation?.rejectionReason || '',
    remarks: reservation?.rejectionReason || buildReservationRemark(reservation),
    uploadedDocuments: mapUploadedDocuments(reservation?.supportingDocuments),
    reservationSummary,
    reservedResources: buildReservedResources(reservation, requestedEquipmentList),
  };
}

function resolveRequestType(reservation, requestedEquipmentList) {
  if (reservation?.venueIdentifier && requestedEquipmentList.length > 0) {
    return 'Both';
  }

  if (requestedEquipmentList.length > 0) {
    return 'Equipment';
  }

  return 'Venue';
}

function resolveRequesterFullName(reservation) {
  return (
    reservation?.borrowerFullName ||
    reservation?.requesterFullName ||
    reservation?.fullName ||
    reservation?.accountFullName ||
    'User'
  );
}

function getReservationFacilityName(reservation, requestedEquipmentList) {
  if (reservation?.venueName) {
    return reservation.venueName;
  }

  if (reservation?.facilityName) {
    return reservation.facilityName;
  }

  if (reservation?.venueIdentifier) {
    return `Venue #${reservation.venueIdentifier}`;
  }

  if (requestedEquipmentList.length > 0) {
    return mapRequestedEquipment(requestedEquipmentList)
      .map((equipment) => equipment.itemName)
      .join(', ');
  }

  return 'N/A';
}

function mapRequestedEquipment(requestedEquipmentList = []) {
  const equipmentList = Array.isArray(requestedEquipmentList) ? requestedEquipmentList : [];

  return equipmentList.map((equipment) => ({
    itemName: equipment?.name || equipment?.equipmentName || equipment,
    itemCount: Number(equipment?.quantity ?? equipment?.selectedQuantity ?? 1) || 1,
  }));
}

function mapUploadedDocuments(supportingDocuments = []) {
  const documentList = Array.isArray(supportingDocuments) ? supportingDocuments : [];

  return documentList.map((documentFile, index) => ({
    fileName: typeof documentFile === 'string' ? documentFile : `Document ${index + 1}`,
    previewLabel: buildDocumentPreviewLabel(typeof documentFile === 'string' ? documentFile : `Document ${index + 1}`),
  }));
}

function buildReservedResources(reservation, requestedEquipmentList = []) {
  const reservedResources = [];
  const facilityName = getReservationFacilityName(reservation, requestedEquipmentList);
  const hasFacility = Boolean(reservation?.venueIdentifier || reservation?.venueName || reservation?.facilityName);

  if (hasFacility && facilityName !== 'N/A') {
    reservedResources.push({
      resourceType: 'Facility',
      resourceName: facilityName,
      resourceCount: 1,
    });
  }

  mapRequestedEquipment(requestedEquipmentList).forEach((equipment) => {
    reservedResources.push({
      resourceType: 'Equipment',
      resourceName: equipment.itemName,
      resourceCount: equipment.itemCount,
    });
  });

  return reservedResources;
}

function buildDocumentPreviewLabel(fileName) {
  const normalizedName = String(fileName || '').trim();
  const parts = normalizedName.split('.');
  const extension = parts.length > 1 ? parts.at(-1).toUpperCase() : '';

  return extension ? `${extension} File` : 'Document';
}

function buildReservationRemark(reservation) {
  const status = String(reservation?.currentStatus || 'Unknown');

  if (status === 'Completed') {
    return 'Reservation was completed successfully.';
  }

  if (status === 'Cancelled') {
    return 'Reservation was cancelled before completion.';
  }

  if (status === 'Rejected') {
    return 'Reservation request was rejected during review.';
  }

  return `Reservation is currently marked as ${status}.`;
}

function formatReservationScheduleRange(startValue, endValue) {
  const formattedStart = formatReservationScheduleDate(startValue);
  const formattedEnd = formatReservationScheduleDate(endValue);

  if (formattedStart && formattedEnd) {
    return formattedStart === formattedEnd
      ? formattedStart
      : `${formattedStart} - ${formattedEnd}`;
  }

  return formattedStart || formattedEnd || 'N/A';
}

function formatReservationScheduleDate(value) {
  if (!value) {
    return '';
  }

  const parsedValue = new Date(value);

  if (Number.isNaN(parsedValue.getTime())) {
    return '';
  }

  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    timeZone: 'Asia/Manila',
  }).format(parsedValue);
}
