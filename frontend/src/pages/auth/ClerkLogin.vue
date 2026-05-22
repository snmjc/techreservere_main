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
          <SignIn
            path="/clerk-login"
            routing="path"
            :signUpUrl="signUpUrl"
            :forceRedirectUrl="postLoginUrl"
            :fallbackRedirectUrl="postLoginUrl"
            :appearance="clerkAppearance"
          />
        </div>
      </div>

      <footer class="clerk-login-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </footer>
    </section>
  </div>
</template>

<script setup>
import { watch, ref } from 'vue';
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
const signUpUrl = '/sign-up';
const clerkAppearance = {
  variables: {
    colorPrimary: '#08784a',
    colorText: '#111827',
    colorTextSecondary: '#6b7280',
    colorBackground: '#ffffff',
    borderRadius: '12px',
    fontFamily: '"Inter", "Segoe UI", system-ui, -apple-system, sans-serif',
  },
  elements: {
    rootBox: 'techreserve-clerk-root',
    cardBox: 'techreserve-clerk-card-box',
    card: 'techreserve-clerk-card',
    headerTitle: 'techreserve-clerk-header-title',
    headerSubtitle: 'techreserve-clerk-header-subtitle',
    formField: 'techreserve-clerk-form-field',
    formFieldLabel: 'techreserve-clerk-field-label',
    formFieldInput: 'techreserve-clerk-field-input',
    formButtonPrimary: 'techreserve-clerk-primary-button',
    footer: 'techreserve-clerk-footer',
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
});
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
  color: #111827;
  font-size: 1.24rem;
  font-weight: 900;
  line-height: 1.2;
  text-align: center;
  margin-bottom: 1.2rem;
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
