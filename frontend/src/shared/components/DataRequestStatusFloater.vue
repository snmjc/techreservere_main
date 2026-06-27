<template>
  <aside class="data-request-status-floater" aria-label="Page data status">
    <div class="data-request-status-floater__header">
      <strong>Data Status</strong>
      <span>{{ activeSummary }}</span>
    </div>
    <div class="data-request-status-floater__list">
      <div
        v-for="item in normalizedItems"
        :key="item.key"
        class="data-request-status-floater__item"
      >
        <span class="data-request-status-floater__dot" :class="`is-${item.state}`"></span>
        <div>
          <strong>{{ item.label }}</strong>
          <small>{{ stateLabels[item.state] || stateLabels.idle }}</small>
          <small v-if="item.cacheNote" class="data-request-status-floater__meta">{{ item.cacheNote }}</small>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
});

const stateLabels = {
  idle: 'Idle',
  loading: 'Loading',
  cached: 'Cached',
  'cached-loading': 'Cached + loading',
  fresh: 'Fresh data',
  error: 'Failed',
};

const normalizedItems = computed(() => props.items
  .filter((item) => item && item.label)
  .map((item, index) => ({
    key: item.key || `${item.label}-${index}`,
    label: item.label,
    state: stateLabels[item.state] ? item.state : 'idle',
    cacheNote: resolveCacheNote(item),
  })));

const activeSummary = computed(() => {
  if (normalizedItems.value.some((item) => item.state === 'loading' || item.state === 'cached-loading')) {
    return 'Loading';
  }

  if (normalizedItems.value.some((item) => item.state === 'error')) {
    return 'Needs review';
  }

  if (normalizedItems.value.some((item) => item.state === 'fresh')) {
    return 'Fresh';
  }

  if (normalizedItems.value.some((item) => item.state === 'cached')) {
    return 'Cached';
  }

  return 'Idle';
});

function resolveCacheNote(item) {
  if (!['cached', 'cached-loading'].includes(item.state) || !item.expiresAt) {
    return '';
  }

  const expiresAt = Number(item.expiresAt);
  if (!Number.isFinite(expiresAt)) {
    return '';
  }

  const seconds = Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));
  if (seconds <= 0) {
    return 'Cache expired';
  }

  if (seconds < 60) {
    return `Cache expires in ${seconds}s`;
  }

  return `Cache expires in ${Math.ceil(seconds / 60)}m`;
}
</script>

<style scoped>
.data-request-status-floater {
  position: fixed;
  right: 18px;
  bottom: 18px;
  z-index: 60;
  width: min(280px, calc(100vw - 36px));
  color: #143328;
  background: rgba(255, 255, 255, 0.96);
  border: 1px solid #d8e6df;
  border-radius: 8px;
  box-shadow: 0 18px 36px rgba(15, 23, 42, 0.16);
  backdrop-filter: blur(12px);
}

.data-request-status-floater__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 0.85rem;
  border-bottom: 1px solid #edf3ef;
}

.data-request-status-floater__header strong {
  font-size: 0.8rem;
  font-weight: 900;
}

.data-request-status-floater__header span {
  color: #047857;
  font-size: 0.72rem;
  font-weight: 900;
  text-transform: uppercase;
}

.data-request-status-floater__list {
  display: grid;
  gap: 0.35rem;
  padding: 0.65rem;
}

.data-request-status-floater__item {
  display: grid;
  grid-template-columns: 10px 1fr;
  align-items: center;
  gap: 0.55rem;
  min-height: 36px;
}

.data-request-status-floater__item strong {
  display: block;
  font-size: 0.76rem;
  font-weight: 850;
}

.data-request-status-floater__item small {
  display: block;
  color: #64746d;
  font-size: 0.7rem;
  font-weight: 750;
}

.data-request-status-floater__meta {
  color: #8a6a18;
  font-size: 0.66rem;
}

.data-request-status-floater__dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  background: #9ca3af;
}

.data-request-status-floater__dot.is-loading {
  background: #2563eb;
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
}

.data-request-status-floater__dot.is-cached {
  background: #f59e0b;
}

.data-request-status-floater__dot.is-cached-loading {
  background: #8b5cf6;
  box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.12);
}

.data-request-status-floater__dot.is-fresh {
  background: #059669;
}

.data-request-status-floater__dot.is-error {
  background: #dc2626;
}
</style>
