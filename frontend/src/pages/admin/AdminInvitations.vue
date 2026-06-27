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
    <DataRequestStatusFloater :items="invitationStatusItems" />
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import DataRequestStatusFloater from '@/shared/components/DataRequestStatusFloater.vue';
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
const invitationStatusItems = computed(() => [
  {
    key: 'invitations',
    label: 'Invitations',
    state: resolveInvitationDataState(),
  },
]);

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
    if (invitations.value.length === 0) {
      invitations.value = [];
    }
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

function resolveInvitationDataState() {
  if (pageError.value && invitations.value.length === 0) return 'error';
  if (loading.value && invitations.value.length > 0) return 'cached-loading';
  if (loading.value) return 'loading';
  if (pageError.value) return 'cached';
  return invitations.value.length > 0 ? 'fresh' : 'idle';
}
</script>

<style scoped>
@import './css/AdminInvitations.css';
</style>

