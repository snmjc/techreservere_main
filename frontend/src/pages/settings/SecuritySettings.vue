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
.settings-page {
  min-height: 100vh;
  background-color: #f5f5f5;
  padding: 2rem;
}

.settings-container {
  max-width: 800px;
  margin: 0 auto;
}

.settings-header {
  margin-bottom: 2rem;
}

.settings-header h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #333;
  margin: 0;
}

.settings-header p {
  font-size: 1rem;
  color: #666;
  margin: 0.5rem 0 0 0;
}

.settings-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.settings-section {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.settings-section h2 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #333;
  margin: 0 0 1.5rem 0;
}

.settings-section p {
  font-size: 0.9rem;
  color: #666;
  margin: 0 0 1rem 0;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  font-weight: 600;
  color: #333;
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
}

.form-group input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 0.95rem;
  transition: border-color 0.3s ease;
}

.form-group input:focus {
  outline: none;
  border-color: #1a6e3a;
  box-shadow: 0 0 0 3px rgba(26, 110, 58, 0.1);
}

.form-group small {
  display: block;
  font-size: 0.85rem;
  color: #999;
  margin-top: 0.25rem;
}

.security-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  background-color: #f9f9f9;
  border-radius: 6px;
  margin-bottom: 1rem;
}

.security-info h3 {
  font-size: 1rem;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.security-info p {
  font-size: 0.9rem;
  color: #666;
  margin: 0.25rem 0 0 0;
}

.session-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border: 1px solid #eee;
  border-radius: 6px;
  margin-bottom: 1rem;
}

.session-info h3 {
  font-size: 0.95rem;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.session-info p {
  font-size: 0.85rem;
  color: #666;
  margin: 0.25rem 0;
}

.session-info small {
  font-size: 0.8rem;
  color: #999;
}

.login-history {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.history-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border: 1px solid #eee;
  border-radius: 6px;
}

.history-info h3 {
  font-size: 0.95rem;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.history-info p {
  font-size: 0.85rem;
  color: #666;
  margin: 0.25rem 0 0 0;
}

.history-time {
  font-size: 0.85rem;
  color: #999;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.95rem;
  transition: all 0.3s ease;
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

.btn-danger {
  background-color: #f44336;
  color: white;
}

.btn-danger:hover {
  background-color: #da190b;
}

.btn-small {
  padding: 0.5rem 1rem;
  font-size: 0.85rem;
}

@media (max-width: 768px) {
  .settings-page {
    padding: 1rem;
  }

  .settings-section {
    padding: 1.5rem;
  }

  .settings-header h1 {
    font-size: 1.5rem;
  }

  .session-item,
  .history-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
}
</style>
