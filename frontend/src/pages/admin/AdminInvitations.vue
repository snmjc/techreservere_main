<template>
  <AdminSidebarLayoutComponent
    :role-label="userRole"
    :navigation-items="navigationItems"
  >
    <div class="admin-invitations-page">
      <div class="invitations-header">
        <h1>User Invitations</h1>
        <button @click="showInviteModal = true" class="btn btn-primary">
          Send Invitation
        </button>
      </div>

      <div class="invitations-content">
        <div v-if="loading" class="loading-state">
          Loading invitations...
        </div>
        <div v-else-if="pageError" class="empty-state">
          {{ pageError }}
        </div>
        <div v-else-if="invitations.length === 0" class="empty-state">
          No invitations sent yet.
        </div>
        <div v-else class="invitations-list">
          <div
            v-for="invitation in invitations"
            :key="invitation.id"
            class="invitation-card"
          >
            <div class="invitation-info">
              <div class="invitation-icon">✉️</div>
              <div class="invitation-details">
                <h3 class="invitation-email">{{ invitation.emailAddress }}</h3>
                <p class="invitation-role">Role: {{ invitation.role }}</p>
                <p class="invitation-date">Sent: {{ formatDate(invitation.sentAt) }}</p>
                <p class="invitation-status">
                  Status: <span :class="['status-badge', invitation.status]">
                    {{ invitation.status }}
                  </span>
                </p>
              </div>
            </div>
            <div class="invitation-actions">
              <button
                v-if="invitation.status === 'pending'"
                @click="resendInvitation(invitation.id)"
                class="btn btn-resend"
                :disabled="isProcessing"
              >
                Resend
              </button>
              <button
                @click="deleteInvitation(invitation.id)"
                class="btn btn-delete"
                :disabled="isProcessing"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Invite Modal -->
      <div v-if="showInviteModal" class="modal-overlay" @click="showInviteModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h2>Send User Invitation</h2>
            <button @click="showInviteModal = false" class="modal-close">×</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label for="inviteEmail">Email Address</label>
              <input
                id="inviteEmail"
                v-model="inviteForm.emailAddress"
                type="email"
                placeholder="user@example.com"
                required
              />
            </div>
            <div class="form-group">
              <label for="inviteRole">Role</label>
              <select id="inviteRole" v-model="inviteForm.role" required>
                <option value="ROLE_BORROWER">Borrower</option>
                <option value="ROLE_ADMIN">Admin</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button @click="showInviteModal = false" class="btn btn-secondary">
              Cancel
            </button>
            <button @click="sendInvitation" class="btn btn-primary" :disabled="isProcessing">
              {{ isProcessing ? 'Sending...' : 'Send Invitation' }}
            </button>
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
import { adminManageAccountsApi } from '@/services/adminManageAccountsApi.js';
import { apiUrl } from '@/shared/utils/apiBase.js';

const authStore = useAuthenticationStore();

const showInviteModal = ref(false);
const loading = ref(false);
const isProcessing = ref(false);
const invitations = ref([]);
const pageError = ref('');

const inviteForm = ref({
  emailAddress: '',
  role: 'ROLE_BORROWER',
});

const userRole = computed(() => {
  const role = String(
    authStore.accountData?.role
    || authStore.accountData?.roleDesignation
    || authStore.accountData?.role_designation
    || authStore.userRole
    || '',
  ).toUpperCase();

  return role.includes('ADMIN') ? 'ADMINISTRATOR' : 'BORROWER';
});

const navigationItems = computed(() => {
  return adminNavigationItems;
});

onMounted(() => {
  fetchInvitations();
});

async function fetchInvitations() {
  loading.value = true;
  try {
    pageError.value = '';
    const result = await adminManageAccountsApi.getAccounts(authStore.authToken);
    if (!result.success) {
      throw new Error(result.error || 'Failed to fetch invitation records.');
    }

    const accounts = Array.isArray(result.data?.accounts) ? result.data.accounts : [];
    invitations.value = accounts
      .filter((account) => account.inviteSentAt || account.inviteAcceptedAt || account.invitationStatus)
      .map((account) => ({
        id: account.accountIdentifier,
        emailAddress: account.emailAddress,
        role: account.roleDesignation || account.roleLabel || account.accountType,
        status: String(account.invitationStatus || 'not_sent').toLowerCase(),
        sentAt: account.inviteSentAt || account.invitedAt || null,
      }))
      .filter((invitation) => invitation.sentAt || invitation.status !== 'not_sent');
  } catch (error) {
    console.error('Error fetching invitations:', error);
    invitations.value = [];
    pageError.value = error.message || 'Failed to load invitation records.';
  } finally {
    loading.value = false;
  }
}

