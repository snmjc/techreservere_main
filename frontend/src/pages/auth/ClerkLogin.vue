<template>
  <div class="clerk-login-page">
    <section class="clerk-login-branding-panel">
      <img
        src="@/assets/Page-20-3.png"
        alt=""
        class="clerk-login-branding-image"
      />
      <div class="clerk-login-branding-content">
        <div class="clerk-login-brand-mark">
          <img
            src="@/assets/TechReserve_LogoB.png"
            alt="TechReserve Logo"
            class="clerk-login-logo"
          />
          <h1 class="clerk-login-brand-title">
            <span class="clerk-login-brand-tech">Tech</span><span class="clerk-login-brand-reserve">Reserve</span>
          </h1>
        </div>

        <div class="clerk-login-brand-copy">
          <p class="clerk-login-kicker">Facilities Office Access</p>
          <h2 class="clerk-login-brand-subtitle">
            Equipment readiness, reservations, and approvals in one workspace.
          </h2>
          <p class="clerk-login-brand-description">
            Built for FEU Institute of Technology teams coordinating rooms, equipment, and requestor access.
          </p>
        </div>
      </div>
    </section>

    <section class="clerk-login-form-panel">
      <div class="clerk-login-form-content">
        <div class="clerk-login-card">
          <form v-if="!isResettingPassword" class="techreserve-local-login-form" @submit.prevent="handleLocalLogin">
            <h2 class="techreserve-local-login-title">Welcome to TechReserve</h2>
            <p v-if="loginError" class="techreserve-local-login-error">{{ loginError }}</p>

            <label class="techreserve-local-login-field">
              <span>Email address</span>
              <input
                v-model.trim="emailAddress"
                type="email"
                autocomplete="username"
                required
              />
            </label>

            <label class="techreserve-local-login-field">
              <span>Password</span>
              <input
                v-model="passwordText"
                type="password"
                autocomplete="current-password"
                required
              />
            </label>

            <div class="techreserve-local-login-options">
              <label class="techreserve-local-login-remember">
                <input
                  v-model="rememberMeChecked"
                  type="checkbox"
                />
                <span>Remember me</span>
              </label>

              <button
                type="button"
                class="techreserve-local-login-link"
                @click="showResetPasswordForm"
              >
                Forgot password?
              </button>
            </div>

            <button class="techreserve-local-login-button" type="submit" :disabled="isSubmitting">
              {{ isSubmitting ? 'Signing in...' : 'Sign in' }}
            </button>

            <p class="techreserve-local-login-signup">
              Don't have an account?
              <router-link :to="{ name: ROUTE_NAMES.customSignUp }">Sign up</router-link>
            </p>
          </form>

          <form v-else class="techreserve-local-login-form" @submit.prevent="handleResetPasswordSubmit">
            <h2 class="techreserve-local-login-title">Reset your password</h2>
            <p v-if="resetPasswordError" class="techreserve-local-login-error">{{ resetPasswordError }}</p>
            <p v-if="resetPasswordMessage" class="techreserve-local-login-info">{{ resetPasswordMessage }}</p>

            <label class="techreserve-local-login-field">
              <span>Email address</span>
              <input
                v-model.trim="resetEmailAddress"
                type="email"
                autocomplete="username"
                required
                :disabled="resetCodeSent"
              />
            </label>

            <template v-if="resetCodeSent">
              <label class="techreserve-local-login-field">
                <span>Email code</span>
                <input
                  v-model.trim="resetCodeText"
                  type="text"
                  inputmode="numeric"
                  autocomplete="one-time-code"
                  required
                />
              </label>

              <label class="techreserve-local-login-field">
                <span>New password</span>
                <input
                  v-model="resetPasswordText"
                  type="password"
                  autocomplete="new-password"
                  required
                />
              </label>

              <label class="techreserve-local-login-field">
                <span>Confirm new password</span>
                <input
                  v-model="resetPasswordConfirmText"
                  type="password"
                  autocomplete="new-password"
                  required
                />
              </label>
            </template>

            <button class="techreserve-local-login-button" type="submit" :disabled="isResetSubmitting">
              {{ resolveResetPasswordButtonText() }}
            </button>

            <button
              type="button"
              class="techreserve-local-login-secondary-button"
              :disabled="isResetSubmitting"
              @click="hideResetPasswordForm"
            >
              Back to sign in
            </button>
          </form>
        </div>
      </div>

      <footer class="clerk-login-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </footer>
    </section>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { AUTH_STORAGE_KEYS } from '@/modules/authentication/utils/authStorage.js';
