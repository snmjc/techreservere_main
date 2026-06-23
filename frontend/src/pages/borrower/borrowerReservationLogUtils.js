export function mapRequestRecordToLog(record, statusFallback = 'Reservation') {
  const scheduleStart = record?.requestScheduleStart || record?.activityTime || record?.neededDate || record?.requestedDate;
  const scheduleEnd = record?.requestScheduleEnd || record?.activityEndTime || scheduleStart;

  return {
    id: record?.requestIdentifier || record?.requestDisplayIdentifier,
    reservationId: String(record?.requestDisplayIdentifier || record?.requestIdentifier || 'N/A'),
    name: record?.requesterFullName || 'You',
    role: record?.requesterRole || 'Borrower',
    date: formatSchedule(scheduleStart, scheduleEnd),
    facility: record?.facilityName || 'N/A',
    type: record?.requestType || 'Reservation',
    purpose: record?.requestPurpose || 'N/A',
    status: record?.recordStatus || record?.requestStatus || statusFallback,
    submitted: formatDateTime(record?.requestedDate),
    approvedBy: 'Facilities Office',
    completed: formatDateTime(record?.neededDate || scheduleEnd),
    activity: buildActivityText(record, statusFallback),
    sortDate: getDateSortValue(scheduleStart),
  };
}

export function filterLogsBySearch(logs, query) {
  const normalizedQuery = String(query || '').toLowerCase().trim();

  if (!normalizedQuery) {
    return logs;
  }

  return logs.filter((log) =>
    [log.reservationId, log.name, log.facility, log.purpose, log.status]
      .some((value) => String(value || '').toLowerCase().includes(normalizedQuery))
  );
}

export function sortLogs(logs, sortBy, sortOrder) {
  return [...logs].sort((first, second) => {
    const firstValue = resolveSortValue(first, sortBy);
    const secondValue = resolveSortValue(second, sortBy);

    if (typeof firstValue === 'string' && typeof secondValue === 'string') {
      return sortOrder === 'asc'
        ? firstValue.localeCompare(secondValue)
        : secondValue.localeCompare(firstValue);
    }

    return sortOrder === 'asc' ? firstValue - secondValue : secondValue - firstValue;
  });
}

export function formatDateTime(value) {
  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return value || 'N/A';
  }

  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: 'numeric',
    minute: '2-digit',
  }).format(date);
}

export function formatSchedule(startValue, endValue) {
  const startDate = formatDateTime(startValue);
  const endDate = formatDateTime(endValue);

  if (startDate !== 'N/A' && endDate !== 'N/A') {
    return `${startDate} - ${endDate}`;
  }

  return startDate !== 'N/A' ? startDate : endDate;
}

function resolveSortValue(log, sortBy) {
  if (sortBy === 'name') return String(log.name || '').toLowerCase();
  if (sortBy === 'facility') return String(log.facility || '').toLowerCase();
  return log.sortDate || 0;
}

function getDateSortValue(value) {
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? 0 : date.getTime();
}

function buildActivityText(record, statusFallback) {
  const status = record?.recordStatus || record?.requestStatus || statusFallback;
  return `Reservation is currently marked as ${status}.`;
}
