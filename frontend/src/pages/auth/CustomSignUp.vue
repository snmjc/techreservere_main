<!-- ===== Custom Sign Up Page ===== -->
<template>
  <div class="custom-signup-page">
    <section class="custom-signup-branding-panel">
      <img
        src="@/assets/Page-20-3.png"
        alt=""
        class="custom-signup-branding-image"
      />
      <div class="custom-signup-branding-content">
        <img
          src="@/assets/TechReserve_LogoB.png"
          alt="TechReserve Logo"
          class="custom-signup-logo"
        />

        <h1 class="custom-signup-brand-title">
          <span class="custom-signup-brand-tech">Tech</span><span class="custom-signup-brand-reserve">Reserve</span>
        </h1>

        <h2 class="custom-signup-brand-subtitle">
          Analytics-Driven Equipment Readiness and Reservation System
        </h2>

        <p class="custom-signup-brand-description">
          Supporting efficient equipment coordination and institutional resource planning at
          <strong>FEU Institute of Technology</strong>.
        </p>
      </div>
    </section>

    <section class="custom-signup-form-panel">
      <img
        src="@/assets/FEU_Tech_official_seal.png"
        alt="FEU Tech Seal Watermark"
        class="custom-signup-watermark"
      />

      <div class="custom-signup-form-content">
        <h1 class="custom-signup-heading">Sign Up</h1>

        <form v-if="!awaitingVerification" class="custom-signup-form" @submit.prevent="handleSignUp">
          <div class="custom-signup-row">
            <label for="accountType">Account Type:</label>
            <select id="accountType" v-model="formData.accountType" class="custom-signup-select">
              <option value="Admin">Admin</option>
              <option value="Borrower">Borrower</option>
            </select>
          </div>

          <div class="custom-signup-row">
            <label for="lastName">Last Name:</label>
            <input
              id="lastName"
              v-model="formData.lastName"
              type="text"
              required
              autocomplete="family-name"
            />
          </div>

          <div class="custom-signup-row">
            <label for="firstName">First Name:</label>
            <input
              id="firstName"
              v-model="formData.firstName"
              type="text"
              required
              autocomplete="given-name"
            />
          </div>

          <div class="custom-signup-row">
            <label for="idNumber">ID Number:</label>
            <input
              id="idNumber"
              v-model="formData.idNumber"
              type="text"
              required
            />
          </div>

          <div class="custom-signup-row">
            <label for="fitEmailAddress">FIT Email Address:</label>
            <input
              id="fitEmailAddress"
              v-model="formData.fitEmailAddress"
              type="email"
              required
              autocomplete="email"
            />
          </div>

          <div class="custom-signup-row">
            <label for="department">Department:</label>
            <input
              id="department"
              v-model="formData.department"
              type="text"
              required
            />
          </div>

          <div class="custom-signup-row">
            <label for="role">Role:</label>
            <select id="role" v-model="formData.role" class="custom-signup-select">
              <option value="FO Admin">FO Admin</option>
              <option value="Faculty">Faculty</option>
              <option value="Student">Student</option>
              <option value="Staff">Staff</option>
            </select>
          </div>

          <div class="custom-signup-row">
            <label for="password">Password:</label>
            <input
              id="password"
              v-model="formData.password"
              type="password"
              required
              autocomplete="new-password"
            />
          </div>

          <div class="custom-signup-row">
            <label for="confirmPassword">Confirm Password:</label>
            <input
              id="confirmPassword"
              v-model="formData.confirmPassword"
              type="password"
              required
              autocomplete="new-password"
            />
          </div>

          <label class="custom-signup-confirmation">
            <input
              v-model="formData.acceptTerms"
              type="checkbox"
              required
            />
            <span>
              I confirm that this account will be used solely for institutional equipment
              reservation purposes and is subject to Facilities Office policies.
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
            {{ isLoading ? 'Registering...' : 'Register' }}
          </button>
        </form>

        <form v-else class="custom-signup-form custom-signup-verification-form" @submit.prevent="handleVerifyEmail">
          <p class="custom-signup-verification-copy">
            Clerk sent a verification code to <strong>{{ formData.fitEmailAddress }}</strong>.
          </p>

          <div class="custom-signup-row">
            <label for="verificationCode">Verification Code:</label>
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
            {{ isLoading ? 'Verifying...' : 'Verify Email' }}
          </button>

          <button type="button" class="custom-signup-secondary-action" @click="awaitingVerification = false">
            Edit registration details
          </button>
        </form>

        <router-link class="custom-signup-back-link" to="/clerk-login">
          Back to Login
        </router-link>
      </div>

      <footer class="custom-signup-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </footer>
    </section>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useSignUp } from '@clerk/vue';
