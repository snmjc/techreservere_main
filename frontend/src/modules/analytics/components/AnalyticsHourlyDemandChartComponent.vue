<!-- ===== AI GENERATED: AnalyticsHourlyDemandChartComponent ===== -->
<template>
  <div class="analytics-hourly-demand-card">
    <div class="analytics-hourly-demand-header">
      <span class="analytics-hourly-demand-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="12 6 12 12 16 14"/>
        </svg>
      </span>
      <div>
        <h3 class="analytics-hourly-demand-title">Hourly Equipment Demand</h3>
        <p class="analytics-hourly-demand-subtitle">Hourly distribution of equipment reservations within a single operational day</p>
      </div>
    </div>
    <div class="analytics-hourly-demand-chart-area">
      <canvas ref="hourlyChartCanvas" width="480" height="200"></canvas>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const hourlyChartCanvas = ref(null);

/**
 * @function drawHourlyDemandChart
 * @description Draws a multi-line chart on the canvas representing hourly equipment demand.
 * @returns {void}
 */
function drawHourlyDemandChart() {
  const canvas = hourlyChartCanvas.value;
  if (!canvas) return;
  const context = canvas.getContext('2d');
  const chartWidth = canvas.width;
  const chartHeight = canvas.height;
  const padding = { top: 20, right: 20, bottom: 30, left: 40 };

  const hourLabels = ['1:00', '2:00', '3:00', '4:00', '5:00', '6:00', '7:00', '8:00', '9:00', '10:00', '11:00', '12:00'];
  const datasetGreen = [2, 5, 3, 8, 6, 4, 7, 5, 9, 6, 4, 3];
  const datasetBlue = [1, 3, 2, 5, 4, 6, 3, 7, 5, 8, 5, 4];
  const datasetRed = [3, 4, 5, 3, 7, 5, 4, 6, 3, 5, 7, 6];

  const maxValue = 12;
  const plotWidth = chartWidth - padding.left - padding.right;
  const plotHeight = chartHeight - padding.top - padding.bottom;

  context.clearRect(0, 0, chartWidth, chartHeight);

  // Grid lines
  context.strokeStyle = 'rgba(255,255,255,0.2)';
  context.lineWidth = 0.5;
  for (let gridIndex = 0; gridIndex <= 4; gridIndex++) {
    const yPos = padding.top + (plotHeight / 4) * gridIndex;
    context.beginPath();
    context.moveTo(padding.left, yPos);
    context.lineTo(chartWidth - padding.right, yPos);
    context.stroke();
  }

  // X-axis labels
  context.fillStyle = 'rgba(255,255,255,0.7)';
  context.font = '9px sans-serif';
  context.textAlign = 'center';
  hourLabels.forEach((label, labelIndex) => {
    const xPos = padding.left + (plotWidth / (hourLabels.length - 1)) * labelIndex;
    context.fillText(label, xPos, chartHeight - 8);
  });

  // Y-axis labels
  context.textAlign = 'right';
  for (let yIndex = 0; yIndex <= 4; yIndex++) {
    const yVal = Math.round((maxValue / 4) * (4 - yIndex));
    const yPos = padding.top + (plotHeight / 4) * yIndex;
    context.fillText(yVal.toString(), padding.left - 6, yPos + 3);
  }

  // Draw lines
  function drawDataLine(dataPoints, strokeColor) {
    context.strokeStyle = strokeColor;
    context.lineWidth = 2;
    context.beginPath();
    dataPoints.forEach((pointValue, pointIndex) => {
      const xPos = padding.left + (plotWidth / (dataPoints.length - 1)) * pointIndex;
      const yPos = padding.top + plotHeight - (pointValue / maxValue) * plotHeight;
      if (pointIndex === 0) {
        context.moveTo(xPos, yPos);
      } else {
        context.lineTo(xPos, yPos);
      }
    });
    context.stroke();
  }

  drawDataLine(datasetGreen, '#16a34a');
  drawDataLine(datasetBlue, '#2563eb');
  drawDataLine(datasetRed, '#dc2626');
}

onMounted(() => {
  drawHourlyDemandChart();
});
</script>
