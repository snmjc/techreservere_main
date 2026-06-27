const PENDING_STATUSES = ['pending review', 'pending', 'submitted'];
const SCHEDULED_STATUSES = ['approved', 'prepared', 'deployed', 'active'];
const PAST_RECORD_STATUSES = [
  'completed',
  'rejected',
  'cancelled',
  'canceled',
  'returned',
  'released',
  'closed',
  'expired',
  'request revision',
];
const ACTIVE_QUEUE_STATUSES = ['deployed', 'active'];

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

export function buildReservationBuckets(apiReservations = [], taskRecords = []) {
  const buckets = createEmptyReservationBuckets();
  const taskRecordsByReservation = groupTaskRecordsByReservation(taskRecords);

  apiReservations.forEach((reservation) => {
    addReservationToBucket(
      buckets,
      reservation,
      taskRecordsByReservation.get(Number(reservation?.reservationIdentifier || 0)) || [],
    );
  });

  return buckets;
}

export function sortReservationRecords(records = [], bucketType = 'pending', direction = getDefaultSortDirection(bucketType)) {
  return [...(Array.isArray(records) ? records : [])].sort((leftRecord, rightRecord) =>
    compareReservationRecords(leftRecord, rightRecord, bucketType, direction)
  );
}

function createEmptyReservationBuckets() {
  return {
    pending: [],
    approved: [],
    active: [],
    past: [],
  };
}

function addReservationToBucket(buckets, reservation, linkedTasks = []) {
  const mappedRecord = mapReservationRecord(reservation, linkedTasks);
  const status = String(reservation?.currentStatus || '').trim();
  addReservationRecordToBuckets(buckets, mappedRecord, status, reservation);
}

function mapReservationRecord(reservation, linkedTasks = []) {
  const requestedEquipmentList = Array.isArray(reservation?.requestedEquipmentList)
    ? reservation.requestedEquipmentList
    : [];
  const reservationSummary = mapRequestedEquipment(requestedEquipmentList);
  const requestType = resolveRequestType(reservation, requestedEquipmentList);
  const workflowSummary = buildWorkflowSummary(linkedTasks);
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
    activityTime: requestScheduleStart || 'N/A',
    activityEndTime: requestScheduleEnd || requestScheduleStart || 'N/A',
    activityNameTitle: reservation?.activityType || 'N/A',
    participantCount: reservation?.requestedQuantity || 0,
    requestStatus: reservation?.currentStatus || 'Unknown',
    borrowerRemarks: reservation?.borrowerRemarks || '',
    cancellationReason: reservation?.rejectionReason || '',
    remarks: reservation?.rejectionReason || buildReservationRemark(reservation),
    uploadedDocuments: mapUploadedDocuments(reservation?.supportingDocuments),
    reservationSummary,
    workflowTasks: linkedTasks,
    workflowTaskCount: linkedTasks.length,
    workflowSummary,
    assignedPersonnel: workflowSummary.assignedPersonnel,
    workflowStatus: workflowSummary.workflowStatus,
    workflowTaskIdentifier: workflowSummary.taskIdentifier,
    workflowTaskTitle: workflowSummary.taskTitle,
    workflowTaskType: workflowSummary.taskType,
    workflowDueDateTimestamp: workflowSummary.dueDateTimestamp,
    reservedResources: buildReservedResources(reservation, requestedEquipmentList),
    scheduleBucket: resolveReservationScheduleState(reservation),
  };
}

function normalizeReservationStatus(status) {
  return String(status || '').trim().toLowerCase();
}

function compareReservationRecords(leftRecord, rightRecord, bucketType, direction) {
  const leftValue = resolveReservationSortValue(leftRecord, bucketType);
  const rightValue = resolveReservationSortValue(rightRecord, bucketType);

  if (leftValue !== rightValue) {
    return direction === 'asc'
      ? leftValue - rightValue
      : rightValue - leftValue;
  }

  const leftIdentifier = resolveReservationIdentifier(leftRecord);
  const rightIdentifier = resolveReservationIdentifier(rightRecord);

  return direction === 'asc'
    ? leftIdentifier - rightIdentifier
    : rightIdentifier - leftIdentifier;
}

function resolveReservationSortValue(record, bucketType) {
  if (bucketType === 'approved' || bucketType === 'active') {
    return resolveReservationDateValue(
      record?.requestScheduleStart
        || record?.activityTime
        || record?.neededDate
        || record?.requestedDate
    );
  }

  return resolveReservationDateValue(record?.requestedDate || record?.requestScheduleStart || record?.activityTime);
}

function resolveReservationDateValue(value) {
  const parsedDate = parseReservationDate(value);
  return parsedDate ? parsedDate.getTime() : 0;
}

function resolveReservationIdentifier(record) {
  return Number(record?.requestIdentifier || record?.reservationIdentifier || 0);
}

function getDefaultSortDirection(bucketType) {
  return bucketType === 'pending' ? 'desc' : 'asc';
}

