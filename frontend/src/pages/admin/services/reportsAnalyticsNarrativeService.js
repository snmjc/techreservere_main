import {
  formatMetricNumber,
  parseDateOnly,
} from '../adminAnalyticsHelpers.js';

export function buildRiskNarrative(bands = []) {
  const normalizedBands = Array.isArray(bands) ? bands : [];
  const getCount = (label) => Number(normalizedBands.find((band) => band?.label === label)?.count || 0);
  const totalEquipment = normalizedBands.reduce((sum, band) => sum + Number(band?.count || 0), 0);
  const highRisk = getCount('High Risk');
  const mediumRisk = getCount('Medium Risk');
  const lowRisk = getCount('Low Risk');
  const veryLowRisk = getCount('Very Low Risk');
  const safeEquipment = lowRisk + veryLowRisk;
  const attentionCount = highRisk + mediumRisk;
  const safeRate = totalEquipment > 0 ? Math.round((safeEquipment / totalEquipment) * 100) : 0;
  const dominantBand = [
    { label: 'Low Risk', count: lowRisk },
    { label: 'High Risk', count: highRisk },
    { label: 'Medium Risk', count: mediumRisk },
    { label: 'Very Low Risk', count: veryLowRisk },
  ].sort((left, right) => right.count - left.count)[0] || { label: 'Low Risk', count: 0 };

  const summary = totalEquipment > 0
    ? `Most tracked equipment is currently in a ${dominantBand.label} condition: ${formatMetricNumber(dominantBand.count, 0)} out of ${formatMetricNumber(totalEquipment, 0)} equipment items (${safeRate}% safe rate). ${attentionCount > 0 ? `${formatMetricNumber(attentionCount, 0)} items still require attention—${formatMetricNumber(highRisk, 0)} High Risk and ${formatMetricNumber(mediumRisk, 0)} Medium Risk.` : 'No equipment currently requires immediate escalation.'} ${veryLowRisk === 0 ? 'No equipment falls under Very Low Risk, which means all tracked assets still have at least one monitored operational risk factor.' : `${formatMetricNumber(veryLowRisk, 0)} equipment items are already in Very Low Risk status.`}`
    : 'No readiness risk data is available for the selected range.';

  const interpretation = totalEquipment > 0
    ? `Overall inventory readiness is ${attentionCount > 0 ? 'stable, but the High- and Medium-Risk equipment should be reviewed first' : 'stable'}. Prioritize items affected by inactive availability, low stock pressure, frequent use, or overdue release status to prevent disruption to reservations and operations.`
    : 'There is no risk distribution to interpret for this range.';

  return { summary, interpretation };
}

