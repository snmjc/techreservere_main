<template>
  <div class="custom-signup-page">
    <section class="custom-signup-branding-panel">
      <img
        src="@/assets/Page-20-3.png"
        alt=""
        class="custom-signup-branding-image"
      />
      <div class="custom-signup-branding-content">
        <div class="custom-signup-brand-mark">
          <img
            src="@/assets/TechReserve_LogoB.png"
            alt="TechReserve Logo"
            class="custom-signup-logo"
          />
          <h1 class="custom-signup-brand-title">
            <span class="custom-signup-brand-tech">Tech</span><span class="custom-signup-brand-reserve">Reserve</span>
          </h1>
        </div>

        <div class="custom-signup-brand-copy">
          <p class="custom-signup-kicker">Account Request</p>
          <h2 class="custom-signup-brand-subtitle">
            Request access to the reservation workspace.
          </h2>
          <p class="custom-signup-brand-description">
            Submit your institutional details so the Facilities Office can verify your TechReserve account.
          </p>
        </div>
      </div>
    </section>

    <section class="custom-signup-form-panel">
      <img
        src="@/assets/FEU_Tech_official_seal.png"
        alt="FEU Tech Seal Watermark"
        class="custom-signup-watermark"
      />

      <div class="custom-signup-form-content">
        <div class="custom-signup-card">
          <div class="custom-signup-card-header">
            <p class="custom-signup-card-kicker">{{ isInvitationMode ? 'Invitation Access' : 'Create Account' }}</p>
            <h1 class="custom-signup-heading">{{ awaitingVerification ? 'Verify your email' : (isInvitationMode ? 'Accept invitation' : 'Sign up') }}</h1>
            <p class="custom-signup-card-copy">
              {{ awaitingVerification ? 'Enter the code Clerk sent to complete your request.' : invitationCopy }}
            </p>
          </div>

          <form v-if="!awaitingVerification" class="custom-signup-form" @submit.prevent="handleSignUp">
            <div class="custom-signup-field-grid">
              <div class="custom-signup-row">
                <label for="firstName">First Name</label>
                <input
                  id="firstName"
                  v-model="formData.firstName"
                  type="text"
                  required
                  autocomplete="given-name"
                />
              </div>

              <div class="custom-signup-row">
                <label for="lastName">Last Name</label>
                <input
                  id="lastName"
                  v-model="formData.lastName"
                  type="text"
                  required
                  autocomplete="family-name"
                />
              </div>

              <div class="custom-signup-row">
                <label for="idNumber">ID Number</label>
                <input
                  id="idNumber"
                  v-model="formData.idNumber"
                  type="text"
                  :required="!isInvitationMode"
                />
              </div>

              <div class="custom-signup-row">
                <label for="department">Department</label>
                <select
                  id="department"
                  v-model="formData.department"
                  :required="!isInvitationMode"
                  class="custom-signup-select"
                >
                  <option value="">Select Department</option>
                  <option
                    v-for="department in departmentOptions"
                    :key="department"
                    :value="department"
                  >
                    {{ department }}
                  </option>
                </select>
              </div>

              <div class="custom-signup-row custom-signup-row-wide">
                <label for="fitEmailAddress">FIT Email Address</label>
                <input
                  id="fitEmailAddress"
                  v-model="formData.fitEmailAddress"
                  type="email"
                  :required="!isInvitationMode"
                  autocomplete="email"
                  :readonly="isInvitationMode"
                />
              </div>

              <div class="custom-signup-row">
                <label for="password">Password</label>
                <div class="custom-signup-password-control">
                  <input
                    id="password"
                    v-model="formData.password"
                    :type="showPassword ? 'text' : 'password'"
                    required
                    autocomplete="new-password"
                  />
                  <button
                    type="button"
                    class="custom-signup-password-toggle"
                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    :title="showPassword ? 'Hide password' : 'Show password'"
                    @click="showPassword = !showPassword"
                  >
                    <svg v-if="!showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                      <circle cx="12" cy="12" r="3" />
                    </svg>
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M2 12s3.5-7 10-7c2.1 0 3.9.72 5.35 1.71" />
                      <path d="M22 12s-3.5 7-10 7c-2.1 0-3.9-.72-5.35-1.71" />
                      <path d="M3 3l18 18" />
                      <path d="M9.88 9.88a3 3 0 0 0 4.24 4.24" />
                    </svg>
                  </button>
                </div>
              </div>

              <div class="custom-signup-row">
                <label for="confirmPassword">Confirm Password</label>
                <div class="custom-signup-password-control">
                  <input
                    id="confirmPassword"
                    v-model="formData.confirmPassword"
                    :type="showConfirmPassword ? 'text' : 'password'"
                    required
                    autocomplete="new-password"
                  />
                  <button
                    type="button"
                    class="custom-signup-password-toggle"
                    :aria-label="showConfirmPassword ? 'Hide confirm password' : 'Show confirm password'"
                    :title="showConfirmPassword ? 'Hide confirm password' : 'Show confirm password'"
                    @click="showConfirmPassword = !showConfirmPassword"
                  >
                    <svg v-if="!showConfirmPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                      <circle cx="12" cy="12" r="3" />
                    </svg>
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M2 12s3.5-7 10-7c2.1 0 3.9.72 5.35 1.71" />
                      <path d="M22 12s-3.5 7-10 7c-2.1 0-3.9-.72-5.35-1.71" />
                      <path d="M3 3l18 18" />
                      <path d="M9.88 9.88a3 3 0 0 0 4.24 4.24" />
                    </svg>
                  </button>
                </div>
              </div>

              <div v-if="!isInvitationMode" class="custom-signup-row custom-signup-row-wide">
                <label for="role">Role</label>
                <select id="role" v-model="formData.role" class="custom-signup-select" :disabled="isInvitationMode">
                  <option value="Student">Student</option>
                  <option value="Faculty">Faculty</option>
                </select>
              </div>

              <div v-if="!isInvitationMode" class="custom-signup-role-boundary custom-signup-row-wide">
                <div class="custom-signup-role-boundary-header">
                  <span class="custom-signup-role-boundary-label">User Verification</span>
                  <strong>{{ formData.role }}</strong>
                </div>

                <div v-if="isStudentRole" class="custom-signup-file-section">
                  <label for="studentSupportingFile">Supporting File</label>
                  <div class="custom-signup-file-control">
                    <input
                      id="studentSupportingFile"
                      ref="studentSupportingFileInput"
                      type="file"
                      :accept="supportingFileAccept"
                      class="custom-signup-file-input"
                      @change="handleStudentSupportingFileChange"
                    />
                    <button type="button" class="custom-signup-file-button" @click="openStudentSupportingFile">
                      Choose File
                    </button>
                    <span v-if="formData.supportingFile" class="custom-signup-file-name">
                      {{ formData.supportingFile.name }}
                    </span>
                    <span v-else class="custom-signup-file-placeholder">No file chosen</span>
                    <button
                      v-if="formData.supportingFile"
                      type="button"
                      class="custom-signup-file-remove"
                      aria-label="Remove selected file"
                      @click="removeStudentSupportingFile"
                    >
                      &times;
                    </button>
                  </div>
                  <p class="custom-signup-role-boundary-note">
                    Required for student requests: upload a PDF, DOC, DOCX, JPG, or PNG file. Maximum file size: 5 MB.
                  </p>
                </div>

                <div v-else class="custom-signup-faculty-note">
                  <strong>No file upload needed for faculty.</strong>
                  <span>Recommended: use your official FIT faculty email and complete your department details for admin verification.</span>
                </div>
              </div>
            </div>

            <label class="custom-signup-confirmation">
              <input
                v-model="formData.acceptTerms"
                type="checkbox"
                required
              />
              <span>
                I confirm that the information provided is accurate and consent to its use for TechReserve account verification.
              </span>
            </label>

            <div v-if="firstErrorMessage" class="custom-signup-error-box">
              {{ firstErrorMessage }}
            </div>

            <div v-if="successMessage" class="custom-signup-success-box">
              {{ successMessage }}
            </div>

            <button
              type="submit"
              class="custom-signup-submit"
              :disabled="isLoading"
            >
              {{ isLoading ? (isInvitationMode ? 'Accepting invitation...' : 'Creating account...') : (isInvitationMode ? 'Accept invitation' : 'Create account') }}
            </button>
          </form>

          <form v-else class="custom-signup-form custom-signup-verification-form" @submit.prevent="handleVerifyEmail">
            <p class="custom-signup-verification-copy">
              Clerk sent a verification code to <strong>{{ formData.fitEmailAddress }}</strong>.
            </p>

            <div class="custom-signup-row custom-signup-row-wide">
              <label for="verificationCode">Verification Code</label>
              <input
                id="verificationCode"
                v-model.trim="verificationCode"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                required
              />
            </div>

            <div v-if="firstErrorMessage" class="custom-signup-error-box">
              {{ firstErrorMessage }}
            </div>

            <div v-if="successMessage" class="custom-signup-success-box">
              {{ successMessage }}
            </div>

            <button
              type="submit"
              class="custom-signup-submit"
              :disabled="isLoading"
            >
              {{ isLoading ? 'Verifying...' : 'Verify email' }}
            </button>

            <button type="button" class="custom-signup-secondary-action" @click="awaitingVerification = false">
              Edit registration details
            </button>
          </form>

          <router-link class="custom-signup-back-link" to="/clerk-login">
            Already have an account? Sign in
          </router-link>
        </div>
      </div>

      <footer class="custom-signup-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </footer>
    </section>
  </div>
