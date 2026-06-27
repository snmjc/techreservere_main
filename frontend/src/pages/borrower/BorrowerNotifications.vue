<!-- ===== Borrower Notifications Page ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'BORROWER'"
    :navigation-items="borrowerNavigationItems"
  >
    <div class="borrower-notifications-page">
      <div class="notifications-header">
        <h1 class="notifications-title">Notifications</h1>
        <button @click="markAllAsRead" class="mark-all-read-btn">
          Mark all as read
        </button>
      </div>

      <div class="notifications-controls">
        <div class="search-group">
          <label for="searchInput">Search:</label>
          <input
            id="searchInput"
            v-model="searchQuery"
            type="text"
            placeholder="Name"
            class="search-input"
          />
        </div>

        <div class="filter-group">
          <label for="showingFilter">Showing:</label>
          <select id="showingFilter" v-model="sortBy" class="filter-select">
            <option value="all">All</option>
            <option value="newest">Newest First</option>
            <option value="oldest">Oldest First</option>
          </select>
        </div>
      </div>

      <div class="filter-tabs">
        <button
          v-for="tab in filterTabs"
          :key="tab.value"
          @click="activeFilter = tab.value"
          :class="['filter-tab', { active: activeFilter === tab.value }]"
        >
          {{ tab.label }}
        </button>
      </div>

      <div class="notifications-list">
        <div v-if="filteredNotifications.length === 0" class="empty-state">
          <p>No notifications</p>
        </div>

        <div
          v-for="notification in paginatedNotifications"
          :key="notification.id"
          class="notification-item"
          :class="{ unread: !notification.isRead }"
        >
          <div class="notification-icon">
            <component :is="getNotificationIcon(notification.type)" />
          </div>

          <div class="notification-content">
            <h3 class="notification-title">{{ notification.title }}</h3>
            <p class="notification-description">{{ notification.description }}</p>
          </div>

          <div class="notification-time">
            {{ formatTime(notification.timestamp) }}
          </div>

          <div
            v-if="!notification.isRead"
            class="unread-dot"
            @click="markAsRead(notification.id)"
          ></div>
        </div>
        <div v-if="totalPages > 1" class="notifications-pagination">
          <button type="button" :disabled="currentPage === 1" @click="currentPage -= 1">Previous</button>
          <span>Page {{ currentPage }} of {{ totalPages }}</span>
          <button type="button" :disabled="currentPage === totalPages" @click="currentPage += 1">Next</button>
        </div>
      </div>
      <DataRequestStatusFloater :items="notificationStatusItems" />
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import DataRequestStatusFloater from '@/shared/components/DataRequestStatusFloater.vue';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import NotificationIconReservation from '@/components/icons/NotificationIconReservation.vue';
import NotificationIconEquipment from '@/components/icons/NotificationIconEquipment.vue';
import NotificationIconSystem from '@/components/icons/NotificationIconSystem.vue';
import NotificationIconMaintenance from '@/components/icons/NotificationIconMaintenance.vue';
import { useNotificationStore } from '@/modules/notification/store/notificationStore.js';

const searchQuery = ref('');
const activeFilter = ref('all');
const sortBy = ref('all');
const currentPage = ref(1);
const pageSize = 10;
const notificationStore = useNotificationStore();

const filterTabs = [
  { label: 'All', value: 'all' },
  { label: 'Unread', value: 'unread' },
  { label: 'Reservations', value: 'reservation' },
  { label: 'System', value: 'system' }
];

const notifications = computed(() => notificationStore.notifications || []);
const notificationStatusItems = computed(() => [
  {
    key: 'notifications',
    label: 'Notifications',
    state: resolveNotificationState(),
  },
]);

onMounted(() => {
  notificationStore.fetchNotifications(true).catch(() => {});
});

function resolveNotificationState() {
  if (notificationStore.isLoading && notifications.value.length > 0) {
    return 'cached-loading';
  }

  if (notificationStore.isLoading) {
    return 'loading';
  }

  return notifications.value.length > 0 ? 'fresh' : 'idle';
}

const filteredNotifications = computed(() => {
  let filtered = [...notifications.value];

  if (activeFilter.value !== 'all') {
    if (activeFilter.value === 'unread') {
      filtered = filtered.filter((notification) => !notification.isRead);
    } else {
      filtered = filtered.filter((notification) => notification.type === activeFilter.value);
    }
  }

  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(
      (notification) =>
        notification.title.toLowerCase().includes(query)
        || notification.description.toLowerCase().includes(query)
    );
  }

  if (sortBy.value === 'newest') {
    filtered.sort((first, second) => second.timestamp - first.timestamp);
  } else if (sortBy.value === 'oldest') {
    filtered.sort((first, second) => first.timestamp - second.timestamp);
  }

  return filtered;
});
const totalPages = computed(() => Math.max(1, Math.ceil(filteredNotifications.value.length / pageSize)));
const paginatedNotifications = computed(() => {
  const startIndex = (currentPage.value - 1) * pageSize;
  return filteredNotifications.value.slice(startIndex, startIndex + pageSize);
});

watch([searchQuery, activeFilter, sortBy], () => {
  currentPage.value = 1;
});

watch(totalPages, (pageCount) => {
  if (currentPage.value > pageCount) {
    currentPage.value = pageCount;
  }
});

const markAllAsRead = async () => {
  await notificationStore.markAllAsRead().catch(() => {});
};

const markAsRead = async (notificationId) => {
  await notificationStore.markAsRead(notificationId).catch(() => {});
};

const getNotificationIcon = (type) => {
  const iconMap = {
    reservation: NotificationIconReservation,
    equipment: NotificationIconEquipment,
    system: NotificationIconSystem,
    maintenance: NotificationIconMaintenance
  };
  return iconMap[type] || NotificationIconSystem;
};

const formatTime = (timestamp) => {
  const now = new Date();
  const diffMs = now - timestamp;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);

  if (diffMins < 1) return 'just now';
  if (diffMins < 60) return `${diffMins}m ago`;
  if (diffHours < 24) return `${diffHours}h ago`;
  if (diffDays < 7) return `${diffDays}d ago`;

  return timestamp.toLocaleDateString();
};
</script>

<style scoped>
@import './css/BorrowerNotifications.css';
</style>

