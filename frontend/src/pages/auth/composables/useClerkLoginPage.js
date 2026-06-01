import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { AUTH_STORAGE_KEYS } from '@/modules/authentication/utils/authStorage.js';
import { apiUrl } from '@/shared/utils/apiBase.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';

export function useClerkLoginPage() {
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

  function routeAfterBackendLogin(account) {
    const role = String(account?.roleDesignation || '').toUpperCase();
    rememberLoginEmailPreference();
    router.replace({ name: role === 'ROLE_ADMIN' || role === 'ADMIN' ? ROUTE_NAMES.adminDashboard : ROUTE_NAMES.borrowerMyReservations });
  }

  function rememberLoginEmailPreference() {
    if (rememberMeChecked.value) {
      localStorage.setItem(AUTH_STORAGE_KEYS.rememberedLoginEmail, emailAddress.value);
    } else {
      localStorage.removeItem(AUTH_STORAGE_KEYS.rememberedLoginEmail);
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
  if (clerkError?.longMessage) return clerkError.longMessage;
  if (clerkError?.message) return clerkError.message;
  return error?.message || 'Invalid email address or password.';
}

function isStrongPassword(value) {
  return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(value);
}