import { apiUrl } from '@/shared/utils/apiBase.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';

const router = useRouter();
const authStore = useAuthenticationStore();

const emailAddress = ref('');
const passwordText = ref('');
const rememberMeChecked = ref(false);
const loginError = ref('');
const isSubmitting = ref(false);
const isResettingPassword = ref(false);
const isResetSubmitting = ref(false);
const resetCodeSent = ref(false);
const resetEmailAddress = ref('');
const resetCodeText = ref('');
const resetPasswordText = ref('');
const resetPasswordConfirmText = ref('');
const resetPasswordError = ref('');
const resetPasswordMessage = ref('');
const resetSignIn = ref(null);

onMounted(() => {
  const rememberedEmail = localStorage.getItem(AUTH_STORAGE_KEYS.rememberedLoginEmail);
  if (rememberedEmail) {
    emailAddress.value = rememberedEmail;
    rememberMeChecked.value = true;
  }
});

async function handleLocalLogin() {
  loginError.value = '';
  isSubmitting.value = true;

  try {
    const account = await authStore.performLogin(emailAddress.value, passwordText.value);
    routeAfterBackendLogin(account);
  } catch (error) {
    if (error?.errorType === 'LocalPasswordUnavailable' || error?.errorType === 'AuthenticationFailed') {
      await handleClerkPasswordLogin();
      return;
    }

    if (error?.errorType === 'AccountDisabled') {
      authStore.performLogout();
      router.replace({ name: ROUTE_NAMES.accountDeactivated });
      return;
    }

    loginError.value = error?.message || 'Invalid email address or password.';
  } finally {
    isSubmitting.value = false;
  }
}

async function handleClerkPasswordLogin() {
  const clerk = await waitForClerk();

  if (!clerk?.client?.signIn || !clerk?.setActive) {
    loginError.value = 'Clerk authentication is still loading. Please try again.';
    return;
  }

  try {
    const clerkSignIn = await clerk.client.signIn.create({
      identifier: emailAddress.value,
      password: passwordText.value,
      strategy: 'password',
    });

    if (clerkSignIn.status !== 'complete' || !clerkSignIn.createdSessionId) {
      loginError.value = 'This account needs additional Clerk verification before sign-in can continue.';
      return;
    }

    const preflightResult = await verifyClerkLoginAllowed();
    if (!preflightResult.success) {
      loginError.value = preflightResult.error || 'Please wait for an administrator invitation before signing in.';
      return;
    }

    rememberLoginEmailPreference();
    await clerk.setActive({ session: clerkSignIn.createdSessionId });
    router.replace({ name: ROUTE_NAMES.postLogin });
  } catch (error) {
    loginError.value = resolveClerkErrorMessage(error);
  }
}

async function verifyClerkLoginAllowed() {
  try {
    const response = await fetch(apiUrl('/api/v1/auth/clerk-login-preflight'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ emailAddress: emailAddress.value }),
    });
    const result = await response.json().catch(() => ({}));

    if (!response.ok) {
      return {
        success: false,
        errorType: result.errorType || result.type || '',
        error: result.errorMessage || result.message || 'Please wait for an administrator invitation before signing in.',
      };
    }

    return { success: true, data: result.data ?? result };
  } catch (error) {
    return {
      success: false,
      error: error?.message || 'Unable to verify invitation status. Please try again.',
    };
  }
}

function waitForClerk(timeoutMs = 4000) {
  if (window.Clerk?.loaded) {
    return Promise.resolve(window.Clerk);
  }

  return new Promise((resolve) => {
    const startedAt = Date.now();
    const timer = window.setInterval(() => {
      if (window.Clerk?.loaded) {
        window.clearInterval(timer);
        resolve(window.Clerk);
        return;
      }

      if (Date.now() - startedAt >= timeoutMs) {
        window.clearInterval(timer);
        resolve(window.Clerk || null);
      }
    }, 100);
  });
}

