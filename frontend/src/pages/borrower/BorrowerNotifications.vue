<!-- ===== Borrower Notifications Page ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'BORROWER'"
    :navigation-items="borrowerNavigationItems"
  >
    <div class="borrower-notifications-page">
      <!-- Header Section -->
      <div class="notifications-header">
        <h1 class="notifications-title">Notifications</h1>
        <button @click="markAllAsRead" class="mark-all-read-btn">
          Mark all as read
        </button>
      </div>

      <!-- Search and Filter Section -->
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

      <!-- Filter Tabs -->
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

      <!-- Notifications List -->
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
          <!-- Icon -->
          <div class="notification-icon">
            <component :is="getNotificationIcon(notification.type)" />
          </div>

          <!-- Content -->
          <div class="notification-content">
            <h3 class="notification-title">{{ notification.title }}</h3>
            <p class="notification-description">{{ notification.description }}</p>
          </div>

          <!-- Time -->
          <div class="notification-time">
            {{ formatTime(notification.timestamp) }}
          </div>

          <!-- Unread Indicator -->
          <div
            v-if="!notification.isRead"
            class="unread-dot"
            @click="markAsRead(notification.id)"
          ></div>

          <!-- Delete Button -->
          <button
            @click="deleteNotification(notification.id)"
            class="delete-btn"
            title="Delete notification"
          >
            🗑️
          </button>
        </div>
      </div>
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';
import NotificationIconReservation from '@/components/icons/NotificationIconReservation.vue';
import NotificationIconEquipment from '@/components/icons/NotificationIconEquipment.vue';
import NotificationIconSystem from '@/components/icons/NotificationIconSystem.vue';
import NotificationIconMaintenance from '@/components/icons/NotificationIconMaintenance.vue';

const searchQuery = ref('');
const activeFilter = ref('all');
const sortBy = ref('all');

const filterTabs = [
  { label: 'All', value: 'all' },
  { label: 'Unread', value: 'unread' },
  { label: 'Reservations', value: 'reservation' },
  { label: 'System', value: 'system' }
];

const notifications = ref([
  {
    id: 1,
    type: 'reservation',
    title: 'New Reservation Request',
    description: 'Juan Dela Cruz requested 5 white tables.',
    timestamp: new Date(Date.now() - 1 * 60000),
    isRead: false
  },
  {
    id: 2,
    type: 'reservation',
    title: 'New Reservation Request',
    description: 'Michael Que requested F503.',
    timestamp: new Date(Date.now() - 20 * 60000),
    isRead: false
  },
  {
    id: 3,
    type: 'equipment',
    title: 'Overdue Equipment',
    description: '2 equipment items are overdue.',
    timestamp: new Date(Date.now() - 21 * 60000),
    isRead: false
  },
  {
    id: 4,
    type: 'system',
    title: 'System Update',
    description: 'Database backup completed.',
    timestamp: new Date(Date.now() - 3 * 60 * 60000),
    isRead: false
  },
  {
    id: 5,
    type: 'reservation',
    title: 'New Reservation Request',
    description: 'Marina Summers requested 2 speakers.',
    timestamp: new Date(Date.now() - 5 * 60 * 60000),
    isRead: true
  },
  {
    id: 6,
    type: 'maintenance',
    title: 'Maintenance Alert',
    description: 'Chairs are incomplete.',
    timestamp: new Date(Date.now() - 5 * 60 * 60000),
    isRead: true
  },
  {
    id: 7,
    type: 'equipment',
    title: 'Overdue Equipment',
    description: '1 equipment item is overdue.',
    timestamp: new Date(Date.now() - 6 * 60 * 60000),
    isRead: true
  }
]);

const filteredNotifications = computed(() => {
  let filtered = notifications.value;

  // Apply filter tab
  if (activeFilter.value !== 'all') {
    if (activeFilter.value === 'unread') {
      filtered = filtered.filter(n => !n.isRead);
    } else {
      filtered = filtered.filter(n => n.type === activeFilter.value);
    }
  }

  // Apply search
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(
      n =>
        n.title.toLowerCase().includes(query) ||
        n.description.toLowerCase().includes(query)
    );
  }

  // Apply sorting
  if (sortBy.value === 'newest') {
    filtered.sort((a, b) => b.timestamp - a.timestamp);
  } else if (sortBy.value === 'oldest') {
    filtered.sort((a, b) => a.timestamp - b.timestamp);
  }

  return filtered;
});

const markAllAsRead = () => {
  notifications.value.forEach(n => {
    n.isRead = true;
  });
};

const markAsRead = (notificationId) => {
  const notification = notifications.value.find(n => n.id === notificationId);
  if (notification) {
    notification.isRead = true;
  }
};

const deleteNotification = (notificationId) => {
  notifications.value = notifications.value.filter(n => n.id !== notificationId);
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

.delete-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.1rem;
  padding: 0.25rem 0.5rem;
  transition: opacity 0.2s ease;
}

.delete-btn:hover {
  opacity: 0.7;
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
