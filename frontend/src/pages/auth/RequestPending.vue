<template>
  <div class="request-pending-page">
    <section class="request-pending-branding-panel">
      <img
        src="@/assets/Page-20-3.png"
        alt=""
        class="request-pending-branding-image"
      />
      <div class="request-pending-branding-content">
        <div class="request-pending-brand-mark">
          <img
            src="@/assets/TechReserve_LogoB.png"
            alt="TechReserve Logo"
            class="request-pending-logo"
          />
          <h1 class="request-pending-brand-title">
            <span>Tech</span><strong>Reserve</strong>
          </h1>
        </div>

        <div class="request-pending-brand-copy">
          <p class="request-pending-kicker">Account Review</p>
          <h2 class="request-pending-brand-subtitle">
            Your access request is waiting for approval.
          </h2>
          <p class="request-pending-brand-description">
            The Facilities Office will verify your account before TechReserve opens your workspace.
          </p>
        </div>
      </div>
    </section>

    <section class="request-pending-form-panel">
      <img
        src="@/assets/FEU_Tech_official_seal.png"
        alt="FEU Tech Seal Watermark"
        class="request-pending-watermark"
      />

      <div class="request-pending-form-content">
        <div class="request-pending-card">
          <div class="request-pending-status-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="9" />
              <path d="M12 7v5l3 2" />
            </svg>
          </div>

          <div class="request-pending-card-header">
            <p class="request-pending-card-kicker">Pending Approval</p>
            <h1>Account under review</h1>
            <p>
              Your account was created successfully. An administrator still needs to verify it before you can continue.
            </p>
          </div>

          <dl class="request-pending-info">
            <div>
              <dt>Email</dt>
              <dd>{{ userEmail || 'Not available' }}</dd>
            </div>
            <div>
              <dt>Name</dt>
              <dd>{{ userName || 'Not available' }}</dd>
            </div>
            <div>
              <dt>Status</dt>
              <dd><span>Pending Approval</span></dd>
            </div>
          </dl>

          <p v-if="statusMessage" class="request-pending-message" :class="{ 'request-pending-message--error': statusMessageType === 'error' }">
            {{ statusMessage }}
          </p>

          <div class="request-pending-actions">
            <button class="request-pending-secondary-button" type="button" @click="handleLogout">
              Sign out
            </button>
            <button class="request-pending-primary-button" type="button" :disabled="isChecking" @click="checkApprovalStatus">
              {{ isChecking ? 'Checking...' : 'Check status' }}
            </button>
          </div>

          <p class="request-pending-note">
            If your request stays pending for 24-48 hours, please contact the system administrator.
          </p>
        </div>
      </div>

      <footer class="request-pending-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </footer>
    </section>
  </div>
</template>

<script setup>
import { useRequestPendingPage } from './composables/useRequestPendingPage.js';

const {
  isChecking,
  statusMessage,
  statusMessageType,
  userEmail,
  userName,
  handleLogout,
  checkApprovalStatus,
} = useRequestPendingPage();
</script>

<style scoped>
@import './css/RequestPending.css';
</style>

