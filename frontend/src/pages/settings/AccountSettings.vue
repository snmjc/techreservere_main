<!-- ===== Account Settings Page ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="userRole"
    :navigation-items="navigationItems"
  >
    <div class="settings-page">
      <div class="settings-container">
      <!-- Header with Tabs -->
      <div class="settings-header">
        <h1>Settings</h1>
        <div class="settings-tabs">
          <button
            v-for="tab in settingsTabs"
            :key="tab.value"
            @click="activeTab = tab.value"
            :class="['settings-tab', { active: activeTab === tab.value }]"
          >
            {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- Settings Content -->
      <div class="settings-content" v-if="activeTab === 'account'">
        <!-- Profile Section -->
        <div class="settings-section account-section">
          <div class="profile-card">
            <!-- Profile Photo -->
            <div class="profile-photo-section">
              <div class="profile-photo">
                <span class="photo-placeholder">👤</span>
              </div>
              <button class="change-photo-btn">Change Photo</button>
            </div>

            <!-- Profile Information -->
            <div class="profile-info">
              <div class="form-group">
                <label for="fullName">Full Name</label>
                <input
                  id="fullName"
                  v-model="formData.fullName"
                  type="text"
                  placeholder="Enter your full name"
                />
              </div>

              <div class="form-group">
                <label for="email">Email Addresses</label>
                <input
                  id="email"
                  v-model="formData.email"
                  type="email"
                  placeholder="Enter your email"
                  disabled
                />
              </div>

              <div class="form-group">
                <label for="phone">Phone Number</label>
                <input
                  id="phone"
                  v-model="formData.phone"
                  type="tel"
                  placeholder="Enter your phone number"
                />
              </div>
            </div>
          </div>

          <button @click="saveChanges" class="btn btn-primary">
            Save Changes
          </button>
        </div>

      </div>

      <!-- Security Tab -->
      <div class="settings-content" v-if="activeTab === 'security'">
        <div class="settings-section security-section">
          <div class="section-header">
            <h2>🔒 Security</h2>
            <p>Manage your password and account security settings</p>
          </div>

          <!-- Change Password -->
          <div class="security-card">
            <h3>Change Password</h3>
            <div class="form-group">
              <label for="currentPassword">Current Password</label>
              <div class="password-input-wrapper">
                <input
                  id="currentPassword"
                  type="password"
                  placeholder="Enter your current password"
                />
                <button class="toggle-password-btn">👁️</button>
              </div>
            </div>

            <div class="form-group">
              <label for="newPassword">New Password</label>
              <div class="password-input-wrapper">
                <input
                  id="newPassword"
                  type="password"
                  placeholder="Enter your new password"
                />
                <button class="toggle-password-btn">👁️</button>
              </div>
              <div class="password-requirements">
                <p class="requirement">
                  <span class="check">✓</span> At least 8 characters
                </p>
                <p class="requirement">
                  <span class="check">✓</span> One uppercase letter
                </p>
                <p class="requirement">
                  <span class="check">✓</span> One number
                </p>
                <p class="requirement">
                  <span class="check">✓</span> One special character
                </p>
              </div>
            </div>

            <div class="form-group">
              <label for="confirmPassword">Confirm New Password</label>
              <div class="password-input-wrapper">
                <input
                  id="confirmPassword"
                  type="password"
                  placeholder="Confirm your new password"
                />
                <button class="toggle-password-btn">👁️</button>
              </div>
            </div>

            <button class="btn btn-primary">Update Password</button>
          </div>

          <!-- Two-Factor Authentication -->
          <div class="security-card two-fa-card">
            <div class="two-fa-content">
              <div class="two-fa-icon">🛡️</div>
              <div class="two-fa-info">
                <h3>Two-Step Verification</h3>
                <p>Two-Step Verification is ON</p>
                <p class="two-fa-description">You're using an authenticator app for your account</p>
                <button class="btn btn-secondary">Manage</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Preferences Tab -->
      <div class="settings-content" v-if="activeTab === 'preferences'">
        <div class="settings-section preferences-section">
          <div class="section-header">
            <h2>⚙️ Preferences</h2>
            <p>Customize your experience and notification settings</p>
          </div>

          <!-- Notification Preferences -->
          <div class="preferences-card">
            <h3>Notification Preferences</h3>
            <p class="card-description">Choose what notifications you want to receive</p>

            <div class="preference-item">
              <div class="preference-info">
                <div class="preference-icon">📅</div>
                <div class="preference-text">
                  <h4>Reservation Updates</h4>
                  <p>Get notified about reservation status</p>
                </div>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" checked />
                <span class="slider"></span>
              </label>
            </div>

            <div class="preference-item">
              <div class="preference-info">
                <div class="preference-icon">⏰</div>
                <div class="preference-text">
                  <h4>Upcoming Reservations</h4>
                  <p>Reminder for upcoming reservations</p>
                </div>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" checked />
                <span class="slider"></span>
              </label>
            </div>

            <div class="preference-item">
              <div class="preference-info">
                <div class="preference-icon">🏢</div>
                <div class="preference-text">
                  <h4>Facilities Announcements</h4>
                  <p>Important facility announcements</p>
                </div>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" checked />
                <span class="slider"></span>
              </label>
            </div>

            <div class="preference-item">
              <div class="preference-info">
                <div class="preference-icon">🔔</div>
                <div class="preference-text">
                  <h4>System Notifications</h4>
                  <p>System updates and maintenance notices</p>
                </div>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" checked />
                <span class="slider"></span>
              </label>
            </div>
          </div>

          <!-- Display Preferences -->
          <div class="preferences-card">
            <h3>Display Preferences</h3>
            <p class="card-description">Customize the system to your preferences</p>

            <div class="preference-item">
              <div class="preference-info">
                <div class="preference-icon">🌐</div>
                <div class="preference-text">
                  <h4>Language</h4>
                  <p>Choose your preferred language</p>
                </div>
              </div>
              <select class="preference-select">
                <option>English</option>
                <option>Filipino</option>
                <option>Spanish</option>
              </select>
            </div>

            <div class="preference-item">
              <div class="preference-info">
                <div class="preference-icon">⏱️</div>
                <div class="preference-text">
                  <h4>Time Format</h4>
                  <p>Choose your preferred time format</p>
                </div>
              </div>
              <select class="preference-select">
                <option>12-Hour (AM/PM)</option>
                <option>24-Hour</option>
              </select>
            </div>
          </div>

          <button class="btn btn-primary">Save Preferences</button>
        </div>
      </div>
      </div>
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import { borrowerNavigationItems } from '@/shared/constants/borrowerNavigationItems.js';

const authStore = useAuthenticationStore();

const activeTab = ref('account');

const settingsTabs = [
  { label: 'Account Settings', value: 'account' },
  { label: 'Security', value: 'security' },
  { label: 'Preferences', value: 'preferences' }
];

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
  return borrowerNavigationItems;
});

