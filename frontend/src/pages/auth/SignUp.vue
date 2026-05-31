<!-- ===== Sign Up Page ===== -->
<template>
  <div class="signup-wrapper">
    <!-- Left Panel - Branding (mirrors login page) -->
    <div class="left-panel">
      <div class="left-overlay"></div>
      <div class="left-content">
        <img src="@/assets/TechReserve_LogoB.png" alt="TechReserve Logo" class="logo-image" />
        <h1 class="brand-title">
          <span class="tech-text">Tech</span><span class="reserve-text">Reserve</span>
        </h1>
        <h2 class="tagline">Analytics-Driven Equipment Readiness and<br />Reservation System</h2>
        <p class="description">
          Supporting efficient equipment coordination and institutional<br />
          resource planning at <strong>FEU Institute of Technology</strong>.
        </p>
      </div>
    </div>

    <!-- Right Panel - Registration Form -->
    <div class="right-panel">
      <img src="@/assets/FEU_Tech_official_seal.png" alt="FEU Tech Seal" class="seal-watermark" />

      <div class="right-content">

        <!-- Step 1: Registration Form -->
        <template v-if="!showVerification">
          <h2 class="form-heading">Sign Up</h2>

          <div v-if="errorMessage" class="error-box">{{ errorMessage }}</div>

          <form class="signup-form" @submit.prevent="handleRegister" novalidate>
            <div class="form-row">
              <label class="form-label">Last Name:</label>
              <input type="text" v-model="form.lastName" class="input-line" placeholder="Dela Cruz" required />
            </div>

            <div class="form-row">
              <label class="form-label">First Name:</label>
              <input type="text" v-model="form.firstName" class="input-line" placeholder="Juan" required />
            </div>

            <div class="form-row">
              <label class="form-label">ID Number:</label>
              <input type="text" v-model="form.idNumber" class="input-line" placeholder="2023-XXXXX" required />
            </div>

            <div class="form-row">
              <label class="form-label">FIT Email Address:</label>
              <input type="email" v-model="form.email" class="input-line" placeholder="jdelacruz@fit.edu.ph" required />
            </div>

            <div class="form-row">
              <label class="form-label">Department:</label>
              <input type="text" v-model="form.department" class="input-line" placeholder="CCSMMA" required />
            </div>

            <div class="form-row">
              <label class="form-label">Role:</label>
              <select v-model="form.role" class="input-select">
                <option value="ROLE_BORROWER">Student</option>
                <option value="ROLE_FACULTY">Faculty</option>
              </select>
            </div>

            <div v-if="isStudentRole" class="form-row">
              <label class="form-label">Supporting File:</label>
              <div class="file-upload-wrapper">
                <input
                  type="file"
                  id="supporting-file"
                  class="file-input-hidden"
                  accept=".docx,.doc,.pdf,.png,.jpeg,.jpg"
                  @change="handleFileChange"
                />
                <label for="supporting-file" class="file-upload-btn">Choose File</label>
                <span v-if="form.file" class="file-name">{{ form.file.name }}</span>
                <span v-else class="file-placeholder">No file chosen</span>
                <button v-if="form.file" type="button" class="file-remove-btn" @click="removeFile">&times;</button>
              </div>
            </div>
            <div v-if="isStudentRole" class="file-types-hint">
              Recommended for student requests: valid student ID or enrollment proof. Accepted: PDF, DOC, DOCX, PNG, JPEG
            </div>

            <div v-else class="faculty-recommendation-box">
              <strong>No supporting file needed for faculty.</strong>
              <span>Recommended: use your official FIT faculty email and complete your department details for admin verification.</span>
            </div>

            <div class="form-row">
              <label class="form-label">Password:</label>
              <input type="password" v-model="form.password" class="input-line" required />
            </div>

            <div class="form-row">
              <label class="form-label">Confirm Password:</label>
              <input type="password" v-model="form.confirmPassword" class="input-line" required />
            </div>

            <div class="checkbox-row">
              <input type="checkbox" v-model="form.agreed" id="policy" class="policy-checkbox" />
              <label for="policy" class="policy-label">
                I confirm that this account will be used solely for institutional equipment
                reservation purposes and is subject to Facilities Office policies.
              </label>
            </div>

            <button type="submit" class="register-btn" :disabled="isLoading">
              {{ isLoading ? 'Registering...' : 'Register' }}
            </button>
          </form>

          <p class="login-prompt">
            Already have an account?
            <a href="#" class="login-link" @click.prevent="navigateToLogin">Log In</a>
          </p>
        </template>

        <!-- Step 2: Email Verification -->
        <template v-else>
          <h2 class="form-heading">Verify Email</h2>
          <p class="verify-info">Enter the 6-digit code sent to <strong>{{ form.email }}</strong></p>

          <div v-if="errorMessage" class="error-box">{{ errorMessage }}</div>

          <div class="verify-form">
            <input
              type="text"
              v-model="verificationCode"
              class="verify-input"
              placeholder="Enter 6-digit code"
              maxlength="6"
            />
            <button class="register-btn" :disabled="isLoading" @click="handleVerification">
              {{ isLoading ? 'Verifying...' : 'Verify & Complete' }}
            </button>
            <p class="resend-text">
              Didn't receive a code?
              <a href="#" @click.prevent="resendCode" class="login-link">Resend</a>
            </p>
          </div>
        </template>

        <p class="page-footer">© 2026 TECHRESERVE. DATAMS MANAGEMENT.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useSignUp, useAuth } from '@clerk/vue';