import { isAdminEmail } from '@/modules/authentication/utils/roleUtils.js';

const router = useRouter();
const { isLoaded: signUpLoaded, signUp, setActive } = useSignUp();

const formData = ref({
  accountType: 'Admin',
  lastName: '',
  firstName: '',
  idNumber: '',
  fitEmailAddress: '',
  department: '',
  role: 'FO Admin',
  password: '',
  confirmPassword: '',
  acceptTerms: false,
});

const errors = ref({});
const isLoading = ref(false);
const successMessage = ref('');
const awaitingVerification = ref(false);
const verificationCode = ref('');

const firstErrorMessage = computed(() => Object.values(errors.value)[0] || '');

function validateForm() {
  errors.value = {};

  if (!formData.value.lastName.trim()) {
    errors.value.lastName = 'Last name is required.';
  }

  if (!formData.value.firstName.trim()) {
    errors.value.firstName = 'First name is required.';
  }

  if (!formData.value.idNumber.trim()) {
    errors.value.idNumber = 'ID number is required.';
  }

  if (!formData.value.fitEmailAddress.trim()) {
    errors.value.fitEmailAddress = 'FIT email address is required.';
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.value.fitEmailAddress)) {
    errors.value.fitEmailAddress = 'Please enter a valid FIT email address.';
  }

  if (!formData.value.department.trim()) {
    errors.value.department = 'Department is required.';
  }

  if (formData.value.password.length < 8) {
    errors.value.password = 'Password must be at least 8 characters.';
  }

  if (formData.value.password !== formData.value.confirmPassword) {
    errors.value.confirmPassword = 'Passwords do not match.';
  }

  if (!formData.value.acceptTerms) {
    errors.value.acceptTerms = 'Please confirm the account purpose policy.';
  }

  return Object.keys(errors.value).length === 0;
}

function getClerkErrorMessage(error) {
  return error?.errors?.[0]?.longMessage
    || error?.errors?.[0]?.message
    || error?.message
    || 'Unable to complete Clerk sign up. Please try again.';
}

function getRoleDesignation() {
  if (isAdminEmail(formData.value.fitEmailAddress)) return 'ROLE_ADMIN';
  return formData.value.accountType === 'Admin' ? 'ROLE_ADMIN' : 'ROLE_BORROWER';
}

