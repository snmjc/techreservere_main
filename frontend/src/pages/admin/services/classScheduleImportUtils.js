import * as XLSX from 'xlsx';



export const importScheduleTargetOptions = [
  { key: 'venueReference', label: 'Room / Venue', required: true },
  { key: 'blockDate', label: 'Single Date' },
  { key: 'dateRangeStart', label: 'Recurring Start Date' },
  { key: 'dateRangeEnd', label: 'Recurring End Date' },
  { key: 'daysOfWeek', label: 'Days of Week' },
  { key: 'startTime', label: 'Start Time', required: true },
  { key: 'endTime', label: 'End Time', required: true },
  { key: 'courseCode', label: 'Course Code' },
  { key: 'courseName', label: 'Course Name' },
  { key: 'instructorName', label: 'Instructor' },
  { key: 'blockType', label: 'Schedule Type' },
  { key: 'academicYear', label: 'Academic Year' },
  { key: 'semesterLabel', label: 'Semester' },
  { key: 'capacityLimit', label: 'Capacity' },
  { key: 'notes', label: 'Notes' },
  { key: 'blockLabel', label: 'Schedule Label' },
];
const importScheduleTargetAliases = {
  venueReference: ['venue', 'room', 'classroom', 'venue name', 'room name', 'venue id', 'room id', 'venue identifier', 'room identifier'],
  blockDate: ['date', 'class date', 'block date', 'schedule date'],
  dateRangeStart: ['start date', 'date start', 'from date', 'range start', 'recurring start date'],
  dateRangeEnd: ['end date', 'date end', 'to date', 'range end', 'recurring end date'],
  daysOfWeek: ['days', 'day', 'weekday', 'weekdays', 'days of week', 'meeting days'],
  startTime: ['start time', 'time start', 'starts at', 'from time'],
  endTime: ['end time', 'time end', 'ends at', 'to time'],
  courseCode: ['course code', 'subject code', 'code', 'class code'],
  courseName: ['course name', 'subject name', 'course', 'subject', 'class name'],
  instructorName: ['instructor', 'faculty', 'teacher', 'professor', 'instructor name'],
  blockType: ['type', 'schedule type', 'block type', 'status'],
  academicYear: ['academic year', 'ay', 'school year'],
  semesterLabel: ['semester', 'term'],
  capacityLimit: ['capacity', 'capacity limit', 'slots', 'seat limit'],
  notes: ['notes', 'remarks', 'comment', 'comments', 'description'],
  blockLabel: ['label', 'block label', 'schedule label', 'title'],
};

export async function parseImportedScheduleFile(file) {
  const fileName = String(file?.name || '').toLowerCase();
  if (fileName.endsWith('.csv')) {
    return normalizeImportedTable(parseCsvText(await readFileAsText(file)));
  }

  if (fileName.endsWith('.xlsx') || fileName.endsWith('.xls')) {
    const workbookBuffer = await readFileAsArrayBuffer(file);
    const workbook = XLSX.read(workbookBuffer, { type: 'array', cellDates: false });
    if (!Array.isArray(workbook.SheetNames) || workbook.SheetNames.length === 0) {
      throw new Error('The workbook does not contain any sheets.');
    }

    const bestSheetCandidate = workbook.SheetNames
      .map((sheetName) => {
        const sheet = workbook.Sheets[sheetName];
        const rawRows = XLSX.utils.sheet_to_json(sheet, { header: 1, raw: false, defval: '' });
        const candidate = extractImportedTableCandidate(rawRows);

        return {
          sheetName,
          ...candidate,
        };
      })
      .sort((leftSheet, rightSheet) => {
        if (leftSheet.headerKeywordScore !== rightSheet.headerKeywordScore) {
          return rightSheet.headerKeywordScore - leftSheet.headerKeywordScore;
        }

        if (leftSheet.headers.length !== rightSheet.headers.length) {
          return rightSheet.headers.length - leftSheet.headers.length;
        }

        if (leftSheet.rows.length !== rightSheet.rows.length) {
          return rightSheet.rows.length - leftSheet.rows.length;
        }

        return leftSheet.sheetName.localeCompare(rightSheet.sheetName);
      })[0];

    return {
      headers: bestSheetCandidate?.headers || [],
      rows: bestSheetCandidate?.rows || [],
      metadata: {
        sheetName: bestSheetCandidate?.sheetName || '',
        headerRowNumber: bestSheetCandidate?.headerRowNumber || 0,
        headerKeywordScore: bestSheetCandidate?.headerKeywordScore || 0,
      },
    };
  }

  throw new Error('Unsupported file type. Please upload a CSV or Excel file.');
}

