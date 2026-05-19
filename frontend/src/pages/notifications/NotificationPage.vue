<!-- ===== Notification Page ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'NOTIFICATIONS'"
    :navigation-items="adminNavigationItems"
  >
    <div class="notification-page">
      <!-- Header -->
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

      <!-- Search and Filters -->
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

        <!-- Filter Tabs -->
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

      <!-- Notifications List -->
      <div class="notification-list">
        <div v-if="filteredNotifications.length === 0" class="notification-empty">
          <p>No notifications found</p>
        </div>

        <div
          v-for="(notification, index) in filteredNotifications"
          :key="notification.id"
          class="notification-item"
          :class="{ 'notification-item--unread': !notification.isRead }"
        >
          <!-- Icon -->
          <div class="notification-icon">
            <component :is="getNotificationIcon(notification.type)" />
          </div>

          <!-- Content -->
          <div class="notification-content">
            <h4 class="notification-item-title">{{ notification.title }}</h4>
            <p class="notification-item-desc">{{ notification.description }}</p>
          </div>

          <!-- Time -->
          <div class="notification-time">
            {{ formatTime(notification.timestamp) }}
          </div>

          <!-- Unread Indicator -->
          <div v-if="!notification.isRead" class="notification-unread-dot"></div>

          <!-- Delete Button -->
          <button
            @click="deleteNotification(notification.id)"
            class="notification-delete"
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
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import NotificationIconReservation from '@/components/icons/NotificationIconReservation.vue';
import NotificationIconEquipment from '@/components/icons/NotificationIconEquipment.vue';
import NotificationIconSystem from '@/components/icons/NotificationIconSystem.vue';
import NotificationIconMaintenance from '@/components/icons/NotificationIconMaintenance.vue';

const router = useRouter();

const adminNavigationItems = [
  {
    label: 'Dashboard',
    routeName: 'adminDashboardPage',
    iconSvg: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'
  },
  {
    label: 'Manage Accounts',
    routeName: 'adminManageAccountsPage',
    iconSvg: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'
  },
  {
    label: 'Manage Facilities',
    routeName: 'adminManageFacilitiesPage',
    iconSvg: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'
  },
  {
    label: 'Manage Equipment',
    routeName: 'adminManageEquipmentPage',
    iconSvg: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/><path d="M12 6v6l4 2"/></svg>'
  },
  {
    label: 'Pending Requests',
    routeName: 'adminPendingRequestsPage',
    iconSvg: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
  },
  {
    label: 'Approved Requests',
    routeName: 'adminApprovedRequestsPage',
    iconSvg: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>'
  },
  {
    label: 'Active Reservations',
    routeName: 'adminActiveReservationsPage',
    iconSvg: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>'
  },
  {
    label: 'Past Records',
    routeName: 'adminPastRecordsPage',
    iconSvg: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'
  },
  {
    label: 'Reports & Analytics',
    routeName: 'adminReportsAnalyticsPage',
    iconSvg: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="2" x2="12" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>'
  }
];

const filterTabs = [
  { label: 'All', value: 'all' },
  { label: 'Unread', value: 'unread' },
  { label: 'Reservations', value: 'reservation' },
  { label: 'System', value: 'system' }
];

const activeFilter = ref('all');
const searchQuery = ref('');
const selectedNotifications = ref([]);

const notifications = ref([
  {
    id: 1,
    type: 'reservation',
    title: 'New Reservation Request',
    description: 'Juan Dela Cruz requested 5 audio tables',
    timestamp: new Date(Date.now() - 1 * 60000),
    isRead: false
  },
  {
    id: 2,
    type: 'reservation',
    title: 'New Reservation Request',
    description: 'Michael Qui requested FOSS',
    timestamp: new Date(Date.now() - 20 * 60000),
    isRead: false
  },
  {
    id: 3,
    type: 'equipment',
    title: 'Overdue Equipment',
    description: '2 equipment items are overdue',
    timestamp: new Date(Date.now() - 21 * 60000),
    isRead: true
  },
  {
    id: 4,
    type: 'system',
    title: 'System Update',
    description: 'Database backup completed',
    timestamp: new Date(Date.now() - 3 * 60 * 60000),
    isRead: true
  },
  {
    id: 5,
    type: 'reservation',
    title: 'New Reservation Request',
    description: 'Marina Summers requested 2 speakers',
    timestamp: new Date(Date.now() - 5 * 60 * 60000),
    isRead: true
  },
  {
    id: 6,
    type: 'maintenance',
    title: 'Maintenance Alert',
    description: 'Chairs are incomplete',
    timestamp: new Date(Date.now() - 5 * 60 * 60000),
    isRead: true
  },
  {
    id: 7,
    type: 'equipment',
    title: 'Overdue Equipment',
    description: '1 equipment item is overdue',
    timestamp: new Date(Date.now() - 4 * 60 * 60000),
    isRead: true
  }
]);

const unreadCount = computed(() => {
  return notifications.value.filter(n => !n.isRead).length;
});

const filteredNotifications = computed(() => {
  let filtered = notifications.value;

  // Apply filter
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
    filtered = filtered.filter(n =>
      n.title.toLowerCase().includes(query) ||
      n.description.toLowerCase().includes(query)
    );
  }

  return filtered;
});

const allSelected = computed(() => {
  return filteredNotifications.value.length > 0 &&
    selectedNotifications.value.length === filteredNotifications.value.length;
});

function toggleSelectAll() {
  if (allSelected.value) {
    selectedNotifications.value = [];
  } else {
    selectedNotifications.value = filteredNotifications.value.map(n => n.id);
  }
}

function toggleNotificationSelect(notificationId) {
  const index = selectedNotifications.value.indexOf(notificationId);
  if (index > -1) {
    selectedNotifications.value.splice(index, 1);
  } else {
    selectedNotifications.value.push(notificationId);
  }
}

function markAllAsRead() {
  notifications.value.forEach(n => {
    n.isRead = true;
  });
}

function deleteNotification(notificationId) {
  const index = notifications.value.findIndex(n => n.id === notificationId);
  if (index > -1) {
    notifications.value.splice(index, 1);
  }
  selectedNotifications.value = selectedNotifications.value.filter(id => id !== notificationId);
}

function deleteSelected() {
  notifications.value = notifications.value.filter(
    n => !selectedNotifications.value.includes(n.id)
  );
  selectedNotifications.value = [];
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

function goBack() {
  router.back();
}
</script>

<style scoped>
@import './css/NotificationPage.css';
</style>