</template>

<script setup>
import { useCustomSignUpPage } from './composables/useCustomSignUpPage.js';

  const {
    formData,
  departmentOptions,
  isLoading,
  successMessage,
  awaitingVerification,
  verificationCode,
  showPassword,
  showConfirmPassword,
    studentSupportingFileInput,
    firstErrorMessage,
    isStudentRole,
    isInvitationMode,
    invitationCopy,
    supportingFileAccept,
    openStudentSupportingFile,
  handleStudentSupportingFileChange,
  removeStudentSupportingFile,
  handleSignUp,
  handleVerifyEmail,
} = useCustomSignUpPage();
</script>

<style scoped>
.custom-signup-page {
  display: flex;
  min-height: 100vh;
  width: 100%;
  overflow: hidden;
  background: #f4f6f3;
  font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
}

.custom-signup-branding-panel {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 46%;
  min-height: 100vh;
  background: #064b33;
  overflow: hidden;
}

.custom-signup-branding-image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  opacity: 0.22;
  z-index: 0;
}

.custom-signup-branding-panel::before {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 1;
  background:
    linear-gradient(135deg, rgba(3, 70, 47, 0.96), rgba(8, 120, 74, 0.82)),
    linear-gradient(0deg, rgba(0, 0, 0, 0.28), transparent 55%);
}

