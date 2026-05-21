<!-- ===== Clerk Login Page ===== -->
<template>
  <div class="clerk-login-page">
    <section class="clerk-login-branding-panel">
      <img
        src="@/assets/Page-20-3.png"
        alt=""
        class="clerk-login-branding-image"
      />
      <div class="clerk-login-branding-content">
        <img
          src="@/assets/TechReserve_LogoB.png"
          alt="TechReserve Logo"
          class="clerk-login-logo"
        />

        <h1 class="clerk-login-brand-title">
          <span class="clerk-login-brand-tech">Tech</span><span class="clerk-login-brand-reserve">Reserve</span>
        </h1>

        <h2 class="clerk-login-brand-subtitle">
          Analytics-Driven Equipment Readiness and Reservation System
        </h2>

        <p class="clerk-login-brand-description">
          Supporting efficient equipment coordination and institutional resource planning at
          <strong>FEU Institute of Technology</strong>.
        </p>
      </div>
    </section>

    <section class="clerk-login-form-panel">
      <img
        src="@/assets/FEU_Tech_official_seal.png"
        alt="FEU Tech Seal Watermark"
        class="clerk-login-watermark"
      />

      <div class="clerk-login-form-content">
        <h2 class="clerk-login-heading">Welcome!</h2>

        <SignIn
          path="/clerk-login"
          routing="path"
          :signUpUrl="signUpUrl"
          :forceRedirectUrl="postLoginUrl"
          :fallbackRedirectUrl="postLoginUrl"
          :appearance="clerkAppearance"
        />
      </div>

      <footer class="clerk-login-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </footer>
    </section>
  </div>
</template>

<script setup>
import { watch } from 'vue';
import { useRouter } from 'vue-router';
import { SignIn, useUser, useAuth } from '@clerk/vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { getClerkToken } from '@/modules/authentication/utils/clerkAuthUtils.js';
import { resolveRole } from '@/modules/authentication/utils/roleUtils.js';

const router = useRouter();
const { isLoaded, isSignedIn, user } = useUser();
const { getToken } = useAuth();
const authStore = useAuthenticationStore();

const postLoginUrl = '/auth/post-login';
const signUpUrl = '/custom-signup';
const clerkAppearance = {
  variables: {
    colorPrimary: '#06894f',
    colorText: '#050505',
    colorTextSecondary: '#4b5563',
    colorBackground: '#ffffff',
    borderRadius: '18px',
    fontFamily: '"Inter", "Segoe UI", system-ui, -apple-system, sans-serif',
  },
  elements: {
    rootBox: 'techreserve-clerk-root',
    cardBox: 'techreserve-clerk-card-box',
    card: 'techreserve-clerk-card',
    headerTitle: 'techreserve-clerk-header-title',
    headerSubtitle: 'techreserve-clerk-header-subtitle',
    formButtonPrimary: 'techreserve-clerk-primary-button',
    footerActionLink: 'techreserve-clerk-footer-link',
  },
};

watch([isLoaded, isSignedIn, user], async ([loaded, signedIn, clerkUser]) => {
  if (!loaded) return;

  if (!signedIn) {
    if (authStore.accountData?.authProvider === 'clerk') {
      authStore.performLogout();
    }
    return;
  }

  if (!clerkUser) return;

  let token = null;
  try {
    token = await getClerkToken(getToken);
  } catch (error) {
    console.error('[ClerkLogin] Failed to retrieve Clerk token:', error);
  }

  authStore.setClerkAuth(token, {
    accountIdentifier: clerkUser.id,
    firstName: clerkUser.firstName || '',
    lastName: clerkUser.lastName || '',
    emailAddress: clerkUser.primaryEmailAddress?.emailAddress || '',
    roleDesignation: resolveRole(clerkUser.publicMetadata?.role, clerkUser.primaryEmailAddress?.emailAddress || ''),
    contactNumber: clerkUser.publicMetadata?.contactNumber || '',
    isActive: true,
    authProvider: 'clerk',
  });

  if (authStore.userRole === 'ROLE_ADMIN') {
    router.replace({ name: 'adminDashboardPage' });
  } else {
    router.replace({ name: 'borrowerMyReservationsPage' });
  }
}, { immediate: true });
</script>

<style scoped>
.clerk-login-page {
  display: flex;
  min-height: 100vh;
  width: 100%;
  overflow: hidden;
  background: #efefef;
  font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
}

.clerk-login-branding-panel {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 52%;
  min-height: 100vh;
  background: linear-gradient(135deg, rgba(4, 115, 68, 0.97), rgba(13, 151, 84, 0.94));
  overflow: hidden;
}