function routeAfterBackendLogin(account) {
  const role = String(account?.roleDesignation || '').toUpperCase();

  rememberLoginEmailPreference();

  if (role === 'ROLE_ADMIN' || role === 'ADMIN') {
    router.replace({ name: ROUTE_NAMES.adminDashboard });
    return;
  }

  router.replace({ name: ROUTE_NAMES.borrowerMyReservations });
}

function rememberLoginEmailPreference() {
  if (rememberMeChecked.value) {
    localStorage.setItem(AUTH_STORAGE_KEYS.rememberedLoginEmail, emailAddress.value);
  } else {
    localStorage.removeItem(AUTH_STORAGE_KEYS.rememberedLoginEmail);
  }
}

function resolveClerkErrorMessage(error) {
  const clerkError = error?.errors?.[0];
  if (clerkError?.longMessage) return clerkError.longMessage;
  if (clerkError?.message) return clerkError.message;
  return error?.message || 'Invalid email address or password.';
}

function showResetPasswordForm() {
  loginError.value = '';
  resetEmailAddress.value = emailAddress.value;
  resetCodeText.value = '';
  resetPasswordText.value = '';
  resetPasswordConfirmText.value = '';
  resetPasswordError.value = '';
  resetPasswordMessage.value = '';
  resetCodeSent.value = false;
  resetSignIn.value = null;
  isResettingPassword.value = true;
}

function hideResetPasswordForm() {
  isResettingPassword.value = false;
  isResetSubmitting.value = false;
  resetCodeSent.value = false;
  resetPasswordError.value = '';
  resetPasswordMessage.value = '';
}

function resolveResetPasswordButtonText() {
  if (isResetSubmitting.value) return resetCodeSent.value ? 'Resetting...' : 'Sending code...';
  return resetCodeSent.value ? 'Reset password' : 'Send reset code';
}

async function handleResetPasswordSubmit() {
  resetPasswordError.value = '';
  resetPasswordMessage.value = '';
  isResetSubmitting.value = true;

  try {
    if (!resetCodeSent.value) {
      await sendResetPasswordCode();
      return;
    }

    await submitResetPassword();
  } finally {
    isResetSubmitting.value = false;
  }
}

async function sendResetPasswordCode() {
  try {
    const clerk = await waitForClerk();
    if (!clerk?.client?.signIn) {
      resetPasswordError.value = 'Clerk authentication is still loading. Please try again.';
      return;
    }

    let clerkSignIn = null;
    try {
      clerkSignIn = await clerk.client.signIn.create({
        identifier: resetEmailAddress.value,
      });

      if (clerkSignIn?.resetPasswordEmailCode?.sendCode) {
        clerkSignIn = await clerkSignIn.resetPasswordEmailCode.sendCode();
      } else {
        clerkSignIn = await clerk.client.signIn.create({
          identifier: resetEmailAddress.value,
          strategy: 'reset_password_email_code',
        });
      }
    } catch (error) {
      resetPasswordError.value = resolveClerkErrorMessage(error);
      return;
    }

    resetSignIn.value = clerkSignIn;
    resetCodeSent.value = true;
    resetPasswordMessage.value = 'Enter the code Clerk sent to your email, then choose a new password.';
  } catch (error) {
    resetPasswordError.value = resolveClerkErrorMessage(error);
  }
}