export function buildForecastNarrative(forecast = {}, actualSeries = [], forecastSeries = []) {
  const actualValues = Array.isArray(actualSeries) ? actualSeries.map((item) => Number(item?.value || 0)) : [];
  const forecastValues = Array.isArray(forecastSeries) ? forecastSeries.map((item) => Number(item?.value || 0)) : [];
  const actualPeak = actualValues.length > 0 ? Math.max(...actualValues) : 0;
  const actualPeakIndex = actualValues.indexOf(actualPeak);
  const actualTailAverage = actualValues.length >= 3
    ? actualValues.slice(-3).reduce((sum, value) => sum + value, 0) / 3
    : actualValues.reduce((sum, value) => sum + value, 0);
  const forecastPeak = Number(forecast?.peakValue || (forecastValues.length > 0 ? Math.max(...forecastValues) : 0));
  const forecastPeakIndex = forecastValues.indexOf(forecastPeak);
  const forecastTailAverage = forecastValues.length >= 3
    ? forecastValues.slice(-3).reduce((sum, value) => sum + value, 0) / 3
    : forecastValues.reduce((sum, value) => sum + value, 0);
  const actualAverage = actualValues.length > 0 ? actualValues.reduce((sum, value) => sum + value, 0) / actualValues.length : 0;
  const forecastAverage = forecastValues.length > 0 ? forecastValues.reduce((sum, value) => sum + value, 0) / forecastValues.length : 0;
  const forecastLookAheadAverage = forecastValues.slice(-3).reduce((sum, value) => sum + value, 0) / Math.max(1, Math.min(3, forecastValues.length));
  const midpointTail = forecastValues.length >= 1
    ? forecastValues.slice(-3).map((value, index, values) => {
        const actualValue = actualValues.slice(-values.length)[index] ?? actualAverage;
        return (Number(actualValue || 0) + Number(value || 0)) / 2;
      })
    : [];
  const midpointTrend = midpointTail.length > 0 ? midpointTail.reduce((sum, value) => sum + value, 0) / midpointTail.length : 0;
  const growthPercent = Number(forecast?.growthPercent || 0);
  const peakDate = formatLongDate(forecast?.peakDate);
  const forecastGap = forecastPeak - actualPeak;
  const averageGap = forecastAverage - actualAverage;
  const peakInForecast = forecastPeakIndex >= 0 && actualPeakIndex >= 0 ? forecastPeakIndex >= actualPeakIndex : false;
  const isSmoothTrend = forecastValues.length >= 4 && Math.max(...forecastValues) - Math.min(...forecastValues) <= Math.max(2, forecastAverage * 0.25);

  const summary = actualValues.length > 0 || forecastValues.length > 0
    ? `This graph compares ${formatMetricNumber(actualValues.length, 0)} actual demand points with ${formatMetricNumber(forecastValues.length, 0)} forecast points over the selected range. The forecast peaks at ${formatMetricNumber(forecastPeak, 1)} requests on ${peakDate}, while actual demand peaks at ${formatMetricNumber(actualPeak, 1)} requests.`
    : 'No demand series is available for this range.';

  const tldr = forecastValues.length > 0
    ? [
        forecastLookAheadAverage >= actualAverage + 2
          ? `If the next 3 days stay above ${formatMetricNumber(actualAverage, 1)} requests, prepare more equipment and staff now.`
          : forecastLookAheadAverage <= actualAverage - 2
            ? `If the next 3 days fall below ${formatMetricNumber(actualAverage, 1)} requests, keep inventory lean and avoid over-preparing.`
            : `If the next 3 days stay near ${formatMetricNumber(actualAverage, 1)} requests, hold current allocation and monitor for changes.`,
        midpointTrend >= forecastLookAheadAverage
          ? 'If the midpoint trend stays above the look-ahead average, keep a cautious buffer because demand may flatten later.'
          : 'If the midpoint trend stays below the look-ahead average, move to a proactive allocation plan because demand may rise later.',
        forecastGap >= 3
          ? 'If the forecast peak is higher than the current peak, expect a sharper surge and stage resources earlier.'
          : forecastGap <= -3
            ? 'If the forecast peak is lower than the current peak, the surge may already be passing and the load should ease.'
            : 'If the forecast peak stays close to the current peak, keep the same operating rhythm and review daily.',
      ].join(' ')
    : 'No forecast trend is available yet for a decision check.';

  const interpretation = forecastValues.length > 0
    ? [
        growthPercent >= 15
          ? 'Demand is materially above the previous period, so reserve extra equipment earlier and watch for capacity pressure.'
          : growthPercent >= 0
            ? 'Demand is slightly above the previous period, so keep a moderate buffer and monitor the next booking cycle.'
            : 'Demand is below the previous period, so release surplus inventory cautiously and avoid overcommitting stock.',
        forecastGap >= 5
          ? 'The forecast peak is much higher than actual demand, which suggests a strong seasonal surge or a concentrated booking window.'
          : forecastGap >= 2
            ? 'The forecast peak is moderately higher than actual demand, which suggests a clear upward shift in request volume.'
            : forecastGap <= -2
              ? 'The forecast peak is below the current peak, so future demand may soften after the current burst.'
              : 'Forecast and actual peaks are close, so the next period should stay near the current operational pattern.',
        averageGap >= 3
          ? 'Average forecast volume is noticeably above actual demand, so this range should be staffed as a higher-demand cycle.'
          : averageGap <= -3
            ? 'Average forecast volume is noticeably below actual demand, so current usage may be tapering off.'
            : 'Average forecast volume is close to actual demand, so the overall pattern is steady.',
        forecastLookAheadAverage >= actualAverage + 2
          ? `The next 3 days average ${formatMetricNumber(forecastLookAheadAverage, 1)} requests, so prepare for a short-term rise in demand.`
          : forecastLookAheadAverage <= actualAverage - 2
            ? `The next 3 days average ${formatMetricNumber(forecastLookAheadAverage, 1)} requests, so demand should ease slightly.`
            : `The next 3 days average ${formatMetricNumber(forecastLookAheadAverage, 1)} requests, so demand should stay near the current baseline.`,
        midpointTrend >= forecastLookAheadAverage
          ? `The midpoint trend sits at ${formatMetricNumber(midpointTrend, 1)} requests, which leans closer to the actual demand line and supports a cautious allocation plan.`
          : `The midpoint trend sits at ${formatMetricNumber(midpointTrend, 1)} requests, which leans toward the forecast line and supports a more proactive allocation plan.`,
        peakInForecast
          ? 'The forecast peak arrives at or after the actual peak, which usually means the pressure is moving later in the period.'
          : 'The forecast peak appears earlier than the actual peak, which may indicate an early spike followed by a taper.',
        isSmoothTrend
          ? `The forecast line is relatively smooth, with a tail average of ${formatMetricNumber(forecastTailAverage, 1)} requests; that makes it better for base staffing plans.`
          : `The forecast line is volatile, with a tail average of ${formatMetricNumber(forecastTailAverage, 1)} requests; that supports a more flexible buffer plan.`,
      ].join(' ')
    : 'There is no forecast trend to interpret for this range.';

  return { tldr, summary, interpretation };
}