export function resolveReservationScheduleState(reservation) {
  const start = parseReservationDate(
    reservation?.eventDateTime
      || reservation?.requestScheduleStart
      || reservation?.activityTime
  );
  const end = parseReservationDate(
    reservation?.endDateTime
      || reservation?.requestScheduleEnd
      || reservation?.activityEndTime
  ) || start;
  if (!start || !end) {
    return 'upcoming';
  }

  const todayKey = formatReservationDayKey(new Date());
  const startKey = formatReservationDayKey(start);
  const endKey = formatReservationDayKey(end);

  if (startKey > todayKey) {
    return 'upcoming';
  }

  if (endKey < todayKey) {
    return 'past';
  }

  return 'active';
}

export function addReservationRecordToBuckets(buckets, record, status, scheduleSource = record) {
  const normalizedStatus = normalizeReservationStatus(status);
  const scheduleState = resolveReservationScheduleState(scheduleSource);

  if (PENDING_STATUSES.includes(normalizedStatus)) {
    buckets.pending.push(record);
    return;
  }

  if (PAST_RECORD_STATUSES.includes(normalizedStatus)) {
    buckets.past.push({
      ...record,
      recordStatus: status,
    });
    return;
  }

  if (SCHEDULED_STATUSES.includes(normalizedStatus)) {
    if (scheduleState === 'past') {
      buckets.past.push({
        ...record,
        recordStatus: status,
      });
      return;
    }

    const scheduledRecord = {
      ...record,
      assignedPersonnel: record.assignedPersonnel || 'Pending Assignment',
      scheduleBucket: scheduleState,
    };

    if (scheduleState === 'active' || ACTIVE_QUEUE_STATUSES.includes(normalizedStatus)) {
      buckets.active.push({
        ...scheduledRecord,
        deploymentStatus: resolveActiveDeploymentLabel(status),
      });
      return;
    }

    buckets.approved.push(scheduledRecord);
  }
}

function resolveActiveDeploymentLabel(status) {
  const normalizedStatus = String(status || '').trim().toLowerCase();
  if (normalizedStatus === 'deployed' || normalizedStatus === 'active') {
    return 'Ongoing Today';
  }

  return 'Scheduled for Today';
}

function parseReservationDate(value) {
  if (!value) {
    return null;
  }

  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function formatReservationDayKey(value) {
  return new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Manila',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(value);
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

function buildWorkflowSummary(linkedTasks = []) {
  if (!Array.isArray(linkedTasks) || linkedTasks.length === 0) {
    return {
      taskIdentifier: null,
      taskTitle: '',
      taskType: '',
      workflowStatus: 'Pending Assignment',
      dueDateTimestamp: '',
      assignedPersonnel: 'Pending Assignment',
    };
  }

  const sortedTasks = [...linkedTasks].sort((left, right) => {
    const leftPriority = getTaskSortWeight(left);
    const rightPriority = getTaskSortWeight(right);
    if (leftPriority !== rightPriority) {
      return leftPriority - rightPriority;
    }

    return Number(right?.taskIdentifier || 0) - Number(left?.taskIdentifier || 0);
  });

  const primaryTask = sortedTasks[0];
  const assignedPersonnelLabels = [...new Set(sortedTasks
    .map((task) => formatTaskAssignedPersonnel(task))
    .filter((value) => value !== 'Pending Assignment'))];

  return {
    taskIdentifier: primaryTask?.taskIdentifier || null,
    taskTitle: primaryTask?.taskTitle || '',
    taskType: primaryTask?.taskType || '',
    workflowStatus: primaryTask?.taskStatus || 'Pending Assignment',
    dueDateTimestamp: primaryTask?.dueDateTimestamp || '',
    assignedPersonnel: assignedPersonnelLabels.length > 0
      ? assignedPersonnelLabels.join(', ')
      : 'Pending Assignment',
  };
}

function getTaskSortWeight(task) {
  const status = String(task?.taskStatus || '').trim().toLowerCase();
  if (status === 'in progress') return 0;
  if (status === 'pending') return 1;
  if (status === 'completed') return 2;
  if (status === 'cancelled') return 3;
  return 4;
}

function formatTaskAssignedPersonnel(task) {
  const staffName = String(task?.assignedStaffName || '').trim();
  const staffId = String(task?.assignedStaffIdNumber || '').trim();

  if (staffName !== '' && staffId !== '') {
    return `${staffName} - ${staffId}`;
  }

  if (staffName !== '') {
    return staffName;
  }

  return 'Pending Assignment';
}

function groupTaskRecordsByReservation(taskRecords = []) {
  return (Array.isArray(taskRecords) ? taskRecords : []).reduce((taskMap, taskRecord) => {
    const reservationIdentifier = Number(taskRecord?.reservationIdentifier || 0);
    if (reservationIdentifier <= 0) {
      return taskMap;
    }

    const existingList = taskMap.get(reservationIdentifier) || [];
    existingList.push(taskRecord);
    taskMap.set(reservationIdentifier, existingList);
    return taskMap;
  }, new Map());
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
    return `${formattedStart} - ${formattedEnd}`;
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