async function submitResetPassword() {
  if (resetPasswordText.value !== resetPasswordConfirmText.value) {
    resetPasswordError.value = 'New passwords do not match.';
    return;
  }

  if (!isStrongPassword(resetPasswordText.value)) {
    resetPasswordError.value = 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.';
    return;
  }

  try {
    const clerk = await waitForClerk();
    if (!clerk?.client?.signIn || !clerk?.setActive) {
      resetPasswordError.value = 'Clerk authentication is still loading. Please try again.';
      return;
    }

    let clerkSignIn = resetSignIn.value;
    if (!clerkSignIn?.attemptFirstFactor && !clerkSignIn?.resetPasswordEmailCode) {
      clerkSignIn = await clerk.client.signIn.create({
        identifier: resetEmailAddress.value,
        strategy: 'reset_password_email_code',
      });
    }

    let result = null;
    if (clerkSignIn?.resetPasswordEmailCode?.verifyCode) {
      result = await clerkSignIn.resetPasswordEmailCode.verifyCode({ code: resetCodeText.value });
      if (result?.status === 'needs_new_password' && result?.resetPasswordEmailCode?.submitPassword) {
        result = await result.resetPasswordEmailCode.submitPassword({
          password: resetPasswordText.value,
          signOutOfOtherSessions: true,
        });
      }
    } else {
      result = await clerkSignIn.attemptFirstFactor({
        strategy: 'reset_password_email_code',
        code: resetCodeText.value,
        password: resetPasswordText.value,
      });
    }

    if (result?.status === 'needs_second_factor') {
      resetPasswordError.value = 'Two-factor authentication is required, but this reset form does not handle it yet.';
      return;
    }

    if (result?.status !== 'complete' || !result?.createdSessionId) {
      resetPasswordError.value = 'Password reset is not complete. Please check the code and try again.';
      return;
    }

    await clerk.setActive({ session: result.createdSessionId });
    await syncPostgresPasswordFromClerk(resetPasswordText.value);
    router.replace({ name: ROUTE_NAMES.postLogin });
  } catch (error) {
    resetPasswordError.value = resolveClerkErrorMessage(error);
  }
}

async function syncPostgresPasswordFromClerk(newPassword) {
  const clerk = await waitForClerk();
  const token = await clerk?.session?.getToken?.();

  if (!token) {
    throw new Error('Password was reset in Clerk, but the system could not sync the local database session.');
  }

  const response = await fetch(apiUrl('/api/v1/accounts/me/password/sync-from-clerk'), {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify({ newPassword }),
  });

  if (!response.ok) {
    const result = await response.json().catch(() => ({}));
    throw new Error(result.errorMessage || 'Password was reset in Clerk, but could not be synced to the local database.');
  }
}

function isStrongPassword(value) {
  return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(value);
}

</script>

<style scoped>
.clerk-login-page {
  display: flex;
  min-height: 100vh;
  width: 100%;
  overflow: hidden;
  background: #f4f6f3;
  font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
}

.clerk-login-branding-panel {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 54%;
  min-height: 100vh;
  background: #064b33;
  overflow: hidden;
}

.clerk-login-branding-image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  opacity: 0.24;
  z-index: 0;
}

.clerk-login-branding-panel::before {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 1;
  background:
    linear-gradient(135deg, rgba(3, 70, 47, 0.95), rgba(8, 120, 74, 0.82)),
    linear-gradient(0deg, rgba(0, 0, 0, 0.28), transparent 55%);
}

.clerk-login-branding-panel::after {
  content: '';
  position: absolute;
  right: 0;
  top: 0;
  width: 18%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.18));
  z-index: 2;
}

.clerk-login-branding-content {
  position: relative;
  z-index: 3;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  width: min(78%, 560px);
  min-height: 72vh;
  color: #ffffff;
}

.clerk-login-brand-mark {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.clerk-login-logo {
  width: 82px;
  height: 82px;
  object-fit: contain;
  filter: drop-shadow(0 10px 18px rgba(0, 0, 0, 0.28));
}

.clerk-login-brand-title {
  margin: 0;
  font-size: clamp(2.15rem, 4vw, 3.85rem);
  font-weight: 900;
  line-height: 1;
}

.clerk-login-brand-tech,
.clerk-login-brand-reserve {
  color: #ffffff;
}

.clerk-login-brand-reserve {
  color: #f5c542;
}

.clerk-login-brand-copy {
  max-width: 520px;
}

.clerk-login-kicker {
  margin: 0 0 0.75rem;
  color: #c9f7de;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.clerk-login-brand-subtitle {
  max-width: 520px;
  margin: 0;
  color: #ffffff;
  font-size: clamp(2rem, 4.1vw, 4.35rem);
  font-weight: 900;
  line-height: 0.98;
}

.clerk-login-brand-description {
  max-width: 440px;
  margin: 1.4rem 0 0;
  color: rgba(255, 255, 255, 0.82);
  font-size: 0.98rem;
  line-height: 1.65;
}

.clerk-login-form-panel {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 46%;
  min-height: 100vh;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.82), rgba(244, 247, 244, 0.96)),
    #f7f9f7;
  overflow: hidden;
  padding: 2rem;
}

