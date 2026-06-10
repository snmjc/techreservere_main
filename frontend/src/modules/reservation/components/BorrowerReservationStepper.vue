<template>
  <section class="borrower-stepper">
    <div class="borrower-stepper-track" />
    <article
      v-for="step in steps"
      :key="step.number"
      class="borrower-stepper-item"
      :class="{
        'is-active': step.number === currentStep,
        'is-complete': step.number < currentStep,
      }"
    >
      <div class="borrower-stepper-icon" v-html="step.iconSvg" />
      <p>{{ step.number }}. {{ step.label }}</p>
      <span>{{ step.number }}</span>
    </article>
  </section>
</template>

<script setup>
const props = defineProps({
  currentStep: {
    type: Number,
    required: true,
  },
});

const steps = [
  {
    number: 1,
    label: 'Reservation Details',
    iconSvg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>',
  },
  {
    number: 2,
    label: 'Select Venue / Equipment',
    iconSvg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="7" height="7"/><rect x="13" y="4" width="7" height="7"/><rect x="4" y="13" width="7" height="7"/><rect x="13" y="13" width="7" height="7"/></svg>',
  },
  {
    number: 3,
    label: 'Additional Information',
    iconSvg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 3h8l4 4v14H7z"/><path d="M15 3v5h5"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>',
  },
  {
    number: 4,
    label: 'Supporting Documents',
    iconSvg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 1 0-7l1.5-1.5a5 5 0 1 1 7 7L17 13"/><path d="M14 11a5 5 0 0 1 0 7L12.5 19.5a5 5 0 1 1-7-7L7 11"/></svg>',
  },
  {
    number: 5,
    label: 'Review Summary',
    iconSvg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 7h6"/><path d="M9 11h6"/><path d="M9 15h4"/></svg>',
  },
];
</script>

<style scoped>
.borrower-stepper {
  position: relative;
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 1rem;
  padding: 1.25rem 1.1rem 1rem;
  background: #ffffff;
  border: 1px solid #e3ebe6;
  border-radius: 16px;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
}

.borrower-stepper-track {
  position: absolute;
  top: 2.32rem;
  right: 10%;
  left: 10%;
  height: 2px;
  background: #dfe8e2;
}

.borrower-stepper-item {
  position: relative;
  z-index: 1;
  display: grid;
  justify-items: center;
  gap: 0.38rem;
  text-align: center;
}

.borrower-stepper-icon {
  display: grid;
  place-items: center;
  width: 46px;
  height: 46px;
  color: #7a8d83;
  background: #f0f4f1;
  border: 1px solid #e0e8e3;
  border-radius: 14px;
}

.borrower-stepper-icon :deep(svg) {
  width: 20px;
  height: 20px;
}

.borrower-stepper-item p {
  margin: 0;
  color: #31483b;
  font-size: 0.68rem;
  font-weight: 800;
}

.borrower-stepper-item span {
  display: inline-grid;
  place-items: center;
  width: 22px;
  height: 22px;
  color: #ffffff;
  background: #9aa8a0;
  border-radius: 999px;
  font-size: 0.68rem;
  font-weight: 900;
}

.borrower-stepper-item.is-active .borrower-stepper-icon {
  color: #0b8a4a;
  background: #ffffff;
  border-color: #99d6b5;
  box-shadow: 0 0 0 4px rgba(12, 128, 72, 0.08);
}

.borrower-stepper-item.is-active span,
.borrower-stepper-item.is-complete span {
  background: #0c8048;
}

.borrower-stepper-item.is-complete .borrower-stepper-icon {
  color: #0b8a4a;
  background: #eef9f1;
  border-color: #bde3cb;
}

@media (max-width: 900px) {
  .borrower-stepper {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .borrower-stepper-track {
    display: none;
  }
}

@media (max-width: 560px) {
  .borrower-stepper {
    grid-template-columns: 1fr;
  }
}
</style>