export function buildForecastDisplaySeries(actualSeries = [], forecastSeries = []) {
  const actualMap = new Map();
  const forecastMap = new Map();

  actualSeries.forEach((item) => {
    const key = item?.date || item?.label || '';
    if (key) actualMap.set(key, Number(item?.value || 0));
  });

  forecastSeries.forEach((item) => {
    const key = item?.date || item?.label || '';
    if (key) forecastMap.set(key, Number(item?.value || 0));
  });

  const dateComparator = (left, right) => parseDateOnly(left).getTime() - parseDateOnly(right).getTime();
  const actualKeys = [...actualMap.keys()].sort(dateComparator);
  const forecastKeys = [...forecastMap.keys()].sort(dateComparator);
  const lastActualKey = actualKeys[actualKeys.length - 1];
  const lastActualDate = lastActualKey ? parseDateOnly(lastActualKey) : null;
  const normalizedForecastKeys = forecastKeys.filter((key) => {
    if (!lastActualDate) return true;
    const date = parseDateOnly(key);
    return Number.isNaN(date.getTime()) || date > lastActualDate;
  });

  if (normalizedForecastKeys.length === 0 && lastActualDate && actualKeys.length > 0) {
    const tailValues = actualKeys.slice(-3).map((key) => actualMap.get(key) || 0);
    const tailAverage = tailValues.length > 0 ? tailValues.reduce((sum, value) => sum + value, 0) / tailValues.length : 0;
    for (let offset = 1; offset <= 3; offset += 1) {
      const futureDate = new Date(lastActualDate);
      futureDate.setDate(futureDate.getDate() + offset);
      const key = formatDateKeyLocal(futureDate);
      normalizedForecastKeys.push(key);
      if (!forecastMap.has(key)) forecastMap.set(key, roundForecastValue(tailAverage));
    }
  }

  const labels = [...new Set([...actualKeys, ...normalizedForecastKeys])].sort(dateComparator);
  const actualValues = labels.map((label) => {
    const isFutureForecastLabel = lastActualDate && parseDateOnly(label).getTime() > lastActualDate.getTime();
    return isFutureForecastLabel ? null : Number(actualMap.get(label) ?? 0);
  });
  const forecastValues = labels.map((label) => Number(forecastMap.get(label) ?? 0));

  return { labels, actualValues, forecastValues };
}