import { apiUrl } from '@/shared/utils/apiBase.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';

const router = useRouter();
const { signUp, isLoaded } = useSignUp();
const { signOut } = useAuth();

const showVerification = ref(false);
const isLoading = ref(false);
const errorMessage = ref('');
const verificationCode = ref('');

const form = ref({
  lastName: '',
  firstName: '',
  idNumber: '',
  email: '',
  department: '',
  role: 'ROLE_BORROWER',
  password: '',
  confirmPassword: '',
  agreed: false,
  file: null,
});

const isStudentRole = computed(() => form.value.role === 'ROLE_BORROWER');

watch(
  () => form.value.role,
  (role) => {
    if (role === 'ROLE_FACULTY') {
      removeFile();
    }
  },
);

function handleFileChange(event) {
  const file = event.target.files[0];
  if (file) form.value.file = file;
}

function removeFile() {
  form.value.file = null;
  const input = document.getElementById('supporting-file');
  if (input) input.value = '';
}

async function handleRegister() {
  errorMessage.value = '';

  if (form.value.password !== form.value.confirmPassword) {
    errorMessage.value = 'Passwords do not match.';
    return;
  }
  if (!form.value.agreed) {
    errorMessage.value = 'Please agree to the Facilities Office policies.';
    return;
  }
  if (!isLoaded.value) {
    errorMessage.value = 'Authentication service not ready. Please try again.';
    return;
  }

  isLoading.value = true;
  try {
    const result = await signUp.value.create({
      emailAddress: form.value.email,
      password: form.value.password,
      firstName: form.value.firstName,
      lastName: form.value.lastName,
    });

    if (result.status === 'missing_requirements') {
      await signUp.value.prepareEmailAddressVerification({ strategy: 'email_code' });
      showVerification.value = true;
    } else if (result.status === 'complete') {
      await saveToPostgres(result.createdUserId);
      await signOut.value();
      router.push({ name: ROUTE_NAMES.clerkLogin });
    }
  } catch (err) {
    errorMessage.value = err.errors?.[0]?.longMessage || err.errors?.[0]?.message || 'Registration failed. Please try again.';
  } finally {
    isLoading.value = false;
  }
}

async function handleVerification() {
  if (!verificationCode.value) return;
  isLoading.value = true;
  errorMessage.value = '';
  try {
    const result = await signUp.value.attemptEmailAddressVerification({ code: verificationCode.value });
    if (result.status === 'complete') {
      await saveToPostgres(result.createdUserId);
      await signOut.value();
      router.push({ name: ROUTE_NAMES.clerkLogin });
    }
  } catch (err) {
    errorMessage.value = err.errors?.[0]?.longMessage || err.errors?.[0]?.message || 'Invalid code. Please try again.';
  } finally {
    isLoading.value = false;
  }
}

