<template>
  <div v-if="showInvitationFlow" class="clerk-invitation-page">
    <div v-if="showInvitationLoadingState" class="clerk-invitation-loading clerk-invitation-loading-card">
      <div class="clerk-invitation-spinner" />
      <p>Accepting your invitation and preparing your dashboard...</p>
    </div>

    <p v-else-if="invitationFlowError" class="clerk-invitation-error">
      {{ invitationFlowError }}
    </p>

    <p v-else class="clerk-invitation-error">
      This invitation is still being prepared. Please reopen the original email link in a moment.
    </p>
  </div>

  <div v-else class="clerk-login-page">
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
              <div class="techreserve-local-login-password-control">
                <input
                  v-model="passwordText"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  autocomplete="current-password"
                  required
                />
                <button
                  type="button"
                  class="techreserve-local-login-password-toggle"
                  :aria-label="isPasswordVisible ? 'Hide password' : 'Show password'"
                  :title="isPasswordVisible ? 'Hide password' : 'Show password'"
                  @click="togglePasswordVisibility"
                >
                  <svg
                    v-if="isPasswordVisible"
                    aria-hidden="true"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <path d="M2 2l20 20" />
                    <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83" />
                    <path d="M9.88 4.24A10.9 10.9 0 0 1 12 4c5 0 9 5 10 8a11.8 11.8 0 0 1-2.03 3.17" />
                    <path d="M6.61 6.61A12.2 12.2 0 0 0 2 12c1 3 5 8 10 8a10.8 10.8 0 0 0 5.39-1.61" />
                  </svg>
                  <svg
                    v-else
                    aria-hidden="true"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z" />
                    <circle cx="12" cy="12" r="3" />
                  </svg>
                </button>
              </div>
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
                class="techreserve-local-login-link-button"
                :disabled="isSubmitting"
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
import { useAuth, useUser } from '@clerk/vue';
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useClerkLoginPage } from './composables/useClerkLoginPage.js';
import { signOutClerk } from '@/modules/authentication/utils/clerkAuthUtils.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { persistPendingRememberSession } from '@/modules/authentication/utils/authStorage.js';
import { ROUTE_NAMES } from '@/router/routeNames.js';

const isPasswordVisible = ref(false);
const route = useRoute();
const router = useRouter();
const authStore = useAuthenticationStore();
const { isLoaded, isSignedIn } = useUser();
const { signOut } = useAuth();

const hasTriggeredSignOut = ref(false);
const isSigningOutExistingSession = ref(false);
const wasSignedInOnEntry = ref(null);
const invitationFlowError = ref('');
const isProcessingInvitationTicket = ref(false);

function togglePasswordVisibility() {
  isPasswordVisible.value = !isPasswordVisible.value;
}

const {
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
} = useClerkLoginPage();

const hasInvitationContext = computed(() => {
  const queryKeys = Object.keys(route.query || {});
  if (queryKeys.some((key) => /clerk|ticket|invitation|redirect/i.test(key))) {
    return true;
  }

  return /(__clerk|ticket|invitation)/i.test(String(route.hash || ''));
});

const hasInvitationTicket = computed(() => String(route.query.__clerk_ticket || '').trim() !== '');
const invitationStatus = computed(() => String(route.query.__clerk_status || '').trim().toLowerCase());
const shouldAutoConsumeInvitationTicket = computed(() => (
  hasInvitationTicket.value
  && ['sign_in', 'sign_up'].includes(invitationStatus.value)
));
const showInvitationFlow = computed(() => hasInvitationContext.value && !isResettingPassword.value);
const showInvitationLoadingState = computed(() => !isLoaded.value || isSigningOutExistingSession.value || isProcessingInvitationTicket.value);