.clerk-login-branding-image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  opacity: 0.22;
  z-index: 0;
}

.clerk-login-branding-panel::before {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 1;
  background: linear-gradient(rgba(4, 129, 76, 0.84), rgba(4, 129, 76, 0.84));
}

.clerk-login-branding-panel::after {
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

.clerk-login-branding-content {
  position: relative;
  z-index: 3;
  display: flex;
  flex-direction: column;
  align-items: center;
  width: min(78%, 460px);
  text-align: center;
}

.clerk-login-logo {
  width: clamp(190px, 19vw, 260px);
  height: clamp(190px, 19vw, 260px);
  object-fit: contain;
  margin-bottom: 1.15rem;
  filter: drop-shadow(0 10px 12px rgba(0, 0, 0, 0.34));
}

.clerk-login-brand-title {
  margin: 0 0 2.3rem;
  font-size: clamp(2.6rem, 4.3vw, 4.15rem);
  font-weight: 800;
  letter-spacing: 0.02em;
  line-height: 1.2;
}

.clerk-login-brand-tech {
  color: #ffffff;
}

.clerk-login-brand-reserve {
  color: #ffc21a;
}

.clerk-login-brand-subtitle {
  max-width: 420px;
  margin: 0 0 1.5rem;
  color: #ffffff;
  font-size: clamp(1rem, 1.35vw, 1.25rem);
  font-weight: 800;
  line-height: 1.25;
}

.clerk-login-brand-description {
  max-width: 410px;
  margin: 0;
  color: rgba(255, 255, 255, 0.85);
  font-size: clamp(0.72rem, 0.95vw, 0.9rem);
  line-height: 1.35;
}

.clerk-login-brand-description strong {
  color: #ffffff;
  font-weight: 800;
}

.clerk-login-form-panel {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 48%;
  min-height: 100vh;
  background: #eeeeee;
  overflow: hidden;
}

.clerk-login-watermark {
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

.clerk-login-form-content {
  position: relative;
  z-index: 1;
  width: min(100%, 430px);
  padding: 2rem;
}

.clerk-login-heading {
  margin: 0 0 1.05rem;
  color: #050505;
  font-size: clamp(2rem, 3.2vw, 3rem);
  font-weight: 900;
  line-height: 1;
  text-align: center;
}

.clerk-login-page-footer {
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

:deep(.techreserve-clerk-root) {
  display: flex;
  justify-content: center;
  width: 100%;
}

:deep(.techreserve-clerk-card-box) {
  width: 100%;
  max-width: 390px;
  box-shadow: none;
}

:deep(.techreserve-clerk-card) {
  width: 100%;
  border: 0;
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.72);
  box-shadow: 0 18px 36px rgba(0, 0, 0, 0.08);
}

:deep(.techreserve-clerk-header-title) {
  color: #050505;
  font-weight: 900;
}

:deep(.techreserve-clerk-header-subtitle) {
  color: #4b5563;
  font-weight: 700;
}

:deep(.techreserve-clerk-primary-button) {
  min-height: 42px;
  border-radius: 999px;
  background: #06894f;
  font-weight: 900;
}

:deep(.techreserve-clerk-primary-button:hover) {
  background: #047342;
}

:deep(.techreserve-clerk-footer-link) {
  color: #06894f;
  font-weight: 900;
}

@media (max-width: 1024px) {
  .clerk-login-branding-panel,
  .clerk-login-form-panel {
    width: 50%;
  }

  .clerk-login-form-content {
    max-width: 390px;
    padding: 1.5rem;
  }

  .clerk-login-page-footer {
    bottom: 2.25rem;
  }
}

@media (max-width: 768px) {
  .clerk-login-page {
    flex-direction: column;
    overflow: auto;
  }

  .clerk-login-branding-panel {
    width: 100%;
    min-height: 42vh;
    padding: 2rem 1rem;
  }

  .clerk-login-branding-panel::after {
    display: none;
  }

  .clerk-login-logo {
    width: 110px;
    height: 110px;
    margin-bottom: 0.5rem;
  }

  .clerk-login-brand-title {
    margin-bottom: 0.9rem;
    font-size: 2rem;
  }

  .clerk-login-brand-subtitle {
    margin-bottom: 0.5rem;
    font-size: 0.88rem;
  }

  .clerk-login-brand-description {
    display: none;
  }

  .clerk-login-form-panel {
    width: 100%;
    min-height: 58vh;
    padding: 2rem 1rem 5rem;
  }

  .clerk-login-form-content {
    padding: 0;
  }

  .clerk-login-watermark {
    right: -25%;
    width: 420px;
  }

  .clerk-login-page-footer {
    bottom: 1.5rem;
  }
}
</style>
