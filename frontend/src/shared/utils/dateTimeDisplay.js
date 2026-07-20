const MANILA_TIME_ZONE = 'Asia/Manila';

export function formatDisplayDate(value) {
  if (!value) return 'N/A';

  const date = parseDisplayDate(value);
  if (Number.isNaN(date.getTime())) return 'N/A';

  return new Intl.DateTimeFormat('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    timeZone: MANILA_TIME_ZONE,
  }).format(date);
}

export function formatDisplayDateTime(value) {
  if (!value) return 'N/A';

  const date = parseDisplayDate(value);
  if (Number.isNaN(date.getTime())) return 'N/A';

  const formattedDateTime = new Intl.DateTimeFormat('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    timeZone: MANILA_TIME_ZONE,
  }).format(date);

  return `${formattedDateTime} PHT+8`;
}

export function formatNullableDateTime(value) {
  return value ? formatDisplayDateTime(value) : 'N/A';
}

export function formatDisplayDateRange(startValue, endValue) {
  const formattedStart = startValue ? formatDisplayDate(startValue) : '';
  const formattedEnd = endValue ? formatDisplayDate(endValue) : '';

  if (formattedStart && formattedEnd) {
    return `${formattedStart} - ${formattedEnd}`;
  }

  return formattedStart || formattedEnd || 'N/A';
}

export function formatDisplayDateTimeRange(startValue, endValue) {
  const formattedStart = startValue ? formatDisplayDateTime(startValue) : '';
  const formattedEnd = endValue ? formatDisplayDateTime(endValue) : '';

  if (formattedStart && formattedEnd) {
    return `${formattedStart} - ${formattedEnd}`;
  }

  return formattedStart || formattedEnd || 'N/A';
}

function parseDisplayDate(value) {
  const normalizedValue = String(value || '').trim();
  if (!normalizedValue) {
    return new Date(Number.NaN);
  }

  const normalizedTimestamp = normalizeTimestampSeparator(normalizedValue);
  const hasExplicitTimeZone = /([zZ]|[+-]\d{2}(?::?\d{2})?)$/.test(normalizedTimestamp);
  if (hasExplicitTimeZone) {
    return new Date(normalizeExplicitTimeZoneOffset(normalizedTimestamp));
  }

  if (/^\d{4}-\d{2}-\d{2}$/.test(normalizedTimestamp)) {
    return new Date(`${normalizedTimestamp}T00:00:00Z`);
  }

  return new Date(`${normalizedTimestamp}Z`);
}

function normalizeTimestampSeparator(value) {
  return value.includes('T') ? value : value.replace(' ', 'T');
}

function normalizeExplicitTimeZoneOffset(value) {
  return value
    .replace(/([+-]\d{2})$/, '$1:00')
    .replace(/([+-]\d{2})(\d{2})$/, '$1:$2');
}
