<!-- ===== Notification Page ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'NOTIFICATIONS'"
    :navigation-items="adminNavigationItems"
  >
    <div style="padding: 2rem; background-color: #f5f5f5; min-height: 100vh;">
      <!-- Header -->
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 style="font-size: 2rem; font-weight: 700; color: #333; margin: 0;">Notifications</h1>
        <button
          @click="markAllAsRead"
          :disabled="unreadCount === 0"
          style="padding: 0.75rem 1.5rem; background-color: #1a6e3a; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.95rem;"
          :style="{ backgroundColor: unreadCount === 0 ? '#ccc' : '#1a6e3a', cursor: unreadCount === 0 ? 'not-allowed' : 'pointer' }"
        >
          Mark all as read
        </button>
      </div>

      <!-- Search and Filters -->
      <div style="background-color: white; padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 8px;">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
          <label style="font-weight: 600; color: #333;">Search:</label>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Name"
            style="flex: 1; padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95rem;"
          />
          <label style="font-weight: 600; color: #333;">Showing:</label>
          <select
            v-model="activeFilter"
            style="padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95rem; background-color: white;"
          >
            <option value="all">All</option>
            <option value="unread">Unread</option>
            <option value="reservation">Reservations</option>
            <option value="system">System</option>
          </select>
        </div>

        <!-- Filter Tabs -->
        <div style="display: flex; gap: 1rem; border-bottom: 2px solid #eee;">
          <button
            v-for="tab in filterTabs"
            :key="tab.value"
            @click="activeFilter = tab.value"
            style="padding: 1rem 0; border: none; background: none; cursor: pointer; font-weight: 600; font-size: 0.95rem; color: #666; border-bottom: 3px solid transparent; transition: all 0.3s ease;"
            :style="{ color: activeFilter === tab.value ? '#1a6e3a' : '#666', borderBottomColor: activeFilter === tab.value ? '#1a6e3a' : 'transparent' }"
          >
            {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- Notifications List -->
      <div style="background-color: white; border-radius: 8px; overflow: hidden;">
        <div v-if="filteredNotifications.length === 0" style="padding: 3rem; text-align: center; color: #999;">
          <p>No notifications found</p>
        </div>

        <div
          v-for="(notification, index) in filteredNotifications"
          :key="notification.id"
          style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem; border-bottom: 1px solid #eee; transition: background-color 0.2s ease;"
          :style="{ backgroundColor: !notification.isRead ? '#f9f9f9' : '#fff' }"
          @mouseenter="$event.target.style.backgroundColor = '#f5f5f5'"
          @mouseleave="$event.target.style.backgroundColor = !notification.isRead ? '#f9f9f9' : '#fff'"
        >
          <!-- Icon -->
          <div style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <component :is="getNotificationIcon(notification.type)" />
          </div>

          <!-- Content -->
          <div style="flex: 1; min-width: 0;">
            <h4 style="font-size: 0.95rem; font-weight: 600; color: #333; margin: 0 0 0.25rem 0;">{{ notification.title }}</h4>
            <p style="font-size: 0.85rem; color: #666; margin: 0;">{{ notification.description }}</p>
          </div>

          <!-- Time -->
          <div style="font-size: 0.85rem; color: #999; min-width: 60px; text-align: right;">
            {{ formatTime(notification.timestamp) }}
          </div>

          <!-- Unread Indicator -->
          <div v-if="!notification.isRead" style="width: 8px; height: 8px; background-color: #1a6e3a; border-radius: 50%; flex-shrink: 0;"></div>

          <!-- Delete Button -->
          <button
            @click="deleteNotification(notification.id)"
            style="padding: 0.5rem 1rem; background-color: #ff6b6b; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; flex-shrink: 0;"
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