function normalizeImportedTable(rawRows) {
  const candidate = extractImportedTableCandidate(rawRows);
  return {
    headers: candidate.headers,
    rows: candidate.rows,
    metadata: {
      sheetName: 'CSV Upload',
      headerRowNumber: candidate.headerRowNumber,
      headerKeywordScore: candidate.headerKeywordScore,
    },
  };
}

function extractImportedTableCandidate(rawRows) {
  const normalizedRows = Array.isArray(rawRows)
    ? rawRows.map((row) => Array.isArray(row) ? row.map((cellValue) => normalizeImportedCellValue(cellValue)) : [])
    : [];
  const headerAnalysis = resolveImportedHeaderRowIndex(normalizedRows);
  const firstDataIndex = headerAnalysis.index;

  if (firstDataIndex < 0) {
    return {
      headers: [],
      rows: [],
      headerKeywordScore: 0,
      headerRowNumber: 0,
    };
  }

  const headers = createImportedHeaders(normalizedRows[firstDataIndex]);
  const rows = normalizedRows
    .slice(firstDataIndex + 1)
    .filter((row) => row.some((cellValue) => cellValue !== ''))
    .map((row) => headers.map((_, headerIndex) => normalizeImportedCellValue(row[headerIndex] ?? '')));

  return {
    headers,
    rows,
    headerKeywordScore: headerAnalysis.keywordScore,
    headerRowNumber: firstDataIndex + 1,
  };
}

function resolveImportedHeaderRowIndex(normalizedRows) {
  const candidateRows = normalizedRows
    .map((row, index) => ({
      index,
      nonEmptyCells: row.filter((cellValue) => cellValue !== '').length,
      keywordScore: row.reduce((score, cellValue) => score + scoreImportedHeaderCell(cellValue), 0),
      populatedCellRatio: row.length > 0 ? row.filter((cellValue) => cellValue !== '').length / row.length : 0,
    }))
    .filter((rowMeta) => rowMeta.nonEmptyCells > 0)
    .slice(0, 250);

  if (candidateRows.length === 0) {
    return { index: -1, keywordScore: 0 };
  }

  const keywordMatches = candidateRows.filter((rowMeta) => rowMeta.keywordScore > 0);
  if (keywordMatches.length > 0) {
    keywordMatches.sort((leftRow, rightRow) => {
      if (leftRow.keywordScore !== rightRow.keywordScore) {
        return rightRow.keywordScore - leftRow.keywordScore;
      }

      if (leftRow.nonEmptyCells !== rightRow.nonEmptyCells) {
        return rightRow.nonEmptyCells - leftRow.nonEmptyCells;
      }

      if (leftRow.populatedCellRatio !== rightRow.populatedCellRatio) {
        return rightRow.populatedCellRatio - leftRow.populatedCellRatio;
      }

      return leftRow.index - rightRow.index;
    });

    return {
      index: keywordMatches[0]?.index ?? -1,
      keywordScore: keywordMatches[0]?.keywordScore ?? 0,
    };
  }

  candidateRows.sort((leftRow, rightRow) => {
    if (leftRow.nonEmptyCells !== rightRow.nonEmptyCells) {
      return rightRow.nonEmptyCells - leftRow.nonEmptyCells;
    }

    if (leftRow.populatedCellRatio !== rightRow.populatedCellRatio) {
      return rightRow.populatedCellRatio - leftRow.populatedCellRatio;
    }

    return leftRow.index - rightRow.index;
  });

  return {
    index: candidateRows[0]?.index ?? -1,
    keywordScore: candidateRows[0]?.keywordScore ?? 0,
  };
}

