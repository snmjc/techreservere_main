import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

export function createReportsAnalyticsChartRenderer() {
  let forecastChartInstance = null;
  let riskChartInstance = null;
  let utilizationChartInstance = null;

  function destroyChart(chartKey) {
    if (chartKey === 'forecast' && forecastChartInstance) {
      forecastChartInstance.destroy();
      forecastChartInstance = null;
    }

    if (chartKey === 'risk' && riskChartInstance) {
      riskChartInstance.destroy();
      riskChartInstance = null;
    }

    if (chartKey === 'utilization' && utilizationChartInstance) {
      utilizationChartInstance.destroy();
      utilizationChartInstance = null;
    }
  }

  function destroyAll() {
    destroyChart('forecast');
    destroyChart('risk');
    destroyChart('utilization');
  }

  function renderForecastChart({
    canvas,
    displaySeries,
    midpointSeries,
    formatShortDate,
    formatMetricNumber,
  }) {
    if (!canvas || (displaySeries?.actualValues || []).length === 0) {
      destroyChart('forecast');
      return;
    }

    destroyChart('forecast');

    forecastChartInstance = new Chart(canvas, {
      type: 'line',
      data: {
        labels: (displaySeries.labels || []).map((label) => formatShortDate(label)),
        datasets: [
          {
            label: 'Actual Demand',
            data: displaySeries.actualValues || [],
            borderColor: '#540d6e',
            backgroundColor: 'rgba(84, 13, 110, 0.12)',
            tension: 0.35,
            pointRadius: 2,
            pointHoverRadius: 4,
          },
          {
            label: 'Forecasted Demand',
            data: displaySeries.forecastValues || [],
            borderColor: '#ee4266',
            borderDash: [8, 6],
            tension: 0.35,
            pointRadius: 2,
            pointHoverRadius: 4,
          },
          {
            label: 'Midpoint Demand',
            data: midpointSeries || [],
            borderColor: '#e59500',
            borderDash: [2, 4],
            tension: 0.35,
            pointRadius: 1,
            pointHoverRadius: 3,
            borderWidth: 2,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: true, labels: { usePointStyle: true } },
          tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: {
              label(context) {
                const label = context.dataset?.label || '';
                const value = Number(context.raw || 0);
                return `${label}: ${formatMetricNumber(value, 1)} requests`;
              },
            },
          },
        },
        scales: {
          x: { grid: { color: '#e5e7eb' } },
          y: { beginAtZero: true, grid: { color: '#e5e7eb' } },
        },
      },
    });
  }

  function renderRiskChart({
    canvas,
    riskBands,
    highRiskEquipment,
    safeRateLabel,
  }) {
    if (!canvas || (riskBands || []).length === 0) {
      destroyChart('risk');
      return;
    }

    destroyChart('risk');

    riskChartInstance = new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: riskBands.map((item) => item.label),
        datasets: [
          {
            data: riskBands.map((item) => Number(item.count || 0)),
            backgroundColor: riskBands.map((item) => item.color),
            borderWidth: 0,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label(context) {
                const label = context.label || '';
                const value = Number(context.raw || 0);
                if (label === 'High Risk') {
                  const names = highRiskEquipment.map((item) => item?.name).filter(Boolean);
                  return [`${label}: ${value} equipment`, ...names.slice(0, 5).map((name) => `• ${name}`)];
                }
                return `${label}: ${value} equipment`;
              },
            },
          },
        },
      },
      plugins: [
        {
          id: 'centerText',
          afterDraw(chart) {
            const { ctx, chartArea } = chart;
            if (!chartArea) return;
            ctx.save();
            ctx.fillStyle = '#15803d';
            ctx.font = '700 18px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(safeRateLabel, (chartArea.left + chartArea.right) / 2, (chartArea.top + chartArea.bottom) / 2);
            ctx.restore();
          },
        },
      ],
    });
  }

  function renderUtilizationChart({
    canvas,
    utilizationItems,
    utilizationComparisonItems,
    formatMetricNumber,
  }) {
    if (!canvas || ((utilizationItems || []).length === 0 && (utilizationComparisonItems || []).length === 0)) {
      destroyChart('utilization');
      return;
    }

    destroyChart('utilization');

    const currentMap = new Map(
      utilizationItems.map((item) => [String(item?.label || ''), Number(item?.value || 0)]),
    );
    const previousMap = new Map(
      utilizationComparisonItems.map((item) => [String(item?.label || ''), Number(item?.value || 0)]),
    );
    const currentSeries = [...new Set([...currentMap.keys(), ...previousMap.keys()])]
      .map((label) => ({
        label,
        currentValue: Number(currentMap.get(label) || 0),
        previousValue: Number(previousMap.get(label) || 0),
        rankValue: Math.max(Number(currentMap.get(label) || 0), Number(previousMap.get(label) || 0)),
      }))
      .sort((left, right) => right.rankValue - left.rankValue || left.label.localeCompare(right.label))
      .slice(0, 5);

    utilizationChartInstance = new Chart(canvas, {
      type: 'bar',
      data: {
        labels: currentSeries.map((item) => item.label),
        datasets: [
          {
            label: 'Current Period',
            data: currentSeries.map((item) => item.currentValue),
            backgroundColor: 'rgba(29, 78, 216, 0.85)',
            borderRadius: 6,
          },
          {
            label: 'Last Year Same Days',
            data: currentSeries.map((item) => item.previousValue),
            backgroundColor: 'rgba(96, 165, 250, 0.45)',
            borderRadius: 6,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: true, labels: { usePointStyle: true } },
          tooltip: {
            callbacks: {
              label(context) {
                return `${context.dataset.label}: ${formatMetricNumber(context.raw || 0, 0)}%`;
              },
            },
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            ticks: {
              callback(value) {
                return `${value}%`;
              },
            },
          },
        },
      },
    });
  }

  return {
    destroyAll,
    renderForecastChart,
    renderRiskChart,
    renderUtilizationChart,
  };
}
