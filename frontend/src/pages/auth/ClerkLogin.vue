<!-- ===== Clerk Login Page ===== -->
<template>
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
      </div>
    </div>

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
  </div>
</template>

<script setup>
import { watch, ref } from 'vue';
import { useRouter } from 'vue-router';
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
  }
});
</script>

<style scoped>
* { margin: 0; padding: 0; box-sizing: border-box; }

.login-wrapper {
  display: flex;
  min-height: 100vh;
  width: 100%;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

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
  overflow: hidden;
  min-height: 100vh;
}

.left-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(160deg, rgba(10, 120, 60, 0.82) 0%, rgba(5, 80, 35, 0.88) 100%);
}

.left-content {
  position: relative;
  z-index: 2;
  text-align: center;
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
  display: flex;
  align-items: center;
  justify-content: center;
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
}

.right-content {
  position: relative;
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
}
</style>