function scoreImportedHeaderCell(cellValue) {
  const normalizedCell = normalizeImportAliasKey(cellValue);
  if (!normalizedCell) {
    return 0;
  }

  const knownHeaderLabels = importScheduleTargetOptions.flatMap((targetOption) => [
    targetOption.label,
    ...(importScheduleTargetAliases[targetOption.key] || []),
  ]);

  return knownHeaderLabels.some((headerLabel) => normalizeImportAliasKey(headerLabel) === normalizedCell) ? 1 : 0;
}

function createImportedHeaders(headerRow) {
  const usedHeaders = new Map();

  return headerRow.map((headerValue, headerIndex) => {
    const baseHeader = normalizeImportedText(headerValue) || `Column ${headerIndex + 1}`;
    const existingCount = usedHeaders.get(baseHeader) || 0;
    usedHeaders.set(baseHeader, existingCount + 1);
    return existingCount === 0 ? baseHeader : `${baseHeader} (${existingCount + 1})`;
  });
}

export function buildInitialImportHeaderSelections(headers) {
  const nextHeaderSelections = {};
  const autoMappedHeaders = {};
  const claimedTargets = new Set();

  headers.forEach((header) => {
    const matchedTarget = matchImportTargetForHeader(header);
    if (matchedTarget && !claimedTargets.has(matchedTarget)) {
      nextHeaderSelections[header] = matchedTarget;
      claimedTargets.add(matchedTarget);
      autoMappedHeaders[header] = true;
    } else {
      nextHeaderSelections[header] = '';
    }
  });

  return {
    headerSelections: nextHeaderSelections,
    autoMappedHeaders,
  };
}

function matchImportTargetForHeader(header) {
  const normalizedHeader = normalizeImportAliasKey(header);

  return importScheduleTargetOptions.find((targetOption) => (
    normalizedHeader === normalizeImportAliasKey(targetOption.label)
    || (importScheduleTargetAliases[targetOption.key] || []).some((alias) => normalizeImportAliasKey(alias) === normalizedHeader)
  ))?.key || '';
}

export function normalizeImportedText(value) {
  return String(value ?? '').trim();
}

function normalizeImportedCellValue(value) {
  if (value === null || value === undefined) {
    return '';
  }

  return String(value).trim();
}

export function normalizeImportedCapacity(value) {
  const normalizedValue = normalizeImportedText(value);
  if (!normalizedValue) {
    return null;
  }

  if (!/^-?\d+$/.test(normalizedValue)) {
    return 'invalid';
  }

  return Number(normalizedValue);
}

export function normalizeImportedDate(value) {
  const normalizedValue = normalizeImportedText(value);
  if (!normalizedValue) {
    return '';
  }

  const numericValue = Number(normalizedValue);
  if (Number.isFinite(numericValue) && normalizedValue !== '') {
    const excelEpoch = new Date(Date.UTC(1899, 11, 30));
    excelEpoch.setUTCDate(excelEpoch.getUTCDate() + numericValue);
    return excelEpoch.toISOString().slice(0, 10);
  }

  const parsedDate = new Date(normalizedValue);
  if (Number.isNaN(parsedDate.getTime())) {
    return '';
  }

  return `${parsedDate.getFullYear()}-${String(parsedDate.getMonth() + 1).padStart(2, '0')}-${String(parsedDate.getDate()).padStart(2, '0')}`;
}

