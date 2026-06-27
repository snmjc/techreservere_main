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
        v-for="user in paginatedUsers"
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
      <div v-if="totalPages > 1" class="approval-pagination">
        <button type="button" :disabled="currentPage === 1" @click="currentPage -= 1">Previous</button>
        <span>Page {{ currentPage }} of {{ totalPages }}</span>
        <button type="button" :disabled="currentPage === totalPages" @click="currentPage += 1">Next</button>
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
    <DataRequestStatusFloater :items="approvalStatusItems" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import DataRequestStatusFloater from '@/shared/components/DataRequestStatusFloater.vue';
import { pendingUserService } from '@/services/pendingUserApi';
import { emailService } from '@/services/emailService';

const pendingUsers = ref([]);
const isLoading = ref(false);
const approvalDataState = ref('idle');
const actionLoading = ref(false);
const activeFilter = ref('pending');
const showRejectForm = ref(false);
const rejectingUserId = ref(null);
const rejectingUserName = ref('');
const rejectionReason = ref('');
const currentPage = ref(1);
const pageSize = 8;

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
const totalPages = computed(() => Math.max(1, Math.ceil(filteredUsers.value.length / pageSize)));
const paginatedUsers = computed(() => {
  const startIndex = (currentPage.value - 1) * pageSize;
  return filteredUsers.value.slice(startIndex, startIndex + pageSize);
});

const pendingCount = computed(() => pendingUsers.value.filter(u => u.status === 'pending').length);
const approvedCount = computed(() => pendingUsers.value.filter(u => u.status === 'approved').length);
const rejectedCount = computed(() => pendingUsers.value.filter(u => u.status === 'rejected').length);
const approvalStatusItems = computed(() => [
  {
    key: 'approval-users',
    label: 'Approval Requests',
    state: approvalDataState.value,
  },
]);

watch(activeFilter, () => {
  currentPage.value = 1;
});

watch(totalPages, (pageCount) => {
  if (currentPage.value > pageCount) {
    currentPage.value = pageCount;
  }
});

const fetchPendingUsers = async () => {
  isLoading.value = true;
  approvalDataState.value = pendingUsers.value.length > 0 ? 'cached-loading' : 'loading';
  try {
    const result = await pendingUserService.getPendingUsers();
    if (result.success) {
      pendingUsers.value = result.data;
      approvalDataState.value = 'fresh';
    } else {
      console.error('Error fetching users:', result.error);
      approvalDataState.value = pendingUsers.value.length > 0 ? 'cached' : 'error';
    }
  } catch (error) {
    console.error('Error:', error);
    approvalDataState.value = pendingUsers.value.length > 0 ? 'cached' : 'error';
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
@import './css/RequestorApproval.css';
</style>