async function registerBackendAccount(clerkUserId) {
  const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/v1/users/register`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      clerkUserId,
      firstName: formData.value.firstName.trim(),
      lastName: formData.value.lastName.trim(),
      emailAddress: formData.value.fitEmailAddress.trim(),
      role: getRoleDesignation(),
      idNumber: formData.value.idNumber.trim(),
      contactNumber: formData.value.idNumber.trim(),
      department: formData.value.department.trim(),
    }),
  });

  const result = await response.json().catch(() => ({}));

  if (!response.ok && response.status !== 409) {
    throw new Error(result.errorMessage || 'Clerk account was created, but backend registration failed.');
  }
}

async function completeRegistration(signUpAttempt) {
  const clerkUserId = signUpAttempt?.createdUserId || signUp.value?.createdUserId;

  if (signUpAttempt?.createdSessionId && setActive.value) {
    await setActive.value({ session: signUpAttempt.createdSessionId });
  }

  if (!clerkUserId) {
    throw new Error('Clerk did not return a user ID for this registration.');
  }

  await registerBackendAccount(clerkUserId);
  successMessage.value = 'Clerk account created. Your access is pending administrator approval.';

  setTimeout(() => {
    router.push('/request-pending');
  }, 1200);
}

async function handleSignUp() {
  if (!validateForm()) {
    return;
  }

  if (!signUpLoaded.value || !signUp.value) {
    errors.value.submit = 'Clerk is still loading. Please try again.';
    return;
  }

  isLoading.value = true;
  errors.value = {};
  successMessage.value = '';

  try {
    const signUpAttempt = await signUp.value.create({
      emailAddress: formData.value.fitEmailAddress.trim(),
      password: formData.value.password,
      firstName: formData.value.firstName.trim(),
      lastName: formData.value.lastName.trim(),
      unsafeMetadata: {
        accountType: formData.value.accountType,
        idNumber: formData.value.idNumber.trim(),
        department: formData.value.department.trim(),
        role: formData.value.role,
        roleDesignation: getRoleDesignation(),
      },
    });

    if (signUpAttempt.status === 'complete') {
      await completeRegistration(signUpAttempt);
      return;
    }

    await signUp.value.prepareEmailAddressVerification({ strategy: 'email_code' });
    awaitingVerification.value = true;
    successMessage.value = 'Enter the verification code Clerk sent to your email.';
  } catch (error) {
    errors.value.submit = getClerkErrorMessage(error);
  } finally {
    isLoading.value = false;
  }
}

async function handleVerifyEmail() {
  if (!verificationCode.value) {
    errors.value.verificationCode = 'Verification code is required.';
    return;
  }

  if (!signUpLoaded.value || !signUp.value) {
    errors.value.submit = 'Clerk is still loading. Please try again.';
    return;
  }

  isLoading.value = true;
  errors.value = {};

  try {
    const signUpAttempt = await signUp.value.attemptEmailAddressVerification({
      code: verificationCode.value,
    });

    if (signUpAttempt.status !== 'complete') {
      errors.value.submit = 'Email verification is incomplete. Please check the code and try again.';
      return;
    }

    await completeRegistration(signUpAttempt);
  } catch (error) {
    errors.value.submit = getClerkErrorMessage(error);
  } finally {
    isLoading.value = false;
  }
}
</script>

<style scoped>
.custom-signup-page {
  display: flex;
  min-height: 100vh;
  width: 100%;
  overflow: hidden;
  background: #efefef;
  font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
}

.custom-signup-branding-panel {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 52%;
  min-height: 100vh;
  background: linear-gradient(135deg, rgba(4, 115, 68, 0.97), rgba(13, 151, 84, 0.94));
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
  background: linear-gradient(rgba(4, 129, 76, 0.84), rgba(4, 129, 76, 0.84));
}

.custom-signup-branding-panel::after {
  content: '';
  position: absolute;
  top: 3.5%;
  right: 37%;
  width: 1px;
  height: 88%;
  background: rgba(0, 0, 0, 0.14);
  box-shadow: 1px 0 rgba(255, 255, 255, 0.08);
  z-index: 2;
}

.custom-signup-branding-content {
  position: relative;
  z-index: 3;
  display: flex;
  flex-direction: column;
  align-items: center;
  width: min(78%, 460px);
  text-align: center;
}

.custom-signup-logo {
  width: clamp(190px, 19vw, 260px);
  height: clamp(190px, 19vw, 260px);
  object-fit: contain;
  margin-bottom: 1.15rem;
  filter: drop-shadow(0 10px 12px rgba(0, 0, 0, 0.34));
}

.custom-signup-brand-title {
  margin: 0 0 2.3rem;
  font-size: clamp(2.6rem, 4.3vw, 4.15rem);
  font-weight: 800;
  letter-spacing: 0.02em;
  line-height: 1.2;
}

.custom-signup-brand-tech {
  color: #ffffff;
}

.custom-signup-brand-reserve {
  color: #ffc21a;
}

.custom-signup-brand-subtitle {
  max-width: 420px;
  margin: 0 0 1.5rem;
  color: #ffffff;
  font-size: clamp(1rem, 1.35vw, 1.25rem);
  font-weight: 800;
  line-height: 1.25;
}

.custom-signup-brand-description {
  max-width: 410px;
  margin: 0;
  color: rgba(255, 255, 255, 0.85);
  font-size: clamp(0.72rem, 0.95vw, 0.9rem);
  line-height: 1.35;
}

.custom-signup-brand-description strong {
  color: #ffffff;
  font-weight: 800;
}

.custom-signup-form-panel {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 48%;
  min-height: 100vh;
  padding: 2rem 1rem 5.5rem;
  background: #eeeeee;
  overflow: hidden;
}

.custom-signup-watermark {
  position: absolute;
  top: -7%;
  right: -31%;
  width: min(78vw, 760px);
  max-width: none;
  opacity: 0.12;
  pointer-events: none;
  user-select: none;
  z-index: 0;
}

.custom-signup-form-content {
  position: relative;
  z-index: 1;
  width: min(100%, 520px);
  margin-left: 1.5rem;
}

.custom-signup-heading {
  margin: 0 0 1.5rem;
  color: #050505;
  font-size: clamp(1.9rem, 2.7vw, 2.5rem);
  font-weight: 900;
  line-height: 1;
  text-align: center;
}

.custom-signup-form {
  display: flex;
  flex-direction: column;
  gap: 0.56rem;
}

.custom-signup-row {
  display: grid;
  grid-template-columns: 230px minmax(190px, 1fr);
  align-items: end;
  column-gap: 0.8rem;
}

.custom-signup-row label {
  color: #050505;
  font-size: clamp(1rem, 1.2vw, 1.18rem);
  font-weight: 900;
  line-height: 1.08;
}

.custom-signup-row input,
.custom-signup-select {
  width: 100%;
  min-height: 28px;
  border: 0;
  outline: none;
  background: transparent;
  color: #050505;
  font-size: 0.9rem;
  font-weight: 900;
  text-align: right;
}

.custom-signup-row input {
  padding: 0 0.25rem 0.15rem;
  border-bottom: 2px solid rgba(0, 0, 0, 0.6);
}

.custom-signup-row input:focus {
  border-bottom-color: #06894f;
}

.custom-signup-select {
  justify-self: end;
  width: min(100%, 180px);
  height: 30px;
  padding: 0 0.9rem 0 1rem;
  border-radius: 999px;
  background: #ffffff;
  box-shadow: 0 8px 14px rgba(0, 0, 0, 0.08);
  text-align: center;
  cursor: pointer;
}

.custom-signup-confirmation {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  max-width: 460px;
  margin: 1.35rem auto 0.1rem;
  color: #050505;
  font-size: clamp(0.7rem, 0.85vw, 0.8rem);
  font-weight: 900;
  line-height: 1.15;
  cursor: pointer;
}

.custom-signup-confirmation input {
  flex: 0 0 auto;
  width: 21px;
  height: 21px;
  margin-top: 0.05rem;
  accent-color: #06894f;
  cursor: pointer;
}

.custom-signup-submit {
  min-height: 34px;
  width: 180px;
  margin: 0.75rem auto 0;
  border: 0;
  border-radius: 999px;
  background: #06894f;
  color: #ffffff;
  font-size: 1rem;
  font-weight: 900;
  cursor: pointer;
  transition: background-color 0.2s ease, transform 0.1s ease;
}

.custom-signup-submit:hover:not(:disabled) {
  background: #047342;
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
  margin: 0.65rem auto 0;
  border: 0;
  background: transparent;
  color: #06894f;
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
  max-width: 420px;
  margin: 0 auto 0.25rem;
  color: #050505;
  font-size: 0.9rem;
  font-weight: 800;
  line-height: 1.35;
  text-align: center;
}

.custom-signup-back-link {
  display: block;
  width: fit-content;
  margin: 1.05rem auto 0;
  color: #06894f;
  font-size: 1.1rem;
  font-weight: 900;
  text-align: center;
  text-decoration: none;
}

.custom-signup-back-link:hover {
  text-decoration: underline;
}

.custom-signup-error-box,
.custom-signup-success-box {
  max-width: 420px;
  margin: 0.4rem auto 0;
  padding: 0.55rem 0.85rem;
  border-radius: 10px;
  font-size: 0.78rem;
  font-weight: 800;
  text-align: center;
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
  bottom: 4.6rem;
  z-index: 1;
  width: 100%;
  text-align: center;
  color: #878787;
  font-size: clamp(0.66rem, 0.9vw, 0.78rem);
  letter-spacing: 0.01em;
  text-transform: uppercase;
}

@media (max-width: 1100px) {
  .custom-signup-branding-panel,
  .custom-signup-form-panel {
    width: 50%;
  }

  .custom-signup-form-content {
    margin-left: 0;
  }

  .custom-signup-row {
    grid-template-columns: 190px minmax(165px, 1fr);
  }

  .custom-signup-page-footer {
    bottom: 2.25rem;
  }
}

@media (max-width: 768px) {
  .custom-signup-page {
    flex-direction: column;
    overflow: auto;
  }

  .custom-signup-branding-panel {
    width: 100%;
    min-height: 34vh;
    padding: 2rem 1rem;
  }

  .custom-signup-branding-panel::after {
    display: none;
  }

  .custom-signup-logo {
    width: 105px;
    height: 105px;
    margin-bottom: 0.5rem;
  }

  .custom-signup-brand-title {
    margin-bottom: 0.75rem;
    font-size: 2rem;
  }

  .custom-signup-brand-subtitle {
    margin-bottom: 0;
    font-size: 0.88rem;
  }

  .custom-signup-brand-description {
    display: none;
  }

  .custom-signup-form-panel {
    width: 100%;
    min-height: 66vh;
    padding: 2rem 1.25rem 5rem;
  }

  .custom-signup-watermark {
    right: -25%;
    width: 420px;
  }

  .custom-signup-row {
    grid-template-columns: 1fr;
    gap: 0.25rem;
  }

  .custom-signup-row input,
  .custom-signup-select {
    text-align: left;
  }

  .custom-signup-select {
    justify-self: stretch;
    width: 100%;
  }

  .custom-signup-submit {
    width: 100%;
  }

  .custom-signup-page-footer {
    bottom: 1.5rem;
  }
}
</style>
