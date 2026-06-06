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
import {
  AUTH_STORAGE_KEYS,
  isPersistentAuthStorage,
  writeStoredJson,
} from '@/modules/authentication/utils/authStorage.js';

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
    writeStoredJson(AUTH_STORAGE_KEYS.account, authStore.accountData, isPersistentAuthStorage());
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
@import './css/AccountSettings.css';
</style>


