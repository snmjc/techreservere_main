import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { verifyClerkLoginAccess } from '@/modules/authentication/services/clerkLoginAccessService.js';
import {
  clearRememberedLoginEmail,
  persistPendingRememberSession,
  readRememberedLoginEmail,
  writeRememberedLoginEmail,
} from '@/modules/authentication/utils/authStorage.js';
import { apiUrl } from '@/shared/utils/apiBase.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';

export function useClerkLoginPage() {
  const router = useRouter();
  const route = useRoute();
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
    const rememberedEmail = readRememberedLoginEmail();
    if (rememberedEmail) {
      emailAddress.value = rememberedEmail;
      rememberMeChecked.value = true;
    }

    const redirectError = String(route.query.error || '').trim();
    if (redirectError !== '') {
      loginError.value = redirectError;
      router.replace({ name: ROUTE_NAMES.clerkLogin });
    }
  });

  async function handleLocalLogin() {
    loginError.value = '';
    isSubmitting.value = true;

    try {
      const emailValidationError = validateInstitutionalLoginEmail(emailAddress.value);
      if (emailValidationError) {
        loginError.value = emailValidationError;
        return;
      }

      if (shouldPreferClerkOnCurrentHost()) {
        await handlePreferredClerkLogin();
        return;
      }

      await handleBackendLogin();
    } finally {
      isSubmitting.value = false;
    }
  }

  async function handleBackendLogin() {
    try {
      const account = await authStore.performLogin(emailAddress.value, passwordText.value, {
        rememberSession: rememberMeChecked.value,
      });
      routeAfterBackendLogin(account);
    } catch (error) {
      // Clerk-backed accounts can appear as invitation-pending before preflight sync
      // updates the local account row, so allow those cases into the Clerk path too.
      if (shouldAttemptClerkPasswordLogin(error)) {
        await handleClerkPasswordLogin();
        return;
      }

      if (shouldAttemptClerkAfterBackendAuthenticationFailure(error)) {
        try {
          await handleClerkPasswordLogin(null, {
            skipPreflight: shouldBypassClerkPreflightOnCurrentHost(),
          });
          return;
        } catch (clerkError) {
          if (!shouldFallbackToBackendAfterClerkFailure(clerkError)) {
            loginError.value = resolveClerkErrorMessage(clerkError);
            return;
          }
        }
      }

      if (error?.errorType === 'AccountDisabled') {
        authStore.performLogout();
        router.replace({ name: ROUTE_NAMES.accountDeactivated });
        return;
      }

      loginError.value = error?.message || 'Invalid email address or password.';
    } finally {
    }
  }

  async function handlePreferredClerkLogin() {
    if (shouldBypassClerkPreflightOnCurrentHost()) {
      try {
        const account = await authStore.performLogin(emailAddress.value, passwordText.value, {
          rememberSession: rememberMeChecked.value,
        });
        routeAfterBackendLogin(account);
        return;
      } catch (error) {
        if (shouldAttemptClerkPasswordLogin(error)) {
          try {
            await handleClerkPasswordLogin(null, { skipPreflight: true });
            return;
          } catch (clerkError) {
            loginError.value = resolveClerkErrorMessage(clerkError);
            return;
          }
        }

        loginError.value = resolveClerkErrorMessage(error);
        return;
      }
    }

    const preflight = await verifyClerkLoginAccess(emailAddress.value);

    if (preflight.success) {
      try {
        await handleClerkPasswordLogin(preflight);
        return;
      } catch (error) {
        if (!shouldFallbackToBackendAfterClerkFailure(error)) {
          loginError.value = resolveClerkErrorMessage(error);
          return;
        }
      }
    }

    if (preflight.errorType && preflight.errorType !== 'AccountPendingInvitation') {
      loginError.value = preflight.error || 'Please wait for an administrator invitation before signing in.';
      return;
    }

    await handleBackendLogin();
  }

  async function handleClerkPasswordLogin(preflightResult = null, options = {}) {
    const skipPreflight = options.skipPreflight === true;
    const preflight = skipPreflight
      ? { success: true }
      : (preflightResult ?? await verifyClerkLoginAccess(emailAddress.value));

    if (!skipPreflight && !preflight.success) {
      throw Object.assign(new Error(preflight.error || 'Please wait for an administrator invitation before signing in.'), {
        preflight,
      });
    }

    const clerk = await waitForClerk();

    if (!clerk?.client?.signIn || !clerk?.setActive) {
      throw new Error('Clerk authentication is still loading. Please try again.');
    }

    const clerkSignIn = await clerk.client.signIn.create({
      identifier: emailAddress.value,
    });

    const completedSignIn = await clerkSignIn.attemptFirstFactor({
      strategy: 'password',
      password: passwordText.value,
    });

    if (completedSignIn.status !== 'complete' || !completedSignIn.createdSessionId) {
      throw new Error('This account needs additional Clerk verification before sign-in can continue.');
    }

    rememberLoginEmailPreference();
    persistPendingRememberSession(rememberMeChecked.value);
    await clerk.setActive({ session: completedSignIn.createdSessionId });
    router.replace({ name: ROUTE_NAMES.postLogin });
  }

  function routeAfterBackendLogin(account) {
    const role = String(account?.roleDesignation || '').toUpperCase();
    rememberLoginEmailPreference();
    router.replace({ name: getLandingRouteName(role) });
  }

  function rememberLoginEmailPreference() {
    if (rememberMeChecked.value) {
      writeRememberedLoginEmail(emailAddress.value);
    } else {
      clearRememberedLoginEmail();
    }
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
      const emailValidationError = validateInstitutionalLoginEmail(resetEmailAddress.value);
      if (emailValidationError) {
        resetPasswordError.value = emailValidationError;
        return;
      }

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

      const clerkSignIn = await requestClerkResetCode(clerk);
      resetSignIn.value = clerkSignIn;
      resetCodeSent.value = true;
      resetPasswordMessage.value = 'Enter the code Clerk sent to your email, then choose a new password.';
    } catch (error) {
      resetPasswordError.value = resolveClerkErrorMessage(error);
    }
  }

  async function requestClerkResetCode(clerk) {
    const clerkSignIn = await clerk.client.signIn.create({ identifier: resetEmailAddress.value });

    if (clerkSignIn?.resetPasswordEmailCode?.sendCode) {
      return clerkSignIn.resetPasswordEmailCode.sendCode();
    }

    return clerk.client.signIn.create({
      identifier: resetEmailAddress.value,
      strategy: 'reset_password_email_code',
    });
  }

  async function submitResetPassword() {
    const validationError = validateResetPassword();
    if (validationError) {
      resetPasswordError.value = validationError;
      return;
    }

    try {
      const clerk = await waitForClerk();
      if (!clerk?.client?.signIn || !clerk?.setActive) {
        resetPasswordError.value = 'Clerk authentication is still loading. Please try again.';
        return;
      }

      const result = await completeClerkPasswordReset(clerk);
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

  async function completeClerkPasswordReset(clerk) {
    let clerkSignIn = resetSignIn.value;
    if (!clerkSignIn?.attemptFirstFactor && !clerkSignIn?.resetPasswordEmailCode) {
      clerkSignIn = await clerk.client.signIn.create({
        identifier: resetEmailAddress.value,
        strategy: 'reset_password_email_code',
      });
    }

    if (!clerkSignIn?.resetPasswordEmailCode?.verifyCode) {
      return clerkSignIn.attemptFirstFactor({
        strategy: 'reset_password_email_code',
        code: resetCodeText.value,
        password: resetPasswordText.value,
      });
    }

    const verified = await clerkSignIn.resetPasswordEmailCode.verifyCode({ code: resetCodeText.value });
    if (verified?.status === 'needs_new_password' && verified?.resetPasswordEmailCode?.submitPassword) {
      return verified.resetPasswordEmailCode.submitPassword({
        password: resetPasswordText.value,
        signOutOfOtherSessions: true,
      });
    }

    return verified;
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

  function validateResetPassword() {
    if (resetPasswordText.value !== resetPasswordConfirmText.value) {
      return 'New passwords do not match.';
    }

    if (!isStrongPassword(resetPasswordText.value)) {
      return 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.';
    }

    return '';
  }

  return {
    ROUTE_NAMES,
    emailAddress,
    passwordText,
    rememberMeChecked,
    loginError,
    isSubmitting,
    isResettingPassword,
    isResetSubmitting,
    resetCodeSent,
    resetEmailAddress,
    resetCodeText,
    resetPasswordText,
    resetPasswordConfirmText,
    resetPasswordError,
    resetPasswordMessage,
    handleLocalLogin,
    showResetPasswordForm,
    hideResetPasswordForm,
    resolveResetPasswordButtonText,
    handleResetPasswordSubmit,
  };
}

function getLandingRouteName(role) {
  if (role === 'ROLE_ADMIN' || role === 'ADMIN') return ROUTE_NAMES.adminDashboard;
  if (role === 'ROLE_STAFF' || role === 'STAFF') return ROUTE_NAMES.employeeDashboard;
  return ROUTE_NAMES.dashboard;
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

function resolveClerkErrorMessage(error) {
  const clerkError = error?.errors?.[0];
  if (typeof clerkError?.longMessage === 'string' && clerkError.longMessage.includes('allowed values for parameter strategy')) {
    return 'This account must finish the Clerk invitation or use a valid Clerk password before signing in.';
  }
  if (clerkError?.longMessage) return clerkError.longMessage;
  if (clerkError?.message) return clerkError.message;
  return error?.message || 'Invalid email address or password.';
}

function isStrongPassword(value) {
  return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(value);
}

function validateInstitutionalLoginEmail(value) {
  const normalizedValue = String(value || '').trim();
  if (normalizedValue === '') {
    return 'Email address is required.';
  }

  if (!/^[^\s@]+@(fit|feutech)\.edu\.ph$/i.test(normalizedValue)) {
    return 'Please use a valid @fit.edu.ph or @feutech.edu.ph email address.';
  }

  return '';
}

function shouldAttemptClerkPasswordLogin(error) {
  const errorType = String(error?.errorType || '');
  return errorType === 'LocalPasswordUnavailable'
    || errorType === 'AccountInvitationPending'
    || errorType === 'AccountSyncPending';
}

function shouldAttemptClerkAfterBackendAuthenticationFailure(error) {
  const errorType = String(error?.errorType || '');
  return shouldPreferClerkOnCurrentHost() && errorType === 'AuthenticationFailed';
}

function shouldPreferClerkOnCurrentHost() {
  const configuredKey = String(import.meta.env.VITE_CLERK_PUBLISHABLE_KEY || '').trim();
  const isDevelopmentClerkKey = configuredKey.startsWith('pk_test_');

  if (!isDevelopmentClerkKey || typeof window === 'undefined') {
    return false;
  }

  const host = window.location.hostname;
  return host === 'localhost' || host === '127.0.0.1';
}

function shouldBypassClerkPreflightOnCurrentHost() {
  return shouldPreferClerkOnCurrentHost();
}

function shouldFallbackToBackendAfterClerkFailure(error) {
  const clerkError = error?.errors?.[0];
  const clerkCode = String(clerkError?.code || '').trim().toLowerCase();
  const message = String(clerkError?.longMessage || clerkError?.message || error?.message || '').toLowerCase();

  if (clerkCode === 'form_identifier_not_found') {
    return true;
  }

  return message.includes('identifier not found')
    || message.includes('couldn\'t find your account')
    || message.includes('cannot find')
    || message.includes('no account found');
}
