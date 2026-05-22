<!-- ===== Settings Page with Tabs ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="userRole"
    :navigation-items="navigationItems"
  >
    <div class="settings-page">
      <div class="settings-wrapper">
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

      <!-- Account Settings Tab -->
      <div v-if="activeTab === 'account'" class="settings-card">
        <div class="card-header">
          <span class="card-icon">✏️</span>
          <div>
            <h2>Profile</h2>
            <p>Manage your profile display</p>
          </div>
        </div>

        <div class="card-content">
          <div class="profile-section">
            <!-- Profile Photo -->
            <div class="profile-photo-container">
              <div class="profile-photo">👤</div>
              <button class="change-photo-btn">Change Photo</button>
            </div>

            <!-- Profile Info -->
            <div class="profile-info">
              <div class="info-item">
                <label>Full Name</label>
                <p>{{ formData.fullName }}</p>
              </div>

              <div class="info-item">
                <label>Email Address</label>
                <p>{{ formData.email }}</p>
              </div>

              <div class="info-item">
                <label>Phone Number</label>
                <p>{{ formData.phone }}</p>
              </div>
            </div>
          </div>
        </div>

        <button class="btn btn-primary">Save Changes</button>
      </div>

      <!-- Security Tab -->
      <div v-if="activeTab === 'security'" class="settings-card">
        <div class="card-header">
          <span class="card-icon">🔒</span>
          <div>
            <h2>Security</h2>
            <p>Manage your password and account security settings</p>
          </div>
        </div>

        <div class="card-content">
          <div class="security-section">
            <!-- Change Password -->
            <div class="security-subsection">
              <h3>Change Password</h3>
              <p class="subsection-label">Current Password</p>

              <div class="form-group">
                <div class="password-input-wrapper">
                  <input
                    type="password"
                    placeholder="Enter your current password"
                    class="password-input"
                  />
                  <button class="toggle-password-btn">👁️</button>
                </div>
              </div>

              <p class="subsection-label">New Password</p>

              <div class="form-group">
                <div class="password-input-wrapper">
                  <input
                    type="password"
                    placeholder="Enter your new password"
                    class="password-input"
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

              <p class="subsection-label">Confirm New Password</p>

              <div class="form-group">
                <div class="password-input-wrapper">
                  <input
                    type="password"
                    placeholder="Confirm your new password"
                    class="password-input"
                  />
                  <button class="toggle-password-btn">👁️</button>
                </div>
              </div>
            </div>

            <!-- Two-Factor Authentication -->
            <div class="two-fa-section">
              <div class="two-fa-card">
                <div class="two-fa-icon">🛡️</div>
                <div class="two-fa-content">
                  <h3>Two-Step Verification</h3>
                  <p>Add an extra layer of security to your account</p>
                  <p class="two-fa-status">Two-Step Verification is ON</p>
                  <button class="btn btn-secondary">Manage</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <button class="btn btn-primary">Update Password</button>
      </div>

      <!-- Preferences Tab -->
      <div v-if="activeTab === 'preferences'" class="settings-card">
        <div class="card-header">
          <span class="card-icon">⚙️</span>
          <div>
            <h2>Preferences</h2>
            <p>Customize your experience and notification settings</p>
          </div>
        </div>

        <div class="card-content">
          <!-- Notification Preferences -->
          <div class="preferences-subsection">
            <h3>Notification Preferences</h3>
            <p class="subsection-description">Choose what notifications you want to receive</p>

            <div class="preference-item">
              <div class="preference-info">
                <div class="preference-icon">📅</div>
                <div class="preference-text">
                  <h4>Reservation Updates</h4>
                  <p>Receive updates about your reservations</p>
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
                  <h4>Upcoming Reminders</h4>
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
                <input type="checkbox" />
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
          <div class="preferences-subsection">
            <h3>Display Preferences</h3>
            <p class="subsection-description">Customize the system to your preferences</p>

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
        </div>

        <button class="btn btn-primary">Save Preferences</button>
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

const currentAccount = computed(() => authStore.accountData || authStore.clerkAccountData || {});

const formData = computed(() => {
  const account = currentAccount.value;
  const firstName = String(account.firstName || '').trim();
  const lastName = String(account.lastName || '').trim();
  const fullName = [firstName, lastName].filter(Boolean).join(' ');

  return {
    fullName: fullName || 'Account User',
    email: account.emailAddress || account.email || 'No email address',
    phone: account.contactNumber || account.phone || 'No phone number',
  };
});

const userRole = computed(() => {
  return authStore.userRole === 'ROLE_ADMIN' ? 'ADMINISTRATOR' : 'BORROWER';
});

const navigationItems = computed(() => {
  if (authStore.userRole === 'ROLE_ADMIN') {
    return adminNavigationItems;
  }
  return borrowerNavigationItems;
});
</script>

<style scoped>
@import './css/SettingsPage.css';
</style>


