<!-- ===== Requestor Approval Dashboard ===== -->
<template>
  <div class="approval-container">
    <div class="approval-header">
      <h1>Account Approval Requests</h1>
      <p>Review and approve pending user accounts</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-value">{{ pendingCount }}</div>
        <div class="stat-label">Pending</div>
      </div>
      <div class="stat-card approved">
        <div class="stat-value">{{ approvedCount }}</div>
        <div class="stat-label">Approved</div>
      </div>
      <div class="stat-card rejected">
        <div class="stat-value">{{ rejectedCount }}</div>
        <div class="stat-label">Rejected</div>
      </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <button
        v-for="tab in filterTabs"
        :key="tab.value"
        @click="activeFilter = tab.value"
        :class="{ active: activeFilter === tab.value }"
        class="filter-tab"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="loading-state">
      <p>Loading pending accounts...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredUsers.length === 0" class="empty-state">
      <p>No accounts to review</p>
    </div>

    <!-- Users List -->
    <div v-else class="users-list">
      <div
        v-for="user in filteredUsers"
        :key="user.id"
        class="user-card"
      >
        <div class="user-info">
          <div class="user-header">
            <h3 class="user-name">{{ user.full_name }}</h3>
            <span :class="['status-badge', user.status]">{{ user.status }}</span>
          </div>
          <div class="user-details">
            <div class="detail-item">
              <span class="label">Email:</span>
              <span class="value">{{ user.email }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Department:</span>
              <span class="value">{{ user.department || 'N/A' }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Organization:</span>
              <span class="value">{{ user.organization || 'N/A' }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Phone:</span>
              <span class="value">{{ user.phone || 'N/A' }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Applied:</span>
              <span class="value">{{ formatDate(user.created_at) }}</span>
            </div>
          </div>

          <!-- Rejection Reason (if rejected) -->
          <div v-if="user.status === 'rejected' && user.rejection_reason" class="rejection-reason">
            <strong>Rejection Reason:</strong>
            <p>{{ user.rejection_reason }}</p>
          </div>
        </div>

        <!-- Actions -->
        <div v-if="user.status === 'pending'" class="user-actions">
          <button
            @click="approveUser(user.id, user.email, user.full_name)"
            class="btn btn-approve"
            :disabled="actionLoading"
          >
            ✓ Approve
          </button>
          <button
            @click="showRejectDialog(user.id, user.full_name)"
            class="btn btn-reject"
            :disabled="actionLoading"
          >
            ✕ Reject
          </button>
        </div>
      </div>
    </div>

    <!-- Reject Dialog -->
    <div v-if="showRejectForm" class="modal-overlay" @click="closeRejectDialog">
      <div class="modal-content" @click.stop>
        <h2>Reject Account</h2>
        <p>Are you sure you want to reject <strong>{{ rejectingUserName }}</strong>'s account?</p>

        <div class="form-group">
          <label for="rejectionReason">Reason for Rejection (optional):</label>
          <textarea
            id="rejectionReason"
            v-model="rejectionReason"
            placeholder="Enter reason for rejection..."
            rows="4"
          ></textarea>
        </div>

        <div class="modal-actions">
          <button @click="closeRejectDialog" class="btn btn-secondary">Cancel</button>
          <button
            @click="confirmReject"
            class="btn btn-reject"
            :disabled="actionLoading"
          >
            {{ actionLoading ? 'Rejecting...' : 'Reject Account' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { pendingUserService } from '@/services/pendingUserApi';
import { emailService } from '@/services/emailService';

const pendingUsers = ref([]);
const isLoading = ref(false);
const actionLoading = ref(false);
const activeFilter = ref('pending');
const showRejectForm = ref(false);
const rejectingUserId = ref(null);
const rejectingUserName = ref('');
const rejectionReason = ref('');

const filterTabs = [
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' }
];

const filteredUsers = computed(() => {
  if (activeFilter.value === 'pending') {
    return pendingUsers.value.filter(u => u.status === 'pending');
  } else if (activeFilter.value === 'approved') {
    return pendingUsers.value.filter(u => u.status === 'approved');
  } else if (activeFilter.value === 'rejected') {
    return pendingUsers.value.filter(u => u.status === 'rejected');
  }
  return pendingUsers.value;
});

const pendingCount = computed(() => pendingUsers.value.filter(u => u.status === 'pending').length);
const approvedCount = computed(() => pendingUsers.value.filter(u => u.status === 'approved').length);
const rejectedCount = computed(() => pendingUsers.value.filter(u => u.status === 'rejected').length);

const fetchPendingUsers = async () => {
  isLoading.value = true;
  try {
    const result = await pendingUserService.getPendingUsers();
    if (result.success) {
      pendingUsers.value = result.data;
    } else {
      console.error('Error fetching users:', result.error);
    }
  } catch (error) {
    console.error('Error:', error);
  } finally {
    isLoading.value = false;
  }
};

const approveUser = async (userId, email, fullName) => {
  if (!confirm(`Are you sure you want to approve ${fullName}'s account?`)) {
    return;
  }

  actionLoading.value = true;
  try {
    // Approve user via backend API
    const result = await pendingUserService.approveUser(userId);
    if (!result.success) {
      alert('Error approving user: ' + result.error);
      actionLoading.value = false;
      return;
    }

    // Send approval email
    const emailResult = await emailService.sendApprovalEmail({
      email,
      fullName,
      organization: result.data.organization,
      supportEmail: 'support@techreserve.com'
    });

    if (emailResult.success) {
      // Update local state
      const userIndex = pendingUsers.value.findIndex(u => u.id === userId);
      if (userIndex !== -1) {
        pendingUsers.value[userIndex].status = 'approved';
        pendingUsers.value[userIndex].approved_at = new Date().toISOString();
      }
      alert('Account approved and email sent successfully!');
    } else {
      alert('Account approved but email failed to send: ' + emailResult.error);
    }
  } catch (error) {
    alert('Error: ' + error.message);
  } finally {
    actionLoading.value = false;
  }
};

const showRejectDialog = (userId, userName) => {
  rejectingUserId.value = userId;
  rejectingUserName.value = userName;
  rejectionReason.value = '';
  showRejectForm.value = true;
};

const closeRejectDialog = () => {
  showRejectForm.value = false;
  rejectingUserId.value = null;
  rejectingUserName.value = '';
  rejectionReason.value = '';
};

const confirmReject = async () => {
  actionLoading.value = true;
  try {
    const user = pendingUsers.value.find(u => u.id === rejectingUserId.value);
    if (!user) {
      alert('User not found');
      actionLoading.value = false;
      return;
    }

    // Reject user via backend API
    const result = await pendingUserService.rejectUser(rejectingUserId.value, rejectionReason.value);
    if (!result.success) {
      alert('Error rejecting user: ' + result.error);
      actionLoading.value = false;
      return;
    }

    // Send rejection email
    const emailResult = await emailService.sendRejectionEmail({
      email: user.email,
      fullName: user.full_name,
      organization: user.organization,
      supportEmail: 'support@techreserve.com'
    }, rejectionReason.value);

    if (emailResult.success) {
      // Update local state
      const userIndex = pendingUsers.value.findIndex(u => u.id === rejectingUserId.value);
      if (userIndex !== -1) {
        pendingUsers.value[userIndex].status = 'rejected';
        pendingUsers.value[userIndex].rejection_reason = rejectionReason.value;
        pendingUsers.value[userIndex].rejected_at = new Date().toISOString();
      }
      closeRejectDialog();
      alert('Account rejected and email sent successfully!');
    } else {
      alert('Account rejected but email failed to send: ' + emailResult.error);
    }
  } catch (error) {
    alert('Error: ' + error.message);
  } finally {
    actionLoading.value = false;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

onMounted(() => {
  fetchPendingUsers();
});
</script>

<style scoped>
.approval-container {
  padding: 2rem;
  background-color: #f5f5f5;
  min-height: 100vh;
}

.approval-header {
  margin-bottom: 2rem;
}

.approval-header h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #333;
  margin: 0;
}

.approval-header p {
  font-size: 1rem;
  color: #666;
  margin: 0.5rem 0 0 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  text-align: center;
  border-left: 4px solid #1a6e3a;
}

.stat-card.approved {
  border-left-color: #4caf50;
}

.stat-card.rejected {
  border-left-color: #f44336;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #333;
}

.stat-label {
  font-size: 0.9rem;
  color: #666;
  margin-top: 0.5rem;
}

.filter-tabs {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.filter-tab {
  padding: 0.75rem 1.5rem;
  background: white;
  border: 2px solid #ddd;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
}

.filter-tab:hover {
  border-color: #1a6e3a;
  color: #1a6e3a;
}

.filter-tab.active {
  background-color: #1a6e3a;
  color: white;
  border-color: #1a6e3a;
}

.loading-state,
.empty-state {
  background: white;
  padding: 3rem;
  border-radius: 8px;
  text-align: center;
  color: #666;
  font-size: 1rem;
}

.users-list {
  display: grid;
  gap: 1.5rem;
}

.user-card {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.user-info {
  flex: 1;
}

.user-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.user-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: #333;
  margin: 0;
}

.status-badge {
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-badge.pending {
  background-color: #fff3cd;
  color: #856404;
}

.status-badge.approved {
  background-color: #d4edda;
  color: #155724;
}

.status-badge.rejected {
  background-color: #f8d7da;
  color: #721c24;
}

.user-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-item .label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #666;
}

.detail-item .value {
  font-size: 0.95rem;
  color: #333;
}

.rejection-reason {
  margin-top: 1rem;
  padding: 1rem;
  background-color: #f8d7da;
  border-left: 4px solid #f44336;
  border-radius: 4px;
}

.rejection-reason strong {
  color: #721c24;
}

.rejection-reason p {
  margin: 0.5rem 0 0 0;
  color: #721c24;
}

.user-actions {
  display: flex;
  gap: 1rem;
  flex-shrink: 0;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.9rem;
  transition: all 0.3s ease;
}

.btn-approve {
  background-color: #4caf50;
  color: white;
}

.btn-approve:hover:not(:disabled) {
  background-color: #45a049;
}

.btn-reject {
  background-color: #f44336;
  color: white;
}

.btn-reject:hover:not(:disabled) {
  background-color: #da190b;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

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
  padding: 2rem;
  border-radius: 8px;
  max-width: 500px;
  width: 90%;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.modal-content h2 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #333;
  margin: 0 0 1rem 0;
}

.modal-content p {
  color: #666;
  margin: 0 0 1.5rem 0;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  font-weight: 600;
  color: #333;
  margin-bottom: 0.5rem;
}

.form-group textarea {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-family: inherit;
  font-size: 0.95rem;
  resize: vertical;
}

.form-group textarea:focus {
  outline: none;
  border-color: #1a6e3a;
  box-shadow: 0 0 0 3px rgba(26, 110, 58, 0.1);
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.btn-secondary {
  background-color: #ddd;
  color: #333;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #ccc;
}

@media (max-width: 768px) {
  .user-card {
    flex-direction: column;
  }

  .user-actions {
    width: 100%;
  }

  .user-actions .btn {
    flex: 1;
  }
}
</style>