.clerk-login-form-content {
  position: relative;
  z-index: 1;
  width: min(100%, 430px);
}

.clerk-login-card {
  width: 100%;
  padding: 1.75rem 1.2rem 1.15rem;
  border: 1px solid rgba(17, 24, 39, 0.07);
  border-radius: 16px;
  background: #ffffff;
  box-shadow: 0 22px 54px rgba(17, 24, 39, 0.11);
}

.clerk-login-page-footer {
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

.techreserve-local-login-form {
  display: flex;
  flex-direction: column;
  width: 100%;
  gap: 1rem;
}

.techreserve-local-login-title {
  margin: 0 0 0.3rem;
  color: #111827;
  font-size: 1.28rem;
  font-weight: 900;
  line-height: 1.25;
  text-align: center;
}

.techreserve-local-login-error {
  margin: 0;
  padding: 0.7rem 0.8rem;
  border: 1px solid #f3b5b5;
  border-radius: 10px;
  background: #fff4f4;
  color: #9f1d1d;
  font-size: 0.82rem;
  font-weight: 700;
  line-height: 1.35;
}

.techreserve-local-login-field {
  display: flex;
  flex-direction: column;
  gap: 0.38rem;
  color: #374151;
  font-size: 0.78rem;
  font-weight: 800;
}

.techreserve-local-login-field input {
  min-height: 40px;
  width: 100%;
  border: 1px solid #d7ded9;
  border-radius: 10px;
  background: #ffffff;
  color: #111827;
  box-shadow: none;
  font-size: 0.92rem;
  font-weight: 500;
  padding: 0 0.8rem;
}

.techreserve-local-login-field input:focus {
  border-color: #08784a;
  box-shadow: 0 0 0 3px rgba(8, 120, 74, 0.12);
  outline: none;
}

.techreserve-local-login-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.8rem;
  margin-top: -0.15rem;
}

.techreserve-local-login-options-single {
  justify-content: flex-start;
}

.techreserve-local-login-remember {
  display: inline-flex;
  align-items: center;
  gap: 0.42rem;
  color: #4b5563;
  cursor: pointer;
  font-size: 0.78rem;
  font-weight: 700;
  line-height: 1;
}

.techreserve-local-login-remember input {
  width: 14px;
  height: 14px;
  margin: 0;
  accent-color: #08784a;
}

.techreserve-local-login-link {
  padding: 0;
  border: 0;
  background: transparent;
  color: #08784a;
  cursor: pointer;
  font-size: 0.78rem;
  font-weight: 800;
}

.techreserve-local-login-link:hover,
.techreserve-local-login-signup a:hover {
  color: #05613d;
  text-decoration: underline;
}

.techreserve-local-login-info {
  margin: -0.15rem 0 0;
  padding: 0.65rem 0.75rem;
  border: 1px solid #bfe8d2;
  border-radius: 10px;
  background: #f0fbf5;
  color: #07543f;
  font-size: 0.78rem;
  font-weight: 700;
  line-height: 1.35;
}

.techreserve-local-login-button {
  min-height: 42px;
  width: 100%;
  border: 0;
  border-radius: 10px;
  background: #08784a;
  color: #ffffff;
  cursor: pointer;
  font-size: 0.92rem;
  font-weight: 900;
  box-shadow: 0 6px 14px rgba(8, 120, 74, 0.14);
}

.techreserve-local-login-button:hover:not(:disabled) {
  background: #05613d;
}

.techreserve-local-login-button:disabled {
  cursor: not-allowed;
  opacity: 0.62;
}

.techreserve-local-login-secondary-button {
  min-height: 40px;
  width: 100%;
  border: 1px solid #cfd8d3;
  border-radius: 10px;
  background: #ffffff;
  color: #374151;
  cursor: pointer;
  font-size: 0.88rem;
  font-weight: 850;
}

.techreserve-local-login-secondary-button:hover:not(:disabled) {
  border-color: #08784a;
  color: #08784a;
}

