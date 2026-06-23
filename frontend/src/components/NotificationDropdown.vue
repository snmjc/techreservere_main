<!-- ===== Notification Dropdown Component ===== -->
<template>
  <div class="notification-dropdown-wrapper">
    <!-- Notification Icon Button -->
    <button
      class="notification-icon-button"
      @click="toggleDropdown"
      :class="{ 'notification-icon-button--active': isDropdownOpen }"
    >
      <svg class="notification-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
      </svg>
      <span v-if="unreadCount > 0" class="notification-badge">{{ unreadCount }}</span>
    </button>

    <!-- Notification Dropdown Panel -->
    <transition name="notification-dropdown-fade">
      <div v-if="isDropdownOpen" class="notification-dropdown-panel">
        <!-- Header -->
        <div class="notification-dropdown-header">
          <h3 class="notification-dropdown-title">Notifications</h3>
          <button
            class="notification-mark-all-read"
            @click="markAllAsRead"
            :disabled="unreadCount === 0"
          >
            Mark all as read
          </button>
        </div>

        <!-- Notifications List -->
        <div class="notification-dropdown-list">
          <div v-if="notifications.length === 0" class="notification-empty-state">
            <p>No notifications</p>
          </div>

          <div
            v-for="notification in displayedNotifications"
            :key="notification.id"
            class="notification-dropdown-item"
            :class="{ 'notification-dropdown-item--unread': !notification.isRead }"
          >
            <!-- Icon -->
            <div class="notification-dropdown-icon">
              <component :is="getNotificationIcon(notification.type)" />
            </div>

            <!-- Content -->
            <div class="notification-dropdown-content">
              <h4 class="notification-dropdown-item-title">{{ notification.title }}</h4>
              <p class="notification-dropdown-item-description">{{ notification.description }}</p>
              <span class="notification-dropdown-item-time">{{ formatTime(notification.timestamp) }}</span>
            </div>

            <!-- Read Indicator -->
            <div
              v-if="!notification.isRead"
              class="notification-dropdown-unread-dot"
              @click="markAsRead(notification.id)"
            ></div>
          </div>
        </div>

        <!-- Footer -->
        <div class="notification-dropdown-footer">
          <router-link to="/notifications" class="notification-view-all-link">
            View all notifications
          </router-link>
        </div>
      </div>
    </transition>

    <!-- Overlay -->
    <div
      v-if="isDropdownOpen"
      class="notification-dropdown-overlay"
      @click="closeDropdown"
    ></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import NotificationIconReservation from '@/components/icons/NotificationIconReservation.vue';
import NotificationIconEquipment from '@/components/icons/NotificationIconEquipment.vue';
import NotificationIconSystem from '@/components/icons/NotificationIconSystem.vue';
import NotificationIconMaintenance from '@/components/icons/NotificationIconMaintenance.vue';
import { useNotificationStore } from '@/modules/notification/store/notificationStore.js';

const isDropdownOpen = ref(false);
const notificationStore = useNotificationStore();

const notifications = computed(() => notificationStore.notifications || []);
const displayedNotifications = computed(() => notifications.value.slice(0, 5));
const unreadCount = computed(() => notificationStore.unreadCount || 0);

function toggleDropdown() {
  isDropdownOpen.value = !isDropdownOpen.value;
  if (isDropdownOpen.value) {
    notificationStore.fetchNotifications(true).catch(() => {});
  }
}

function closeDropdown() {
  isDropdownOpen.value = false;
}

async function markAsRead(notificationId) {
  await notificationStore.markAsRead(notificationId).catch(() => {});
}

async function markAllAsRead() {
  await notificationStore.markAllAsRead().catch(() => {});
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

function handleClickOutside(event) {
  const wrapper = document.querySelector('.notification-dropdown-wrapper');
  if (wrapper && !wrapper.contains(event.target)) {
    closeDropdown();
  }
}

onMounted(() => {
  notificationStore.fetchNotifications(true).catch(() => {});
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
@import './css/NotificationDropdown.css';
</style>
