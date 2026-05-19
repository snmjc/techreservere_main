<!-- ===== Preferences Settings Page ===== -->
<template>
  <div class="settings-page">
    <div class="settings-container">
      <!-- Header -->
      <div class="settings-header">
        <h1>Preferences</h1>
        <p>Customize your experience</p>
      </div>

      <!-- Settings Content -->
      <div class="settings-content">
        <!-- Notification Preferences -->
        <div class="settings-section">
          <h2>Notification Preferences</h2>

          <div class="preference-item">
            <div class="preference-info">
              <h3>Email Notifications</h3>
              <p>Receive notifications via email</p>
            </div>
            <label class="toggle-switch">
              <input v-model="preferences.emailNotifications" type="checkbox" />
              <span class="slider"></span>
            </label>
          </div>

          <div class="preference-item">
            <div class="preference-info">
              <h3>Reservation Updates</h3>
              <p>Get notified about reservation status changes</p>
            </div>
            <label class="toggle-switch">
              <input v-model="preferences.reservationUpdates" type="checkbox" />
              <span class="slider"></span>
            </label>
          </div>

          <div class="preference-item">
            <div class="preference-info">
              <h3>Approval Notifications</h3>
              <p>Get notified when your requests are approved/rejected</p>
            </div>
            <label class="toggle-switch">
              <input v-model="preferences.approvalNotifications" type="checkbox" />
              <span class="slider"></span>
            </label>
          </div>

          <div class="preference-item">
            <div class="preference-info">
              <h3>System Announcements</h3>
              <p>Receive important system announcements</p>
            </div>
            <label class="toggle-switch">
              <input v-model="preferences.systemAnnouncements" type="checkbox" />
              <span class="slider"></span>
            </label>
          </div>
        </div>

        <!-- Display Preferences -->
        <div class="settings-section">
          <h2>Display Preferences</h2>

          <div class="form-group">
            <label for="theme">Theme</label>
            <select id="theme" v-model="preferences.theme">
              <option value="light">Light</option>
              <option value="dark">Dark</option>
              <option value="auto">Auto (System)</option>
            </select>
          </div>

          <div class="form-group">
            <label for="language">Language</label>
            <select id="language" v-model="preferences.language">
              <option value="en">English</option>
              <option value="fil">Filipino</option>
              <option value="es">Spanish</option>
            </select>
          </div>

          <div class="form-group">
            <label for="timezone">Timezone</label>
            <select id="timezone" v-model="preferences.timezone">
              <option value="UTC+8">UTC+8 (Manila)</option>
              <option value="UTC+0">UTC+0 (London)</option>
              <option value="UTC-5">UTC-5 (New York)</option>
            </select>
          </div>
        </div>

        <!-- Privacy Preferences -->
        <div class="settings-section">
          <h2>Privacy Preferences</h2>

          <div class="preference-item">
            <div class="preference-info">
              <h3>Profile Visibility</h3>
              <p>Allow others to see your profile</p>
            </div>
            <label class="toggle-switch">
              <input v-model="preferences.profileVisibility" type="checkbox" />
              <span class="slider"></span>
            </label>
          </div>

          <div class="preference-item">
            <div class="preference-info">
              <h3>Activity Status</h3>
              <p>Show when you're online</p>
            </div>
            <label class="toggle-switch">
              <input v-model="preferences.activityStatus" type="checkbox" />
              <span class="slider"></span>
            </label>
          </div>

          <div class="preference-item">
            <div class="preference-info">
              <h3>Data Collection</h3>
              <p>Allow us to collect usage data to improve the service</p>
            </div>
            <label class="toggle-switch">
              <input v-model="preferences.dataCollection" type="checkbox" />
              <span class="slider"></span>
            </label>
          </div>
        </div>

        <!-- Save Button -->
        <div class="settings-section">
          <button @click="savePreferences" class="btn btn-primary">
            Save Preferences
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const preferences = ref({
  emailNotifications: true,
  reservationUpdates: true,
  approvalNotifications: true,
  systemAnnouncements: true,
  theme: 'light',
  language: 'en',
  timezone: 'UTC+8',
  profileVisibility: true,
  activityStatus: true,
  dataCollection: true
});

const savePreferences = () => {
  alert('Preferences saved successfully!');
};
</script>

<style scoped>
@import './css/PreferencesSettings.css';
</style>


