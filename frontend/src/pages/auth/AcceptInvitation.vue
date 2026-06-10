<template>
  <div class="accept-invitation-page">
    <section class="accept-invitation-brand-panel">
      <img
        src="@/assets/Page-20-3.png"
        alt=""
        class="accept-invitation-brand-image"
      />
      <div class="accept-invitation-brand-copy">
        <img
          src="@/assets/TechReserve_LogoB.png"
          alt="TechReserve Logo"
          class="accept-invitation-logo"
        />
        <p class="accept-invitation-kicker">Clerk Invitation Access</p>
        <h1>Finish your TechReserve invitation inside the system.</h1>
        <p>
          Use the invitation link from your email to activate your Clerk account,
          sign in automatically, and continue to your TechReserve dashboard.
        </p>
      </div>
    </section>

    <section class="accept-invitation-form-panel">
      <div class="accept-invitation-card">
        <div class="accept-invitation-header">
          <h2>{{ activeMode === 'sign-up' ? 'Accept Invitation' : 'Sign In' }}</h2>
          <p>
            {{ hasInvitationContext
              ? 'Complete the Clerk invitation to create your active TechReserve session.'
              : 'If you already accepted the invitation, sign in here to continue.' }}
          </p>
        </div>

        <div class="accept-invitation-toggle">
          <button
            type="button"
            class="accept-invitation-toggle-button"
            :class="{ 'accept-invitation-toggle-button--active': activeMode === 'sign-up' }"
            @click="activeMode = 'sign-up'"
          >
            Accept Invitation
          </button>
          <button
            type="button"
            class="accept-invitation-toggle-button"
            :class="{ 'accept-invitation-toggle-button--active': activeMode === 'sign-in' }"
            @click="activeMode = 'sign-in'"
          >
            Already Registered
          </button>
        </div>

        <p v-if="routeError" class="accept-invitation-error">{{ routeError }}</p>
        <p v-else-if="!hasInvitationContext" class="accept-invitation-helper">
          The invitation query was not detected in this URL. Open the exact Clerk email link,
          or sign in if your invitation was already completed.
        </p>

        <div class="accept-invitation-widget">
          <SignUp
            v-if="activeMode === 'sign-up'"
            path="/accept-invitation"
            routing="path"
            sign-in-url="/accept-invitation"
            :force-redirect-url="redirects.signUpForceRedirectUrl"
            :fallback-redirect-url="redirects.signUpFallbackRedirectUrl"
            :appearance="clerkAppearance"
          />
          <SignIn
            v-else
            path="/accept-invitation"
            routing="path"
            sign-up-url="/accept-invitation"
            :force-redirect-url="redirects.signInForceRedirectUrl"
            :fallback-redirect-url="redirects.signInFallbackRedirectUrl"
            :appearance="clerkAppearance"
          />
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { SignIn, SignUp, useUser } from '@clerk/vue'
import { ROUTE_NAMES } from '@/router/routeNames.js'
import { resolveClerkRedirectOptions } from '@/modules/authentication/utils/clerkRedirects.js'

const route = useRoute()
const router = useRouter()
const { isLoaded, isSignedIn } = useUser()

const redirects = resolveClerkRedirectOptions()
const routeError = computed(() => String(route.query.error || '').trim())
const hasInvitationContext = computed(() => {
  const queryKeys = Object.keys(route.query || {})
  if (queryKeys.some((key) => /clerk|ticket|invitation|redirect/i.test(key))) {
    return true
  }

  return /(__clerk|ticket|invitation)/i.test(String(route.hash || ''))
})
const activeMode = ref(hasInvitationContext.value ? 'sign-up' : 'sign-in')

watch(hasInvitationContext, (nextValue) => {
  activeMode.value = nextValue ? 'sign-up' : 'sign-in'
}, { immediate: false })

watch([isLoaded, isSignedIn], ([loaded, signedIn]) => {
  if (!loaded || !signedIn) {
    return
  }

  router.replace({ name: ROUTE_NAMES.postLogin })
}, { immediate: true })

const clerkAppearance = {
  elements: {
    rootBox: 'techreserve-clerk-root',
    cardBox: 'techreserve-clerk-card-box',
    card: 'techreserve-clerk-card',
    headerTitle: 'techreserve-clerk-header-title',
    headerSubtitle: 'techreserve-clerk-header-subtitle',
    formFieldRow: 'techreserve-clerk-form-field',
    formFieldLabel: 'techreserve-clerk-field-label',
    formFieldInput: 'techreserve-clerk-field-input',
    footer: 'techreserve-clerk-footer',
    footerActionLink: 'techreserve-clerk-footer-link',
    formButtonPrimary: 'techreserve-clerk-primary-button',
    socialButtonsBlockButton: 'techreserve-clerk-social-button',
    dividerRow: 'techreserve-clerk-divider-row',
  },
}
</script>

