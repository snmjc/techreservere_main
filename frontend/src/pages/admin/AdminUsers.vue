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
    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/v1/users/pending`, {
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
      `${import.meta.env.VITE_API_BASE_URL}/api/v1/users/${accountIdentifier}/approve`,
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
      `${import.meta.env.VITE_API_BASE_URL}/api/v1/users/${accountIdentifier}/reject`,
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
.admin-users-page {
  padding: 2rem;
  margin-left: 240px;
  margin-top: 56px;
}

.users-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.users-header h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #333;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.users-tabs {
  display: flex;
  gap: 1rem;
  border-bottom: 2px solid #ddd;
  margin-bottom: 2rem;
}

.tab-button {
  padding: 0.75rem 1.5rem;
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  font-weight: 600;
  color: #666;
  font-size: 1rem;
  transition: all 0.3s ease;
  position: relative;
}

.tab-button:hover {
  color: #333;
}

.tab-button.active {
  color: #1a6e3a;
  border-bottom-color: #1a6e3a;
}

.tab-count {
  background-color: #f44336;
  color: white;
  border-radius: 50%;
  padding: 0.1rem 0.5rem;
  font-size: 0.75rem;
  margin-left: 0.5rem;
}

.users-section {
  background: white;
  border-radius: 8px;
  padding: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.loading-state,
.empty-state {
  text-align: center;
  padding: 3rem;
  color: #666;
  font-size: 1rem;
}

.users-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.user-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  background: #f9f9f9;
  border-radius: 8px;
  border-left: 4px solid #ddd;
}

.user-info {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex: 1;
}

.user-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background-color: #ddd;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.25rem;
  color: #666;
}

.user-avatar.approved {
  background-color: #4caf50;
  color: white;
}

.user-avatar.rejected {
  background-color: #f44336;
  color: white;
}

.user-details {
  flex: 1;
}

.user-name {
  font-size: 1.1rem;
  font-weight: 600;
  color: #333;
  margin: 0 0 0.25rem 0;
}

.user-email {
  font-size: 0.9rem;
  color: #666;
  margin: 0 0 0.25rem 0;
}

.user-role {
  font-size: 0.875rem;
  color: #999;
  margin: 0 0 0.25rem 0;
}

.user-date {
  font-size: 0.875rem;
  color: #999;
  margin: 0;
}

.user-actions {
  display: flex;
  gap: 0.5rem;
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.875rem;
  transition: all 0.3s ease;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-refresh {
  background-color: #1a6e3a;
  color: white;
}

.btn-refresh:hover {
  background-color: #145a30;
}

.btn-approve {
  background-color: #4caf50;
  color: white;
}

.btn-approve:hover {
  background-color: #45a049;
}

.btn-reject {
  background-color: #f44336;
  color: white;
}

.btn-reject:hover {
  background-color: #da190b;
}

.user-status {
  display: flex;
  align-items: center;
}

.status-badge {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
}

.status-badge.approved {
  background-color: #e8f5e9;
  color: #4caf50;
}

.status-badge.rejected {
  background-color: #ffebee;
  color: #f44336;
}

@media (max-width: 768px) {
  .admin-users-page {
    padding: 1rem;
  }

  .users-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .user-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .user-actions {
    width: 100%;
    justify-content: flex-start;
  }

  .users-tabs {
    flex-wrap: wrap;
  }
}
</style>