async function resendCode() {
  try {
    await signUp.value.prepareEmailAddressVerification({ strategy: 'email_code' });
    errorMessage.value = '';
  } catch (err) {
    errorMessage.value = 'Failed to resend code.';
  }
}

async function saveToPostgres(clerkUserId) {
  try {
    const response = await fetch(apiUrl('/api/v1/users/register'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        clerkUserId,
        firstName: form.value.firstName,
        lastName: form.value.lastName,
        emailAddress: form.value.email,
        role: form.value.role,
        contactNumber: form.value.idNumber,
        department: form.value.department,
      }),
    });
    if (!response.ok && response.status !== 409) {
      const errorData = await response.json();
      console.error('Failed to save user to PostgreSQL:', errorData);
      throw new Error(errorData.message || 'Failed to save to PostgreSQL');
    }
    const data = await response.json();
    console.log('Account saved to PostgreSQL:', data);
    return data;
  } catch (err) {
    console.error('Error saving to PostgreSQL:', err);
    throw err;
  }
}

function navigateToLogin() {
  router.push({ name: ROUTE_NAMES.clerkLogin });
}
</script>

<style scoped>
* { margin: 0; padding: 0; box-sizing: border-box; }

.signup-wrapper {
  display: flex;
  min-height: 100vh;
  width: 100%;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

/* ===== LEFT PANEL ===== */
.left-panel {
  width: 45%;
  background-image: url('@/assets/Page-20-3.png');
  background-size: cover;
  background-position: center;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  position: relative;
  overflow: hidden;
  min-height: 100vh;
}

.left-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(160deg, rgba(10, 120, 60, 0.82) 0%, rgba(5, 80, 35, 0.88) 100%);
}

.left-content {
  position: relative;
  z-index: 2;
  text-align: center;
  color: white;
  max-width: 320px;
}

.logo-image {
  display: block;
  width: 130px;
  height: 130px;
  margin: 0 auto 1.25rem;
  filter: drop-shadow(0 6px 18px rgba(0,0,0,0.35));
}

.brand-title {
  font-size: 2.6rem;
  font-weight: 700;
  margin-bottom: 1.25rem;
  letter-spacing: -0.5px;
}

