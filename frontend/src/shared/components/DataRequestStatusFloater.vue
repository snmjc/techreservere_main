<template>
  <aside class="data-request-status-floater" aria-label="Page data status">
    <div class="data-request-status-floater__header">
      <button
        type="button"
        class="data-request-status-floater__toggle"
        :aria-expanded="isExpanded"
        @click="isExpanded = !isExpanded"
      >
        <strong>Data Status</strong>
        <span>{{ isExpanded ? 'Hide' : 'Show' }}</span>
      </button>
      <span>{{ activeSummary }}</span>
    </div>
    <div v-if="!isExpanded" class="data-request-status-floater__compact" aria-label="Compact data status counts">
      <template v-for="(countItem, index) in compactCounts" :key="countItem.state">
        <span class="data-request-status-floater__compact-item">
          <span class="data-request-status-floater__dot" :class="`is-${countItem.state}`"></span>
          {{ countItem.count }}
        </span>
        <span v-if="index < compactCounts.length - 1" class="data-request-status-floater__compact-separator">|</span>
      </template>
    </div>
    <div v-else class="data-request-status-floater__list">
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
import { computed, ref } from 'vue';

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
});

const isExpanded = ref(typeof window === 'undefined' ? true : window.innerWidth > 640);

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

const compactCounts = computed(() => {
  const counts = {
    loading: 0,
    'cached-loading': 0,
    error: 0,
    fresh: 0,
    cached: 0,
    idle: 0,
  };

  normalizedItems.value.forEach((item) => {
    counts[item.state] = (counts[item.state] || 0) + 1;
  });

  return Object.entries(counts)
    .filter(([, count]) => count > 0)
    .map(([state, count]) => ({ state, count }));
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

.data-request-status-floater__toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0;
  color: inherit;
  background: transparent;
  border: 0;
  cursor: pointer;
}

.data-request-status-floater__header strong {
  font-size: 0.8rem;
  font-weight: 900;
}

.data-request-status-floater__header span,
.data-request-status-floater__toggle span {
  color: #047857;
  font-size: 0.72rem;
  font-weight: 900;
  text-transform: uppercase;
}

.data-request-status-floater__toggle span {
  color: #64746d;
}

.data-request-status-floater__list {
  display: grid;
  gap: 0.35rem;
  padding: 0.65rem;
}

.data-request-status-floater__compact {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.45rem;
  padding: 0.65rem;
  color: #143328;
  font-size: 0.76rem;
  font-weight: 900;
}

.data-request-status-floater__compact-item {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.data-request-status-floater__compact-separator {
  color: #a5b4ac;
  font-weight: 800;
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

@media (max-width: 640px) {
  .data-request-status-floater {
    right: 12px;
    bottom: 12px;
    width: min(220px, calc(100vw - 24px));
  }

  .data-request-status-floater__list {
    max-height: 220px;
    overflow-y: auto;
  }
}
</style>
