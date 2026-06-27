export const ADMIN_ANALYTICS_RANGE_PRESETS = [
  { key: '30d', label: 'Last 30 Days', days: 30 },
  { key: '14d', label: 'Last 14 Days', days: 14 },
  { key: '7d', label: 'Last 7 days', days: 7 },
];

export function resolveAdminAnalyticsDateRange(presetKey, now = new Date()) {
  const preset = ADMIN_ANALYTICS_RANGE_PRESETS.find((item) => item.key === presetKey) || ADMIN_ANALYTICS_RANGE_PRESETS[0];
  if (preset.startDateIso && preset.endDateIso) {
    const startDate = parseDateOnly(preset.startDateIso);
    const endDate = parseDateOnly(preset.endDateIso);
    const days = Math.max(1, Math.round((endDate.getTime() - startDate.getTime()) / 86400000) + 1);
    return {
      days,
      startDate,
      endDate,
      startDateIso: preset.startDateIso,
      endDateIso: preset.endDateIso,
    };
  }

  const endDate = startOfDay(now);
  let startDate = startOfDay(now);

  startDate.setDate(startDate.getDate() - Math.max(0, (preset.days || 30) - 1));

  return {
    days: preset.days,
    startDate,
    endDate,
    startDateIso: formatDateIso(startDate),
    endDateIso: formatDateIso(endDate),
  };
}

export function formatDateIso(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

export function formatDateRangeLabel(startDateIso, endDateIso) {
  if (!startDateIso || !endDateIso) return 'No range selected';
  const formatter = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  return `${formatter.format(parseDateOnly(startDateIso))} - ${formatter.format(parseDateOnly(endDateIso))}`;
}

export function formatMetricNumber(value, digits = 0) {
  const number = Number(value);
  if (!Number.isFinite(number)) return '0';
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: digits,
    maximumFractionDigits: digits,
  }).format(number);
}

export function formatMetricDelta(value, digits = 1, suffix = '%') {
  const number = Number(value);
  if (!Number.isFinite(number)) return `0${suffix}`;
  const prefix = number > 0 ? '+' : '';
  return `${prefix}${formatMetricNumber(number, digits)}${suffix}`;
}

export function formatLeadTimeHours(value) {
  const number = Number(value);
  if (!Number.isFinite(number) || number <= 0) return '0 hrs';
  return `${formatMetricNumber(number, 1)} hrs`;
}

export function buildLineChartModel(series = [], options = {}) {
  const width = options.width || 720;
  const height = options.height || 280;
  const padding = {
    top: options.paddingTop ?? 24,
    right: options.paddingRight ?? 18,
    bottom: options.paddingBottom ?? 40,
    left: options.paddingLeft ?? 56,
  };
  const values = series.map((item) => Number(item?.value ?? item?.demand ?? 0));
  const maxValue = Math.max(options.minMaxValue || 0, ...values, 1);
  const plotWidth = width - padding.left - padding.right;
  const plotHeight = height - padding.top - padding.bottom;

  if (series.length === 0) {
    return {
      width,
      height,
      polylinePoints: '',
      areaPath: '',
      yAxisLabels: buildYAxisLabels(maxValue),
      xAxisLabels: [],
      pointMarkers: [],
      gridLinesY: [],
      chartBounds: {
        left: padding.left,
        right: width - padding.right,
      },
    };
  }

  const points = series.map((item, index) => {
    const x = padding.left + ((plotWidth / Math.max(1, series.length - 1)) * index);
    const numericValue = Number(item?.value ?? item?.demand ?? 0);
    const y = padding.top + plotHeight - ((numericValue / maxValue) * plotHeight);
    return { x, y, value: numericValue, label: item?.label || item?.date || '' };
  });
  const polylinePoints = points.map(({ x, y }) => `${x},${y}`).join(' ');
  const areaPath = points.length > 0
    ? `M${points[0].x} ${padding.top + plotHeight} L${points.map(({ x, y }) => `${x} ${y}`).join(' L')} L${points[points.length - 1].x} ${padding.top + plotHeight} Z`
    : '';
  const xAxisLabels = sampleAxisLabels(points, options.maxXAxisLabels || 6, height - 6);
  const yAxisLabels = buildYAxisLabels(maxValue).map((item, index, items) => ({
    value: item,
    x: padding.left - 12,
    y: padding.top + ((plotHeight / Math.max(1, items.length - 1)) * index) + 4,
  }));
  const gridLinesY = buildGridLinePositions(padding.top, plotHeight, Math.max(1, yAxisLabels.length - 1));
  const pointMarkers = points.filter((_, index) => (
    index === 0 || index === points.length - 1 || index % Math.max(1, Math.ceil(points.length / 4)) === 0
  ));

  return {
    width,
    height,
    polylinePoints,
    areaPath,
    yAxisLabels,
    xAxisLabels,
    pointMarkers,
    gridLinesY,
    chartBounds: {
      left: padding.left,
      right: width - padding.right,
    },
  };
}