.custom-signup-branding-panel::after {
  content: '';
  position: absolute;
  right: 0;
  top: 0;
  width: 18%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.18));
  z-index: 2;
}

.custom-signup-branding-content {
  position: relative;
  z-index: 3;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  width: min(78%, 520px);
  min-height: 72vh;
  color: #ffffff;
}

.custom-signup-brand-mark {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.custom-signup-logo {
  width: 82px;
  height: 82px;
  object-fit: contain;
  filter: drop-shadow(0 10px 18px rgba(0, 0, 0, 0.28));
}

.custom-signup-brand-title {
  margin: 0;
  font-size: clamp(2.15rem, 4vw, 3.65rem);
  font-weight: 900;
  line-height: 1;
}

.custom-signup-brand-tech {
  color: #ffffff;
}

.custom-signup-brand-reserve {
  color: #ffc21a;
}

.custom-signup-brand-copy {
  max-width: 500px;
}

.custom-signup-kicker {
  margin: 0 0 0.75rem;
  color: #c9f7de;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.custom-signup-brand-subtitle {
  max-width: 500px;
  margin: 0;
  color: #ffffff;
  font-size: clamp(2rem, 4vw, 4rem);
  font-weight: 900;
  line-height: 0.98;
}

.custom-signup-brand-description {
  max-width: 430px;
  margin: 1.4rem 0 0;
  color: rgba(255, 255, 255, 0.82);
  font-size: 0.98rem;
  line-height: 1.65;
}

.custom-signup-form-panel {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 54%;
  min-height: 100vh;
  padding: 2rem 2rem 5rem;
  background:
    radial-gradient(circle at top right, rgba(8, 120, 74, 0.09), transparent 34%),
    #f5f6f4;
  overflow: hidden;
}

.custom-signup-watermark {
  position: absolute;
  top: -7%;
  right: -28%;
  width: min(64vw, 660px);
  max-width: none;
  opacity: 0.07;
  pointer-events: none;
  user-select: none;
  z-index: 0;
}

.custom-signup-form-content {
  position: relative;
  z-index: 1;
  width: min(100%, 700px);
}

.custom-signup-card {
  width: 100%;
  max-height: calc(100vh - 7rem);
  overflow: auto;
  padding: clamp(1.35rem, 2.5vw, 2rem);
  border: 1px solid rgba(17, 24, 39, 0.08);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.9);
  box-shadow: 0 24px 70px rgba(17, 24, 39, 0.12);
  backdrop-filter: blur(18px);
}

