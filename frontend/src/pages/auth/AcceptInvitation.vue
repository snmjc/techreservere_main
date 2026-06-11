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
        <p class="accept-invitation-kicker">Invitation Access</p>
        <h1>Finish your TechReserve invitation with the invited account.</h1>
        <p>
          If another user is currently signed in, TechReserve will sign that session out first,
          then continue the Clerk invitation flow for the invited account only.
        </p>
      </div>
    </section>

    <section class="accept-invitation-form-panel">
      <div class="accept-invitation-card">
        <div class="accept-invitation-header">
          <h2>{{ cardTitle }}</h2>
          <p>{{ cardDescription }}</p>
        </div>

        <p v-if="statusMessage" class="accept-invitation-helper">{{ statusMessage }}</p>
        <p v-if="errorMessage" class="accept-invitation-error">{{ errorMessage }}</p>

        <div v-if="showLoadingState" class="accept-invitation-loading">
          <div class="accept-invitation-spinner" />
          <span>{{ loadingMessage }}</span>
        </div>

        <div v-else-if="hasInvitationTicket" class="accept-invitation-widget">
          <SignUp
            path="/accept-invitation"
            routing="path"
            sign-in-url="/accept-invitation"
            force-redirect-url="/auth/post-login"
            fallback-redirect-url="/auth/post-login"
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
import { SignUp, useAuth, useUser } from '@clerk/vue'
import { ROUTE_NAMES } from '@/router/routeNames.js'
import { signOutClerk } from '@/modules/authentication/utils/clerkAuthUtils.js'
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js'

const route = useRoute()
const router = useRouter()
const authStore = useAuthenticationStore()
const { isLoaded, isSignedIn } = useUser()
const { signOut } = useAuth()

const isSigningOutExistingSession = ref(false)
const hasTriggeredSignOut = ref(false)
const wasSignedInOnEntry = ref(null)
const errorMessage = ref('')
const statusMessage = ref('')
const loadingMessage = ref('Preparing your invitation...')

const invitationTicket = computed(() => String(route.query.__clerk_ticket || '').trim())
const hasInvitationTicket = computed(() => invitationTicket.value !== '')
const showLoadingState = computed(() => !isLoaded.value || isSigningOutExistingSession.value)
const canonicalInvitationPath = computed(() => '/accept-invitation')
const canonicalInvitationFullPath = computed(() => {
  const searchParams = new URLSearchParams()

  Object.entries(route.query || {}).forEach(([key, value]) => {
    if (Array.isArray(value)) {
      value.forEach((entry) => {
        if (entry !== undefined && entry !== null) {
          searchParams.append(key, String(entry))
        }
      })
      return
    }

    if (value !== undefined && value !== null) {
      searchParams.set(key, String(value))
    }
  })

  const queryString = searchParams.toString()
  return queryString ? `${canonicalInvitationPath.value}?${queryString}` : canonicalInvitationPath.value
})
const cardTitle = computed(() => {
  if (!hasInvitationTicket.value) return 'Invitation Link Missing'
  if (isSigningOutExistingSession.value) return 'Switching Account'
  return 'Accept Invitation'
})
const cardDescription = computed(() => {
  if (!hasInvitationTicket.value) {
    return 'Open the exact Clerk invitation email link so TechReserve can continue the sign-up flow for the invited account.'
  }

  if (isSigningOutExistingSession.value) {
    return 'The current Clerk session is being signed out first so the invited account can finish its own registration.'
  }

  return 'Create the invited account session below. Clerk will keep the invitation email locked to the email address from the invitation.'
})

watch([isLoaded, isSignedIn, hasInvitationTicket], async ([loaded, signedIn, hasTicket]) => {
  if (!loaded) {
    return
  }

  if (wasSignedInOnEntry.value === null) {
    wasSignedInOnEntry.value = signedIn
  }

  if (!hasTicket) {
    errorMessage.value = 'This invitation link is missing the Clerk invitation ticket. Open the original email link and try again.'
    return
  }

  if (!signedIn) {
    statusMessage.value = 'Continue with the invited account below.'
    errorMessage.value = ''
    return
  }

  if (hasTriggeredSignOut.value) {
    return
  }

  if (wasSignedInOnEntry.value === false) {
    return
  }

  hasTriggeredSignOut.value = true
  isSigningOutExistingSession.value = true
  loadingMessage.value = 'Signing out the current account before continuing with the invitation...'
  statusMessage.value = 'The currently signed-in session cannot accept this invitation safely.'
  errorMessage.value = ''

  try {
    authStore.performLogout()
    await signOutClerk(signOut, {
      redirectUrl: `${window.location.origin}${canonicalInvitationFullPath.value}`,
    })
  } catch (error) {
    console.error('[AcceptInvitation] Failed to sign out the existing Clerk session.', error)
    errorMessage.value = 'Unable to sign out the current account automatically. Please sign out and reopen the invitation link.'
    isSigningOutExistingSession.value = false
    hasTriggeredSignOut.value = false
  }
}, { immediate: true })

watch([isLoaded, isSignedIn], ([loaded, signedIn]) => {
  if (
    !loaded
    || !signedIn
    || isSigningOutExistingSession.value
    || (wasSignedInOnEntry.value === true && !hasTriggeredSignOut.value)
  ) {
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

.accept-invitation-error {
  margin: 1rem 0 0;
  padding: 0.75rem 0.8rem;
  border: 1px solid #f3b5b5;
  border-radius: 10px;
  background: #fff4f4;
  color: #9f1d1d;
  font-size: 0.82rem;
  font-weight: 700;
}

.accept-invitation-loading {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 1.2rem;
  color: #08784a;
  font-size: 0.9rem;
  font-weight: 800;
}

.accept-invitation-spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(8, 120, 74, 0.18);
  border-top-color: #08784a;
  border-radius: 50%;
  animation: accept-invitation-spin 0.9s linear infinite;
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

@keyframes accept-invitation-spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
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
