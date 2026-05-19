<!-- ===== Security Settings Page ===== -->
<template>
  <div class="settings-page">
    <div class="settings-container">
      <!-- Header -->
      <div class="settings-header">
        <h1>Security Settings</h1>
        <p>Manage your account security and access</p>
      </div>

      <!-- Settings Content -->
      <div class="settings-content">
        <!-- Password Section -->
        <div class="settings-section">
          <h2>Password</h2>
          
          <div class="form-group">
            <label for="currentPassword">Current Password</label>
            <input
              id="currentPassword"
              v-model="passwordForm.currentPassword"
              type="password"
              placeholder="Enter your current password"
            />
          </div>

          <div class="form-group">
            <label for="newPassword">New Password</label>
            <input
              id="newPassword"
              v-model="passwordForm.newPassword"
              type="password"
              placeholder="Enter your new password"
            />
            <small>At least 8 characters with uppercase, lowercase, and numbers</small>
          </div>

          <div class="form-group">
            <label for="confirmPassword">Confirm Password</label>
            <input
              id="confirmPassword"
              v-model="passwordForm.confirmPassword"
              type="password"
              placeholder="Confirm your new password"
            />
          </div>

          <button @click="changePassword" class="btn btn-primary">
            Change Password
          </button>
        </div>

        <!-- Two-Factor Authentication -->
        <div class="settings-section">
          <h2>Two-Factor Authentication</h2>
          <p>Add an extra layer of security to your account</p>

          <div class="security-item">
            <div class="security-info">
              <h3>Authenticator App</h3>
              <p>Use an authenticator app to generate security codes</p>
            </div>
            <button
              @click="enable2FA"
              :class="['btn', twoFactorEnabled ? 'btn-secondary' : 'btn-primary']"
            >
              {{ twoFactorEnabled ? 'Disable' : 'Enable' }}
            </button>
          </div>
        </div>

        <!-- Active Sessions -->
        <div class="settings-section">
          <h2>Active Sessions</h2>
          <p>Manage your active login sessions</p>

          <div v-for="session in activeSessions" :key="session.id" class="session-item">
            <div class="session-info">
              <h3>{{ session.device }}</h3>
              <p>{{ session.location }}</p>
              <small>Last active: {{ formatDate(session.lastActive) }}</small>
            </div>
            <button @click="logoutSession(session.id)" class="btn btn-small btn-danger">
              Logout
            </button>
          </div>

          <button @click="logoutAllSessions" class="btn btn-danger" style="margin-top: 1rem;">
            Logout All Other Sessions
          </button>
        </div>

        <!-- Login History -->
        <div class="settings-section">
          <h2>Login History</h2>

          <div class="login-history">
            <div v-for="login in loginHistory" :key="login.id" class="history-item">
              <div class="history-info">
                <h3>{{ login.device }}</h3>
                <p>{{ login.location }}</p>
              </div>
              <div class="history-time">
                {{ formatDate(login.timestamp) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const passwordForm = ref({
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
});

const twoFactorEnabled = ref(false);

const activeSessions = ref([
  {
    id: 1,
    device: 'Chrome on Windows',
    location: 'Manila, Philippines',
    lastActive: new Date()
  },
  {
    id: 2,
    device: 'Safari on iPhone',
    location: 'Manila, Philippines',
    lastActive: new Date(Date.now() - 2 * 60 * 60 * 1000)
  }
]);

const loginHistory = ref([
  {
    id: 1,
    device: 'Chrome on Windows',
    location: 'Manila, Philippines',
    timestamp: new Date()
  },
  {
    id: 2,
    device: 'Safari on iPhone',
    location: 'Manila, Philippines',
    timestamp: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000)
  },
  {
    id: 3,
    device: 'Firefox on Windows',
    location: 'Quezon City, Philippines',
    timestamp: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000)
  }
]);

const changePassword = () => {
  if (!passwordForm.value.currentPassword || !passwordForm.value.newPassword || !passwordForm.value.confirmPassword) {
    alert('Please fill in all password fields');
    return;
  }

  if (passwordForm.value.newPassword !== passwordForm.value.confirmPassword) {
    alert('New passwords do not match');
    return;
  }

  alert('Password changed successfully!');
  passwordForm.value = {
    currentPassword: '',
    newPassword: '',
    confirmPassword: ''
  };
};

const enable2FA = () => {
  twoFactorEnabled.value = !twoFactorEnabled.value;
  alert(twoFactorEnabled.value ? 'Two-factor authentication enabled' : 'Two-factor authentication disabled');
};

const logoutSession = (sessionId) => {
  activeSessions.value = activeSessions.value.filter(s => s.id !== sessionId);
  alert('Session logged out');
};

const logoutAllSessions = () => {
  if (confirm('This will logout all other sessions. Continue?')) {
    activeSessions.value = activeSessions.value.slice(0, 1);
    alert('All other sessions logged out');
  }
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>

<style scoped>
@import './css/SecuritySettings.css';
</style>