.custom-signup-card-header {
  margin-bottom: 1.2rem;
}

.custom-signup-card-kicker {
  margin: 0 0 0.5rem;
  color: #08784a;
  font-size: 0.74rem;
  font-weight: 900;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.custom-signup-heading {
  margin: 0;
  color: #111827;
  font-size: clamp(1.85rem, 3vw, 2.45rem);
  font-weight: 900;
  line-height: 1.05;
}

.custom-signup-card-copy {
  margin: 0.55rem 0 0;
  color: #6b7280;
  font-size: 0.94rem;
  line-height: 1.5;
}

.custom-signup-form {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.custom-signup-field-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.85rem;
}

.custom-signup-row {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.custom-signup-row-wide {
  grid-column: 1 / -1;
}

.custom-signup-row label {
  color: #374151;
  font-size: 0.78rem;
  font-weight: 800;
}

.custom-signup-row input,
.custom-signup-select {
  width: 100%;
  min-height: 42px;
  border: 1px solid #d6ddd8;
  border-radius: 10px;
  outline: none;
  background: #ffffff;
  color: #111827;
  font-size: 0.9rem;
  font-weight: 700;
  transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

.custom-signup-row input {
  padding: 0 0.85rem;
}

.custom-signup-password-control {
  position: relative;
  display: flex;
  align-items: center;
}

.custom-signup-password-control input {
  padding-right: 2.75rem;
}

.custom-signup-password-toggle {
  position: absolute;
  right: 0.55rem;
  display: grid;
  place-items: center;
  width: 32px;
  height: 32px;
  padding: 0;
  color: #6b7280;
  background: transparent;
  border: 0;
  border-radius: 8px;
  cursor: pointer;
  transition: color 0.18s ease, background-color 0.18s ease;
}

.custom-signup-password-toggle:hover,
.custom-signup-password-toggle:focus-visible {
  color: #08784a;
  background: rgba(8, 120, 74, 0.08);
  outline: none;
}

.custom-signup-password-toggle svg {
  width: 18px;
  height: 18px;
}

.custom-signup-row input:focus,
.custom-signup-select:focus {
  border-color: #08784a;
  box-shadow: 0 0 0 3px rgba(8, 120, 74, 0.12);
}

.custom-signup-select {
  padding: 0 0.85rem;
  cursor: pointer;
}

.custom-signup-role-boundary {
  padding: 0.95rem;
  border: 1px solid #d6e7dd;
  border-radius: 12px;
  background: #f8fcfa;
}

.custom-signup-role-boundary-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
  color: #0f3d2a;
}

.custom-signup-role-boundary-header strong {
  padding: 0.25rem 0.6rem;
  border-radius: 999px;
  background: #dff5e9;
  color: #08784a;
  font-size: 0.72rem;
  font-weight: 900;
}

.custom-signup-role-boundary-label {
  font-size: 0.78rem;
  font-weight: 900;
  text-transform: uppercase;
}

.custom-signup-file-section {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.custom-signup-file-section > label {
  color: #374151;
  font-size: 0.78rem;
  font-weight: 800;
}

.custom-signup-file-control {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  min-width: 0;
  flex-wrap: wrap;
}

.custom-signup-file-input {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  pointer-events: none;
}

.custom-signup-file-button {
  min-height: 34px;
  padding: 0 0.85rem;
  border: 0;
  border-radius: 999px;
  background: #08784a;
  color: #ffffff;
  cursor: pointer;
  font-size: 0.8rem;
  font-weight: 900;
}

.custom-signup-file-button:hover {
  background: #05613d;
}

.custom-signup-file-name,
.custom-signup-file-placeholder {
  min-width: 0;
  color: #4b5563;
  font-size: 0.78rem;
  font-weight: 700;
}

.custom-signup-file-name {
  max-width: 260px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.custom-signup-file-placeholder {
  color: #9ca3af;
}

.custom-signup-file-remove {
  display: grid;
  place-items: center;
  width: 22px;
  height: 22px;
  padding: 0;
  border: 0;
  border-radius: 50%;
  background: #dc2626;
  color: #ffffff;
  cursor: pointer;
  font-size: 1rem;
  line-height: 1;
}

.custom-signup-role-boundary-note,
.custom-signup-faculty-note {
  margin: 0.2rem 0 0;
  color: #5b665f;
  font-size: 0.78rem;
  font-weight: 700;
  line-height: 1.45;
}

.custom-signup-faculty-note {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  padding: 0.75rem 0.85rem;
  border: 1px solid #d9eadf;
  border-radius: 10px;
  background: #ffffff;
}

.custom-signup-faculty-note strong {
  color: #08784a;
  font-size: 0.82rem;
}

.custom-signup-confirmation {
  display: flex;
  align-items: flex-start;
  gap: 0.7rem;
  margin: 0.1rem 0 0;
  color: #4b5563;
  font-size: 0.82rem;
  font-weight: 700;
  line-height: 1.4;
  cursor: pointer;
}

.custom-signup-confirmation input {
  flex: 0 0 auto;
  width: 18px;
  height: 18px;
  margin-top: 0.1rem;
  accent-color: #08784a;
  cursor: pointer;
}

.custom-signup-submit {
  min-height: 44px;
  width: 100%;
  margin: 0.1rem 0 0;
  border: 0;
  border-radius: 10px;
  background: #08784a;
  color: #ffffff;
  font-size: 0.95rem;
  font-weight: 900;
  cursor: pointer;
  transition: background-color 0.18s ease, transform 0.1s ease;
}

.custom-signup-submit:hover:not(:disabled) {
  background: #05613d;
}

.custom-signup-submit:active:not(:disabled) {
  transform: scale(0.98);
}

.custom-signup-submit:disabled {
  background: #8ab9a3;
  cursor: not-allowed;
}

.custom-signup-secondary-action {
  width: fit-content;
  margin: 0 auto;
  border: 0;
  background: transparent;
  color: #08784a;
  font-size: 0.85rem;
  font-weight: 900;
  cursor: pointer;
}

.custom-signup-secondary-action:hover {
  text-decoration: underline;
}

.custom-signup-verification-form {
  gap: 1rem;
}

.custom-signup-verification-copy {
  margin: 0;
  color: #4b5563;
  font-size: 0.92rem;
  font-weight: 700;
  line-height: 1.5;
}

.custom-signup-back-link {
  display: block;
  width: fit-content;
  margin: 1rem auto 0;
  color: #08784a;
  font-size: 0.9rem;
  font-weight: 900;
  text-align: center;
  text-decoration: none;
}

.custom-signup-back-link:hover {
  text-decoration: underline;
}

.custom-signup-error-box,
.custom-signup-success-box {
  margin: 0;
  padding: 0.7rem 0.85rem;
  border-radius: 10px;
  font-size: 0.82rem;
  font-weight: 800;
}

.custom-signup-error-box {
  border: 1px solid #fecaca;
  background: #fef2f2;
  color: #b91c1c;
}

.custom-signup-success-box {
  border: 1px solid #bbf7d0;
  background: #f0fdf4;
  color: #166534;
}

.custom-signup-page-footer {
  position: absolute;
  bottom: 2rem;
  z-index: 1;
  width: 100%;
  text-align: center;
  color: #7a827d;
  font-size: 0.72rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

@media (max-width: 1100px) {
  .custom-signup-branding-panel,
  .custom-signup-form-panel {
    width: 50%;
  }

  .custom-signup-field-grid {
    grid-template-columns: 1fr;
  }

  .custom-signup-page-footer {
    bottom: 1.35rem;
  }
}

@media (max-width: 768px) {
  .custom-signup-page {
    flex-direction: column;
    overflow: auto;
  }

  .custom-signup-branding-panel {
    width: 100%;
    min-height: 36vh;
    padding: 2rem 1.25rem;
  }

  .custom-signup-branding-panel::after {
    display: none;
  }

  .custom-signup-branding-content {
    width: min(100%, 520px);
    min-height: auto;
    gap: 2rem;
  }

  .custom-signup-logo {
    width: 62px;
    height: 62px;
  }

  .custom-signup-brand-title {
    font-size: 2rem;
  }

  .custom-signup-brand-subtitle {
    font-size: 1.85rem;
  }

  .custom-signup-brand-description {
    margin-top: 0.85rem;
    font-size: 0.9rem;
  }

  .custom-signup-form-panel {
    width: 100%;
    min-height: 64vh;
    padding: 1.25rem 1rem 4rem;
  }

  .custom-signup-watermark {
    right: -25%;
    width: 420px;
  }

  .custom-signup-card {
    max-height: none;
    padding: 1.25rem;
    border-radius: 14px;
  }

  .custom-signup-page-footer {
    bottom: 1.5rem;
  }
}
</style>
