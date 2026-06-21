import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { notificationApi } from '@/modules/notification/services/notificationApi.js';

function normalizeNotificationType(type) {
  const normalized = String(type || '').trim().toLowerCase();
  if (normalized === 'reservation') return 'reservation';
  if (normalized === 'equipment') return 'equipment';
  if (normalized === 'maintenance') return 'maintenance';
  return 'system';
}

function normalizeNotificationRecord(notification) {
  return {
    id: notification.notificationIdentifier,
    type: normalizeNotificationType(notification.notificationType),
    title: notification.notificationTitle || 'Notification',
    description: notification.notificationMessage || 'No additional details provided.',
    timestamp: notification.createdTimestamp ? new Date(notification.createdTimestamp) : new Date(),
    isRead: Boolean(notification.isRead),
  };
}

export const useNotificationStore = defineStore('notificationStore', () => {
  const notifications = ref([]);
  const isLoading = ref(false);
  const hasLoaded = ref(false);

  const unreadCount = computed(() => notifications.value.filter((notification) => !notification.isRead).length);

  async function fetchNotifications(force = false) {
    if (isLoading.value || (hasLoaded.value && !force)) {
      return notifications.value;
    }

    isLoading.value = true;
    try {
      const response = await notificationApi.listNotifications();
      notifications.value = response.map(normalizeNotificationRecord);
      hasLoaded.value = true;
      return notifications.value;
    } finally {
      isLoading.value = false;
    }
  }

  async function markAsRead(notificationId) {
    const notification = notifications.value.find((item) => item.id === notificationId);
    if (!notification || notification.isRead) {
      return;
    }

    notification.isRead = true;
    try {
      await notificationApi.markAsRead(notificationId);
    } catch (error) {
      notification.isRead = false;
      throw error;
    }
  }

  async function markAllAsRead() {
    const unreadNotifications = notifications.value.filter((notification) => !notification.isRead);
    if (unreadNotifications.length === 0) {
      return;
    }

    unreadNotifications.forEach((notification) => {
      notification.isRead = true;
    });

    try {
      await Promise.all(unreadNotifications.map((notification) => notificationApi.markAsRead(notification.id)));
    } catch (error) {
      unreadNotifications.forEach((notification) => {
        notification.isRead = false;
      });
      throw error;
    }
  }

  return {
    notifications,
    isLoading,
    unreadCount,
    fetchNotifications,
    markAsRead,
    markAllAsRead,
  };
});