watch([isLoaded, isSignedIn, showInvitationFlow, hasInvitationTicket], async ([loaded, signedIn, invitationFlow, hasTicket]) => {
  if (!invitationFlow || !loaded) {
    return;
  }

  if (wasSignedInOnEntry.value === null) {
    wasSignedInOnEntry.value = signedIn;
  }

  if (!hasTicket) {
    invitationFlowError.value = '';
    return;
  }

  if (!signedIn) {
    invitationFlowError.value = '';
    return;
  }

  if (hasTriggeredSignOut.value || wasSignedInOnEntry.value === false) {
    return;
  }

  hasTriggeredSignOut.value = true;
  isSigningOutExistingSession.value = true;

  try {
    authStore.performLogout();
    await signOutClerk(signOut, {
      redirectUrl: `${window.location.origin}${route.fullPath}`,
    });
  } catch (error) {
    console.error('[ClerkLogin] Failed to sign out the existing Clerk session for invitation flow.', error);
    invitationFlowError.value = 'Please sign out of the current account, then reopen the original Clerk invitation link.';
    isSigningOutExistingSession.value = false;
    hasTriggeredSignOut.value = false;
  }
}, { immediate: true });

watch([isLoaded, isSignedIn, showInvitationFlow], ([loaded, signedIn, invitationFlow]) => {
  if (
    !invitationFlow
    || !loaded
    || !signedIn
    || isSigningOutExistingSession.value
    || (wasSignedInOnEntry.value === true && !hasTriggeredSignOut.value)
  ) {
    return;
  }

  router.replace({ name: ROUTE_NAMES.postLogin });
}, { immediate: true });

watch([isLoaded, isSignedIn, showInvitationFlow, shouldAutoConsumeInvitationTicket], async ([loaded, signedIn, invitationFlow, shouldAutoConsume]) => {
  if (!invitationFlow || !loaded || signedIn || !shouldAutoConsume) {
    return;
  }

  if (isSigningOutExistingSession.value || isProcessingInvitationTicket.value) {
    return;
  }

  invitationFlowError.value = '';
  isProcessingInvitationTicket.value = true;

  try {
    const clerk = await waitForClerk();
    if (!clerk?.setActive) {
      throw new Error('Clerk authentication is still loading. Please try the invitation link again.');
    }

    const ticket = String(route.query.__clerk_ticket || '').trim();
    const result = invitationStatus.value === 'sign_up'
      ? await autoCompleteInvitationSignUp(clerk, ticket)
      : await autoCompleteInvitationSignIn(clerk, ticket);

    if (result?.status !== 'complete' || !result?.createdSessionId) {
      throw new Error(
        invitationStatus.value === 'sign_up'
          ? 'This invitation could not be completed automatically.'
          : 'Clerk could not complete this invitation automatically.'
      );
    }

    persistPendingRememberSession(true);
    await clerk.setActive({ session: result.createdSessionId });
    router.replace({ name: ROUTE_NAMES.postLogin });
  } catch (error) {
    console.error('[ClerkLogin] Failed to auto-complete invitation ticket.', error);
    invitationFlowError.value = error?.errors?.[0]?.longMessage
      || error?.errors?.[0]?.message
      || error?.message
      || 'This invitation could not be completed automatically. Please reopen the original invite link.';
  } finally {
    isProcessingInvitationTicket.value = false;
  }
}, { immediate: true });

async function autoCompleteInvitationSignIn(clerk, ticket) {
  if (!clerk?.client?.signIn) {
    throw new Error('Clerk authentication is still loading. Please try the invitation link again.');
  }

  return clerk.client.signIn.create({
    strategy: 'ticket',
    ticket,
  });
}

async function autoCompleteInvitationSignUp(clerk, ticket) {
  if (!clerk?.client?.signUp) {
    throw new Error('Clerk authentication is still loading. Please try the invitation link again.');
  }

  const result = await clerk.client.signUp.create({
    strategy: 'ticket',
    ticket,
    password: buildInvitationPassword(),
  });

  if (result?.status !== 'complete') {
    const missingFieldNames = Array.isArray(result?.missingFields) ? result.missingFields.join(', ') : '';
    throw new Error(
      missingFieldNames
        ? `This invitation still requires Clerk fields: ${missingFieldNames}.`
        : 'This invitation could not be completed automatically.'
    );
  }

  return result;
}

function buildInvitationPassword() {
  const ticket = String(route.query.__clerk_ticket || '').trim();
  const ticketSeed = ticket.slice(0, 12) || 'TechReserve';
  return `Tr!${ticketSeed}9z#Aq7`;
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

</script>

<style scoped>
@import './css/ClerkLogin.css';
</style>

