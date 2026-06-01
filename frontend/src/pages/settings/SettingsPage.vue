<template>
  <AdminSidebarLayoutComponent
    :role-label="userRole"
    :navigation-items="navigationItems"
  >
    <div class="settings-page">
      <div class="settings-wrapper">
        <div class="settings-header">
          <h1>Settings</h1>
          <div class="settings-tabs" role="tablist" aria-label="Settings sections">
            <button
              v-for="tab in settingsTabs"
              :key="tab.value"
              type="button"
              :class="['settings-tab', { active: activeTab === tab.value }]"
              @click="selectTab(tab.value)"
            >
              {{ tab.label }}
            </button>
          </div>
        </div>

        <p v-if="loadError" class="settings-alert error">{{ loadError }}</p>
        <p v-else-if="isLoadingProfile" class="settings-alert">Loading account settings...</p>

        <section v-if="activeTab === 'account'" class="settings-card">
          <div class="card-header">
            <div>
              <h2>Account Settings</h2>
              <p>Update your profile information saved in the User Accounts database.</p>
            </div>
          </div>

          <div class="card-content account-grid">
            <div class="profile-summary">
              <div class="profile-photo">
                <img v-if="accountProfile.profilePhotoData" :src="accountProfile.profilePhotoData" alt="Profile photo" />
                <span v-else>{{ accountInitials }}</span>
              </div>
              <div>
                <p class="profile-name">{{ fullName || 'Account User' }}</p>
                <p class="profile-meta">{{ accountProfile.emailAddress || 'No email address' }}</p>
                <p class="profile-meta">{{ accountProfile.roleLabel || accountProfile.accountType || 'No role' }}</p>
              </div>
            </div>

            <div class="readonly-grid">
              <div class="info-item">
                <label>ID Number</label>
                <p>{{ accountProfile.idNumber || 'Not set' }}</p>
              </div>
              <div class="info-item">
                <label>Email Address</label>
                <p>{{ accountProfile.emailAddress || 'Not set' }}</p>
              </div>
              <div class="info-item">
                <label>Role</label>
                <p>{{ accountProfile.roleLabel || accountProfile.roleDesignation || 'Not set' }}</p>
              </div>
              <div class="info-item">
                <label>Phone Number</label>
                <p>{{ accountProfile.contactNumber || 'Not set' }}</p>
              </div>
            </div>

            <form class="settings-form" @submit.prevent="saveAccountSettings">
              <div class="form-row">
                <label for="firstName">First Name</label>
                <input
                  id="firstName"
                  v-model.trim="accountForm.firstName"
                  type="text"
                  autocomplete="given-name"
                  :disabled="isSavingAccount"
                />
              </div>

              <div class="form-row">
                <label for="lastName">Last Name</label>
                <input
                  id="lastName"
                  v-model.trim="accountForm.lastName"
                  type="text"
                  autocomplete="family-name"
                  :disabled="isSavingAccount"
                />
              </div>

              <div class="form-row">
                <label for="phoneNumber">Phone Number (10 digits, starts with 9)</label>
                <input
                  id="phoneNumber"
                  v-model.trim="accountForm.contactNumber"
                  type="tel"
                  inputmode="numeric"
                  maxlength="10"
                  placeholder="9XXXXXXXXX"
                  :disabled="isSavingAccount"
                  @input="sanitizePhoneInput"
                />
              </div>

              <div class="form-row">
                <label for="profilePhoto">Profile Photo (.jpg only)</label>
                <input
                  id="profilePhoto"
                  ref="profilePhotoInput"
                  type="file"
                  accept=".jpg,image/jpeg"
                  :disabled="isSavingAccount"
                  @change="handlePhotoChange"
                />
                <p v-if="selectedPhotoName" class="field-hint">{{ selectedPhotoName }}</p>
              </div>

              <p v-if="accountError" class="settings-alert error">{{ accountError }}</p>
              <p v-if="accountSuccess" class="settings-alert success">{{ accountSuccess }}</p>

              <button class="btn btn-primary" type="submit" :disabled="isSavingAccount">
                {{ isSavingAccount ? 'Saving...' : 'Save Changes' }}
              </button>
            </form>

            <EmployeeWorkLogSheet
              :is-visible="isEmployeeAccount"
              :work-logs="employeeWorkLogs"
              :loading="workLogsLoading"
              :error="workLogsError"
              @refresh="loadEmployeeWorkLogs"
            />
          </div>
        </section>

        <section v-if="activeTab === 'security'" class="settings-card">
          <div class="card-header">
            <div>
              <h2>Security</h2>
              <p>Update the local password used for TechReserve sign-in.</p>
            </div>
          </div>

          <form class="settings-form" @submit.prevent="updatePassword">
            <div class="form-row">
              <label for="currentPassword">Current Password</label>
              <input
                id="currentPassword"
                v-model="passwordForm.currentPassword"
                type="password"
                autocomplete="current-password"
                :disabled="isUpdatingPassword"
              />
            </div>

            <div class="form-row">
              <label for="newPassword">New Password</label>
              <input
                id="newPassword"
                v-model="passwordForm.newPassword"
                type="password"
                autocomplete="new-password"
                :disabled="isUpdatingPassword"
              />
              <div class="password-requirements">
                <p :class="['requirement', { met: passwordRequirements.length }]">At least 8 characters</p>
                <p :class="['requirement', { met: passwordRequirements.upper }]">One uppercase letter</p>
                <p :class="['requirement', { met: passwordRequirements.lower }]">One lowercase letter</p>
                <p :class="['requirement', { met: passwordRequirements.number }]">One number</p>
                <p :class="['requirement', { met: passwordRequirements.special }]">One special character</p>
              </div>
            </div>

            <div class="form-row">
              <label for="confirmPassword">Confirm New Password</label>
              <input
                id="confirmPassword"
                v-model="passwordForm.confirmPassword"
                type="password"
                autocomplete="new-password"
                :disabled="isUpdatingPassword"
              />
            </div>

            <p v-if="passwordError" class="settings-alert error">{{ passwordError }}</p>
            <p v-if="passwordSuccess" class="settings-alert success">{{ passwordSuccess }}</p>

            <button class="btn btn-primary" type="submit" :disabled="isUpdatingPassword">
              {{ isUpdatingPassword ? 'Updating...' : 'Update Password' }}
            </button>
          </form>
        </section>

        <section v-if="activeTab === 'preferences'" class="settings-card">
          <div class="card-header">
            <div>
              <h2>Preferences</h2>
              <p>Notification preferences are retained for the current session.</p>
            </div>
          </div>

          <div class="preferences-subsection">
            <div class="preference-item" v-for="item in preferenceItems" :key="item.label">
              <div class="preference-text">
                <h4>{{ item.label }}</h4>
                <p>{{ item.description }}</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" v-model="item.enabled" />
                <span class="slider"></span>
              </label>
            </div>
          </div>
        </section>
      </div>
    </div>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import EmployeeWorkLogSheet from './components/EmployeeWorkLogSheet.vue';
import { useSettingsPage } from './composables/useSettingsPage.js';

const {
  activeTab,
  isLoadingProfile,
  isSavingAccount,
  isUpdatingPassword,
  loadError,
  accountError,
  accountSuccess,
  passwordError,
  passwordSuccess,
  selectedPhotoName,
  profilePhotoInput,
  accountProfile,
  accountForm,
  passwordForm,
  employeeWorkLogs,
  workLogsLoading,
  workLogsError,
  settingsTabs,
  preferenceItems,
  userRole,
  navigationItems,
  fullName,
  accountInitials,
  isEmployeeAccount,
  passwordRequirements,
  selectTab,
  loadEmployeeWorkLogs,
  saveAccountSettings,
  updatePassword,
  handlePhotoChange,
  sanitizePhoneInput,
} = useSettingsPage();
</script>

<style scoped>
@import './css/SettingsPage.css';
</style>