export function roundForecastValue(value) {
  return Math.round(Number(value || 0) * 100) / 100;
}

export function buildOptimizationNarrative(metrics = [], summary = {}) {
  const normalizedMetrics = Array.isArray(metrics) ? metrics : [];
  const findMetric = (needle) => normalizedMetrics.find((metric) => String(metric?.label || '').toLowerCase().includes(needle));
  const utilizationValue = Number(findMetric('utilization')?.value || 0);
  const conflictValue = Number(findMetric('conflict')?.value || 0);
  const constraintValue = Number(findMetric('constraint')?.value || 0);
  const unassignedValue = Number(findMetric('unassigned')?.value || 0);
  const completed = Number(summary?.completedThisPeriod || 0);
  const activeReservations = Number(summary?.activeReservations || 0);

  const summaryText = normalizedMetrics.length > 0
    ? `This panel compares ${formatMetricNumber(activeReservations, 0)} active reservations and ${formatMetricNumber(completed, 0)} completed requests with efficiency indicators for conflict reduction, utilization, constraint satisfaction, and unassigned work.`
    : 'No allocation efficiency data is available for this range.';

  const interpretation = normalizedMetrics.length > 0
    ? [
        conflictValue >= 10 ? 'Conflict reduction is improving strongly, so overlapping reservations are being handled well.' : conflictValue >= 0 ? 'Conflict reduction is flat to slightly positive, so the current schedule is holding but still needs monitoring.' : 'Conflict reduction is slipping, so overlapping bookings or timing clashes need attention.',
        utilizationValue >= 10 ? 'Equipment utilization is trending up, which means demand is being absorbed efficiently.' : utilizationValue >= 0 ? 'Equipment utilization is steady, which supports the current allocation plan.' : 'Equipment utilization is falling, so some inventory may be underused or waiting idle.',
        constraintValue >= 95 ? 'Constraint satisfaction is very high, so most requests are being resolved within the current rules.' : constraintValue >= 80 ? 'Constraint satisfaction is acceptable, but the remaining unresolved items should be checked for bottlenecks.' : 'Constraint satisfaction is weak, which suggests the current allocation rules are blocking too many requests.',
        unassignedValue <= 5 ? 'Unassigned requests are low, so the system is matching demand well.' : unassignedValue <= 20 ? 'Unassigned requests are moderate, so a small redistribution may help.' : 'Unassigned requests are high, so the allocation plan should be revisited sooner rather than later.',
      ].join(' ')
    : 'There is no efficiency trend to interpret for this range.';

  return { summary: summaryText, interpretation };
}