.tech-text   { color: white; }
.reserve-text { color: #f5c518; }

.tagline {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 1rem;
  line-height: 1.4;
}

.description {
  font-size: 0.82rem;
  line-height: 1.55;
  color: rgba(255,255,255,0.88);
}

/* ===== RIGHT PANEL ===== */
.right-panel {
  width: 55%;
  background-color: #f2f2f2;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 2.5rem;
  position: relative;
  overflow: hidden;
}

.seal-watermark {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 1000px;
  height: 1000px;
  object-fit: contain;
  opacity: 0.1;
  pointer-events: none;
  user-select: none;
}

.right-content {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 480px;
}

/* ===== HEADING ===== */
.form-heading {
  font-size: 2rem;
  font-weight: 700;
  color: #111;
  text-align: center;
  margin-bottom: 1.25rem;
}

/* ===== FORM ROWS ===== */
.signup-form {
  width: 100%;
}

.form-row {
  display: grid;
  grid-template-columns: 170px 1fr;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.form-label {
  font-size: 0.92rem;
  font-weight: 700;
  color: #111;
}

.input-line {
  width: 100%;
  padding: 0.35rem 0.25rem;
  font-size: 0.88rem;
  color: #333;
  background: transparent;
  border: none;
  border-bottom: 1.5px solid #666;
  outline: none;
  text-align: right;
  transition: border-color 0.2s;
}

.input-line:focus { border-bottom-color: #1a6e3a; }
.input-line::placeholder { color: #bbb; }

.input-select {
  width: 100%;
  padding: 0.35rem 0.75rem;
  font-size: 0.88rem;
  color: #333;
  background: white;
  border: 1px solid #aaa;
  border-radius: 20px;
  outline: none;
  cursor: pointer;
  text-align: center;
  text-align-last: center;
}

.input-select:focus { border-color: #1a6e3a; }

/* ===== CHECKBOX ===== */
.checkbox-row {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  margin: 1rem 0;
}

.policy-checkbox {
  width: 16px;
  height: 16px;
  margin-top: 0.15rem;
  accent-color: #1a6e3a;
  flex-shrink: 0;
  cursor: pointer;
}

.policy-label {
  font-size: 0.78rem;
  color: #333;
  line-height: 1.45;
  cursor: pointer;
}

/* ===== REGISTER BUTTON ===== */
.register-btn {
  display: block;
  margin: 0 auto 0.75rem;
  padding: 0.6rem 2.5rem;
  font-size: 0.95rem;
  font-weight: 700;
  color: white;
  background-color: #1a6e3a;
  border: none;
  border-radius: 20px;
  cursor: pointer;
  transition: background-color 0.2s, transform 0.1s;
  letter-spacing: 0.3px;
}

.register-btn:hover:not(:disabled) { background-color: #145a30; }
.register-btn:disabled { background-color: #88c4a0; cursor: not-allowed; }

/* ===== ERROR ===== */
.error-box {
  margin-bottom: 0.75rem;
  padding: 0.55rem 1rem;
  font-size: 0.83rem;
  color: #b91c1c;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  text-align: center;
}

/* ===== LOGIN PROMPT ===== */
.login-prompt {
  text-align: center;
  font-size: 0.83rem;
  color: #555;
}

.login-link {
  color: #1a6e3a;
  font-weight: 600;
  font-style: italic;
  text-decoration: none;
}

.login-link:hover { text-decoration: underline; }

/* ===== VERIFICATION ===== */
.verify-info {
  text-align: center;
  font-size: 0.88rem;
  color: #444;
  margin-bottom: 1.25rem;
}

.verify-form {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.verify-input {
  width: 200px;
  padding: 0.6rem 1rem;
  font-size: 1.2rem;
  text-align: center;
  letter-spacing: 0.3rem;
  border: 1.5px solid #aaa;
  border-radius: 8px;
  outline: none;
}

.verify-input:focus { border-color: #1a6e3a; }

.resend-text {
  font-size: 0.82rem;
  color: #666;
}

/* ===== FILE UPLOAD ===== */
.file-upload-wrapper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.file-input-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  pointer-events: none;
}

.file-upload-btn {
  display: inline-block;
  padding: 0.25rem 0.9rem;
  font-size: 0.8rem;
  font-weight: 600;
  color: white;
  background-color: #1a6e3a;
  border-radius: 14px;
  cursor: pointer;
  white-space: nowrap;
  transition: background-color 0.2s;
}

.file-upload-btn:hover { background-color: #145a30; }

.file-name {
  font-size: 0.78rem;
  color: #333;
  font-style: italic;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 160px;
}

.file-placeholder {
  font-size: 0.78rem;
  color: #aaa;
}

.file-remove-btn {
  width: 20px;
  height: 20px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 50%;
  font-size: 1rem;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.file-remove-btn:hover { background: #b91c1c; }

.file-types-hint {
  text-align: right;
  font-size: 0.72rem;
  color: #999;
  margin-top: -0.5rem;
  margin-bottom: 0.5rem;
}

.faculty-recommendation-box {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  margin: 0.35rem 0 0.75rem 170px;
  padding: 0.75rem 0.85rem;
  border: 1px solid #d9eadf;
  border-radius: 10px;
  background: #f8fcfa;
  color: #4b5563;
  font-size: 0.78rem;
  line-height: 1.45;
}

.faculty-recommendation-box strong {
  color: #08784a;
  font-size: 0.82rem;
}

/* ===== FOOTER ===== */
.page-footer {
  text-align: center;
  font-size: 0.72rem;
  color: #aaa;
  margin-top: 1rem;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
  .signup-wrapper { flex-direction: column; }
  .left-panel  { width: 100%; min-height: 40vh; }
  .right-panel { width: 100%; }
  .form-row { grid-template-columns: 1fr; gap: 0.2rem; }
  .input-line { text-align: left; }
  .faculty-recommendation-box { margin-left: 0; }
  .seal-watermark { width: 320px; height: 320px; }
}
</style>