const formData = ref({
  fullName: '',
  email: '',
  phone: '',
  department: '',
  organization: ''
});

// Initialize form data from authentication store
const initializeFormData = () => {
  const account = authStore.accountData;
  if (account) {
    formData.value.fullName = `${account.firstName || ''} ${account.lastName || ''}`;
    formData.value.email = account.emailAddress || '';
    formData.value.phone = account.contactNumber || '';
  }
};

// Initialize on component mount
initializeFormData();

const accountCreatedDate = ref(new Date('2024-01-15'));
const lastLoginDate = ref(new Date());

const saveChanges = () => {
  // Parse full name back to first and last name
  const nameParts = formData.value.fullName.split(' ');
  const firstName = nameParts[0] || '';
  const lastName = nameParts.slice(1).join(' ') || '';

  // Update the account data in the store
  if (authStore.accountData) {
    authStore.accountData.firstName = firstName;
    authStore.accountData.lastName = lastName;
    authStore.accountData.contactNumber = formData.value.phone;

    // Update localStorage
    localStorage.setItem('techreserve_auth_account', JSON.stringify(authStore.accountData));
  }

  alert('Account information saved successfully!');
};

const handleDeleteAccount = () => {
  if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
    alert('Account deletion initiated. You will receive a confirmation email.');
  }
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
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
  margin-left: 240px;
  margin-top: 56px;
}

.settings-container {
  max-width: 900px;
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

.form-group input:disabled {
  background-color: #f5f5f5;
  cursor: not-allowed;
}

.form-group small {
  display: block;
  font-size: 0.85rem;
  color: #999;
  margin-top: 0.25rem;
}

.status-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 0;
  border-bottom: 1px solid #eee;
}

.status-item:last-child {
  border-bottom: none;
}

.status-label {
  font-weight: 600;
  color: #333;
}

.status-value {
  color: #666;
  font-size: 0.95rem;
}