export function buildUtilizationNarrative(items = []) {
  const normalizedItems = Array.isArray(items) ? items : [];
  const sortedItems = [...normalizedItems].sort((left, right) => Number(right?.value || 0) - Number(left?.value || 0));
  const topItems = sortedItems.slice(0, 5);
  const highest = topItems[0];
  const lowest = topItems[topItems.length - 1];
  const highestValue = Number(highest?.value || 0);
  const lowestValue = Number(lowest?.value || 0);
  const utilizationSpread = highestValue - lowestValue;
  const topAverage = topItems.length > 0 ? topItems.reduce((sum, item) => sum + Number(item?.value || 0), 0) / topItems.length : 0;
  const topThreeAverage = topItems.slice(0, 3).reduce((sum, item) => sum + Number(item?.value || 0), 0) / Math.max(1, Math.min(3, topItems.length));
  const lowerHalfAverage = topItems.length > 1
    ? topItems.slice(Math.floor(topItems.length / 2)).reduce((sum, item) => sum + Number(item?.value || 0), 0) / Math.max(1, topItems.length - Math.floor(topItems.length / 2))
    : topAverage;
  const nextThreeDayIntensity = topThreeAverage >= 60 ? 'high' : topThreeAverage >= 35 ? 'moderate' : 'light';

  const summary = topItems.length > 0
    ? `This chart ranks the top ${formatMetricNumber(topItems.length, 0)} categories by utilization, from ${highest?.label || 'the highest category'} at ${formatMetricNumber(highest?.value || 0, 0)}% down to ${lowest?.label || 'the lowest visible category'} at ${formatMetricNumber(lowest?.value || 0, 0)}%.`
    : 'No category utilization data is available yet.';

  const pastComparison = topItems.length > 0
    ? [
        `The leading categories average ${formatMetricNumber(topThreeAverage, 0)}%, while the lower half averages ${formatMetricNumber(lowerHalfAverage, 0)}%, so past usage is concentrated in a small set of equipment.`,
        utilizationSpread >= 30 ? 'That wide gap means the next 3 days will likely feel intense on the busiest items, so prepare extra buffer for those categories first.' : utilizationSpread >= 10 ? 'That moderate gap means demand is mixed, so watch the busiest items first while keeping the rest available.' : 'That narrow gap means usage is fairly even, so a steady allocation plan should hold for the next 3 days.',
      ].join(' ')
    : 'No utilization history is available yet to compare against past demand.';

  const interpretation = topItems.length > 0
    ? [
        highestValue >= 75 ? 'The leading category is very active, so it should keep the highest stock buffer.' : highestValue >= 40 ? 'The leading category is moderately active, so it needs regular replenishment but not emergency allocation.' : 'The leading category is still low, so the current stock level is adequate unless demand changes quickly.',
        utilizationSpread >= 30 ? 'The spread between top and bottom categories is wide, which points to uneven demand and a need for targeted reallocation.' : utilizationSpread >= 10 ? 'The spread between categories is moderate, so the inventory mix is somewhat balanced but still needs tuning.' : 'The categories are clustered closely together, so a balanced allocation strategy should work well.',
        topAverage >= 50 ? `The top categories average ${formatMetricNumber(topAverage, 0)}%, so the most-used equipment group should be protected first.` : `The top categories average only ${formatMetricNumber(topAverage, 0)}%, so you can keep a lighter buffer and watch for changes.`,
        lowestValue <= 20 ? 'The lowest categories are quiet enough to consider consolidation or temporary redistribution.' : 'The lower categories still see meaningful usage, so avoid cutting them too aggressively.',
        nextThreeDayIntensity === 'high' ? 'For the next 3 days, expect high intensity on the busiest categories and keep them prioritized in scheduling.' : nextThreeDayIntensity === 'moderate' ? 'For the next 3 days, expect moderate intensity and keep a small buffer on the busiest categories.' : 'For the next 3 days, expect light intensity and avoid over-allocating stock to this panel.',
      ].join(' ')
    : 'There is no category utilization trend to interpret for this range.';

  return { summary, pastComparison, interpretation };
}

export function resolveOptimizationDecision(metric) {
  const label = String(metric?.label || '').toLowerCase();
  if (label.includes('conflict')) return 'Decision: reduce clashes by reserving fewer overlapping items at the same time.';
  if (label.includes('utilization')) return 'Decision: keep this allocation if demand is steady; add inventory if the rate keeps rising.';
  if (label.includes('constraint')) return 'Decision: continue the current plan and monitor unresolved requests for bottlenecks.';
  if (label.includes('unassigned')) return 'Decision: reassign idle inventory or relax scheduling limits if this stays above target.';
  return 'Decision: review this metric alongside the forecast and risk bands before reallocating.';
}

export function resolveUtilizationTooltip(item) {
  if (!item) return 'Equipment utilization';
  return `${item.label || 'Category'} utilization: ${formatMetricNumber(item.value || 0, 0)}%`;
}

function formatLongDate(value) {
  if (!value) return 'No forecast peak yet';
  const date = parseDateOnly(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(date);
}

function formatDateKeyLocal(date) {
  if (!(date instanceof Date) || Number.isNaN(date.getTime())) return '';
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}
