<!-- ===== Notification Page ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'NOTIFICATIONS'"
    :navigation-items="adminNavigationItems"
  >
    <div class="notification-page">
      <div class="notification-header">
        <h1 class="notification-title">Notifications</h1>
        <button
          @click="markAllAsRead"
          :disabled="unreadCount === 0"
          class="notification-mark-all"
        >
          Mark all as read
        </button>
      </div>

      <div class="notification-controls">
        <div class="notification-controls-row">
          <label class="notification-label">Search:</label>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Name"
            class="notification-input"
          />
          <label class="notification-label">Showing:</label>
          <select
            v-model="activeFilter"
            class="notification-select"
          >
            <option value="all">All</option>
            <option value="unread">Unread</option>
            <option value="reservation">Reservations</option>
            <option value="system">System</option>
          </select>
        </div>

        <div class="notification-tabs">
          <button
            v-for="tab in filterTabs"
            :key="tab.value"
            @click="activeFilter = tab.value"
            class="notification-tab"
            :class="{ 'notification-tab--active': activeFilter === tab.value }"
          >
            {{ tab.label }}
          </button>
        </div>
      </div>

      <div class="notification-list">
        <div v-if="filteredNotifications.length === 0" class="notification-empty">
          <p>No notifications found</p>
        </div>

        <div
          v-for="notification in filteredNotifications"
          :key="notification.id"
          class="notification-item"
          :class="{ 'notification-item--unread': !notification.isRead }"
        >
          <div class="notification-icon">
            <component :is="getNotificationIcon(notification.type)" />
          </div>

          <div class="notification-content">
            <h4 class="notification-item-title">{{ notification.title }}</h4>
            <p class="notification-item-desc">{{ notification.description }}</p>
          </div>

          <div class="notification-time">
            {{ formatTime(notification.timestamp) }}
          </div>

          <div
            v-if="!notification.isRead"
            class="notification-unread-dot"
            @click="markAsRead(notification.id)"
          ></div>
        </div>
      </div>
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import NotificationIconReservation from '@/components/icons/NotificationIconReservation.vue';
import NotificationIconEquipment from '@/components/icons/NotificationIconEquipment.vue';
import NotificationIconSystem from '@/components/icons/NotificationIconSystem.vue';
import NotificationIconMaintenance from '@/components/icons/NotificationIconMaintenance.vue';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { useNotificationStore } from '@/modules/notification/store/notificationStore.js';

const filterTabs = [
  { label: 'All', value: 'all' },
  { label: 'Unread', value: 'unread' },
  { label: 'Reservations', value: 'reservation' },
  { label: 'System', value: 'system' }
];

const activeFilter = ref('all');
const searchQuery = ref('');
const notificationStore = useNotificationStore();

const notifications = computed(() => notificationStore.notifications || []);
const unreadCount = computed(() => notificationStore.unreadCount || 0);

onMounted(() => {
  notificationStore.fetchNotifications().catch(() => {});
});

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
    filtered = filtered.filter((notification) =>
      notification.title.toLowerCase().includes(query)
      || notification.description.toLowerCase().includes(query)
    );
  }

  return filtered;
});

async function markAllAsRead() {
  await notificationStore.markAllAsRead().catch(() => {});
}

async function markAsRead(notificationId) {
  await notificationStore.markAsRead(notificationId).catch(() => {});
}

function getNotificationIcon(type) {
  const iconMap = {
    reservation: NotificationIconReservation,
    equipment: NotificationIconEquipment,
    system: NotificationIconSystem,
    maintenance: NotificationIconMaintenance
  };
  return iconMap[type] || NotificationIconSystem;
}

function formatTime(timestamp) {
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
}
</script>

<style scoped>
@import './css/NotificationPage.css';
</style>