export function buildDualLineChartModel(actualSeries = [], forecastSeries = [], options = {}) {
  const width = options.width || 760;
  const height = options.height || 260;
  const padding = {
    top: options.paddingTop ?? 30,
    right: options.paddingRight ?? 30,
    bottom: options.paddingBottom ?? 30,
    left: options.paddingLeft ?? 52,
  };
  const normalizedSeries = normalizeDualSeries(actualSeries, forecastSeries, options.maxDataPoints || 14);
  const labels = normalizedSeries.labels;
  const actualValues = normalizedSeries.actualValues;
  const forecastValues = normalizedSeries.forecastValues;
  const maxValue = Math.max(1, ...actualValues, ...forecastValues);
  const plotWidth = width - padding.left - padding.right;
  const plotHeight = height - padding.top - padding.bottom;

  const buildPoints = (values) => values.map((value, index) => ({
    x: padding.left + ((plotWidth / Math.max(1, values.length - 1)) * index),
    y: padding.top + plotHeight - ((value / maxValue) * plotHeight),
  }));

  const actualPoints = buildPoints(actualValues);
  const forecastPoints = buildPoints(forecastValues);

  return {
    width,
    height,
    actualPolylinePoints: actualPoints.map(({ x, y }) => `${x},${y}`).join(' '),
    forecastPolylinePoints: forecastPoints.map(({ x, y }) => `${x},${y}`).join(' '),
    xAxisLabels: sampleAxisLabels(labels.map((label, index) => ({
      x: padding.left + ((plotWidth / Math.max(1, labels.length - 1)) * index),
      label,
    })), options.maxXAxisLabels || 6, height - 8, true),
    yAxisLabels: buildYAxisLabels(maxValue).map((item, index, items) => ({
      value: item,
      x: 18,
      y: padding.top + ((plotHeight / Math.max(1, items.length - 1)) * index) + 4,
    })),
    gridLinesY: buildGridLinePositions(padding.top, plotHeight, 5),
    gridLinesX: buildGridLinePositions(padding.left, plotWidth, Math.max(1, labels.length - 1), true),
  };
}

function normalizeDualSeries(actualSeries, forecastSeries, maxDataPoints) {
  const length = Math.max(actualSeries.length, forecastSeries.length);
  const labels = Array.from({ length }, (_, index) => (
    actualSeries[index]?.label || forecastSeries[index]?.label || ''
  ));
  const actualValues = Array.from({ length }, (_, index) => Number(actualSeries[index]?.value ?? 0));
  const forecastValues = Array.from({ length }, (_, index) => Number(forecastSeries[index]?.value ?? 0));

  if (length <= maxDataPoints) {
    return { labels, actualValues, forecastValues };
  }

  const bucketSize = Math.ceil(length / maxDataPoints);
  const normalizedLabels = [];
  const normalizedActualValues = [];
  const normalizedForecastValues = [];

  for (let start = 0; start < length; start += bucketSize) {
    const end = Math.min(length, start + bucketSize);
    const actualBucket = actualValues.slice(start, end);
    const forecastBucket = forecastValues.slice(start, end);
    normalizedLabels.push(labels[start]);
    normalizedActualValues.push(roundChartValue(averageChartValue(actualBucket)));
    normalizedForecastValues.push(roundChartValue(averageChartValue(forecastBucket)));
  }

  return {
    labels: normalizedLabels,
    actualValues: normalizedActualValues,
    forecastValues: normalizedForecastValues,
  };
}

function averageChartValue(values) {
  if (values.length === 0) return 0;
  return values.reduce((sum, value) => sum + value, 0) / values.length;
}

function roundChartValue(value) {
  return Math.round(value * 10) / 10;
}

export function buildRiskDonutStyle(bands = []) {
  const total = bands.reduce((sum, band) => sum + Number(band?.count || 0), 0);
  if (total <= 0) {
    return { background: 'conic-gradient(#d1d5db 0 100%)' };
  }

  let cursor = 0;
  const segments = bands.map((band) => {
    const percentage = (Number(band?.count || 0) / total) * 100;
    const start = cursor;
    cursor += percentage;
    return `${band.color} ${start}% ${cursor}%`;
  });

  return {
    background: `conic-gradient(${segments.join(', ')})`,
  };
}

function sampleAxisLabels(points, maxLabels, yPosition, precomputed = false) {
  if (points.length === 0) return [];
  const step = Math.max(1, Math.ceil(points.length / maxLabels));
  const labels = points
    .filter((_, index) => index === 0 || index === points.length - 1 || index % step === 0)
    .map((point) => ({
      x: precomputed ? point.x : point.x,
      y: yPosition,
      label: precomputed ? point.label : point.label,
    }));

  if (labels.length >= 2) {
    const previousLabel = labels[labels.length - 2];
    const lastLabel = labels[labels.length - 1];
    if (Math.abs(lastLabel.x - previousLabel.x) < 44) {
      labels.splice(labels.length - 2, 1);
    }
  }

  return labels;
}

function buildYAxisLabels(maxValue) {
  return Array.from({ length: 5 }, (_, index) => {
    const factor = 4 - index;
    return Math.round((maxValue / 4) * factor);
  });
}

function buildGridLinePositions(origin, distance, segments, vertical = false) {
  return Array.from({ length: segments + 1 }, (_, index) => {
    const value = origin + ((distance / Math.max(1, segments)) * index);
    return vertical ? { x: value } : { y: value };
  });
}

function startOfDay(date) {
  const next = new Date(date);
  next.setHours(0, 0, 0, 0);
  return next;
}

export function parseDateOnly(value) {
  if (typeof value !== 'string' || !value) return new Date(NaN);
  const [year, month, day] = value.split('-').map((part) => Number(part));
  if (!Number.isFinite(year) || !Number.isFinite(month) || !Number.isFinite(day)) {
    return new Date(value);
  }

  return new Date(year, month - 1, day);
}