export function normalizeImportedTime(value) {
  const normalizedValue = normalizeImportedText(value);
  if (!normalizedValue) {
    return '';
  }

  if (/^\d{1,2}:\d{2}$/.test(normalizedValue)) {
    const [hourValue, minuteValue] = normalizedValue.split(':').map(Number);
    if (hourValue >= 0 && hourValue <= 23 && minuteValue >= 0 && minuteValue <= 59) {
      return `${String(hourValue).padStart(2, '0')}:${String(minuteValue).padStart(2, '0')}`;
    }
  }

  const timeMatch = normalizedValue.match(/^(\d{1,2})(?::(\d{2}))?\s*(am|pm)$/i);
  if (timeMatch) {
    let hourValue = Number(timeMatch[1]);
    const minuteValue = Number(timeMatch[2] || '00');
    const meridiem = timeMatch[3].toLowerCase();
    if (meridiem === 'pm' && hourValue < 12) hourValue += 12;
    if (meridiem === 'am' && hourValue === 12) hourValue = 0;
    if (hourValue >= 0 && hourValue <= 23 && minuteValue >= 0 && minuteValue <= 59) {
      return `${String(hourValue).padStart(2, '0')}:${String(minuteValue).padStart(2, '0')}`;
    }
  }

  const numericValue = Number(normalizedValue);
  if (Number.isFinite(numericValue) && numericValue >= 0 && numericValue < 1) {
    const totalMinutes = Math.round(numericValue * 24 * 60);
    const hourValue = Math.floor(totalMinutes / 60) % 24;
    const minuteValue = totalMinutes % 60;
    return `${String(hourValue).padStart(2, '0')}:${String(minuteValue).padStart(2, '0')}`;
  }

  const parsedTime = new Date(`1970-01-01T${normalizedValue}`);
  if (!Number.isNaN(parsedTime.getTime())) {
    return `${String(parsedTime.getHours()).padStart(2, '0')}:${String(parsedTime.getMinutes()).padStart(2, '0')}`;
  }

  return '';
}

export function normalizeImportedDayList(value) {
  const normalizedValue = normalizeImportedText(value);
  if (!normalizedValue) {
    return [];
  }

  const dayMap = {
    mon: 'Monday',
    monday: 'Monday',
    tue: 'Tuesday',
    tues: 'Tuesday',
    tuesday: 'Tuesday',
    wed: 'Wednesday',
    weds: 'Wednesday',
    wednesday: 'Wednesday',
    thu: 'Thursday',
    thur: 'Thursday',
    thurs: 'Thursday',
    thursday: 'Thursday',
    fri: 'Friday',
    friday: 'Friday',
    sat: 'Saturday',
    saturday: 'Saturday',
    sun: 'Sunday',
    sunday: 'Sunday',
  };

  return Array.from(new Set(
    normalizedValue
      .split(/[,/|]+/)
      .map((dayValue) => dayMap[normalizeImportAliasKey(dayValue)] || '')
      .filter(Boolean)
  ));
}

export function countRecurringImportMatches(startDate, endDate, daysOfWeek) {
  if (!startDate || !endDate || !Array.isArray(daysOfWeek) || daysOfWeek.length === 0) {
    return 0;
  }

  const dayNumbers = {
    Monday: 1,
    Tuesday: 2,
    Wednesday: 3,
    Thursday: 4,
    Friday: 5,
    Saturday: 6,
    Sunday: 7,
  };
  const selectedDayNumbers = daysOfWeek
    .map((dayName) => dayNumbers[dayName] || 0)
    .filter(Boolean);

  if (selectedDayNumbers.length === 0) {
    return 0;
  }

  const startValue = new Date(`${startDate}T00:00:00Z`);
  const endValue = new Date(`${endDate}T00:00:00Z`);
  if (Number.isNaN(startValue.getTime()) || Number.isNaN(endValue.getTime()) || startValue > endValue) {
    return 0;
  }

  let matchCount = 0;
  const cursor = new Date(startValue);

  while (cursor <= endValue) {
    const weekdayNumber = cursor.getUTCDay() === 0 ? 7 : cursor.getUTCDay();
    if (selectedDayNumbers.includes(weekdayNumber)) {
      matchCount += 1;
    }

    cursor.setUTCDate(cursor.getUTCDate() + 1);
  }

  return matchCount;
}

