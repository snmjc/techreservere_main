import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

export function createDashboardChartRenderer() {
  let resourceChartInstance = null;

  function destroyResourceChart() {
    if (!resourceChartInstance) return;
    resourceChartInstance.destroy();
    resourceChartInstance = null;
  }

  function destroyAll() {
    destroyResourceChart();
  }

  function renderResourceUtilizationChart({
    canvas,
    resourceSeries,
    formatMetricNumber,
  }) {
    const displaySeries = (resourceSeries || [])
      .filter((item) => item?.label)
      .map((item) => ({
        label: String(item.label),
        demand: Number(item.demand || 0),
        utilizationRate: Number(item.utilizationRate || 0),
      }));

    if (!canvas || displaySeries.length === 0) {
      destroyResourceChart();
      return;
    }

    destroyResourceChart();

    resourceChartInstance = new Chart(canvas, {
      type: 'line',
      data: {
        labels: displaySeries.map((item) => item.label),
        datasets: [
          {
            label: 'Reservation Demand',
            data: displaySeries.map((item) => item.demand),
            borderColor: '#08784a',
            backgroundColor: 'rgba(8, 120, 74, 0.14)',
            fill: true,
            tension: 0.35,
            pointRadius: 3,
            pointHoverRadius: 5,
            borderWidth: 3,
          },
          {
            label: 'Equipment Utilization',
            data: displaySeries.map((item) => item.utilizationRate),
            borderColor: '#1d4ed8',
            backgroundColor: 'rgba(29, 78, 216, 0.1)',
            borderDash: [8, 5],
            tension: 0.35,
            pointRadius: 2,
            pointHoverRadius: 4,
            borderWidth: 2,
            yAxisID: 'utilization',
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false,
        },
        plugins: {
          legend: {
            display: true,
            labels: {
              usePointStyle: true,
              boxWidth: 8,
              color: '#60706a',
              font: {
                size: 11,
                weight: '700',
              },
            },
          },
          tooltip: {
            callbacks: {
              label(context) {
                const label = context.dataset?.label || '';
                const value = Number(context.raw || 0);
                const suffix = context.dataset?.yAxisID === 'utilization' ? '%' : '';
                return `${label}: ${formatMetricNumber(value, suffix ? 1 : 0)}${suffix}`;
              },
            },
          },
        },
        scales: {
          x: {
            grid: {
              display: false,
            },
            ticks: {
              color: '#60706a',
              font: {
                size: 11,
                weight: '700',
              },
              maxRotation: 0,
              autoSkip: true,
              maxTicksLimit: 7,
            },
          },
          y: {
            beginAtZero: true,
            grid: {
              color: '#e8efeb',
            },
            ticks: {
              color: '#60706a',
              precision: 0,
              font: {
                size: 11,
                weight: '700',
              },
            },
          },
          utilization: {
            beginAtZero: true,
            max: 100,
            position: 'right',
            grid: {
              drawOnChartArea: false,
            },
            ticks: {
              color: '#60706a',
              callback(value) {
                return `${value}%`;
              },
              font: {
                size: 11,
                weight: '700',
              },
            },
          },
        },
      },
    });
  }

  return {
    destroyAll,
    renderResourceUtilizationChart,
  };
}
