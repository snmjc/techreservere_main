<!-- ===== AI GENERATED: AnalyticsMonthlyForecastChartComponent ===== -->
<template>
  <div class="analytics-monthly-forecast-card">
    <div class="analytics-monthly-forecast-header">
      <span class="analytics-monthly-forecast-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
          <line x1="16" y1="2" x2="16" y2="6"/>
          <line x1="8" y1="2" x2="8" y2="6"/>
          <line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
      </span>
      <div>
        <h3 class="analytics-monthly-forecast-title">Monthly Equipment Usage Forecast</h3>
        <p class="analytics-monthly-forecast-subtitle">Forecast of peak equipment usage from January to December of 2025</p>
      </div>
    </div>
    <div class="analytics-monthly-forecast-chart-area">
      <canvas ref="monthlyChartCanvas" width="480" height="220"></canvas>
    </div>
    <p class="analytics-monthly-forecast-x-label">Month</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const monthlyChartCanvas = ref(null);

/**
 * @function drawMonthlyForecastChart
 * @description Draws a multi-line chart on the canvas representing monthly equipment forecast.
 * @returns {void}
 */
function drawMonthlyForecastChart() {
  const canvas = monthlyChartCanvas.value;
  if (!canvas) return;
  const context = canvas.getContext('2d');
  const chartWidth = canvas.width;
  const chartHeight = canvas.height;
  const padding = { top: 20, right: 20, bottom: 30, left: 45 };

  const monthLabels = ['Jan', '', 'Apr', '', 'Aug', '', 'Dec'];
  const monthPositions = [0, 1, 3, 5, 7, 9, 11];
  const datasetGreen = [180, 220, 280, 250, 200, 170, 220, 190, 160, 200, 230, 210];
  const datasetBlue = [150, 180, 250, 220, 180, 150, 200, 170, 140, 180, 200, 190];
  const datasetRed = [120, 140, 190, 170, 250, 200, 160, 180, 200, 170, 150, 180];

  const maxValue = 350;
  const plotWidth = chartWidth - padding.left - padding.right;
  const plotHeight = chartHeight - padding.top - padding.bottom;

  context.clearRect(0, 0, chartWidth, chartHeight);

  // Grid lines
  context.strokeStyle = 'rgba(255,255,255,0.2)';
  context.lineWidth = 0.5;
  for (let gridIndex = 0; gridIndex <= 5; gridIndex++) {
    const yPos = padding.top + (plotHeight / 5) * gridIndex;
    context.beginPath();
    context.moveTo(padding.left, yPos);
    context.lineTo(chartWidth - padding.right, yPos);
    context.stroke();
  }

  // X-axis labels
  context.fillStyle = 'rgba(255,255,255,0.7)';
  context.font = '10px sans-serif';
  context.textAlign = 'center';
  monthLabels.forEach((label, labelIndex) => {
    if (label) {
      const xPos = padding.left + (plotWidth / 11) * monthPositions[labelIndex];
      context.fillText(label, xPos, chartHeight - 8);
    }
  });

  // Y-axis labels
  context.textAlign = 'right';
  for (let yIndex = 0; yIndex <= 5; yIndex++) {
    const yVal = Math.round((maxValue / 5) * (5 - yIndex));
    const yPos = padding.top + (plotHeight / 5) * yIndex;
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
  drawMonthlyForecastChart();
});
</script>
