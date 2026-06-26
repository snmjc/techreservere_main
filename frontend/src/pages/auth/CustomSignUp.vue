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
              <div v-if="!isInvitationMode" class="custom-signup-row">
                <label for="firstName">First Name</label>
                <input
                  id="firstName"
                  v-model="formData.firstName"
                  type="text"
                  :required="!isInvitationMode"
                  autocomplete="given-name"
                  @input="sanitizeNameField('firstName')"
                />
              </div>

              <div v-if="!isInvitationMode" class="custom-signup-row">
                <label for="lastName">Last Name</label>
                <input
                  id="lastName"
                  v-model="formData.lastName"
                  type="text"
                  :required="!isInvitationMode"
                  autocomplete="family-name"
                  @input="sanitizeNameField('lastName')"
                />
              </div>

              <div class="custom-signup-row">
                <label for="idNumber">ID Number</label>
                <input
                  id="idNumber"
                  v-model="formData.idNumber"
                  type="text"
                  inputmode="numeric"
                  maxlength="9"
                  :required="!isInvitationMode"
                  @input="sanitizeIdNumberField"
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
                <label for="fitEmailAddress">Institutional Email Address</label>
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
                    Required for student requests: upload a PDF, DOC, DOCX, JPG, or PNG file. Accepted documents include a Certificate of Officership, Student ID, or proof that you are from the organization. Maximum file size: 5 MB.
                  </p>
                </div>

                <div v-else class="custom-signup-faculty-note">
                  <strong>No file upload needed for faculty.</strong>
                  <span>Recommended: use your official institutional email and complete your department details for admin verification.</span>
                </div>
              </div>
            </div>

            <label class="custom-signup-confirmation">
              <input
                v-model="formData.acceptTerms"
                type="checkbox"
                required
                @change="handlePrivacyConsentChange"
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

    <div
      v-if="showDataPrivacyModal"
      class="custom-signup-privacy-overlay"
      role="dialog"
      aria-modal="true"
      aria-labelledby="customSignupPrivacyTitle"
    >
      <section class="custom-signup-privacy-modal">
        <header class="custom-signup-privacy-header">
          <h2 id="customSignupPrivacyTitle">Data Privacy Policy Statement</h2>
        </header>
        <div class="custom-signup-privacy-body">
          <img
            :src="dataPrivacyPolicyImage"
            alt="FEU Group of Schools Data Privacy Policy Statement"
            class="custom-signup-privacy-image"
          />
        </div>
        <footer class="custom-signup-privacy-footer">
          <button type="button" class="custom-signup-privacy-button" @click="closeDataPrivacyModal">
            OK
          </button>
        </footer>
      </section>
    </div>
  </div>
</template>

<script setup>
import { useCustomSignUpPage } from './composables/useCustomSignUpPage.js';
import dataPrivacyPolicyImage from '@/assets/data privacy(1).png';

const {
  formData,
  departmentOptions,
  isLoading,
  successMessage,
  awaitingVerification,
  verificationCode,
  showPassword,
  showConfirmPassword,
  showDataPrivacyModal,
  studentSupportingFileInput,
  firstErrorMessage,
  isStudentRole,
  isInvitationMode,
  invitationCopy,
  supportingFileAccept,
  sanitizeNameField,
  sanitizeIdNumberField,
  handlePrivacyConsentChange,
  closeDataPrivacyModal,
  openStudentSupportingFile,
  handleStudentSupportingFileChange,
  removeStudentSupportingFile,
  handleSignUp,
  handleVerifyEmail,
} = useCustomSignUpPage();
</script>

<style scoped>
@import './css/CustomSignUp.css';
</style>

