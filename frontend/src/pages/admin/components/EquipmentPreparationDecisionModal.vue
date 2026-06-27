<template>
  <div v-if="decisionItem" class="equipment-preparation-modal-backdrop" @click.self="emit('close')">
    <section class="equipment-preparation-modal" role="dialog" aria-modal="true" :aria-label="`${equipmentName} demand basis`">
      <header class="equipment-preparation-modal__header">
        <div>
          <p>Equipment Preparation</p>
          <h3>{{ equipmentName }}</h3>
        </div>
        <button type="button" class="equipment-preparation-modal__close" aria-label="Close" @click="emit('close')">&times;</button>
      </header>

      <div class="equipment-preparation-modal__summary">
        <span class="equipment-preparation-modal__pill" :class="`is-${decisionItem.tone || 'steady'}`">
          {{ decisionItem.decision || 'Keep prepared' }}
        </span>
        <strong>{{ decisionItem.action || 'Monitor availability and avoid lending all units early.' }}</strong>
      </div>

      <dl class="equipment-preparation-modal__metrics">
        <div>
          <dt>Forecasted Demand</dt>
          <dd>{{ formatNumber(decisionItem.predictedDemand) }}</dd>
        </div>
        <div>
          <dt>Current Usage</dt>
          <dd>{{ formatNumber(decisionItem.currentUsage ?? decisionItem.count) }}</dd>
        </div>
        <div>
          <dt>Past Same-Date Usage</dt>
          <dd>{{ formatNumber(decisionItem.previousYearCount) }}</dd>
        </div>
        <div>
          <dt>Forecast Buffer</dt>
          <dd>{{ formatNumber(decisionItem.predictionGap) }}</dd>
        </div>
      </dl>

      <section class="equipment-preparation-modal__basis">
        <h4>Demand Basis</h4>
        <p>{{ decisionItem.signal }}</p>
        <p>{{ decisionItem.reason }}</p>
      </section>

      <section class="equipment-preparation-modal__details">
        <div>
          <span>Total Quantity</span>
          <strong>{{ formatNumber(decisionItem.totalQuantity) }}</strong>
        </div>
        <div>
          <span>Forecast Score</span>
          <strong>{{ formatNumber(decisionItem.score, 2) }}</strong>
        </div>
      </section>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  decisionItem: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close']);

const equipmentName = computed(() => props.decisionItem?.name || 'Equipment');

function formatNumber(value, digits = 0) {
  const number = Number(value);
  if (!Number.isFinite(number)) {
    return '0';
  }

  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: digits,
    maximumFractionDigits: digits,
  }).format(number);
}
</script>

<style scoped>
.equipment-preparation-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 80;
  display: grid;
  place-items: center;
  padding: 1rem;
  background: rgba(15, 23, 42, 0.52);
  backdrop-filter: blur(6px);
}

.equipment-preparation-modal {
  width: min(620px, calc(100vw - 2rem));
  max-height: min(88vh, 780px);
  overflow: auto;
  padding: 1.1rem;
  color: #143328;
  background: linear-gradient(180deg, #ffffff, #f7fbf8);
  border: 1px solid #d5e7dd;
  border-radius: 12px;
  box-shadow: 0 30px 80px rgba(15, 23, 42, 0.28);
}

.equipment-preparation-modal__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.equipment-preparation-modal__header p {
  margin: 0 0 0.2rem;
  color: #047857;
  font-size: 0.72rem;
  font-weight: 900;
  text-transform: uppercase;
}

.equipment-preparation-modal__header h3 {
  margin: 0;
  color: #132238;
  font-size: 1.15rem;
  font-weight: 900;
}

.equipment-preparation-modal__close {
  width: 36px;
  height: 36px;
  color: #475569;
  background: #eff6f2;
  border: 1px solid #d9e7df;
  border-radius: 50%;
  font: inherit;
  font-size: 1.2rem;
  cursor: pointer;
}

.equipment-preparation-modal__summary {
  display: grid;
  gap: 0.55rem;
  margin-top: 1rem;
  padding: 0.85rem;
  background: #f8faf9;
  border: 1px solid #dce9e1;
  border-radius: 8px;
}

.equipment-preparation-modal__summary strong {
  color: #24332e;
  font-size: 0.84rem;
  line-height: 1.45;
}

.equipment-preparation-modal__pill {
  justify-self: start;
  padding: 0.32rem 0.6rem;
  border-radius: 999px;
  font-size: 0.68rem;
  font-weight: 900;
}

.equipment-preparation-modal__pill.is-urgent {
  color: #9a3412;
  background: #ffedd5;
  border: 1px solid #fed7aa;
}

.equipment-preparation-modal__pill.is-steady {
  color: #065f46;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
}

.equipment-preparation-modal__metrics {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.65rem;
  margin: 1rem 0;
}

.equipment-preparation-modal__metrics div,
.equipment-preparation-modal__details div {
  padding: 0.75rem;
  background: #ffffff;
  border: 1px solid #dce9e1;
  border-radius: 8px;
}

.equipment-preparation-modal__metrics dt,
.equipment-preparation-modal__details span {
  color: #607668;
  font-size: 0.68rem;
  font-weight: 900;
  text-transform: uppercase;
}

.equipment-preparation-modal__metrics dd,
.equipment-preparation-modal__details strong {
  display: block;
  margin: 0.35rem 0 0;
  color: #0f172a;
  font-size: 1rem;
  font-weight: 950;
}

.equipment-preparation-modal__basis {
  display: grid;
  gap: 0.45rem;
  padding: 0.85rem;
  background: #ffffff;
  border: 1px solid #dce9e1;
  border-radius: 8px;
}

.equipment-preparation-modal__basis h4 {
  margin: 0;
  color: #132238;
  font-size: 0.9rem;
  font-weight: 900;
}

.equipment-preparation-modal__basis p {
  margin: 0;
  color: #475569;
  font-size: 0.78rem;
  font-weight: 750;
  line-height: 1.45;
}

.equipment-preparation-modal__details {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.65rem;
  margin-top: 0.85rem;
}

@media (max-width: 680px) {
  .equipment-preparation-modal__metrics,
  .equipment-preparation-modal__details {
    grid-template-columns: 1fr;
  }
}
</style>
