<!-- ===== Clerk Login Page ===== -->
<template>
<<<<<<< HEAD
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
=======
  <div class="login-wrapper">
    <!-- Left Panel - Branding -->
    <div class="left-panel">
      <div class="left-overlay"></div>
      <div class="left-content">
        <img src="@/assets/TechReserve_LogoB.png" alt="TechReserve Logo" class="logo-image" />
        <h1 class="brand-title">
          <span class="tech-text">Tech</span><span class="reserve-text">Reserve</span>
        </h1>
        <h2 class="tagline">Analytics-Driven Equipment Readiness and<br/>Reservation System</h2>
        <p class="description">Supporting efficient equipment coordination and institutional<br/>resource planning at <strong>FEU Institute of Technology</strong>.</p>
>>>>>>> bc882ef93b9a3d481a3bbd1e8f31f6f4ee910779
      </div>
    </section>

<<<<<<< HEAD
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
=======
    <!-- Right Panel - Login Form -->
    <div class="right-panel">
      <!-- FEU Seal Watermark -->
      <img src="@/assets/FEU_Tech_official_seal.png" alt="FEU Tech Seal" class="seal-watermark" />

      <div class="right-content">
        <h2 class="welcome-heading">Welcome!</h2>

        <!-- Clerk Embedded Sign In -->
        <div class="clerk-signin-wrapper">
          <SignIn 
            :appearance="clerkAppearance" 
            sign-up-url="/signup"
            path="/clerk-login"
            routing="path"
          />
        </div>

        <!-- Sign Up Link -->
        <p class="signup-prompt">
          Don't have an account?
          <router-link to="/signup" class="signup-link">Sign up</router-link>
        </p>

        <!-- Footer -->
        <p class="login-footer">© 2026 TECHRESERVE. DATAMS MANAGEMENT.</p>
      </div>
    </div>
>>>>>>> bc882ef93b9a3d481a3bbd1e8f31f6f4ee910779
  </div>
</template>

<script setup>
import { watch, ref } from 'vue';
import { useRouter } from 'vue-router';
<<<<<<< HEAD
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
=======
import { SignIn, useUser } from '@clerk/vue';

const clerkAppearance = {
  elements: {
    footerAction__signIn: { display: 'none' },
  },
};

const router = useRouter();
const { isSignedIn } = useUser();
const hasRedirected = ref(false);

// Simple redirect after sign-in
watch(isSignedIn, (signedIn) => {
  console.log('[ClerkLogin] isSignedIn changed:', signedIn, 'hasRedirected:', hasRedirected.value);
  
  if (signedIn && !hasRedirected.value) {
    hasRedirected.value = true;
    console.log('[ClerkLogin] User signed in, redirecting to dashboard...');
    
    // Use window.location for a hard redirect to avoid router issues
    console.log('[ClerkLogin] Using window.location to redirect...');
    window.location.href = '/borrower/my-reservations';
>>>>>>> bc882ef93b9a3d481a3bbd1e8f31f6f4ee910779
  }
});
</script>

<style scoped>
<<<<<<< HEAD
.clerk-login-page {
=======
* { margin: 0; padding: 0; box-sizing: border-box; }

.login-wrapper {
>>>>>>> bc882ef93b9a3d481a3bbd1e8f31f6f4ee910779
  display: flex;
  min-height: 100vh;
  width: 100%;
  overflow: hidden;
  background: #efefef;
  font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
}

<<<<<<< HEAD
.clerk-login-branding-panel {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 52%;
  min-height: 100vh;
  background: linear-gradient(135deg, rgba(4, 115, 68, 0.97), rgba(13, 151, 84, 0.94));
=======
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
>>>>>>> bc882ef93b9a3d481a3bbd1e8f31f6f4ee910779
  overflow: hidden;
  min-height: 100vh;
}

<<<<<<< HEAD
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
=======
.left-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(160deg, rgba(10, 120, 60, 0.82) 0%, rgba(5, 80, 35, 0.88) 100%);
>>>>>>> bc882ef93b9a3d481a3bbd1e8f31f6f4ee910779
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
<<<<<<< HEAD
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
=======
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

.tech-text  { color: white; }
.reserve-text { color: #f5c518; }

.tagline {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 1rem;
  line-height: 1.4;
  color: white;
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
>>>>>>> bc882ef93b9a3d481a3bbd1e8f31f6f4ee910779
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
<<<<<<< HEAD
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
=======
  padding: 3rem 2.5rem;
  position: relative;
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
>>>>>>> bc882ef93b9a3d481a3bbd1e8f31f6f4ee910779
}

.clerk-login-form-content {
  position: relative;
<<<<<<< HEAD
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
=======
  z-index: 2;
  width: 100%;
  max-width: 360px;
}

.welcome-heading {
  font-size: 2.1rem;
  font-weight: 700;
  color: #111;
  margin-bottom: 1.5rem;
  text-align: center;
}

/* ===== CLERK COMPONENT WRAPPER ===== */
.clerk-signin-wrapper {
  display: flex;
  justify-content: center;
  margin-bottom: 1rem;
}

.loading-text {
  color: #555;
  font-size: 0.9rem;
}

.logged-out-message {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  text-align: center;
  max-width: 360px;
}

.logged-out-message h3 {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
  color: #333;
}

.logged-out-message p {
  color: #666;
  margin-bottom: 1.5rem;
  font-size: 0.95rem;
}

.logged-out-actions {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.cookie-note {
  font-size: 0.8rem;
  color: #999;
  margin-top: 0.5rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-primary {
  background-color: #0a783c;
  color: white;
}

.btn-primary:hover {
  background-color: #086332;
}

.btn-secondary {
  background-color: #e9ecef;
  color: #333;
}

.btn-secondary:hover {
  background-color: #dee2e6;
}

.signed-in-notice {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  text-align: center;
  max-width: 360px;
}

.signed-in-notice p {
  color: #555;
  margin-bottom: 1rem;
  font-size: 0.95rem;
}

.dashboard-link {
  display: inline-block;
  padding: 0.75rem 1.5rem;
  background-color: #0a783c;
  color: white;
  text-decoration: none;
  border-radius: 4px;
  font-size: 0.95rem;
  transition: background-color 0.2s;
}

.dashboard-link:hover {
  background-color: #086332;
}

/* ===== SIGNUP PROMPT ===== */
.signup-prompt {
  text-align: center;
  font-size: 0.85rem;
  color: #555;
  margin-top: 0.75rem;
}

.signup-link {
  color: #1a6e3a;
  font-style: italic;
  font-weight: 600;
  text-decoration: none;
}

.signup-link:hover { text-decoration: underline; }

/* ===== FOOTER ===== */
.login-footer {
  text-align: center;
  font-size: 0.72rem;
  color: #aaa;
  margin-top: 1.5rem;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
  .login-wrapper { flex-direction: column; }
  .left-panel  { width: 100%; min-height: 40vh; }
  .right-panel { width: 100%; min-height: 60vh; }
  .seal-watermark { width: 260px; height: 260px; }
  .brand-title { font-size: 2rem; }
  .welcome-heading { font-size: 1.6rem; }
>>>>>>> bc882ef93b9a3d481a3bbd1e8f31f6f4ee910779
}
</style>
