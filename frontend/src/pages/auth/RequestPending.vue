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
.request-pending-page {
  display: flex;
  min-height: 100vh;
  width: 100%;
  overflow: hidden;
  background: #f4f6f3;
  font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
}

.request-pending-branding-panel {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 54%;
  min-height: 100vh;
  background: #064b33;
  overflow: hidden;
}

.request-pending-branding-image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  opacity: 0.24;
  z-index: 0;
}

.request-pending-branding-panel::before {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 1;
  background:
    linear-gradient(135deg, rgba(3, 70, 47, 0.95), rgba(8, 120, 74, 0.82)),
    linear-gradient(0deg, rgba(0, 0, 0, 0.28), transparent 55%);
}

.request-pending-branding-panel::after {
  content: '';
  position: absolute;
  right: 0;
  top: 0;
  width: 18%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.18));
  z-index: 2;
}

.request-pending-branding-content {
  position: relative;
  z-index: 3;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  width: min(78%, 560px);
  min-height: 72vh;
  color: #ffffff;
}

.request-pending-brand-mark {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.request-pending-logo {
  width: 82px;
  height: 82px;
  object-fit: contain;
  filter: drop-shadow(0 10px 18px rgba(0, 0, 0, 0.28));
}

.request-pending-brand-title {
  margin: 0;
  color: #ffffff;
  font-size: clamp(2.15rem, 4vw, 3.85rem);
  font-weight: 900;
  line-height: 1;
}

.request-pending-brand-title strong {
  color: #f5c542;
}

.request-pending-brand-copy {
  max-width: 520px;
}

.request-pending-kicker {
  margin: 0 0 0.75rem;
  color: #c9f7de;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.request-pending-brand-subtitle {
  max-width: 520px;
  margin: 0;
  color: #ffffff;
  font-size: clamp(2rem, 4.1vw, 4.35rem);
  font-weight: 900;
  line-height: 0.98;
}

.request-pending-brand-description {
  max-width: 440px;
  margin: 1.4rem 0 0;
  color: rgba(255, 255, 255, 0.82);
  font-size: 0.98rem;
  line-height: 1.65;
}

.request-pending-form-panel {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 46%;
  min-height: 100vh;
  overflow: hidden;
  background: #f4f6f3;
}

.request-pending-watermark {
  position: absolute;
  right: -18%;
  top: 4%;
  width: min(620px, 78vw);
  opacity: 0.045;
  pointer-events: none;
}

.request-pending-form-content {
  position: relative;
  z-index: 1;
  width: min(430px, calc(100% - 2rem));
}

.request-pending-card {
  padding: 2rem;
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 14px;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
}

.request-pending-status-icon {
  display: grid;
  place-items: center;
  width: 54px;
  height: 54px;
  margin-bottom: 1rem;
  color: #08784a;
  background: #e5f6ee;
  border-radius: 16px;
}

.request-pending-status-icon svg {
  width: 28px;
  height: 28px;
}

.request-pending-card-header {
  margin-bottom: 1.25rem;
}

.request-pending-card-kicker {
  margin: 0 0 0.4rem;
  color: #08784a;
  font-size: 0.72rem;
  font-weight: 900;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.request-pending-card-header h1 {
  margin: 0;
  color: #111827;
  font-size: 1.85rem;
  font-weight: 950;
  line-height: 1.05;
}

.request-pending-card-header p {
  margin: 0.8rem 0 0;
  color: #667085;
  font-size: 0.92rem;
  line-height: 1.6;
}

.request-pending-info {
  display: grid;
  gap: 0.65rem;
  margin: 0;
  padding: 0;
}

.request-pending-info div {
  display: grid;
  gap: 0.22rem;
  padding: 0.72rem 0.82rem;
  background: #f8fbfa;
  border: 1px solid #e1e9e5;
  border-radius: 9px;
}

.request-pending-info dt {
  color: #667085;
  font-size: 0.72rem;
  font-weight: 850;
  text-transform: uppercase;
}

.request-pending-info dd {
  margin: 0;
  color: #111827;
  overflow-wrap: anywhere;
  font-size: 0.9rem;
  font-weight: 800;
}

.request-pending-info dd span {
  display: inline-flex;
  width: fit-content;
  padding: 0.18rem 0.6rem;
  color: #a16207;
  background: #fef3c7;
  border-radius: 999px;
  font-size: 0.76rem;
  font-weight: 900;
}

.request-pending-message {
  margin: 1rem 0 0;
  padding: 0.72rem 0.85rem;
  color: #065f46;
  background: #ecfdf5;
  border: 1px solid #bbf7d0;
  border-radius: 9px;
  font-size: 0.84rem;
  font-weight: 800;
}

.request-pending-message--error {
  color: #991b1b;
  background: #fee2e2;
  border-color: #fecaca;
}

.request-pending-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.8rem;
  margin-top: 1.35rem;
}

.request-pending-primary-button,
.request-pending-secondary-button {
  min-height: 42px;
  border-radius: 9px;
  font-size: 0.9rem;
  font-weight: 900;
  cursor: pointer;
}

.request-pending-primary-button {
  color: #ffffff;
  background: #08784a;
  border: 1px solid #08784a;
}

.request-pending-secondary-button {
  color: #374151;
  background: #f3f4f6;
  border: 1px solid #d1d5db;
}

.request-pending-primary-button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.request-pending-note {
  margin: 1rem 0 0;
  color: #667085;
  font-size: 0.78rem;
  line-height: 1.5;
}

.request-pending-page-footer {
  position: absolute;
  bottom: 1.5rem;
  z-index: 1;
  color: #667085;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.08em;
}

@media (max-width: 980px) {
  .request-pending-page {
    flex-direction: column;
    min-height: auto;
    overflow: auto;
  }

  .request-pending-branding-panel,
  .request-pending-form-panel {
    width: 100%;
    min-height: auto;
  }

  .request-pending-branding-panel {
    padding: 2rem 1.25rem;
  }

  .request-pending-branding-content {
    width: min(100%, 620px);
    min-height: 360px;
  }

  .request-pending-form-panel {
    padding: 2rem 0 4.5rem;
  }
}

@media (max-width: 560px) {
  .request-pending-brand-mark {
    gap: 0.7rem;
  }

  .request-pending-logo {
    width: 58px;
    height: 58px;
  }

  .request-pending-card {
    padding: 1.3rem;
  }

  .request-pending-actions {
    grid-template-columns: 1fr;
  }
}
</style>
