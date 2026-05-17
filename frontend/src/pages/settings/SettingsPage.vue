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

const formData = ref({
  fullName: 'Farah Kenawy',
  email: 'fakenawy@feu.edu.ph',
  phone: '0959 565 7777'
});

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
</script>

<style scoped>
.settings-page {
  padding: 0;
  background-color: transparent;
  margin-left: 240px;
  margin-top: 56px;
  min-height: calc(100vh - 56px);
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding-top: 2rem;
}

.settings-wrapper {
  width: 100%;
  max-width: 900px;
  padding: 0;
  background-color: transparent;
}

.settings-header {
  margin-bottom: 2rem;
}

.settings-header h1 {
  font-size: 1.75rem;
  font-weight: 700;
  color: #333;
  margin: 0 0 1.5rem 0;
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

.settings-card {
  background: transparent;
  border-radius: 0;
  box-shadow: none;
  padding: 0;
  margin-bottom: 2rem;
}

.card-header {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  padding-bottom: 0;
  border-bottom: none;
}

.card-icon {
  font-size: 1.5rem;
  min-width: 2rem;
}

.card-header h2 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #333;
  margin: 0;
}

.card-header p {
  font-size: 0.85rem;
  color: #666;
  margin: 0.25rem 0 0 0;
}

.card-content {
  margin-bottom: 2rem;
}

/* Account Settings Styles */
.profile-section {
  display: flex;
  gap: 2rem;
}

.profile-photo-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.profile-photo {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background-color: #ccc;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
}

.change-photo-btn {
  background: none;
  border: none;
  color: #1a6e3a;
  font-weight: 600;
  cursor: pointer;
  text-decoration: underline;
  font-size: 0.9rem;
}

.profile-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-item label {
  font-weight: 600;
  color: #333;
  font-size: 0.9rem;
}

.info-item p {
  color: #666;
  font-size: 0.95rem;
  margin: 0;
}

/* Security Styles */
.security-section {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.security-subsection {
  padding-bottom: 2rem;
  border-bottom: 1px solid #eee;
}

.security-subsection h3 {
  font-size: 1.1rem;
  font-weight: 700;
  color: #333;
  margin: 0 0 1.5rem 0;
}

.subsection-label {
  font-weight: 600;
  color: #333;
  font-size: 0.9rem;
  margin: 1rem 0 0.5rem 0;
}

.form-group {
  margin-bottom: 1rem;
}

.password-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.password-input {
  width: 100%;
  padding: 0.75rem 2.5rem 0.75rem 1rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 0.95rem;
}

.password-input:focus {
  outline: none;
  border-color: #1a6e3a;
  box-shadow: 0 0 0 3px rgba(26, 110, 58, 0.1);
}

.toggle-password-btn {
  position: absolute;
  right: 0.75rem;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
}

.password-requirements {
  margin-top: 1rem;
  padding: 1rem;
  background-color: #f9f9f9;
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

.two-fa-section {
  padding-top: 1rem;
}

.two-fa-card {
  display: flex;
  gap: 1.5rem;
  padding: 1.5rem;
  background-color: #f0f8f4;
  border-left: 4px solid #1a6e3a;
  border-radius: 6px;
}

.two-fa-icon {
  font-size: 2rem;
  min-width: 2rem;
}

.two-fa-content h3 {
  font-size: 1rem;
  font-weight: 700;
  color: #333;
  margin: 0 0 0.5rem 0;
}

.two-fa-content p {
  font-size: 0.85rem;
  color: #666;
  margin: 0.25rem 0;
}

.two-fa-status {
  font-weight: 600;
  color: #1a6e3a;
  margin: 0.75rem 0 !important;
}

/* Preferences Styles */
.preferences-subsection {
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid #eee;
}

.preferences-subsection:last-child {
  border-bottom: none;
}

.preferences-subsection h3 {
  font-size: 1.1rem;
  font-weight: 700;
  color: #333;
  margin: 0 0 0.5rem 0;
}

.subsection-description {
  font-size: 0.85rem;
  color: #666;
  margin: 0 0 1.5rem 0;
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
  font-size: 0.85rem;
  padding: 0.5rem 1rem;
}

.btn-secondary:hover {
  background-color: #ccc;
}

@media (max-width: 768px) {
  .settings-page {
    padding: 1rem;
    margin-left: 0;
  }

  .profile-section {
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