<style scoped>
.accept-invitation-page {
  display: flex;
  min-height: 100vh;
  background: #f4f6f3;
}

.accept-invitation-brand-panel,
.accept-invitation-form-panel {
  width: 50%;
}

.accept-invitation-brand-panel {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background: #064b33;
}

.accept-invitation-brand-image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  opacity: 0.22;
}

.accept-invitation-brand-panel::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(145deg, rgba(4, 78, 49, 0.94), rgba(7, 120, 74, 0.82));
}

.accept-invitation-brand-copy {
  position: relative;
  z-index: 1;
  width: min(78%, 560px);
  color: #ffffff;
}

.accept-invitation-logo {
  width: 84px;
  height: 84px;
  object-fit: contain;
}

.accept-invitation-kicker {
  margin: 1.2rem 0 0.75rem;
  color: #c9f7de;
  font-size: 0.8rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.accept-invitation-brand-copy h1 {
  margin: 0;
  font-size: clamp(2rem, 4vw, 3.9rem);
  line-height: 0.98;
}

.accept-invitation-brand-copy p:last-child {
  max-width: 480px;
  margin-top: 1rem;
  color: rgba(255, 255, 255, 0.84);
  font-size: 0.98rem;
  line-height: 1.65;
}

.accept-invitation-form-panel {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.accept-invitation-card {
  width: min(100%, 460px);
  padding: 1.5rem;
  border: 1px solid rgba(17, 24, 39, 0.07);
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 24px 60px rgba(17, 24, 39, 0.11);
}

.accept-invitation-header h2 {
  margin: 0;
  color: #111827;
  font-size: 1.35rem;
  font-weight: 900;
}

.accept-invitation-header p,
.accept-invitation-helper {
  margin: 0.55rem 0 0;
  color: #5f6b66;
  font-size: 0.9rem;
  line-height: 1.55;
}

.accept-invitation-toggle {
  display: flex;
  gap: 0.65rem;
  margin: 1.25rem 0 1rem;
}

.accept-invitation-toggle-button {
  flex: 1;
  min-height: 40px;
  border: 1px solid #d7ded9;
  border-radius: 999px;
  background: #ffffff;
  color: #4b5563;
  font-size: 0.86rem;
  font-weight: 800;
  cursor: pointer;
}

.accept-invitation-toggle-button--active {
  border-color: #08784a;
  background: #08784a;
  color: #ffffff;
}

.accept-invitation-error {
  margin: 0 0 1rem;
  padding: 0.75rem 0.8rem;
  border: 1px solid #f3b5b5;
  border-radius: 10px;
  background: #fff4f4;
  color: #9f1d1d;
  font-size: 0.82rem;
  font-weight: 700;
}

.accept-invitation-widget {
  margin-top: 1rem;
}

:deep(.techreserve-clerk-root) {
  display: flex;
  justify-content: center;
  width: 100%;
}

:deep(.techreserve-clerk-card-box) {
  width: 100%;
}

:deep(.techreserve-clerk-card) {
  width: 100%;
  border: 0;
  background: transparent;
  box-shadow: none;
  padding: 0;
}

:deep(.techreserve-clerk-header-title) {
  color: #111827;
  font-size: 1.12rem;
  font-weight: 900;
}

:deep(.techreserve-clerk-header-subtitle) {
  color: #5f6b66;
}

:deep(.techreserve-clerk-form-field) {
  margin-bottom: 0.9rem;
}

:deep(.techreserve-clerk-field-label) {
  color: #374151;
  font-size: 0.78rem;
  font-weight: 800;
}

:deep(.techreserve-clerk-field-input) {
  min-height: 40px;
  border: 1px solid #d7ded9;
  border-radius: 10px;
  background: #ffffff;
  color: #111827;
  box-shadow: none;
}

:deep(.techreserve-clerk-field-input:focus) {
  border-color: #08784a;
  box-shadow: 0 0 0 3px rgba(8, 120, 74, 0.12);
}

:deep(.techreserve-clerk-primary-button) {
  min-height: 42px;
  border-radius: 10px;
  background: #08784a;
  font-weight: 900;
}

:deep(.techreserve-clerk-primary-button:hover) {
  background: #05613d;
}

:deep(.techreserve-clerk-footer-link) {
  color: #08784a;
  font-weight: 900;
}

@media (max-width: 900px) {
  .accept-invitation-page {
    flex-direction: column;
  }

  .accept-invitation-brand-panel,
  .accept-invitation-form-panel {
    width: 100%;
  }

  .accept-invitation-brand-panel {
    min-height: 36vh;
    padding: 2rem 1.25rem;
  }

  .accept-invitation-form-panel {
    min-height: 64vh;
    padding: 1.25rem 1rem 2rem;
  }

  .accept-invitation-card {
    padding: 1.2rem 1rem;
  }
}
</style>