export function normalizeImportAliasKey(value) {
  return normalizeImportedText(value).toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
}

export function createEmptyImportDetectionMetadata() {
  return {
    sheetName: '',
    headerRowNumber: 0,
    headerKeywordScore: 0,
  };
}

export function downloadClassScheduleImportTemplate() {
  const templateRows = [
    [
      'Room / Venue',
      'Single Date',
      'Recurring Start Date',
      'Recurring End Date',
      'Days of Week',
      'Start Time',
      'End Time',
      'Course Code',
      'Course Name',
      'Instructor',
      'Schedule Type',
      'Academic Year',
      'Semester',
      'Capacity',
      'Notes',
      'Schedule Label',
    ],
    [
      'F704',
      '2026-06-27',
      '',
      '',
      '',
      '07:00 AM',
      '09:50 AM',
      'CCS0043L',
      'Capstone Defense',
      'Prof. Cruz',
      'Class Schedule',
      '2026-2027',
      '1st Semester',
      '40',
      'Single-date example row',
      'TW21 - CCS0043L - M',
    ],
    [
      'F704',
      '',
      '2026-06-26',
      '2026-07-21',
      'Mon, Wed',
      '07:00 AM',
      '09:50 AM',
      'CCS0043L',
      'Capstone Defense',
      'Prof. Cruz',
      'Class Schedule',
      '2026-2027',
      '1st Semester',
      '40',
      'Recurring example row',
      'TW21 - CCS0043L - M',
    ],
  ];
  const csvContent = templateRows
    .map((rowValues) => rowValues.map((cellValue) => `"${String(cellValue ?? '').replace(/"/g, '""')}"`).join(','))
    .join('\r\n');

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const objectUrl = URL.createObjectURL(blob);
  const downloadLink = document.createElement('a');
  downloadLink.href = objectUrl;
  downloadLink.download = 'classroom-schedule-import-template.csv';
  document.body.appendChild(downloadLink);
  downloadLink.click();
  document.body.removeChild(downloadLink);
  URL.revokeObjectURL(objectUrl);
}

function readFileAsText(file) {
  return new Promise((resolve, reject) => {
    const fileReader = new FileReader();
    fileReader.onload = () => resolve(String(fileReader.result || ''));
    fileReader.onerror = () => reject(new Error('Failed to read the selected file.'));
    fileReader.readAsText(file);
  });
}

function readFileAsArrayBuffer(file) {
  return new Promise((resolve, reject) => {
    const fileReader = new FileReader();
    fileReader.onload = () => resolve(fileReader.result);
    fileReader.onerror = () => reject(new Error('Failed to read the selected file.'));
    fileReader.readAsArrayBuffer(file);
  });
}

function parseCsvText(csvText) {
  const rows = [];
  let currentRow = [];
  let currentCell = '';
  let isInsideQuotes = false;

  for (let index = 0; index < csvText.length; index += 1) {
    const currentCharacter = csvText[index];
    const nextCharacter = csvText[index + 1];

    if (currentCharacter === '"') {
      if (isInsideQuotes && nextCharacter === '"') {
        currentCell += '"';
        index += 1;
      } else {
        isInsideQuotes = !isInsideQuotes;
      }
      continue;
    }

    if (currentCharacter === ',' && !isInsideQuotes) {
      currentRow.push(currentCell);
      currentCell = '';
      continue;
    }

    if ((currentCharacter === '\n' || currentCharacter === '\r') && !isInsideQuotes) {
      if (currentCharacter === '\r' && nextCharacter === '\n') {
        index += 1;
      }
      currentRow.push(currentCell);
      rows.push(currentRow);
      currentRow = [];
      currentCell = '';
      continue;
    }

    currentCell += currentCharacter;
  }

  if (currentCell !== '' || currentRow.length > 0) {
    currentRow.push(currentCell);
    rows.push(currentRow);
  }

  return rows;
}