async function sendInvitation() {
  if (!inviteForm.value.emailAddress) {
    alert('Please enter an email address');
    return;
  }

  isProcessing.value = true;
  try {
    const token = authStore.authToken;
    const response = await fetch(apiUrl('/api/v1/users/invite'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      body: JSON.stringify({
        emailAddress: inviteForm.value.emailAddress,
        role: inviteForm.value.role,
        invitedBy: authStore.accountData?.accountIdentifier,
      }),
    });

    if (response.ok) {
      alert('Invitation sent successfully');
      showInviteModal.value = false;
      inviteForm.value.emailAddress = '';
      fetchInvitations();
    } else {
      const error = await response.json();
      alert(error.message || 'Failed to send invitation');
    }
  } catch (error) {
    console.error('Error sending invitation:', error);
    alert('Error sending invitation');
  } finally {
    isProcessing.value = false;
  }
}

async function resendInvitation(invitationId) {
  const invitationRecord = invitations.value.find((invitation) => invitation.id === invitationId);
  if (!invitationRecord) {
    alert('Invitation record not found.');
    return;
  }

  if (!confirm('Resend this invitation?')) {
    return;
  }

  isProcessing.value = true;
  try {
    const token = authStore.authToken;
    const response = await fetch(apiUrl('/api/v1/users/invite'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      body: JSON.stringify({
        emailAddress: invitationRecord.emailAddress,
        role: invitationRecord.role,
        invitedBy: authStore.accountData?.accountIdentifier,
      }),
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({}));
      throw new Error(error.message || 'Failed to resend invitation');
    }

    alert('Invitation resent successfully');
    fetchInvitations();
  } catch (error) {
    console.error('Error resending invitation:', error);
    alert(error.message || 'Error resending invitation');
  } finally {
    isProcessing.value = false;
  }
}

async function deleteInvitation(invitationId) {
  void invitationId;
  alert('Deleting invitation records is not supported by the current backend API.');
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
.admin-invitations-page {
  width: 100%;
  max-width: 1240px;
  margin: 0 auto;
  padding: 6rem 1rem 2rem;
}

.invitations-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.invitations-header h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #333;
  margin: 0;
}

.invitations-content {
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

.invitations-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.invitation-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  background: #f9f9f9;
  border-radius: 8px;
  border-left: 4px solid #1a6e3a;
}

.invitation-info {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex: 1;
}

.invitation-icon {
  font-size: 2rem;
}

.invitation-details {
  flex: 1;
}

.invitation-email {
  font-size: 1.1rem;
  font-weight: 600;
  color: #333;
  margin: 0 0 0.25rem 0;
}

.invitation-role,
.invitation-date,
.invitation-status {
  font-size: 0.875rem;
  color: #666;
  margin: 0 0 0.25rem 0;
}

.status-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge.pending {
  background-color: #fff3cd;
  color: #856404;
}

.status-badge.accepted {
  background-color: #d4edda;
  color: #155724;
}

.status-badge.declined {
  background-color: #f8d7da;
  color: #721c24;
}

.invitation-actions {
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

.btn-primary {
  background-color: #1a6e3a;
  color: white;
}

.btn-primary:hover {
  background-color: #145a30;
}

.btn-secondary {
  background-color: #ddd;
  color: #333;
}

.btn-secondary:hover {
  background-color: #ccc;
}

.btn-resend {
  background-color: #2196f3;
  color: white;
}

.btn-resend:hover {
  background-color: #1976d2;
}

.btn-delete {
  background-color: #f44336;
  color: white;
}

.btn-delete:hover {
  background-color: #da190b;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  padding: 2rem;
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.modal-header h2 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #333;
  margin: 0;
}

.modal-close {
  background: none;
  border: none;
  font-size: 2rem;
  cursor: pointer;
  color: #666;
  padding: 0;
  line-height: 1;
}

.modal-close:hover {
  color: #333;
}

.modal-body {
  margin-bottom: 1.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  font-weight: 600;
  color: #333;
  margin-bottom: 0.5rem;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 1rem;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #1a6e3a;
  box-shadow: 0 0 0 3px rgba(26, 110, 58, 0.1);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

@media (max-width: 768px) {
  .admin-invitations-page {
    padding: 1rem;
  }

  .invitations-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .invitation-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .invitation-actions {
    width: 100%;
    justify-content: flex-start;
  }

  .modal-content {
    padding: 1.5rem;
  }

  .modal-footer {
    flex-direction: column;
  }

  .modal-footer .btn {
    width: 100%;
  }
}
</style>
