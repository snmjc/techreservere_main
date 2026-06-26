<template>
  <AdminSidebarLayoutComponent
    :role-label="userRole"
    :navigation-items="navigationItems"
  >
    <div class="admin-users-page">
      <div class="users-header">
        <h1>User Management</h1>
        <div class="header-actions">
          <button @click="fetchPendingUsers" class="btn btn-refresh">
            Refresh
          </button>
        </div>
      </div>

      <div class="users-tabs">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          @click="activeTab = tab.value"
          :class="['tab-button', { active: activeTab === tab.value }]"
        >
          {{ tab.label }}
          <span v-if="tab.count > 0" class="tab-count">{{ tab.count }}</span>
        </button>
      </div>

      <div class="users-content">
        <div v-if="activeTab === 'pending'" class="users-section">
          <div v-if="loading" class="loading-state">
            Loading pending users...
          </div>
          <div v-else-if="pendingUsers.length === 0" class="empty-state">
            No pending users to approve.
          </div>
          <div v-else class="users-list">
            <div
              v-for="user in pendingUsers"
              :key="user.accountIdentifier"
              class="user-card"
            >
              <div class="user-info">
                <div class="user-avatar">
                  {{ user.firstName.charAt(0) }}{{ user.lastName.charAt(0) }}
                </div>
                <div class="user-details">
                  <h3 class="user-name">
                    {{ user.firstName }} {{ user.lastName }}
                  </h3>
                  <p class="user-email">{{ user.emailAddress }}</p>
                  <p class="user-role">{{ user.roleDesignation }}</p>
                  <p class="user-date">Registered: {{ formatDate(user.createdTimestamp) }}</p>
                </div>
              </div>
              <div class="user-actions">
                <button
                  @click="approveUser(user.accountIdentifier)"
                  class="btn btn-approve"
                  :disabled="isProcessing"
                >
                  Approve
                </button>
                <button
                  @click="rejectUser(user.accountIdentifier)"
                  class="btn btn-reject"
                  :disabled="isProcessing"
                >
                  Reject
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="activeTab === 'approved'" class="users-section">
          <div v-if="loading" class="loading-state">
            Loading approved users...
          </div>
          <div v-else-if="approvedUsers.length === 0" class="empty-state">
            No approved users found.
          </div>
          <div v-else class="users-list">
            <div
              v-for="user in approvedUsers"
              :key="user.accountIdentifier"
              class="user-card"
            >
              <div class="user-info">
                <div class="user-avatar approved">
                  {{ user.firstName.charAt(0) }}{{ user.lastName.charAt(0) }}
                </div>
                <div class="user-details">
                  <h3 class="user-name">
                    {{ user.firstName }} {{ user.lastName }}
                  </h3>
                  <p class="user-email">{{ user.emailAddress }}</p>
                  <p class="user-role">{{ user.roleDesignation }}</p>
                  <p class="user-date">Approved: {{ formatDate(user.createdTimestamp) }}</p>
                </div>
              </div>
              <div class="user-status">
                <span class="status-badge approved">Approved</span>
              </div>
            </div>
          </div>
        </div>

        <div v-if="activeTab === 'rejected'" class="users-section">
          <div v-if="loading" class="loading-state">
            Loading rejected users...
          </div>
          <div v-else-if="rejectedUsers.length === 0" class="empty-state">
            No rejected users found.
          </div>
          <div v-else class="users-list">
            <div
              v-for="user in rejectedUsers"
              :key="user.accountIdentifier"
              class="user-card"
            >
              <div class="user-info">
                <div class="user-avatar rejected">
                  {{ user.firstName.charAt(0) }}{{ user.lastName.charAt(0) }}
                </div>
                <div class="user-details">
                  <h3 class="user-name">
                    {{ user.firstName }} {{ user.lastName }}
                  </h3>
                  <p class="user-email">{{ user.emailAddress }}</p>
                  <p class="user-role">{{ user.roleDesignation }}</p>
                  <p class="user-date">Rejected: {{ formatDate(user.createdTimestamp) }}</p>
                </div>
              </div>
              <div class="user-status">
                <span class="status-badge rejected">Rejected</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { apiUrl } from '@/shared/utils/apiBase.js';

const authStore = useAuthenticationStore();

const activeTab = ref('pending');
const loading = ref(false);
const isProcessing = ref(false);
const pendingUsers = ref([]);
const approvedUsers = ref([]);
const rejectedUsers = ref([]);

const tabs = computed(() => [
  { label: 'Pending', value: 'pending', count: pendingUsers.value.length },
  { label: 'Approved', value: 'approved', count: approvedUsers.value.length },
  { label: 'Rejected', value: 'rejected', count: rejectedUsers.value.length },
]);

const userRole = computed(() => {
  const account = authStore.accountData;
  if (account && account.role) {
    return account.role === 'ROLE_ADMIN' ? 'ADMINISTRATOR' : 'BORROWER';
  }
  return 'USER';
});

const navigationItems = computed(() => {
  const account = authStore.accountData;
  if (account && account.role === 'ROLE_ADMIN') {
    return adminNavigationItems;
  }
  return [];
});

onMounted(() => {
  fetchPendingUsers();
});

async function fetchPendingUsers() {
  loading.value = true;
  try {
    const token = authStore.authToken;
    const response = await fetch(apiUrl('/api/v1/users/pending'), {
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    });

    if (response.ok) {
      const data = await response.json();
      pendingUsers.value = data.users || [];
    } else {
      console.error('Failed to fetch pending users');
    }
  } catch (error) {
    console.error('Error fetching pending users:', error);
  } finally {
    loading.value = false;
  }
}

async function approveUser(accountIdentifier) {
  if (!confirm('Are you sure you want to approve this user?')) {
    return;
  }

  isProcessing.value = true;
  try {
    const token = authStore.authToken;
    const response = await fetch(
      apiUrl(`/api/v1/users/${accountIdentifier}/approve`),
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      }
    );

    if (response.ok) {
      alert('User approved successfully');
      fetchPendingUsers();
    } else {
      alert('Failed to approve user');
    }
  } catch (error) {
    console.error('Error approving user:', error);
    alert('Error approving user');
  } finally {
    isProcessing.value = false;
  }
}

async function rejectUser(accountIdentifier) {
  if (!confirm('Are you sure you want to reject this user?')) {
    return;
  }

  isProcessing.value = true;
  try {
    const token = authStore.authToken;
    const response = await fetch(
      apiUrl(`/api/v1/users/${accountIdentifier}/reject`),
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      }
    );

    if (response.ok) {
      alert('User rejected successfully');
      fetchPendingUsers();
    } else {
      alert('Failed to reject user');
    }
  } catch (error) {
    console.error('Error rejecting user:', error);
    alert('Error rejecting user');
  } finally {
    isProcessing.value = false;
  }
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>

<style scoped>
@import './css/AdminUsers.css';
</style>