.status-value.active {
  color: #4caf50;
  font-weight: 600;
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

.btn-danger {
  background-color: #f44336;
  color: white;
}

.btn-danger:hover {
  background-color: #da190b;
}

.danger-zone {
  border-left: 4px solid #f44336;
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
}

.settings-tabs {
  display: flex;
  gap: 2rem;
  border-bottom: 2px solid #ddd;
}

.settings-tab {
  padding: 0.75rem 0;
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  font-weight: 600;
  color: #666;
  font-size: 0.95rem;
  transition: all 0.3s ease;
}

.settings-tab:hover {
  color: #333;
}

.settings-tab.active {
  color: #1a6e3a;
  border-bottom-color: #1a6e3a;
}

.settings-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
  margin-top: 2rem;
}

.section-header {
  margin-bottom: 2rem;
}

.section-header h2 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #333;
  margin: 0 0 0.5rem 0;
}

.section-header p {
  font-size: 0.9rem;
  color: #666;
  margin: 0;
}

.account-section {
  padding: 0;
}

.profile-card {
  display: flex;
  gap: 2rem;
  padding: 2rem;
  background: white;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.profile-photo-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.profile-photo {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background-color: #e0e0e0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3rem;
}

.change-photo-btn {
  padding: 0.5rem 1rem;
  background: none;
  border: none;
  color: #1a6e3a;
  font-weight: 600;
  cursor: pointer;
  text-decoration: underline;
}

.profile-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.security-section {
  padding: 2rem;
}

.security-card {
  background-color: #f9f9f9;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.security-card h3 {
  font-size: 1.1rem;
  font-weight: 700;
  color: #333;
  margin: 0 0 1rem 0;
}

.password-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.password-input-wrapper input {
  width: 100%;
  padding: 0.75rem 2.5rem 0.75rem 1rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 0.95rem;
}

.toggle-password-btn {
  position: absolute;
  right: 0.75rem;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.1rem;
}

.password-requirements {
  margin-top: 1rem;
  padding: 1rem;
  background-color: white;
  border-radius: 6px;
}

.requirement {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0.5rem 0;
  font-size: 0.85rem;
  color: #666;
}

.check {
  color: #1a6e3a;
  font-weight: 700;
}

.two-fa-card {
  background-color: #f0f8f4;
  border-left: 4px solid #1a6e3a;
}

.two-fa-content {
  display: flex;
  gap: 1.5rem;
  align-items: flex-start;
}

.two-fa-icon {
  font-size: 2.5rem;
}

.two-fa-info h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.1rem;
  color: #333;
}

.two-fa-info p {
  margin: 0.25rem 0;
  font-size: 0.9rem;
  color: #666;
}

.two-fa-description {
  font-size: 0.85rem;
  color: #999;
  margin-bottom: 1rem !important;
}

.preferences-section {
  padding: 2rem;
}

.preferences-card {
  background-color: #f9f9f9;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.preferences-card h3 {
  font-size: 1.1rem;
  font-weight: 700;
  color: #333;
  margin: 0 0 0.5rem 0;
}

.card-description {
  font-size: 0.85rem;
  color: #999;
  margin: 0 0 1rem 0;
}

.preference-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 0;
  border-bottom: 1px solid #eee;
}

.preference-item:last-child {
  border-bottom: none;
}

.preference-info {
  display: flex;
  gap: 1rem;
  flex: 1;
}

.preference-icon {
  font-size: 1.5rem;
  min-width: 2rem;
}

.preference-text h4 {
  font-size: 0.95rem;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.preference-text p {
  font-size: 0.85rem;
  color: #666;
  margin: 0.25rem 0 0 0;
}

.toggle-switch {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 24px;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  transition: 0.4s;
  border-radius: 24px;
}

.slider:before {
  position: absolute;
  content: '';
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.4s;
  border-radius: 50%;
}

input:checked + .slider {
  background-color: #1a6e3a;
}

input:checked + .slider:before {
  transform: translateX(26px);
}

.preference-select {
  padding: 0.5rem 1rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 0.9rem;
  background-color: white;
  cursor: pointer;
}

.preference-select:focus {
  outline: none;
  border-color: #1a6e3a;
  box-shadow: 0 0 0 3px rgba(26, 110, 58, 0.1);
}

.btn-secondary {
  background-color: #ddd;
  color: #333;
}

.btn-secondary:hover {
  background-color: #ccc;
}

@media (max-width: 768px) {
  .settings-page {
    padding: 1rem;
  }

  .settings-section {
    padding: 1.5rem;
  }

  .profile-card {
    flex-direction: column;
    align-items: center;
  }

  .settings-tabs {
    flex-wrap: wrap;
    gap: 1rem;
  }

  .preference-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
}
</style>