.techreserve-local-login-secondary-button:disabled {
  cursor: not-allowed;
  opacity: 0.62;
}

.techreserve-local-login-signup {
  margin: -0.1rem 0 0;
  color: #6b7280;
  text-align: center;
  font-size: 0.82rem;
  font-weight: 650;
}

.techreserve-local-login-signup a {
  color: #08784a;
  font-weight: 900;
  text-decoration: none;
}

:deep(.techreserve-clerk-root) {
  display: flex;
  justify-content: center;
  width: 100%;
}

:deep(.techreserve-clerk-card-box) {
  width: 100%;
  max-width: 100%;
  box-shadow: none;
}

:deep(.techreserve-clerk-card) {
  width: 100%;
  border: 0;
  border-radius: 0;
  background: transparent;
  box-shadow: none;
  padding: 0;
}

:deep(.techreserve-clerk-header-title) {
  color: transparent;
  font-size: 0;
  line-height: 1;
  text-align: center;
  margin-bottom: 1.2rem;
}

:deep(.techreserve-clerk-header-title)::before {
  content: 'Welcome to TechReserve';
  display: block;
  color: #111827;
  font-size: 1.28rem;
  font-weight: 900;
  line-height: 1.25;
}

:deep(.techreserve-clerk-header-subtitle) {
  display: none;
}

:deep(.techreserve-clerk-form-field) {
  gap: 0.34rem;
  margin-bottom: 0.85rem;
}

:deep(.techreserve-clerk-field-label) {
  color: #374151;
  font-size: 0.78rem;
  font-weight: 800;
}

:deep(.techreserve-clerk-field-input) {
  min-height: 38px;
  border: 1px solid #d7ded9;
  border-radius: 10px;
  background: #ffffff;
  color: #111827;
  box-shadow: none;
  font-size: 0.9rem;
  font-weight: 500;
}

:deep(.techreserve-clerk-field-input:focus) {
  border-color: #08784a;
  box-shadow: 0 0 0 3px rgba(8, 120, 74, 0.12);
}

:deep(.techreserve-clerk-primary-button) {
  min-height: 40px;
  border-radius: 10px;
  background: #08784a;
  font-weight: 900;
  box-shadow: 0 6px 14px rgba(8, 120, 74, 0.14);
}

:deep(.techreserve-clerk-primary-button:hover) {
  background: #05613d;
}

:deep(.techreserve-clerk-social-block),
:deep(.techreserve-clerk-social-button),
:deep(.techreserve-clerk-divider-row) {
  display: none;
}

:deep(.techreserve-clerk-footer) {
  border-top: 1px solid #edf0ed;
  background: #ffffff;
  opacity: 0.78;
}

:deep(.techreserve-clerk-footer-link) {
  color: #08784a;
  font-weight: 900;
}

@media (max-width: 1024px) {
  .clerk-login-branding-panel,
  .clerk-login-form-panel {
    width: 50%;
  }

  .clerk-login-form-content {
    max-width: 420px;
  }

  .clerk-login-page-footer {
    bottom: 1.35rem;
  }
}

@media (max-width: 768px) {
  .clerk-login-page {
    flex-direction: column;
    overflow: auto;
  }

  .clerk-login-branding-panel {
    width: 100%;
    min-height: 38vh;
    padding: 2rem 1.25rem;
  }

  .clerk-login-branding-panel::after {
    display: none;
  }

  .clerk-login-branding-content {
    width: min(100%, 520px);
    min-height: auto;
    gap: 2rem;
  }

  .clerk-login-logo {
    width: 62px;
    height: 62px;
  }

  .clerk-login-brand-title {
    font-size: 2rem;
  }

  .clerk-login-brand-subtitle {
    font-size: 1.85rem;
  }

  .clerk-login-brand-description {
    margin-top: 0.85rem;
    font-size: 0.9rem;
  }

  .clerk-login-form-panel {
    width: 100%;
    min-height: 62vh;
    padding: 1.25rem 1rem 4rem;
  }

  .clerk-login-card {
    padding: 1.35rem 1rem 1rem;
    border-radius: 14px;
  }

  .clerk-login-page-footer {
    bottom: 1.5rem;
  }
}
</style>
