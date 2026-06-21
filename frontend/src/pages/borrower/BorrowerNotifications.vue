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
          v-for="notification in filteredNotifications"
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
      </div>
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import NotificationIconReservation from '@/components/icons/NotificationIconReservation.vue';
import NotificationIconEquipment from '@/components/icons/NotificationIconEquipment.vue';
import NotificationIconSystem from '@/components/icons/NotificationIconSystem.vue';
import NotificationIconMaintenance from '@/components/icons/NotificationIconMaintenance.vue';
import { useNotificationStore } from '@/modules/notification/store/notificationStore.js';

const searchQuery = ref('');
const activeFilter = ref('all');
const sortBy = ref('all');
const notificationStore = useNotificationStore();

const filterTabs = [
  { label: 'All', value: 'all' },
  { label: 'Unread', value: 'unread' },
  { label: 'Reservations', value: 'reservation' },
  { label: 'System', value: 'system' }
];

const notifications = computed(() => notificationStore.notifications || []);

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
.borrower-notifications-page {
  padding: 2rem;
  background-color: #f5f5f5;
  min-height: 100vh;
}

.notifications-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.notifications-title {
  font-size: 2rem;
  font-weight: 700;
  color: #333;
  margin: 0;
}

.mark-all-read-btn {
  padding: 0.5rem 1rem;
  background-color: transparent;
  border: 1px solid #999;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9rem;
  color: #666;
  transition: all 0.3s ease;
}

.mark-all-read-btn:hover {
  background-color: #f0f0f0;
  border-color: #666;
}

.notifications-controls {
  display: flex;
  gap: 2rem;
  margin-bottom: 2rem;
  align-items: center;
}

.search-group {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.search-group label {
  font-weight: 600;
  color: #333;
  font-size: 0.95rem;
}

.search-input {
  padding: 0.5rem 1rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.9rem;
  width: 200px;
}

.search-input:focus {
  outline: none;
  border-color: #1a6e3a;
  box-shadow: 0 0 0 3px rgba(26, 110, 58, 0.1);
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.filter-group label {
  font-weight: 600;
  color: #333;
  font-size: 0.95rem;
}

.filter-select {
  padding: 0.5rem 1rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.9rem;
  background-color: white;
  cursor: pointer;
}

.filter-select:focus {
  outline: none;
  border-color: #1a6e3a;
  box-shadow: 0 0 0 3px rgba(26, 110, 58, 0.1);
}

.filter-tabs {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  border-bottom: 2px solid #ddd;
}

.filter-tab {
  padding: 0.75rem 1.5rem;
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  font-weight: 600;
  color: #666;
  font-size: 0.95rem;
  transition: all 0.3s ease;
}

.filter-tab:hover {
  color: #333;
}

.filter-tab.active {
  color: #1a6e3a;
  border-bottom-color: #1a6e3a;
}

.notifications-list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #999;
  font-size: 1rem;
}

.notification-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background-color: white;
  border-bottom: 1px solid #eee;
  transition: background-color 0.2s ease;
}

.notification-item:hover {
  background-color: #f9f9f9;
}

.notification-item.unread {
  background-color: #f5f5f5;
}

.notification-icon {
  width: 40px;
  height: 40px;
  min-width: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  background-color: #f0f0f0;
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-title {
  font-size: 0.95rem;
  font-weight: 600;
  color: #333;
  margin: 0 0 0.25rem 0;
}

.notification-description {
  font-size: 0.85rem;
  color: #666;
  margin: 0;
}

.notification-time {
  font-size: 0.85rem;
  color: #999;
  min-width: 60px;
  text-align: right;
}

.unread-dot {
  width: 10px;
  height: 10px;
  min-width: 10px;
  background-color: #1a6e3a;
  border-radius: 50%;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.unread-dot:hover {
  background-color: #145a30;
}

@media (max-width: 768px) {
  .borrower-notifications-page {
    padding: 1rem;
  }

  .notifications-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .notifications-controls {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }

  .search-input {
    width: 100%;
  }

  .filter-tabs {
    flex-wrap: wrap;
  }

  .notification-item {
    flex-wrap: wrap;
  }

  .notification-time {
    order: 3;
    width: 100%;
    text-align: left;
    margin-top: 0.5rem;
  }
}
</style>
