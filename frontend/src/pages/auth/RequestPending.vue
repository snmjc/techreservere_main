<template>
  <div class="request-pending-page">
    <div class="pending-container">
      <div class="pending-card">
        <div class="pending-icon">⏳</div>
        <h1 class="pending-title">Account Pending Approval</h1>
        <p class="pending-description">
          Your account has been successfully registered and is currently pending administrator approval.
        </p>
        <p class="pending-description">
          You will receive an email notification once your account has been approved.
        </p>
        
        <div class="pending-info">
          <div class="info-item">
            <span class="info-label">Email:</span>
            <span class="info-value">{{ userEmail }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Name:</span>
            <span class="info-value">{{ userName }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Status:</span>
            <span class="info-value status-pending">Pending Approval</span>
          </div>
        </div>

        <div class="pending-actions">
          <button @click="handleLogout" class="btn btn-logout">
            Sign Out
          </button>
          <button @click="checkApprovalStatus" class="btn btn-check" :disabled="isChecking">
            {{ isChecking ? 'Checking...' : 'Check Status' }}
          </button>
        </div>

        <p class="pending-note">
          If you don't receive approval within 24-48 hours, please contact your administrator.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useUser, useAuth } from '@clerk/vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { signOutClerk } from '@/modules/authentication/utils/clerkAuthUtils.js';

const router = useRouter();
const { user, isSignedIn } = useUser();
const { signOut } = useAuth();
const authStore = useAuthenticationStore();

const isChecking = ref(false);

const userEmail = computed(() => user.value?.primaryEmailAddress?.emailAddress || '');
const userName = computed(() => {
  if (user.value) {
    return `${user.value.firstName || ''} ${user.value.lastName || ''}`.trim();
  }
  return '';
});

onMounted(() => {
  // Check if user is signed in
  if (!isSignedIn.value) {
    router.push({ name: 'clerkLoginPage' });
  }
});

async function handleLogout() {
<<<<<<< HEAD
  try {
    await signOutClerk(signOut);
  } finally {
    authStore.performLogout();
    router.push({ name: 'clerkLoginPage' });
  }
=======
  await signOut.value();
  authStore.performLogout();
  authStore.setClerkSignedOut();
  router.push({ name: 'clerkLoginPage' });
>>>>>>> bc882ef93b9a3d481a3bbd1e8f31f6f4ee910779
}

async function checkApprovalStatus() {
  isChecking.value = true;
  try {
    const account = await authStore.loadClerkAccount(getToken.value);

    if (!account) {
      alert('Unable to verify your account. Please try again later.');
      return;
    }

    if (account.status === 'approved') {
      if (account.roleDesignation === 'ROLE_ADMIN') {
        router.push({ name: 'adminDashboardPage' });
      } else {
        router.push({ name: 'borrowerMyReservationsPage' });
      }
      return;
    }

    if (account.status === 'rejected') {
      alert('Your registration has been rejected. Please contact the administrator.');
      return;
    }

    alert('Your account is still pending approval. Please check back later.');
  } catch (error) {
    console.error('Error checking approval status:', error);
    alert('Unable to check approval status. Please try again later.');
  } finally {
    isChecking.value = false;
  }
}
</script>

<style scoped>
.request-pending-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
}

.pending-container {
  max-width: 500px;
  width: 100%;
}

.pending-card {
  background: white;
  border-radius: 16px;
  padding: 3rem;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  text-align: center;
}

.pending-icon {
  font-size: 4rem;
  margin-bottom: 1.5rem;
}

.pending-title {
  font-size: 2rem;
  font-weight: 700;
  color: #333;
  margin: 0 0 1rem 0;
}

.pending-description {
  font-size: 1rem;
  color: #666;
  margin: 0 0 0.5rem 0;
  line-height: 1.6;
}

.pending-info {
  background: #f5f5f5;
  border-radius: 8px;
  padding: 1.5rem;
  margin: 2rem 0;
  text-align: left;
}

.info-item {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid #ddd;
}

.info-item:last-child {
  border-bottom: none;
}

.info-label {
  font-weight: 600;
  color: #333;
}

.info-value {
  color: #666;
}

.status-pending {
  color: #f39c12;
  font-weight: 600;
}

.pending-actions {
  display: flex;
  gap: 1rem;
  margin: 2rem 0;
}

.btn {
  flex: 1;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.btn-logout {
  background-color: #f44336;
  color: white;
}

.btn-logout:hover {
  background-color: #da190b;
}

.btn-check {
  background-color: #1a6e3a;
  color: white;
}

.btn-check:hover {
  background-color: #145a30;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.pending-note {
  font-size: 0.875rem;
  color: #999;
  margin: 1rem 0 0 0;
}

@media (max-width: 768px) {
  .pending-card {
    padding: 2rem;
  }

  .pending-title {
    font-size: 1.5rem;
  }

  .pending-actions {
    flex-direction: column;
  }
}
</style>
